<?php
namespace app\index\controller;

use think\Controller;
use think\facade\Cache;
use think\facade\Request;

class Index extends Controller
{
    public function initialize()
    {
        if (!session('?uid')) {
            $this->redirect('login');
        }
    }

    public function index()
    {
        return view();
    }

    public function init()
    {
        $redis = Cache::store('redis')->handler();
        $token = input('token');
        $key = config('chat.key');
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
            session('uid', $userInfo['id']);
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
                    session('uid', $userInfo['id']);
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

    public function uploadImage()
    {
        $file     = Request::file('file');
        $info     = $file->move( './uploads/image');
        $file_url = '/uploads/image/' . $info->getSaveName();
        if ($info) {
            $res = [
                'code' => 0,
                'msg' => '',
                'data' => [
                    'src' => $file_url,
                    'name' => $info->getFilename()
                ]
            ];
            return json($res);
        }else{
            $res = [
                'code' => 1,
                'msg' => '文件上传失败',
                'data' => []
            ];
            return json($res);
        }
    }

    public function chatlog()
    {
        $to_id = Request::param('id');
        $from_id = session('uid');
        $arr = [$from_id, $to_id];
        sort($arr);
        $redis = Cache::store('redis')->handler();
        if ($redis->exists("chat:{$arr[0]}:{$arr[1]}")){
            $chat_record = $redis->lRange("chat:{$arr[0]}:{$arr[1]}", 0, -1);
            foreach ($chat_record as $k => $v){
                $chat_record[$k] = json_decode($v, true);
            }
            $res = [
                'code' => 0,
                'msg' => '',
                'data' => $chat_record
            ];
            $this->assign('chat_record', json_encode($res, 258));
        }
        return view();
    }

    public function updateSign()
    {
        $sign = Request::post('sign');
        $token = Request::post('token');
        $key = config('chat.key');
        $username = decrypt($token, $key);
        $redis = Cache::store('redis')->handler();
        $userInfo = $redis->hGetAll('user:' . $username);
        $userInfo['sign'] = $sign;
        $redis->hMset('user:' . $username, $userInfo);
    }
}
