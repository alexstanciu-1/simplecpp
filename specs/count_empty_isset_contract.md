# count(), empty(), isset() runtime contract
Doc Status: normative

## v0.2 collection-only emptiness — 2026-10-07

This amendment supersedes older scalar/wrapper and missing-key emptiness rules in
this document and subordinate runtime/generator material. `empty(x)` asks whether
an existing collection contains zero elements. Lists, keyed collections and strings
qualify; a collection containing a null element is not empty. Strings are empty
only at length zero, so `"0"` is not empty. Where both contracts exist, `empty(x)`
agrees with `count(x) === 0` without requiring traversal to implement emptiness.

Numbers, booleans, null, ordinary records and ownership/nullable/Result wrappers
have no implicit emptiness contract. Wrappers do not delegate this operation to
their payload. Known unsupported types reject during preparation, even on a keyed
path whose entry might be missing. `mixed`/`dynamic` dispatch by runtime kind:
string/table kinds qualify; unsupported kinds throw `invalid_empty_operand`.

Keyed `empty` requires an existing supported leaf. Missing keys, null leaves and
invalid intermediate/key shapes throw, never return true or create data. Path
evaluation remains lazy: stop at the first failure, without evaluating later keys.
Root and reached key expressions run once; already-performed effects are not undone.
Presence remains a separate operation: use `!isset(...)` for absence. A potential
`missing` spelling and user-defined collection emptiness registration are deferred.

Ordinary inline class/struct records are present and truthy regardless of their
fields. Nullable inline records are present/truthy exactly when they contain a
record. These observations do not copy, consume or require a copyable payload.
Neither grants an emptiness contract to the record or its wrapper.

The previous PHP-like scalar/wrapper emptiness contract is intentionally replaced.
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
- `empty(...)` is a collection cardinality query, including strings
- `isset(...)` is a null-sensitive presence query
- `isset(...)` and `empty(...)` must be fully non-mutating
- keyed `isset(...)` must not collapse into pure key-existence semantics when the stored value can be `null`
- wrapper sentinel states must not be reported as present merely because a wrapper object exists

### v0.2 ownership-handle admission

For `shared<T>` and `unique<T>`, `isset` means the handle is present; it neither
reads the pointee nor copies the handle. `empty` is not supported on these wrappers.
A present handle to false/zero is still present. Conditions, unary negation
and strict null comparisons use that same presence meaning.
Weak handles require explicit locking; implicit conditions, negation, probes and
null comparisons are rejected during preparation. Legacy runtime weak observers
do not grant source-level permission or keep the observed object alive.
Ownership handles do not acquire a `count` contract.

### v0.2 callable presence

Explicit `function<R(Args...)>` storage is present exactly when it contains a
callable. `isset`, conditions and unary negation observe that state without
copying the callable environment. Concrete inferred closures are always present.
Neither callable form supports `empty` or `count`. Default construction and
reset semantics follow the [closure contract](language/closures.md#callable-presence-and-reset).
For typed callable collection entries, keyed `isset` requires both successful lookup
and a present callable. A stored absent callable is not present merely because its
key exists. The established lazy, non-creating path rules still apply.

## 3. `count(...)`

`count(...)` returns the logical size only for countable carriers.

Currently countable:
- `vector_t<T>`
- `fixed_array_t<T, N>`
- compiler-module `Storage<T>` and `Keyed_Storage<T>` (live membership, excluding holes)
- `hash_t<T>`
- `mixed_t` that currently carries a live hash/table-compatible value
- `dynamic_t` whose current kind is a table (list or keyed object)

Normative rule:
- countable input -> return size
- anything else -> throw runtime error

For v0.2, statically known non-countable operands reject during preparation;
`mixed`/`dynamic` retain runtime kind checks. The runtime-error examples below
describe runtime dispatch, not permission to compile known invalid operands.
Strings are not countable even though they support collection emptiness; use
`strlen` for byte length. Wrappers do not forward countability to their payloads.
Counting observes container membership without copying/consuming the container
or its elements, including noncopyable elements. Runtime carrier support does not
imply that every carrier already has source registration in the v0.2 compiler.

Examples:
- `count([])` -> `0`
- `count([1, 2])` -> `2`
- `count(null)` -> runtime error
- `count(42)` -> runtime error
- `count("abc")` -> runtime error

## 4. `empty(...)`

The v0.2 collection-only amendment above governs admission and failure. Empty
strings and collections return true; nonempty strings/collections return false.
All other families reject, statically where known and dynamically otherwise.
Missing/invalid keyed paths throw without creation. Do not use this operation as
negated truthiness or a null-sensitive absence test.

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

For supported scalar, boxed and explicit callable payloads, `isset` observes the Result state:
value payloads and the true sentinel are present; failure and false sentinels are
absent. A successful Result carrying boxed null is still in its value state.
Probing the extracted box directly instead reports null as absent.
A successful Result carrying an absent callable is likewise present, whereas probing
the extracted callable reports absence. Conditions instead delegate to callable
presence for value states; neither observation invokes the callable.
No Result family has an implicit `empty` contract. Payload emptiness requires
explicit extraction and a supported resulting collection/string.
Ordinary exceptions evaluating the operand propagate.

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
false, `empty` throws); later key expressions do not run. A null/invalid root therefore
skips even the first key. Ordinary exceptions in evaluated root/key expressions
propagate. An invalid runtime key shape yields false for `isset`, and throws for
`empty`; neither uses a blanket exception catch. Statically unsupported key/type combinations are preparation
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
- `empty("")` is true and `empty("0")` is false
- `empty` on null/scalars/wrappers rejects
- `empty` on a missing keyed lookup throws
- `isset` retains null-sensitive wrapper state semantics
- no autovivification occurs during `isset(...)` / `empty(...)`

## Compiler object collections

The compiler module registers its two collections with the countable lookup
protocol. `isset(collection, key)` tests live membership, since null records cannot
be inserted. Required reads throw without insertion; `unset` tolerates absence.
`empty(collection)` tests live count. Record-valued keyed entries do not acquire
an emptiness contract; use `isset(collection, key)` for membership. See
`specs/compiler_storage.md` for source syntax and iteration rules.
