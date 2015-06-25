<?php

if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

class TestThread extends \Thread
{
    public function run() {
        echo $this->getThreadId() . "\n";
        /*
        while(1) {
            $i = 0;
            while(++$i < 100) {
                $this->objects[$i] = new \stdClass();
                usleep(100);
            }
        }
        */
        return $this;
    }
}

$tMax = 1000;
$t = array();

for ($i = 0; $i < $tMax; $i++) {
    $t[$i] = new TestThread();
}

for ($i = 0; $i < $tMax; $i++) {
    $t[$i]->start();
}

/*
for ($i = 0; $i < $tMax; $i++) {
    $t[$i]->join();
}
*/

sleep(1);

echo "finished script...\n";
