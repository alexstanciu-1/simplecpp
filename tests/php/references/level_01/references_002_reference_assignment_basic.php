<?php
declare(strict_types=1);

// Basic reference assignment through a second alias.
$value = 2;
$first =& $value;
$second =& $first;
$second = 6;

echo $value, "\n";
