<?php
namespace ViewerNodeNamespace;

use Exception;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\Connector;

class ViewerNodeSocket
{

    const SERVER_NODE_ADDR = "localhost:3345";

    public $browserConnection;
    public $nodeServerConnection;

    function __construct() {
        $loop = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new class($this) implements MessageComponentInterface {

                        private $vnSocket;

                        public function __construct(ViewerNodeSocket $vnSocket) {
                            $this->vnSocket = $vnSocket;
                        }

                        function onOpen(ConnectionInterface $conn) {
                            $this->vnSocket->browserConnection = $conn;
                        }

                        function onMessage(ConnectionInterface $from,$msg) {
                            $this->vnSocket->sendMessageToNodeServer($this->vnSocket->nodeServerConnection,$msg);
                        }

                        function onClose(ConnectionInterface $conn) {
                        }

                        function onError(ConnectionInterface $conn,Exception $e) {
                        }
                    }
                )
            ),
            8080
        )->loop;

        $connection = new Connector($loop);
        $connection->connect(self::SERVER_NODE_ADDR)->then(
            function (\React\Socket\ConnectionInterface $connection) {
                $this->nodeServerConnection = $connection;

                $connection->write("{\"header\":\"TEST\"}\r\n\r\n");

                $connection->on("data", function($pocket) {
                    $this->sendMessageToBrowser($this->browserConnection,trim($pocket));
                });

                $connection->on('end', function() {
                });

                $connection->on('error', function(Exception $e) {
                });

                $connection->on('close', function() {
                });
            },
            function(Exception $error) {
                // failed to connect due to $error
            }
        );

        $loop->run();
    }

    function sendMessageToNodeServer(\React\Socket\ConnectionInterface $conn,$msg) {
        $conn->write("{$msg}\r\n\r\n");
    }

    function sendMessageToBrowser(ConnectionInterface $conn,$msg) {
        $conn->send($msg);
    }


}