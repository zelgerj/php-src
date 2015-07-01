<?php

if (!class_exists('\Thread')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

/*
* In this example, you will see how to make a process and thread synchronize with each other
*/

/* this is just so reading the logic makes sense */
function do_some_work($m) { usleep($m); }


class ExampleThread extends Thread {
    
    /*
    * always set defaults for threaded objects in constructors
    * note: using entry level defaults (at the class declaration)
    *   does not work as expected in pthreads, this is because
    *   handlers are not invoked to set defaults at the class level
    */
    public function __construct() {
        
    }
    
    public function run() {
        try {
            while (!$this->isWaiting()) {
                /* the process is not yet waiting */
                /* in the real world, this would indicate
                    that the process is still working */
                /* in the real world, you might have work to do here */
                echo ".";
            }
            echo "\n";
            
            /* always synchronize before calling notify/wait */
            $this->synchronized(function(){
                /* there's no harm in notifying when no one is waiting */
                /* better that you notify no one than deadlock in any case */
                $this->notify();
            });
        } catch (EngineException $e) {
            var_dump($e);
        }
    }
}

/* construct the new thread */
$GLOBALS['asdf'] = $t = new ExampleThread();

/* start the new thread */
if ($t->start()) {
    printf("\nProcess Working ...\n");
    
    do_some_work(1000);
      
    /* synchronize in order to call wait */
    $t->synchronized(function(){
        /*
        * note: do not stay synchronized for longer than you must
        *   this is to reduce contention for the lock 
        *   associated with the threads internal state
        */
        printf("\nProcess Waiting ...\n");
        $this->wait();
        printf("Process Done ...\n");
    });
    
}


echo "finished script...\n";

?>
