<?php

use App\Lib\Api\ApiBase;
use App\Extend\Weixin\Enterprise;
use App\Extend\Message;
use App\Lib\Model\Position;

/**
 * OA办公模块
 */
class OaApi extends ApiBase {

    //$target_userid 目标的微信id  $creator_name 创建者名  $contacts_name 首要联系人名称 $problem故障问题
    private function sendWxEnterpriseMessage($target_userid, $content){
        import('@.ORG.WeixinEnterprise');
        $WeixinEnterprise = new WeixinEnterprise();
        return $WeixinEnterprise->sendTextMessage('user', $target_userid, $content);
    }

    private function sendWorkReportAuditResult($toUserId, $leaderRoleName, $auditLevel, $auditAdvice){
        $content = "CRM 工作报告批阅结果:\n领导".$leaderRoleName."对你的工作报告进行了评价。\n结果如下：\n评价：".$auditLevel."\n意见：".$auditAdvice;

        $targetUserInfo = M('user')->where(['user_id' => $toUserId])->field('telephone, weixinid')->find();

        $message = new Message();
 
        $message->sendMessage($targetUserInfo['telephone'], $content);

        return $this->sendWxEnterpriseMessage($targetUserInfo['weixinid'], $content);
    }

    function audit_work_report () {

        $role_id = I('get.role_id');
        $date = I('get.date', Date('Y-m-d'));
        $audit_role_id = $this->user['role_id'];
        $audit_role_Info = Position::getPositionSubtree($audit_role_id);
        $role_Info = Position::getPositionSubtree($role_id);
        if ($audit_role_Info['lev'] >= $role_Info['lev']){
            $this->ajaxReturn(null, -1, '您无权对他/她评阅');
        }

        $report = M('ManagerReport')->where(['role_id' => $role_id, 'report_date' => $date])->find();

        if (!$report) {
            $this->ajaxReturn(null, -1, '该成员今天未提交工作报告');
        }

        $auditLevel = I('post.audit_level');

        if (!$auditLevel) {
            $this->ajaxReturn(null, -1, '请给他一个总体评价把');
        }
        
        $auditAdvice = I('post.audit_advice');

        $result = M('ManagerReportAudit')->add([
            'role_id' => $role_id, 
            'report_date' => $date,
            'report_id' => $report['report_id'],
            'audit_level' => $auditLevel, 
            'audit_advice' => $auditAdvice,
            'audit_role_id' => $this->user['role_id'],
            'audit_role_name' => $this->user['name'],
            'create_time' => time()
        ]);

        if ($result !== false) {
            $this->sendWorkReportAuditResult($role_id, $this->user['name'], $auditLevel, $auditAdvice);
            $this->ajaxReturn($result);
        } else {
            $this->ajaxReturn(null, -1, '可能哪里出错了, 请联系管理员');
        }
    }

    function get_work_report_aduit_logs () {

        $role_id = I('get.role_id');
        $date = I('get.date', Date('Y-m-d'));

        $reportList = M('ManagerReportAudit')->where(['role_id' => $role_id, 'report_date' => $date])->select();
        // echo M()->_sql();
        if ($reportList !== false) {
            $this->ajaxReturn($reportList);
        } else {
            $this->ajaxReturn(null, -1, '可能哪里出错了, 请联系管理员');
        }
    }

    /**
     * 奖惩表数据的添加
     */
    public function add_staff_score()
    {
        $posts = I('post.');
        $data = [
            [
                'role_id' => $posts['role_id'],
                'score' => $posts['rewards'],
                'reason' => $posts['rewards_reason'],
                'type' => 1,
                'for_day' => strtotime($posts['for_day']),
                'audit_id' => $posts['audit_id'],
                'create_time' => time()
            ],
            [
                'role_id' => $posts['role_id'],
                'score' => $posts['deduction'],
                'reason' => $posts['deduction_reason'],
                'type' => -1,
                'for_day' => strtotime($posts['for_day']),
                'audit_id' => $posts['audit_id'],
                'create_time' => time()
            ]
        ];
        $result = M('StaffScore')->addAll($data);
        if ($result !== false) {
            $this->ajaxReturn(null, 0, '添加成功');
        } else {
            $this->ajaxReturn(null, -1, '添加失败');
        }
    }

    /**
     * 奖惩表数据的查询
     */
    public function get_staff_score()
    {
        $where['role_id'] = I('get.role_id');
        $where['for_day'] = ['between', [I('get.for_day', null ,'strtotime'), I('get.for_day', null, 'strtotime')+86399]];
        $list = M('RewardsPunishments')->where($where)->select();
        if ($list !== false) {
            $this->ajaxReturn($list);
        } else {
            $this->ajaxReturn(null, -1, '可能哪里出错了, 请联系管理员');
        }
    }
}