<?php

use App\Lib\Api\ApiBase;

class IndexApi extends ApiBase{
    /**
     *  permission 未登录可访问
     *  allow 登录访问
     **/
    public function _initialize(){
    }

    public function syncCustomerLntLat(){

    }

    //公告
    public function test(){
        $customer_list = M('customer')->field(['customer_id','address'])->where(['longitude' => 0,'latitude' => 0])->select();

        import('@.ORG.AMap');

        foreach ($customer_list as $customer) {

            $result  = AMap::getGeoCodes(str_replace("\n","",$customer['address']));
            $lnt_lat_string = explode(',',$result['geocodes'][0]['location']);

            $data = [
                'customer_id' => $customer['customer_id'],
                'longitude' => floatval($lnt_lat_string[0]),
                'latitude' => floatval($lnt_lat_string[1]),
            ];

            M('customer')->save($data);
        }
    }

    //公告
    public function customer(){
        header('Access-Control-Allow-Origin:*');
        $customer_list = M('customer')->field(['customer_id', 'name', 'address', 'longitude', 'latitude'])->select();
        echo json_encode($customer_list);
    }

    public function district()
    {
        import('@.ORG.TencentMap');

        $this->ajaxReturn(TencentMap::getDistrictList()) ;
    }

    /**
     * 获取版本信息
     */
    public function getVersion()
    {
        $version = [
            'version'      => '1.3.0',
            'url'          => 'http://mcrm.lyfz.net/download/app.apk/v/1.1',
            'forceUpdate'  => false
        ];
        $this->ajaxReturn($version);
    }
}
