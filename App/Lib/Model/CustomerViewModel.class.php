<?php

class CustomerViewModel extends ViewModel {
    protected $viewFields;
    public function _initialize(){
        $main_must_field = [
            'customer_id', 'owner_role_id',
            'is_locked', 'creator_role_id',
            'contacts_id', 'delete_role_id',
            'create_time', 'delete_time',
            'update_time', 'is_deleted'
        ];
          //已数组返回结果
        $main_list = array_unique(
            array_merge(
                M('Fields') ->where(['model'=>'customer', 'is_main'=>1])->getField('field', true),
                $main_must_field
            )
        );
         //dump($main_list);exit;
        //这里是查询到相应模式的customer，is_main不代表主键字段
        $data_list = M('Fields')
        ->where([
            'model'=>'customer',
            'is_main'=>0
        ])
        ->getField('field', true);
           // dump($data_list);exit;
        $data_list['_on'] = 'customer.customer_id = customer_data.customer_id';
        $main_list['_type'] = 'LEFT';
        $this->viewFields = [
            'customer'=>$main_list,
            'customer_data'=>$data_list
        ];
    }
}