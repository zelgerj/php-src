--TEST--
Test detach is successful
--DESCRIPTION--
This test will detach a thread and check the return value
--FILE--
<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.inc';

class ThreadTest extends Thread {
    public function run() {
    }
}

$thread = new ThreadTest();
$thread->start();
$result = $thread->detach();
var_dump($result);
sleep(1);
?>
--EXPECT--
bool(true)
