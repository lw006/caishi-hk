<?php
namespace app\v1\controller;
use app\common\controller\Api;
use app\v1\model;
use think\Db;
use think\Exception;
use think\Request;
use think\Loader;
use think\Cache;


/**
 * Class User
 * @title 用户接口
 * @url /v1/user
 * @version 0.1
 * @desc  用户接口,该返回字段为每次请求的格式。对应接口的返回值仅为data中的内容
 * @return int errorno 请求成功标志
 * @return string message 错误提示信息
 * @return object data 返回数据
 * @readme
 */
class User extends Api
{
    // 允许访问的请求类型
    public $restMethodList = 'get|post|put';

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
            'all' => [
            ],
            'getTags' => [
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'false', 'desc' => '用户唯一标识,不传默认获取所有标签', ],
            ],
            'getSmsverify' => [
                'tel' => ['name' => 'tel', 'type' => 'string', 'require' => 'true', 'desc' => '接收验证码的手机号',],
            ],
            'postRegister' => [
                'tel' => ['name' => 'tel', 'type' => 'string', 'require' => 'true', 'desc' => '用户电话',],
                'verifycode' => ['name' => 'code', 'type' => 'int', 'require' => 'true', 'desc' => '验证码',],
                'password' => ['name' => 'password', 'type' => 'string', 'require' => 'true', 'desc' => '密码',],
                'username' => ['name' => 'username', 'type' => 'string', 'require' => 'true', 'desc' => '用户昵称', ],
            ],
            'postLogin' => [
                'tel' => ['name' => 'tel', 'type' => 'string', 'require' => 'true', 'desc' => '用户电话', ],
                'password' => ['name' => 'password', 'type' => 'string', 'require' => 'true','desc' => '密码',],
            ],
//            'postOtherLogin' => [
//                'uid' => ['name' => 'uid', 'type' => 'string', 'require' => 'true', 'desc' => '友盟生成的用户唯一标识', ],
//                'username' => ['name' => 'username', 'type' => 'string', 'require' => 'true', 'desc' => '用户昵称', ],
//                'avatar' => ['name' => 'avatar', 'type' => 'string', 'require' => 'true', 'desc' => '用户头像URL', ],
//            ],
            'postEdit' => [
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户唯一标识',],
                'avatar' => ['name' => 'avatar', 'type' => 'image', 'require' => 'false', 'desc' => '用户头像',],
                'username' => ['name' => 'username', 'type' => 'string', 'require' => 'false', 'desc' => '用户昵称',],
                'usertags' => ['name' => 'usertags', 'type' => 'array', 'require' => 'false', 'desc' => '用户标签',],
                'signature' => ['name' => 'signature', 'type' => 'string', 'require' => 'false', 'desc' => '用户个性签名',],
            ],
            'postResetpwd' => [
                'tel' => ['name' => 'tel', 'type' => 'string', 'require' => 'true', 'desc' => '用户电话', ],
                'verifycode' => ['name' => 'code', 'type' => 'int', 'require' => 'true', 'desc' => '验证码',],
                'password' => ['name' => 'password', 'type' => 'string', 'require' => 'true','desc' => '密码',]
            ],
            'postAuthenticate'=>[
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户唯一标识',],
                'realname' => ['name' => 'realname', 'type' => 'string', 'require' => 'true', 'desc' => '用户真实姓名',],
                'IDCardNo' => ['name' => 'IDCardNo', 'type' => 'string', 'require' => 'true', 'desc' => '用户身份证号',],
                'IDCardImg' => ['name' => 'IDCardImg', 'type' => 'image', 'require' => 'true', 'desc' => '用户身份证照片',],
            ],
            'getAutherized'=>[
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户唯一标识',]
            ],
            'postFollow'=>[
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户唯一标识',],
                'follow_userid' => ['name' => 'follow_userid', 'type' => 'int', 'require' => 'true', 'desc' => '被关注用户唯一标识',],
            ],
            'getFollowers'=>[
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户唯一标识',],
            ],
            'getFollowing'=>[
                'userid' => ['name' => 'userid', 'type' => 'int', 'require' => 'true', 'desc' => '用户唯一标识',],
            ],
        ];
        //可以合并公共参数
        return array_merge(parent::requestRules(), $rules);
    }

    public static function responseRules()
    {
        $rules = [
            'all' =>[],
            'getTags' => [
                'DM' => ['name' => 'DM', 'type' => 'int', 'desc' => '标签代码',],
                'MS' => ['name' => 'MS', 'type' => 'string', 'desc' => '标签描述',],
            ],
            'getSmsverify' => [
                'verifycode' => ['name' => 'code', 'type' => 'int', 'desc' => '验证码',],
            ],
            'postRegister' => [
                'userid' => ['name' => 'userid','type' => 'int','desc' => '用户ID(也是腾讯IM唯一标识identifier)',],
                'username' => ['name' => 'username', 'type' => 'string', 'desc' => '用户昵称',],
                'usersig' => ['name' => 'usersig', 'type' => 'string', 'desc' => '腾讯IM签名',],
                'authenticated' => ['name' => 'authenticated', 'type' => 'int', 'desc' => '是否认证标识',],
                'followers' => ['name' => 'followers', 'type' => 'int', 'desc' => '粉丝数量',],
                'following' => ['name' => 'following', 'type' => 'int', 'desc' => '关注数量',],
                'avatar' => ['name' => 'avatar', 'type' => 'string', 'desc' => '用户头像URL',],
            ],
            'postLogin' => [
                'userid' => ['name' => 'userid','type' => 'int','desc' => '用户ID(也是腾讯IM唯一标识identifier)',],
                'username' => ['name' => 'username', 'type' => 'string', 'desc' => '用户昵称', ],
                'usersig' => ['name' => 'usersig', 'type' => 'string', 'desc' => '腾讯IM签名', ],
                'authenticated' => ['name' => 'authenticated', 'type' => 'int', 'desc' => '是否认证标识', ],
                'followers' => ['name' => 'followers', 'type' => 'int', 'desc' => '粉丝数量', ],
                'following' => ['name' => 'following', 'type' => 'int', 'desc' => '关注数量', ],
                'avatar' => ['name' => 'avatar', 'type' => 'string', 'desc' => '用户头像URL', ],
            ],
//            'postOtherLogin' => [
//                'uid' => ['name' => 'uid', 'type' => 'string', 'require' => 'true', 'desc' => '友盟生成的用户唯一标识', ],
//                'username' => ['name' => 'username', 'type' => 'string', 'require' => 'true', 'desc' => '用户昵称', ],
//                'avatar' => ['name' => 'avatar', 'type' => 'string', 'require' => 'true', 'desc' => '用户头像URL', ],
//            ],
            'postEdit' => [
                'userid' => ['name' => 'userid','type' => 'int','desc' => '用户ID(也是腾讯IM唯一标识identifier)',],
                'username' => ['name' => 'username', 'type' => 'string', 'desc' => '用户昵称', ],
                'authenticated' => ['name' => 'authenticated', 'type' => 'int', 'desc' => '是否认证标识',],
                'followers' => ['name' => 'followers', 'type' => 'int', 'desc' => '粉丝数量', ],
                'following' => ['name' => 'following', 'type' => 'int', 'desc' => '关注数量', ],
                'avatar' => ['name' => 'avatar', 'type' => 'string', 'desc' => '用户头像URL', ],
            ],
            'postResetpwd' => [

            ],
            'postAuthenticate'=>[
                'ispass'=>['name' => 'ispass', 'type' => 'int', 'desc' => '用户认证状态 0：未通过   1：审核中   2：已通过', ]
            ],
            'getAutherized'=>[
                'ispass'=>['name' => 'ispass', 'type' => 'int', 'desc' => '用户认证状态 0：未通过   1：审核中   2：已通过', ],
                'userinfo'=>['name' => 'userinfo', 'type' => 'object', 'desc' => '认证通过后更新的用户信息',],
                'message'=>['name' => 'errorinfo', 'type' => 'string', 'desc' => '未通过认证的提示信息',],
            ],
            'getFollowing'=>[
                'userid' => ['name' => 'userid', 'type' => 'int','desc' => '用户唯一标识',],
                'useranme' => ['name' => 'useranme', 'type' => 'string','desc' => '用户昵称',],
                'signature' => ['name' => 'signature', 'type' => 'string','desc' => '用户签名',],
                'tags' => ['name' => 'tags', 'type' => 'array','desc' => '用户标签',],
            ],
            'getFollowers'=>[
                'userid' => ['name' => 'userid', 'type' => 'int','desc' => '用户唯一标识',],
                'useranme' => ['name' => 'useranme', 'type' => 'string','desc' => '用户昵称',],
                'signature' => ['name' => 'signature', 'type' => 'string','desc' => '用户签名',],
                'tags' => ['name' => 'tags', 'type' => 'array','desc' => '用户标签',],
            ],
            'getFavorite'=>[
                
            ],
        ];
        return array_merge(parent::responseRules(), $rules);
    }

    /**
     * @title 获取所有标签接口
     * @url /v1/user/tags
     * @type get
     * @desc 注册新用户时选择标签使用
     * @return int DM 标签代码
     * @return string MS 标签描述
     */
    public function getTags()
    {
        $userid = request()->get('userid');
        if (empty($userid)){
            $tags = Db::name('system_const')->where(['LB'=>1001])->field('DM,MS')->select();
            return $this->sendSuccess($tags);
        }
        else {
//            $tags = Db::query("SELECT b.MS FROM cnfol_user_tag  a INNER JOIN cnfol_system_const b ON a.tagid=b.DM AND b.LB=1001 WHERE a.userid=?",[$userid]);
//            return $this->sendSuccess($tags);
        }
    }

    /**
     * @title 短信验证码接口
     * @url /v1/user/smsverify
     * @type get
     * @desc 获取短信验证码 允许每个手机号3分钟获取一次
     * @param Request $request
     * @return int code 短信验证码
     */
    public function getSmsverify(Request $request)
    {
        $tel = $request->get('tel');
        $telPatten = '/^(1(([35][0-9])|(47)|[8][0126789]))\d{8}$/';
        if ( ! preg_match($telPatten,$tel) )
            return $this->sendError(1002,'手机号非法',403);

        if (Cache::has('tel_'.$tel))
            return $this->sendError(1002,'验证码获取间隔不少于3分钟',403);
        $code = rand(100000,999999);
        // todo 此处发送验证码到对应手机号
        Cache::set('tel_'.$tel,$code,180);
        return $this->sendSuccess(['verifycode'=>$code]);
    }
    


    /**
     * @title 用户注册接口
     * @url /v1/user/Register
     * @type post
     * @desc 注册新用户
     * @readme
     * @param \think\Request $request
     * @return int userid 用户id
     * @return string username 用户昵称
     * @return string tel 用户电话
     * @return string avatar 头像地址
     * @return string create_time 注册时间
     * @return string group_id 用于直播时的房间号
     * @return string usersig 用于登录腾讯IM验证签名
     * @return int authenticated 是否为认证用户
     */
    public function postRegister(Request $request)
    {
        $verifycode = $request->post('verifycode');
        $tel = $request->post('tel');
        $username = $request->post('username');
        $password = $request->post('password');
        if ($verifycode === NULL || $tel === NULL || $password === NULL )
            return $this->sendError(4001,'缺少参数',403);
        //todo 检验手机短信验证码

        $telPatten = '/^(1(([35][0-9])|(47)|[8][0126789]))\d{8}$/';
        if ( ! preg_match($telPatten,$tel) )
            return $this->sendError(4002,'手机号非法',403);
        elseif (! empty(Db::name('user')->where(['tel'=>$tel])->find()))
            return $this->sendError(4002,'该手机号已被注册',403);

        $usernmPatten = '/^[a-zA-Z\x{4e00}-\x{9fa5}]{4,14}$/u';
        if (! preg_match($usernmPatten,$username))
            return $this->sendError(4003,'用户名不符合规范',403);
        elseif (! empty(Db::name('user_info')->where(['username'=>$username])->find()))
            return $this->sendError(4003,'该昵称已被使用',403);

        $passwdPatten = '/^[0-9a-zA-Z]{6,16}$/';
        if (! preg_match($passwdPatten,$password))
            return $this->sendError(4004,'密码不符合规范',403);
        // 验证通过，开始写库
        $user = new model\User();
        $info = $user->add(['tel'=>$tel,'password'=>$password,'username'=>$username]);
        if (!$info)
            return $this->sendError(4005,$user->getError(),403);
        return $this->sendSuccess($info);
    }

    /**
     * @title 用户登录接口
     * @url /v1/user/Login
     * @type post
     * @desc 用户登录
     * @readme
     * @param \think\Request $request
     * @return int userid 用户id
     * @return string username 用户昵称
     * @return string tel 用户电话
     * @return string avatar 头像地址
     * @return string email 邮箱
     * @return string created_at 注册时间
     * @return string updated_at 更新时间
     * @return string identifier 用于腾讯IM唯一标识符
     * @return string usersig 用于登录腾讯IM验证签名
     * @return int authenticated 是否为认证用户
     */
    public function postLogin(Request $request)
    {
        $data['tel'] = $request->get('tel');
        $data['password'] = $request->get('password');

        $userInfo = Db::name('user')
            ->where(['tel'=>$data['tel']])
            ->field('userid,username,password,tel,avatar,email,created_at,updated_at,identifier,usersig,authenticated,wrong_time')
            ->find();
        if(empty($userInfo)){
            return $this->sendError(2001,'手机号未注册',201);
        }
        elseif ($userInfo['password'] !== md5($data['password'])){
            if ($userInfo['wrong_time'] == 50){
                return $this->sendError(2001,'错误次数超过最大限制',201);
            }else{
                Db::name('user')
                    ->where(['tel'=>$data['tel']])
                    ->setInc('wrong_time');
                return $this->sendError(2001,'密码错误,您还有'.(50-$userInfo['wrong_time']).'次登录机会',201);
            }
        }
        else{
            unset($userInfo['password'],$userInfo['wrong_time']);
            return $this->sendSuccess($userInfo);
        }
    }
    
    /**
     * @title 修改用户资料接口
     * @url /v1/user/Edit
     * @type post
     * @desc 修改用户资料接口
     * @param \think\Request $request
     * @return object
     */
    public function postEdit(Request $request)
    {
        $userid = $request->post('userid/d');
        if ($userid === null){
            return $this->sendError(4003,'Missing Agruments!',403);
        }
        $user = model\User::get($userid);
        //halt($user);
        if (!empty($request->file())){
            $file = $request->file('avatar');
            $file->validate(['size'=>10240000,'ext'=>'jpeg,png,jpg,gif'])->rule('date');
            $upload = $file->move( PUBLIC_PATH . 'avatar/');
            if ( $upload ) {
                $avatar = str_replace(config('base_url'),PUBLIC_PATH,$user->getData('avatar'));
                $data['avatar'] = config('base_url') . 'avatar/' . $upload->getSavename();
                $data['updated_at'] = date('Y-m-d H:i:s');
                $flag = $user->allowField(true)->save($data);
                if ($flag){
                    is_file($avatar) && unlink($avatar);
                    return $this->sendSuccess();
                }else{
                    return $this->sendError(4003,$user->getError(),403);
                }
            } else {
                $error = $file->getError();
                return $this->sendError(403,$error,403);
            }
        }else{
            $data = $request->except('userid','post');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $flag = $user->allowField(true)->save($data);
            if ($flag){
                return $this->sendSuccess();
            }else{
                return $this->sendError(4003,$user->getError(),403);
            }
        }

    }

    /**
     * @title 找回(重置)密码
     * @url /v1/user/resetpwd
     * @type post
     * @desc 修改用户资料接口
     * @param \think\Request $request
     * @return Exception | NULL
     */
    public function postResetpwd(Request $request){
        $verifycode = $request->post('verifycode');
        $tel = $request->post('tel');
        $password = $request->post('password');
        if ($verifycode === NULL || $tel === NULL || $password === NULL )
            return $this->sendError(4001,'缺少参数',403);
        //todo 检验手机短信验证码
        
        $telPatten = '/^(1(([35][0-9])|(47)|[8][0126789]))\d{8}$/';
        if ( ! preg_match($telPatten,$tel) )
            return $this->sendError(4002,'手机号非法',403);
        elseif ( empty(Db::name('user')->where(['tel'=>$tel])->find()) )
            return $this->sendError(4002,'手机号未注册',403);
        
        $passwdPatten = '/^[0-9a-zA-Z]{6,16}$/';
        if (! preg_match($passwdPatten,$password))
            return $this->sendError(4004,'密码不符合规范',403);
        
        $user = new model\User();
        $flag = $user->resetpwd($tel,$password);
        if ( !$flag )
            return $this->sendError(4004,$user->getError(),403);
        return $this->sendSuccess();
    }

    /**
     * @title 第三方用户登录接口
     * @url /demo/user/OtherLogin
     * @type post
     * @desc 友盟登录 放第二版做
     * @readme
     * @param \think\Request $request
     * @return object
     */
    public function postOtherLogin(Request $request)
    {
        $uid = $request->post('uid',null);
        if(empty($uid))return $this->sendError(4003,'Missing Arguments',403);
        $res = Db::name('user')->where('uid',$uid)->field('userid')->find();
        if ( empty($res) ){
            $data = $request->post();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['identifier'] = md5($data['created_at']);
            $data['usersig'] = signature($data['identifier']);
            Db::name('user')->insert($data);
        } else {
            $data = $request->post();
            unset($data['uid']);
            $data['updated_at'] = date('Y-m-d H:i:s');
            Db::name('user')->where($res)->update($data);
        }
        $userinfo = Db::name('user')
            ->where(['uid'=>$uid])
            ->field('userid,username,tel,avatar,email,created_at,updated_at,identifier,usersig')
            ->find();
        return $this->sendSuccess($userinfo);
    }

    /**
     * @title 申请认证
     * @url /v1/user/Authenticate
     * @type post
     * @desc 用户申请认证接口
     * @param \think\Request $request
     * @return object
     */
    public function postAuthenticate(Request $request){
        $userid = $request->post('userid');
        $realname = $request->post('realname');
        $IDCardNo = $request->post('IDCardNo');
        $IDCardImg = $request->file('$IDCardImg');
        if (empty($userid) || empty($realname) || empty($IDCardNo) || empty($IDCardImg))
            return $this->$this->sendError(4002,'缺少参数',403);
        // todo 正则判断真实姓名 身份证号


        // todo 判断并保存用户身份证图片
    }

    /**
     * @title 获取认证状态
     * @url /v1/user/Autherized
     * @type get
     * @desc 查看认证状态
     * @param Request $request
     * @return object
     */
    public function getAutherized(Request $request){
        
    }

    /**
     * @title 用户关注
     * @url /v1/user/follow
     * @type post
     * @desc 相互关注接口
     * @param \think\Request $request
     * @return Exception | NULL
     */
    public function postFollow(Request $request){
        $userid = $request->post('userid');
        $follow_userid = $request->post('follow_userid');
        $user = new model\User();
        $flag = $user->addFollow($userid,$follow_userid);
        if (!$flag)
            return $this->sendError(4004,$user->getError(),403);
        return $this->sendSuccess();
    }

    /**
     * @title 获取用户粉丝列表
     * @url /v1/user/Followers
     * @type get
     * @desc 粉丝列表
     * @param \think\Request $request
     * @return Exception | NULL
     */
    public function getFollowers(Request $request){
        $userid =  $request->get('userid');
        $user = new model\User();
        $user->followers($userid);
    }

    /**
     * @title 获取用户关注列表
     * @url /v1/user/Following
     * @type get
     * @desc 关注列表
     * @param \think\Request $request
     * @return Exception | NULL
     */
    public function getFollowing(Request $request){
        $userid = $request->get('userid');
        $user = new model\User();
    }

    /**
     * @title 获取用户的收藏
     * @url /v1/user/Favorite
     * @type get
     * @desc 用户收藏列表 放第二期做 
     * @param Request $request
     * @return Exception | NULL
     */
    public function getFavorite(Request $request){
        
    }

}