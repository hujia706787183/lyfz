<?php
namespace App\Lib\Plugins\Product;
use App\Lib\Abstracts\ProductPlugin;
use App\Extend\Http;
use App\Lib\Model\Order;
use EasyWeChat\Core\Exception;

class ProductMessage extends ProductPlugin
{
    const BASE_URL = 'http://msg.lyfz.net:8600/ISmsService/';

    public function renderButton()
    {
        return [
            [
                'label'  => '查询',
                'action' => 'getCustomerAccount',
            ],
            [
                'label'  => '充值',
                'action' => 'smsRecharge',
            ],
            [
                'label'  => '添加/修改',
                'action' => 'customerSmsAccount',
            ],
            [
                'label'  => '充值记录',
                'action' => 'rechargeRecord',
            ],
//            [
//                'label'  => '删除',
//                'action' => 'deleteCustomer',
//            ],
        ];
    }

    public function renderForm()
    {
        return [
            [
                'label' => '短信账号',
                'name'  => 'message_account',
                'type'  => 'text',
            ],
            [
                'label' => '短信密码',
                'name'  => 'message_account_password',
                'type'  => 'text',
            ]
        ];
    }

    public function echoButtons()
    {
        $renderButtons = self::renderButton();
        $stringButtons = '';
        foreach ($renderButtons as $renderButton){
            if (empty($this->order_product_info['message_account'])){//规避账号为空的情况
                $stringButtons .= "<button type='button'  onclick='{$renderButton['action']}($this->order_product_id, 0)' class='btn btn-default btn-xs'>{$renderButton["label"]}</button>&nbsp;&nbsp;&nbsp;";
            }else{
                $stringButtons .= "<button type='button'  onclick='{$renderButton['action']}($this->order_product_id, \"{$this->order_product_info['message_account']}\")' class='btn btn-default btn-xs'>{$renderButton["label"]}</button>&nbsp;&nbsp;&nbsp;";
            }
        }
        return $stringButtons;
    }


    /**
     * 查询余额
     * @param null $account
     * @param null $password
     * @return mixed
     * @throws \Exception 查询异常
     */
    public static function queryBalance($account, $password)
    {
        $params = [
            'account'  =>  $account,
            'password' =>  strtoupper(md5($account.$password))
        ];
        $response = Http::PostJson(self::BASE_URL.'QueryLatestBalance', $params);
        if ($response['code'] == 200) {
            return $response['data'];
        } else {
            throw new \Exception($response['msg'], -1);
        }
    }

    /**
     * 充值单价
     * @return int
     */
    public static function smsPrice()
    {
       $response = Http::PostJson(self::BASE_URL.'SmsPrice');
        if ($response['code'] == 200) {
            return $response['data'];
        } else {
            return -1;
        }
    }

    public static function createOrder($order_product_id, $money, $owner_role_id = 1, $recharge_count = null, $remarks = 'test')
    {
        $rOrderProduct = M('ROrderProduct')->field('customer_id, owner_role_id')->where('id = %d', $order_product_id)->find();
        $order_number = 'O'.date('Ymd').mt_rand(1000,9999);
        $order_info = [
            'customer_id' => $rOrderProduct['customer_id'],
            'name' => '短信充值',
            'order_number' => $order_number,
            'price' => $money,
            'owner_role_id' => $owner_role_id,
            'creator_role_id' => 0,
            'create_time' => time(),
            'pay_time' => time(),
            'status' => 0,
        ];
        $order_id = M('receivables')->add($order_info);

        $messageAccount = M('r_order_product_extra') -> where('order_product_id = %d AND extra_key = "%s"', $order_product_id, 'message_account') ->order('id desc') -> getField('extra_value') ;

        if ((strpos($messageAccount, '/') !== false)){
            $message = explode('/', $messageAccount);
            $accountName = $message[0];
        }else{
            $accountName = $messageAccount;
        }
        if (empty($recharge_count)){//客户自己充
            $unit_price = self::smsPrice();
            $plugin_params = serialize(['account' => $accountName, 'recharge_count'=>intval(ceil($money/$unit_price)), 'money'=>$money, 'remarks'=>$remarks]);
        }else{//CRM充
            $plugin_params = serialize(['account' => $accountName, 'recharge_count'=>$recharge_count, 'money'=>$money, 'remarks'=>$remarks]);
        }

        $OrderPlugin = [
            'order_id' => $order_id,
            'plugin_id'     => 1,
            'plugin_name'   => 'MessagePlugin',
            'createtime'    => time(),
            'plugin_params' => $plugin_params,
        ];

        M('OrderPlugin')->add($OrderPlugin);

        return $order_id;
    }
}
