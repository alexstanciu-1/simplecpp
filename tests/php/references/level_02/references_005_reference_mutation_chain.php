<?php
declare(strict_types=1);

$x = 10;
$a =& $x;
$b =& $a;
$b = 77;
echo $x, "
";
