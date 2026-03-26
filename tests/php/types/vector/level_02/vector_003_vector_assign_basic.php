<?php
declare(strict_types=1);

// Vector assignment between explicit vector<int> variables.
$a /** vector<int> */ = [1, 2];
$b /** vector<int> */ = [];
$b = $a;

echo $b[0], "
";
echo $b[1], "
";
