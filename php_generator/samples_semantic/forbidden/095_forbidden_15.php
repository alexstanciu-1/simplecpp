<?php
// 095_forbidden_15.php

$a = "test";
$b = 5;
// expected: invalid operation in S2S (string + int)
echo $a + $b;

