<?php
namespace App\Socket;

use App\Http\Controllers\ChatController;
use App\Models\Operator;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketServer implements MessageComponentInterface
{
    protected $clients;
    private $subscriptions;
    private $users;
    private $userresources;
    private $operators;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->users = [];
        $this->userresources = [];
        $this->operators = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        // عملیات مورد نیاز هنگام باز شدن اتصال سوکت
//        error_log('client start '.json_encode($conn->resourceId));
    }

    public function onClose(ConnectionInterface $conn)
    {
        // عملیات مورد نیاز هنگام بسته شدن اتصال سوکت
        $this->clients->detach($conn);

        //remove client
        foreach ($this->users as $user){
            if ($user['client'] === $conn->resourceId){
                unset($this->users[$user['data']->uuid]);
                foreach ($this->operators as $operator){
                    $operator['conn']->send(json_encode($this->users));
                }
                //delete massage > 100 "operator and client"
                return true;
            }
        }

    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // عملیات مورد نیاز هنگام بروز خطا در اتصال سوکت
        error_log($e);

    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {

//                        id:session,
//                        name:"",
//                        user_id:localStorage.getItem('--chat--user--')??0,
//                        sender:"client",
//                        to: 0,
//                        from: session,
//                        command: 'client-connected',
//                        token:"1114",
//                        massage:""
        // عملیات مورد نیاز هنگام دریافت پیام از سوکت
        $data = json_decode($msg);
        $setUser = [
            'conn' => $conn,
            'data' => (object)[
                'uuid' => $data->id,
                'name' => $data->name??'client'.$conn->resourceId,
                'user_id' => $data->user_id??0,
                'massage' => $data->massage,
                'to' => $data->to,
                'sender' => $data->sender,
                'ip' => $_SERVER['REMOTE_ADDR'],
            ],
            'token' => $data->token,
            'client' => $conn->resourceId,
            'time' => time()
        ];
        if (isset($data->command)) {
            switch ($data->command) {
                case 'check-online':
                    $conn->send(json_encode((object)[
                        'command' => 'online',
                        'from' => $data->id,
                        'online' => isset($this->users[$data->id])
                    ]));
                    break;
                case "client-connected":
                    $this->users[$data->id] = $setUser;

                    error_log('user Connected on id: '. $data->id);
                    break;

                case "operator-connected":
                    $this->operators[$data->id] = $setUser;
                    error_log('operator Connected on id: '. $data->id);
                    break;

                case "clients-list":
                    $chat_list = $this->chatList($data);
                    $items = [];
                    foreach ($chat_list as $item){
                        $item->online = false;
                        if (isset($this->users[$item->from])){
//                            error_log($this->users[$item->from]);

                            $item->online = true;
                        }
                        $items[] = $item;
                    }
                    $conn->send(json_encode($items));
                    //error_log($chat_list);
                    break;
                case "massage-send":
                    //send msg to client or operator
                    if ($data->sender === 'client'){
                        //send to all operators ->token and ->group_operators

                        $chat_list = $this->chatList($data);
                        $items = [];
                        foreach ($chat_list as $item){
                            $item->online = false;
                            if (isset($this->users[$item->from])){
                                $item->online = true;
                            }
                            $items[] = $item;
                        }
                        foreach ($this->operators as $operator){

                            $operator['conn']->send(json_encode($items));
                            $operator['conn']->send(json_encode($data));
                            //error_log($chat_list);
                        }
                    }else {
                        //send to client
                        //if client online
                        if (isset($this->users[$data->to])){
                            $this->users[$data->to]['conn']->send(json_encode($data));
                        }
                    }
                    //add to database
                    ChatController::insert($data);
                break;

                case 'show-detail':
                    $conn->send(json_encode($this->chatDetail($data)));
                    break;

                default:
                    $example = array(
                        'methods' => [
                            "subscribe" => '{command: "subscribe", channel: "global"}',
                            "groupchat" => '{command: "groupchat", message: "hello glob", channel: "global"}',
                            "message" => '{command: "message", to: "1", message: "it needs xss protection"}',
                            "register" => '{command: "register", userId: 9}',
                        ],
                    );
                    $conn->send(json_encode($example));
                    break;
            }
        }
        //$this->operator->send($msg);
    }

    public function chatList($op) {
        //get operator id as id
        //error_log($op->page);
        $data = (object)['operator' => $op->op, 'token' => $op->token, 'page'=> 1];
        return ChatController::chatList($data);
    }

    public function chatDetail($data) {
        return ChatController::chatDetal((object)[
            'page' => $data->page??1,
            'user_id' => $data->user_id,
            'token' => $data->token,
            'sender' => $data->sender
        ]);
    }

    public function notification(){}
}


