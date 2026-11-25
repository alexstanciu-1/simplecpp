# Counting, emptiness and presence

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

### v0.2 ownership-handle admission

For `shared<T>` and `unique<T>`, `isset` means the handle is present; it neither
reads the pointee nor copies the handle. `empty` is not supported on these wrappers.
A present handle to false/zero is still present. Conditions, unary negation
and strict null comparisons use that same presence meaning.
Weak handles require explicit locking; implicit conditions, negation, probes and
null comparisons are rejected during preparation. Legacy runtime weak observers
do not grant source-level permission or keep the observed object alive.
Ownership handles do not acquire a `count` contract.

### v0.2 enum presence — 2026-10-25

`isset` accepts initialized plain enum values and enum-valued keyed leaves.
Every initialized enum case is present, independently of its backing value: zero,
negative values and the first unit-enum case are not absence. Plain operands are
evaluated exactly once, preserving their effects before returning true.

Keyed probes require successful lookup before observing the enum value. Missing
keys return false without inserting entries; nested keys remain lazy and later key
expressions are not evaluated after a missing prefix. Existing nullable/Result leaf
state rules remain authoritative. Presence does not copy or consume the enum.
This admission does not grant enum conditions, `empty`, or `count`.

### v0.2 callable presence

Explicit `function<R(Args...)>` storage is present exactly when it contains a
callable. `isset`, conditions and unary negation observe that state without
copying the callable environment. Concrete inferred closures are always present.
Neither callable form supports `empty` or `count`. Default construction and
reset semantics follow the [closure contract](language/closures.md#callable-presence-and-reset).
For typed callable collection entries, keyed `isset` requires both successful lookup
and a present callable. A stored absent callable is not present merely because its
key exists. The established lazy, non-creating path rules still apply.

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
keys/RHS/validation/commit ordering. Keyed roots may also be instance fields reached
from local/parameter storage through shared or inline owners. Bind the actual field
without a container copy and retain shared owners before evaluating keys. Replacing
an owner handle does not redirect the selected field; changing that field's contents
is observed by subsequent prefix re-resolution. Property visibility applies, and
read-only fields may be probed. An absent object owner throws during ordinary field
access before any keys run; it is not a missing container entry.

Multiple probe operands, standalone property probes, temporary/indexed-object field
roots, additional ownership-wrapper roots and zero-argument bracket probes remain
outside this bounded implementation slice.

Generator lowering must preserve the runtime contract above.

Required rule:
- `isset($a[k])` lowers through the runtime `php::isset(...)` path
- `empty($a[k])` lowers through the runtime `php::empty(...)` path
- generator shortcuts such as pure `has-key` lowering are not valid when they erase the distinction between `missing` and `existing null`
