<?php
namespace app\admin\model;

/**
 * AdminGroup表模型
 * @package app\admin\model
 */
class AdminGroup extends Base
{

    public function getList($field = 'id,name', $where = 'state=1')
    {
        return $this->field($field)->where($where)->select();
    }
}

/* End of file AdminGroup.php */
/* Location: ./application/admin/model/AdminGroup.php */