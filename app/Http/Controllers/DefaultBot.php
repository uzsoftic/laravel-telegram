<?php

namespace App\Http\Controllers;

use Uzsoftic\LaravelTelegram\Bot;
use Illuminate\Http\Request;

class DefaultBot extends Controller
{
    public Bot $bot;
    public Request $request;

    public function index(Request $request, $config = 'default'){
        $this->bot = Bot($request, $config);

        // CHECK REQUEST
        if (!$this->bot && $this->bot->hasRequest()){

        }

    }

    public function testRequest(){

    }

}
