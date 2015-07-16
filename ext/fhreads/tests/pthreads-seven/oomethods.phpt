--TEST--
Test access to user defined methods in the object context
--DESCRIPTION--
User methods are now imported from your declared class into the thread
--FILE--
<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.inc';

class ThreadTest extends Thread {
	public function objectTest(){
		return $this->value;
	}
	
	public function run(){
		$this->value = 1;
	}
}
$thread = new ThreadTest();
if($thread->start()) {
	$thread->join();
	var_dump($thread->objectTest());
}
?>
--EXPECT--
int(1)