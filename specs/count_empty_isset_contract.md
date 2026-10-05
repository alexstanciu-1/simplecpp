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
- compiler-module `Storage<T>` and `Keyed_Storage<T>` (live membership, excluding holes)
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

### Result wrapper states

For supported scalar and boxed mixed/dynamic payloads, probes follow this state table. They do not throw
merely because a Result is in a non-value state, and do not extract that absent payload.

| State | `isset` | `empty` |
| --- | --- | --- |
| Value payload in any Result family | true | Payload emptiness |
| `result<T>` failure | false | true |
| `result_or_false<T>` false sentinel | false | true |
| `result_or_bool<T>` false sentinel | false | true |
| `result_or_bool<T>` true sentinel | true | false |

A wrapped boolean-false **payload** is therefore present, unlike the false sentinel.
A wrapped `"0"` is present and not empty, even though it is falsey in a condition.
See [the condition/presence/extraction distinctions](conditional_expression_matrix.md#result-truthiness-is-not-success-or-presence).
Ordinary exceptions evaluating the probe operand still propagate.

A successful Result carrying boxed null is still in its value state: `isset` is
true and `empty` is true. Probing the extracted box directly instead reports null
as absent. This follows the wrapper-state rule, not implicit recursive unwrapping
for presence. Payload emptiness still delegates to the box's scalar/container rules.

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

### v0.2 keyed evaluation order

Keyed probes traverse lazily, left-to-right. Evaluate/bind the root once, check that
the current receiver supports lookup, evaluate the reached key once, and inspect its
entry. Missing/null/non-container intermediates end the probe immediately (`isset`
false, `empty` true); later key expressions do not run. A null/invalid root therefore
skips even the first key. Ordinary exceptions in evaluated root/key expressions
propagate. An invalid runtime key shape yields the probe's absent result, not a
blanket exception catch. Statically unsupported key/type combinations are preparation
errors under the v0.2 diagnostic policy.

Key expressions may mutate the root. Re-resolve the reached prefix from the bound
root and saved earlier keys after each such expression; use its current state, never
an earlier interior reference. If the prefix disappeared, stop without evaluating
later keys. No source expression is repeated. No whole-container snapshot is implied.
Cross-family mixed-table/dynamic paths are excluded by the approved
[v0.2 family-separation contract](dynamic_types.md#v02-separated-mixeddynamic-families--2026-10-05).
Runtime/compiler migration remains a Chapter 06 implementation gate.

This replaces the earlier eager-key proposal for probes; writes keep their separate
keys/RHS/validation/commit ordering. Multiple probe operands, property paths and
zero-argument bracket probes remain outside the bounded implementation slice.

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

## Compiler object collections

The compiler module registers its two collections with the countable lookup
protocol. `isset(collection, key)` tests live membership, since null records cannot
be inserted. Required reads throw without insertion; `unset` tolerates absence.
`empty(collection)` tests live count, and `empty(collection, key)` is true only
for absent membership (present records are non-null object handles). See
`specs/compiler_storage.md` for source syntax and iteration rules.
