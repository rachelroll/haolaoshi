<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use EasyWeChat\Factory;

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
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $openid,
        ]);

        return $result;
    }
}
