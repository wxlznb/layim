<?php


namespace app\gateway;

use GatewayWorker\Lib\Gateway;

class Events
{
    public static function onMessage($client_id, $data)
    {

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
