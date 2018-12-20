<?php

// namespace App\Lib\Api;
use App\Lib\Api\ApiBase;
use App\Lib\Model\Position;
use App\Lib\Model\User;

class DepartmentApi extends ApiBase
{
    public function _initialize(){
        parent::_initialize();
        $action = array(
            // 'permission' => ['getlist', 'reportdateadjust', 'getauditlevel', 'getcountofmonthnoplan', 'getcountofmonthplan','getdepartmentid','getmonthplan','getdayplan','getyesterdayplan','getcompletedoftoday','getcompletedofmonth','getauditrecords'],
            'permission' => ['getlist'],
            'allow' => ['getleadersplan', 'getreportstatisticsformonth', 'getroleinfo','getteammembers','getteammembersroleids','getreport','adddayplan','addmonthplan','gethigherupteammembers','focus'],
            'roles' => [
                'customer' => []
            ]
        );
        try {
            $this->checkPermission($action);
        } catch (\Exception $e){
            $code = -1;
            $e->getCode() && $code = $e->getCode();
            $this->ajaxReturn(null, $code, L($e->getMessage()));
            return;
        }
    }

    public function getlist($department_id = 0, $fetch_sub = 0){
        if ($department_id){
            $where['department_id'] = $department_id;
        }

        $db_role_department = M('RoleDepartment');

        if ($fetch_sub == 1){
            $sub_department = M('RoleDepartment')->where(['parent_id' => $department_id])->buildSql();
            $db_role_department->union($sub_department);
        }

        $this->ajaxReturn($db_role_department->where($where)->select());
    }

    //-----------------------------------经理汇报--------------------------------------------//

    /**
     * 定义每天以凌晨六点为基准划分 如2018年1月22日 表示的时间段为 2018年1月22日凌晨6点--2018年1月23日凌晨6点
     * @param $report_date
     * @return false|string
     */
    public function reportDateAdjust($report_date)
    {
        $currentTimestamp = strtotime($report_date);
        $adjustTimestamp = $currentTimestamp - 6*3600;
        return date('Y-m-d', $adjustTimestamp);
    }

    /**
     * @param null $report_date
     * @param int $deep
     */
    public function getLeadersPlan($report_date=null, $deep=1)
    {
        $role_id = $_SESSION['role_id'] ?? $this->user['role_id'];
        $position_ids = Position::getSubordinateByLevelDifference($role_id, $deep);
        $role_ids = User::getRoleIdsByPositionIds($position_ids);
        $focus_role_ids = M('ManagerFocus')->where(['role_id'=>$role_id,'unfollow'=>0])->getField('focus_role_id', true);
        if ($deep==0){
            $role_ids = User::getDepartmentRoleIds($role_id); 
        }else if($deep==1){
           array_unshift($role_ids, $role_id);
        }
        
        if (!empty($focus_role_ids)){
            $role_ids = array_unique(array_merge($focus_role_ids, $role_ids));
        }
        
        $role_infos = User::getRoleInfoByRoleIds($role_ids);
        $report_date = is_null($report_date)?self::reportDateAdjust(date('Y-m-d H:i:s')):$report_date;
        $role_arr = [];
        
        foreach ($role_infos as $key => $role_info){
            $role_arr[$key]['role_id'] = $role_info['role_id'];
            $role_arr[$key]['role_name'] = $role_info['name'];
            $role_arr[$key]['team'] = $role_info['team'];
            $role_arr[$key]['hasPlanToday'] = is_null($this->getDayPlan($role_info['role_id'], $report_date))?false:true;
            $role_arr[$key]['countOfMonthNoPlan'] = $this->getCountOfMonthNoPlan($role_info['role_id'], $report_date);
            $role_arr[$key]['auditRecordsNumber'] = $this->getAuditRecords($role_info['role_id'], $report_date);
            if (in_array($role_info['role_id'],$focus_role_ids)){
                $role_arr[$key]['unfollow'] = 0;//关注
            }else{
                $role_arr[$key]['unfollow'] = 1;//未关注
            }
        }
        $this -> ajaxReturn($role_arr);
    }



    /**
     * 获取工作汇报统计(对于月)
     * @param null $report_date
     * @param int $deep
     */
    public function getReportStatisticsForMonth($report_date=null, $deep=1)
    {
        $roleId = $_SESSION['role_id'] ?? $this->user['role_id'];
        // $roleId = 66;
        $report_date = empty($report_date)?self::reportDateAdjust(date('Y-m-d H:i:s')):$report_date;

        if ($deep==0){
            $role_ids = User::getDepartmentRoleIds($roleId);
        }else{
            $role_ids = User::getSubtreeRoleIds($roleId, $deep);
            array_unshift($role_ids, $roleId);
        }
        $role_report_statistics = [];
        foreach ($role_ids as $key=>$role_id){
            $role_report_statistics[$key]['role_info'] = User::getRoleInfo($role_id);
            $role_report_statistics[$key]['count_of_month_plan'] = $this->getCountOfMonthPlan($role_id, $report_date);
            $role_report_statistics[$key]['audit_level_statistics'] = $this->getAuditLevel($role_id, $report_date);
        }
        $this->ajaxReturn($role_report_statistics);
    }

    /**
     * 获取评阅等级
     * @param null $role_id
     * @param null $report_date
     * @return mixed
     */
    public function getAuditLevel($role_id=null, $report_date=null)
    {
        $report_date = is_null($report_date) ? date('Y-m-d') : $report_date;
        $report_date = substr($report_date,0, strrpos($report_date,'-'));//获取年月 如2018-01
        $where['report_date'] = ['like',$report_date.'%'];
        $where['role_id'] = $role_id;
        $audit_level_count = M('ManagerReportAudit')->field('audit_level,count(audit_level) audit_level_count')->where($where)->group('audit_level')->select();
        $level_statistics = ['good' => 0, 'medium' => 0, 'bad' => 0];
        foreach ($audit_level_count as $audit_level){
            switch ($audit_level['audit_level']){
                case '好': $level_statistics['good'] = intval($audit_level['audit_level_count']); break;
                case '中': $level_statistics['medium'] = intval($audit_level['audit_level_count']); break;
                case '差': $level_statistics['bad'] =  intval($audit_level['audit_level_count']); break;
            }
        }
        return $level_statistics;
    }

    /**
     * 获取本月没汇报的次数
     * @param null $role_id
     * @param null $report_date
     * @return false|mixed|string
     */
    public function getCountOfMonthNoPlan($role_id=null, $report_date=null)
    {
        $report_date = is_null($report_date) ? date('Y-m-d') : $report_date;
        $countOfMonthPlan = $this->getCountOfMonthPlan($role_id, $report_date);
        //查询是否是本月
        if (substr($report_date,0, strrpos($report_date,'-')) == date('Y-m')){//查询为本月
            $days = date('d');
        }else{
            $nextMonth = date('m', strtotime($report_date))+1;//获取下一个月的月份
            if ($nextMonth > 12){
                //下个月的第一天
                $nextMonth = date('Y', strtotime($report_date))+1;
                $nextMonthFirstDay = $nextMonth.'-01';
            }else{
                $nextMonthFirstDay = date('Y', strtotime($report_date)).'-'.$nextMonth;
            }
            $days = date('d',strtotime($nextMonthFirstDay)-1);//获取查询当月的天数
        }
        return $days-$countOfMonthPlan;
    }

    /**
     * 获取本月汇报次数
     * @param null $role_id
     * @param null $report_date
     * @return mixed
     */
    public function getCountOfMonthPlan($role_id=null, $report_date=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $report_date = is_null($report_date) ? date('Y-m') : substr($report_date,0, strrpos($report_date,'-'));
        $where['report_date'] = ['like',$report_date.'%'];
        $where['role_id'] = $role_id;
        $where['type'] = 1;
        $count = M('manager_report')->where($where)->count();
        return $count;

    }

    /**
     * 获取部门id
     * @param null $role_id
     * @return mixed
     */
    public function getDepartmentId($role_id=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $positionId = Position::getPositionId($role_id);
        return M('position')->where('position_id = %d', $positionId)->getField('department_id');
    }

    /**
     * 获取某人信息
     * @param null $role_id
     */
    public function getRoleInfo($role_id = null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $roleInfo = M('user')->field('name, role_id')->where('role_id = %d', $role_id)->find();
        $roleInfo['team'] = M('role_department')->where('department_id = %d', $this->getDepartmentId($role_id))->getField('name');
        $this->ajaxReturn($roleInfo);
    }

    /**
     * 获取团队成员
     * @param $role_id
     */
    public function getTeamMembers($role_id=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null,-1,'请传入role_id');
        }
        $teamMemberRoles = [];
        $teamMembers = [];
        $positions = Position::getPositions($role_id);
        foreach ($positions as $position){
            $role = M('role')->where('position_id = %d', $position['position_id'])->select();
            if (!empty($role))
                $teamMemberRoles = array_merge($teamMemberRoles, $role);
        }
        foreach ($teamMemberRoles as $teamMemberRole){
            if (M('user')->field('user_id, role_id, name')->where('role_id = %d AND status = 1', $teamMemberRole['role_id'])->find())
                $teamMembers[] =  M('user')->field('user_id, role_id, name')->where('role_id = %d AND status = 1', $teamMemberRole['role_id'])->find();
        }
        $this -> ajaxReturn($teamMembers);
    }

    /**
     * 获取该角色id下的role_id 包括自己
     * @param $role_id
     * @return array
     */
    public function getTeamMembersRoleIds($role_id=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $teamMemberRoles = [];
        $roleIds = [];
        $positions = Position::getPositions($role_id);
        foreach ($positions as $position){
            $role = M('role')->where('position_id = %d', $position['position_id'])->select();
            if (!empty($role))
                $teamMemberRoles = array_merge($teamMemberRoles, $role);
        }
        foreach ($teamMemberRoles as $teamMemberRole){
            $roleIds[] = $teamMemberRole['role_id'];
        }
        return $roleIds;
    }

    /**
     * 获取团队某月的计划
     * @param null $role_id
     * @param null $report_date
     * @return mixed
     */
    public function getMonthPlan($role_id=null, $report_date=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $report_date = is_null($report_date) ? date('Y-m') : substr($report_date,0, strrpos($report_date,'-'));
        $where['report_date'] = ['like', $report_date.'%'];
        $monthPlan = M('manager_report')->field('biggest_progress,review,good_deeds', true)->where($where)->where('type = 2 AND role_id = %d', $role_id)->find();
        
        $monthPlan && $monthPlan['work_arrangement'] = array_filter(explode('|', $monthPlan['work_arrangement']));
        return $monthPlan;
    }

    /**
     * 获取团队某天的计划
     * @param null $role_id
     * @param null $report_date
     * @return mixed
     */
    public function getDayPlan($role_id=null, $report_date=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $report_date = is_null($report_date) ? date('Y-m-d') : $report_date;
        $dayPlan = M('manager_report')->where('type = 1 AND role_id = %d AND report_date = "%s"', $role_id, $report_date)->find();
        return $dayPlan;
    }

    /**
     * 获取团队昨天的计划
     * @param null $role_id
     * @param null $report_date
     * @return mixed
     */
    public function getYesterdayPlan($role_id=null, $report_date=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $report_date = is_null($report_date) ? date('Y-m-d') : $report_date;
        $report_date = date('Y-m-d', strtotime($report_date)-86400);
        $yesterdayPlan = M('manager_report')->where('type = 1 AND role_id = %d AND report_date = "%s"', $role_id, $report_date)->find();
        return $yesterdayPlan;
    }

    /**
     * 获取今天完成的任务
     * @param $role_id
     * @param $report_date
     * @return int
     */
    public function getCompletedOfToday($role_id=null, $report_date=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $report_date = is_null($report_date) ? date('Y-m-d') : $report_date;
        $teamMembersRoleIds = $this->getTeamMembersRoleIds($role_id);
        $create_time= array(array('elt',strtotime($report_date)+86400),array('egt',strtotime($report_date)), 'and');
        $order_list = M('receivables')->alias('r')
            ->join('inner join '.C('DB_PREFIX').'receivingorder receiving on receiving.receivables_id=r.receivables_id')
            ->where(array('r.is_deleted'=>0, 'r.pay_time'=>$create_time, 'r.owner_role_id' => ['in', $teamMembersRoleIds]))
            ->group('r.receivables_id')
            ->field('r.`owner_role_id`, r.price,  sum(money) as received, count(receiving.receivingorder_id) as receving_count')
            ->buildSql();

        $user_receviables_list = M()->table("$order_list t")
            ->field('owner_role_id, sum(received) as total_received')
            ->group('owner_role_id')
            ->select();
        $total_received = 0;
        foreach ($user_receviables_list as $user_receviables){
            $total_received += $user_receviables['total_received'];//实收金额
        }
        return $total_received;
    }

    /**
     * 获取某月的完成量
     * @param null $role_id
     * @param null $report_date
     * @return int
     */
    public function getCompletedOfMonth($role_id=null, $report_date=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $report_date = is_null($report_date) ? date('Y-m-d') : $report_date;
        $teamMembersRoleIds = $this->getTeamMembersRoleIds($role_id);
        $nextMonth = date('m', strtotime($report_date))+1;//获取下一个月的月份
        if ($nextMonth > 12){
            //下个月的第一天
            $nextMonth = date('Y', strtotime($report_date))+1;
            $nextMonthFirstDay = $nextMonth.'-01';
        }else{
            $nextMonthFirstDay = date('Y', strtotime($report_date)).'-'.$nextMonth;
        }
        $create_time= array(array('elt',strtotime($nextMonthFirstDay)-1), array('egt',strtotime(substr($report_date,0, strrpos($report_date,'-')))), 'and');

        $order_list = M('receivables')->alias('r')
            ->join('inner join '.C('DB_PREFIX').'receivingorder receiving on receiving.receivables_id=r.receivables_id')
            ->where(array('r.is_deleted'=>0, 'r.pay_time'=>$create_time, 'r.owner_role_id' => ['in', $teamMembersRoleIds]))
            ->group('r.receivables_id')
            ->field('r.`owner_role_id`, r.price,  sum(money) as received, count(receiving.receivingorder_id) as receving_count')
            ->buildSql();

        $user_receviables_list = M()->table("$order_list t")
            ->field('owner_role_id, sum(received) as total_received')
            ->group('owner_role_id')
            ->select();
//        $this->ajaxReturn(M()->_sql());
        $total_received = 0;
        foreach ($user_receviables_list as $user_receviables){
            $total_received += $user_receviables['total_received'];//实收金额
        }
        return $total_received;
    }


    /**
     * 获取汇报信息
     * @param null $role_id
     * @param null $report_date
     */
    public function getReport($role_id=null, $report_date=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $report_date = is_null($report_date) ? self::reportDateAdjust(date('Y-m-d H:i:s')) : $report_date;
        $plan = [
            'completedOfToday'  => $this->getCompletedOfToday($role_id, $report_date),//某天完成的
            'completedOfMonth'  => $this->getCompletedOfMonth($role_id, $report_date),//某月完成的
            'yesterdayPlanning' => is_null($this->getYesterdayPlan($role_id, $report_date)) ? 20000 : $this->getYesterdayPlan($role_id, $report_date)['planning'],//昨天写的计划
            'dayPlanning'       => is_null($this->getDayPlan($role_id, $report_date)) ? 0 : $this->getDayPlan($role_id, $report_date),//今天写的计划
            'monthPlanning'     => is_null($this->getMonthPlan($role_id, $report_date)) ? 0 : $this->getMonthPlan($role_id, $report_date),//本月计划
        ];
        $this->ajaxReturn($plan);
    }

    /**
     * 获取批阅次数
     * @param null $role_id
     * @param null $report_date
     * @return mixed
     */
    public function getAuditRecords($role_id=null, $report_date=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $report_date = is_null($report_date) ? date('Y-m-d') : $report_date;
        return M('ManagerReportAudit')->where('role_id = %d AND report_date = "%s"', $role_id, $report_date)->count();
    }

    /**
     * 添加日计划
     * report_date  2017-12-27
     */
    public function addDayPlan()
    {
        $post = I('post.');
        if (!isset($post['role_id'])){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $todayPlan = $this->getDayPlan($post['role_id'], self::reportDateAdjust(date('Y-m-d H:i:s')));
        if (is_null($todayPlan)){//还未写日计划
            $todayPlanParam = [
                'role_id'    => $post['role_id'],
                'role_name'  => isset($post['role_name'])?$post['role_name']:'',
                'matters_today'  => isset($post['matters_today'])?$post['matters_today']:'',
                'biggest_progress' => isset($post['biggest_progress'])?$post['biggest_progress']:'',
                'review'     => isset($post['review'])?$post['review']:'',
                'good_deeds' => isset($post['good_deeds'])?$post['good_deeds']:'',
                'planning'   => $post['planning'],
                'work_arrangement' => $post['work_arrangement'],
                'report_date'=> self::reportDateAdjust(date('Y-m-d H:i:s')),
                'type'       => 1
            ];
            $addTodayPlan = M('manager_report')->add($todayPlanParam);
            if (!$addTodayPlan){
                $this -> ajaxReturn(null, -1, '日计划添加失败');
            }else{
                $this -> ajaxReturn(null, 0, '数据添加成功');
            }
        }else{
            if ($todayPlan['report_date']==self::reportDateAdjust(date('Y-m-d H:i:s'))){//只能修改今天的
                $todayPlanParam = [
                    'matters_today'  => isset($post['matters_today'])?$post['matters_today']:'',
                    'biggest_progress' => isset($post['biggest_progress'])?$post['biggest_progress']:'',
                    'review'     => isset($post['review'])?$post['review']:'',
                    'good_deeds' => isset($post['good_deeds'])?$post['good_deeds']:'',
                ];
                $updateTodayPlan = M('manager_report')->where('report_id = %d', $todayPlan['report_id'])->save($todayPlanParam);
                if ($updateTodayPlan === false){
                    $this->ajaxReturn(null, -1, '日计划更新失败');
                }else{
                    $this -> ajaxReturn(null, 0, '日计划更新成功');
                }
            }else{
                $this->ajaxReturn(null, -1, '不能修改今日以外的日计划');
            }
        }
    }

    /**
     * 添加月计划
     */
    public function addMonthPlan()
    {
        $post = I('post.');
        if (!isset($post['role_id'])){
            $this->ajaxReturn(null, -1, 'role_id不能为空');
        }
        $monthPlan = $this->getMonthPlan($post['role_id'], date('Y-m-d'));
        if (is_null($monthPlan)){//还没写月计划
//            if ((!empty($post['month_planning'])) && (!empty($post['month_work_arrangement']))){
                $monthPlanParam = [
                    'role_id'    => $post['role_id'],
                    'role_name'  => isset($post['role_name'])?$post['role_name']:'',
                    'planning'   => $post['planning'],
                    'work_arrangement' => $post['work_arrangement'],
                    'report_date'=> date('Y-m-d'),
                    'type'       => 2
                ];
                $addMonthPlan = M('manager_report')->add($monthPlanParam);
                if (!$addMonthPlan){
                    $this -> ajaxReturn(null, -1, '月计划添加失败');
                }else{
                    $this -> ajaxReturn(null, 0, '月计划添加成功');
                }
//            }
        }else{
            if (end($monthPlan['work_arrangement'])!=$post['work_arrangement']){
                if ($post['work_arrangement']){//如果本月核心任务填了，则将新填入的内容加上，否则不做处理
                    $monthPlan['work_arrangement'][] = $post['work_arrangement'];
                }
                $monthPlanParam = [
                    'work_arrangement' => implode('|',$monthPlan['work_arrangement']),
                    'report_date'=> date('Y-m-d'),
                ];
                $updateMonthPlan = M('manager_report')->where('report_id = %d', $monthPlan['report_id'])->save($monthPlanParam);
                if ($updateMonthPlan === false){
                    $this -> ajaxReturn(null, -1, '月计划更新失败');
                }
            }
            if($post['planning'] < $monthPlan['planning']){
                $this -> ajaxReturn(null, -1, '业绩目标不能小于您之前的目标');
            }else{
                $updateMonthPlan = M('manager_report')->where('report_id = %d', $monthPlan['report_id'])->save(['planning'=>$post['planning']]);
                if ($updateMonthPlan === false){
                    $this -> ajaxReturn(null, -1, '月计划更新失败');
                }
            }
            $this -> ajaxReturn(null, 0, '月计划更新成功');
        }
    }

    /**
     * 获取上级团队成员
     * @param $role_id
     */
    public function getHigherUpTeamMembers($role_id=null)
    {
        if (is_null($role_id)){
            $this->ajaxReturn(null,-1,'请传入role_id');
        }
        $teamMemberRoles = [];
        $teamMembers = [];
        $positions = Position::getHigherUpPositions($role_id);
        foreach ($positions as $position){
            $role = M('role')->where('position_id = %d', $position['position_id'])->select();
            if (!empty($role))
                $teamMemberRoles = array_merge($teamMemberRoles, $role);
        }
        foreach ($teamMemberRoles as $teamMemberRole){
            if (M('user')->field('user_id, role_id, name')->where('role_id = %d AND status = 1', $teamMemberRole['role_id'])->find())
                $teamMembers[] =  M('user')->field('user_id, role_id, name')->where('role_id = %d AND status = 1', $teamMemberRole['role_id'])->find();
        }
        $this -> ajaxReturn($teamMembers);
    }

    /**
     * 修改关注状态
     * @param $focus_role_id
     */
    public function focus($focus_role_id)
    {
        if (empty($focus_role_id)){
            $this->ajaxReturn(null, -1, '参数缺失');
        }
        $role_id = session('role_id') ?? $this->user['role_id'];
        $manager_focus = M('ManagerFocus');
        $focus_data = $manager_focus->where(['role_id'=>$role_id, 'focus_role_id'=>$focus_role_id])->find();
        if ($focus_data){
            $flag = $manager_focus->where(['focus_id'=>$focus_data['focus_id']])->save(['focus_time'=>time(), 'unfollow'=>intval(!$focus_data['unfollow'])]);
            if ($flag && $focus_data['unfollow']){
                $this->ajaxReturn('关注成功',0,'关注成功');
            }elseif($flag){
                $this->ajaxReturn('取消关注成功',0,'取消关注成功');
            }else{
                $this->ajaxReturn('操作失败',-1,'操作失败');
            }
        }else{
            $flag = $manager_focus->add([
                'role_id' => $role_id,
                'focus_role_id' => $focus_role_id,
                'focus_time' => time(),
                'unfollow' => 0
            ]);
            if ($flag){
                $this->ajaxReturn('关注成功',0,'关注成功');
            }else{
                $this->ajaxReturn('操作失败',-1,'操作失败');
            }
        }
    }
}