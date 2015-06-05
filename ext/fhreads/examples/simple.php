<?php

// to be compatible with pthreads lib
if (!class_exists('\Thread')) {
  require_once __DIR__ . DIRECTORY_SEPARATOR . "Thread.php";
}

class DummyThread extends Thread
{
  public function __construct($a) {
    $this->a = $a;
  }

    public function run()
    {
      usleep(200);
        echo __METHOD__ . PHP_EOL;
      $this->a->a = new stdClass();
    }
}

class TestThread extends Thread
{
  public $x;

  public function run()
  {
    $b = new \stdClass();
    unset($b);
    $this->x = new \stdClass();
    echo "### IN THREAD CONTEXT" . PHP_EOL;
    var_dump($this->x);

    $t = new DummyThread($this);
    $t->start();
  }
}

$a = new \stdClass();

$t = new TestThread();
$t->start();

$z = new \stdClass();
echo "### IN SCRIPT CONTEXT" . PHP_EOL;
var_dump($z);

var_dump($t);
unset($t);

echo "finished script..." . PHP_EOL . PHP_EOL;
