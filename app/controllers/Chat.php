<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $rooms;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->rooms = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
    $data = json_decode($msg, true);
    // print_r($data);

    if (isset($data['room'])) {
        // This is a room message, handle it as before
        $room = $data['room'];

        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = new \SplObjectStorage();
        }

        $this->rooms[$room]->attach($from);
        // print_r($this->rooms[$room]);
        $numRecv = count($this->rooms[$room]) - 1;

        // Send the message to all clients in the room
        $this->sendToRoom($room, $data['content'] , $data['user_name'], $from);

        echo "Online in this room: " . ($numRecv + 1) . "\n";
    } elseif (isset($data['recipient'])) {
        // This is a private message
        $recipientId = $data['recipient'];
        $messageContent = $data['content'];

        // Find the recipient in the connected clients
        foreach ($this->clients as $client) {
            if ($client->resourceId == $recipientId) {
                // Send the private message to the recipient
                $client->send(json_encode(['content' => $messageContent, 'room' => 'private']));
                echo "Private message sent to user {$recipientId}\n";
                break;
            }
        }
    }
}


    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";

        // Remove the client from all rooms
        foreach ($this->rooms as $room => $clients) {
            $this->rooms[$room]->detach($conn);

            // If there are no more clients in the room, remove the room
            if ($this->rooms[$room]->count() === 0) {
                unset($this->rooms[$room]);
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function sendToRoom($room, $message,$user_name , $exclude)
    {
        $encodedMessage = json_encode(['content' => $message, 'room' => $room , 'user_name'=>$user_name]);

        foreach ($this->rooms[$room] as $client) {
            if ($client !== $exclude) {
                $client->send($encodedMessage);
            }
        }
    }

    protected function joinRoom(ConnectionInterface $conn, $room)
    {
        $joinMessage = json_encode([
            'content' => "Joined room: $room",
            'room' => $room
        ]);

        $this->sendToRoom($room, $joinMessage, $conn);
    }

    protected function leaveRoom(ConnectionInterface $conn, $room)
    {
        $leaveMessage = json_encode([
            'content' => "Left room: $room",
            'room' => $room
        ]);

        $this->sendToRoom($room, $leaveMessage, $conn);
    }
}
