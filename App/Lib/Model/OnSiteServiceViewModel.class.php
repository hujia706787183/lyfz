<?php
class OnSiteServiceViewModel extends ViewModel{
    public $viewFields = array(
        'onsiteservice' => [
           'customer_id','customer_name','contacts_name','sid','addtime','manyidu'
        ],
        'user' => [
            'name'=>'user_name',
            'role_id',
            'user_id',
            '_on'=>'user.user_id=onsiteservice.sid'
        ],
        'role' => [
            '_on' => 'role.role_id = user.role_id'
        ],
        'position'=>[
            'department_id',
            '_on'=>'position.position_id=role.position_id'
        ],
        'role_department'=>[
            'name'=>'department_name',
            '_on' =>'role_department.department_id=position.department_id'
        ]
    );
}