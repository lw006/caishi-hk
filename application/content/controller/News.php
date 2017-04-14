<?php
namespace app\content\controller;

use think\Request;
use app\content\model;
/**
 * 新闻控制器
 * @title 新闻接口
 * @url /content/news
 * @desc  有关于新闻的接口
 * @version 1.0
 * @readme /doc/md/user.md
 */
class News extends Base
{
    // 允许访问的请求类型
    public $restMethodList = 'get|post';
    /**
     * 请求参数规则
     * @name 字段名称
     * @type 类型
     * @require 是否必须
     * @default 默认值
     * @desc 说明
     * @range 范围
     * @return array
     */
    public static function requestRules()
    {
        $rules = [
            //共用参数
            'all' => [
            ],
            'getList' => [
                'limit' => ['name' => 'limit', 'type' => 'int', 'require' => 'false', 'default' => '10', 'desc' => '每页数量', 'range' => '>=0','example'=>'10'],
                'order' => ['name' => 'order', 'type' => 'enum', 'require' => 'false', 'default' => 'create_time', 'desc' => '排序字段', 'range' => 'id,create_time','example'=>'update_time'],
                'by' => ['name' => 'by', 'type' => 'enum', 'require' => 'false','default'=>'desc','desc' => '排序方式','range'=>['asc','desc'],'example' => 'asc'],
            ],
        ];
        //可以合并公共参数
        return array_merge(parent::requestRules(), $rules);
    }
    /**
     * 返回参数规则
     * @name 字段名称
     * @type 类型
     * @require 是否必须
     * @default 默认值
     * @desc 说明
     * @range 范围
     * @return array
     */
    public static function responseRules()
    {
        $rules = [
            //共用参数
            'all' => [
                'id' => ['name' => 'id', 'type' => 'int', 'desc' => '数据库ID'],
            ],
            'getList' => [
                'title' => ['name' => 'title', 'type' => 'string', 'desc' => '标题'],
                'by' => ['name' => 'by', 'type' => 'enum', 'require' => 'false','default'=>'desc','desc' => '排序方式','range'=>['asc','desc'],'example' => 'asc'],
            ],
        ];
        //可以合并公共参数
        return array_merge(parent::responseRules(), $rules);
    }
    /**
     * @title 新闻列表接口
     * @url /content/news/getList
     * @type get
     * @desc 获取新闻列表
     * @readme
     * @param \think\Request $request
     * @return string json
     */
    public function getList(){
        $param = $this->request->get();
        $limit = $this->request->get('limit',10);
        $order = empty($param['order']) ? 'create_time' : $param['order'];
        $by = empty($param['by']) ? 'desc' : $param['by'];
        $news = new model\News();
        $map = ['state'=>1];
        if(!empty($param['cid'])){
            $map['cid'] = $param['cid'];
        }
        $result = $news->where($map)->field(true)->order($order,$by)->limit($limit)->select();
        $this->success('','',$result);
    }
}