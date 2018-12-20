<?php
namespace App\Lib\Abstracts;
abstract class ProductPlugin
{
    public $order_product_id;
    public $customer_id;
    public $order_product_info = [];

    public function __construct($order_product_id)
    {
        $this -> order_product_id = $order_product_id;
        $this -> getOrderProductExtra($order_product_id);
        $this -> customer_id = M('r_order_product')->where('id = %d', $order_product_id)->getField('customer_id');
    }

    public abstract function renderButton();

    /**
     * 设置产品信息的表单
     * @return mixed
     */
    public abstract function renderForm();

    /**
     * 输出对应产品的按钮
     * @return mixed
     */
    public abstract function echoButtons();

    /**
     * 查 r_order_product_extra表 获取对应的数据
     * @param $order_product_id
     */
    protected function getOrderProductExtra($order_product_id)
    {
        // 默认按照升序排列,然后查到对应的order_product_id 对应的相关信息，后面的值覆盖前面的值
        $orderProductExtraData = M('r_order_product_extra') -> where('order_product_id = %d', $order_product_id) -> getField('extra_key, extra_value') ;
        foreach ($orderProductExtraData as $extra_key => $extra_value){
            if ('message_account' == $extra_key && (strpos($extra_value, '/') !== false)){
                $message = explode('/', $extra_value);
                $this -> order_product_info[$extra_key] = $message[0];
                $this -> order_product_info['message_account_password'] = $message[1];
            }elseif('next_service_fee_date' == $extra_key && (strpos($extra_value, '-')===false)){
                $this -> order_product_info[$extra_key] = date('Y-m-d H:i:s', $extra_value);
            }else{
                $this -> order_product_info[$extra_key] = $extra_value;
            }
        }
    }


}