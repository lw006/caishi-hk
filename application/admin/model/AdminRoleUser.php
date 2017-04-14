<?php
namespace app\admin\model;

/**
 * AdminRoleUser表模型
 * @package app\admin\model
 */
class AdminRoleUser extends Base
{

    public function getList($field = 'id,name', $where = 'state=1')
    {
        return $this->field($field)->where($where)->select();
    }
}

/* End of file AdminRoleUser.php */
/* Location: ./application/admin/model/AdminRoleUser.php */