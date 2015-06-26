<?php 

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

class Storage {
    public $mutex;
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

class DaemonThread extends Thread
{
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function run()
    {
        while(1) {
            echo '$this->data->watchVar = ' . $this->data->watchVar . PHP_EOL;
            sleep(1);
        }
    }
}

class TestThread extends Thread
{   
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function run()
    {
        $this->data->set($this->getThreadId(), $this->getThreadId());
        $this->data->watchVar = $this->getThreadId();
    }
}

class ChangerThread extends Thread
{
    public function __construct($data)
    {
         $this->data = $data;
    }
    
    public function run()
    {
        while(1) {
            usleep(10000);
            fhread_mutex_lock($this->data->mutex);
            $this->data->watchVar = md5(microtime());
            fhread_mutex_unlock($this->data->mutex);
        }
        
    }
}

$data = new Storage();

$index = 10;
$t = [];

for ($i = 0; $i < $index; $i++) {
    $t[$i] = new TestThread($data);
}
for ($i = 0; $i < $index; $i++) {
    var_dump($t[$i]->start());
}
for ($i = 0; $i < $index; $i++) {
    $t[$i]->join();
}

var_dump($data);

/*
$c = [];
for ($i = 0; $i < $index; $i++) {
    $c[$i] = new ChangerThread($data);
    $c[$i]->start();
}

$w = new DaemonThread($data);
$w->start();
$w->join();
*/

var_dump($t);

echo "finished script..." . PHP_EOL . PHP_EOL;