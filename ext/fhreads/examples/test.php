<?php

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}


class TestThread extends Thread
{
    public function run()
    {
        sleep(2);
    }
}

$t = new TestThread("1");
$t->start();

var_dump($t);

echo "finished script..." . PHP_EOL;
