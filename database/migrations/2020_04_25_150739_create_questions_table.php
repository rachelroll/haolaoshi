<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->text('content')->comment('问题内容');
            $table->string('photos')->default('')->comment('提问附图');
            $table->integer('user_id')->default(0)->comment('学生ID');
            $table->integer('teacher_id')->default(0)->comment('老师ID');
            $table->integer('subject_id')->default(0)->comment('科目ID');
            $table->integer('parent_id')->default(0)->comment('首次提问ID');
            $table->integer('thumbs')->default(0)->comment('点赞数');
            $table->tinyInteger('published')->default(0)->comment('是否公开问题: 0: 公开 | 1: 不公开');
            $table->integer('total_price')->default(0)->comment('总金额');
            $table->integer('paid_fee')->default(0)->comment('实付金额');
            $table->integer('wait_paid_fee')->default(0)->comment('待支付金额');
            $table->tinyInteger('status')->default(0)->comment('支付状态');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('questions');
    }
}
