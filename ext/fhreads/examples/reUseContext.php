<?php

// include classes
if (!class_exists('\Thread')) {
    require_once __DIR__ . "/../classes/Thread.php";
}

class TestThread extends \Thread
{
    public function run() {
        echo "tsrmls: " . fhread_tsrm_get_ls_cache() . PHP_EOL;
        sleep(1);
    }
}

$a = new TestThread();
$a->start();
$a->join();

$a->start();

