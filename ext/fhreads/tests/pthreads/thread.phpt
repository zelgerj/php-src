--TEST--
Test basic threading
--DESCRIPTION--
This test will create and join a simple thread
--FILE--
<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.inc';

class ThreadTest extends Thread {
	public function run(){
		/* nothing to do */
	}
}
$thread = new ThreadTest();
if($thread->start())
	var_dump($thread->join());
?>
--EXPECT--
bool(true)