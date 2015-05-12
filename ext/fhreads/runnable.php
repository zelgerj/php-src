<?php


class TestRunnable
{
    public function __construct($storage)
    {
        $this->storage = $storage;
    }
    public function run()
    {
        $this->storage->set('testKey', 'threadValue');
        $this->storage->set('testRunnableKey', fhread_self());
    }
}

$___runnable = new TestRunnable($a);
$___runnable->run();
