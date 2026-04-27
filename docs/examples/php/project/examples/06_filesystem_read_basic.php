<?php

declare(strict_types=1);

function example_06_filesystem_read_basic(): void
{
	$contents = file_get_contents("data/greeting.txt");

	if ($contents === null) {
		echo "read=null\n";
		return;
	}

	echo "read=", $contents;
}
