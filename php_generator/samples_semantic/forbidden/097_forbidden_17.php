<?php
// 097_forbidden_17.php

$a = "test";
$b = 5;
// expected: invalid operation in S2S (string + int)
echo $a + $b;

