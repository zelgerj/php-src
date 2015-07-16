--TEST--
Test mutex operations
--DESCRIPTION--
This test will ensures mutex functionality
--FILE--
<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.inc';

$mutex = Mutex::create();
var_dump(Mutex::lock($mutex));
var_dump(Mutex::unlock($mutex));
var_dump(Mutex::destroy($mutex));
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
