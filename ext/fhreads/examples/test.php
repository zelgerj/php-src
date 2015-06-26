<?php

define('strict_types', 1);

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}


class TestThread extends Thread
{
    public $mutex;
    public function __construct(int $a)
    {
        $this->mutex = fhread_mutex_init();
    }
    
    public function run()
    {
        fhread_mutex_lock($this->mutex);
        $this->a += 10;
        fhread_mutex_unlock($this->mutex);
    }
}

$t = new TestThread("1");
$t->start();
$t->join();

var_dump($t);

fhread_mutex_lock($t->mutex);
echo "finished script..." . PHP_EOL;

fhread_mutex_unlock($t->mutex);
