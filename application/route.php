<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;
Route::rest('update', ['post', '/:id', 'update']);
Route::resource('common','common/Common');
Route::resource('menu_api','apilib/MenuApi');
Route::resource('user','demo/User');
return [
    '__pattern__' => [
        'name' => '\w+',
    ],
//    '[v1]' => [
//        'user/[:fun]' => ['demo/User/init',], //用户模块接口
//        'dynamic/[:fun]' => ['demo/Dynamic/init',], //动态模块接口
//    ],

    //认证
    '[oauth]' => [
        'accessToken' => ['demo/Auth/accessToken',],//获取令牌
        'refreshToken' => ['demo/Auth/refreshToken',],//刷新令牌
        'getServerTime' => ['demo/Auth/getServerTime',],//获取服务器时间戳
    ],

];
