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

All three flavors use the bounded `foreach (source as value)` and key/value forms;
PHP variable names keep their `$` prefix, while C++/JS use bare names. This is the
Simple C++ traversal syntax, not JavaScript for-of compatibility.

The bounded `foreach ($source as $value)` and `foreach ($source as $key => $value)`
forms accept registered vector, hash, mixed-table and dynamic-container sources.
Custom structs use the compiler-known `Iterator<T>` or `Iterator<T,K>`
[Iterator contract](inheritance_v0_2.md#compiler-known-traversal-capability).
Evaluate the source expression once before binding either variable; retain it for
the loop lifetime, including temporary function results. Vector keys are `int` and
values are T; hash<V,K> keys are K and values are V. Mixed iteration values are mixed;
dynamic iteration values are dynamic. Both retain the established int/string key
domain (represented by scalar mixed keys). This follows the approved
[separated-family contract](dynamic_types.md#v02-separated-mixeddynamic-families--2026-10-05),
whose traversal bindings use the same value-copy contract. Vectors visit index order, hashes/tables
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

### Mutation during value iteration

For v0.2 vector, hash, mixed tables and dynamic containers, traversal borrows the
live source wrapper and captures its initial physical extent. Each advance reloads
current storage, checks current bounds, skips deleted slots and copies the current
key/value before entering the body. It retains no element pointer across body code.
There is no collection snapshot or mutation-preservation subscription.

Mutation during traversal has no stable visitation guarantee: shifting, deletion,
clear, replacement and packed/associative transitions may change which entries are
visited. The captured physical limit bounds traversal; growth does not extend it.
Memory safety and normal runtime errors are required, not a preserved sequence.
Mixed keeps ordinary copy-on-write; dynamic keeps shared-table semantics. Traversal
must not itself introduce another table owner. Scalar/null replacement of a boxed
source throws on the next advance, including when its previous limit was exhausted.

The source expression is evaluated once. The caller keeps its wrapper and any
containing owner alive until traversal ends; retaining only a raw reference to a
field of an otherwise released owner is insufficient. Each nested traversal owns
its own integer position and limit. Copied elements keep their normal value/shared
semantics. Break, return and exceptions use ordinary scoped cleanup.

Concurrent mutation and by-reference foreach remain deferred. Native callers must
obey wrapper lifetime and distinct cursor/output requirements. The runtime primitives and bounded source foreach integration are implemented.
Custom traversal uses synchronous next calls without a captured extent;
see the Iterator contract above. Keyed loops require Iterator<T,K>; value-only loops
accept either contract. [Yield producers](generators_v0_2.md) reuse this protocol.
See [native traversal protocol](../v0.2/specs/collection_traversal.md).

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

### v0.2 object-identity hash keys — 2026-10-21

`hash<V, K>` admits a source class/interface `K` with its default shared storage.
Hashing and comparison use object identity, independent of fields. Distinct objects
with equal fields are distinct keys; aliases replace the same entry. Keys are
retained by the map. Membership copies have value semantics while key identities
remain shared. Key operations apply ordinary shared upcasts to the declared key
type before hashing/comparison; no container covariance is introduced.

Empty construction (`new hash<V, K>()` or typed `[]`), keyed initialization,
read/write/probe/removal and iteration share existing container contracts. Value
records, `value<K>`, weak and unique handles do not gain hash-key capabilities.
No object coercion into the mixed/dynamic scalar key domain is introduced.
The portable PHP carrier requires non-null object keys; see
[portable object hashes](portability/object_hashes.md) for its stricter aliasing and
iteration rules. This slice does not define new null-key portability.

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

The v0.2 key domain is ordinary `int` for vectors, the declared ordinary
`int`/`string` or shared source class/interface key for typed hashes, and
`int`/`string`/runtime-checked `mixed` for
mixed/dynamic. Mixed keys at typed boundaries use ordinary checked extraction.
No implicit bool/float/null key coercion is introduced. Static invalid types reject
during preparation; unknown invalid mixed key kinds throw at runtime, even when the
receiver is null. Existing numeric-string normalization applies to mixed/dynamic.

#### v0.2 compact runtime key limit (2026-10-29)

The rewritten compact mixed/dynamic table storage admits integer keys only in
`0…INT32_MAX` (0…2147483647). Negative or larger integer keys throw before narrowing,
including on probes/removal; they must not alias string identities or tombstones.
Append chooses one greater than the greatest live integer key, or zero when none
exists, and throws before mutation if that key would exceed `INT32_MAX`. This is a
key-domain limit, not a live-entry-count limit. Removing the maximum key permits
append to use the new live maximum. Typed hashes retain their declared key domain.

This approved limit governs the new v0.2 runtime foundation; it is not a claim that
all existing compiler/runtime consumers have migrated. Source numeric-string
normalization remains a separate adapter responsibility: when it yields an integer,
that integer is subject to this limit. The private storage accepts already classified
integer keys and byte-exact string keys and does not itself normalize strings.

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
append (`$rows[][0] = 1`) is deferred. Call-result roots and native element
references remain outside this bounded slice. Stable properties and indexed
compound updates are admitted by the subsequent amendments below.

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

### v0.2 inline storage budget

For byte arrays and rectangular arrays of byte arrays, the compiler accepts up to
65,536 bytes of declared fixed-array storage per function. Each local counts once,
including copies and declarations in mutually exclusive branches. Inline record locals
and by-value record parameters contribute their embedded array bytes, including
inherited and nested inline fields. A shared handle does not embed the pointee.
The budget applies to each call individually and measures array payload storage;
native stack bookkeeping, other fields and recursion have their own costs.

A fixed array is stored inline in its owner. An instance field of a record
constructed with new lives inside that record's heap allocation and does not
consume the function's frame budget. Each array shape and each record's combined
inline fixed-array payload are capped independently at 4,294,967,295 bytes.
Preparation checks each dimension before multiplying and checks record payloads
before adding embedded fields. Native storage uses one
physical slot for a zero dimension, charged to the budget. Its logical extent is
zero, so every index fails. Each signed index is checked once against the extent
of its own dimension before subscripting. A failing access raises
`sequence_index_out_of_range` through the runtime exception boundary.

### 9.3 Other unsupported append targets

If the target is not array/hash compatible and is not the null-state `mixed` bootstrap case:
- throw or reject according to the lowering/runtime boundary involved

## v0.2 typed collection presence methods

Typed vectors and hashes expose `has(key)`: PHP/C++ `->has(key)`, JS `.has(key)`.
It returns bool indicating presence, independent of the stored value's truthiness.
Vector indexes must be canonical int/int64; hash keys must exactly match their declared
type. String keys use all bytes, including embedded NUL. Negative or beyond-end vector
indexes and absent hash keys return false. A probe neither copies the element nor
mutates or materializes the collection's storage.

Expression receivers follow the same receiver-before-key and shared-allocation lifetime
rules as the removal methods below. An empty shared owner still throws before evaluating
the key. Key-expression effects are allowed and are not undone; the query itself is
read-only. This adds no `isset` syntax or value-search semantics.

### Free key-presence query

`hash_contains(collection, key)` returns bool for key existence on a published
runtime or application-defined hash. Keys must have the hash's exact canonical key
type. It does not coerce keys, read/copy the value, insert missing entries or require
a nullable value carrier. Present zero, empty text and empty shared-handle payloads
count as present. Source insertion/conversion rules remain unchanged.

The receiver is evaluated once before the key; an inaccessible/empty shared owner
fails before key evaluation. The key is evaluated once, and its effects or exceptions
are preserved. A selected shared allocation stays alive through the query even if
key evaluation replaces its original owner. The query itself is read-only.

This free spelling is available in cpp/php/js through the ordinary function fallback:
a declared source function takes precedence, and an explicit function import does not
fall back to this builtin when its target is missing. A constant alias does not shadow
the function namespace. Vectors and boxed collections are outside this free-helper
slice. `isset` remains null-sensitive and is not an alias for key existence.

### Copied optional hash lookup

`hash_find(collection, key)` is available for published runtime hashes whose API
already publishes a nullable result for their value type. It evaluates the receiver
and key once under the same exact-key and receiver-lifetime rules as `hash_contains`.
It performs one lookup, returning an empty nullable on absence or an owned payload
copy on presence. Missing lookup does not insert or throw; key evaluation, allocation
and payload-copy failures still propagate. Present zero and empty text remain present.

The returned copy survives replacement, removal or destruction of the hash. Existing
nullable presence/extraction rules apply; this helper neither unwraps the result nor
weakens empty-extraction checks. Indexed `collection[key]` continues to throw on a miss.
Function declaration/import precedence is the same as for `hash_contains`.

The first slice covers the four published int/string key/value combinations. There is
no implicit application nullable-carrier generation: application structs/classes,
boxed values, nested collections and nullable-valued hashes remain outside this helper's
current admission. Application lookup must separately preserve absence versus a present
empty shared handle; it must not be approximated with an empty handle alone.

### Shared value or null

In cpp/php/js, `hash_get_or_null(collection, key)` accepts a typed application hash
whose value type is a required shared class handle. The result has the optional
handle type of the same payload. A hit copies the shared handle and preserves
allocation identity and lifetime independently of the hash. A miss returns an
empty handle without insertion. A present empty handle also returns empty.
Use `hash_contains` when key presence itself matters.

The exact key, single evaluation, receiver lifetime and function resolution rules
of `hash_contains` apply. The query performs one storage lookup on an allocated
table. Indexed reads and the presence-preserving `hash_find` contract are unchanged.

## v0.2 typed collection removal methods

Typed vectors and hashes expose `remove(key)` as a method: PHP/C++ `->remove(key)`
and JS `.remove(key)`. The result is bool: true only when an element was removed.
Vector indexes must be canonical int/int64; hash keys must exactly match the declared
key type, including byte-exact string keys. There are no new implicit key conversions.

Vector removal shifts later elements left, preserves their order and retains capacity.
Negative indexes, indexes at or beyond size, and removal from an empty vector return
false without mutation. Hash removal preserves all remaining key identities; an absent
key returns false. Empty receivers are not materialized by unsuccessful removal.
Copies remain independent, and normal destruction releases the removed element.

Receivers support the same expression paths as `clear()`. Resolve the receiver once
before the key argument; keep the selected shared allocation alive through that argument
and the removal. Empty shared owners fail before the key runs. Argument failures prevent
removal, without undoing earlier argument effects. This does not grant property references
or change the separate `unset(...)` syntax and its historical vector restriction.

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

The bounded v0.2 slice admits one keyed target per statement rooted in an established
local/parameter or writable instance field. Field-root binding uses the same shared
owner retention and inline borrowing as writes, before lazy key evaluation. Replacing
the source handle cannot redirect removal; changing the selected field is observed
by prefix re-resolution. Visibility and mutable-content access rules apply. An absent
object owner throws during field access, before keys, rather than acting as a missing
container parent. Hash, mixed-table and dynamic removal are supported. Vector-element
removal is deferred pending a decision on shifting versus preserving index identity;
vectors may still appear as existing intermediate containers. Temporary/indexed-object
field roots, additional ownership-wrapper roots, intermediate append, native interior
references and cross-family paths remain gated.
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

## v0.2 indexed compound updates

Indexed compound assignment uses existing elements only, including nested paths
and stable container fields. Bind the root and retain field owners, then evaluate,
convert and validate keys once, root-to-leaf. Read a value snapshot of the existing
leaf before evaluating the RHS once. Compute and convert using the ordinary
compound contract, then re-resolve the complete saved path and store only if every
segment still exists and has a compatible container kind.

Missing entries fail before the RHS. RHS deletion or wrong-kind replacement fails
at write-back without recreation; RHS effects remain. A replacement leaving a valid
path receives the result computed from the old leaf. Vector keys denote positions,
not stable element identities. No interior element reference survives RHS execution.
Validation precedes mutable descent/COW; allocation failures are not transactional.

This shared protocol applies to vector/hash and mixed/dynamic tables. Numeric boxed
updates preserve the old leaf's stored-kind checks. Compound append, value-producing indexed
increment/decrement, fixed-width compound arithmetic and native element aliases
remain deferred. Ordinary assignment's creation policy is unchanged.

### Discarded indexed increment/decrement — 2026-11-14

For currently supported typed vector/hash elements whose scalar type admits numeric
updates, standalone prefix/postfix `++` and `--` evaluate the receiver and key once,
retain any containing shared owner, read the existing element, apply the ordinary scalar
update rule and store the updated value. Missing keys, invalid indexes and empty owners
throw without insertion. No interior element reference escapes. Key-expression effects
precede the element read, and remain if a later check fails. Prefix/postfix results are
discarded; value-producing updates and indexed updates in for headers remain deferred.
This does not activate additional collection specializations or scalar types.

## 13. Wording convention

For this topic:
- model/spec boundary statements should use **outside the supported subset** when describing unsupported semantics
- generator/runtime failure behavior may use **rejected** or **throws** when describing the concrete implementation outcome

## v0.2 shared-owner hash keys

Typed `hash<Value,Key>` additionally admits supported shared-owner key types in all
flavors. Hash-key eligibility requires the storage contract plus nonmutating,
nonthrowing hashing/equality, with equal keys producing equal hashes and stable key
identity while stored. This capability is separate from arithmetic or general equality
operator admission. Existing int/string key behavior remains unchanged.

Shared keys compare allocation identity, consistent with owner `==`/`===`. Base/interface
views hash the same control identity, not an adjusted payload/view address. Mutating
object fields does not change its key. Expressions must have the exact declared key type;
existing conversions can produce that type before lookup/insertion. Nullable owners admit
one empty-owner key. This does not authorize an empty required class value.

A hash owns strong copies of its keys. Table copies retain the same object identities;
removal, clear and destruction release the stored owners. Keys keep their allocations
alive, including while external owners are cleared. This does not provide cycle collection
or thread-safe reference counts. Value-copy key traversal retains its selected key through
the loop body under the existing traversal rules.

Weak observers, value-record keys and source-defined hashing methods remain outside this
slice. Hash values remain independently governed by their supported storage contracts.
The implementation and verification are recorded in
[the common key contract](../v0.2/compiler/docs/hash_keys.md).

### v0.2 typed populated vector initialization

An explicitly typed local vector initializer may contain ordered positional elements
in `[...]`, with an optional trailing comma. Its element type provides the ordinary
typed-destination conversion context. Direct nested literals receive that context
recursively. Each element expression and its destination conversion completes before the next
element is evaluated. Evaluation or conversion failure prevents later elements.
Partial inputs and destination storage are destroyed on failure. Internal allocation
and transfer timing is not specified: implementations may build private typed input
then bulk-construct, or incrementally populate the destination. Allocation failure
may therefore interrupt at different points; allocation counts, growth and individual
append steps are not source-observable guarantees.
Previously performed external effects remain. The local is not visible until its
initializer succeeds. Ordinary value-copy and shared-identity contracts apply.

This bounded compiler slice admits existing vector specializations through the
metadata-defined sequence-initialization capability. It does not activate additional
element families, infer a type from a populated literal, admit keyed entries or
extend populated initialization to hash/mixed/dynamic. Destination propagation for
calls, returns, assignments and append remains a separate implementation slice.
