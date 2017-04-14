<?php
namespace app\admin\controller;

use think\Db;
use think\Loader;
use app\admin\model;

/**
 * 节点控制器
 * @package app\admin\controller
 */
class AdminNode extends Base
{

    protected $table = 'AdminNode';

    protected function filter(&$map)
    {
        if ($this->request->action() == 'index') {
            $map['pid'] = $this->request->param('pid', 0);
        }

        if ($this->request->param('title')) {
            $map['title'] = ["like", "%" . $this->request->param('title') . "%"];
        }
        if ($this->request->param('name')) {
            $map['name'] = ["like", "%" . $this->request->param('name') . "%"];
        }
    }

    /**
     * 首页
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            // 模块
            $modules = model\AdminNode::where('pid=0')->order('sort asc')->select();
            $this->view->assign('modules', $modules);
            $this->view->assign('node', '');

            return $this->view->fetch();
        }
        try {
            return $this->getNodeList();
        } catch (\Exception $e) {
            return ajax_return_error($e->getMessage());
        }
    }

    /**
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    private function getNodeList(){
        $moduleId = $this->request->param('module_id');
        $groupId = $this->request->param('group_id');

        if ($this->request->param('type') == 'group') {
            // 查询分组

            // 查询二级节点下分组信息
            $groupIds = $this->model->where("level=2 AND pid='{$moduleId}'")->column('group_id');
            if (!$groupIds) {
                return ajax_return_error('该模块下没有任何节点');
            }
            // 分组下菜单个数
            $groupIds = array_count_values($groupIds);

            // 分组信息
            $groupList = model\AdminGroup::where(['id' => ['in', array_keys($groupIds)]])
                ->order('sort asc')
                ->field('id,name,icon,sort,state')
                ->select();

            return ajax_return(['count' => $groupIds, 'list' => $groupList]);
        } else {
            // 查询节点
            $list = $this->model->where("(level=2 AND pid='{$moduleId}' AND group_id='{$groupId}') or (level>2)")
                ->select();
            // 重新组装节点
            $list2 = [];
            $tpl = '<span class="c-warning">[ %type_text% ]</span>%title% (%name%) ';
            $tpl .= '<span class="c-secondary">[ 层级：%level% ]</span> %opt% ';
            $tpl .= '<a class="label label-primary radius J_add" data-id="%id%" href="javascript:;" title="添加子节点">添加</a>';
            foreach ($list as $row) {
                $name = $tpl;
                foreach ($row->append(['type_text','opt'])->toArray() as $k => $v){
                    $name = str_replace('%' . $k . '%', $v, $name);
                }
                $list2[] = [ 'name' => $name ,'id' => $row['id'], 'pid' => $row['pid']];
            }
            $node = list_to_tree($list2, 'id', 'pid', 'children', $moduleId);

            return ajax_return(['list' => $node]);
        }
    }

    /**
     * 回收站
     */
    public function recycleBin()
    {
        $list_group = Loader::model('AdminGroup')->getList();
        $this->view->assign('group_list', reset_by_key($list_group, "id"));
        return parent::index();
    }

    /**
     * 保存排序
     */
    public function sort()
    {
        $data = $this->request->only(['id', 'pid', 'sort', 'level']);
        $model_node = new model\AdminNode();
        $model_node->save($data, ['id' => $data['id']]);

        return ajax_return_adv('保存排序成功');
    }

    protected function beforeAdd()
    {
        //分组
        $model_group = new model\AdminGroup();
        $group_list = $model_group->getList();
        $this->view->assign('group_list', $group_list);

        //父节点和层级
        $model_node = new model\AdminNode();
        $node = $model_node->where("id", $this->request->param('pid/d'))->field("id,level")->find();
        $vo['pid'] = $node['id'];
        $vo['level'] = intval($node['level']) + 1;
        $this->view->assign('vo', $vo);
    }

    protected function beforeEdit()
    {
        // 分组
        $group_list = (new model\AdminGroup())->getList();
        $this->view->assign('group_list', $group_list);
    }

    /**
     * 禁用限制
     */
    protected function beforeForbid()
    {
        // 禁止禁用 Admin 模块,权限设置节点
        $this->filterId([1, 2, 3, 4, 5, 6], '该记录不能被禁用');
    }

    /**
     * 删除限制
     */
    protected function beforeDelete()
    {
        // 禁止删除 Admin 模块,权限设置节点
        $this->filterId([1, 2, 3, 4, 5, 6], '该节点不能被删除');
    }

    /**
     * 删除限制
     */
    protected function beforeDeleteForever()
    {
        // 禁止删除 Admin 模块,权限设置节点
        $this->filterId([1, 2, 3, 4, 5, 6], '该节点不能被删除');
    }

    /**
     * 节点快速导入
     */
    public function load()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $node_template = isset($data['node']) ? $data['node'] : [];
            $node_detect = isset($data['node_name']) ? $data['node_name'] : [];
            unset($data['node'], $data['node_name']);

            $error = Loader::model('AdminNode', 'logic')->insertLoad($node_template, $node_detect, $data);

            if ($error) {
                //拼接错误信息
                $errormsg = "部分节点导入失败：";
                foreach ($error as $err) {
                    $errormsg .= "<br>{$err['data']['title']}({$err['data']['name']})：{$err['error']}";
                }
                $errormsg .= "<p class='c-red'>请手动刷新页面</p>";

                return ajax_return_adv('', '', $errormsg);
            }

            return ajax_return_adv("批量导入成功");
        } else {
            // 分组
            $group_list = Loader::model('AdminGroup')->getList();
            $this->view->assign('group_list', $group_list);

            // 父节点和层级
            $db_node = Db::name("AdminNode");
            $node = $db_node->where("id", $this->request->param('pid/d'))->field("id,pid,name,level")->find();
            $vo['pid'] = $node['id'];
            $vo['level'] = intval($node['level']) + 1;
            $this->view->assign('vo', $vo);

            // 模板库
            $node_template = Db::name("AdminNodeLoad")->field("id,name,title")->where("state=1")->select();
            $this->view->assign("node_template", $node_template);

            // 公共方法
            $node_public = \ReadClass::method("\\app\\admin\\Controller");
            $this->view->assign("node_public", $node_public ?: []);

            // 当前方法
            // 递归获取所有父级节点
            $parent_node = "";
            $pid = $node['pid'];
            while ($pid > 1) {
                if ($current_node = $db_node->where("id", $pid)->field("id,pid,name")->find()) {
                    $parent_node = "\\" . $current_node['name'] . $parent_node;
                    $pid = $current_node['pid'];
                } else {
                    break;
                }
            }
            // 方法生成
            $node_current_name = "\\app\\admin\\controller" . strtolower($parent_node) . "\\" .
                \think\Loader::parseName($node['name'], 1);
            $node_current = \ReadClass::method($node_current_name);
            $this->view->assign("node_current", $node_current ?: []);

            return $this->view->fetch();
        }
    }
}
