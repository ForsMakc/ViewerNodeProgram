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

    public $nodeId;

    public $request;
    protected $response;

    public $browserConnection;
    public $nodeServerConnection;


    function __construct($port) {
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
//                            print("Сообщение отправляется: $msg\n");
                            $this->vnSocket->request = $msg;
                            $this->vnSocket->sendMessageToNodeServer($this->vnSocket->nodeServerConnection,$this->vnSocket->request);
                        }

                        function onClose(ConnectionInterface $conn) {
                            exit();
                        }

                        function onError(ConnectionInterface $conn,Exception $e) {
                            exit();
                        }

                    }
                )
            ),
            $port
        )->loop;

        $connection = new Connector($loop);
        $connection->connect(self::SERVER_NODE_ADDR)->then(
            function (\React\Socket\ConnectionInterface $connection) use ($loop) {
                $this->nodeServerConnection = $connection;
                $pocketData = new PocketData(PocketData::CONNECT);
                $this->sendMessageToNodeServer($this->nodeServerConnection,$pocketData->getPocket());

                $connection->on("data", function($msg) use ($connection) {
                    if ($pocketData = $this->cumulateMessage($msg)) {
                        switch ($pocketData->getHeader()) {
                            case PocketData::OK: {
                                $this->nodeId = $pocketData->getNodeId();
                                if ($this->browserConnection) {
                                    $this->sendMessageToBrowser($this->browserConnection,$pocketData->getPocket());
                                } else {
//                                    print("Не удалсь отправить данные на WebSocket!");
                                    $pocketData = new PocketData(PocketData::TEST);
                                    $this->sendMessageToNodeServer($this->nodeServerConnection,$pocketData->getPocket());
                                }
                                break;
                            }
                            case PocketData::DATA: {
//                                print("Пришло сообщение даных");
                                if ($this->browserConnection) {
                                    $this->sendMessageToBrowser($this->browserConnection,$pocketData->getPocket());
                                }
                                break;
                            }
                            default: {
                                if ($this->browserConnection) {
                                    $this->sendMessageToBrowser($this->browserConnection,$pocketData->getPocket());
                                }
                                break;
                            }
                        }
                    }
                });

                $connection->on('end', function() {
                    exit();
                });

                $connection->on('error', function(Exception $e) {
                    exit();
                });

                $connection->on('close', function() {
                    exit();
                });
            },
            function(Exception $error) {
//                print("Не удалось подключиться к серверу:\n$error");
                exit();
            }
        );

        $loop->run();
    }

    function cumulateMessage($msg) {
        $this->response .= $msg;
        if (strpos($this->response,PHP_EOL . PHP_EOL) !== false) {
//            print("Пришли данные: $this->response\n");
//            print(strlen($this->response) . " - длина сообщения\n");
            $pocketData = (new PocketData())->setPocket($this->response);
            $this->response = "";
            return $pocketData;
        }
        return null;
    }

    function sendMessageToNodeServer(\React\Socket\ConnectionInterface $conn, $msg) {
        $conn->write($msg);
    }

    function sendMessageToBrowser(ConnectionInterface $conn, $msg) {
        $conn->send($msg);
    }

}