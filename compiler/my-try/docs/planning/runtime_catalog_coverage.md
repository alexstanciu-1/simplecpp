# Runtime catalog coverage and next groups
Doc Status: planning

## Direction

Migrate the runtime's source-facing symbols by owning module, not by adding special
cases to expression preparation. Runtime metadata is loaded once into typed startup
records, then installed against each semantic registry. The
[catalog specification](../../../../specs/runtime_module_metadata.md) owns the format.
This is an implementation roadmap, not a new language contract or an exhaustive
inventory of every internal C++ helper.

Agreed 2026-10-09: use one runtime module as the normal implementation/review unit.
Inventory its public contracts first, register all settled signatures together, repair
small local defects, and validate a module batch. Executable JSON contains supported
contracts only. Report semantic conflicts and missing model prerequisites together;
do not request approval for each ordinary metadata row or populate speculative rows.

## Current coverage — 2026-10-09

| Module | Registered source surface | Shared path |
| --- | --- | --- |
| core | Byte `strlen`; `count` for vector/hash/mixed/dynamic | Concrete and inferred generic contracts; non-retaining unary observation |
| json | `json_decode`, `json_decode_mixed`, both `json_encode` families | Concrete overloads and Result returns |
| strings | 29 source helpers / 45 signatures, plus three padding constants; inventory below | Fixed/generic signatures, value sequencing, settled constants and existing PHP adapters for false-result contracts |

The host and native-driver startup lists select these three modules. Other consumers
can select a subset; `strings` depends on `core`, and repeated selections deduplicate.
String registration adds metadata and tests, not parsing/preparation/backend handlers.

## Strings module inventory — 2026-10-09

Audit boundary: source-facing functions/types in `modules/strings/strings.hpp`, their
PHP adapters and strict symbol-map spellings. Top-level builtin specs own semantics;
`runtime/specs/catalog.md` supplies the explicit-unit helper contracts. Native private
mechanics such as `make_pad_bytes` are not source APIs. `hash_string` and
`stable_hash_string_u64` happen to share the PHP adapter header but belong to the
separate stable-hash owner and remain for that owner's module audit.

| Public surface | Status | Contract / remaining prerequisite |
| --- | --- | --- |
| strtolower, strtoupper, lcfirst, ucfirst | Registered | ASCII case operations |
| str_contains, str_starts_with, str_ends_with | Registered | Byte predicates, empty needles supported |
| trim, ltrim, rtrim | Registered, 1/2 args | Byte-set masks |
| bin2hex, str_replace | Registered | Binary-safe hex; three-string replacement only |
| str_pad, STR_PAD_LEFT/RIGHT/BOTH | Registered, 2/3/4 args | Byte padding, checked pad string/mode |
| number_format | Registered int/float, 1/2/4 args | Boxed variants deferred under CH15-RUNTIME-FORMAT-01; bool not admitted |
| explode | Registered, 2/3 args | vector<string>, byte split, all signed limits; signed-minimum overflow repaired |
| implode | Registered vector<string> and hash<string,K> | Generic K; stored iteration order; boxed element coercion unresolved |
| substr | Registered, 2/3 args | Existing code-point contract, **not** changed to bytes by the strlen decision |
| substr_compare | Registered, 3/4/5 args | Byte windows and ASCII-insensitive comparison |
| hex2bin | Registered | Existing PHP adapter returns result_or_false<string>, not internal result<string> |
| string_byte_len, string_byte_at, string_byte_find, string_byte_slice, string_byte_slice_equals | Registered; find has 2/3 args | Explicit byte coordinates; find preserves result_or_false<int> through the PHP adapter |
| string_utf8_codepoint_count, string_utf8_codepoint_at, string_utf8_slice_codepoints | Registered | Explicit code-point units; not portability-framework validation wrappers |
| string_grapheme_count, string_grapheme_slice | Registered | Existing conservative cluster policy, not a new full Unicode segmentation promise |
| strpos, strrpos | Held for semantic reconciliation | Top-level specs require byte positions; runtime find/rfind and subordinate catalog use code points, even through PHP adapters |
| substr_replace | Held for semantic reconciliation | Spec says omitted length inserts and unusable negative window is a no-op; runtime replaces the suffix and inserts for some unusable windows |
| implode(mixed/dynamic) | Held for contract review | Documented string-only elements versus adapter cast/coercion; dynamic admission not established |
| strlen(nullable<string>) / runtime length overload | Not registered | Core's bounded v0.2 contract is string byte length; no implicit nullable overload |
| string_parts_builder (10 free helpers), text_builder (11 free helpers) | Blocked on registered runtime types | Need truthful value/ownership/member contracts; text byte-span append additionally needs source::byte_span |

This completes the **ready-contract module pass**, not every runtime capability.
No source aliases for internal `find`, `rfind`, `split`, `join`, `hex_decode` or
`length` are invented. Existing profile adapters remain the owners where public
wrapper shape differs from the underlying shared helper.

## Remaining work, grouped by prerequisite

| Group | Examples | Next prerequisite / check |
| --- | --- | --- |
| Boxed formatting | number_format(mixed/dynamic) | Deferred CH15-RUNTIME-FORMAT-01: explicit numeric-kind validation before registration |
| Contract-sensitive strings | strpos/strrpos, substr_replace, boxed implode | Resolve the specific differences in the module inventory above; hex2bin and code-point substr are now registered without semantic changes |
| Container-producing/consuming helpers | collection APIs | Audit by owning module; typed split/join now use existing application/generic contracts |
| Numeric and utility helpers | scalar math, formatting, predicates | Inventory authoritative signatures, supported widths and overload ambiguity; not every helper is a free function |
| Registered runtime types | JSON document/nodes, filesystem handles, compiler Storage, builders | Type/ownership/member registration is not yet represented by this function/constant metadata schema |
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

The same runner covers both trim arities, literal byte-set masks (including no
range expansion), empty/all-trimmed inputs, unchanged non-ASCII whitespace, embedded
NUL/high bytes for hex encoding and replacement, and argument snapshots when a mask
producer replaces the original string. These are metadata additions only; no parser,
preparation, backend or runtime implementation changed for this group.

Padding/formatting adds a reusable optional module-constant schema and separate
type-lifetime constant inventory. Tests cover full signed-64 boundaries, schema/range
failures, collisions, reset identities, source shadowing/constant aliases/labels, padding byte behavior
and errors, and all six numeric formatting overloads. No parser, expression preparation,
C++ emitter or runtime changes were needed. Boxed formatting remains explicitly deferred.

2026-10-09 evidence: `runtime_strings.py --results /tmp/runtime-pad-format-20261009-3`
passed four PHP suites and 19 generated-program builds/runs, without PCH or a native
compiler rebuild. A final added cross-module collision assertion passed through the
shared runner in `/tmp/runtime-pad-format-20261009-final-catalog` (0.39s PHP run).
The runner used CLI fallback because sandbox socket creation prevented FPM startup.

Module-pass evidence: `runtime_strings.py --results /tmp/runtime-strings-module-20261009-1`
passed four PHP suites, 30 generated-program builds/runs, and a direct UBSan split-limit
proof. The same runner covers typed hash/vector joins, positive/zero/negative/minimum
split limits, wrapper success at zero/empty payload versus failure, binary values,
code-point slicing, explicit byte/Unicode helpers, runtime range errors and rejected
types/arities. No PCH or native compiler rebuild. Metadata/schema/model changes were
unnecessary; the runtime repair is the overflow-safe negative-limit magnitude.
The final PHP check in `/tmp/runtime-strings-module-20261009-final` also proves the
held search/replacement and builder names remain unavailable (1.18s).
