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
    
    public function __construct($data)
    {
        $this->data = $data;
        $this->testObjArray = array();
    }
    
    public function run()
    {      
        $this->testInt = 123;
        usleep(rand(1000,20000));
        $this->testStr = 'test';
        usleep(rand(1000,20000));
        $this->testFloat = 1.234;
        usleep(rand(1000,20000));
        $this->testBool = true;
        usleep(rand(1000,20000));
        $this->testArray = array('a' => 'b');
        usleep(rand(1000,20000));
        $this->testObj = new TestObject();
        $this->testObjArray[] = new TestObject();
        $this->testObjArray[0]->haha = 'haha';
        usleep(rand(1000,20000));
        //$this->data->{$this->getThreadId()} = $this->getThreadId();
    }
}

/*
for ($i = 1; $i < 200; $i++) {
    var_dump(fhread_tsrm_new_interpreter_context());
}


sleep(3);

exit(0);

*/
/*
$t = new TestThread();
$t->outside = 'outside';
$t->start();
$t->join();
*/

$data = new \stdClass();

$ths = array();
$tMax = 1000;

$ctxCount = 1;


for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i] = new TestThread($data);
}

while(1) {

    
    for ($i = 1; $i <= $tMax; $i++) {
        $ths[$i]->start();
    }
    
    for ($i = 1; $i <= $tMax; $i++) {
        $ths[$i]->join();
    }
    
    echo "$tMax threads finished, restarting..." . PHP_EOL;
    var_dump($ths[$tMax]);

sleep(1);

}


echo PHP_EOL . "finished script!" . PHP_EOL;

