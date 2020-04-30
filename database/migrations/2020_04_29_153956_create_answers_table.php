<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();

            $table->text('content')->comment('回复内容');
            $table->string('voice_reply')->default('')->comment('语音回复');
            $table->string('photos')->default('')->comment('回复附图');
            $table->integer('teacher_id')->default(0)->comment('老师ID');
            $table->integer('question_id')->default(0)->comment('提问ID');

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
        Schema::dropIfExists('answers');
    }
}
