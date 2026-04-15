<?php
declare(strict_types=1);

// Keyed isset() must be null-sensitive: present value => true, null => false, missing => false.
$row = [];
$row["id"] = 123;
$row["maybe"] = null;


echo isset($row["id"]) ? "T\n" : "F\n";
echo isset($row["maybe"]) ? "T\n" : "F\n";
echo isset($row["missing"]) ? "T\n" : "F\n";
