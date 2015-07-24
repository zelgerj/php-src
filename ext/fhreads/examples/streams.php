<?php

if (!class_exists('\Thread')) {
    require_once __DIR__ . "/../tests/bootstrap.inc";
}

class ThreadWorker extends \Thread
{
    public function __construct($server)
    {
        $this->server = $server;
    }

    public function run()
    {
        while($this->server)
        {
            if ($client = stream_socket_accept($this->server)) {
                $callback = $this->onRequest;
                $callback($client);
            }
        }
    }
    
    public function onRequest($callback)
    {
        $this->onRequest = $callback;
    }
}

class Server extends \Thread
{
    public function __construct($workerCount)
    {
        $this->workerCount = $workerCount;
    }
    
    public function run()
    {
        $server = stream_socket_server('tcp://0.0.0.0:9001', $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
        
        for ($i = 0; $i < $this->workerCount; $i++) {
            $worker[$i] = new ThreadWorker($server);
            
            $worker[$i]->onRequest(function($client) {
                fread($client, 2048);
                $outputBuffer = fopen('php://temp', 'w+');
                
                fwrite($outputBuffer,  "200 OK HTTP/1.1\r\nConnection: close\r\n\r\n");
                rewind($outputBuffer);
                stream_copy_to_stream($outputBuffer, $client);
                
                fclose($outputBuffer);
                fclose($client);
            });
            
        }
        for ($i = 0; $i < $this->workerCount; $i++) {
            $worker[$i]->start();
        }
        for ($i = 0; $i < $this->workerCount; $i++) {
            $worker[$i]->join();
        }
    }
}

$server = new Server(8);
$server->start();
$server->join();
