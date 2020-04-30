<?php

namespace App\Http\Controllers\Api;

use App\Questions;
use Illuminate\Http\Request;

class QuestionController extends BaseController
{

    /**
     * @return mixed
     */
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
        $questions = Questions::has('answers', '>=', 3)->where($condition)->get();

        $questions->each(static function ($item) use (&$data) {
            $data[] = [
                'content'    => $item->content,
                'pics'       => $item->photos,
                'teacher_id' => $item->teacher_id,
                'subject_id' => $item->subject_id,
                'parent_id'  => $item->parent_id,
                'thumbs'     => $item->thumbs,
                'published'  => $item->published,
                'enabled'    => $item->enabled,
                'created_at' => $item->created_at,
            ];
        });

        return $this->success($data);
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function show($id)
    {
        $question = Questions::with([
            'answers' => function ($query) {
                $query->select([
                    'content',
                    'voice_reply',
                    'photos',
                    'type',
                    'teacher_id',
                    'question_id',
                    'created_at',
                ])->orderBy('created_at', 'DESC');
            },
        ])->select()->find($id);

        $data = [];
        $question->answers->each(static function ($item) use (&$data) {
            $data[] = [
                'content'     => $item->content,
                'voice_reply' => $item->voice_reply,
                'photos'      => $item->photos,
                'type'        => $item->type,
                'teacher_id'  => $item->teacher_id,
                'question_id' => $item->question_id,
                'created_at'  => $item->created_at,
            ];
        });

        return $this->success($data);
    }
}
