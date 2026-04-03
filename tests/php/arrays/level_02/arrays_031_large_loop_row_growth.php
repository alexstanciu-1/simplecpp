<?php
declare(strict_types=1);

$x = [];
for ($i = 0; $i < 100; $i++) {
    $x[] = ["id" => $i + 1, "name" => "Alex{$i}"];
}

echo $x[0]["name"], "
";
echo $x[99]["id"], "
";
echo count($x), "
";
