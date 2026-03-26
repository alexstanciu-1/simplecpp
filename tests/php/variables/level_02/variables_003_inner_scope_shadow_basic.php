<?php
declare(strict_types=1);

// Nested block assignment.
$x = 10;

if (true) {
	$x = 20;
	echo $x, "
";
}

echo $x, "
";
