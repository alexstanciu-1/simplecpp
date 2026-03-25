<?php
declare(strict_types=1);

/** @var array<int,int> */
$v = [1,2,3];

foreach ($v as &$value) {
    $value = 10; // should mutate
}

foreach ($v as $value) {
    echo $value, "\n";
}
