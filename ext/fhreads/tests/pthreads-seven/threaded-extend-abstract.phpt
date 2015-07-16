--TEST--
Test extending abstract
--DESCRIPTION--
This is regression test for #409
--FILE--
<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.inc';

var_dump(Threaded::extend(ReflectionFunctionAbstract::class));
?>
--EXPECT--
bool(true)
