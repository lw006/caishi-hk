<?php
namespace app\admin\model;

/**
 * AdminNodeUser表模型
 * @package app\admin\model
 */
class AdminNodeUser extends Base
{

    public function getList($field = 'id,name', $where = 'state=1')
    {
        return $this->field($field)->where($where)->select();
    }
}

/* End of file AdminNodeUser.php */
/* Location: ./application/admin/model/AdminNodeUser.php */