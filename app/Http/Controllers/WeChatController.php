<?php

namespace App\Http\Controllers;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Log;

class WeChatController extends Controller
{
    //
    public function unifiedOrder($order_id, $total_fee, $openid)
    {
        $config = config('wechat.payment.default');

        $app = Factory::payment($config);

        $result = $app->order->unify([
            'body' => '咖啡猫在线-答题付费',
            'out_trade_no' => $order_id,
            'total_fee' => $total_fee,
            'trade_type' => 'JSAPI',
            'openid' => $openid,
            'notify_url' => 'https://teacher.cafecatedu.com/api/v1/wechat/wechat-notify'
        ]);

        return $result;
    }

    public function wechatNotify()
    {
        $config = config('wechat.payment.default');

        $app = Factory::payment($config);


        $response = $app->handlePaidNotify(function ($message, $fail) {
            // 你的逻辑
            //return true;

            Log::info($message);

            // 或者错误消息
            //$fail('Order not exists.');
        });

        return $response;
    }
}
