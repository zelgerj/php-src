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
    }
    
    public function run()
    {      
        $this->testInt = 123;
        $this->testStr = 'test';
        $this->testFloat = 1.234;
        $this->testBool = true;
        $this->testArray = array('a' => 'b');
        $this->testObj = new TestObject();

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
$tMax = 10;

$ctxCount = 1;

while(1) {
for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i] = new TestThread($data);
}

for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i]->start();
}

for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i]->join();
}

for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i]->detach();
    unset($ths[$i]);
}

sleep(2);

}


var_dump($data);

echo PHP_EOL . "finished script!" . PHP_EOL;

