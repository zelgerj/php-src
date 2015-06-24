<?php 

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

class Storage {
    public $data = [];
    public function set($key, $value) {
        $this->data[$key] = $value;
    }
}

class TestThread extends Thread
{   
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function run() {
        echo __METHOD__ . ':' . $this->getThreadId() . PHP_EOL;
        $this->data->set($this->getThreadId(), 'wasHere');
    }
}

$data = new Storage();

$index = 10;
$t = [];

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