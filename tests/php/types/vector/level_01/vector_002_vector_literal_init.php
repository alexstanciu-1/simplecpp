<?php
declare(strict_types=1);

// Basic vector literal initialization in explicit typed context.
$items /** vector<int> */ = [1, 2, 3];

echo count($items), "\n";
