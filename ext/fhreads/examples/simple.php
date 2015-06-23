<?php 

class TestRunnable
{
    public $test = 'asdf';
    
    public function run() {
        $this->asdf = 'aaaa';
        echo __METHOD__ . PHP_EOL;
    }
}

$r = new TestRunnable();

$status = fhread_create($r, $threadId);
fhread_join($threadId);

sleep(1);

var_dump($r);

echo "finished script..." . PHP_EOL . PHP_EOL;