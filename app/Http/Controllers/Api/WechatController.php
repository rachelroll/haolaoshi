<?php

namespace App\Http\Controllers\Api;
use App\Questions;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Log;

class WechatController extends BaseController
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
            Log::info($message);

            $order = Questions::find( $message['out_trade_no']);

            if (!$order || $order->status == 1) { // 如果订单不存在 或者 订单已经支付过了
                return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }

            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                // 用户是否支付成功
                if (array_get($message, 'result_code') === 'SUCCESS') {
                    $order->updated_at = time(); // 更新支付时间为当前时间
                    $order->status = 1;

                    // 用户支付失败
                } elseif (array_get($message, 'result_code') === 'FAIL') {
                    $order->status = 2;
                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }

            $order->save(); // 保存订单

            return true;
        });

        return $response;
    }
}
