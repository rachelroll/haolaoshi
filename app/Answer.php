<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{

    protected $connection = 'mysql';
    protected $table = 'answers';
    protected $guarded = [];
    protected $with = ['user'];

    public function question()
    {
        return $this->belongsTo(Questions::class, 'question_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


}
