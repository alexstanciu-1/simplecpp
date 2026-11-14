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
[boxed-result extension](boxed_results.md) admits result_mixed/result_dynamic only;
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
Public traversal remains RT-DEBT-01. See [validation](boxed_slice_validation.md).
