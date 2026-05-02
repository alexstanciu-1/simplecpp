<?php
declare(strict_types=1);

// String condition truthiness stays PHP-like for control flow, even though string->bool normalization is stricter.
$empty = "";
$zero = "0";
$word = "hello";

echo $empty ? "T\n" : "F\n";
echo $zero ? "T\n" : "F\n";
echo $word ? "T\n" : "F\n";

echo ($empty ?: "fallback"), "\n";
echo ($zero ?: "fallback"), "\n";
echo ($word ?: "fallback"), "\n";
