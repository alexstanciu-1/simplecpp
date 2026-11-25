# Concrete mixed and dynamic publication

Doc Status: normative
Scope: v0.2 boxed runtime publication and the supported source bindings.

## Owners and demand

`runtime/metadata/runtime.json` registers the optional `boxed` family policy.
`tools/api_generator/php/boxed.php` prepares its demand and dependencies;
`php/render_boxed.php` and `templates/boxed/boxed_*.cpp.tpl` render the prepared family.
The sole production generator remains PHP. Storage semantics remain in
[private mixed](mixed_storage.md), [private dynamic](dynamic_storage.md) and
[packed storage](packed_storage.md), not in emission.

A publication requests:

```json
"boxed": {
  "header": "scpp/boxed.hpp",
  "families": ["mixed", "dynamic"],
  "scalar_conversions": true
}
```

Either family may be requested alone with `scalar_conversions: false`. Requesting
conversion requires both. Duplicate/unknown/empty requests, missing policy, header
or name collisions, and missing dependencies reject before any output is written.
The complete bounded API requires published int64, bool_t, float64 and string,
including explicit native long-long/double transport. The default publication selects both families independently. Boxed values are not admitted as typed vector/hash payloads. The subsequent
[boxed-result extension](boxed_results.md) admits result_mixed, result_dynamic and result_or_false_mixed;
other boxed carrier families remain deferred.

The probe measures the actual private mixed/dynamic layouts. Target facts carry an
optional `boxed` record; demands require it. Including the private layout header in
the preprocessed probe fingerprint makes layout changes invalidate cached facts.
No PHP host layout or hard-coded 24-byte capacity determines the public ABI.
Generated assertions check public/private size, alignment and kind discriminants.

The generated public header and include closure contain no templates, standard
containers or private storage headers. `mixed` and `dynamic` are distinct concrete
classes with aligned opaque inline storage; there is no extra wrapper allocation
per scalar. Private placement construction/destruction uses the existing
`ownership_access` lifetime helper. All native objects receive their actual special
member operations; live storage is never copied as raw bytes.

## API

Both classes expose the same bounded operations, preserving their family:

| API | Meaning |
| --- | --- |
| default/copy/move/destruction/assignment | Null default; cheap nonthrowing copies; moved-from null |
| `from_boolean(bool_t)`, `from_integer(int64)`, `from_floating(float64)` | Explicit inline scalar factories, nonthrowing |
| `from_string(const string&)` | Owned byte-preserving string factory |
| `container()` | Explicit empty container |
| `kind()` | `boxed_kind::{null,boolean,integer,floating,string,container}` |
| `get_boolean/get_integer/get_floating/get_string` | Exact checked extraction; string is an independent copy |
| `read(const int64&)`, `read(const string&)` | Copied same-family value; null on miss or null receiver |
| `has(key)` | bool_t membership, including present null payloads; not source `isset` |
| `set(key, same-family value)` | void insertion/replacement |
| `remove(key)` | bool_t indicating removal |
| `append(same-family value)` | Assigned int64 key, bounded by INT32_MAX |
| `container_size()` | int64 count; requires container kind |
| `is_packed_container()` | bool_t physical dense shape; requires container kind |
| `clean()` | Nonthrowing reset of this binding to null |

Key signatures take int64 by value for set, string by const reference; read/has/remove
use const references. Only declared runtime arguments are admitted. Native integers,
C strings, truth conversion, implicit boxing/extraction, cross-family assignment,
subscript, operators, traversal and mutable interior access are absent.

When requested, `mixed::from_dynamic_scalar(const dynamic&)` and
`dynamic::from_mixed_scalar(const mixed&)` preserve scalar/null kind and value.
Every container conversion rejects, including empty containers. Factories finish
before receiving-value replacement. Neither set nor append silently converts peers.

## Semantics and limits

Mixed copies detach on mutation; dynamic containers preserve shared identity,
including nested reads. Scalar copies have value semantics. Null mixed bootstraps
on set/append; null dynamic requires `container()`. `clean()` and whole-binding
replacement do not clear an aliased dynamic table. Missing probes/removals do not
intern strings or allocate tables. Non-container scalar indexed access throws.

This native API consumes classified keys: integers in 0…INT32_MAX and byte-exact
strings, including numeric spellings and embedded NUL. It does not implement source
numeric-string normalization. Source indexed operations use a separate adapter that shares canonical unsigned decimal
classification with JSON. Decimal spellings through UINT32_MAX normalize to integers;
integer keys above INT32_MAX then fail. Wider spellings, signed spellings, leading zeroes
(except "0") and embedded NUL remain byte strings. Each boxed keyed write validates its key and converts its RHS before the store.
The two arguments follow native C++ evaluation order. If either fails, the store is
skipped and effects already performed by either argument remain observable. Typed hashes continue to use their declared key domain.

The storage contracts own failure behavior. Invalid kinds throw runtime errors,
invalid integer keys throw out_of_range, append exhaustion throws overflow_error,
and allocation failure preserves logical contents. Shared dynamic aliases see the
same committed mutation or the same prior contents after failure. Private capacities
or valid interned keys may remain allocated. Strong cycles remain supported and
require explicit edge removal for reference-count reclamation (RT-DEBT-02).

## Linking and next scope

Compile generated `src/boxed.cpp` alongside that publication's `src/runtime.cpp`;
link `scpp_boxed_storage` (which links the packed-key pool). Private compile includes
are `runtime/src/boxed`, `runtime/src/ownership`, and `runtime/src/numeric`, plus
`runtime/src/sequence` for generated core string support. The example publication
and complete link setup are under `tests/boxed/`.

The default publication enables `boxed.scalar_dispatch`. It registers `mixed` and
`dynamic` through independent providers and adds checked scalar conversions, numeric
addition and ordering. Each family has its own public header and runtime bindings.
Generated dispatch selects the ordinary scalar rule for the observed kind. The application
links the prebuilt runtime. The boxed storage API above can also be published separately.
Typed local `[]`, single-level indexed reads and keyed writes are available in all three
flavors. Keys have static type int64 or string. A read returns a same-family value and
writes use ordinary same-family copies or scalar boxing. Append, boxed key expressions,
populated literals, nested writes and explicit family crossing remain deferred.
Native traversal follows the [common protocol](collection_traversal.md); the [compiler foreach slice](../compiler/docs/foreach.md) consumes it. See [validation](boxed_slice_validation.md).

## Source cardinality and keyed methods

All three flavors support `count(box)`, `empty(box)`, and receiver methods `has(key)`
and `remove(key)` for mixed/dynamic. PHP/C++ spell receiver access `->`; JS uses `.`.
Count returns int64 and requires a container, throwing `boxed_expected_container` otherwise.
Empty tests byte length for a string or entry count for a container. Other kinds throw
`invalid_empty_operand`. Has/remove return bool; null and missing keys return false, while other
scalar receivers throw `boxed_expected_container`. A stored null value is present.

Key arguments must have exact static int64 or string type. They use the same source
normalization and range checks as indexing, including numeric strings and embedded NUL.
Invalid keys fail before removal. Removal preserves mixed copy independence and affects
all aliases of a dynamic container; a missing removal does not create a container.
Expression receivers are supported without new ownership rules.

Metadata `boxed.source_collection` declares the count and empty bindings, source key domains and
normalized method bindings. Generated source_has/source_remove adapt keys before invoking
the existing classified-key API; the native has/remove contracts remain unchanged. The
compiler consumes generated signatures and existing cardinality/argument protocols.
`clear()` requires a container and leaves it empty. Mixed copies remain independent;
dynamic aliases observe the same cleared table. It differs from `clean()`, which resets
one binding to null. Clearing may release capacity. Active traversals remain memory-safe;
there is no snapshot or concurrent-mutation guarantee.

Source append uses the existing native next-key policy. Mixed null can become a container;
dynamic null rejects. Keys remain bounded by INT32_MAX; exhaustion throws before storing.

Instance fields admit mixed/dynamic and default to null. To initialize a container in a
constructor, create a typed empty local and assign it to the field. Direct contextual `[]`
assignment and bracket field defaults remain separate syntax work. Copies retain the usual
mixed value semantics and dynamic shared identity. Static boxed properties remain deferred.

Indexed compounds and discarded increment/decrement require an existing key. Missing keys
throw `boxed_missing_key`; a present null still fails scalar operator admission. The old
value is copied, the scalar operation is selected from existing metadata using its actual
kind, and write-back validates presence after RHS effects. No raw element reference escapes.
Arithmetic results retain the boxed family; ordering returns bool. String compounds use
PHP `.=` versus C++/JS `+=`; PHP numeric `+=` rejects string payloads. Scalar division,
shift and power guards remain in effect. Nested indexing remains deferred. These additions
introduce no public object layout or additional boxed kinds.

## Exact mixed type predicates

The general API and PHP flavor publish `is_bool(mixed): bool`,
`is_int(mixed): bool`, and `is_float(mixed): bool`. Each observes its single
argument once, by const reference, and tests its stored kind. False has boolean
kind, zero has integer kind, and an integral-valued float has floating kind.
Null, strings (including numeric strings), and containers return false for all
three. The operation only reads the tag, including for a temporary mixed value.

The argument must already have source type mixed. Passing a plain scalar,
dynamic or a nullable scalar is a type error. The returned bool is a snapshot
and introduces no flow-sensitive narrowing. Each predicate lowers to the same
native function for both its PHP and general spelling.
