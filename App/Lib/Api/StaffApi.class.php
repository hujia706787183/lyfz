<?php

use App\Lib\Api\ApiBase;

class StaffApi extends ApiBase
{
    public function getList($department_id = 0, $details = 0){
        $db_user = M('User')->alias('user');

        $fields = [ 'user.name', 'user.user_id' ];

        if ($details){
            $fields = array_merge($fields, [ 'sex', 'email', 'telephone', 'address' ]);
        }

        if ($department_id){

            $conditions = [
                'position.department_id' => $department_id,
                'status' => 1
            ];

            if ($department_id == 13) {
                $conditions = [
                    '_complex' => $conditions,
                    '_logic' => 'OR',
                    'status' => 2
                ];
                $fields[] = '13 as department_id';
            } else {
                $fields[] = 'department_id';
            }

            $db_user
            ->join(C('DB_PREFIX').'role role on role.role_id=user.role_id')
            ->join(C('DB_PREFIX').'position position on position.position_id=role.position_id')
            ->where($conditions)
            ->order('convert(user.name using gbk)');

            
        }

        $user_list = $db_user->field($fields)->select();
        if ($department_id == 13){
            
        }
        $this->ajaxReturn($user_list);
    }

    public function location_report(){
        $report_info = [
            'x' => I('post.x'),
            'y' => I('post.y'),
            'address' => I('post.address'),
            'create_time' => time(),
        ];
        
        var_dump(M('sign')->select());
    }
}