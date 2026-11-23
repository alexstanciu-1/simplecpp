# Typed keyed local initialization

Doc Status: supporting

All three flavors accept bracket-keyed input for explicitly typed locals of the four
published `hash<Value,Key>` combinations using integer/string keys and values:

```php
$codes hash<int,string> = ["identifier" => 1, "integer" => 2,];
```

This implements the bounded [data initialization contract](../../../specs/data_initialization.md).
It does not introduce PHP array key coercions. Key admission/equality comes from the
existing strict hash contract; values use ordinary typed-destination conversions.
Existing empty `[]` construction is unchanged.

Each key expression and its conversion completes before its value, and each value
completes before the next entry. Evaluation/conversion failure suppresses the remaining
input. Private typed input retains duplicates; the factory checks equality only after
materialization. Consequently duplicate detection does not suppress later input effects.
Equal keys throw `duplicate_initializer_key`; hash collisions alone are valid. Ordinary
`set` still replaces values. Internal allocation timing is unspecified.

Concrete application hashes also accept this notation. A registry can construct
its table once and share its existing descriptors across lookups:

```php
$table hash<Binding,int> = [1 => $fixed, 2 => $overloads];
return $table;
```

Each entry uses the hash's existing key checks and value conversion. A derived
descriptor can enter through the ordinary shared upcast. Inline records are
copied into the input, including records with a required constructor. Application
entry sizes depend on source layouts, so these literals use guarded heap input
even for small tables. The destination still presizes from the full entry count.
The generated capability and concrete factory follow the published initialization
names and consume/read-only selection. Removing the capability disables literals.

## Owners and cost

A dedicated keyed expression owns ordered entry nodes, each with key/value expressions
and one approved value conversion. Strict keys reuse their existing identity admission;
there is no redundant key-conversion table. Positional AST nodes gain no fields. Common
collection/reset owners visit both children; there are no extra preparation passes,
normalized-key inventories or compile-time duplicate maps.

Generated `keyed_initializer_type` queries expose the existing key contract, value type
and one immutable `initialization_transport`. This transport is also used by positional
bulk construction. Its native input type, factory, heap guard and inline count come from
metadata. The handwritten compiler has no hash/scalar-name admission branches. Provider
contracts remain fixed per compiler session; regeneration requires a fresh session.

Native aggregate input evaluates key then value in source order. It copies source
lvalues into private storage and consumes only that storage. Metadata publishes
`from_entries` (read-only) and optional `from_owned_entries` (consuming), with concrete
`keyed_entry` and `keyed_input` types. They introduce no public C++ container templates.
The default 64-entry inline limit is checked against a 4096-byte budget by native size
assertions including both key and value. Larger input uses a typed heap array and the
same concrete guard pattern as vectors. Native array construction unwinds partial input.

The runtime factory checks size bounds, reserves entries/live-slot bookkeeping and
prepares an index sized for the full input at a conservative load factor. Index width
also accounts for the physical entry count. Filling does not grow or rehash. This does
not promise one allocation or exact public capacity. Negative counts, counts above
`UINT32_MAX / 2`, and entry-byte overflow are rejected before destination allocation.
Empty input needs no backing. Construction checks absence then uses the common
known-new insertion worker; normal `set` checks replacement before using that worker.
No runtime hash algorithm is duplicated in the emitter.

Failures destroy partial destination/input. Read-only factories preserve input entries;
consuming factories leave moved entries destructible. Returned hashes own independent
values; no input pointer escapes. Source user factories remain read-only and deferred.

## Verification

- `keyed_initialization.php`: 144 fixtures across cpp/php/js, including application-value copies,
  duplicate application keys with effectful inputs, the four published runtime types,
  NUL/heap strings, boundaries, duplicate literals/computed keys/constants, ordered
  failures, source-copy independence, rejected/deferred syntax and source incremental
  changes/deletion/retry/recovery against fresh output. Preparation visits each
  expression once; emission is pure.
- `keyed_initialization_native.py`: runs those fixtures on Clang or GCC.
- `keyed_initialization_ownership.py`: actual emitted inputs plus actual runtime storage;
  no-growth allocation counts, index-width/capacity boundaries, collision equality,
  empty/overflow behavior, copy/consume cleanup and allocation-failure sweeps. Sanitizers
  instrument the actual runtime and generated factory implementation.
- `keyed_initialization_incremental.py`: retained PHP sessions and real Clang/GCC Ninja
  depfiles; no-op builds, caller/unrelated object reuse on body edits, invalid key
  signatures, declaration deletion/restoration and factory retirement/restoration.
- `bootstrap_runtime_lookup_native.py` verifies descriptor identity, registry misses, caller reuse
  after key edits, invalid key types, deletion and restoration against fresh builds.
- Generator `keyed_initialization.py`: fresh-session read-only/consume selection, renamed
  guard/entry/factory, forced heap input, relocated headers, reduced publication and
  capability removal/restoration. Invalid metadata preserves the last complete export.

Native and sanitizer proofs pass on dev002 with Clang 18.1.3 and GCC 13.3. See the
[focused timing report](../../specs/planning/benchmarks/keyed_initialization/report.md)
and [planning checklist](../../specs/planning/collection_initialization.md).

PHP and JS also support boxed keyed literals and recursively constructed values.
Both families use ordered input and reject duplicate normalized keys. See the
[literal family rules](../../../specs/data_initialization.md#2-construction-type-must-be-known).

Deferred: constants/static tables, additional key and value families,
integer-hash positional input, user factories, general moves/borrows and hot reload.
Existing nested vector and append-based application-vector support is preserved.
