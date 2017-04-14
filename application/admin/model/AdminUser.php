<?php
namespace app\admin\model;

/**
 * AdminUser表模型
 * @package app\admin\model
 */
class AdminUser extends Base
{
    //自动完成
    protected $auto = ['password'];

    /**
     * 获取Nodes
     */
    public function nodes($id)
    {
        /* ADMIN可见所有NODE */
        if (ADMIN) {
            $nodes = (new AdminNode())->where("state=1 AND group_id > 0")->field("id,pid,name,group_id,title,type")->select();
        } else {
            $user_data = $this::with('roles.nodes')->find($id);
            $nodes = [];
            foreach($user_data->roles as $roles){
                $nodes = array_merge($nodes,$roles->nodes);
            };
        }
        return $nodes;
    }

    /**
     * 获取Role
     * @return \think\model\relation\BelongsToMany
     */
    public function roles(){
        return $this->belongsToMany('AdminRole');
    }

    protected function setPasswordAttr($value)
    {
        return password_hash_tp($value);
    }

    /**
     * 修改密码
     */
    public function updatePassword($uid, $password)
    {
        return $this->where("id", $uid)->update(['password' => password_hash_tp($password)]);
    }
}

/* End of file AdminUser.php */
/* Location: ./application/admin/model/AdminUser.php */