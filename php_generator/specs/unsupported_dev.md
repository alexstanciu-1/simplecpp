# What is NOT PHP (Important Differences)

Simple C++ is not PHP. Key differences:

## Null
$x = null; // ❌
$x /** ?int */ = null; // ✅

## References
$b =& $a; // ❌

## unset
unset($x); // ❌
clean($x); // ✅

## Arrays
$a = [1,2,3]; // ❌
$a /** vector<int> */ = [1,2,3]; // ✅

## Dynamic properties
$obj->newProp = 1; // ❌

## Include
require "file.php"; // ❌

## Typing
$x = 10;
$x = "hello"; // ❌

Think: PHP syntax + static typing + C++ runtime.
