<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31 0031
 * Time: 21:59
 */
class InventoryRecordsModel extends Model{
    public function delete($trueDel = false)
    {
        if ($trueDel) {
            return parent::delete();
        }
        $data['delete_time'] = time();
        return parent::save($data);
    }


}