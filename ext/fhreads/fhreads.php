<?php

require_once "ext/fhreads/Thread.php";

class TestObject
{
    public function __construct() {
        // echo __METHOD__ . PHP_EOL;
    }
    
    public function __destruct() {
        // echo __METHOD__ . PHP_EOL;
    }
}

class TestThread extends Thread
{
    public $test = 'test';
    
    public function __construct()
    {
        /*
        $this->array = &$array;
        $this->data = $data;
        $this->a = 'initial';
        */
    }
    
    public function run()
    {
        // $v = 'asdf';
        $this->inside = 'inside';
        
        /*
        //usleep(rand(100,200));
        
        $this->testInt = 123;
        $this->testStr = 'test';
        $this->testFloat = 1.234;
        $this->testBool = true;
        $this->testArray = array('a' => 'b');
        $this->testObj = new TestObject();
        
        $this->a->value = 'test';
        $this->a->{$this->getThreadId()} = $this->getThreadId();
        */
        /*
        $this->counter++;
        usleep(100);
        $this->data->{$this->getThreadId()} = $this->getThreadId();
        $this->data->array[] = $this->getThreadId();
        $this->data->counter++;
        */
        echo __METHOD__ . PHP_EOL;
    }
}

/*
$t = new TestThread();
$t->outside = 'outside';
$t->start();
$t->join();
*/

$ths = array();
$tMax = 100;

for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i] = new TestThread();
}

for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i]->start();
}

for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i]->join();
}

$t = $ths[1];
echo 'Try to get $t->inside' . PHP_EOL;
$a = $t->inside;
var_dump($a);

echo PHP_EOL . "finished script!" . PHP_EOL;

