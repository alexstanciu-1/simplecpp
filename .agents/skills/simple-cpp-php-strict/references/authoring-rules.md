# Authoring Rules

Use these rules when writing or reviewing strict PHP++ application code.

## Mental Model

PHP++ is PHP-like syntax over the Simple C++ model. It is not PHP compatibility mode.

- `.phs` is the preferred source extension.
- `.php` inputs are compatibility inputs only.
- Generated C++ is useful for inspection, not for authoring fixes.
- Strict mode is selected by `prism.json`, not by PHP `declare(strict_types=1);`.

Strict project shape:

```json
{
  "runtime": {
    "languages": {
      "php": {
        "profile": "strict"
      }
    }
  }
}
```

## Source File Shape

Preferred:

```php
echo "hello\n";
```

Avoid:

```php
<?php
declare(strict_types=1);

echo "hello\n";
```

Prism++ source starts directly with Prism++ declarations or executable code.

## Types

Use explicit types whenever the source shape is known.

Preferred:

```php
function add(int $left, int $right): int {
	return $left + $right;
}

$count int = 0;
$name /** string */ = $row["name"];
```

Avoid:

```php
function add($left, $right) {
	return $left + $right;
}

$name = $row["name"];
```

Both shorthand and annotation forms are valid at supported typed sites:

```php
$count int = 0;
$count /** int */ = 0;
```

## Containers

Use typed containers when the shape is known at compile time.

```php
$items vector<string> = [];
$by_name hash<int> = ["a" => 1, "b" => 2];
$by_id hash<string, int> = [10 => "alex"];
```

Guidance:

- Use `vector<T>` for typed sequential data.
- Use `hash<T>` for typed string-keyed data.
- Use `hash<T, T_KEY>` for typed non-string key families.
- Use dynamic/mixed containers only when the shape is genuinely dynamic, such as decoded JSON before stabilization.

## Dynamic Values

Dynamic expressions stay dynamic until an explicit typed boundary or narrowing point.

Preferred:

```php
$row = json_decode($text);
$name string = $row["name"];
$count int = $row["count"];
```

```php
$counts hash<int> = [];
$counts["id"] = $row["id"];
```

```php
$items vector<int> = [];
$items[] = $row["count"];
```

Avoid carrying unresolved dynamic state through the rest of the program:

```php
$row = json_decode($text);
$count = $row["count"];
echo $count + 1, "\n";
```

Missing-key reads return `null` before typed-boundary conversion is attempted, so guard uncertain data before stabilizing it.

```php
if (isset($row["count"])) {
	$count /** int */ = $row["count"];
	echo $count, "\n";
}
```

## Wrappers

Strict APIs commonly return wrapper-shaped results. Resolve them near a meaningful boundary with `take(...)`.

```php
$err /** error_t */;
$text /** string */ = "";

if (!take($text, $err, fs_get($path))) {
	echo "read failed\n";
	return;
}

echo $text, "\n";
```

Common patterns:

```php
if (!take($fh, io_open($path, "rb"))) {
	return;
}

$pos = str_strpos("banana", "zz") ?? -1;
```

## State Checks

Keep failure, absence, and boolean state separate.

Preferred:

```php
if ($value === false) {
	return;
}

if ($value === null) {
	return;
}
```

Avoid:

```php
if (!$value) {
	return;
}
```

Prefer explicit comparisons:

```php
if ($status === "ready") {
	start_job();
}
```

Avoid direct conditions over arbitrary strings or unresolved `mixed` values.

## Strict Library Surface

Use strict family-prefixed names:

- Filesystem: `fs_get`, `fs_put`, `fs_scan`, `fs_exists`, `fs_remove`
- Strings: `str_strlen`, `str_replace`, `str_explode`, `str_implode`, `str_strpos`
- IO: `io_open`, `io_read`, `io_write`, `io_close`
- JSON: `json_decode`, `json_encode`

Some PHP-owned helpers remain shared where documented, such as:

- `take`
- `count`
- `empty`
- `isset`
- `cli_argc`
- `cli_argv`
- `cli_args`
- `shell_exec`

When unsure, check:

- `specs/simple_cpp_php_strict_quick_learn.md`
- `docs/examples/php/strict/`
- `specs/php/library_profiles.md`
