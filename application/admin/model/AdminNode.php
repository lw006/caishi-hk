<?php
namespace app\admin\model;

/**
 * 节点模型
 * @package app\admin\model
 */
class AdminNode extends Base
{
    /**
     * 首页列表生成菜单项
     */
    public function getMenu()
    {
        if (ADMIN) {
            $nodes = $this->where("state=1 AND group_id > 0")->field("id,pid,name,group_id,title,type")->select();
        } else {
            $nodes = $this->find(UID)->hasOne('app\common\model\MenuApi','id','pid')->bind(['pid_title'=>'title']);
        }
        //dump($this->find(UID)->);
        return $nodes;
    }

    /**
     * 插入批量导入的节点
     *
     * @param array $node_template 节点模板
     * @param array $node_detect   代码中探测到的节点
     * @param array $data          其他数据
     *
     * @return array 错误信息
     */
    public function insertLoad($node_template, $node_detect, $data)
    {
        $error = [];
        $insert_all = [];
        $validate = \think\Loader::validate("AdminNode");
        $model_load = new AdminNodeLoad();
        // 有选择模板
        if ($node_template) {
            $nodes = $model_load->where("id", "in", $node_template)->field("title,name")->select();
            foreach ($nodes as $node) {
                $insert = array_merge($data, $node);
                // 数据校验
                if (!$validate->check($insert)) {
                    $error[] = ["data" => $node, "error" => $validate->getError()];
                    continue;
                }
                $insert_all[] = $insert;
            }
        }
        // 有选择自动探测到的节点
        if ($node_detect) {
            foreach ($node_detect as $node) {
                list($data['name'], $data['title']) = explode("###", $node);
                // 数据校验
                if (!$validate->check($data)) {
                    $error[] = ["data" => $data, "error" => $validate->getError()];
                    continue;
                }
                $insert_all[] = $data;
            }
        }
        //TODO 对两种方式产生重复数据的校验
        if ($insert_all) {
            $model_load->insertAll($insert_all);
        }

        return $error;
    }

    public function getTypeTextAttr($value,$data){
        return $data['level'] == 1 ? '模块' : ($data['type'] ? '控制器' : '方法');

    }
    public function getOptAttr($value,$data){

        return show_status($data['state'], $data['id']);

    }
    public function adminRoleNode()
    {
        return $this->hasMany('app\common\model\MenuApi','id','pid')->bind(['pid_title'=>'title']);
    }
    public function menuApi()
    {
        return $this->hasMany('app\common\model\MenuApi','id','pid')->bind(['pid_title'=>'title']);
    }
}