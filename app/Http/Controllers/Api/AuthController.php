<?php

namespace App\Http\Controllers\Api;

use App\Questions;
use App\User;
use Illuminate\Http\Request;

class AuthController extends BaseController
{

    public function login()
    {
        $code = request()->get('code');

        $config = config('wechat.mini_program.default');
        $app = Factory::miniProgram($config);
        $session = $app->auth->session($code);

        $oepnid = $session['oepnid'];

        $user = User::firstOrCreate(['openid',$oepnid]);
        $user->login_time = now();

        // Creating a token without scopes...
        $token = $user->createToken('Token Name')->accessToken;

        return $this->success([
            'token'=>$token,
        ]);
    }
}
