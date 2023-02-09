<?php

namespace App\Http\Controllers;

use Uzsoftic\LaravelTelegramBot\Bot;
use Illuminate\Http\Request;

class DefaultBot extends Controller
{
    public Bot $bot;
    public Request $request;

    public function index(Request $request, $config = 'default'){
        $this->bot = Bot($request, $config);

        // CHECK REQUEST AND USE SAME FUNCTION
        if (!$this->bot && $this->bot->hasRequest()){

            if ($this->bot->hasMessage()){
                $this->message();
            }elseif($this->bot->hasCallback()){
                $this->callback();
            }elseif($this->bot->hasInline()){
                $this->inline();
            }

        }
        // ...

    }

    public function message(){
        $this->bot->sendMessage(env('TELEGRAM_DEFAULT_ADMIN'), 'Hello World');
        //match ()
    }

    public function callback(){

    }

    public function inline(){

    }

    public function testRequest():void {
        //dd(request()->all());
        $this->bot = Uzsoftic\LaravelTelegramBot\Bot([], 'default');
        $this->bot->sendMessage(env('TELEGRAM_DEFAULT_ADMIN'), 'Hello World');
    }

}
