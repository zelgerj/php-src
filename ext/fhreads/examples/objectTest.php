<?php


error_reporting(E_ALL);

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

class DummyThread extends Thread
{
    public function run()
    {
        $this->synchronized(function($self) {
            echo 'In Synchronized Thread Context.' . PHP_EOL;
            $self->a = new stdClass();
            sleep(1);
        });
        usleep(100);
        $this->synchronized(function($self) {
            echo "finished Thread run\n";
        });
    }
}


$objs1 = new stdClass();
$objs1 = new stdClass();
$objs1 = new stdClass();

$t = new DummyThread();

$t->start();



usleep(1000);
$t->synchronized(function($self) use($objs1) {
    echo 'In Synchronized Global Context.' . PHP_EOL;
    $self->a = $objs1;
    sleep(1);
});

var_dump($objs1);

$t->join();

var_dump($t);

echo "finished script\n";