<?php
namespace App\Lib\Api;

class ApiBase extends \Action {

    protected $user = null;
    
    protected function _initialize(){
        C(M('config')->getField('name, value'));
        if (!session('?user_id')){
            $token = I('SERVER.HTTP_AUTHORIZATION');
            if ($token) {
                $user = M('user')->where(['token' => $token])->find();
                if (!$user) {
                    $user = M('customer_auth')->where(['token' => $token])->order('id desc')->find();
                    if ($user) {
                        $user['roles'] = ['customer'];
                    }
                }
            }
            $user && $this->user = $user;
        } else {
            $this->user = [
                'user_id' => session('user_id'),
                'role_id' => session('role_id')
            ];
        }
    }
    
    public function user($force_login = true) {
        if ($this->user) {
            return $this->user;
        } else {
            if ($force_login) {
                throw new \Exception('UNLOGIN', -10003);
            } else {
                return null;
            }
        }
    }

    public function ajaxReturn($data = null, $code = 0, $info = 'SUCC'){
        $responseResult = [
            'code' => $code,
            'info' => L($info),
        ];

        if (!empty($data)) {
            $responseResult['data'] = $data;
        }

        parent::ajaxReturn($responseResult);
    }

    public function debugReturn($info = 'REQUEST FAILED', $code = -1){

        $debug = true;
        
        $responseResult = [
            'code' => $code,
            'info' => $info,
        ];

        if (!$debug) {
            unset($responseResult['info']);
        }
        
        parent::ajaxReturn($responseResult);
    }

    public function checkPermission($params){
        if (session('?admin') || $this->user['category_id'] == 1) {
            return true;
        }


        $m = MODULE_NAME;
        $a = ACTION_NAME;
        $allow = $params['allow'];
        $permission = $params['permission'];
        $role_rules = $params['roles'];

        if ($this->user && $this->user['roles']){
            foreach ($this->user['roles'] as $role) {
                if (in_array($a, $role_rules[$role])){
                    return true;
                }
            }
        }

        if(!session('?user_id') && intval(cookie('user_id')) != 0 && trim(cookie('name')) != '' && trim(cookie('salt_code')) != ''){
            $user = M('user')->where(array('user_id' => intval(cookie('user_id'))))->find();
            if (md5(md5($user['user_id'] . $user['name']).$user['password']) == trim(cookie('salt_code'))) {
                $d_role = D('RoleView');
                $role = $d_role->where('user.user_id = %d', $user['user_id'])->find();
                if($user['category_id'] == 1){
                    session('admin', 1);
                }
                session('role_id', $role['role_id']);
                session('position_id', $role['position_id']);
                session('role_name', $role['role_name']);
                session('department_id', $role['department_id']);
                session('name', $user['name']);
                session('user_id', $user['user_id']);
            }
        }

        if (in_array($a, $permission)) {
            return true;
        } elseif ((session('?position_id') && session('?role_id')) || $this->user) {
            if (in_array($a, $allow)) {
                return true;
            } else {
                $positionId = session('position_id');
                
                if (!$positionId) {
                    $role_id = $this->user['role_id'];
                    $roleInfo = M('role')->where(['role_id' => $role_id])->find();
                    $positionId = $roleInfo['position_id'];
                }

                $url = strtolower($m).'/'.strtolower($a);
                $ask_per = M('permission')->where('url = "%s" and position_id = %d', $url, $positionId)->find();
                if (is_array($ask_per) && !empty($ask_per)) {
                    return true;
                } else {
                    throw new \Exception('NO PERMISSION');
                }
            }
        } else {
            throw new \Exception('UNLOGIN', -10003);
        }
    }
}