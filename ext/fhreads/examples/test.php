<?php

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}



class TestThread extends \Thread
{

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function run()
    {
        $this->data->initial = 2;
        $this->data->thread = $this->getThreadId();
        $this->data->threadInstance = $this;


        var_dump($this->data->resource);

        $this->data->closure->__invoke();
    }
}


$data = new \stdClass();
$data->initial = 1;
$data->resource = simplexml_load_string('<xml></xml>');
$data->closure = function() {
    echo 'hallo' . PHP_EOL;
};

$t = new TestThread($data);
$t->start();
$t->join();
