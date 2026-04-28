<?php
declare(strict_types=1);

echo str_substr("abcdef", -2), "\n";
echo str_substr("abcdef", 1, -2), "\n";
echo str_substr_compare("abcdef", "BC", 1, 2, true), "\n";
echo str_trim("--hi--", "-"), "\n";
echo str_ltrim("--hi--", "-"), "\n";
echo str_rtrim("--hi--", "-"), "\n";
echo str_implode("|", str_explode(",", "a,b,c", 2)), "\n";
echo str_implode("|", str_explode(",", "a,b,c", -1)), "\n";
echo (str_strpos("banana", "na", 3) ?? -1), "\n";
echo (str_strrpos("banana", "na", -3) ?? -1), "\n";
