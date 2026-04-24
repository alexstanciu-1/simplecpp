<?php
declare(strict_types=1);

$data = (object) ["a" => 1, "b" => 2];

foreach ($data as &$value) {
	$value = $value + 10;
}

echo $data->a, "\n";
echo $data->b, "\n";
