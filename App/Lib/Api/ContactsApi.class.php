<?php
use App\Lib\Api\ApiBase;

class ContactsApi extends ApiBase
{
    /**
     * 获取联系人列表
     * @param int $page
     * @param int $page_size
     * @param string $keyword
     */
    public function get_contacts_list($page = 1, $page_size = 15, $keyword='')
    {
        if (!empty($keyword)){
            $keyword = preg_replace('/^|$|\s+/', '%', trim($keyword));

            $where['customer.name|contacts.name'] = ['like', $keyword];
        }
        $contacts_list = M('contacts')->alias('contacts')
            ->field('contacts.contacts_id,customer.customer_id,contacts.name contacts_name,telephone,customer.name customer_name,qq_no,email,saltname,contacts.description')
            ->join(C('DB_PREFIX').'r_contacts_customer contacts_customer ON contacts_customer.contacts_id = contacts.contacts_id')
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = contacts_customer.customer_id')
            ->where($where)
            ->page($page, $page_size)
            ->order('contacts.contacts_id desc')
            ->select();
        $sql = M()->_sql();

        $total = M('contacts')->alias('contacts')
            ->join(C('DB_PREFIX').'r_contacts_customer contacts_customer ON contacts_customer.contacts_id = contacts.contacts_id')
            ->join(C('DB_PREFIX').'customer customer ON customer.customer_id = contacts_customer.customer_id')
            ->where($where)
            ->count();

        $this->ajaxReturn(['contacts_list'=>$contacts_list, 'page_info'=>[ 'size'=>$page_size, 'total'=>intval($total) ], 'sql'=>$sql]);
    }
}