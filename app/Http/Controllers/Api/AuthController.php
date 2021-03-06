<?php

namespace App\Http\Controllers\Api;

use App\Questions;
use App\Teacher;
use App\User;
use EasyWeChat\Factory;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function login()
    {
        $code = request()->get('code');
        $config = config('wechat.mini_program.default');
        $app = Factory::miniProgram($config);
        $session = $app->auth->session($code);
        $openid = $session['openid'];
        //$openid = "oFYRe5Q9G-nWbU30V9T_V19xV2YQ";
        $user = User::firstOrCreate(
            ['openid' => $openid],
            ['login_time' => now()]
        );

        $token = $user->createToken('Token Name')->accessToken;

        return $this->success([
            'token'=>$token,
        ]);
    }

    public function checkToken()
    {
        $user_id = request()->user()->id;

        if ($user_id) {
            return $this->success('未过期');
        }
    }
}
