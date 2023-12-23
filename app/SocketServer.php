<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// require 'vendor/autoload.php';
// require './app/controllers/Chat.php';


Class SocketServer{

    public function __construct(){
        echo "running on";
    }

     public function run()
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new Chat()
                )
            ),
            8080
        );

        echo "WebSocket server started at 0.0.0.0:8080\n";

        $server->run();
    }
}