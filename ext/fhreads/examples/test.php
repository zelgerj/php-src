<?php

namespace Test;

if (!class_exists('\Thread')) {
    require_once __DIR__ . "/../tests/bootstrap.inc";
}

class TestThread extends \Thread
{
    public function __construct($file) {
        $this->file = $file;
    }
    
    public function run()
    {
        rewind($this->file->handle);
        var_dump(fread($this->file->handle, 256));
        $r = array();
        $i = 0;
        while($i < 100) {
            $r[$i] = fopen('php://temp', 'w+');
            fclose($r[$i]);
            $i++;
        }
    }
}

$file = new \stdClass();
$file->handle = fopen('php://temp', 'w+');

fwrite($file->handle, 'This is a test');

$threads = [];
$index = 10;
for ($i = 0; $i < $index; $i++) {
    $threads[$i] = new TestThread($file);
}

for ($i = 0; $i < $index; $i++) {
    $threads[$i]->start();
}

for ($i = 0; $i < $index; $i++) {
    $threads[$i]->join();
}

echo "finished script..." . PHP_EOL;
