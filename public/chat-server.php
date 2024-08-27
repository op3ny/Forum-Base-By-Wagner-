<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use React\Socket\Server as ReactServer;
use Ratchet\Server\IoServer;
use MongoDB\Client as MongoClient;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $collection;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $client = new MongoClient('mongodb://localhost:27017');
        $this->collection = $client->selectCollection('chatdb', 'messages');
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if ($data['action'] === 'new_message') {
            $this->saveMessage($data);

            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    $client->send($msg);
                } else {
                    $client->send($msg); // Envia a mensagem para o remetente também
                }
            }
        }
    }

    protected function saveMessage($data) {
        $this->collection->insertOne([
            'username' => $data['username'],
            'message' => $data['message'],
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

$loop = React\EventLoop\Factory::create();
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080,
    '0.0.0.0', // O endereço IP para escutar; '0.0.0.0' escuta em todas as interfaces
    $loop
);

echo "Servidor WebSocket iniciado em ws://localhost:8080\n";
$server->run();
