<?php

namespace App\Utils\Wechat\Enterprise;

use App\Utils\WeixinCrypt\WXBizMsgCrypt;
/**
 * 微信企业号操作类,目前仅根据利亚方舟实际情况实现部分接口
 * 已实现 
 *     获取TOKEN
 *     发送文本消息
 *     关注与取消关注事件
 *     获取用户列表
 *     获取用户信息
 *     获取部门信息
 */
class Application {
    protected $corpid = '';
    protected $corpsecret = '';
    protected $encodingAesKey = '';
    protected $token = '';

    protected $access_token = "";
    protected $API_BASE = "\https://qyapi.weixin.qq.com/cgi-bin";
    protected $wxcpt;

    protected $request_object;

    public function __construct($config){
        $this->config = $config;
        $this->config['base'] = [
            'corpid' => C('WX_ENTERPRISE_CORPID'),
            'corpsecret' => C('WX_ENTERPRISE_CORPSECRIT'),
            'encodingAesKey' => C('WX_ENTERPRISE_ENCODING_AES_KEY'),
            'token' => C('WX_ENTERPRISE_TOKEN'),
        ];

        $this->corpid = C('WX_ENTERPRISE_CORPID');
        $this->corpsecret = C('WX_ENTERPRISE_CORPSECRIT');
        $this->encodingAesKey = C('WX_ENTERPRISE_ENCODING_AES_KEY');
        $this->token = C('WX_ENTERPRISE_TOKEN');

        $this->getAccessToken();
        include_once "./App/Lib/ORG/WXBizMsgCrypt/WXBizMsgCrypt.php";
        $this->wxcpt = new WXBizMsgCrypt($this->token, $this->encodingAesKey, $this->corpid);

        $this->request_object = $this->getMessage();
    }

    /**
     * 获取AccessToken，一般情况下不用手动调用
     * @param  boolean $forceRefresh 强制刷新AccessToken 否则缓存7200秒
     * @return String                获取到或者缓存AccessToken
     */
    public function getAccessToken($forceRefresh = false) {
        $this->access_token = S('WeixinEnterpriseAccessToken');
        if (!$this->access_token || $forceRefresh){
            $result = \Http::sendRequest($this->API_BASE."/gettoken?corpid=$this->corpid&corpsecret=$this->corpsecret");
            $this->access_token = $result["access_token"];
            S('WeixinEnterpriseAccessToken', $result["access_token"], 7200);
        }
        return $this->access_token;
    }

    /**
     * 给企业成员推送文本消息
     * @param  String        $type    目标类型 user
     * @param  Array|String  $target  目标Id
     * @param  Int           $agentId 应用号
     * @param  String        $content 推送内容
     * @return Object          请求相应内容 
     */
    public function sendTextMessage($type, $targetId, $content, $agentId=1000028){
        if(!in_array($type, ['user', 'party', 'tag'])){
            throw new Exception("Type Error");
        }

        $data = [
            "to".$type => is_array($targetId)?join('|', $targetId):$targetId,
            "msgtype" => "text",
            "agentid" => $agentId,
            "text" => [
                "content" => $content
            ],
            "safe" => 0
        ];
        return \Http::sendRequest($this->API_BASE."/message/send?access_token=$this->access_token", $data, [], true);
    }
    /**
     * 获取单个成员信息
     * @param  String $userid 成员id
     * @return Array          成员信息，结构参见微信官方文档
     */
    public function getMemberInfo($userId) {
        return \Http::sendRequest($this->API_BASE."/user/get?access_token=$this->access_token&userid=$userId");
    }
    /**
     * 获取部门信息
     * @return Array          部门信息，结构参见微信官方文档
     */
    public function getDepartments() {
        return \Http::sendRequest($this->API_BASE."/department/list?access_token=$this->access_token")["department"];
    }
    /**
     * 获取成员列表
     * @return Array          成员列表，结构参见微信官方文档
     */
    public function getMembers($departmentId, $fetchChild=0, $status=1){
        return \Http::sendRequest($this->API_BASE."/user/list?access_token=$this->access_token&department_id=$departmentId&status=$status&fetch_child=$fetchChild");
    }
    /**
     * 获取微信Post过来的数据
     * @param  boolean $needDecrypt         线上数据经过加密,需要解密,但是测试时不需要解密
     * @param  boolean $needReturnXmlObject 是否需要返回XML对象,
     * @return String|Object                返回解密后的数据或对象
     */
    public function getMessage($needDecrypt=true, $needReturnXmlObject = true){
        // post请求的原数据
        $message_raw = file_get_contents("php://input");
        if (!$needDecrypt){
            return $message_raw;
        }

        $msg_signature = $_GET['msg_signature'];;
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];

        if (isset($_GET['echostr'])) {
            /* 接入认证 */
            $echostr_encrypted = $_GET['echostr'];
            $echostr_decrypted = '';
            $err_code = $this->wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr_encrypted, $echostr_decrypted);
            if ($err_code == 0) {
                echo $echostr_decrypted;
            } else {
                print("ERR: " . $err_code . "\n\n");
            }
            exit;
        }

        $message_decrypted = "";  // 解析之后的明文
        $err_code = $this->wxcpt->DecryptMsg($msg_signature, $timestamp, $nonce, $message_raw, $message_decrypted);

        if ($needReturnXmlObject){
            return simplexml_load_string($message_decrypted, null, LIBXML_NOCDATA);
        } else {
            return $message_decrypted;
        }
    }
    
    protected $event_list = [];

    public function on($eventName, $callback){
        $this->event_list[$eventName][] = $callback;
        return $this;
    }

    public function serve(){
        if ($this->request_object->MsgType == 'event'){
            foreach ($this->event_list[$this->request_object->Event->__toString()] as $callback) {
                $callback && $callback($this->request_object);
            }
        }
        // if ($this->request_object->MsgType == 'location'){
        //     foreach ($this->event_list['location'] as $callback) {
        //         $callback && $callback($this->request_object);
        //     }
        // }
    }
    protected $response_templates = [
        'text' => "<xml>
           <ToUserName><![CDATA[%s]]></ToUserName>
           <FromUserName><![CDATA[%s]]></FromUserName> 
           <CreateTime>%s</CreateTime>
           <MsgType><![CDATA[text]]></MsgType>
           <Content><![CDATA[%s]]></Content>
        </xml>"
    ];

    public function response($type, $content, $needEncrypt=true){
        $timestamp = time();
        $origin_reply_message = sprintf($this->response_templates[$type], 
            $this->request_object->FromUserName->__toString(),
            $this->request_object->ToUserName->__toString(),
            $timestamp,
            $content
        );
        $reply_message = $origin_reply_message;
        if ($needEncrypt){
            $nonce = rand(100000,200000);
            $this->wxcpt->EncryptMsg($origin_reply_message, $timestamp, $nonce, $reply_message);
        }
        echo $reply_message;
    }

    public function oauth($config = []){
        $config = array_merge($this->config['base'], $config);
        return new Oauth($config);
    }
}