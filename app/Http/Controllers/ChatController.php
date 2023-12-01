<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;


class ChatController extends Controller
{
    //

    public static function insert($data) {
        //find operator as if sender "client" to "id"
        $operator = 0;
        if ($data->sender === 'client'){
            if ($data->to){ $operator = $data->to; }
        }else {
            $oprt = Operator::where('user_id', $data->from)->first();
            $operator = $oprt->id;
        }
        return Chat::create([
            'massage' => $data->massage,
            'token' => $data->token,
            'from' => $data->from,
            'to' => $data->to,
            'operator' => $operator,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'sender' => $data->sender//client, operator
        ]);
    }

    public static function chatList($data) {
        //select chat `where token & (operator or 0)` `group user`  `order id DESC` paginate limit 15
        $currentPage =1;
        if (isset($data->page)){
            $currentPage = $data->page;
        }
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });
       $result = Chat::whereIn('operator', [0,(int)$data->operator])
                ->where('token', $data->token)
                ->where('sender', 'client')
                ->orderBy('id','DESC')
                ->groupBy('from')->paginate(50);

        $res = [];
        foreach ($result as $item) {
            $chat = Chat::where('token', $data->token)
                ->where(function($q) use ($item) {
                    $q->where('from', $item->from)
                        ->orWhere('to', $item->from);
                })
                ->orderBy('id','desc')->first();
            $item->count = Chat::select('id')->where('from', $item->from)->where('status', false)->get()->count();
            $item->massage = $chat->massage;
            $item->created_at = $chat->created_at;
            $res[] = $item;
        }
       return $res;
    }

    public static function chatDetal($data){
        self::status((object)['token' => $data->token, 'to' => $data->user_id, 'sender'=>$data->sender]);
        $currentPage =1;
        if (isset($data->page)){
            $currentPage = $data->page;
        }
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });
        $result = Chat::where('token', $data->token)
            ->where(function($q) use ($data) {
                $q->where('from', $data->user_id)
                    ->orWhere('to', $data->user_id);
            })
//            ->where('from', '449-230-265-545')
//            ->whereIn('to', ['449-230-265-545'])
            ->orderBy('id', 'DESC')
            ->paginate(100);
        return collect($result);
    }
    public static function status($data){
        if ($data->sender === 'operator'){
           return Chat::where('from', $data->to)
                ->where('token', $data->token)
                ->where('status',false)
                ->update([
                'status' => true
            ]);
        }else {
           return Chat::where('operator', $data->to)
                ->where('token', $data->token)
                ->where('status',false)
                ->update([
                'status' => true
            ]);
        }

    }

    public function chat(Request $request) {
        //select chat `where token & (operator or 0)` `group user`  `order id DESC` paginate limit 15
        $currentPage =1;
        if (isset($request->page)){
            $currentPage = $request->page;
        }
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });
        $result = Chat::whereIn('operator', [0,1])
            ->where('token', '1114')
            ->where('sender', 'client')
            ->orderBy('id','DESC')
            ->groupBy('from')->paginate(5);
        foreach ($result as $item) {
            $chat = Chat::select('massage')->where('from', $item->from)->orderBy('id','desc')->first();
            $item->count = Chat::select('id')->where('from', $item->from)->where('status', false)->get()->count();
            $item->massage = $chat->massage;
            $res[] = $item;
        }
        return $res;
    }

    public function detail(Request $request) {
        //select chat `where token & (operator or 0)` `group user`  `order id DESC` paginate limit 15
        $currentPage =1;
        if (isset($request->page)){
            $currentPage = $request->page;
        }
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });
        $result = Chat::where('token', '1114')
            ->where(function($q) use ($request) {
                $q->where('from', $request->f)
                    ->orWhere('to', $request->f);
            })
//            ->where('from', '449-230-265-545')
//            ->whereIn('to', ['449-230-265-545'])
            ->orderBy('id','DESC')
            ->paginate(50);
        return collect($result);
    }
}


