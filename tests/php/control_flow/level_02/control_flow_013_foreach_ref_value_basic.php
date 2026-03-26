<?php
declare(strict_types=1);

$items /** vector<int> */ = [1, 2];
foreach ($items as &$item) {
	$item = $item + 10;
}

echo $items[0], "
";
echo $items[1], "
";
