<?php

namespace App\Http\Controllers\Api;

use App\Answer;
use App\Questions;
use App\User;
use Carbon\Carbon;
use EasyWeChat\Factory;
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
        $role = request()->get('role', 0);

        $condition = [];
        if ($subject_id != 0) {
            $condition = [
                'subject_id' => $subject_id,
            ];
        }

        if ($role == 1) { // 学生
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
                        'grade'      => Questions::GRADE[$item->grade],
                        'price'      => $item->total_price
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
                    'grade'      => Questions::GRADE[$item->grade],
                    'price'      => $item->total_price
                ];
            });

            $data = collect($data)->sortByDesc('created_at')->values();

            return $this->success($data);
        } elseif ($role == 0) { // 老师
            $questions = Questions::doesntHave('answers')->where($condition)->orderBy('id', 'DESC')->get();
            $data = [];
            $questions->each(static function ($item) use (&$data){
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
                    'grade'      => Questions::GRADE[$item->grade],
                    'price'      => $item->total_price
                ];
            });

            $data = collect($data)->sortByDesc('created_at')->values();

            return $this->success($data);
        }
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
                    'ctype',
                    'question_id',
                    'created_at',
                    'user_id'
                ])->orderBy('created_at', 'ASC');
            },
            //'teacher' => function ($query) {
            //    $query->select([
            //        'id',
            //        'avatar'
            //    ]);
            //}
        ])->select()->find($id);

        $pics = [];
        foreach ($question->photos as $val) {
            $pics[] = env('CDN_DOMAIN') . '/haolaoshi/' . $val;
        }

        $date = new Carbon($question->created_at);
        $date = $date->format('Y/m/d H:i');

        $teacher_id = $question->teacher_id;
        $user = User::where('teacher_id', $teacher_id)->first();

        $first_question = [
            'photos' => $pics,
            'id' => $question->id,
            'type' => $question->type,
            'content' => $question->content,
            'user_id' => $question->user_id,
            'subject_id' => $question->subject_id,
            'thumbs' => $question->thumbs,
            'created_at' => $date,
            'teacher_avatar' => $user->avatar,
        ];

        $data = [
            'question' => $first_question
        ];
        $times = 1;
        $question->answers->each(static function ($item) use (&$data, &$times) {
            $date = new Carbon($item->created_at);
            $date = $date->format('Y-m-d H:i');
            $content = '';
            switch ($item->ctype) {
                case 1:
                    $content = $item->content;
                    break;
                case 2:
                    $content = env('CDN_DOMAIN') . '/haolaoshi/' . $item->content;
                    break;
                case 3:
                    $content = env('CDN_DOMAIN') . '/haolaoshi/voice' . $item->content;
                    break;
            }
            if ($item->type == 1) {
                $times++;
            }
            $data['answers'][] = [
                'content'     => $content,
                'type'        => $item->type,
                'user_id'  => $item->user_id,
                'question_id' => $item->question_id,
                'created_at'  => $date,
                'ctype' => $item->ctype,
                'times' => $times,
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

    // 创建一条问题
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

        $user = User::find($user_id);

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
            'grade' => $user->grade,
            'total_price' => 200,
            'wait_paid_fee' => 200
        ]);

        if ($res) {
            $wxpay = new WechatController();
            $result = $wxpay->unifiedOrder($res->id, 1, $user->openid);

            if ($result['result_code'] == 'SUCCESS') {
                $timeStamp = '"'.time().'"';
                $pay_sign = strtoupper(MD5('appId='.config('wechat.payment.default.app_id').'&nonceStr='. $result['nonce_str'].'&package=prepay_id='. $result['prepay_id'].'&signType=MD5&timeStamp='.$timeStamp.'&key='. config('wechat.payment.default.key')));

                return $this->success(compact('result', 'timeStamp', 'pay_sign'));
            } else {
                return $this->failed('支付失败');
            }
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

    // 学生/老师查看自己的答疑列表
    public function orderList()
    {
        $role = request()->get('role', 1); // 1: 学生 | 0: 老师
        $user_id = request()->user()->id;

        if($role) { // 学生
            $questions = Questions::with('teacher')->where('user_id', $user_id)->get();
            $data = [];
            $questions->each(static function ($item) use(&$data) {
                $date = new Carbon($item->created_at);
                $date = $date->format('Y/m/d H:i');

                if ($item->teacher_id) {
                    $teacher_name = $item->teacher->name;
                    $teacher_photo = env('CDN_DOMAIN') . '/' . $item->teacher->photo;

                    $data[] = [
                        'id' => $item->id,
                        'name' => $teacher_name,
                        'photo' => $teacher_photo,
                        'status' => Questions::STATUS[$item->status],
                        'date' => $date,
                        'subject'    => Questions::SUBJECT_NAME[ $item->subject_id ],
                    ];
                } else {
                    $data[] = [
                        'id' => $item->id,
                        'status' => Questions::STATUS[$item->status],
                        'date' => $date,
                        'subject'    => Questions::SUBJECT_NAME[ $item->subject_id ],
                    ];
                }
            });
        } else { // 老师
            $questions = Questions::with('user')->where('teacher_id', $user_id)->get();
            $data = [];
            $questions->each(static function ($item) use(&$data) {
                $date = new Carbon($item->created_at);
                $date = $date->format('Y/m/d H:i');

                $student_name = $item->user->nickname;
                $student_avatar = env('CDN_DOMAIN') . '/haolaoshi/' . $item->user->avatar;
                $data[] = [
                    'id' => $item->id,
                    'name' => $student_name,
                    'avatar' => $student_avatar,
                    'status' => Questions::STATUS[$item->status],
                    'date' => $date,
                    'subject'    => Questions::SUBJECT_NAME[ $item->subject_id ],
                ];
            });
        }

        return $this->success($data);
    }

    // 学生/老师查看自己的答疑详情
    public function orderShow($id)
    {
        $question = Questions::with([
            'answers' => function ($query) {
                $query->select([
                    'content',
                    'type',
                    'question_id',
                    'user_id',
                    'created_at',
                    'ctype'
                ])->orderBy('id', 'ASC');
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
            $user_id = $item->user_id;
            $name = $item->user->nickname;
            $face = env('CDN_DOMAIN') . '/haolaoshi/' . $question->user->avatar;
            if($item->type == 1) {
                if ($i <= $count + 1) {
                    $msgs[] = [
                        'ctype' => 4,
                        'face' => $face,
                        'msg' => '第 ' . $i . ' 次提问',
                    ];
                    $i++;
                }
            }

            if ($item->ctype == 2) {
                $msg = env('CDN_DOMAIN') . '/haolaoshi/' . $item->content;
            } elseif ($item->ctype == 3) {
                $msg = $item->content;
            } else {
                $msg = $item->content;
            }

            $msgs[] = [
                'ctype' => $item->ctype,
                'face' => $face,
                'msg' => $msg,
                'name' => $name,
                'date' => $date,
                'id' => $user_id,
            ];
        });

        $imuserid = request()->user()->id;
        $status = $question->status;

        return $this->success(compact('msgs', 'imuserid', 'status', 'count', 'time_count'));
    }

    public function studentReply()
    {
        $content = request()->get('content', '');
        $user_id = request()->user()->id;
        $question_id = request()->get('question_id', '');
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

    public function teacherReply()
    {
        $content = request()->get('content', '');
        $user_id = request()->user()->id;
        $question_id = request()->get('question_id', '');
        $ctype = request()->get('ctype', '');

        $msg = Answer::create([
            'user_id' => $user_id,
            'type' => 0,
            'question_id' => $question_id,
            'content' => $content,
            'ctype' => $ctype
        ]);

        $date = new Carbon($msg->created_at);
        $date = $date->format('Y/m/d H:i');

        $data = [
            'ctype' => $ctype,
            'face' => env('CDN_DOMAIN') . '/haolaoshi/' . request()->user()->avatar,
            'msg' => $msg->content,
            'name' => request()->user()->nickname,
            'date' => $date,
            'id' => request()->user()->id,
        ];

        return $this->success($data);
    }

    public function teacherConfirmToAnswer()
    {
        $question_id = request()->get('question_id', '');
        $question = Questions::find($question_id);
        $question->teacher_id = request()->user()->id;
        $question->save();
        //$question = Questions::where('id', $question_id)->update([
        //    'teacher_id' => request()->user()->id
        //]);

        $student_id = $question->user_id;

        $student = User::find($student_id);

        if ($question) {
            $data = [
                'template_id' => 'qJOnRTHZeNDnwq8v6pHrusuz6TA7TTcWZNJTV5ay4dY', // 所需下发的订阅模板id
                'touser' => $student->openid,     // 接收者（用户）的 openid
                'page' => '',       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
                'data' => [         // 模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
                    'name2' => [
                        'value' => request()->user()->name, // 回复者
                    ],
                    'time3' => [
                        'value' => date('Y-m-d', time()) , // 回复时间
                    ],
                    'thing4' => [
                        'value' => '提问已匹配到老师, 正在整理打字中, 预计30分钟内回复.', // 回复内容
                    ],
                    'thing5' => [
                        'value' => '如有疑问' // 温馨提示
                    ],
                    'date6' => [
                        'value' => date('Y-m-d', strtotime($question->created_at))  // 提问时间
                    ]
                ],
            ];

            $config = config('wechat.mini_program.default');

            $app = Factory::miniProgram($config);

            $app->subscribe_message->send($data);

            return $this->success('接单成功');
        }
    }
}
