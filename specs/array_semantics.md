# Array / Table Semantics in Prism++
Doc Status: normative
Status: Active

For v0.2, [collection-only emptiness](count_empty_isset_contract.md#v02-collection-only-emptiness--2026-10-07)
supersedes old scalar/null/missing-key `empty` rules in this document. Keyed `empty`
requires an existing string/collection leaf; missing/null and invalid paths throw.
`isset`/`unset` retain their separately specified absence behavior and lazy traversal.

See `specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.
See also:
- [Data notation and initialization interfaces](data_initialization.md) — the v0.2 construction standard; implementation availability is tracked separately
- `specs/dynamic_types.md`
- `specs/native_reference_safety.md`
- `specs/references.md`

## 1. Purpose

This document defines the current supported Prism++ array/table subset.

It is intentionally narrower than full PHP array semantics.
The goal is to stabilize:
- value reads
- write paths
- nested mutation
- append behavior
- missing-key behavior
- `isset` / `empty` / `unset` on array/table carriers
- the interaction between array reads and typed value destinations

This document does **not** expand the supported native-reference surface.
Reference safety remains governed by `specs/native_reference_safety.md` and `specs/references.md`.

## v0.2 value iteration

The bounded `foreach ($source as $value)` and `foreach ($source as $key => $value)`
forms accept registered vector, hash, mixed-table and dynamic-container sources.
Evaluate the source expression once before binding either variable; retain it for
the loop lifetime, including temporary function results. Vector keys are `int` and
values are T; hash<V,K> keys are K and values are V. Mixed iteration values are mixed;
dynamic iteration values are dynamic. Both retain the established int/string key
domain (represented by scalar mixed keys). This follows the approved
[separated-family contract](dynamic_types.md#v02-separated-mixeddynamic-families--2026-10-05),
whose runtime/compiler migration is still pending. Vectors visit index order, hashes/tables
visit their established entry order. Each selected value uses its ordinary copy
semantics; no native reference to an element is exposed.

The key is a fresh loop-local binding. The value reuses an already visible binding
when present, applying its ordinary typed-assignment conversion before each body
execution; otherwise it is a new loop-local binding inferred from the element type.
New bindings do not leak outside the loop. Zero iterations leave an existing value
unchanged. Key and value names must be distinct. Conversion failure propagates before
that body execution; completed earlier iterations are not rolled back. This v0.2 rule
supersedes older contradictory generator guidance that both names always shadow.

Preparation rejects statically noniterable types. Mixed and dynamic are admitted
statically but checked at runtime: a non-container payload (including null) throws.
Valid empty containers execute zero iterations. The old runtime's
silent-empty mixed fallback is not the v0.2 rule. Weak and cross-family iteration,
automatic nullable/Result unwrapping, destructuring and by-reference bindings remain
outside this implementation slice. Ordinary `break`, `continue` and `return` apply;
an iteration body alone cannot satisfy a required return because the source can be empty.

**Mutation/replacement during iteration is unresolved and unsupported in this slice.**
This includes mutations through aliases or calls. No shallow/deep collection snapshot
is introduced; copying the current element is not a snapshot of all entries. Neither
live-mutation behavior nor a particular rejection/checking policy is promised here.
CH06-ITER-01 blocks final catalog closeout in Chapter 15 until the user agrees on semantics and a suitable
performant lifetime/invalidation design. It does not block non-mutating value iteration.

## 2. Core model

### v0.2 function boundaries

Whole-container arguments and returns use ordinary value/copy semantics: typed
vector/hash values are independent values, mixed tables preserve copy-on-write,
and dynamic container copies preserve shared object identity; dynamic scalars copy
as values. Replacing a by-value
dynamic parameter rebinds only that parameter; modifying its shared object is
observable through the caller's handle. An exact-type whole-container local or
parameter may bind to an ordinary reference parameter, including wrapper replacement.

An indexed argument such as `consume($items[$key])` is an ordinary value read followed
by the parameter's ordinary conversion. It never changes into a write path merely
because it occurs in a call. Missing typed-container entries throw; missing
mixed/dynamic entries yield null in the receiver's family before any required typed conversion.
Neither form inserts entries. Arguments and their conversions are evaluated in
source order; failure prevents later argument evaluation and callee entry without
rolling back earlier source effects. Previously materialized value arguments retain
their normal copy/identity semantics across later argument evaluation.

Indexed expressions cannot bind to native reference parameters, even when their
value type matches exactly or the element is itself a container. A temporary is
not a substitute for a reference to the original slot. These rules supersede old
generator guidance that direct DIM call arguments could autovivify.

Strict `mixed` parameters are not a spelling alias for legacy PHP `array` parameters:
they do not implicitly impose a table-only function-entry guard. Legacy `array` /
`?array` admission and guard support are separately tracked, not established by
these strict-container boundaries.

### v0.2 construction boundary

Construction now follows [data initialization](data_initialization.md): positional,
keyed and named notation selects a recognized initialization interface on a known
target type. Typed, mixed and dynamic construction share that protocol. An unresolved
target is a preparation error, not an implicit dynamic array. Nested mixed/dynamic
construction inherits its enclosing construction family, and entries retain that
family. Cross-family containers reject. Factory-specific data validation runs at runtime.

This updates construction defaults, not the read/write/reference rules below.
v0.1 implicit array examples are not proof of v0.2 target resolution or support.

Prism++ treats array/table operations through three semantic classes:

### 2.1 Value read path

For v0.2, distinguish statically typed containers from mixed/dynamic carriers:

| Receiver | Read result | Missing key/index |
| --- | --- | --- |
| `vector<T>` | copied `T` value | runtime bounds error, including negative indexes |
| `hash<V, K>` | copied `V` value | runtime missing-key error |
| `mixed` | `mixed` value | null-kind mixed |
| `dynamic` | `dynamic` value | null-kind dynamic |

The null-on-miss rules below describe mixed/dynamic array/table reads, not typed
vector/hash access. A typed destination never changes the lookup policy: dynamic
lookup first yields its own family value (possibly null), then ordinary checked extraction applies.
This clarifies the former broad wording without imposing nullable results on typed
container reads. See [the bracket contract](index_operator.md).

The receiver is evaluated before the key, each once. Reads copy the selected value,
not an interior reference; ordinary handle copies retain shared identity. A receiver
temporary remains alive through lookup and result copying. Binding a stable receiver
does not promise a deep snapshot of that container before key-expression effects.
No read inserts keys or autovivifies containers.

The first v0.2 key domain is ordinary `int` for vectors, the declared ordinary
`int`/`string` key for typed hashes, and `int`/`string`/runtime-checked `mixed` for
mixed/dynamic. Mixed keys at typed boundaries use ordinary checked extraction.
No implicit bool/float/null key coercion is introduced. Static invalid types reject
during preparation; unknown invalid mixed key kinds throw at runtime, even when the
receiver is null. Existing numeric-string normalization applies to mixed/dynamic.

Null receivers/intermediates return same-family null for valid keys.
Existing non-container scalars throw instead of being treated as missing keys.
Nested reads reuse this rule at each step without creating intermediate storage.
Existing runtime table carriers remain admitted; expired weak carriers read as null.
Chapter 06's containment debt stays open until implementation matches the approved
family separation; existing cross-family runtime carriers are not v0.2 permissions.

Examples:
```php
$x = $a["k"];
echo $a["k"];
return $a["k"];
takeValue($a["k"]);
```

Rules:
- a value read does not create storage
- a value read does not autovivify
- a missing-key read produces `null`
- nested value reads also stay non-mutating

### 2.2 Write path

#### v0.2 operation and evaluation contract

Writable indexed access is a selected operation, not permission to expose native
element references. Readability, indexed writing, append and future compound updates
are independent capabilities of the receiver contract. Empty brackets select append
only when its registered contract supports that operation in assignment context.

Direct writes evaluate/bind the receiver first, then evaluate, convert and validate
the key (when present), then evaluate and convert the assigned value, then perform
the store/append. Each expression executes once. Invalid key conversion/kind prevents
RHS evaluation. Receiver-kind, bounds and storage validation occur at the write;
failed RHS conversion cannot insert a placeholder key or append an element. Earlier
source-expression side effects are not rolled back. This is not a promise of strong
transactional rollback on allocation/copy failures during storage mutation.

An indexed receiver may be writable instance-field storage, using the same operation
contract as a local container. The selected field is not copied for mutation. Shared
owners along the field path are retained before keys/RHS; replacing the source handle
during those expressions does not redirect the pending write to the replacement.
Inline fields are borrowed in place. Property visibility and read-only restrictions
apply normally; this grants no escaping native reference into heap storage.

Vector indexed writes replace existing elements with checked bounds, never grow at
index `count`; append explicitly adds one element. Typed hashes insert or replace,
and only integer-keyed typed hashes append. Mixed/dynamic use existing integer/string
key admission and normalization; appends use the next integer key, not entry count.
Updating an existing key is valid: construction's duplicate-key prohibition does not
apply to ordinary writes. Null mixed may bootstrap table storage on write/append.
Null dynamic rejects mutation; explicitly construct a container first.

Construction, element assignment and append share a container-element value boundary:
typed receiving elements apply ordinary checked value conversions; mixed/dynamic
elements admit same-family values and native scalar/null boxing as construction.
Cross-family containers reject; cross-family scalar/null values require explicit
conversion. There is no special implicit dynamic-to-mixed element-boxing exception.
The receiving element supplies context to a direct nested data literal. Mixed/dynamic
literal defaults follow the enclosing family:
`$rows[] = [1, 2]` for `vector<vector<int>>`, or `$data["child"] = {enabled: true}`
for dynamic. Shared dynamic payload identity and mixed copy-on-write are preserved.
An assignment expression yields the converted assigned value, not an appended key
or native reference; a statement need not materialize that otherwise-unused result.

#### v0.2 nested write paths

Nested writes extend the same operation contract. Bind the local/parameter root;
evaluate, convert and validate all explicit keys left-to-right; evaluate and convert
the RHS; validate the complete destination path without mutation; then commit.
Each source expression executes once. Final append is supported; intermediate
append (`$rows[][0] = 1`) is deferred. Properties, call-result roots, indexed compound
updates and native element references remain outside this bounded slice.

Path resolution observes the root's current state **after** key/RHS effects. For
example, an RHS that replaces the root changes the object/container subsequently
targeted. The compiler must not preserve an earlier interior reference or read-copy
snapshot across these calls. Internal traversal references are allowed only after
source evaluation and cannot escape as source-language references.

Creation and validation rules:

- Existing typed vector/hash intermediates are traversed in place. A missing typed
  hash intermediate throws; explicitly initialize it first. No vector index grows
  the vector, including an intermediate index.
- Mixed table paths may create missing/null intermediate mixed tables. Existing
  scalar intermediates throw rather than being replaced.
- Dynamic paths traverse already-constructed dynamic children. Missing/null children
  throw; construct children explicitly first. Do not
  bootstrap mixed tables beneath a dynamic receiver.
- Cross-family mixed-table/dynamic paths and aggregate insertion reject under the
  separated-family contract, consistently with direct writes and construction.
  Statically known unsupported combinations reject during preparation; unknown
  payloads are checked at runtime before destination mutation.

Invalid keys skip later key/RHS evaluation. Failed RHS evaluation/conversion and
path kind/missing/bounds checks cause **no write-induced partial creation or changes**.
Previously executed key/RHS side effects remain. Mutation-time allocation/copy
failures still have no general transaction/rollback guarantee. Validation and commit
are synchronous; concurrent unsynchronized mutation is not made safe by this rule.
Mixed copy-on-write and dynamic shared identity must both be preserved. No full-root
transaction copy is required; ordinary COW detachment may copy shared tables.

General source-defined indexing remains separate work.

Examples:
```php
$a["k"] = 1;
$a["x"]["y"] = 1;
$a[] = $v;
```

Rules:
- a write path is allowed to create storage
- top-level missing-key write creates the key
- nested writes may autovivify missing intermediate table/hash nodes
- nested writes throw if an existing intermediate node has the wrong kind and cannot act as a table/hash carrier

### 2.3 Native-reference path
Examples:
```php
f($a["k"]);     // where f(int &$x)
$r =& $a["k"];
return $a["k"]; // by reference
```

Rules:
- array/property paths are not approved native by-reference binding targets in the current safe subset
- generated code may still call `.try_ref()` on the **resulting value** as a restricted runtime attempt
- `.try_ref()` succeeds only for `shared_p<T>` and returns a copy
- `.try_ref()` on all other element/value kinds fails by throwing
- `.try_ref()` does not provide slot write-back aliasing

This preserves the rule that Prism++ does not expose native references or pointers to dynamic interior storage owned by another object.

## 3. Missing-key behavior

### 3.1 Plain value read

```php
$x = $a["missing"];
```

Result:
- returns `null`
- does not create the key

### 3.2 Typed value destinations reached from array reads

Examples:
```php
$x int = $a["k"];
takeInt($a["k"]);
function f(): int { return $a["k"]; }
```

Normative rule:
- array read semantics do **not** get a special typed-context rule
- the array read first yields its normal value result (`null` on miss)
- then the ordinary typed value-boundary rules from `specs/dynamic_types.md` apply

So in typed value contexts:
- the same missing-key read still yields `null`
- conversion or failure is then determined by the ordinary typed boundary rules
- by-reference boundaries remain excluded from this model

## 4. `isset($a[k])`

### 4.1 Missing key

```php
isset($a["missing"])
```

Result:
- `false`

### 4.2 Existing key with `null`

```php
$a["k"] = null;
isset($a["k"])
```

Result:
- `false`

### 4.3 Existing key with non-null value

Result:
- `true`

Normative summary:
- `isset($a[k])` is null-sensitive in the current intended subset
- it must not collapse into pure key-existence semantics
- the normative cross-runtime contract now lives in `specs/count_empty_isset_contract.md`

## 5. `empty($a[k])`

### 5.1 Missing key

```php
empty($a["missing"])
```

Result:
- throws `invalid_empty_operand` without creating an entry

### 5.2 Existing key

`empty($a[k])` follows the same top-level runtime contract documented in `specs/count_empty_isset_contract.md`.

In particular:
- only strings and collection-valued leaves qualify; zero elements means empty
- `"0"` is nonempty; null, numeric/bool scalars and wrappers do not qualify
- known unsupported leaf types reject during preparation; dynamic kind failures throw

## 6. Top-level keyed write

```php
$a["k"] = 1;
```

Result:
- creates the key if missing
- overwrites the key if already present

## 7. Nested write

### 7.1 Missing intermediate

```php
$a["x"]["y"] = 1;
```

If `x` is missing:
- autovivify `x` as a table/hash carrier
- then continue the write

### 7.2 Wrong intermediate kind

If `x` already exists but is not table/hash compatible:
- throw
- do not silently overwrite the existing scalar/object/value into a table/hash

This yields the current nested-write rule:
- missing intermediate â†’ autovivify
- wrong existing intermediate kind â†’ throw

## 8. Read-path side effects

Pure reads must not create storage.

Examples:
```php
$x = $a["k"];
return $a["k"];
takeValue($a["k"]);
```

Rule:
- no pure read path may materialize a missing key or nested structure

## 9. Append semantics

### 9.1 Supported target kinds

```php
$a[] = $v;
```

Append is supported on array/hash-compatible carriers.

### 9.2 Null-state `mixed` bootstrap

If the target is `mixed` and currently in the null state:
- append is allowed
- the target autovivifies into an array/table carrier

## 10. `fixed_array<T, N>` first-slice semantics

`fixed_array<T, N>` is the source-facing fixed-size sequential container.

Current rules:
- `N` must be a non-negative integer literal in the type expression
- array literals assigned or returned into `fixed_array<T, N>` are positional only
- literal element count must exactly match `N`
- indexed reads and writes are supported
- `foreach` iterates in index order with `int` keys
- `count(...)` returns `N`
- `empty(...)` is true only when `N` is zero
- append, unset, resize, keyed literals, and mixed/dynamic fixed-array conversion are not part of the first slice

### 9.3 Other unsupported append targets

If the target is not array/hash compatible and is not the null-state `mixed` bootstrap case:
- throw or reject according to the lowering/runtime boundary involved

## 10. `unset($a[k])`

### v0.2 keyed removal

Removal follows the same lazy root-to-leaf key evaluation and prefix re-resolution
as keyed probes, but an existing wrong-kind receiver/intermediate throws. Missing/null
parents (including absent dynamic handles) stop with no-op; later keys are skipped.
Invalid runtime key kinds throw. Key-expression exceptions propagate and their prior
side effects remain. Do not create entries, bootstrap tables, or prune empty parents.
Only a present final slot is removed, including a slot whose value is null.
No COW detachment is needed for a no-op; actual removal preserves mixed value semantics
and dynamic shared identity. Remaining associative keys retain their identities.

The first v0.2 slice admits one keyed target per statement rooted in an established
local/parameter. Hash, mixed-table and dynamic removal are supported. Vector-element
removal is deferred pending a decision on shifting versus preserving index identity;
vectors may still appear as existing intermediate containers. Properties,
intermediate append, native interior references and cross-family paths remain gated.
Whole-variable reset is separately governed by the
[v0.2 reset/alias contract](native_reference_safety.md#v02-whole-storage-reset-and-aliases--2026-10-06).
An established local alias may supply the root of keyed removal; this grants no
reference to a container slot and does not detach the alias.
Writes retain their existing evaluation/failure contract; this lazy rule does not
change assignment or append.

### 10.1 Missing key

```php
unset($a["missing"]);
```

Result:
- no-op

### 10.2 Nested unset with missing parent

```php
unset($a["x"]["y"]);
```

If `x` is missing:
- no-op

### 10.3 Existing key

If the key exists:
- remove that key
- preserve the visible identity of all remaining keys

## 11. Interaction with reference safety

This array/table subset does not widen the supported reference model.

Normative rules:
- array/property paths are still outside the supported native by-reference subset
- by-reference normalization from `mixed_t` to native `T&` remains unsupported
- generated code may use `.try_ref()` only as the restricted handle-copy escape hatch already documented elsewhere

Important distinction:
- shared pointee mutation visibility through `shared_p<T>` may still be observable
- PHP slot aliasing, slot rebinding, and slot write-back are **not** provided by that model

## 12. Supported vs unsupported summary

### Supported
- value read of existing key
- value read of missing key â†’ `null`
- top-level keyed write
- nested keyed write with autovivification of missing intermediates
- append on array/hash carriers
- append on `mixed(kind=null)` with autovivification
- `isset($a[k])` with null-sensitive semantics
- `empty($a[k])` for an existing collection/string leaf; missing, null and unsupported kinds throw under the v0.2 collection-only contract
- `unset($a[k])` as no-op on missing keys and removal on existing keys
- typed value destinations fed by array reads through the ordinary typed-boundary rules

### Outside the supported subset / rejected
- any array/property path as a direct native by-reference binding target
- any rule that would require exposing native interior references or pointers from dynamic storage
- nested write that silently overwrites a wrong-kind existing intermediate into a table/hash
- any special typed-context read rule that changes missing-key semantics only because the destination is typed

## 13. Wording convention

For this topic:
- model/spec boundary statements should use **outside the supported subset** when describing unsupported semantics
- generator/runtime failure behavior may use **rejected** or **throws** when describing the concrete implementation outcome
