# count(), empty(), isset() runtime contract
Doc Status: normative
This document is the single semantic source of truth for the current Prism++ runtime contract for:
- `count(...)`
- `empty(...)`
- `isset(...)`

It exists to prevent drift between generator lowering, runtime helpers, and tests.

## 1. Scope

This contract applies across:
- plain values
- `mixed_t`
- keyed array/hash-like access
- nested keyed access through non-mutating reads
- invalid lookup shapes

Property access follows the same top-level rule once lowered into a value read.

## 2. Design rule

These APIs are semantic hubs and must not be implemented as unrelated shortcuts.

Normative rule:
- `count(...)` is a strict countability query
- `empty(...)` is a PHP-aligned emptiness query with one explicit Prism++ deviation for `"0"`
- `isset(...)` is a null-sensitive presence query
- `isset(...)` and `empty(...)` must be fully non-mutating
- keyed `isset(...)` must not collapse into pure key-existence semantics when the stored value can be `null`
- wrapper sentinel states must not be reported as present merely because a wrapper object exists

## 3. `count(...)`

`count(...)` returns the logical size only for countable carriers.

Currently countable:
- `vector_t<T>`
- `fixed_array_t<T, N>`
- `hash_t<T>`
- `mixed_t` that currently carries a live hash/table-compatible value

Normative rule:
- countable input -> return size
- anything else -> throw runtime error

Examples:
- `count([])` -> `0`
- `count([1, 2])` -> `2`
- `count(null)` -> runtime error
- `count(42)` -> runtime error
- `count("abc")` -> runtime error

## 4. `empty(...)`

`empty(...)` is intended to stay close to PHP for value states that semantically make sense, with one deliberate Prism++ deviation:
- `empty("0")` is `false` in Prism++

Normative rule:
- for supported value families, `empty(x)` follows PHP-style emptiness
- `"0"` remains the one documented exception and evaluates as not empty
- unsupported/nonsensical type families must runtime-error rather than inventing ad-hoc emptiness semantics

`empty(x)` is `true` for:
- `null`
- `false`
- `0`
- `0.0`
- empty string `""`
- empty countable / empty array-like value
- missing keyed lookup
- invalid keyed lookup shape
- wrapper states that expose no present value under the same probe semantics

`empty(x)` is `false` for:
- `"0"`
- any other non-empty string
- any non-empty countable
- present non-empty object-handle / scalar / wrapper values that have a visible present value and are not otherwise empty by the supported rule

Examples:
- `empty(null)` -> `true`
- `empty(false)` -> `true`
- `empty(0)` -> `true`
- `empty(0.0)` -> `true`
- `empty("")` -> `true`
- `empty([])` -> `true`
- `empty("0")` -> `false`

This single documented difference from PHP must remain stated anywhere `empty(...)` semantics are described.

## 5. `isset(...)`

`isset(...)` is null-sensitive.

Normative rule:
- present non-null value -> `true`
- missing keyed lookup -> `false`
- existing keyed slot with `null` -> `false`
- invalid lookup shape -> `false`
- wrapper false/empty sentinel states that do not expose a present value under probe semantics -> `false`

Examples:
- `isset($a["missing"])` -> `false`
- `$a["k"] = null; isset($a["k"])` -> `false`
- `$a["k"] = 1; isset($a["k"])` -> `true`

## 6. Non-mutating lookup rule

`isset(...)` and `empty(...)` must never:
- create slots
- autovivify intermediate containers
- write default values
- mutate the base container as a side effect of probing

This applies to:
- top-level keyed access
- nested keyed access
- mixed/native carrier paths

## 7. Lowering rule

Generator lowering must preserve the runtime contract above.

Required rule:
- `isset($a[k])` lowers through the runtime `php::isset(...)` path
- `empty($a[k])` lowers through the runtime `php::empty(...)` path
- generator shortcuts such as pure `has-key` lowering are not valid when they erase the distinction between `missing` and `existing null`

## 8. Tests

At minimum, coverage must prove:
- `count(42)` throws
- `count(mixed_t(hash))` returns size
- `empty(null)` is `true`
- `empty(false)` is `true`
- `empty(0)` is `true`
- `empty(0.0)` is `true`
- `empty("")` is `true`
- `empty("0")` is `false`
- `empty(missing keyed lookup)` is `true`
- `isset(missing keyed lookup)` is `false`
- `isset(existing null)` is `false`
- `isset(result_or_false(false-sentinel))` is `false`
- `isset(result_or_bool(false-sentinel))` is `false`
- no autovivification occurs during `isset(...)` / `empty(...)`
