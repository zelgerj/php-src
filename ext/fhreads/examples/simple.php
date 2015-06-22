<?php 

class TestRunnable
{
    public function run() {
        echo 'hallo' . PHP_EOL;
    }
}

$runnable = new TestRunnable();
$status = fhread_create($runnable, $threadId);

var_dump($status);
var_dump($threadId);


echo "finished script..." . PHP_EOL . PHP_EOL;