<?php
namespace app\v1\controller;
use app\common\controller\Api;
use app\v1\model;
use think\Db;
use think\Loader;
use think\Response;
use think\Request;
use think\Config;
use think\cache\Driver\Redis;

/**
 * Class Live
 * @title 直播接口
 * @url /v1/live
 * @version 0.1
 * @desc 直播接口,该返回字段为每次请求的格式。对应接口的返回值仅为data中的内容
 * @return int errorno 请求成功标志
 * @return string message 错误提示信息
 * @return object data 返回数据
 */
class Live extends Api
{
    // 允许访问的请求类型
    public $restMethodList = 'get|post';

    /**
     * 参数规则
     * @name 字段名称
     *
     * @type 类型
     * @require 是否必须
     * @default 默认值
     * @desc 说明
     * @range 范围
     * @return array
     */
    public static function requestRules()
    {
        // 各个接口参数
        $rules = [
            //共用参数
            'all' => [
            ],
            // 获取推流地址
            'postRequestPushAddr' => [
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户ID',],
                'title' => ['name' => 'title', 'type' => 'string', 'require' => 'true','desc' => '直播标题',],
                'frontcover' => ['name' => 'frontcover', 'type' => 'image', 'require' => 'true','desc' => '封面地址',],
                'location' => ['name' => 'location', 'type' => 'string', 'require' => 'false','desc' => '地理位置',],
                'angle' => ['name' => 'angle', 'type' => 'int', 'require' => 'false','desc' => '0:横屏(默认) 1:竖屏',],
                'quality' => ['name' => 'quality', 'type' => 'int', 'require' => 'false','desc' => '0:标清 1:高清 2:超清',],
            ],
            // 修改直播间状态
            'postChangeStatus' => [
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户ID',],
                'stream_id' => ['name' => 'stream_id', 'type' => 'string', 'require' => 'true', 'desc' => '用来区别不通推流地址的唯一id',],
                'status' => ['name' => 'status', 'type' => 'int', 'require' => 'true', 'desc' => '1:上线(开始) 2:下线(结束)',],
            ],
            // 修改直播点赞数量
            'postChangeCount' => [
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户id',],
                'liveuserid' => ['name' => 'liveuserid', 'type' => 'int', 'require' => 'true', 'desc' => '主播的用户id',],
                'group_id' => ['name' => 'group_id', 'type' => 'string', 'require' => 'true', 'desc' => '群组ID，区分是哪个视频',],
            ],
            // 拉取直播列表
            'getList' => [
//                'flag' => ['name' => 'flag', 'type' => 'int', 'require' => 'true', 'desc' => '1:拉取在线直播列表 2:拉取7天内点播列表 3:拉取在线直播和7天内点播列表，直播列表在前，点播列表在后',],
                'pageno' => ['name' => 'pageno', 'type' => 'int', 'require' => 'false', 'desc' => '分页号',],
                'pagesize' => ['name' => 'pagesize', 'type' => 'int', 'require' => 'false', 'desc' => '分页大小',],
            ],
            // 直播广场
            'getPlaza' => [
//                'flag' => ['name' => 'flag', 'type' => 'int', 'require' => 'true', 'desc' => '1:拉取在线直播列表 2:拉取7天内点播列表 3:拉取在线直播和7天内点播列表，直播列表在前，点播列表在后',],
                'tags' => ['name' => 'tags', 'type' => 'array', 'require' => 'false', 'desc' => '筛选的条件(用户标签)',],
                'order' => ['name' => 'order', 'type' => 'int', 'require' => 'false', 'desc' => '排序方式  1:正序   0:倒序',],
                'pageno' => ['name' => 'pageno', 'type' => 'int', 'require' => 'false', 'desc' => '分页号',],
                'pagesize' => ['name' => 'pagesize', 'type' => 'int', 'require' => 'false', 'desc' => '分页大小',],
            ],
            // 观众进入直播间
            'postEnterGroup' => [
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户id',],
                'liveuserid' => ['name' => 'liveuserid', 'type' => 'int', 'require' => 'true', 'desc' => '主播的用户id',],
                'group_id' => ['name' => 'groupid', 'type' => 'string', 'require' => 'true', 'desc' => '群组id',],
                'username' => ['name' => 'username', 'type' => 'string', 'require' => 'false', 'desc' => '用户昵称',],
                'avatar' => ['name' => 'avatar', 'type' => 'string', 'require' => 'false', 'desc' => '头像地址',],
            ],
            // 观众离开直播间
            'postQuitGroup' => [
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户id',],
                'liveuserid' => ['name' => 'liveuserid', 'type' => 'int', 'require' => 'true', 'desc' => '主播的用户id',],
                'groupid' => ['name' => 'groupid', 'type' => 'string', 'require' => 'true', 'desc' => '群组id',],
            ],
            // 拉取直播间观众列表
            'getGroupMemberList' => [
                'group_id' => ['name' => 'groupid', 'type' => 'string', 'require' => 'true', 'desc' => '群组id',],
                'liveuserid' => ['name' => 'liveuserid', 'type' => 'int', 'require' => 'false', 'desc' => '主播的用户id',],
                'pageno' => ['name' => 'pageno', 'type' => 'int', 'require' => 'false', 'desc' => '分页号',],
                'pagesize' => ['name' => 'pagesize', 'type' => 'int', 'require' => 'false', 'desc' => '分页大小',],
            ],
            // 获取主播信息
            'getPuserInfo' => [
                'userid' => ['name' => 'userid', 'type' => 'string', 'require' => 'true', 'desc' => '用户id',],
                'type' => ['name' => 'type', 'type' => 'int', 'require' => 'true', 'desc' => '0:直播 1:录播',],
                'fileid' => ['name' => 'fileid', 'type' => 'string', 'require' => 'true', 'desc' => '点播文件id，type为0时可忽略',],
            ]
        ];
        //可以合并公共参数
        return array_merge(parent::requestRules(), $rules);
    }

    public static function responseRules(){
        $rules = [
            //共用参数
            'all'=>[],
            // 获取推流地址
            'postRequestPushAddr' => [
                'push_url' => ['name' => 'push_url','type' => 'string','desc' => '推流地址',],
                'play_url' => ['name' => 'play_url','type' => 'array','desc' => '3种格式拉流地址',],
            ],
            // 修改直播间状态
            'postChangeStatus' => [
                'max_viewer_count'=>['name' => 'max_viewer_count','type' => 'int','desc' => '(直播结束时返回此字段。)本次直播观众峰值',],
            ],
            // 修改直播点赞数量
            'postChangeCount' => [],
            // 拉取直播列表 首页
            'getList' => [
                'userid' => ['name' => 'userid', 'type' => 'int','desc' => '用户唯一标识',],
                'stream_id' => ['name' => 'stream_id', 'type' => 'string','desc' => '直播流唯一标识',],
                'viewer_count' => ['name' => 'viewercount', 'type' => 'int','desc' => '观众数量',],
                'like_count' => ['name' => 'likecount', 'type' => 'int','desc' => '点赞数量',],
                'title' => ['name' => 'title', 'type' => 'int','desc' => '直播标题',],
                'frontcover' => ['name' => 'frontcover', 'type' => 'int','desc' => '封面地址',],
                'angle' => ['name' => 'angle', 'type' => 'int','desc' => '直播角度',],
                'quality' => ['name' => 'quality', 'type' => 'int','desc' => '直播画面质量',],
                'flv_play_url' => ['name' => 'flv_play_url', 'type' => 'int','desc' => 'flv拉流地址',],
                'hls_play_url' => ['name' => 'hls_play_url', 'type' => 'int','desc' => 'hls拉流地址',],
                'start_time' => ['name' => 'start_time', 'type' => 'int','desc' => '开播时间',],
            ],
            // 直播广场
            'getPlaza' => [
                'userid' => ['name' => 'userid', 'type' => 'int','desc' => '用户唯一标识',],
                'username' => ['name' => 'username', 'type' => 'int','desc' => '用户昵称',],
                'tags' => ['name' => 'tags', 'type' => 'int','desc' => '用户标签',],
                'stream_id' => ['name' =>'stream_id','type' => 'string','desc' =>'直播流唯一标识',],
                'viewer_count' => ['name' => 'viewercount', 'type' => 'int','desc' => '观众数量',],
                'like_count' => ['name' => 'likecount', 'type' => 'int','desc' => '点赞数量',],
                'title' => ['name' => 'title', 'type' => 'int','desc' => '直播标题',],
                'frontcover' => ['name' => 'frontcover', 'type' => 'int','desc' => '封面地址',],
                'angle' => ['name' => 'angle', 'type' => 'int','desc' => '直播角度',],
                'quality' => ['name' => 'quality', 'type' => 'int','desc' => '直播画面质量',],
                'flv_play_url' => ['name' => 'flv_play_url', 'type' => 'int','desc' => 'flv拉流地址',],
                'hls_play_url' => ['name' => 'hls_play_url', 'type' => 'int','desc' => 'hls拉流地址',],
                'start_time' => ['name' => 'start_time', 'type' => 'int','desc' => '开播时间',],
            ],
            // 观众进入直播间
            'postEnterGroup' => [
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户id',],
                'liveuserid' => ['name' => 'liveuserid', 'type' => 'int', 'require' => 'true', 'desc' => '主播的用户id',],
                'group_id' => ['name' => 'groupid', 'type' => 'string', 'require' => 'true', 'desc' => '群组id',],
                'username' => ['name' => 'username', 'type' => 'string', 'require' => 'false', 'desc' => '用户昵称',],
                'avatar' => ['name' => 'avatar', 'type' => 'string', 'require' => 'false', 'desc' => '头像地址',],
            ],
            // 观众离开直播间
            'postQuitGroup' => [
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户id',],
                'liveuserid' => ['name' => 'liveuserid', 'type' => 'int', 'require' => 'true', 'desc' => '主播的用户id',],
                'groupid' => ['name' => 'groupid', 'type' => 'string', 'require' => 'true', 'desc' => '群组id',],
            ],
            // 拉取直播间观众列表
            'getGroupMemberList' => [
                'group_id' => ['name' => 'groupid', 'type' => 'string', 'require' => 'true', 'desc' => '群组id',],
                'liveuserid' => ['name' => 'liveuserid', 'type' => 'int', 'require' => 'false', 'desc' => '主播的用户id',],
                'pageno' => ['name' => 'pageno', 'type' => 'int', 'require' => 'false', 'desc' => '分页号',],
                'pagesize' => ['name' => 'pagesize', 'type' => 'int', 'require' => 'false', 'desc' => '分页大小',],
            ],
        ];
        //可以合并公共参数
        return array_merge(parent::responseRules(),$rules);
    }

    /**
     * @title 请求直播推流地址
     * @url /v1/live/RequestPushAddr
     * @type post
     * @desc 该接口用于提交直播相关数据（如用户信息以及标题、位置等直播数据）并返回推流地址，业务Server收到请求后，将直播相关数据存储到db中，观众端请求直播列表时返回该数据
     * @param Request $request
     * @return string push_url 推流地址
     * @return array play_url 3种格式拉流地址
     */
    public function postRequestPushAddr(Request $request)
    {
        if (!$request->has('userid','post',true) || !$request->has('title','post',true)){
            return $this->sendError(4001,'Missing Arguments',403);
        }
        $user = model\User::get($request->post('userid\d'));
        if (empty($user))
            return $this->sendError(4002,'用户不存在或未认证',403);
        
        $live_conf = Config::get('live_conf');
        $streamId = uniqid();
        $pushUrl = getPushUrl($live_conf['bizid'],$streamId,$live_conf['pushkey'],date('Y-m-d H:i:s',strtotime('+2 hours')));
        $playUrl = getPlayUrl($live_conf['bizid'],$streamId);
        $param = $request->post();
        $live = new model\Live();
        if (!empty($request->file())){
            $file = $request->file('frontcover');
            $file->validate(['size'=>10240000,'ext'=>'jpeg,png,jpg,gif'])->rule('date');
            $upload = $file->move( PUBLIC_PATH . 'frontcover/');
            if ( $upload ) {
                $param['frontcover'] = config('base_url') . 'frontcover/' . $upload->getSavename();
            } else {
                unset($param['frontcover']) ;
            }
        }
        $data = array_merge($param,
            [
                'push_url'=>$pushUrl,
                'rtmp_play_url'=>$playUrl[0],
                'flv_play_url'=>$playUrl[1],
                'hls_play_url'=>$playUrl[2],
                'stream_id'=>$live_conf['bizid'] .'_'. $streamId,
                'create_time'=>date('Y-m-d H:i:s'),
            ]);
        $flag = $live->save($data);
        if ($flag){
            return $this->sendSuccess(['stream_id'=>$live_conf['bizid'] .'_'. $streamId,'push_url'=>$pushUrl,'play_url'=>$playUrl]);
        }else{
            return $this->sendError(4003,'未知错误',403);
        }
    }

    /**
     * @title 修改直播状态
     * @url /v1/live/ChangeStatus
     * @type post
     * @desc 主播开始推流，并收到开始推流事件（PUSH_EVT_PUSH_BEGIN）时，调用此接口，将该流状态置为上线；主播停止推流后，调用此接口，将该流状态置为下线
     * @param \think\Request $request
     * @return
     */
    public function postChangeStatus(\think\Request $request)
    {
        if (!$request->has('userid','post',true) || !$request->has('groupid','post',true) || !$request->has('stream_id','post',true) || !$request->has('status','post',true)){
            return $this->sendError(4004,'Missing Arguments',403);
        }
        $data = $request->except('status','post');
        $status = (int)$request->post('status');
        $live = model\Live::get($data);
        if (empty($live))
            return $this->sendError(4005,'直播流ID与群组ID不匹配',403);
        
        if ($status === 1){
            $flag = $live->save(['check_status'=>$status,'start_time'=>date('Y-m-d H:i:s')]);
            $res = [];
        }
        else{
            $flag = $live->save(['check_status'=>$status,'end_time'=>date('Y-m-d H:i:s')]);
            $res['max_viewer_count'] = $live->getData('max_viewer_count');
        }
        
        if ($flag){
            return $this->sendSuccess($res);
        }
        else{
            return $this->sendError(4006,$live->getError(),403);
        }

    }

    /**
     * @title 首页直播列表
     * @url /v1/live/List
     * @type get
     * @desc 从业务Server拉取列表，供APP首页展示
     * @param \think\Request $request
     * @return int totalcount 列表总数
     * @return array list 直播/点播列表数据
     * @return int list.userid 用户id
     * @return string list.groupid 群组id
     * @return string list.stream_id 直播流id 此字段为空代表是回放视频
     * @return int list.viewercount 在线数量
     * @return int list.likecount 点赞数量
     * @return string list.title 直播标题
     * @return string list.frontcover 直播封面地址
     * @return string list.angle 直播角度
     * @return string list.flv_play_url flv播放地址
     * @return string list.hls_play_url hls播放地址
     * @return string list.start_time 开始直播的时间
     */
    public function getList(\think\Request $request)
    {
        $live = new model\Live();
        $pagesize = $request->get('pagesize\d',6);
        $pageno = $request->get('pageno\d',1);
        $data['totalcount'] = $live->where(['status'=>1,'check_status'=>1])->count();
        $data['list'] = $live
            ->field('userid,groupid,stream_id,viewer_count,like_count,title,frontcover,angle,flv_play_url,hls_play_url,start_time')
            ->where(['status'=>1,'check_status'=>1])
            ->page($pageno,$pagesize)
            ->select();
        if (empty($live->getError())){
            return $this->sendSuccess($data);
        }
        else{
            return $this->sendError(4007,$live->getError(),403);
        }

    }

    /**
     * @title 直播广场列表
     * @url /v1/live/Plaza
     * @type get
     * @desc 从业务Server拉取列表，直播广场展示
     * @param Request $request
     * @return object
     */
    public function getPlaza(Request $request)
    {
        $live = new model\Live();
        $pagesize = $request->get('pagesize\d',6);
        $pageno = $request->get('pageno\d',1);
        $data['totalcount'] = $live->where(['status'=>1,'check_status'=>1])->count();
        $data['list'] = $live
            ->field('userid,groupid,stream_id,viewer_count,like_count,title,frontcover,angle,flv_play_url,hls_play_url,start_time')
            ->where(['status'=>1,'check_status'=>1])
            ->page($pageno,$pagesize)
            ->select();
        if (empty($live->getError())){
            return $this->sendSuccess($data);
        }
        else{
            return $this->sendError(4007,$live->getError(),403);
        }

    }

    /**
     * @title 修改计数器
     * @url /v1/live/ChangeCount
     * @type post
     * @desc 该接口用于修改点赞数量，观众点赞后向业务服务器发送该协议，业务服务器修改点赞计数
     * @param \think\Request $request
     * @return object
     */
    public function postChangeCount(\think\Request $request)
    {
        $data = $request->post();
        if ( !isset($data['groupid']) || !isset($data['userid'])|| !isset($data['liveuserid']) ){
            return $this->sendError(4008,'Missing Arguments',403);
        }
        $live = new model\Live();
        $flag = $live
            ->where(['groupid'=>$data['groupid'],'userid'=>$data['liveuserid']])
            ->setInc('like_count');
        if ($flag){
            return $this->sendSuccess();
        }
        else{
            return $this->sendError(4009,$live->getError(),403);
        }
    }

    /**
     * @title 通知业务服务器有群成员进入
     * @url /v1/live/EnterGroup
     * @type post
     * @desc 如按等级排序，可由业务服务器维护群成员列表，根据需求修改排序规则。观众加群成功后，调用此接口通知业务服务器有成员进入
     * @param \think\Request $request
     * @return object
     */
    public function postEnterGroup(\think\Request $request)
    {
        $data = $request->post();
        if ( !isset($data['groupid']) || !isset($data['userid'])|| !isset($data['liveuserid']) ){
            return $this->sendError(4010,'Missing Arguments',403);
        }
        $live = model\Live::get(['groupid'=>$data['groupid'],'userid'=>$data['liveuserid']]);
        
        if (empty($live))
            return $this->sendError(4011,'房间号不存在！',403);
        $group = new model\Group();
        $flag = $group->allowField(true)->save($data);

        if ($flag){
            $live->inc('viewer_count');
            $live->save();
            $viewer_count = $live->getData('viewer_count');
            $max_viewer_count = $live->getData('max_viewer_count');
            
            if (($viewer_count+1) > $max_viewer_count){
                $live->save(['max_viewer_count'=>($viewer_count+1)]);
            }
            return $this->sendSuccess();
        }
        else{
            return $this->sendError(4011,$group->getError(),403);
        }
        
    }

    /**
     * @title 通知业务服务器有群成员退出
     * @url /v1/live/QuitGroup
     * @type post
     * @desc 如按等级排序，可由业务服务器维护群成员列表，根据需求修改排序规则。观众加群成功后，调用此接口通知业务服务器有成员进入
     * @param \think\Request $request
     * @return object
     */
    public function postQuitGroup(\think\Request $request)
    {
        $data = $request->post();
        if ( !isset($data['groupid']) || !isset($data['userid'])|| !isset($data['liveuserid']) ){
            return $this->sendError(4012,'Missing Arguments',403);
        }
        
        $flag = model\Group::where(['groupid'=>$data['groupid'],'userid'=>$data['userid'],'liveuserid'=>$data['liveuserid']])->delete();
        //$flag = model\Group::destroy(['groupid'=>$data['groupid'],'userid'=>$data['userid'],'liveuserid'=>$data['liveuserid']]);
        if ($flag){
            model\Live::where(['groupid'=>$data['groupid'],'userid'=>$data['liveuserid']])->setDec('viewer_count');
            return $this->sendSuccess();
        }
        else{
            return $this->sendError(4013,'加群记录不存在',403);
        }
    }

    /**
     * @title 拉取群成员列表
     * @url /v1/live/GroupMemberList
     * @type get
     * @desc 直播间成员列表
     * @param Request $request
     * @return int totalcount 群成员总数
     * @return array memberlist 群成员列表
     * @return int memberlist.userid 用户id
     * @return string memberlist.username 用户昵称
     * @return string memberlist.avatar 用户头像地址
     */
    public function getGroupMemberList(\think\Request $request)
    {
        if ( !$request->has('liveuserid','post',true) || !$request->has('groupid','post',true) ){
            return $this->sendError(4014,'Missing Arguments',403);
        }
        $group = new model\Group();
        $parm['liveuserid'] = $request->post('liveuserid');
        $parm['groupid'] = $request->post('groupid');
        $pagesize = $request->post('groupid',6);
        $pageno = $request->post('groupid',1);
        $data['totalcount'] = $group->count($parm);
        $data['memberlist'] = $group->field('userid,username,avatar')->page($pageno,$pagesize)->select();
        if (empty($group->getError())){
            return $this->sendSuccess($data);
        }
        else{
            return $this->sendError(4015,$group->getError(),403);
        }
        
    }
    
}