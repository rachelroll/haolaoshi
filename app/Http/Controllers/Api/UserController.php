<?php

namespace App\Http\Controllers\Api;

use App\User;
use Illuminate\Http\Request;

class UserController extends BaseController
{

    public function roleCreate()
    {
        $role = request()->get('type');
        $grade = request()->get('grade') ?? 0;

        $user_id = request()->user()->id;

        // 老师
        if ($role == 0) {
            $param = ['role' => $role];
        } else {
            // 学生
            $param = [
                'role' => $role,
                'grade' => $grade
            ];
        }

        $res = User::where('id', $user_id)->update($param);

        if ($res) {
            return $this->success(['role' => $role]);
        }
    }

    public function getRole()
    {
        $user_id = request()->user()->id;
        $role = User::where('id', $user_id)->pluck('role')->all()[0];
        if ($role == 9) {
            return $this->failed('没有尚未创建角色');
        }
        
        return $this->success([
            'role' => $role,
        ]);
    }
}
