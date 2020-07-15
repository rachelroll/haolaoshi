<?php

namespace App\Http\Controllers\Api;

use App\Teacher;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TeacherController extends BaseController
{
    // 老师注册表单提交
    public function register()
    {
        $data = request()->only([
            'real_name',
            'educated',
            'graduated',
            'teachingAges',
            'certificationNumber',
            'subjects',
            'grades',
            'specialPoints',
            'results',
        ]);

        $validator = Validator::make($data, [
            'real_name' => 'required',
            'educated' => 'required',
            'graduated' => 'required',
            'teachingAges' => 'required',
            'certificationNumber' => 'required',
            'subjects' => 'required',
            'grades' => 'required',
            'specialPoints' => 'required',
            'results' => 'required',
        ], [
            'real_name.required' => '请填写真实姓名',
            'educated.required' => '请补充最高学历信息',
            'graduated.required' => '请填写毕业院校',
            'teachingAges.required' => '请补充教龄信息',
            'certificationNumber.required' => '请填写教师资格证编号',
            'subjects.required' => '请选择擅长科目',
            'grades.required' => '请选择答疑的年级范围',
            'specialPoints.required' => '请填写教学特点',
            'results.required' => '请填写教学成果',
        ]);

        if ($validator->fails()) {
            return $this->failed(2, $validator->errors()->first());
        }

        $authorized = User::find(request()->user()->id)->authorized;

        if ($authorized) {
            $res = Teacher::where('user_id', request()->user()->id)->update([
                'user_id' => request()->user()->id,
                'edu_background' => request()->educated,
                'graduated' => request()->graduated,
                'edu_ages' => request()->teachingAges,
                'certificate_no' => request()->certificationNumber,
                'subject' => json_encode(request()->subjects),
                'grade' => json_encode(request()->grades),
                'special' => request()->specialPoints,
                'result' => request()->results,
            ]);

            if ($res) {
                return $this->success('提交成功');
            }
        } else {
            $id = DB::table('teachers')->insertGetId(
                [
                    'user_id' => request()->user()->id,
                    'edu_background' => request()->educated,
                    'graduated' => request()->graduated,
                    'edu_ages' => request()->teachingAges,
                    'certificate_no' => request()->certificationNumber,
                    'subject' => json_encode(request()->subjects),
                    'grade' => json_encode(request()->grades),
                    'special' => request()->specialPoints,
                    'result' => request()->results,
                ]
            );

            if ($id) {
                $res = User::where('id', request()->user()->id)->update([
                    'teacher_id' => $id,
                    'authorized' => 1
                ]);

                if ($res) {
                    return $this->success('提交成功');
                }
            } else {
                return $this->failed('信息保存失败');
            }
        }
    }

    // 请求老师表单
    public function registerCreate()
    {
        $educated = Teacher::EDUCATED;
        $teachingAges = Teacher::TEACHINGAGES;
        $title = Teacher::TITLE;

        return $this->success(compact('educated', 'teachingAges', 'title'));
    }
}
