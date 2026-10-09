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
| core | Byte `strlen`; `count` for vector/hash/mixed/dynamic; process memory usage/peak; cli_argc/cli_argv/cli_args; getenv/shell_exec | Concrete and inferred generic contracts; non-retaining unary observation |
| json | `json_decode`, `json_decode_mixed`, both `json_encode` families | Concrete overloads and Result returns |
| datetime | All 11 strict dt_* helpers; clocks, sleep, UTC/local formatting and parsing | Fixed scalar/void signatures, uint64 counters and result<int>; separately linked provider |
| filesystem | 19 strict fs_* helpers; inventory below | Shared scalar/container Result calls; providers bundled in snippet runtime |
| regex (opt-in) | 10 helpers / 18 signatures and two split constants; inventory below | Nested container Results, ordinary value calls; explicit PCRE2 provider linkage |
| strings | 29 source helpers / 45 signatures, plus three padding constants; inventory below | Fixed/generic signatures, value sequencing, settled constants and existing PHP adapters for false-result contracts |

The host and native-driver startup lists select the five non-regex modules. Other consumers
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

## Regex module inventory — 2026-10-09

The opt-in `regex.json` depends on core, but is deliberately absent from the host
and native-driver default selections. Composition roots select it with
`Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($directory,
['regex']))` before compilation. Existing programmatic selection is sufficient;
this pass adds neither a CLI module option nor automatic native-cache linkage.
Consumers link `modules/regex/regex.cpp` and PCRE2-8 explicitly. The host needs the
PCRE2 development package; the focused runner queries pkg-config rather than
installing anything or silently skipping execution.

| Public surface | Status | Contract / remaining prerequisite |
| --- | --- | --- |
| regex_jit_available | Registered | bool; JIT is opportunistic, not required for matching |
| regex_quote | Registered, 1/2 args | Escapes metacharacters and optional delimiter |
| regex_match, regex_match_named | Registered, 2/3 args | Packed or named captures; optional byte offset |
| regex_match_all, regex_match_all_pattern_order, regex_match_all_named | Registered, 2/3 args | Match-order, pattern-order or named nested containers |
| regex_grep | Registered | Typed vector<string> input/output, input-order filtering |
| regex_replace | Registered, 3 args only | Positional backreferences, unlimited replacements |
| regex_split | Registered, 2/3/4 args | Optional limit/flags; PREG_SPLIT_NO_EMPTY=1 and PREG_SPLIT_DELIM_CAPTURE=2 |
| regex_replace with limit; replacement/filter count outputs | Held | C++ const-limit/nonconst-count overloads collide for emitted value snapshots; need unambiguous provider bindings and shared reference-overload admission |
| regex_filter | Held for semantic reconciliation | Its builtin spec says literal replacement; runtime replace_impl expands backreferences; empty inputs also bypass pattern/limit validation |
| regex_replace_callback, regex_replace_callback_array | Held | Metadata cannot yet describe explicit callable signatures; no string or mixed substitute |
| preg_* aliases, unsupported output-shape flags | Excluded | Legacy aliases are not strict APIs; runtime retains errors for unsupported split flags |

Empty successful captures are not failure. `take`/`isset` observe Result state;
container Result conditions and automatic wrapper `empty` remain rejected. Byte
offsets and `/u` keep existing runtime semantics; registration does not promise new
Unicode zero-length matching behavior. Scalar and nested container payloads use the
same registry-driven Result transport/copy rules; no regex-specific type admission.
See [CH15-RUNTIME-REGEX-01](../catalog/15_blocking_debt.md#ch15-runtime-regex-01--regex-contracts-and-binding-prerequisites).

## Remaining work, grouped by prerequisite

CLI prerequisite completed (2026-10-09): executable assembly emits
`main(int argc, char** argv)` and initializes the PHP runtime CLI context once,
before authored entry statements. The include-only `lang/php/cli_context.hpp`
separates this small ABI state from boxed/process helpers; no retained fragments
are mutated by assembly. `cli_argc`, `cli_argv` and `cli_args` are ordinary core
metadata registrations. No call-name dispatch, implicit source globals, or type
declarations were added. Managed child-process handles remain gated on runtime-type
metadata. The subsequent environment/shell pass registers `getenv` and `shell_exec`
as ordinary core functions returning `result_or_false<string>`. Missing environment
variables differ from present empty values; shell pipe-open failure differs from
empty output or a nonzero child exit. Shell reads now use byte-counted fread rather
than NUL-terminated fgets appends, preserving binary output. No compiler processing
or type registration changed. Thread-safe environment mutation, bounded capture,
stream-read failures and close-status policy are not added by this registration.

Core-utility audit (2026-10-09): `memory_get_usage` and `memory_get_peak_usage`
are registered together, each with zero arguments or one bool `real_usage` argument.
They report process resident/peak bytes, not PHP allocator internals; both bool
values select the same measurement policy. Unsupported measurements may return zero.
The narrow existing provider header owns behavior; no new runtime implementation,
type registration or compiler dispatch is needed.

Stable string hashing is **held**, not registered: its implementation mixes
`std::hash<std::string>` despite a cross-process/standard-library stability promise.
Algorithm changes require persisted-key migration review; see
[CH15-RUNTIME-HASH-01](../catalog/15_blocking_debt.md#ch15-runtime-hash-01--stable-hashing-implementation-and-persisted-key-compatibility).
Byte-span hashing also needs the source type model. Type predicates require a
separate family-consistency audit: the existing PHP provider recognizes mixed numeric
kinds but its generic fallback does not establish equivalent dynamic-kind behavior.
Do not fill this gap by enumerating concrete scalar/container overloads in metadata.

| Group | Examples | Next prerequisite / check |
| --- | --- | --- |
| Boxed formatting | number_format(mixed/dynamic) | Deferred CH15-RUNTIME-FORMAT-01: explicit numeric-kind validation before registration |
| Contract-sensitive strings | strpos/strrpos, substr_replace, boxed implode | Resolve the specific differences in the module inventory above; hex2bin and code-point substr are now registered without semantic changes |
| Container-producing/consuming helpers | collection APIs | Audit by owning module; typed split/join now use existing application/generic contracts |
| Numeric and utility helpers | predicates, stable hashing; future scalar math | Audit real provider ownership; scalar math has no existing public module/strict registry to migrate |
| Registered runtime types | JSON document/nodes, filesystem handles, compiler Storage, builders | Ordinary nominal/template declarations and capabilities now supported; audit actual APIs next; members/factories, specialized storage and handle lifetimes remain separate prerequisites |
| Defaults and richer signatures | optional/default parameters, variadics, reference outputs | Reuse existing callable models, but extend metadata decoding and selection under explicit contracts |
| Optional/linking modules | IO, process, regex, curl, tasks, DB/UI | Pair public contracts with runtime type dependencies and build/link integration; datetime/filesystem and ready regex signatures registered, automatic module linkage still separate |
| Non-type template arguments | fixed_array<T,N> | Extend the type-argument model deliberately; do not enumerate element types or sizes |
| Profile availability | strict versus legacy PHP surfaces | Add explicit availability rules; old symbol maps are not permission to expose legacy-only contracts |

Reference sources: top-level `specs/builtins/`, strict quick-learn signatures,
`generators/php/specs/php_runtime_symbols_strict.json`, its legacy counterpart,
and runtime module headers. Specs own meaning; old symbol maps/headers are evidence.
The old `php_builtins.json` manifest identifies itself as a planning scaffold and
must not be treated as a complete ready-to-import catalog.

## Proof boundary

Runtime-type foundation: `types`/`cpp_types` records decode directly into typed startup
data. One transient registration worker installs ordinary nominal/template definitions
and closed forward defaults before signatures. Vector/hash declarations and bindings
moved out of PHP; source publication now enumerates the registry's exposed names.
No parser, preparation or expression-emission family branches were added. The
metadata-only receipt/parcel fixtures cover nominal and nested generic calls, defaults,
Result transport, conditional copy capabilities, reset/recovery and operation opt-in
boundaries. Specialized semantic roles remain compiler-owned; the CH15 metadata audit
stays open for remaining owner/protocol registrations.

Evidence: `/tmp/my-try-runtime-types-20261009-validated` passes 12 focused PHP suites
(0.50–1.73s each) and five generated C++ compile/run tasks (3.94–5.01s each).
FPM was unavailable under the socket sandbox, so the shared runner used its CLI
fallback. No PCH, full regression sweep or native compiler rebuild was performed.

Environment/shell evidence: `/tmp/runtime-environment-20261009-1` passes two PHP
suites (0.71–0.72s) and five C++ build/run tasks (2.47–6.84s). Fixed commands prove
empty/text/binary/multi-buffer output and nonzero exit semantics; an injected pipe
opener deterministically proves false without resource exhaustion. Explicit child
environment values prove missing versus empty, take preservation and coalescing.
No PCH or native compiler rebuild; POSIX/Linux execution only.

CLI evidence: `/tmp/runtime-cli-20261009-final` passes five PHP suites, including
S2S goldens/incremental checks (28.53s; others 0.54–1.12s), and five C++ build/run
tasks (1.63–5.83s). Argument execution covers no user arguments and a second run
with empty/spaced/UTF-8/flag-like arguments, cross-unit access and independent mixed
snapshots. Representative function-try and renamed-entry exception probes and the
existing runtime process regression pass. Generated builds exposed and repaired a
missing direct Result include and internal namespace shadowing in php_process.hpp;
public behavior is unchanged. Existing entrypoint instrumentation was mechanically
adapted to argc/argv; not every affected historical suite was rerun. No PCH or native
compiler rebuild.

Core-memory evidence: `/tmp/runtime-memory-module-20261009-1` passes three PHP
suites (0.53–0.93s) and three generated-program builds/runs (1.83–6.11s).
Both helpers and all four registered signatures execute; bool expressions are
evaluated once and source declarations shadow runtime names normally. Linux /proc
observations are positive; tests do not assert equality across changing samples.
Stable-hash names remain rejected. No PCH or native compiler rebuild.

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

Registry-foundation correction (2026-10-09): Result transport now uses the registered
`result_payload` opt-in; template parameters declare conditional capabilities
separately from formation constraints. One registry evaluator replaces named
Result/nullable/vector/hash copy branches and lets analysis supply observed source
facts without caching them. `/tmp/registry-capabilities-20261009-2` passes eight PHP
suites and 17 generated-C++/runtime checks, including an unfamiliar `parcel<T>`
provider with no new preparation/lowering path. JSON type declarations remain out
of scope; this repairs the existing type-registration foundation. No native compiler
rebuild or PCH.

Regex evidence: `/tmp/runtime-regex-module-20261009-final` passes five PHP suites
(0.44–0.93s), nine generated-program builds/runs (4.01–10.32s), and the existing
native regex regression (10.14s). PCRE2 10.42 is present; the separate provider
compiled once in 2.50s. All 18 signatures execute, including the three nested
capture shapes, without parser/preparation/emitter changes. Unselected calls reject
before emission; startup defaults remain unchanged. No PCH or native compiler rebuild.

Supplementary foundation checks in `/tmp/registry-capabilities-20261009-review`
pass type-catalog, capability-rule validation, container calls, nullable records and
ownership (0.39–2.11s per PHP suite). Registration rejects conditional transport
opt-ins: transport support is unconditional; copying is checked independently.
