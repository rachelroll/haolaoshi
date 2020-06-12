<?php

namespace App\Http\Controllers\Api;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    public function roleCreate()
    {
        $role = request()->get('type');
        $grade = request()->get('grade') ?? 0;

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

        $res = User::where('id', request()->user()->id)->update($param);

        if ($res) {
            return $this->success(['role' => $role]);
        }
    }

    public function getRole()
    {
        $role = User::where('id', request()->user()->id)->pluck('role')->all()[0];
        if ($role == 9) {
            return $this->failed('没有尚未创建角色');
        }

        return $this->success([
            'role' => $role,
        ]);
    }

    public function setting()
    {
        $role = (int)request()->get('role');
        $grade = (int)request()->get('grade') ?? 0;

        if ($role == 0) {
            $authorized = User::find(request()->user()->id)->authorized;

            if ($authorized == 1) {
                $res = User::where('id', request()->user()->id)->update([
                    'role' => $role,
                ]);
                if ($res) {
                    return $this->success(['role' => 0]);
                }
            } else {
                return $this->failed('尚未注册老师', 201);
            }
        }

        $res = User::where('id', request()->user()->id)->update([
            'role' => 1,
            'grade' => $grade
        ]);

        $data = [
            'role' => 1,
            'grade' => $grade
        ];
        if ($res) {
            return $this->success($data);
        }
    }

    public function getGrade()
    {
        $grade = User::find(request()->user()->id)->grade;

        return $this->success($grade);
    }
}
