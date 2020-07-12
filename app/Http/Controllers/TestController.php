<?php

namespace App\Http\Controllers;

use App\Questions;
use Illuminate\Http\Request;

class TestController extends Controller
{

    public function test()
    {
        if (env('APP_DEBUG')) {


            $question = Questions::with('answers')->find(1);
            dd($question);







        }


    }
}
