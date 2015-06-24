<?php 

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

class Storage {
    private $mutex;
    public $data = [];
    
    public function __construct() {
        $this->mutex = fhread_mutex_init();
    }
    
    public function set($key, $value) {
        fhread_mutex_lock($this->mutex);
        $this->data[$key] = $value;
        fhread_mutex_unlock($this->mutex);
    }
}

class TestThread extends Thread
{   
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function run() {
        $this->data->set($this->getThreadId(), $this->getThreadId());
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