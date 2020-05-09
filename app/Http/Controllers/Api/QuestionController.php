<?php

namespace App\Http\Controllers\Api;

use App\Answer;
use App\Questions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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

        $questions = Questions::has('answers', '>=', 1)->where($condition)->get();

        $data = [];
        $questions->where('type',0)->chunk(2)->each(static function ($i)  use (&$data) {
            $tmp = [];
            $i->each(static function ($item) use (&$tmp) {
                $tmp['created_at'] = $item->created_at;
                $tmp['type'] = 0;
                $tmp[] = [
                    'id'    => $item->id,
                    'content'    => $item->content,
                    'teacher_id' => $item->teacher_id,
                    'subject'    => Questions::SUBJECT_NAME[ $item->subject_id ],
                    'thumbs'     => $item->thumbs,
                    'created_at' => $item->created_at,
                    'type'       => $item->type,
                ];

            });
            $data[] = $tmp;
        });

        $questions->whereNotIn('type',0)->each(static function ($item) use (&$data){

            $pics = [];
            foreach ($item->photos as $val) {
                $pics[] = env('CDN_DOMAIN') . '/haolaoshi/' . $val;
            }

            $data[] = [
                'id'         => $item->id,
                'content'    => $item->content,
                'teacher_id' => $item->teacher_id,
                'subject'    => Questions::SUBJECT_NAME[ $item->subject_id ],
                'pics'       => $pics,
                'thumbs'     => $item->thumbs,
                'created_at' => $item->created_at,
                'type'       => $item->type,
            ];
        });

        $data = collect($data)->sortByDesc('created_at')->values();

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
            'teacher' => function ($query) {
                $query->select([
                    'id',
                    'avatar'
                ]);
            }
        ])->select()->find($id);

        $pics = [];
        foreach ($question->photos as $val) {
            $pics[] = env('CDN_DOMAIN') . '/haolaoshi/' . $val;
        }

        $date = new Carbon($question->created_at);
        $date = $date->format('Y/m/d H:i');

        $first_question = [
            'photos' => $pics,
            'id' => $question->id,
            'type' => $question->type,
            'content' => $question->content,
            'user_id' => $question->user_id,
            'subject_id' => $question->subject_id,
            'thumbs' => $question->thumbs,
            'created_at' => $date,
            'teacher_avatar' => env('CDN_DOMAIN') . '/haolaoshi/' . $question->teacher->avatar
        ];

        $data = [
            'question' => $first_question
        ];

        $question->answers->each(static function ($item) use (&$data) {
            $date = new Carbon($item->created_at);
            $date = $date->format('Y-m-d H:i');
            $data['answers'][] = [
                'content'     => $item->content,
                'voice_reply' => $item->voice_reply,
                'photos'      => [env('CDN_DOMAIN') . '/haolaoshi/' . $item->photos],
                'type'        => $item->type,
                'teacher_id'  => $item->teacher_id,
                'question_id' => $item->question_id,
                'created_at'  => $date,
            ];
        });

        return $this->success($data);
    }

    public function thumb()
    {
        $question_id = request()->get('id', 0);

        $res = Questions::where('id', $question_id)->increment('thumbs');

        return $this->success($res);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required',
            'subject' => 'required',
        ], [
            'content' => '请用至少 10个字描述你的问题',
            'subject' => '请选择学科',
        ]);


        if($validator->fails()){
            return $this->failed($validator->messages());
        }

        $user_id = request()->user()->id;
        $photos = request()->get('photos', []);
        $subject_id = request()->get('subject', 0);
        $content = request()->get('content', '');
        $published = request()->get('published', 1);

        $photos_count = count($photos);

        switch ($photos_count) {
            case 0:
                $type = 0; // 纯文字
                break;
            case 1:
                $type = rand(1, 2); // 左图或右图
                break;
            case 2:
                $type = 3; // 两张图
                break;
            default:
                $type = 4; // 多图
                break;
        }

        $res = Questions::create([
            'user_id' => $user_id,
            'photos' => $photos,
            'subject_id' => $subject_id,
            'content' => $content,
            'published' => $published,
            'type' => $type,
        ]);

        if ($res) {
            return $this->success('提交成功');
        }
    }

    public function photo(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'avatar' => 'mimes:jpeg,bmp,png,jpg',
        ],[
            'avatar.mimes'=>'图片格式错误'
        ]);

        if($validator->fails()){
            return $this->failed($validator->messages(), 200);
        }

        $file = $request->file('img');

        if ($file->isValid()) {
            //$extension=$file->getClientOriginalExtension();
            $path = $file->getRealPath();
            $filename = 'haolaoshi/' . date('Y-m-d-h-i-s') . '-' . $file->getClientOriginalName();

            $bool = Storage::disk('oss')->put($filename, file_get_contents($path));

            if ($bool) {
                return [
                    'success' => true,
                    'msg'     => '上传成功',
                    'url'     =>  env('CDN_DOMAIN') . '/' . $filename,
                    'filename' => $filename,
                ];
            }

            return [
                'success'   => false,
                'msg'       => '上传失败,请联系管理员',
                'file_path' => '',
            ];
        }
    }

    // 学生查看自己的答疑列表
    public function orderList()
    {
        $user_id = request()->user()->id;

        $questions = Questions::with('teacher')->where('user_id', $user_id)->get();

        $data = [];
        $questions->each(static function ($item) use(&$data) {
            $date = new Carbon($item->created_at);
            $date = $date->format('Y/m/d H:i');

            $teacher_name = NULL;
            $teacher_avatar = NULL;
            if ($item->teacher) {
                $teacher_name = $item->teacher->name;
                $teacher_avatar = env('CDN_DOMAIN') . '/haolaoshi/' . $item->teacher->avatar;
            }
            $data[] = [
                'id' => $item->id,
                'teacher_name' => $teacher_name,
                'teacher_avatar' => $teacher_avatar,
                'status' => Questions::STATUS[$item->status],
                'date' => $date,
                'subject'    => Questions::SUBJECT_NAME[ $item->subject_id ],
            ];
        });

        return $this->success($data);
    }

    // 学生查看自己的答疑详情
    public function orderShow($id)
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
                ])->orderBy('id', 'ASC');
            },
            'teacher' => function ($query) {
                $query->select([
                    'id',
                    'avatar',
                    'name'
                ]);
            },
            'user' => function ($query) {
                $query->select([
                    'id',
                    'nickname',
                    'avatar'
                ]);
            }
        ])->select()->find($id);

        $date = new Carbon($question->created_at);
        $date = $date->format('Y/m/d H:i');

        $time_diff = (time() - strtotime($date));
        if ($time_diff < 3600 * 24) {
            $time_count = 3600 * 24 - $time_diff;
        } else {
            $time_count = 0;
        }

        $msgs[] = [
            'ctype' => 1,
            'face' => env('CDN_DOMAIN') . '/haolaoshi/' . $question->user->avatar,
            'msg' => $question->content,
            'name' => $question->user->nickname,
            'date' => $date,
            'id' => $question->user_id,
        ];

        foreach ($question->photos as $val) {
            $msgs[] = [
                'ctype' => 2,
                'face' => env('CDN_DOMAIN') . '/haolaoshi/' . $question->user->avatar,
                'msg' => env('CDN_DOMAIN') . '/haolaoshi/' . $val,
                'name' => $question->user->nickname,
                'date' => $date,
                'id' => $question->user_id,
            ];
        }

        $count = Answer::where([
            'question_id' => $id,
            'type' => 1,
        ])->count();
        $i = 2;

        $question->answers->each(static function ($item) use (&$msgs, $question, $count, &$i) {
            $date = new Carbon($item->created_at);
            $date = $date->format('Y/m/d H:i');
            if($item->type == 0) {
                $id = $item->teacher_id;
                $name = $question->teacher->name;
                $face = env('CDN_DOMAIN') . '/haolaoshi/' . $question->teacher->avatar;
            }else {
                $id = $question->user->id;
                $name = $question->user->nickname;
                $face = env('CDN_DOMAIN') . '/haolaoshi/' . $question->user->avatar;

                if ($i <= $count + 1) {
                    $msgs[] = [
                        'ctype' => 4,
                        'face' => $face,
                        'msg' => '第 ' . $i . ' 次提问',
                    ];
                    $i++;
                }
            }

            if ($item->photos) {
                $ctype = 2;
                $msg = env('CDN_DOMAIN') . '/haolaoshi/' . $item->photos;
            } elseif ($item->voice_reply) {
                $ctype = 3;
                $msg = $item->voice_reply;
            } else {
                $ctype = 1;
                $msg = $item->content;
            }

            $msgs[] = [
                'ctype' => $ctype,
                'face' => $face,
                'msg' => $msg,
                'name' => $name,
                'date' => $date,
                'id' => $id,
            ];
        });

        $imuserid = $question->user->id;
        $status = $question->status;

        return $this->success(compact('msgs', 'imuserid', 'status', 'count', 'time_count'));
    }

    public function studentReply()
    {
        $content = request()->get('content', '');
        $user_id = request()->user()->id;
        $question_id = request()->get('question_id', '');
        $teacher_id = Questions::find($question_id)->teacher_id;
        $ctype = request()->get('ctype', '');

        $count = Answer::where([
            'question_id' => $question_id,
            'type' => 1,
        ])->count();

        if ($count < 4) {
            if ($count == 3) {
                $res = Questions::where('id', $question_id)->where('status', 2)->update([
                    'status' => 3,
                ]);
            }
            $msg = Answer::create([
                'user_id' => $user_id,
                'type' => 1,
                'question_id' => $question_id,
                'teacher_id' => $teacher_id,
                'times' => $count + 2,
            ]);

            switch ($ctype) {
                case 1:
                    $msg->content = $content;
                    break;
                case 2:
                    $msg->photos = $content;
                    break;
                case 3:
                    $msg->voice_reply = $content;
                    break;
            }

            $msg->save();

            $date = new Carbon($msg->created_at);
            $date = $date->format('Y/m/d H:i');

            $data = [
                'ctype' => 1,
                'face' => env('CDN_DOMAIN') . '/haolaoshi/' . request()->user()->avatar,
                'msg' => $msg->content,
                'name' => request()->user()->nickname,
                'date' => $date,
                'id' => request()->user()->id,
            ];

            return $this->success($data);
        } else {
            return $this->failed('本次提问已结束');
        }
    }
}
