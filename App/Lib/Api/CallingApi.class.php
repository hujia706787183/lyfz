<?php

use App\Lib\Api\ApiBase;
use App\Extend\Message;

class CallingApi extends ApiBase {
    public function sendMessage($tel, $content) {

        $messenger = new Message();
        $messenger->sendMessage($tel, $content);

        $record = [
            'content' => $content,
            'telephone' => $tel,
            'sendtime' => time(),
            'role_id' => $this->user()['role_id']
        ];
        M('sms_record')->add($record);
    }
    protected function sendCustomerCallInMessage($tel) {
        if (C('customer_enable') == 'true'){
        $msgTempalte = C('call_in_msg_template');
            if (trim($msgTempalte)){
                $this->sendMessage($tel, $msgTempalte);
            }
         }
         return $this->ajaxReturn();
    }
    protected function sendUnknownCallInMessage($tel) {
        if (C('unknown_enable') == 'true'){
            $msgTempalte = C('unknown_call_in_msg_template');
            if (trim($msgTempalte)){
                $this->sendMessage($tel, $msgTempalte);
            }
         }
         return $this->ajaxReturn();
    }
    protected function sendStaffCallInMessage($tel) {
        if (C('staff_enable') == 'true'){
            $msgTempalte = C('staff_call_in_msg_template');
            if (trim($msgTempalte)){
                $this->sendMessage($tel, $msgTempalte);
            }
        }
        return $this->ajaxReturn();
    }
    protected function sendMissedCallInMessage($tel) {
        if (C('missed_enable') == 'true'){
            $msgTemplate = C('missed_call_in_msg_template');
            if (trim($msgTemplate)){
                $this->sendMessage($tel, $msgTemplate);
            }
        }
        return $this->ajaxReturn();
    }
    public function addRecord() {
        $fields = [ 'type', 'tel', 'record_uri', 'desc' , 'calltime' ];
        /* 过滤 */
        $recordInfo = array_intersect_key(I('post.'), array_flip($fields));
        
        $recordInfo['role_id'] = $this->user()['role_id'];

        if (empty($recordInfo['tel'])) {
             $this->ajaxReturn(null, -1, '缺少 tel 参数');
        }

        $contactsId = M('contacts')->where(['telephone' => $recordInfo['tel']])->getField('contacts_id');
        if ($contactsId){
            $recordInfo['contact_id'] = $contactsId;
        }

        if ($recordInfo['calltime']){
            $recordInfo['calltime'] = date('Y-m-d H:i:s', $recordInfo['calltime']);
        }

        $recordId = M('calling_record')->add($recordInfo);

        if ($recordId === false) {
            return $this->debugReturn(M()->getLastSql());
        }
    }

    public function sendCallMessage($tel, $missed=false)
    {
        // if ($missed){
        //     return $this->sendMissedCallInMessage($tel);
        // }else {
            if (empty($tel))
                $this->ajaxReturn(null, -1, '缺少 tel 参数');

            if (M('contacts')->where(['telephone' => $tel])->getField('contacts_id'))
                return $this->sendCustomerCallInMessage($tel);
            elseif (M('staff')->where(['telephone' => ['like', "%$tel%"]])->count())
                return $this->sendStaffCallInMessage($tel);
            else
                return $this->sendUnknownCallInMessage($tel);
        // }
    }


    public function getRecordList ($customer_id) {
        if ($customer_id) {
            $list = M('r_contacts_customer')
            ->alias('contacts_customer')
            ->join('inner join '.C('DB_PREFIX').'calling_record calling_record on calling_record.contact_id = contacts_customer.contacts_id')
            ->join(C('DB_PREFIX').'contacts contacts on contacts.contacts_id = contacts_customer.contacts_id')
            ->join(C('DB_PREFIX').'user user on user.role_id = calling_record.role_id')
            ->where(['customer_id' => $customer_id])
            ->field('contacts.name as contact_name, user.name as role_name,calling_record.tel, calltime, record_uri')
            ->select();
            // echo M()->getLastSql();
        } else {
            $list = M('calling_record')
            ->alias('calling_record')
            ->join(C('DB_PREFIX').'contacts contacts on contacts.contacts_id = calling_record.contact_id')
            ->join(C('DB_PREFIX').'user user on user.role_id = calling_record.role_id')
            ->field('contacts.name as contact_name, user.name as role_name,calling_record.tel, calltime, record_uri')
            ->select();
        }

        $this->ajaxReturn($list);
    }
}
