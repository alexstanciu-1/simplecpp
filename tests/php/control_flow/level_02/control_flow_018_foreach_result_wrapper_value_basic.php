<?php
declare(strict_types=1);

$items /** vector<string> */ = ["alpha.txt", "beta.txt"];
$files /** result_or_false<vector<string>> */ = $items;

foreach ($files as $file) {
	echo $file, "\n";
}
