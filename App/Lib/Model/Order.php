<?php

namespace App\Lib\Model;

// use App\Extend\Message;

class Order {
    protected static $table = 'receivables';
    protected $order_id = '';
    protected $customer_id = '';

    public function setOrderId($order_id){
        $this->order_id = $order_id;
    }

    public function getOrderId(){
        return $this->order_id;
    }

    public function setCustomerId($customer_id){
        $this->customer_id = $customer_id;
    }

    public function getCustomerId(){
        return $this->customer_id;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getOwnerRoleId() {
        return $this->owner_role_id;
    }

    public function setInfo($info){
        foreach($info as $key => $value){
            $this->$key = $value;
        }
    }
    /**
     * 查找一个对象
     * @param  [type] $order_id               订单id
     * @param  [type] $fetch_from_database    是否从数据库获取数据,如果只是更新,可能不需要获取原有数据
     * @return [type]                         [description]
     */
    public static function find($order_id, $fetch_from_database = true){
        $order = new Order();
        $order->setOrderId($order_id);

        if($fetch_from_database){
            $order_info = M(self::$table)->where(['receivables_id' => $order_id])->field('price, customer_id, owner_role_id')->find();
            $order->setInfo($order_info);
        }

        return $order;
    }

    public static function createOrder($order_info){
        $order_id = M(self::$table)->add($order_info);

        if ($order_id === false){
            throw new \Exception('CREATE ORDER FAILD');
        }

        $order = self::find($order_id, false);
        $order->setInfo($order_info);
        return $order;
    }

    /** 订单插件 */
    protected function invokeOrderPlugin($receiving){
        $order_id = $receiving['receivables_id'];

        $plugins = M('order_plugin')->where(['order_id' => $order_id])->select();

        foreach($plugins as $plugin){
            $pluginClassName = 'App\Lib\Plugins\Order\\'.$plugin['plugin_name'];

            $pluginInstance = new $pluginClassName();

            $pluginInstance->onOrderReceiving($receiving, $plugin['plugin_params']);
        }
    }

    public function getTotalReceivingMoney(){
        $db_receivings = M('Receivingorder');
        return $db_receivings->where(['receivables_id' => $this->order_id, 'is_deleted' => 0])->sum('money');
    }
    
    /*收款*/
    public function receiving($recevingInfo){
        
        $db_receivings = M('Receivingorder');
        $receiving_id = $db_receivings->add($recevingInfo);

        if ($receiving_id === false){
            throw new \Exception('ORDER RECEIVING FAILD');
        }
        
        if ($recevingInfo['status'] == 1) {
            $total_receiving = $this->getTotalReceivingMoney();

            $status = ($total_receiving >= $this->price) + 1;

            $affected_rows = M('receivables')->where(['receivables_id' => $this->order_id])->save(['status' => $status]);

            if ($affected_rows && $status == 2){
                $this->invokeOrderPlugin($recevingInfo);
                event('AddStocks', $this->order_id);
            }
        }

        return $receiving_id;
    }
    
    /* 添加产品 */
    public function addProduct($product_list, $creator_role_id){
        // $message = new Message();
        $product_info_list = M('product')
            ->where(['product_id' => ['in', array_keys($product_list)]])
            ->field('product_id,name as product_name,suggested_price')
            ->select();
        if (!($this->order_id && $this->customer_id)){
            throw new \Exception('OBJECT ERROR');
        }
        $platinum_ids = M('product')->where(['category_id' => ['in', '1,5,8']])->getField('product_id', true);//获取铂金版,黄金版,标准版产品id
        foreach ($product_info_list as $key => $product_info){
            $product_info_list[$key]['order_id'] = $this->order_id;
            $product_info_list[$key]['create_time'] = time();
            $product_info_list[$key]['customer_id'] = $this->customer_id;
            $product_info_list[$key]['price'] = $product_list[$product_info['product_id']] ?? $product_info['suggested_price'];
            $product_info_list[$key]['owner_role_id'] = $this->owner_role_id;
            $product_info_list[$key]['creator_role_id'] = $creator_role_id;

            $order_product_id = M('r_order_product')->add($product_info_list[$key]);

            $product_info_list[$key]['order_product_id'] = $order_product_id;

            event('ProductAddToCustomer', $product_info_list[$key]);
            // if (in_array($product_info['product_id'], $platinum_ids)){
            //     $message ->sendProcessMessage($this->customer_id, Message::ORDER_MESSAGE);//发订单短信
            // }
        }

        // $result = M('r_order_product')->addAll($product_info_list);

        if ($result === false){
            throw new \Exception('SAVE FAILD'); 
        }
    }

}