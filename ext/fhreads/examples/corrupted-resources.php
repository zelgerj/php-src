<?php

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Threaded.php";
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

class Work extends Threaded {
    public function run(){

    }
}

class Test extends Thread {
    public function run(){
        $test = new Work();
        $this->test = $test;
        print_r($this->test);
        var_dump($this->test);
        $stream = fopen("/tmp/test.txt", "w+");
        var_dump($stream);
        $this->stream = $stream;
        stream_set_blocking($this->stream, 0);
        var_dump($this->stream);
    }
}

$test =new Test();
$test->start();
