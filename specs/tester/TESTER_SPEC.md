# Plain Language Tester Spec
Doc Status: supporting

## Purpose

This document defines the first tester pass for the Simple C++ PHP-like language.

Focus only on the plain language:

- values and types
- expressions
- statements
- small feature combinations

Do not test modules or library-heavy helpers in this pass.

## Core Rule

A short plain-language program should produce the same result through the project pipeline as it would as source-language intent.

If a small obvious example gives the wrong result, that is a bug.

## In Scope

- variables
- scalar values
- nullable values
- arrays
- arithmetic
- string concatenation
- assignment
- `echo`
- functions
- `return`
- `if`
- `if/else`
- `while`
- `do/while`
- `for`
- `foreach` by value
- constants
- basic classes
- namespaces
- `use`
- basic references

## Out Of Scope

- filesystem
- JSON
- I/O
- process helpers
- database helpers
- FastCGI
- generated C++ editing
- string-library helpers such as `strlen`, `substr`, `explode`, `implode`, `trim`, `strtolower`, `strtoupper`

Important:

- string values are in scope
- string library calls are out of scope

## Source Shape

Use normal PHP-like syntax.

Preferred starting shape:

```php
<?php
declare(strict_types=1);
```

## General Testing Rules

- keep each test short
- prove one idea at a time
- combine only 2 to 4 ideas in one test
- prefer obvious expected output
- avoid large mixed-feature programs

## Data Types

### Variables

What to test:

- define a variable
- read it back
- reassign it
- assign from another variable

Example:

```php
<?php
declare(strict_types=1);

$value = 11;
$other = $value;
$value = 14;

echo $other, "\n";
echo $value, "\n";
```

Expected:

```text
11
14
```

### `int`

What to test:

- initialization
- reassignment
- use in arithmetic

Example:

```php
<?php
declare(strict_types=1);

$value = 5;
$value = $value + 3;

echo $value, "\n";
```

Expected:

```text
8
```

### `float`

What to test:

- initialization
- reassignment
- use in arithmetic

Example:

```php
<?php
declare(strict_types=1);

$value = 1.5;
$value = $value + 2.0;

echo $value, "\n";
```

Expected:

```text
3.5
```

### `bool`

What to test:

- initialization
- reassignment
- use in a branch

Example:

```php
<?php
declare(strict_types=1);

$flag = true;
$flag = false;

if ($flag) {
	echo "T\n";
} else {
	echo "F\n";
}
```

Expected:

```text
F
```

### `string`

What to test:

- initialization
- reassignment
- output

Example:

```php
<?php
declare(strict_types=1);

$text = "alpha";
$text = "beta";

echo $text, "\n";
```

Expected:

```text
beta
```

### Nullable Values

What to test:

- initialize with `null`
- assign a value later
- read the value back

Example:

```php
<?php
declare(strict_types=1);

$value /** ?int */ = null;
$value = 7;

echo $value, "\n";
```

Expected:

```text
7
```

### Arrays

What to test:

- empty array
- append
- integer keys
- string keys
- nested arrays
- overwrite by key
- read after write

Example: append and read

```php
<?php
declare(strict_types=1);

$items = [];
$items[] = 4;
$items[] = 9;

echo $items[0], "\n";
echo $items[1], "\n";
```

Expected:

```text
4
9
```

Example: string keys

```php
<?php
declare(strict_types=1);

$row = ["name" => "alex", "age" => 30];

echo $row["name"], "\n";
echo $row["age"], "\n";
```

Expected:

```text
alex
30
```

Example: nested read

```php
<?php
declare(strict_types=1);

$x = [
	"outer" => [
		"inner" => [
			"value" => 42
		]
	]
];

echo $x["outer"]["inner"]["value"], "\n";
```

Expected:

```text
42
```

## Expressions

### Addition

```php
<?php
declare(strict_types=1);

echo 10 + 3, "\n";
```

Expected:

```text
13
```

### Subtraction

```php
<?php
declare(strict_types=1);

echo 10 - 3, "\n";
```

Expected:

```text
7
```

### Multiplication

```php
<?php
declare(strict_types=1);

echo 6 * 4, "\n";
```

Expected:

```text
24
```

### Division

```php
<?php
declare(strict_types=1);

echo 12 / 3, "\n";
```

Expected:

```text
4
```

### String Concatenation

```php
<?php
declare(strict_types=1);

echo "A" . "B", "\n";
echo "N=" . 3, "\n";
```

Expected:

```text
AB
N=3
```

### Reads

What to test:

- variable read
- array element read
- nested array read
- object property read
- class constant read

Property read example:

```php
<?php
declare(strict_types=1);

class Box
{
	public int $value = 15;
}

$box = new Box();
echo $box->value, "\n";
```

Expected:

```text
15
```

## Statements

### Assignment

```php
<?php
declare(strict_types=1);

$left = 2;
$right = 5;
$left = $right;

echo $left, "\n";
```

Expected:

```text
5
```

### `echo`

Single value:

```php
<?php
declare(strict_types=1);

echo "hello", "\n";
```

Expected:

```text
hello
```

Multiple values:

```php
<?php
declare(strict_types=1);

echo "x=", 4, "\n";
```

Expected:

```text
x=4
```

### `return`

```php
<?php
declare(strict_types=1);

function pick(bool $flag): int
{
	if ($flag) {
		return 1;
	}
	return 2;
}

echo pick(false), "\n";
```

Expected:

```text
2
```

## Functions

What to test:

- typed parameters
- typed returns
- default parameters
- local variables
- top-level call

Typed parameter and return:

```php
<?php
declare(strict_types=1);

function add_one(int $value): int
{
	return $value + 1;
}

echo add_one(4), "\n";
```

Expected:

```text
5
```

Default parameter:

```php
<?php
declare(strict_types=1);

function add(int $left, int $right = 2): int
{
	return $left + $right;
}

echo add(5), "\n";
```

Expected:

```text
7
```

## Control Flow

### `if`

```php
<?php
declare(strict_types=1);

if (3 > 1) {
	echo "yes\n";
}
```

Expected:

```text
yes
```

### `if/else`

```php
<?php
declare(strict_types=1);

$value = 2;

if ($value > 3) {
	echo "big\n";
} else {
	echo "small\n";
}
```

Expected:

```text
small
```

### `while`

```php
<?php
declare(strict_types=1);

$i = 0;
while ($i < 3) {
	echo $i, "\n";
	$i = $i + 1;
}
```

Expected:

```text
0
1
2
```

### `do/while`

```php
<?php
declare(strict_types=1);

$i = 0;
do {
	echo $i, "\n";
	$i = $i + 1;
} while ($i < 2);
```

Expected:

```text
0
1
```

### `for`

```php
<?php
declare(strict_types=1);

for ($i = 1; $i <= 3; $i = $i + 1) {
	echo $i, "\n";
}
```

Expected:

```text
1
2
3
```

### `foreach` by value

```php
<?php
declare(strict_types=1);

$items /** vector<int> */ = [4, 5];

foreach ($items as $item) {
	echo $item, "\n";
}
```

Expected:

```text
4
5
```

## Constants

### Top-level constant

```php
<?php
declare(strict_types=1);

const LIMIT = 9;

echo LIMIT, "\n";
```

Expected:

```text
9
```

### Class constant

```php
<?php
declare(strict_types=1);

class Box
{
	public const SIZE = 12;
}

echo Box::SIZE, "\n";
```

Expected:

```text
12
```

## Classes

What to test:

- construction
- property read/write
- instance method call
- class constant access

Property write and read:

```php
<?php
declare(strict_types=1);

class Box
{
	public int $value = 0;
}

$box = new Box();
$box->value = 22;

echo $box->value, "\n";
```

Expected:

```text
22
```

Method call:

```php
<?php
declare(strict_types=1);

class Box
{
	public function getValue(): int
	{
		return 17;
	}
}

$box = new Box();
echo $box->getValue(), "\n";
```

Expected:

```text
17
```

## Namespaces

### Namespace declaration and local class use

```php
<?php
declare(strict_types=1);

namespace Demo;

class Box
{
	public static function value(): int
	{
		return 31;
	}
}

echo Box::value(), "\n";
```

Expected:

```text
31
```

### Fully qualified access

```php
<?php
declare(strict_types=1);

namespace Demo;

class Box
{
	public static function value(): int
	{
		return 8;
	}
}

namespace MainArea;

echo \Demo\Box::value(), "\n";
```

Expected:

```text
8
```

## `use`

### Basic import

```php
<?php
declare(strict_types=1);

namespace Lib;

class Box
{
	public static function value(): int
	{
		return 41;
	}
}

namespace App;

use Lib\Box;

echo Box::value(), "\n";
```

Expected:

```text
41
```

### Namespace alias

```php
<?php
declare(strict_types=1);

namespace Lib\Math;

class Box
{
	public static function value(): int
	{
		return 43;
	}
}

namespace App;

use Lib\Math as M;

echo M\Box::value(), "\n";
```

Expected:

```text
43
```

## References

### Local reference alias

```php
<?php
declare(strict_types=1);

$value = 5;
$alias =& $value;
$alias = 9;

echo $value, "\n";
```

Expected:

```text
9
```

### Simple reference assignment

```php
<?php
declare(strict_types=1);

$left = 3;
$right = 8;
$alias =& $left;
$alias =& $right;
$alias = 11;

echo $left, "\n";
echo $right, "\n";
```

Expected:

```text
3
11
```

## Combination Examples

Use small combinations like these:

Typed variable + arithmetic + `echo`:

```php
<?php
declare(strict_types=1);

$value = 4;
echo $value + 6, "\n";
```

Function + typed parameter + return:

```php
<?php
declare(strict_types=1);

function twice(int $v): int
{
	return $v * 2;
}

echo twice(7), "\n";
```

Loop + array read:

```php
<?php
declare(strict_types=1);

$items = [10, 20];
for ($i = 0; $i < 2; $i = $i + 1) {
	echo $items[$i], "\n";
}
```

Nested array + deep read:

```php
<?php
declare(strict_types=1);

$row = ["a" => ["b" => 5]];
echo $row["a"]["b"], "\n";
```

Class + method + `echo`:

```php
<?php
declare(strict_types=1);

class User
{
	public function id(): int
	{
		return 77;
	}
}

$u = new User();
echo $u->id(), "\n";
```

Namespace + class + static call:

```php
<?php
declare(strict_types=1);

namespace N;

class Util
{
	public static function code(): int
	{
		return 55;
	}
}

echo Util::code(), "\n";
```

Reference + reassignment + readback:

```php
<?php
declare(strict_types=1);

$v = 1;
$r =& $v;
$r = 6;
echo $v, "\n";
```

## Failure Types To Look For

- wrong output value
- missing output
- extra output
- wrong line order
- wrong branch path
- wrong loop count
- wrong array readback
- wrong nested array access
- wrong function return
- wrong method result
- wrong namespace resolution
- wrong reference propagation

## Suggested Coverage Order

1. variables and scalar types
2. `echo`
3. arithmetic and concatenation
4. functions and returns
5. `if` and loops
6. arrays
7. constants
8. classes
9. namespaces and `use`
10. references

## Repo Anchors

- `tests/php/variables/level_01/`
- `tests/php/types/bool/level_01/`
- `tests/php/types/int/level_01/`
- `tests/php/types/float/level_01/`
- `tests/php/types/string/level_01/`
- `tests/php/types/nullable/level_01/`
- `tests/php/operators/level_01/`
- `tests/php/output/level_01/`
- `tests/php/functions/level_01/`
- `tests/php/control_flow/level_01/`
- `tests/php/arrays/level_01/`
- `tests/php/constants/level_01/`
- `tests/php/classes/level_01/`
- `tests/php/namespaces/level_01/`
- `tests/php/use/level_01/`
- `tests/php/references/level_01/`

Do not use:

- `tests/php/filesystem/`
- `tests/php/io/`
- `tests/php/json/`
- `tests/php/process/`
- `tests/php/strings/`

## Reporting Format

Include:

- title
- exact source code
- expected output
- actual output
- whether the issue is stable or intermittent
- smallest repro
- feature area

## Bottom Line

This first-pass tester spec is for core language confidence.

Test small plain-language programs first.
Test small combinations second.
Exclude modules and string-library helpers.
