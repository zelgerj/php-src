<?php

// include classes
if (!class_exists('\Thread')) {
    require_once __DIR__ . "/../classes/Thread.php";
}

class TestThread extends Thread
{
    public function run() {
        
    }
}

$t = new TestThread();
var_dump($t);
$t->start();
var_dump($t);