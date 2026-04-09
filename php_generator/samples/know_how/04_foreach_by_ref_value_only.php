<?php
declare(strict_types=1);

/** @var array<int,int> */
$data = [1, 2, 3];

foreach ($data as &$v) {
	$v++;
}

foreach ($data as $value) {
	echo $value, "\n";
}
