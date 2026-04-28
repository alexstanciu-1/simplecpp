<?php
declare(strict_types=1);

$value = json_decode("{\"a\":1,\"b\":[2,3],\"c\":\"x\"}");
echo $value["a"], "\n";
echo $value["b"][1], "\n";
echo json_encode($value), "\n";
