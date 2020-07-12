<?php

namespace App;

use App\Scopes\QuestionScope;
use Illuminate\Database\Eloquent\Model;

class Questions extends Model
{

    protected $guarded = [];

    protected $casts = [
        'photos' => 'array'
    ];

    public const SUBJECT_NAME = ['全部', '语文', '数学', '英语', '物理', '生物', '化学', '历史', '地理'];
    public const GRADE = [
        '一年级',
        '二年级',
        '三年级',
        '四年级',
        '五年级',
        '六年级',
        '初一',
        '初二',
        '初三',
        '高一',
        '高二',
        '高三',
    ];

    public const STATUS = ['待支付', '匹配老师中', '进行中', '已结束'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new QuestionScope);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'question_id', 'id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}










