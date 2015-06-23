<?php 

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

class TestThread extends Thread
{   
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function run() {
        $this->data->{$this->getThreadId()} = $this;
        $this->data->testVar = 'hahaha';
        $this->data->testObject = new \stdClass();
    }
}

$data = new \stdClass();

$r = new TestThread($data);
$r->start();
$r->join();


var_dump($data);

echo "finished script..." . PHP_EOL . PHP_EOL;