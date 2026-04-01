<?php
// 082_forbidden_2.php

$a = "test";
$b = 5;
// expected: invalid operation in S2S (string + int)
echo $a + $b;

