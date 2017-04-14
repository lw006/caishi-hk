<?php
// +----------------------------------------------------------------------
// | When work is a pleasure, life is a joy!
// +----------------------------------------------------------------------
// | User: ShouKun Liu  |  Email:24147287@qq.com  | Time:2017/3/5 9:58
// +----------------------------------------------------------------------
// | TITLE: 用户接口
// +----------------------------------------------------------------------
namespace app\v1\controller;
use app\common\controller\Api;
use app\v1\model;
use think\Db;
use think\Request;
use think\Url;

/**
 * Class Dynamic
 * @title 动态接口
 * @url /v1/dynamic
 * @desc 发布文字、语音、短视频的接口
 * @version 0.1
 * @return int errorno 请求成功标志
 * @return string message 错误提示信息
 * @return object data 返回数据
 * @readme
 */
class Dynamic extends Api
{
    // 允许访问的请求类型
    public $restMethodList = 'get|post';

    /**
     * 参数规则
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
            'all'=>[
                'time'=> ['name' => 'time', 'type' => 'int', 'require' => 'true', 'default' => '', 'desc' => '时间戳', 'range' => '',]
                ],
            'postIssue'=>[
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户唯一标识',],
                'type' => ['name' => 'type', 'type' => 'string', 'require' => 'true', 'desc' => '动态的类型',],
                'title' => ['name' => 'title', 'type' => 'string', 'require' => 'true', 'desc' => '动态的标题',],
                'content' => ['name' => 'content', 'type' => 'string', 'require' => 'false', 'desc' => '动态文本',],
                'image' => ['name' => 'image', 'type' => 'image', 'require' => 'false', 'desc' => '动态的图片',],
                'audio' => ['name' => 'audio', 'type' => 'audio', 'require' => 'false', 'desc' => '动态的音频',],
                'video' => ['name' => 'video', 'type' => 'video', 'require' => 'false', 'desc' => '动态的视频',],
            ],

        ];
        //可以合并公共参数
        return array_merge(parent::setRules(),$rules);
    }

    public static function responseRules(){
        $rules = [
            //共用参数
            'all'=>[],
            'postIssue'=>[],
            'getList'=>[
                'userid' => ['name' => 'userid', 'type' => 'int', 'desc' => '用户唯一标识',],
                'username' => ['name' => 'username', 'type' => 'int', 'desc' => '用户昵称',],
                'tags' => ['name' => 'tags', 'type' => 'int', 'desc' => '用户标签',],
                'type' => ['name' => 'type', 'type' => 'string', 'desc' => '动态的类型',],
                'title' => ['name' => 'title', 'type' => 'string', 'desc' => '动态的标题',],
                'content' => ['name' => 'content', 'type' => 'string', 'desc' => '动态文本',],
                'image' => ['name' => 'image', 'type' => 'string', 'desc' => '动态的图片url',],
                'audio' => ['name' => 'audio', 'type' => 'string', 'desc' => '动态的音频url',],
                'video' => ['name' => 'video', 'type' => 'string', 'desc' => '动态的视频url',],
            ],
        ];
        //可以合并公共参数
        return array_merge(parent::responseRules(),$rules);
    }
    /**
     * @title 发布动态
     * @url /v1/dynamic/Issue
     * @type post
     * @desc 发布语音、文字、小视频的接口
     * @param \think\Request $request
     * @return object
     */
    public function postIssue(\think\Request $request){

        $data['content'] = $request->post('content',null);
        $data['accessory'] = $request->post('accessory',null);
        $data['userid'] = $request->post('userid',null);
        if (empty($data['userid']) || (empty($data['content']) && empty($data['accessory']) ) ){
            return $this->sendError(201,'missing params',201);
        }
        else {
            $res = Db::name('user')->where('userid',$data['userid'])->find();
            if ( empty( $res ) ) {
                return $this->sendError(201,'用户ID不存在',201);
            }
            else{
                if (!empty($_FILES)) {
                    $file = $request->file('accessory');
                    $file->validate(['size'=>10240000,'ext'=>'wma,wav,mp3,mp4'])->rule('date');
                    $upload = $file->move( PUBLIC_PATH . 'uploads/');
                    if ( $upload ) {
                        $data['accessory_name'] = $upload->getFilename();
                        $data['accessory_path'] = config('base_url') . 'uploads/' . $upload->getSavename();
                    } else {
                        $error = $file->getError();
                        return $this->sendError(403,$error,403);
                    }
                }
                unset($data['accessory']);
                //dump($data);
                try{
                    $data['created_at'] = date('Y-m-d H-i-s');
                    Db::name('dynamic')->insert($data);
                    return $this->sendSuccess();
                }catch (\Exception $e){
                    $error = $e->getMessage();
                    return $this->sendError(403,$error,403);
                }
            }
        }
    }

    /**
     * @title 拉取动态列表
     * @url /v1/dynamic/List
     * @type get
     * @desc 动态首页获取动态信息列表
     * @param Request $request
     * @return object
     */
    public function getList(Request $request){
        $id = $request->get('id',null);
        if (empty($id)){
            $data = Db::name('dynamic')
                ->alias('a')
                ->join('__USER__ b','a.userid=b.userid','inner')
                ->limit(10)
                ->order('a.id DESC')
                ->field('a.id,a.userid,a.content,a.created_at,a.accessory_path,IFNULL(b.username,b.tel) AS username')
                ->select();
        } else {
            $id = (int)$id;
            $data = Db::name('dynamic')
                ->alias('a')
                ->join('__USER__ b','a.userid=b.userid','inner')
                ->limit(10)
                ->order('a.id DESC')
                ->field('a.id,a.userid,a.content,a.created_at,a.accessory_path,IFNULL(b.username,b.tel) AS username')
                ->where("id > $id")
                ->select();
        }
        return $this->sendSuccess($data);
    }

    /**
     * @title 获取单条动态接口
     * @url /v1/dynamic/Obtain
     * @type get
     * @desc 指定ID获取动态的接口  放第二版做
     * @param Request $request
     * @return object
     */
    public function getObtain(Request $request){
        
    }

    /**
     * @title 获取单条动态的评论接口
     * @url /v1/dynamic/Comments
     * @type get
     * @desc 指定ID获取动态的评论接口  放第二版做
     * @param Request $request
     * @return object
     */
    public function getComments(Request $request){

    }

    /**
     * @title 指定动态ID发表评论
     * @url /v1/dynamic/Comment
     * @type post
     * @desc 指定动态ID发表评论的接口  放第二版做
     * @param Request $request
     * @return object
     */
    public function postComment(Request $request){

    }

    /**
     * @title 指定动态ID点赞
     * @url /v1/dynamic/Like
     * @type post
     * @desc 指定动态ID点赞的接口  放第二版做
     * @param Request $request
     * @return object
     */
    public function postLike(Request $request){

    }

    /**
     * @title 指定动态ID加入收藏
     * @url /v1/dynamic/Favorite
     * @type post
     * @desc 指定动态ID加入收藏  放第二版做
     * @param Request $request
     * @return object
     */
    public function postFavorite(Request $request){

    }

    /**
     * @title 指定动态ID转发
     * @url /v1/dynamic/Transpond
     * @type post
     * @desc 指定动态ID转发  放第二版做
     * @param Request $request
     * @return object
     */
    public function postTranspond(Request $request){
        
    }
}