<?php
namespace app\v1\model;

use \think\Model;
class User extends Model
{
    protected $pk = 'userid';
    protected $autoWriteTimestamp = 'datetime';
    // 自动完成
    protected $auto = ['password'];
    protected function setPasswordAttr($value){
        return empty($value) ? NULL : md5($value) ;
    }

    /**
     * @title 添加用户
     */
    public function add($data){
        if (isset($data['umid'])){
            $data['userid'] = $this->allowField('umid')->save($data);
        }
        else {
            $data = ['tel'=>$data['tel'],'password'=>$data['password'],'followers'=>666];
            $data['userid'] = $this->allowField('tel,password')->save($data);
        }
        if (! $data['userid'])
            return FALSE;
        $data['usersig'] = signature($data['userid']);
        $flag = $this->name('user_info')->insert($data);
        if (!$flag)
            return FALSE;
        return $this->search(['userid'=>$data['userid']]);
    }

    /**
     * @title 添加关注
     */
    public function addFollow($userid,$follow_userid){
        $res = $this->whereIn('userid',[$userid,$follow_userid])->count('userid');
        if ($res !== 2){
            $this->error = '关注用户不存在';
            return false;
        }
        return $this->name('user_follow')->insert(['userid'=>$userid,'follow_userid'=>$follow_userid]);
    }
    /**
     * @title 粉丝列表
     */
    public function followers($userid){
        $userids = $this->name('user_follow')->where('follow_userid',$userid)->field('userid')->select();
        // todo 使用TP5的关联模型来修改下面代码
        $sql = " SELECT b.userid,b.username,b.signature,c.MS FROM cnfol_user_tag a INNER JOIN cnfol_user_info b on a.userid=b.userid INNER JOIN cnfol_system_const c ON c.LB=1001 AND a.tagid=c.DM WHERE A.userid IN :userids ";
        $data = $this->query($sql,['userids'=>$userids]);
        $res = [];
        $arr = [];
        foreach ($data as $k => $v){
            if (array_key_exists($v['userid'],$res)){
                array_push($res[$v['userid']]['tags'],$v['MS']);
            }else{
                $v['tags'] = [$v['MS']];
                unset($v['MS']);
                $res[$v['userid']] = $v;
            }
        }
    }

    /**
     * @title 修改用户密码
     */
    public function resetpwd($tel,$pass){
        return $this->isUpdate(true)->save(['password'=>$pass,'wrong_times'=>5],['tel'=>$tel]);
    }

    /**
     * @title 查找用户
     */
    public function search($data){
        if (isset($data['userid'])){
            $res = $this->field('a.userid,a.authenticated,a.followers,a.following,b.username,b.usersig,b.avatar,b.signature')
                ->name('user')->alias('a')
                ->join('__USER_INFO__ b','a.userid=b.userid')
                ->where($data)->find();
        }
        else {
            $res = $this->field('a.userid,a.authenticated,a.followers,a.following,b.username,b.usersig,b.avatar')
                ->name('user')->alias('a')
                ->join('__USER_INFO__ b','a.userid=b.userid')
                //todo 根据需求自定义修改where条件
                ->where($data)
                ->select();
        }
        return $res;
        
    }
}

/* End of file User.php */