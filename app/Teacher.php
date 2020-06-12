<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{

    protected $guarded = [];

    const EDUCATED = ['请选择', '博士', '硕士', '本科', '专科'];
    const TEACHINGAGES = ['请选择', '1-3年', '4-6年', '7年及以上'];
    const TITLE = ['无', '三级教师', '二级教师', '一级教师', '高级教师', '正高级教师'];
}
