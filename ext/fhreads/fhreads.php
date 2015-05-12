<?php


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

$maxThreads = 10;

$a = new \Storage();
$a->set('testKey', 'globalValue');

echo 'Calling fhreads_self()' . PHP_EOL;
var_dump(fhread_self());
echo '---' . PHP_EOL . PHP_EOL;

for ($i = 1; $i <= $maxThreads; $i++) {
    echo 'Calling fhreads_create()' . PHP_EOL;
    $threadId = fhread_create();
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
