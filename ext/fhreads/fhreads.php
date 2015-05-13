<?php

require_once "ext/fhreads/Thread.php";

class TestThread extends Thread
{
    public function __construct()
    {
        $this->a = 'initial';
    }

    public function run()
    {
        echo "yea iam running" . PHP_EOL;
        $this->a = 'threaded';
    }
}

$t = new TestThread();
$t->start();

sleep(1);

// $t->join();

var_dump($t);
/*
class Storage
{
    public $data = array();
    
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
    
    public function get($key)
    {
        return $this->data[$key];
    }
}
*/

/*
$a = new \Storage();
$a->set('testKey', 'globalValue');


new \UserlandThread();

var_dump(fhread_object_get_handle($t));

//var_dump(fhread_create(new \UserlandThread()));

sleep(1);

var_dump($GLOBALS);

/*

class UserlandThread
{
    public function run()
    {
        echo "yea iam running";
    }
}

$maxThreads = 1;

$a = new \Storage();
$a->set('testKey', 'globalValue');

$t = new \UserlandThread();

echo 'Calling fhreads_self()' . PHP_EOL;
var_dump(fhread_self());
echo '---' . PHP_EOL . PHP_EOL;

for ($i = 1; $i <= $maxThreads; $i++) {
    echo 'Calling fhreads_create()' . PHP_EOL;
    $threadId = fhread_create($t);
    var_dump($threadId);
    echo '---' . PHP_EOL . PHP_EOL;
}
    
for ($i = 1; $i <= 2; $i++)
{
    usleep(100000);
    echo ".";
}

echo PHP_EOL . PHP_EOL;

echo 'Calling fhreads_join()' . PHP_EOL;
var_dump(fhread_join($threadId));
echo '---' . PHP_EOL . PHP_EOL;

var_dump($a);

$threadValue = $a->get('testKey');

var_dump($threadValue);

$a->set('testKey', 'endScriptValue');

var_dump($a);

unset($a);

var_dump($a);

*/