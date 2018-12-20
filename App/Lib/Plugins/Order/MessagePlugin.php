<?php

namespace App\Lib\Plugins\Order;

use App\Lib\Plugins\Message\Gateway;

class MessagePlugin {
    public function onOrderReceiving($receivingInfo, $pluginParams) {
        $recharge_config = unserialize($pluginParams);
        $order_receiving_info = M('receivables')->alias('orders')
            ->where(['orders.receivables_id' => $receivingInfo['receivables_id'], 'receiving.status' => 1])
            ->join(C('DB_PREFIX').'receivingorder receiving on orders.receivables_id = receiving.receivables_id')
            ->field('orders.price as need_pay, sum(receiving.money) as receivied')->find();

        if (floatval($order_receiving_info['receivied']) >= floatval($order_receiving_info['need_pay'])){
            $account = Gateway::getAccount($recharge_config['account']+'');
            if ($recharge_config['recharge_count'] < 0){
                return false;
            }elseif(!(M('pluginSmsRechargeRecord')->where('order_id = %d', $receivingInfo['receivables_id'])->find())){
                $response = $account->smsRecharge(floatval($order_receiving_info['receivied']), intval($recharge_config['recharge_count']), $recharge_config['remark']);
                if (200 == $response['code']){
                    $rechargeRecord = [
                        'message_account' => $recharge_config['account'],
                        'money' => $order_receiving_info['receivied'],
                        'recharge_count' => $recharge_config['recharge_count'],
                        'order_id' => $receivingInfo['receivables_id'],
                        'recharge_time' => time(),
                        'status' => 1
                    ];
                    M('pluginSmsRechargeRecord')->add($rechargeRecord);
                }else{
                    return $response;
                }
            }
        }
    }
}