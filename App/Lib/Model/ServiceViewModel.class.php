<?php
class ServiceViewModel extends ViewModel {
    public $viewFields = array(
        'service' => [
            'service_id','is_solve','customer_id','is_recycle_bin','fault_subpeople_id','return_result',
            'self_satisfaction','remote_time','operation_people_phone','operator_name','operator_qq','sale_people_id',
            'teacher_id','service_personal_id','visit_user_id','return_remarks','crm_anjazk','`desc`'
        ],
        'customer' => [
            '`name`'=>'customer_name',
            'address'=>'customer_address',
            'customer_id',
            '_on'=>'service.customer_id=customer.customer_id'
        ],
        'contacts' => [
            '`name`'=>'contacts_name',
            'telephone'=>'contacts_phone',
            '_on' => 'contacts.contacts_id = customer.contacts_id'
        ],
    );
}