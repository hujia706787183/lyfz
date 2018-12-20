<?php

use App\Extend\Weixin\Enterprise as WeixinWork;

class WeixinEnterpriseApi extends Action {
    protected $weixin;

    public function __construct(){
        // import('@.ORG.WeixinEnterprise');
        // $this->weixin = new WeixinEnterprise();
    }
    //解析xml
    public function syncMemberByMobile(){
        $user = M('user')->getField('telephone,user_id');
        $all_weixin_user = $this->weixin->getMembers(1,1);
        foreach ($all_weixin_user['userlist'] as $item) {
            $data = [
                'user_id' => $user[$item['mobile']],
                'weixinid' => $item['userid']
            ];
            M('user')->save($data);
        }
    }

    public function contactsEvent(){

        $app = WeixinWork::getContactsApp();

        $app->server->push(function($message) use ($app) {
            $wxUserId = $message['UserID'];
            if ($message['Event'] == 'change_contact') {
                if ($message['ChangeType'] == 'update_user') {
                    $user = $app->user->get($wxUserId);
                    $userid = M('user')->where(['telephone' => $user['mobile']])->getField('user_id');
                    if ($userid){
                        M('user')->where(['user_id' => $userid])->save(['weixinid' => $user['userid']]);
                    }
                }
            }
        });

        $response = $app->server->serve();
        
        $response->send();
        // $this->weixin
        //     // 订阅
        //     ->on('subscribe', function($message){
        //         $memberInfo = $this->weixin->getMemberInfo($message->FromUserName);
        //         $userid = M('user')->where(['telephone' => $memberInfo['mobile']])->getField('user_id');
        //         if ($userid){
        //             $data = [
        //                 'user_id' => $userid,
        //                 'weixinid' => $memberInfo['userid']
        //             ];
        //             M('user')->save($data);
        //         }
        //     })
        //     // 取消订阅
        //     ->on('unsubscribe', function($message){
        //         $userid = M('user')->where(['weixinid' => $message->FromUserName->__toString()])->getField('user_id');
        //         if ($userid){
        //             $data = [
        //                 'user_id' => $userid,
        //                 'weixinid' => ''
        //             ];
        //             M('user')->save($data);
        //         }
        //     })
        //     // 地理位置
        //     // ->on('LOCATION', function($message){
        //         // $userid = M('user')->where(['weixinid' => $message->FromUserName->__toString()])->getField('user_id');
        //         // $timestamp = time();
        //         // $data = [
        //         //     "user_id" => $userid,   
        //         //     "x" => floatval($message->Latitude->__toString()),
        //         //     "y" => floatval($message->Longitude->__toString()),
        //         //     "precision" => floatval($message->Precision->__toString()),//偏差度
        //         //     "create_time" => $timestamp
        //         // ];

        //         // $content = file_get_contents("http://restapi.amap.com/v3/geocode/regeo?output=json&location=".$data['x'].",".$data['y']."&key=6aa768ed9bdd9a52295295df030729c3");
        //         // $json = json_decode($content);

        //         // $data['address'] =  $json->{'regeocode'}->{'formatted_address'};
        //         // // import('@.ORG.AMap');
        //         // // $regeo_codes = AMap::getRegeoCodes($data["y"].",".$data["x"]);
        //         // // $data['address'] = $regeo_codes['regeocode']['formatted_address'];
        //         // $result = M('sign')->data($data)->add();
                
        //         // if ($result){
        //         //     $this->weixin->response('text',"您已成功在位置{$data['address']}处签到成功");
        //         // }
        //     // })
        //     ->serve();
    }

    public function oauth() {
        $app = WeixinWork::getApp();
        $app->oauth->scopes(['snsapi_base'])->redirect('http://mcrm.lyfz.net/api.php/WeixinEnterprise/oauth_callback')->send();
    }

    public function oauth_callback () {
        $app = WeixinWork::getApp();
        var_dump($app->oauth->stateless()->user());
    }

    public function getSigns()
    {
        $work = WeixinWork::getSignApp();

        $today = strtotime(date('Ymd'));

        $user_list = M('user')->getField('weixinid', true);
        $this->ajaxReturn($work->oa->checkinRecords($today, $today + 24*60*60 - 1, $user_list, 2));

    }
}