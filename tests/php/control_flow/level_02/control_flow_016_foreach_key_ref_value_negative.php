<?php
declare(strict_types=1);

$items /** vector<int> */ = [4, 5];
foreach ($items as $k => &$value) {
	$value = $value + 1;
}

echo $items[0], "
";
