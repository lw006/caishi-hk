<?php
namespace app\admin\model;

use app\common\model;
/**
 * 模型基类
 */
class Base extends model\Common
{
    public function getStateTextAttr($value,$data)
    {
        $state = [0=>'禁用',1=>'正常',2=>'待审核'];
        return $state[$data['state']];
        $this->save();
    }
}

/* End of file Base.php */
/* Location: ./application/admin/model/Base.php */