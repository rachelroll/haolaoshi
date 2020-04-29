<?php

namespace App\Http\Controllers\Api;

use App\Questions;
use Illuminate\Http\Request;

class QuestionController extends BaseController
{

    public function list()
    {
        $subject_id = request()->get('subject_id', 0);

        $condition = [];
        if ($subject_id) {
            $condition = [
                'subject_id' => $subject_id,
            ];
        }

        $data = [];
        $questions = Questions::has('answers', '>=', 3)->with('answers')->where($condition)->get();

        dd($questions);



        //$questions->each(static function ($item) {
        //    $item->load('answers');
        //});

        $questions->each(static function ($item) use (&$data) {
            $data[] = [
                'content'       => $item->content,
                'pics'          => $item->photos,
                'teacher_id'    => $item->teacher_id,
                'subject_id'    => $item->subject_id,
                'parent_id'     => $item->parent_id,
                'thumbs'        => $item->thumbs,
                'published'     => $item->published,
                'enabled'       => $item->enabled,
                'created_at'    => $item->created_at,
            ];
        });

    }
}
