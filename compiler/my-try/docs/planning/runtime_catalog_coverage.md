# Runtime catalog coverage and next groups
Doc Status: planning

## Direction

Migrate the runtime's source-facing symbols by owning module, not by adding special
cases to expression preparation. Runtime metadata is loaded once into typed startup
records, then installed against each semantic registry. The
[catalog specification](../../../../specs/runtime_module_metadata.md) owns the format.
This is an implementation roadmap, not a new language contract or an exhaustive
inventory of every internal C++ helper.

## Current coverage — 2026-10-09

| Module | Registered source surface | Shared path |
| --- | --- | --- |
| core | Byte `strlen`; `count` for vector/hash/mixed/dynamic | Concrete and inferred generic contracts; non-retaining unary observation |
| json | `json_decode`, `json_decode_mixed`, both `json_encode` families | Concrete overloads and Result returns |
| strings | `strtolower`, `strtoupper`, `lcfirst`, `ucfirst`, `str_contains`, `str_starts_with`, `str_ends_with` | Existing fixed signatures and value-argument sequencing |

The host and native-driver startup lists select these three modules. Other consumers
can select a subset; `strings` depends on `core`, and repeated selections deduplicate.
String registration adds metadata and tests, not parsing/preparation/backend handlers.

## Remaining work, grouped by prerequisite

| Group | Examples | Next prerequisite / check |
| --- | --- | --- |
| Further concrete string overloads | trim/ltrim/rtrim, bin2hex, replace | Verify normative strict signatures and runtime bindings; documented finite arities can use ordinary overload rows |
| Contract-sensitive strings | strpos/strrpos, substr family, hex2bin | Reconcile older byte/falseable docs with strict runtime code-point/nullable/Result contracts before enabling; do not silently generalize the strlen decision |
| Container-producing/consuming helpers | explode, implode, collection APIs | Audit exact element/key contracts and any mutating/reference requirements; generic registration itself is available |
| Numeric and utility helpers | scalar math, formatting, predicates | Inventory authoritative signatures, supported widths and overload ambiguity; not every helper is a free function |
| Registered runtime types | JSON document/nodes, filesystem handles, compiler Storage, builders | Type/ownership/member registration is not yet represented by this callable-only metadata schema |
| Defaults and richer signatures | optional/default parameters, variadics, reference outputs | Reuse existing callable models, but extend metadata decoding and selection under explicit contracts |
| Optional/linking modules | filesystem/IO, datetime, process, regex, curl, tasks, DB/UI | Pair validated public contracts with runtime type dependencies, module selection and build/link integration; metadata dependencies currently order loading only |
| Non-type template arguments | fixed_array<T,N> | Extend the type-argument model deliberately; do not enumerate element types or sizes |
| Profile availability | strict versus legacy PHP surfaces | Add explicit availability rules; old symbol maps are not permission to expose legacy-only contracts |

Reference sources: top-level `specs/builtins/`, strict quick-learn signatures,
`generators/php/specs/php_runtime_symbols_strict.json`, its legacy counterpart,
and runtime module headers. Specs own meaning; old symbol maps/headers are evidence.
The old `php_builtins.json` manifest identifies itself as a planning scaffold and
must not be treated as a complete ready-to-import catalog.

## Proof boundary

`tests/runtime_strings.py` checks exact generated-program output for ASCII transforms,
unchanged UTF-8 bytes, empty strings, byte predicates, source shadowing, input purity,
and ordered argument snapshots when a later argument changes the original string.
PHP checks also cover selection, dependency deduplication and rejected calls, with
catalog/generic regressions. No native compiler rebuild is implied.
