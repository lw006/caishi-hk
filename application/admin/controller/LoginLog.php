<?php
/**
 * tpAdmin [a web admin based ThinkPHP5]
 *
 * @author yuan1994 <tianpian0805@gmail.com>
 * @link http://tpadmin.yuan1994.com/
 * @copyright 2016 yuan1994 all rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

//------------------------
// 登录日志控制器
//-------------------------

namespace app\admin\controller;

use app\admin\model;

class LoginLog extends Base
{
    protected static $isdelete = false; //禁用该字段

    protected static $blacklist = ['add', 'edit', 'delete', 'deleteforever', 'forbid', 'resume', 'recycle', 'recyclebin', 'clear'];

    protected $table = 'admin_login_log';
    
    protected function filter(&$map)
    {
        if ($this->request->param('login_location')) {
            $map['login_location'] = ["like", "%" . $this->request->param('login_location') . "%"];
        }

        // 关联筛选
        if ($this->request->param('account')) {
            $map['user.account'] = ["like", "%" . $this->request->param('account') . "%"];
        }
        if ($this->request->param('name')) {
            $map['user.realname'] = ["like", "%" . $this->request->param('name') . "%"];
        }

        // 设置属性
        $map['_table'] = "admin_login_log";
        $map['_order_by'] = "admin_login_log.id desc";
        $map['_func'] = function (model\AdminLoginLog $model) use ($map) {
            $model->alias($map['_table'])->join(model\AdminUser::getTable() . ' user', 'admin_login_log.admin_id = user.id');
        };
    }

    /**
     * 获取模型
     *
     * @param string $controller
     * @param bool   $type 是否返回模型的类型
     *
     * @return \think\db\Query|\think\Model|array
     */
    protected function getModel($controller = '', $type = false)
    {
            $model = new model\AdminLoginLog();
            $modelType = 'model';

        return $type ? ['type' => $modelType, 'model' => $model] : $model;
    }
}
