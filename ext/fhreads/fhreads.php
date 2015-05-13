<?php

require_once "ext/fhreads/Thread.php";

class TestThread extends Thread
{
    public function __construct($data, &$array)
    {
        $this->array = &$array;
        $this->data = $data;
        $this->a = 'initial';
    }
    
    public function run()
    {
        $this->counter++;
        $this->data->{$this->getThreadId()} = $this->getThreadId();
        $this->data->array[] = $this->getThreadId();
        $this->data->counter++;
        echo __METHOD__ . PHP_EOL;
    }
    
    public function __destruct()
    {
        echo __METHOD__ . PHP_EOL;
    }
}

$data = new \stdClass();
$data->counter = 0;
$data->resource = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);
$data->array = array('a' => 'a');

$array = array('a' => 'a');

$tMax = 10;

for ($i = 1; $i <= $tMax; $i++) {
    $t[$i] = new TestThread($data, $array);
}

for ($i = 1; $i <= $tMax; $i++) {
    $t[$i]->start();
}


for ($i = 1; $i <= $tMax; $i++) {
    $t[$i]->join();
    $t[$i]->detach();
    unset($t[$i]);
}

var_dump($data);

unset($data);

var_dump($data);