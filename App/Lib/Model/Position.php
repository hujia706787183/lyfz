<?php
namespace App\Lib\Model;
class Position {
    /**
     * 获取位置id
     * @param $role_id
     * @return mixed
     */
    public static function getPositionId($role_id)
    {
        return M('role')->where('role_id = %d', $role_id)->getField('position_id');
    }
    /**
     * 获取parent_id
     * @param $role_id
     * @return mixed
     */
    public static function getParentId($role_id)
    {
        return M('position')->where(['position_id'=>self::getPositionId($role_id)])->getField('parent_id');
    }

    /**
     * 获取该角色的位置信息
     * @param $role_id
     * @return mixed
     */
    public static function getPositionInfo($role_id)
    {
        $position_id = self::getPositionId($role_id);
        return M('position')->where('position_id = %d', $position_id)->find();
    }

    /**
     * 位置关系无限极分类
     * @param $positions
     * @param int $parent_id
     * @return array
     */
    public static function positionSubtree($positions, $parent_id=0)
    {
        static $positionSubtree = [];
        foreach ($positions as $position){
            if ($position['parent_id']==$parent_id){
                $positionSubtree[] = $position;
                self::positionSubtree($positions, $position['position_id']);
            }
        }
        return $positionSubtree;
    }

    /**
     * 获取该角色的下属  $sub=0不含自己的位置
     * @param $role_id
     * @param int $sub
     * @return array
     */
    public static function getPositions($role_id, $sub = 1)
    {
        $position_id = self::getPositionId($role_id);
        $positionSubtree = self::positionSubtree(M('position')->select(), $position_id);
        if ($sub){
            array_unshift($positionSubtree, self::getPositionInfo($role_id));
        }
        return $positionSubtree;
    }

    /**
     * 含层级关系的详细信息 （目前无用） 单按位置来划分，并不能分得清，可根据role_id  user_id来分
     * @param $positions
     * @param int $parent_id
     * @param int $level
     * @param string $location
     * @return array
     */
    public static function locationSubtree($positions, $parent_id=0, $level=1, $location='')
    {
        static $positionSubtree = [];
        foreach ($positions as $position){
            $position['lev'] = $level;
            if ($level == 1)
                $position['location'] = $parent_id;
            else
                $position['location'] = $location.'_'.$parent_id;

            if ($position['parent_id'] == $parent_id){
                $positionSubtree[] = $position;
                self::locationSubtree($positions, $position['position_id'], $level+1, $position['location']);
            }
        }
        return $positionSubtree;
    }

    /**
     * 返回所有层级关系（目前无用）
     * @return array
     */
    public static function getOrganizationStructure()
    {
        $positionSubtree = self::locationSubtree(M('position')->select());
        return $positionSubtree;
    }

    /**
     * 获取上级部门的positionId
     * @param $role_id
     * @return mixed
     */
    public static function getHigherUpPositionId($role_id)
    {
        $position_id = self::getPositionId($role_id);
        return M('position')->where('position_id = %d', $position_id)->getField('parent_id');
    }

    /**
     * 获取该角色上一级的位置信息
     * @param $role_id
     * @return array
     */
    public static function getHigherUpPositions($role_id)
    {
        $parentPositionId = self::getHigherUpPositionId($role_id);
        $parentPositionSubtree = self::positionSubtree(M('position')->select(), $parentPositionId);
        if ($parentPositionId != 0)
            array_unshift($parentPositionSubtree, M('position')->where('position_id = %d', $parentPositionId)->find());
        return $parentPositionSubtree;
    }



    /**
     * 查某个role_id对应的位置详细信息 不传参的话则查所有的
     * @param $role_id
     * @return bool|mixed
     */
    public static function getPositionSubtree($role_id=null)
    {
        $positions = M('position')->select();
        $positionSubtree = self::locationSubtree($positions);
        if (empty($role_id)){
            return $positionSubtree;
        }else{
            $positionId = self::getPositionId($role_id);
            foreach ($positionSubtree as $positions){
                if ($positions['position_id'] == $positionId){
                    return $positions;
                }
            }
            return false;
        }
    }

    /**
     * @param $role_id
     * @param int $deep 深度
     * @param int $sub 0不含自己 1包含自己 (指的是position_id)
     * @return array
     */
    public static function getSubordinateByLevelDifference($role_id, $deep=1, $sub=0)
    {
        $position_id = self::getPositionId($role_id);
        $positions = M('position')->where(['position_id'=>['neq',35]])->select();
        $position_ids = [];
        $positionSubtrees = self::locationSubtree($positions, $position_id);
        foreach ($positionSubtrees as $positionSubtree){
            if ($positionSubtree['lev']<=$deep){
                $position_ids[] = $positionSubtree['position_id'];
            }
        }
        if ($sub){
            array_unshift($position_ids, $position_id);
        }
        return $position_ids;
    }
}
