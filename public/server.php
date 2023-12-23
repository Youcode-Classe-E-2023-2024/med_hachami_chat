<?php

require_once '../app/SocketServer.php';
require_once '../vendor/autoload.php';
require_once '../app/controllers/Chat.php';
 $server = new SocketServer();
 $server->run();
 echo "running server";