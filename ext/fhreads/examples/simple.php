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
        $this->data->testObjects = array();
    }
    
    public function run() {
        $this->data->{$this->getThreadId()} = $this->getThreadId();
        $this->data->testVar = 'hahaha';
        $this->data->testObjects[] = new \stdClass();
    }
}

$data = new \stdClass();

$t = array();

$index = 10;
for ($i = 0; $i < $index; $i++) {
    $t[$i] = new TestThread($data);
}

for ($i = 0; $i < $index; $i++) {
    $t[$i]->start();
}

for ($i = 0; $i < $index; $i++) {
    $t[$i]->join();
}

var_dump($data);

echo "finished script..." . PHP_EOL . PHP_EOL;