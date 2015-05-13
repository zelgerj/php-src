<?php

require_once "ext/fhreads/Thread.php";

class TestThread extends Thread
{
    public function __construct($data)
    {
        $this->data = $data;
        $this->a = 'initial';
    }
    
    public function run()
    {
        $this->counter++;
        $this->data->{$this->getThreadId()} = $this->getThreadId();
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

$tMax = 2000;

for ($i = 1; $i <= $tMax; $i++) {
    $t[$i] = new TestThread($data);
}

for ($i = 1; $i <= $tMax; $i++) {
    $t[$i]->start();
}


for ($i = 1; $i <= $tMax; $i++) {
    $t[$i]->join();
}

var_dump($data);

var_dump($data->counter);

unset($data);

var_dump($data);