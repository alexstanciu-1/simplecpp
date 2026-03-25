<?php
declare(strict_types=1);

/** @var array<int,int> */
$v = [1,2,3];

foreach ($v as $value) {
    $value = 10; // should NOT affect array
}

foreach ($v as $k => $value) {
    echo $k, ":", $value, "\n";
}
