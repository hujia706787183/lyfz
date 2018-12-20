<?php

class GhostAction extends Action
{
    public function _initialize(){
        $action = array(
            'permission'=>['index'],
            'allow'=>[]
        );
        B('Authenticate', $action);
    }
    public function index()
    {
        session('user_url', $_SERVER['REQUEST_URI']);
        $tel = I('get.tel');
        $isapp = I('get.isapp', 0);
        $contacts = M('contacts')->alias('contacts')
            ->join(C('DB_PREFIX').'r_contacts_customer contacts_customer ON contacts_customer.contacts_id = contacts.contacts_id')
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = contacts_customer.customer_id')
            ->where(['telephone'=>$tel])
            ->select();
        $staff = M('staff')->where(['personal_call|telephone'=>$tel])->find();
        if ($staff){
            redirect(U('user/view_staff', ['id'=>$staff['id']]));
        }else{
            $contacts_count = count($contacts);
            if (!$isapp){
                if (1 == $contacts_count){
                    redirect(U('customer/view',['id'=>$contacts[0]['customer_id']]));
                }elseif(0 == $contacts_count){
                    redirect(U('contacts/add',['tel'=>$tel]));
                }else{
                    redirect(U('customer/index', ['act'=>'search', 'field'=>'contacts->telephone' ,'condition'=>'contains', 'search'=>$tel]));
                }
            }else{
                if (1 == $contacts_count){
                    redirect('http://'.I('server.HTTP_HOST').'/m/#/customer/details?id='.$contacts[0]['customer_id']);
                }elseif(0 == $contacts_count){
                    redirect('http://'.I('server.HTTP_HOST').'/m/#/customer/create?tel='.$tel);
                }else{
                    redirect('http://'.I('server.HTTP_HOST').'/m/#/customer/list?keyword='.$tel);
                }
            }
        }
    }
}