--TEST--
Ensure that resources are thread-safe in concurrent operations
--FILE--
<?php 

namespace Test;

if (!class_exists('\Thread')) {
    require_once __DIR__ . "/../bootstrap.inc";
}

class TestThread extends \Thread
{
    public function __construct($file, $iMax) {
        $this->iMax = $iMax;
        $this->file = $file;
    }
    
    public function run()
    {
        rewind($this->file->handle);
        var_dump(fread($this->file->handle, 256));
        $r = array();
        $i = 0;
        while($i < $this->iMax) {
            $r[$i] = fopen('php://temp', 'w+');
            usleep(rand(5000,10000));
            $i++;
        }
        $this->r = $r;
        $i = 0;
        while($i < $this->iMax) {
            fclose($r[$i]);
            usleep(rand(5000,10000));
            $i++;
        }
    }
    
    public function __destruct() {
        unset($this->file);
    }
}

$file = new \stdClass();
$file->handle = fopen('php://temp', 'w+');
fwrite($file->handle, 'This is a test');

$threads = [];
$iMax = 20;
for ($i = 0; $i < $iMax; $i++) {
    $threads[$i] = new TestThread($file, $iMax);
}

for ($i = 0; $i < $iMax; $i++) {
    $threads[$i]->start();
}

$sumRCount = 0;
for ($i = 0; $i < $iMax; $i++) {
    $threads[$i]->join();
    $sumRCount += sizeof($threads[$i]->r);
}

echo "\r";
var_dump(($iMax * $iMax) === $sumRCount);

?>
--EXPECT--
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
string(14) "This is a test"
bool(true)
