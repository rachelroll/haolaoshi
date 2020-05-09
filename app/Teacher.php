<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{

    public function questions()
    {
        return $this->hasMany(Questions::class, 'teacher_id', 'id');
    }
}
