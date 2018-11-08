<?php
include __DIR__ . '/vendor/autoload.php';

use mutex\Mutex;

$ok = Mutex::lock('test', 30);
var_dump($ok);
if ($ok) {
    for ($i = 0; $i < 60; ++$i) {
        echo "$i\n";
        if (!rand(0, 3)) die;
        sleep(1);
    }
}
