<?php

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

// define counter thread class which counts the shared data objects counter property
class CounterThread extends Thread
{
    /**
     * Constructor which refs the shared data object to this
     *
     * @param object $data
     */
    public function __construct($data)
    {
        //$this->data = $data;
    }

    public function run()
    {
      /*
        // lock data object
        fhread_mutex_lock($this->data->mutex);
        // inc counter
        ++$this->data->counter;
        // add message that this thread was here
        $this->data->objects[$this->getThreadId()] = new stdClass();
        // unlock data object
        fhread_mutex_unlock($this->data->mutex);
        */
        usleep(rand(0,2000));

        $this->a = array();
        $ii = 0;
        while(1) {
          $i = 0;
          $this->a = array();
          while (++$i < 100) {
            $this->a[$i] = new stdClass();
            usleep(10);
          }
          echo ".";
        }
    }
}

// create data storage object
$data = new \stdClass();
$data->objects = array();
// init mutex
$data->mutex = fhread_mutex_init();
// init counter property to be zero
$data->counter = 0;

// init threads array
$ths = array();

// define max threads
$tMax = 100;

// initiate threads
for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i] = new CounterThread($data);
}

// start threads
for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i]->start();
}

// wait for all thread to be finished by joining them
for ($i = 1; $i <= $tMax; $i++) {
    $ths[$i]->join();
}

// echo status
echo "$tMax threads finished..." . PHP_EOL;

// echo status
echo PHP_EOL . "finished script!" . PHP_EOL;
