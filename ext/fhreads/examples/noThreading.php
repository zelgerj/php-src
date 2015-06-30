<?php

class Test {
    public function __construct()
    {
        
    }
    public function run()
    {
        echo __METHOD__ . PHP_EOL;
    }
}

$t = new Test();
$t->run();

usleep(100000);

echo "finished script..." . PHP_EOL;

?>