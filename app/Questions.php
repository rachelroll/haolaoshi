<?php

namespace App;

use App\Scopes\QuestionScope;
use Illuminate\Database\Eloquent\Model;

class Questions extends Model
{

    protected $guarded = [];

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
}
