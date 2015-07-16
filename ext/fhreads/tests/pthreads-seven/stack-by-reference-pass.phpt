--TEST--
Test stacking by reference ONLY (pass)
--DESCRIPTION--
Stacking without a reference is not allowed, so the API should enforce the requirement
--FILE--
<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.inc';

class T extends Threaded { 
	public function run() {
		printf("OK");
	}
}

class W extends Worker {
	public function run() {}
}

$t = new W();
$t->start();
$o = new T();
$t->stack($o);
$t->shutdown();
?>
--EXPECT--
OK

