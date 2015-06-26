<?php

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

class WorkerThread extends Thread
{
    public function __construct($data)
    {
        $this->data = $data;
        
    }
    
    public function run()
    {
        echo "Thread is waiting...\n";
        $this->synchronized(function($self) {
            $self->wait();
            $this->data->test = 'test';
        });
        echo "Thread was released...\n";
    }
}

$data = new stdClass();

$wt = new WorkerThread($data);
$wt->start();

sleep(1);

$wt->synchronized(function($self) {
    $self->notify();
});

sleep(1);

echo "finished script...\n";
$wt->join();