<?php

// include classes
if (!class_exists('\Thread')) {
    require_once __DIR__ . "/../classes/Thread.php";
}

class TestThread extends \Thread
{
    public function run() {
        while(1) {
            $i = 0;
            $this->objects = array();
            while(++$i < 100) {
                $this->objects[$i] = new \stdClass();
            }
        }
    }
}

$tMax = 100;
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
