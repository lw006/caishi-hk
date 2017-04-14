<?php
namespace app\admin\model;

/**
 * 登录日志模型
 */
class AdminLoginLog extends Base
{
    public function adminUser()
    {
        return $this->hasOne('AdminUser', "id", "admin_id");
    }
}
/* End of file AdminLoginLog.php */
/* Location: ./application/admin/model/AdminLoginLog.php */