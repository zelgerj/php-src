--TEST--
Test stateful fatalities
--DESCRIPTION--
This test verifies that state includes fatalities
--FILE--
<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.inc';

class TestThread extends Thread {
	public function run(){
		@i_do_not_exist();
	}
}
$test = new TestThread();
$test->start();
$test->join();
var_dump($test->isTerminated());
?>
--EXPECTF--
bool(true)
