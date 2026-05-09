# PHP Habit Gotchas

Use this reference when code looks natural in normal PHP but fails or becomes fragile in strict PHP++.

## File Headers

Do not add PHP file headers to Prism++ source.

Avoid:

```php
<?php
declare(strict_types=1);
```

Use headerless `.phs` source:

```php
echo "hello\n";
```

Prism++ strictness is controlled by project profile and language/runtime rules, not by PHP `strict_types`.

## Profile Mixing

Do not mix strict and legacy surfaces.

Strict examples:

```php
$text /** string */ = "";
take($text, $err, fs_get($path));
echo str_strlen($text), "\n";
```

Legacy-style names are not strict defaults:

```php
file_get_contents($path);
strlen($text);
fopen($path, "rb");
```

If the project is legacy, use legacy guidance instead of this skill.

## Includes And Project Shape

Do not use PHP `require`, `require_once`, `include`, or `include_once` as the default project composition mechanism.

Use `prism.json` project dependencies and `/** @lib-export */` for cross-project visibility.

## Truthiness

Do not collapse `false`, `null`, empty strings, zero, and errors into one generic condition unless the local spec says that exact behavior is intended.

Preferred:

```php
if ($result === false) {
	return;
}

if ($result === null) {
	return;
}
```

Avoid:

```php
if (!$result) {
	return;
}
```

Do not use arbitrary string or unresolved `mixed` values directly as conditions when intent matters.

## Dynamic JSON

`json_decode(...)` returns dynamic data. Treat typed reads from it as shape assumptions.

Preferred:

```php
$data = json_decode($text);

if (isset($data["name"])) {
	$name /** string */ = $data["name"];
	echo $name, "\n";
}
```

Avoid assuming decoded JSON is already typed:

```php
$data = json_decode($text);
$name = $data["name"];
```

## Arrays And Tables

Prism++ array/table behavior is narrower than full PHP arrays.

Remember:

- Plain reads do not create storage.
- Missing-key reads produce `null`.
- Writes may create storage.
- Nested writes may autovivify missing table/hash nodes.
- Wrong-kind intermediates throw instead of silently becoming arrays.
- Array/property paths are not approved native by-reference binding targets in the safe subset.

Prefer typed containers when possible:

```php
$items vector<int> = [1, 2, 3];
$scores hash<int> = ["alice" => 10];
```

## Wrappers Are Not Plain Values

Strict APIs often return wrappers instead of raw values.

Preferred:

```php
$err /** error_t */;
$text /** string */ = "";

if (!take($text, $err, fs_get($path))) {
	echo "read failed\n";
	return;
}
```

Avoid carrying wrapper state forward as if it were ordinary PHP dynamic output.

## Debug Code Can Be Fragile Too

When adding debug output, keep it strict:

- Stabilize dynamic values before arithmetic or formatting.
- Use typed loop variables and typed containers when shape is known.
- Keep debug export helpers small and explicit.
- Validate with `scpp build` or `scpp run`, then inspect `scpp error` on failure.

## Backend Noise

Do not start debugging by running Ninja directly or editing generated C++.

Use:

```bash
scpp error
scpp full-error
scpp last-run
scpp full-last-run
```

Generated C++ and `.line.tsv` files are useful after the saved diagnostics point there.
