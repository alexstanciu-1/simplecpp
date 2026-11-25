# Regex callbacks and runtime target composition
Doc Status: supporting

Completed 2026-10-20 on dev002. **V-R05 and V-R11 close**, advancing the original
inventory from **37/64 (57.8125%) to 39/64 (60.9375%)**, a gain of **3.125 percentage
points**. No batch implementation blocker remains. Original findings and failed
baseline evidence remain intact.

## Contracts and ownership

V-R05 exposes `regex_replace_callback` with three/four arguments and
`regex_replace_callback_array` with two/three arguments. Metadata uses the existing
structural `function<string(vector<string>)>` contract and typed string-keyed hash;
the provider receives ordinary `std::function` storage. No compiler implementation
or callable model change was needed. Owned captures cross the ordinary boundary;
borrowed captures remain rejected.

Limits are unlimited at -1, bounded per pattern when positive, and invoke no
callbacks at zero. Limit validation precedes pattern validation, including empty
tables; zero limit still validates patterns. Invalid patterns return false.
Callback output is literal bytes. Callback tables apply entries in insertion order
against the current rewritten subject. Exceptions propagate, preserving completed
effects; input values are not mutated by the provider.

The shared regex replacement worker now separates matching/output traversal from
replacement rendering. Ordinary replacement expands backreferences; callbacks
render the same match through their typed capture vector. Both share the corrected
zero-length/UTF search progression and copied-output position. Explicit
`replace_callback_limited` and `replace_callback_array_limited` entrypoints prevent
writable source snapshots from selecting the older count-output overloads.

V-R11 gives CMake separate owners:

- `scpp_runtime_core`: core-only implementation bundle, without filesystem sources.
- `scpp_runtime_configuration`: common include, language and feature settings.
- Provider libraries: depend on core; MySQL implementation moves to `scpp_mysql`.
- `scpp_runtime`: retained consumer-facing interface aggregate.

Built-in core/providers/adapters consistently follow `BUILD_SHARED_LIBS`.
Shared providers use one shared core and its process-wide string-key pool.
Static compositions resolve provider dependencies through targets. The historical
`core/runtime.cpp` snippet bundle still includes core plus filesystem; production
metadata/native-builder composition remains unchanged. Named target compatibility
is preserved; the interface aggregate no longer produces a `libscpp_runtime` file.

Contracts: [callbacks](../../../../specs/builtins/regex/preg_replace_callback.md),
[callback tables](../../../../specs/builtins/regex/preg_replace_callback_array.md),
and [CMake ownership](../../../../specs/architecture/runtime_layering.md#cmake-core-and-aggregate-ownership).

## Verification

All executions ran on `alex-ai@dev002.travelfuse.ro` in
`/home/alex-ai/callback-composition/20261020-01`, based on `8459eabc` plus the
recorded overlay. Shared runners use 12 slots; the six CMake configurations each
use two build jobs. PHP uses CLI fallback because CLI/FPM versions differ.

| Checkpoint | Evidence |
| --- | --- |
| `runtime-baseline-01` | Nine expected behavioral failures among 34 direct callback checks |
| `source-baseline-01` | All four callback forms unresolved before registration |
| `composition-baseline-01` | Unchanged aggregate JSON-document consumer fails to link with unresolved runtime symbols |
| `runtime-01` | All 34 direct provider checks pass |
| `source-01` | The same 34 checks pass through generated source functions; defaults, owned captures, callback/table snapshots, effects, seven preparation rejections and two retained failure/retry/repair/no-op cycles pass |
| `composition-02` | Six static/shared configurations pass aggregate/direct-provider/core-only consumers, isolated JSON control, core/nullable/dynamic tests, string-pool identity, optional regex/process tests and MySQL/legacy-adapter compilation |
| `regex-regressions-01` | Five existing PHP checks, ten generated programs and the native regex suite pass |
| `replacement-regressions-01` | Existing replacement/filter oracle, source snapshots and production cold/warm builds pass |

Callback and replacement production builds retain exact stdout; warm builds
compile zero units and perform zero links. The callback oracle and generated source
use ASan/UBSan; this is not a claim that every CMake regression is instrumented.
Checks include empty matches, nonempty alternatives, UTF progression, NUL bytes,
capture contents, literal output, empty successful payloads, zero/positive/unlimited
limits, invalid-pattern/limit precedence, callback exceptions and absent invocation.

The [results](callback_composition_results.json), [audit](callback_composition_audit.json)
and [raw evidence](callback_composition_evidence.json.gz) retain **133 accepted
command outcomes and 70 native executions**. Baselines are counted separately.
All **6,336 snapshot file hashes** matched on dev002; local executable inputs match
that snapshot except removal of one trailing blank line in `runtime_core.cpp`.
The audit proves that exact whitespace difference and records subsequent
documentation-only changes.
The [initial](callback_composition_source_initial.json) and
[final](callback_composition_source_final.json) manifests preserve provenance.
The initial archive predates the source-admission harness; that later harness ran
against unchanged pre-repair metadata and compiler files. The first four-config
CMake pass remains in the archive as an intermediate checkpoint.

## Retained boundaries

No full operator matrix or native compiler rebuild ran. Windows/macOS execution,
external mixed static-core/shared-provider compositions, GUI/provider platform
sweeps and database integration remain unverified. MySQL/legacy-adapter compilation
does not close V-E03. Count-output source APIs, legacy callback adaptation, broader
closure borrowing/S05 and project CLI composition remain separate. Shared provider
repairs naturally reach existing forwarding callers; native regex regression
controls cover the existing legacy forwarding path.

The older Chapter 15 regex/link-order findings remain historical evidence; this
report and current coverage map supersede their callback and aggregate-link holds.
Unrelated Chapter 15 working-tree edits and pre-existing test caches are untouched.

## Implementation commits

- `99d919cb`: shared regex callback traversal, validation and direct runtime proof.
- `13251d1c`: strict callback metadata and source/production proofs.
- `b26b107d`: CMake core/provider composition and six-configuration verification.

The evidence and ledger closeout is committed separately.
