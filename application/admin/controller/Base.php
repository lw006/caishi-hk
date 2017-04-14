<?php
namespace app\admin\controller;

use app\common\controller\Web;
use think\Model;
use think\Session;
use think\Db;
use think\Config;
use think\Loader;
use think\Exception;
use think\exception\HttpException;
use app\admin\logic\Pub as PubLogic;

/**
 * admin模块总控制器
 * @package app\admin\controller
 */
class Base extends Web
{
    /**
     * @var array 黑名单方法，禁止访问某些方法
     */
    protected static $blacklist = [];
    /**
     * @var array 白名单方法，如果设置会覆盖黑名单方法，只允许白名单方法能正常访问
     */
    protected static $allowList = [];

    protected $table;
    protected $model;

    public function _base()
    {

        // 白名单/黑名单方法
        if ($this::$allowList && !in_array($this->request->action(), $this::$allowList)) {
            throw new HttpException(404, 'method not exists:' . $this->request->controller() . '->' . $this->request->action());
        } elseif ($this::$blacklist && in_array($this->request->action(), $this::$blacklist)) {
            throw new HttpException(404, 'method not exists:' . $this->request->controller() . '->' . $this->request->action());
        }
        Config::load(APP_PATH.'admin/extra/rbac.php');
        // 用户ID
        defined('UID') or define('UID', Session::get(Config::get('user_auth_key')));
        // 是否是管理员
        defined('ADMIN') or define('ADMIN', true === Session::get(Config::get('admin_auth_key')));

        // 检查认证识别号
        if (null === UID) {
            $this->notLogin();
        } else {
            $this->auth();
        }

        if($this->table) {
            $this->model = Loader::model($this->table);
        }
        // 前置方法
        $beforeAction = "before" . $this->request->action();
        if (method_exists($this, $beforeAction)) {
            $this->$beforeAction();
        }
        $this->view->assign('param', $this->request->param());
    }


    /**
     * 获取实际的控制器名称(应用于多层控制器的场景)
     *
     * @param $controller
     *
     * @return mixed
     */
    protected function getRealController($controller = '')
    {
        if (!$controller) {
            $controller = $this->request->controller();
        }
        $controllers = explode(".", $controller);
        $controller = array_pop($controllers);

        return $controller;
    }

    /**
     * 默认更新字段方法
     *
     * @param string     $field 更新的字段
     * @param string|int $value 更新的值
     * @param string     $msg 操作成功提示信息
     * @param string     $pk 主键，默认为主键
     * @param string     $input 接收参数，默认为主键
     */
    protected function updateField($field, $value, $msg = "操作成功", $pk = "", $input = "")
    {
        if (!$pk) {
            $pk = $this->model->getPk();
        }
        if (!$input) {
            $input = $this->model->getPk();
        }
        $ids = $this->request->param($input);
        $where[$pk] = ["in", $ids];
        if (false === $this->model->where($where)->update([$field => $value])) {
            return ajax_return_adv_error($this->model->getError());
        }

        return ajax_return_adv($msg, '');
    }


    /**
     * 未登录处理
     */
    protected function notLogin()
    {
        PubLogic::notLogin();
    }

    /**
     * 权限校验
     */
    protected function auth()
    {
        // 用户权限检查
        if (
            Config::get('rbac.user_auth_on') &&
            !in_array($this->request->module(), explode(',', Config::get('rbac.not_auth_module')))
        ) {
            if (!\Rbac::AccessCheck()) {
                throw new HttpException(403, "没有权限");
            }
        }
    }

    /**
     * 过滤禁止操作某些主键
     *
     * @param        $filterData
     * @param string $error
     * @param string $method
     * @param string $key
     */
    protected function filterId($filterData, $error = '该记录不能执行此操作', $method = 'in_array', $key = 'id')
    {
        $data = $this->request->param();
        if (!isset($data[$key])) {
            throw new HttpException(404, '缺少必要参数');
        }
        $ids = is_array($data[$key]) ? $data[$key] : explode(",", $data[$key]);
        foreach ($ids as $id) {
            switch ($method) {
                case '<':
                case 'lt':
                    $ret = $id < $filterData;
                    break;
                case '>':
                case 'gt':
                    $ret = $id < $filterData;
                    break;
                case '=':
                case 'eq':
                    $ret = $id == $filterData;
                    break;
                case '!=':
                case 'neq':
                    $ret = $id != $filterData;
                    break;
                default:
                    $ret = call_user_func_array($method, [$id, $filterData]);
                    break;
            }
            if ($ret) {
                throw new Exception($error);
            }
        }
    }

    /**
     * 首页
     * @return mixed
     */
    public function index()
    {
        // 列表过滤器，生成查询Map对象
        $map = [];
        $fields = $this->model->getTableInfo('', 'fields');
        $param = $this->request->param();
        foreach ($param as $key => $val) {
            if ($val !== "" && in_array($key, $fields)) {
                $map[$key] = $val;
            }
        }

        // 对应方法的过滤器
        $actionFilter = 'filter' . $this->request->action();
        if (method_exists($this, $actionFilter)) {
            $this->$actionFilter($map);
        }

        // 自定义过滤器
        if (method_exists($this, 'filter')) {
            $this->filter($map);
        }

        $list = $this->model->getPageList($map);

        // 模板赋值显示
        $this->view->assign('list', $list);
        $this->view->assign('count', $list->total());
        $this->view->assign("page", $list->render());
        $this->view->assign('numPerPage', $list->listRows());
        return $this->view->fetch();
    }

    /**
     * 回收站
     * @return mixed
     */
    public function recycleBin()
    {
        //只查询软删除数据
        $this->model->onlyTrashed();
        return $this->index();
    }

    /**
     * 添加
     * @return mixed
     */
    public function add()
    {
        if (!$this->request->isAjax()) {
            // 添加
            return $this->view->fetch(isset($this->template) ? $this->template : 'edit');
        }
        $controller = $this->request->controller();

        // 插入
        $data = $this->request->except(['id']);

        // 验证
        if (class_exists($validateClass = Loader::parseClass(Config::get('app.validate_path'), 'validate', $controller))) {
            $validate = new $validateClass();
            if (!$validate->check($data)) {
                return ajax_return_adv_error($validate->getError());
            }
        }

        // 写入数据
        $ret = $this->model->isUpdate(false)->save($data);


        return ajax_return_adv('添加成功');
    }

    /**
     * 编辑
     * @return mixed
     */
    public function edit()
    {
        $controller = $this->request->controller();

        if ($this->request->isAjax()) {
            // 更新
            $data = $this->request->post();
            if (!$data['id']) {
                return ajax_return_adv_error("缺少参数ID");
            }

            // 验证
            if (class_exists($validateClass = Loader::parseClass(Config::get('app.validate_path'), 'validate', $controller))) {
                $validate = new $validateClass();
                if (!$validate->check($data)) {
                    return ajax_return_adv_error($validate->getError());
                }
            }

            // 更新数据
            $ret = $this->model->isUpdate(true)->save($data, ['id' => $data['id']]);

            return ajax_return_adv("编辑成功");
        } else {
            // 编辑
            $id = $this->request->param('id');
            if (!$id) {
                throw new HttpException(404, "缺少参数ID");
            }
            $vo = $this->model->find($id);
            if (!$vo) {
                throw new HttpException(404, '该记录不存在');
            }

            $this->view->assign("vo", $vo);

            return $this->view->fetch();
        }
    }

    /**
     * 默认删除操作
     */
    public function delete()
    {
        return $this->updateField('delete_time', date('Y-m-d H:i:s'), "移动到回收站成功");
    }

    /**
     * 从回收站恢复
     */
    public function recycle()
    {
        return $this->updateField('delete_time', null, "恢复成功");
    }

    /**
     * 默认禁用操作
     */
    public function forbid()
    {
        return $this->updateField('state', 0, "禁用成功");
    }


    /**
     * 默认恢复操作
     */
    public function resume()
    {
        return $this->updateField('state', 1, "恢复成功");
    }


    /**
     * 永久删除
     */
    public function deleteForever()
    {
        $pk = $this->model->getPk();
        $ids = $this->request->param($pk);
        $where[$pk] = ["in", $ids];
        if (false === $this->model->where($where)->delete()) {
            return ajax_return_adv_error($this->model->getError());
        }

        return ajax_return_adv("删除成功");
    }

    /**
     * 清空回收站
     */
    public function clear()
    {
        if (false === $this->model->onlyTrashed()->delete()) {
            return ajax_return_adv_error($this->model->getError());
        }

        return ajax_return_adv("清空回收站成功");
    }

    /**
     * 保存排序
     */
    public function saveOrder()
    {
        $param = $this->request->param();
        if (!isset($param['sort'])) {
            return ajax_return_adv_error('缺少参数');
        }

        foreach ($param['sort'] as $id => $sort) {
            $this->model->where('id', $id)->update(['sort' => $sort]);
        }

        return ajax_return_adv('保存排序成功', '');
    }


}

/* End of file Base.php */
/* Location: ./application/admin/controller/Base.php */
