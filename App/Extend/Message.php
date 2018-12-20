<?php
namespace App\Extend;

/**
 * 流程短信发送接口
 */
class Message
{

    const ORDER_MESSAGE = 2;//订单短信 添加订单时发送 Model/Order里面使用
    const SHIPMENTS = 3;//发货 CustomerAction/express中
    const BEFORE_INSTALLATION = 4;//安装前
    const AFTER_INSTALLATION = 5;//安装后
    const INSTALLATION_ONE_DAY_AWAY = 6;//安装离店后一天
    const INSTALLATION_THREE_DAYS_AWAY = 7;//安装后3天
    const INSTALLATION_SEVEN_DAYS_AWAY = 8;//安装后7天
    const INSTALLATION_FIFTEEN_DAYS_AWAY = 9;//安装后15天
    const INSTALLATION_ONE_MONTH_AWAY = 10;//安装一个月后
    const INSTALLATION_TWO_MONTHS_AWAY = 11;//安装两个月后

    const SCHEDULED_TO_COMPLETE = 12; // 调度完成

    protected $codeStatus = [
        100 => '服务器授理并等待发送中',
         0  => '成功',
        -1  => '当前账号余额不足',
        -2  => '当前账号错误',
        -3  => '当前密码错误',
        -4  => '参数不够或参数内容的类型错误',
        -5  => '手机号码格式不对',
        -6  => '短信内容编码不对',
        -7  => '短信内容含有敏感字符',
        -8  => '无接收数据',
        -9  => '系统维护中',
        -10 => '手机号码数量超长_每次最多5000个',
        -11 => '短信内容超长_每条390个字符',
        -12 => '其它错误',
        -13 => '服务器错误',
        -14 => '域名不正确',
        -15 => '域名所在服务器未提交过IP',
        -16 => '硬件码不能小于5个字符',
        -17 => '服务器连接失败',
        -18 => '硬件码不能为空',
        -19 => '查询的短信记录不存在或已被删除',
        -20 => '短信超时过期忽略发送',
        -21 => '提交异常_请联系服务商解决',
    ];

    /**
     * 查询账户余额接口
     * @return mixed
     */
    public function queryBalance()
    {
        $params = [
            'account'  =>  $this->getAccount()['account'],
            'password' =>  strtoupper(md5($this->getAccount()['account'].$this->getAccount()['password'])),
        ];
        return Http::PostJson($this->getAccount()['messageGatewayUrl'].'QueryBalance', $params);
    }

    /**
     * 获取配置选项信息
     */
    public function getAccount()
    {
        $sms = unserialize(M('Config')->where('name = "message"')->getField('value'));

        return array(
            'messageGatewayUrl' => $sms['messageGatewayUrl'],
            'account' => $sms['account'],
            'password' => $sms['password']
        );
    }
    /**
     * 发送短信
     * @param string $phone
     * @param string $content
     * @return object
     */
    public function sendMessage($phone='', $content='')
    {
        $res = (object)[];
        if($phone == ''){
            $res['code'] = -1;
            $res['info'] = '发送手机号码为空！';
            return $res;
        }

        if($content == ''){
            $res['code']= -1;
            $res['info'] = '发送内容为空！';
            return $res;
        }
        $params = [
            'account'  =>  $this->getAccount()['account'],
            'password' =>  strtoupper(md5($this->getAccount()['account'].$this->getAccount()['password'])),
            'phone'    =>  $phone,
            'content'  =>  $content,
            'time'     =>  date('Y-m-d H:i:s')
        ];
        return Http::PostJson($this->getAccount()['messageGatewayUrl'].'SendMessage', $params);
    }

    /**
     * 流程短信发送
     * @param $customer_id
     * @param $template_id
     * @param int $order_product_id //唯一确定产品
     * @param null $express_id
     * @param null $door_service_id
     * @param int $role_id
     * @return bool|object
     */
    public function sendProcessMessage($customer_id,  $template_id, $order_product_id=0, $express_id=null, $door_service_id=null,$role_id=1)
    {
        if(2==M('sms_template')->where('template_id=%d', $template_id)->getField('is_auto_send')){//等于2就不发送短信
            return false;
        }
        if ($order_product_id){
            $where['order_product.id'] = $order_product_id;
        }
        $contactsInfo = M('customer')->alias('customer')
            ->field('contacts.name contacts_name, contacts.telephone, customer.name customer_name, receivables.name product_name, onsiteservice.operator_name')
            ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id=customer.contacts_id')
            ->join(C('DB_PREFIX').'receivables receivables ON receivables.customer_id=customer.customer_id')
            ->join(C('DB_PREFIX').'onsiteservice onsiteservice ON onsiteservice.customer_id=customer.customer_id')
            ->join(C('DB_PREFIX').'r_order_product order_product ON order_product.customer_id=customer.customer_id')
            ->where($where)
            ->where('customer.customer_id = %d', $customer_id)
            ->find();
        if ($express_id){//兼容发货关键字匹配
            $expressInfo = M('express')->where('express_id = %d', $express_id)->find();
            $patterns = ['/{contacts_name}/', '/{product_name}/', '/{express_company}/', '/{receiving_num}/'];
            $replacements = [$contactsInfo['contacts_name'], $contactsInfo['product_name'], $expressInfo['express_company'], $expressInfo['express_number']];
        }else{
            if ($door_service_id)
                $shortUrl = Http::urlLongToShort('http://mcrm.lyfz.net/index.php/doorservice/getProblemList?id='.$door_service_id)[0]['url_short'];
            else
                $shortUrl = '';
            $patterns = ['/{contacts_name}/', '/{product_name}/', '/{operator_name}/', '/{current_time}/', '/{url}/'];
            $replacements = [$contactsInfo['contacts_name'], $contactsInfo['product_name'], $contactsInfo['operator_name'], date('Y-m-d H:i'), $shortUrl];
        }
        
        $smsTemplate = M('sms_template') -> where('template_id = %d', $template_id) -> getField('content');

        switch ($template_id){
            case self::ORDER_MESSAGE:
                $smsTemplate = M('sms_template') -> where('template_id = %d', self::ORDER_MESSAGE) -> getField('content');
                break;
            case self::SHIPMENTS:
                $smsTemplate = M('sms_template') -> where('template_id = %d', self::SHIPMENTS) -> getField('content');
                break;
            case self::BEFORE_INSTALLATION:
                $smsTemplate = M('sms_template') -> where('template_id = %d', self::BEFORE_INSTALLATION) -> getField('content');
                break;
            case self::AFTER_INSTALLATION:
                $smsTemplate = M('sms_template') -> where('template_id = %d', self::AFTER_INSTALLATION) -> getField('content');
                break;
            case self::INSTALLATION_ONE_DAY_AWAY:
                $smsTemplate = M('sms_template') -> where('template_id = %d', self::INSTALLATION_ONE_DAY_AWAY) -> getField('content');
                break;
            case self::INSTALLATION_THREE_DAYS_AWAY:
                $smsTemplate = M('sms_template') -> where('template_id = %d', self::INSTALLATION_THREE_DAYS_AWAY) -> getField('content');
                break;
            case self::INSTALLATION_SEVEN_DAYS_AWAY:
                $smsTemplate = M('sms_template') -> where('template_id = %d', self::INSTALLATION_SEVEN_DAYS_AWAY) -> getField('content');
                break;
            case self::INSTALLATION_FIFTEEN_DAYS_AWAY:
                $smsTemplate = M('sms_template') -> where('template_id = %d', self::INSTALLATION_FIFTEEN_DAYS_AWAY) -> getField('content');
                break;
            case self::INSTALLATION_ONE_MONTH_AWAY:
                $smsTemplate = M('sms_template') -> where('template_id = %d', self::INSTALLATION_ONE_MONTH_AWAY) -> getField('content');
                break;
            case self::INSTALLATION_TWO_MONTHS_AWAY:
                $smsTemplate = M('sms_template') -> where('template_id = %d', self::INSTALLATION_TWO_MONTHS_AWAY) -> getField('content');
                break;
            default:
                $smsTemplate = '';
                break;
        }
        $content = preg_replace($patterns, $replacements, $smsTemplate);
        if ($express_id){
            $telephone = M('express')->where('express_id = %d', $express_id)->getField('telephone');
            if (!empty($telephone))//发货时可能用的不是客户默认的电话，就读取新写的号码
            {
                $messageInfo = $this->sendMessage($telephone, $content);
                $contactsInfo['telephone'] = $telephone;
            }
            else $messageInfo = $this->sendMessage($contactsInfo['telephone'], $content);
        }else{
            $messageInfo = $this->sendMessage($contactsInfo['telephone'], $content);
        }
        if ($messageInfo['code']==200 && !$this->isExist($contactsInfo['telephone'], $content)){  // 实际没发的短信不写入数据库
            $record = ['content'=>$content, 'telephone'=>$contactsInfo['telephone'], 'sendtime'=>time(), 'to_customer_id'=>$customer_id, 'status'=>$this->codeStatus[$messageInfo['data']['code']]??'',
                'to_customer_name'=>$contactsInfo['customer_name'], 'to_contacts_name'=>$contactsInfo['contacts_name'], 'role_id'=>$_SESSION['role_id']??$role_id];
            M('sms_record')->add($record);
            return $messageInfo;//发送成功
        }else{
            return $messageInfo;//发送失败
        }
    }

    /**
     * 发货短信
     * @param $expressInfo
     * @param int $role_id
     * @return bool|object
     */
    public function sendExpressMessage($expressInfo, $role_id=1)
    {
        $smsTemplate = M('sms_template') -> where('template_id = %d', self::SHIPMENTS)->field('content, is_auto_send')->find();
        if ($smsTemplate['is_auto_send'] == 2) {
            return false;
        }
        $patterns = ['/{contacts_name}/', '/{product_name}/', '/{express_company}/', '/{receiving_num}/'];
        $replacements = [$expressInfo['receiver'], $expressInfo['content'], $expressInfo['express_company'], $expressInfo['express_number']];
        $content = preg_replace($patterns, $replacements, $smsTemplate['content']);
        $customer_name = M('customer')->where(['customer_id'=>$expressInfo['customer_id']])->getField('name');
        $messageInfo = $this->sendMessage($expressInfo['telephone'], $content);

        if ($messageInfo['code']==200  && !$this->isExist($expressInfo['telephone'], $content)){  // 实际没发的短信不写入数据库
            $record = ['content'=>$content, 'telephone'=>$expressInfo['telephone'], 'sendtime'=>time(), 'to_customer_id'=>$expressInfo['customer_id'], 'status'=>$this->codeStatus[$messageInfo['data']['code']]??'',
                'to_customer_name'=>$customer_name, 'to_contacts_name'=>$expressInfo['receiver'], 'role_id'=>$_SESSION['role_id']??$role_id];
            M('sms_record')->add($record);
            return $messageInfo;//发送成功
        }else{
            return $messageInfo;//发送失败
        }
    }

    /**
     * 给上门老师发短信
     * @param $template_id
     * @param $door_service_id
     * @return bool|object
     */
    public function sendMessageToTeacher($template_id, $door_service_id)
    {
        if(2==M('sms_template')->where('template_id=%d', $template_id)->getField('is_auto_send')){//等于2就不发送短信
            return false;
        }
        $data = M('onsiteservice')->alias('onsiteservice')
            ->field('contacts.name contacts_name, contacts.telephone contacts_telephone, customer.name customer_name,onsiteservice.submitter manager_name,user.name operator_name,user.telephone,user.user_id,salesman.name salesman, salesman.telephone salesman_telephone')
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = onsiteservice.customer_id')
            ->join(C('DB_PREFIX').'contacts contacts ON contacts.contacts_id=customer.contacts_id')
            ->join(C('DB_PREFIX').'user user ON user.user_id = onsiteservice.tid')
            ->join(C('DB_PREFIX').'user salesman ON salesman.user_id = onsiteservice.sid')
            ->where('onsiteservice.id = %d', $door_service_id)
            ->find();

        if (!empty($data['telephone'])){
            $patterns = ['/{operator_name}/', '/{manager_name}/', '/{customer_name}/', '/{contacts_name}/', '/{contacts_telephone}/', '/{salesman}/', '/{salesman_telephone}/'];
            $replacements = [$data['operator_name'], $data['manager_name'], $data['customer_name'], $data['contacts_name'], $data['contacts_telephone'], $data['salesman'], $data['salesman_telephone']];
            switch ($template_id){
                case self::SCHEDULED_TO_COMPLETE:
                    $smsTemplate = M('sms_template') -> where('template_id = %d', self::SCHEDULED_TO_COMPLETE) -> getField('content');
                    break;
                default:
                    $smsTemplate = '';
                    break;
            }
            $content = preg_replace($patterns, $replacements, $smsTemplate);
            $messageInfo = $this->sendMessage($data['telephone'], $content);
            if ($messageInfo['code']==200 && !$this->isExist($data['telephone'], $content)){ // 实际没发的短信不写入数据库
                $record = ['content'=>$content, 'telephone'=>$data['telephone'], 'sendtime'=>time(), 'to_customer_id'=>$data['user_id'], 'status'=>$this->codeStatus[$messageInfo['data']['code']]??'',
                    'to_customer_name'=>$data['operator_name'], 'to_contacts_name'=>$data['operator_name'], 'role_id'=>$_SESSION['role_id']];
                M('sms_record')->add($record);
                return $messageInfo;//发送成功
            }else{
                return $messageInfo;//发送失败
            }
        }else{
            return false;
        }
    }

    /**
     * 判断数据库是否存在此短信
     * @param $telephone
     * @param $content
     * @param int $days 相同号码 相同内容可再次发送的时间
     * @return mixed
     */
    public function isExist($telephone, $content, $days = 7)
    {
        return M('sms_record')->where(['telephone'=>$telephone, 'content'=>$content, 'sendtime'=>['egt', time()-$days*86400]])->find();
    }
}