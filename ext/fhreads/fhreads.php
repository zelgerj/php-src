<?php

echo 'Calling fhreads_self()' . PHP_EOL;
var_dump(fhreads_self());
echo '---' . PHP_EOL . PHP_EOL;

echo 'Calling fhreads_create()' . PHP_EOL;
$threadId = fhreads_create();
var_dump($threadId);
echo '---' . PHP_EOL . PHP_EOL;

echo 'Calling fhreads_join()' . PHP_EOL;
var_dump(fhreads_join($threadId));
echo '---' . PHP_EOL . PHP_EOL;

