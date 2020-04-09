<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Route;

Route::rule('register', 'index/index/register', 'get|post');
Route::rule('login', 'index/index/login', 'get|post');
Route::get('chat', 'index/index/index');
Route::post('init', 'index/index/init');
Route::rule('uploadImage', 'index/index/uploadImage', 'post');
Route::rule('chatlog', 'index/index/chatlog', 'get');
Route::post('updateSign', 'index/index/updateSign');
