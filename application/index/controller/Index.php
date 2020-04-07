<?php
namespace app\index\controller;

use think\Controller;
use think\facade\Cache;
use think\facade\Request;

class Index extends Controller
{
    public function index()
    {
        return view();
    }

    public function init()
    {
        $redis = Cache::store('redis')->handler();
        $key = config('chat.key');
        $token = input('token');
        $username = decrypt($token, $key);
        $userInfo = $redis->hGetAll('user:' . $username);
        $keys = $redis->keys('user:*');
        $list = [];
        $count = 0;
        foreach ($keys as $k){
            if ($k === 'user:' . $username || $k === 'user:id'){
                continue;
            }
            if ($redis->hMget($k, ['status'])['status'] === 'online'){
                $count++;
            }
            $list[] = $redis->hGetAll($k);
        }
        $res = [
            'code' => 0,
            'msg' => '',
            'data' => [
                'mine' => $userInfo,
                'friend' => [
                    [
                        'groupname' => 'worker实战小组',
                        'id' => 1,
                        'online' => $count,
                        'list' => $list
                    ]
                ]
            ]
        ];
        return json($res);
    }

    public function register()
    {
        if (Request::isPost()) {
            $data = Request::post();
            // redis存用户信息 hash
            $redis = Cache::store('redis')->handler();
            if ($redis->hGetAll('user:' . $data['username'])){
                $res = [
                    'code' => 1,
                    'msg' => '该用户名已被注册',
                    'data' => []
                ];
                return json($res);
            }
            $user_id = $redis->incr('user:id');
            $userInfo = [
                'id' => $user_id,
                'username' => $data['username'],
                'password' => md5($data['pass']),
                'sign' => '这个家伙很懒,什么都没有留下~',
                'avatar' => 'http://cdn.firstlinkapp.com/upload/2016_6/1465575923433_33812.jpg',
                'status' => 'online'
            ];
            $redis->hMset('user:' . $data['username'], $userInfo);
            //session('username', $data['username']);
            $key = config('chat.key');
            $token = encrypt($data['username'], $key);
            $res = [
                'code' => 0,
                'msg' => '注册成功',
                'data' => [
                    'token' => $token
                ]
            ];
            return json($res);
        }
        return view();
    }

    public function login()
    {
        if (Request::isPost()) {
            $data = Request::post();
            $redis = Cache::store('redis')->handler();
            $userInfo = $redis->hGetAll('user:' . $data['username']);
            $key = config('chat.key');
            $token = encrypt($data['username'], $key);
            if (empty($userInfo)) {
                $res = [
                    'code' => 1,
                    'msg' => '该用户不存在!',
                    'data' => []
                ];
                return json($res);
            }else{
                if ($data['username'] === $userInfo['username'] && md5($data['pass']) === $userInfo['password']){
                    $res = [
                        'code' => 0,
                        'msg' => '登录成功!',
                        'data' => [
                            'token' => $token
                        ]
                    ];
                    session('username', $data['username']);
                    return json($res);
                }else{
                    $res = [
                        'code' => 1,
                        'msg' => '用户名或密码错误!',
                        'data' => []
                    ];
                    return json($res);
                }
            }
        }
        return view();
    }
}
