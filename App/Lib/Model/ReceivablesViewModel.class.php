<?php 
	class ReceivablesViewModel extends ViewModel{
		public $viewFields = array(
			'receivables' => [
                'receivables_id','order_number','name','price','creator_role_id',
                'owner_role_id','delete_role_id','is_deleted','delete_time',
                'pay_time','contract_id','customer_id','create_time',
                'description','create_time','status',
                '_type'=>'LEFT'
            ],
			'customer' => [
                'name' => 'customer_name',
                'contacts_id' => 'contacts_id',
                '_on' => 'receivables.customer_id=customer.customer_id',
                '_type'=>'LEFT'
            ],
			'contract' => [
                'number' => 'contract_name',
                 '_on' => 'receivables.contract_id=contract.contract_id',
                '_type' => 'LEFT'
             ],
			'role' => [
                '_on' => 'receivables.creator_role_id=role.role_id',
                 '_type' => 'LEFT'
             ],
			'user' => [
                'name' => 'creator_name',
                 '_on' => 'role.user_id = user.user_id'
             ]
		);
	}