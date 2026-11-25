# Coding Style
Doc Status: supporting

Purpose: give Codex and human contributors a short default style guide for writing Prism++ code with the current PHP-like surface.

This file is guidance, not a new semantic authority.

Profile scope:

- this guide uses strict `.phs` authoring as the default posture for new code
- older legacy-compatible syntax may still appear elsewhere in the repo, but it is not the recommended style here
- typed docblock declarations such as `$count /** int */ = 0;` are legacy compatibility syntax and should not be used as normal strict authoring

Primary references:

- `specs/strict_mode.md`
- `specs/php/canonical_examples.md`
- `specs/php/catalog.md`
- `specs/dynamic_types.md`
- `specs/array_semantics.md`

## Default Posture

Prefer:

- explicit and predictable code
- typed boundaries at meaningful points
- early stabilization of dynamic values
- explicit handling of failure and absence

Avoid:

- clever PHP shortcuts
- ambiguous truthiness
- unnecessary propagation of `mixed`
- code that depends on full PHP semantics not explicitly supported here

## 1. Types

Prefer explicit types at important boundaries:

- function parameters
- return types
- typed locals when they clarify intent
- typed properties

Good:

```php
function add(int $left, int $right): int {
	return $left + $right;
}

$count int = $data["count"];
```

Less preferred:

```php
function add($left, $right) {
	return $left + $right;
}

$count = $data["count"];
```

## 2. `mixed`

Treat `mixed` as something to stabilize, not something to spread.

Prefer:

- converting at explicit typed boundaries
- narrowing before reuse
- carrying `mixed` only when flexibility is intentional

Good:

```php
$id int = $row["id"];
```

Less preferred:

```php
$id = $row["id"];
$next = $id + 1;
```

## 3. `null` and `false`

Keep `null` and `false` distinct.

Prefer explicit checks for:

- failure
- absence
- success

Good:

```php
$res = find_user();

if ($res === false) {
	return;
}

if ($res === null) {
	return;
}
```

Avoid:

```php
if (!$res) {
	return;
}
```

## 4. Comparisons

Prefer strict comparisons.

Good:

```php
if ($value === 0) {
	echo "zero";
}
```

Avoid loose comparisons unless there is a very specific reason and the behavior is intentional.

## 5. Conditions

Avoid unresolved `mixed` values or arbitrary strings directly in conditions when intent matters.

Prefer:

- explicit boolean normalization
- direct state comparisons
- explicit null/false checks

Good:

```php
if ($status === "ready") {
	start_job();
}
```

Less preferred:

```php
if ($status) {
	start_job();
}
```

## 6. Arrays / Tables

Remember that the current array/table model is narrower than full PHP.

Keep code clear about:

- read vs write paths
- missing-key handling
- append intent

Good:

```php
$items = [];
$items[] = 1;
$items[] = 2;

$name string = $row["name"];
```

Guidance:

- do not assume reads autovivify
- do not assume array/property paths are native by-reference targets
- handle missing keys intentionally

## 7. Functions

Prefer small functions with explicit contracts.

Good:

```php
function format_label(string $name, int $id): string {
	return $name . " #" . $id;
}
```

Guidance:

- keep parameter and return types explicit
- make failure states visible in the function contract
- avoid relying on implicit conversions to rescue unclear code

## 8. Builtins and Libraries

Assume selective support, not full PHP parity.

Prefer:

- checking the relevant builtin spec first
- using already-documented patterns
- adding support deliberately rather than assuming it exists

Check:

- `specs/builtins/`
- `docs/*_builtins.md`

## 9. Generated C++

Do not write source-language code to imitate generated C++.

Generated C++ is useful for:

- debugging lowering
- understanding helper calls
- locating the implementation owner

It is not the style guide for authoring Prism++.

## 10. Short Rule Set

If you only remember a few things, remember these:

- prefer explicit types at meaningful boundaries
- stabilize `mixed` early
- keep `null` and `false` separate
- prefer `===`
- avoid ambiguous truthiness
- write for the supported Prism++ subset, not for full PHP
