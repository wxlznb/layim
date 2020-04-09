<?php


namespace app\gateway;

use GatewayWorker\Lib\Gateway;
use think\facade\Cache;

class Events
{
    public static function onMessage($client_id, $data)
    {
        $data = json_decode($data, true);
        switch ($data['type']) {
            case 'message':
                // 返回给客户端的数据
                $res = [
                    'emit' => 'message',
                    'data' => [
                        'username' => $data['data']['mine']['username'],
                        'avatar' => $data['data']['mine']['avatar'],
                        'id' => $data['data']['mine']['id'],
                        'type' => $data['data']['to']['type'],
                        'content' => $data['data']['mine']['content'],
                        'mine' => false,
                        'fromid' => $data['data']['mine']['id'],
                        'timestamp' => time() * 1000,
                    ]
                ];

                // 存储聊天记录
                $from_id = $data['data']['mine']['id']; // 发送者id
                $to_id = $data['data']['to']['id']; // 接收者id
                $redis = Cache::store('redis')->handler(); // 获取redis对象实例
                $chaLog = $data['data']['mine'];
                unset($chaLog['mine']);
                $chaLog['timestamp'] = time() * 1000;

                // 永远把id小的放前面
                $arr = [$from_id, $to_id];
                sort($arr);
                $redis->rPush("chat:{$arr[0]}:{$arr[1]}", json_encode($chaLog, 258));

                // 'chat:1:2' 1给2发送消息
//                if ($redis->exists("chat:{$from_id}:{$to_id}")){
//                    $redis->rPush("chat:{$from_id}:{$to_id}", json_encode($chaLog, 258));
//
//                }elseif ($redis->exists("chat:{$to_id}:{$from_id}")){
//                    $redis->rPush("chat:{$to_id}:{$from_id}", json_encode($chaLog, 258));
//
//                }else{
//                    $redis->rPush("chat:{$from_id}:{$to_id}", json_encode($chaLog, 258));
//
//                }

                // 向指定用户发送数据
                Gateway::sendToUid($data['data']['to']['username'], json_encode($res,258));
                break;
        }
    }

    public static function onWebSocketConnect($client_id, $data)
    {
        $token = $data['get']['token'];
        $key = config('chat.key');
        $uid = decrypt($token, $key);
        // 绑定客户端id
        Gateway::bindUid($client_id, $uid);
    }
}
