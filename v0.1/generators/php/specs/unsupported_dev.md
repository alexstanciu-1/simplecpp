# What is NOT PHP (Important Differences)
Doc Status: planning
Prism++ is not PHP. Key differences:

## Null
$x = null; // âœ… first assignment lowers to mixed_t x = null;
$x /** ?int */ = null; // âœ…

## References
$b =& $a; // âŒ

## unset
unset($x); // âŒ
clean($x); // âœ…

## Arrays
$a = [1,2,3]; // âŒ
$a /** vector<int> */ = [1,2,3]; // âœ…

## Dynamic properties
$obj->newProp = 1; // âŒ

## Include
require "file.php"; // âŒ
include "file.php"; // âŒ
require_once __DIR__ . "/file.php"; // âŒ
require_once "file.php"; // âœ… compile-time #include subset only

## Typing
$x = 10;
$x = "hello"; // âŒ

Think: PHP syntax + static typing + C++ runtime.
