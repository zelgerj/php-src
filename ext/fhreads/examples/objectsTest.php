<?php

if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

class TestThread extends \Thread
{
    public function run() {
        while(1) {
            $i = 0;
            while(++$i < 100) {
                $this->objects[$i] = new \stdClass();
            }
        }
    }
}

$tMax = 10;
$t = array();

for ($i = 0; $i < $tMax; $i++) {
    $t[$i] = new TestThread();
}

for ($i = 0; $i < $tMax; $i++) {
    $t[$i]->start();
}

for ($i = 0; $i < $tMax; $i++) {
    $t[$i]->join();
}


echo "finished script...\n";
