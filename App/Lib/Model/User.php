<?php
namespace App\Lib\Model;

class User
{
    /**
     * 获取部门id
     * @param null $role_id
     * @return mixed
     */
    public static function getDepartmentId($role_id)
    {
        $positionId = Position::getPositionId($role_id);
        return M('position')->where('position_id = %d', $positionId)->getField('department_id');
    }

    /**
     * 获取某人信息
     * @param $role_id
     * @return mixed
     */
    public static function getRoleInfo($role_id)
    {
        $roleInfo = M('user')->field('name, role_id')->where('role_id = %d', $role_id)->find();
        $roleInfo['team'] = M('role_department')->where('department_id = %d', self::getDepartmentId($role_id))->getField('name');
        return $roleInfo;
    }

    /**
     * 获取团队成员
     * @param $role_id
     * @return mixed
     */
    public static function getTeamMembers($role_id)
    {
        $teamMemberRoles = [];
        $teamMembers = [];
        $positions = Position::getPositions($role_id);
        foreach ($positions as $position){
            $role = M('role')->where('position_id = %d', $position['position_id'])->select();
            if (!empty($role))//防止某个$role为空
                $teamMemberRoles = array_merge($teamMemberRoles, $role);
        }
        foreach ($teamMemberRoles as $teamMemberRole){
            if (M('user')->field('user_id, role_id, name')->where('role_id = %d AND status = 1', $teamMemberRole['role_id'])->find())
                $teamMembers[] =  M('user')->field('user_id, role_id, name')->where('role_id = %d AND status = 1', $teamMemberRole['role_id'])->find();
        }
        return $teamMembers;
    }

    /**
     * 获取该角色id下的role_id $subtree=0不含自己的位置 $subtree=1包含自己的位置（包含了同级别的人）
     * @param $role_id
     * @param $subtree
     * @return array
     */
    public static function getTeamMembersRoleIds($role_id, $subtree=1)
    {
        $teamMemberRoles = [];
        $roleIds = [];
        $positions = Position::getPositions($role_id, $subtree);
        foreach ($positions as $position){
            $role = M('role')->where('position_id = %d', $position['position_id'])->select();
            if (!empty($role)){
                $teamMemberRoles = array_merge($teamMemberRoles, $role);
            }
        }
        foreach ($teamMemberRoles as $teamMemberRole){
            $roleIds[] = $teamMemberRole['role_id'];
        }
        return $roleIds;
    }

    /**
     * 通过position_id获取role_id
     * @param $position_ids
     * @return mixed
     */
    public static function getRoleIdsByPositionIds($position_ids)
    {
    //    $role_ids = M('role')->where(['position_id'=>['in', $position_ids]])->getField('role_id', true);
        $role_ids = M('role')->alias('role')->join('inner join '.C('DB_PREFIX').'user user on user.user_id = role.user_id and user.status = 1')->where(['position_id'=>['in', $position_ids]])->field('role.role_id')->select();
        $role_ids = array_column($role_ids, 'role_id');
        return empty($role_ids) ? [] : $role_ids;
    }

    /**
     * 获取大团队里的position_id
     * @param $role_id
     * @return array
     */
    public static function getDepartmentPositionIds($role_id)
    {
        $parent_id = Position::getParentId($role_id);
        return M('position')->where(['parent_id'=>$parent_id,'position_id'=>['neq',35]])->getField('position_id',true);//不含离职员工
    }

    /**
     * 获取大团队里的role_id
     * @param $role_id
     * @return array
     */
    public static function getDepartmentRoleIds($role_id)
    {
        $position_ids = self::getDepartmentPositionIds($role_id);
        return M('role')->where(['position_id'=>['in',$position_ids]])->getField('role_id', true);
    }
    /**
     * 通过role_id 获取相关信息
     * @param $role_ids
     * @return array
     */
    public static function getRoleInfoByRoleIds($role_ids)
    {
        $roleInfo = [];
        foreach ($role_ids as $key=>$role_id){
            $roleInfo[$key]['role_id'] = $role_id;
            $roleInfo[$key]['name'] = M('user')->where('role_id = %d', $role_id)->getField('name');
            $roleInfo[$key]['team'] = M('role_department')->where('department_id = %d', self::getDepartmentId($role_id))->getField('name');
        }
        return $roleInfo;
    }

    /**
     * 获取下属的role_ids 不含自己
     * @param $role_id
     * @param int $deep
     * @return mixed
     */
    public static function getSubtreeRoleIds($role_id, $deep=1)
    {
        $position_ids = Position::getSubordinateByLevelDifference($role_id, $deep);
        $role_ids = User::getRoleIdsByPositionIds($position_ids);
        return $role_ids;
    }

    public static function departmentSubtree($departments, $parent_id=0)
    {
        $departmentSubtree = [];
        foreach ($departments as $department){
            if ($department['parent_id'] == $parent_id){
                $departmentSubtree[] = (int) $department['department_id'];
                $departmentSubtree = array_merge($departmentSubtree, static::departmentSubtree($departments, $department['department_id']));
            }
        }
        return $departmentSubtree;
    }

    /**
     * 通过department_id 获取该部门下属的部门id  sub=1 包含自己
     * @param $parent_id
     * @param int $sub
     * @return array
     */
    public static function getDepartmentIds($parent_id, $sub=1)
    {
        $results = M('role_department')->select();
        $data = static::departmentSubtree($results,$parent_id);
        if ($sub) array_unshift($data, $parent_id);
        return $data;
    }
}