<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();

            $table->string('name')->default('')->comment('老师名字');
            $table->string('avatar')->default('')->comment('老师头像');
            $table->string('subject')->default('')->comment('擅长科目');
            $table->string('certificate')->default('')->comment('资格证书');
            $table->string('certificate_no')->default('')->comment('资格证书编号');
            $table->string('grade')->default('')->comment('年级');
            $table->string('special')->default('')->comment('教学特点');
            $table->string('result')->default('')->comment('教学成果');
            $table->string('title')->default('')->comment('职称');
            $table->string('graduated')->default('')->comment('毕业于');
            $table->string('edu_background')->default('')->comment('最高学历');
            $table->integer('edu_ages')->default(0)->comment('教龄');
            $table->string('labels')->default(0)->comment('标签');

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
        Schema::dropIfExists('teachers');
    }
}
