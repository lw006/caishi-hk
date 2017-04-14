<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

//------------------------
// App 自定义助手函数
//-------------------------

use think\Cache;
use think\Config;
use think\Cookie;
use think\Db;
use think\Debug;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\Lang;
use think\Loader;
use think\Log;
use think\Model;
use think\Request;
use think\Response;
use think\Session;
use think\Url;
use think\View;


// 用户自定义函数

/**
 * 获取 UserSig
 * @param1 用户标识符
 * @param2 应用id
 * @param3 私钥存放路径 使用绝对路径
 * @param4 signature工具命令的路径
 * @return userSig
 */
function signature($identifier, $sdkappid='', $private_key_path='',$tool_path='')
{
    $live_conf = Config::get('live_conf');
    empty($tool_path)?$tool_path=$live_conf['tool_path']:'';
    empty($private_key_path)?$private_key_path=$live_conf['private_key_path']:'';
    empty($sdkappid)?$sdkappid=$live_conf['SdkAppId']:'';
    
    # 这里需要写绝对路径，开发者根据自己的路径进行调整
    $command = $tool_path
        . ' ' . escapeshellarg($private_key_path)
        . ' ' . escapeshellarg($sdkappid)
        . ' ' . escapeshellarg($identifier);
    $ret = exec($command, $out, $status);
    if ($status == -1)
    {
        return null;
    }
    return $out[0];
}

/**
 * 获取推流地址
 * 如果不传key和过期时间，将返回不含防盗链的url
 * @param $bizId 腾讯云分配到的bizid
 * @param $streamId 用来区别不通推流地址的唯一id
 * @param $key 安全密钥
 * @param $time 过期时间 sample 2016-11-12 12:00:00
 * @return String url
 */
function getPushUrl( $bizId, $streamId, $key = null, $time = null){

    $livecode = $bizId . "_" . $streamId; //直播码
    if($key && $time){
        $txTime = strtoupper(base_convert(strtotime($time),10,16));
        //txSecret = MD5( KEY + livecode + txTime )
        //livecode = bizid+"_"+stream_id  如 8888_test123456
        $livecode = $bizId."_".$streamId; //直播码
        $txSecret = md5($key.$livecode.$txTime);
        $ext_str = "?".http_build_query(array(
                "bizid"=> $bizId,
                "txSecret"=> $txSecret,
                "txTime"=> $txTime
            ));
    }
    return "rtmp://".$bizId.".livepush.myqcloud.com/live/".$livecode.(isset($ext_str) ? $ext_str : "");
}

/**
 * 获取播放地址
 * @param $bizId 腾讯云分配到的bizid
 * @param $streamId 用来区别不通推流地址的唯一id
 * @return String url
 */
function getPlayUrl($bizId, $streamId){
    $livecode = $bizId."_".$streamId; //直播码
    return array(
        "rtmp://".$bizId.".liveplay.myqcloud.com/live/".$livecode,
        "http://".$bizId.".liveplay.myqcloud.com/live/".$livecode.".flv",
        "http://".$bizId.".liveplay.myqcloud.com/live/".$livecode.".m3u8"
    );
}

/**
 * CURL请求
 *
 * @param string  $url     请求地址
 * @param array   $data    请求数据 key=>value 键值对
 * @param integer $timeout 超时时间,单位秒
 * @param integer $ishttp  是否使用https连接 0:否 1:是
 * @return array
 */
function curl_post($url, $data, $timeout = 5)
{
    $ishttp = substr($url, 0, 8) == "https://" ? TRUE : FALSE;

    $ch = curl_init();
    if (is_array($data)) {
        $data = http_build_query($data);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    if($ishttp)
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $result['data'] = curl_exec($ch);
    $result['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return $result;
}

/**
 *
 * 短信发送
 * @param 
 * @param 
 * @return multitype:string |number
 */
function sendSmsAction() {

    include EXTEND_PATH . 'taobao-sdk/TopSdk.php';
    $c = new TopClient;
    $c->appkey = '23748619';
    $c->secretKey = '7874a1875dd0949995ac4577f31ddc6e';
    $req = new AlibabaAliqinFcSmsNumSendRequest;
    $req->setExtend("123456");
    $req->setSmsType("normal");
    $req->setSmsFreeSignName("阿里大于");
    $req->setSmsParam("{\"code\":\"1234\",\"product\":\"alidayu\"}");
    $req->setRecNum("13000000000");
    $req->setSmsTemplateCode("SMS_585014");
    $resp = $c->execute($req);
    dump($resp);
}


			
