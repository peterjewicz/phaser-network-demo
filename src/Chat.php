<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    public $players;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->players = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";

        $response = [];
        $response[0] = 'player';

        // here we could initiate a player object but we'll just
        // set this manually to speed things up for now
        $player = [];
        $maxX=400;
        $maxY=300;
        $player['xPos'] = rand(1,$maxX);
        $player['yPos'] = rand(1,$maxY);
        $player['id'] = [$conn->resourceId];

        $this->players[$conn->resourceId] = $player;

        $response[1] = $this->players;
        $response = json_encode($response);


        foreach ($this->clients as $client) {
          $client->send($response);
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $newMsg = json_decode($msg);
        if($newMsg[0] === 'player22222'){
          foreach ($this->clients as $client) {
            $response = [];
            $response[0] = 'player';
            $response[1] = 'New user has joined';
            $response = json_encode($response);
            $client->send($response);
          }
        } else{
          $numRecv = count($this->clients) - 1;
          echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
              , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

          $this->players[$from->resourceId]['xPos'] = $this->players[$from->resourceId]['xPos'] + $newMsg[0];
          $this->players[$from->resourceId]['yPos'] = $this->players[$from->resourceId]['yPos'] + $newMsg[1];
          foreach ($this->clients as $client) {
                  $response = [];
                  $response[0] = 'playerMovement';
                  $response[1] = $this->players;
                  // we do want to send back to the receiver as the server is the final word on game state
                  $client->send(json_encode($response));
          }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
