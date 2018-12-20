<?php
/**
 * 短信接口
 */
class Message {
    /**
     * 发送短信
     * @param $phone 发送的电话号码
     * @param $content 短信内容
     */
    public function sendMessage($phone,$content){
        if($phone == ''){
            $res->meta->code = 400;
            $res->meta->message = '发送手机号码为空！';
            return $res;
        }
        
        if($content == ''){
            $res->meta->code = 400;
            $res->meta->message = '发送内容为空！';
            return $res;
        }
        
        return $this->requestSendMessageApi($phone,$content);
    }
    
    /**
     * 获取配置选项信息
     */
    private function getAccount(){
        $sms = unserialize(M('Config')->where('name = "sms"')->getField('value'));
        
        return array(
            'messageGatewayUrl' => $sms['sign_name'],
            'account' => $sms['uid'],
            'password' => $sms['passwd']
        );
    }
    
    /**
     * 解析短信网关返回xml数据
     * @param $xml_str xml数据
     */
    private function analysisData($xml_str){
        $str = json_decode(json_encode(simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA)), true);	
        $arr = explode('/', $str[0]); 

        return array(
            'code'=>$arr[0],
            'Balance'=>trim($arr[2],'Balance:'),
            'SMSCharacterCount'=>trim($arr[3],'SMSCharacterCount:'),
            'Signatures'=>trim($arr[4],'Signatures:'),
            'OwnedOperators'=>trim($arr[5],'OwnedOperators:'),
        );
    }
    
    /**
     * 请求发送短信API
     * @param $phone 发送的电话号码
     * @param $content 短信内容
     */
    private function requestSendMessageApi($phone,$content){
        //发送数据初始化
        $time = date('Y-m-d H:i:s');
        $t = date('YmdHis');
        $account = $this->getAccount()['account'];
        $password = $this->getAccount()['password'];
        $messageGatewayUrl = $this->getAccount()['messageGatewayUrl'];
        if($account == '' || $password == '' || $messageGatewayUrl == ''){
            $res->meta->code = 400;
            $res->meta->message = '短信网关配置信息填写不完整！';
            return $res;
        }
        
        //组装数据
        $request_data = $messageGatewayUrl.'webService/SendSmsMessage?account='.$account.'&password='.urlencode($password).'&phone='.$phone.'&content='.urlencode($content).'&time='.urlencode($time).'&t='.$t.'';

        //请求api
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $request_data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET"
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);

        if($err){
            $res->meta->code = 400;
            $res->meta->message = $err;
        }else{
            $analysis_res = $this->analysisData($response);
            if($analysis_res['code'] == 0 || $analysis_res['code'] == 100){
                $res->meta->code = 200;
                $res->meta->message = '成功';
            }else{
                $res->meta->code = 400;
                $res->meta->message = '短信发送失败code:'.$analysis_res['code'];
            }
        }
        
        return $res;
    }
}