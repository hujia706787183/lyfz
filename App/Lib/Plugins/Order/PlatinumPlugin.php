<?php
namespace App\Lib\Plugins\Order;

class PlatinumPlugin {
    public function onOrderReceiving($receivingInfo, $pluginParams) {
        $recharge_config = unserialize($pluginParams);
        $order_receiving_info = M('receivables')->alias('orders')
            ->where(['orders.receivables_id' => $receivingInfo['receivables_id'], 'receiving.status' => 1])
            ->join(C('DB_PREFIX').'receivingorder receiving on orders.receivables_id = receiving.receivables_id')
            ->field('orders.price as need_pay, sum(receiving.money) as receivied')->find();

        $serviceRechargeRecord = M('pluginServiceRechargeRecord')->where('order_id = %d', $receivingInfo['receivables_id'])->find();//看是否有充值记录

        if (floatval($order_receiving_info['receivied']) >= floatval($order_receiving_info['need_pay']) && !$serviceRechargeRecord){
            $next_service_fee_date = M('ROrderProductExtra')->where('extra_key="next_service_fee_date" AND order_product_id = %d', $recharge_config['order_product_id'])->order('id desc')->getField('extra_value');
            if ($next_service_fee_date){//在此次之前就有下次服务时间
                // $next_service_fee_date += $recharge_config['service_time'] * 365*24*3600;
                $next_service_fee_date = time() + $recharge_config['service_time'] * 365*24*3600; // 有下次服务时间还是按照当前时间添加
                M('ROrderProductExtra')->where('extra_key="next_service_fee_date" AND order_product_id = %d', $recharge_config['order_product_id'])->save(['extra_value'=>$next_service_fee_date]);

                $domainName = M('ROrderProductExtra')->where('extra_key="domain" AND order_product_id = %d', $recharge_config['order_product_id'])->order('id desc')->getField('extra_value');
                event('SetProductInfo', ['domain'=>$domainName, 'next_service_fee_date'=>date('Y-m-d', $next_service_fee_date)]);
            }else{
                $next_service_fee_date = time() + $recharge_config['service_time'] * 365*24*3600;
                M('ROrderProductExtra')->add(['order_product_id'=>$recharge_config['order_product_id'], 'extra_key'=>'next_service_fee_date', 'extra_value'=>$next_service_fee_date]);
            }
            $rechargeRecord = [
                'service_time' => $recharge_config['service_time'],
                'money' => $order_receiving_info['receivied'],
                'order_id' => $receivingInfo['receivables_id'],
                'recharge_time' => time(),
                'status' => 1
            ];
            M('pluginServiceRechargeRecord')->add($rechargeRecord);
        }
    }
}