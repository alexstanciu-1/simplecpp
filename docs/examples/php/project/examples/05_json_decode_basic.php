<?php

declare(strict_types=1);

function example_05_json_decode_basic(): void
{
	$json = '{"name":"Alex","count":2}';
	$decoded = json_decode($json);
	$name /** string */ = $decoded["name"];
	$count /** int */ = $decoded["count"];

	echo "name=", $name, "\n";
	echo "count=", $count, "\n";
}
