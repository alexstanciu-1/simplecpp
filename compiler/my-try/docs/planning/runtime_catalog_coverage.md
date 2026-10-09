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
| datetime | All 11 strict dt_* helpers; clocks, sleep, UTC/local formatting and parsing | Fixed scalar/void signatures, uint64 counters and result<int>; separately linked provider |
| filesystem | 19 strict fs_* helpers; inventory below | Shared scalar/container Result calls; providers bundled in snippet runtime |
| strings | 29 source helpers / 45 signatures, plus three padding constants; inventory below | Fixed/generic signatures, value sequencing, settled constants and existing PHP adapters for false-result contracts |

The host and native-driver startup lists select these five modules. Other consumers
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

## Datetime module inventory — 2026-10-09

The whole current strict surface in `modules/datetime/datetime.hpp` is registered
in `runtime/metadata/modules/datetime.json`:

| Names | Result | Status |
| --- | --- | --- |
| dt_now, dt_now_ms, dt_monotonic_ms | int | Registered, zero arguments |
| dt_monotonic_us, dt_monotonic_ns | uint64 | Registered, zero arguments; no narrowing to canonical int |
| dt_sleep_ms | void | Registered, int milliseconds; nonpositive values return immediately |
| dt_format_iso_utc | string | Registered, int Unix seconds |
| dt_parse_iso_utc | result<int> | Registered, exact UTC input string |
| dt_format | string | Registered, format string and int Unix seconds |
| dt_format_now | string | Registered, format string |
| dt_parse | result<int> | Registered, bounded local/common/ISO input string |

Legacy `time`, `date` and `strtotime` are not strict aliases and remain unregistered.
Timezone databases, calendar arithmetic and DateTime classes are outside the existing
runtime contract, not missing JSON rows. There is no existing public math module or
strict math-symbol inventory to migrate; a numeric-library extension would require
its own contracts rather than speculative abs/round/etc. registrations.

Datetime is not header-only: link `runtime/include/modules/datetime/datetime.cpp`
alongside the core runtime. Metadata dependency `core` orders catalog loading only.
The focused runner builds the provider once; the existing project build owns runtime
module linkage. My-try's bounded native cache still does not infer optional module
objects from catalog selection. That build-owner integration remains separate work.

Known review limits: direct uint64 binary comparisons remain outside current compiler
integer operations, so the fixture checks monotonic unsigned outputs in the host
harness without changing the return type. The existing runtime's local mktime path
treats timestamp -1 as failure, and host time_t range/DST normalization need review;
this registration does not redefine those edge semantics. See CH15-RUNTIME-DATETIME-01.

## Filesystem module inventory — 2026-10-09

Audit boundary: strict names from the quick-learn contract and symbol map, the
filesystem builtin specs, and `modules/filesystem/filesystem.hpp`. Legacy PHP
false-wrapper names are not aliases of the strict Result APIs.

| Names | Result | Status |
| --- | --- | --- |
| fs_is_file, fs_is_dir, fs_is_link, fs_exists | bool | Registered; is_link observes the link itself |
| fs_get, fs_realpath | result<string> | Registered; empty file is a successful empty string |
| fs_put, fs_size, fs_mtime | result<int> | Registered; put truncates/overwrites and returns bytes |
| fs_scan | result<vector<string>> | Registered through shared Result operations; sorted names without dot entries |
| fs_mkdir, fs_touch, fs_rmdir, fs_remove, fs_copy, fs_rename | bool | Registered; one directory, no recursive removal; copy overwrites |
| fs_dirname, fs_basename | string | Registered; existing filesystem lexical semantics, no extra PHP normalization |
| fs_read_snapshot | result<string> | Registered; checked Linux read, unsupported platforms return error |
| fs_lock_try, fs_lock_release, fs_lock_transfer | Deferred | Registered closeable file_lock handle/ownership prerequisite; no mixed surrogate |
| scpp_output_* | Intentionally excluded | Internal portability/output-owner protocol, not public source APIs |
| io_* stream/resource helpers | Separate owner/prerequisite | Registered resource handle and lifetime contracts required |

No schema, parser or emitter changes. Host/native startup select the
module; the focused runner links the snippet `core/runtime.cpp`, which already
bundles `filesystem.cpp` and `snapshot.cpp`. Do not also link standalone copies.
Automatic linkage for other optional modules remains build-owner work, not metadata loading.
The ordinary rmdir provider now uses a directory-only OS primitive, correcting its
previous ability to delete regular files without introducing a check/remove race.
Linux proofs cover file/symlink preservation; Windows `_wrmdir` is not tested here.
Snapshot guarantees remain those in the authoritative fs_read_snapshot spec, not
an atomic snapshot promise against same-size/same-mtime in-place edits.
The initial generated fs_scan proof exposed `Result_Conversions`' payload-family
restriction. The follow-up resolves it through shared exact vector/hash payload
admission and recursive copy-capability preparation. It preserves failure/empty
distinctions and rejects container Result conditions/Elvis, whose payloads have no
truthiness contract. No flattening of the Result or filesystem-specific preparation.

## Remaining work, grouped by prerequisite

| Group | Examples | Next prerequisite / check |
| --- | --- | --- |
| Boxed formatting | number_format(mixed/dynamic) | Deferred CH15-RUNTIME-FORMAT-01: explicit numeric-kind validation before registration |
| Contract-sensitive strings | strpos/strrpos, substr_replace, boxed implode | Resolve the specific differences in the module inventory above; hex2bin and code-point substr are now registered without semantic changes |
| Container-producing/consuming helpers | collection APIs | Audit by owning module; typed split/join now use existing application/generic contracts |
| Numeric and utility helpers | predicates, stable hashing; future scalar math | Audit real provider ownership; scalar math has no existing public module/strict registry to migrate |
| Registered runtime types | JSON document/nodes, filesystem handles, compiler Storage, builders | Type/ownership/member registration is not yet represented by this function/constant metadata schema |
| Defaults and richer signatures | optional/default parameters, variadics, reference outputs | Reuse existing callable models, but extend metadata decoding and selection under explicit contracts |
| Optional/linking modules | IO, process, regex, curl, tasks, DB/UI | Pair public contracts with runtime type dependencies and build/link integration; datetime and ordinary filesystem signatures registered, automatic module linkage still separate |
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

Datetime evidence: `runtime_datetime.py --results /tmp/runtime-datetime-module-20261009-3`
passed three PHP suites, six generated-program builds/runs and the existing native
datetime runtime regression. The separately linked provider compiled in 1.98s;
PHP suites took 0.36–0.80s each and generated/runtime tasks 3.50–8.50s each under
the shared pool. Local-format fixtures use explicit `TZ=UTC0`. No PCH, compiler
native rebuild, runtime behavior change, or new preparation/emission path.

Filesystem evidence: `runtime_filesystem.py --results /tmp/runtime-filesystem-module-20261009-6`
passed three PHP suites (0.46–0.86s), five generated-program builds/runs
(2.41–4.83s), and the existing Linux snapshot race/error regression (6.53s).
It covers all 18 registered bindings, source shadowing, selection/reset, operational
failure versus empty/zero success, and regular-file/directory-link preservation by
rmdir. Earlier iterations corrected fixture bool/string assertions, PHS path quoting
and duplicate provider linkage; the attempted fs_scan fixture exposed the recorded
shared Result prerequisite. No PCH or native compiler rebuild; Windows remains untested.

Container-Result follow-up: `/tmp/result-containers-20261009-final` passes six PHP
suites, 15 generated-program builds/runs and the Linux snapshot regression. It
enables fs_scan (including sorted/empty/failure outputs) and proves the reusable
vector/hash Result matrix across all three wrapper families. PHP suites took
0.51–2.06s; generated tasks took 2.70–12.71s under the shared pool. Copy-capability
edit/reject/repair agrees with a clean build. Supplementary container-call,
nullable-record and ownership PHP regressions pass in
`/tmp/result-container-copy-review-20261009`. No native compiler rebuild or PCH.
