<?php
declare(strict_types=1);

function ok(): mixed {
	return 7;
}

function fail(): mixed {
	return null;
}

$a = ok();
$b = fail();

var_dump($a ?? 0);
var_dump($b ?? 0);
