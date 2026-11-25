# Strict curl integration
Doc Status: planning

V-R03 and V-E02 completed on 2026-10-20. Overall progress is now
**43/64 closed (67.1875%)**, zero partial and 21 open: **+3.125 percentage points**.

Verified implementation checkpoints: `1870ecc12` (runtime HTTP harness) and
`c8bd50e36` (candidate curl metadata, approved property/extraction transport and
focused tests). These are partial goal checkpoints, not a curl compatibility
closeout. Final transport and acceptance harness: `e6fd0b523`.

## Authorized scope

Expose the nine documented curl names (13 concrete signatures), two shared
provider types, nine response fields and 19 integer constants through the existing
metadata owners. Verify the opt-in provider and strict fixtures on dev002 using
default template prelowering. Keep runtime implementation changes, legacy curl,
general project CLI work and a full matrix separate.

The candidate `runtime/metadata/modules/curl.json` uses existing source/library/
definition build requirements. The first checkpoint changed no compiler or runtime
implementation; the approved compiler transport implementation is recorded below.
Final default-prelowered application builds and missing-library diagnostics pass;
compiler-native portability was not rebuilt.

An origin fetch during the prerequisite review found no newer remote commits:
the local branch was 14 commits ahead and zero behind. All 19 candidate metadata
constant values match the provider header; this is not a substitute for source
behavioral acceptance.

## Runtime-first checkpoint

Probes used the frozen S11/S13 source tree at
`/home/alex-ai/tokenizer-io/20261020-final` plus the new curl metadata file.
The pre-existing source files were not changed. Results live under
`/home/alex-ai/curl/20261020-probe-01`, `-02`, and `-03`.
Host libcurl reports 8.5.0 through pkg-config.

- Existing `tests/runtime/native/test_curl.cpp` builds and passes with the real
  curl provider enabled.
- Default-prelowered `curl_init` plus prepared `take` builds and executes.
- Default-prelowered exec/getinfo/Result extraction and mixed output build and
  execute when response properties are not accessed.
- Response property access publishes but native strict application validation
  rejects template-bearing provider header inclusion.
- A separate mixed-to-int probe rejects during publication with
  `prelower_templates: boxed extraction requires concrete transport`.
  This is distinct from working Result-to-mixed extraction and mixed output.

## Fixture findings

All five strict fixture bodies were probed with comment declarations migrated.
HTTP fixtures additionally need an explicit `$argv=cli_args()` harness preamble.
The first fixture sweep omitted the strings module and argv setup; those harness
errors are preserved in probe-02, not classified as compiler defects.

With that setup corrected (probe-03), file, GET and POST fixtures reach the
response-property prelowering failure. The HTTPS fixture first rejects its
untyped mixed URL at the exact string argument boundary. It needs explicit
string stabilization, which exposes the boxed-extraction transport prerequisite.
The JSON fixture first rejects taking default `json_decode` into mixed: v0.2
intentionally returns dynamic. Its compatibility variant should explicitly use
`json_decode_mixed`, as already recorded for the standalone strict JSON fixture.
These additional adaptations must be labelled separately from declaration-only
migrations. At that initial checkpoint no HTTP or external HTTPS execution pass was claimed.
The final local HTTP acceptance below supersedes that blocker; external HTTPS
execution remains opt-in.

## Bounded prerequisites — approved and implemented

1. Concrete transport for registered provider instance-property reads and plain
   writes. Shared preparation retains property identity, admission and value
   conversions; the existing compiled operation/representation owners carry the
   native access. Preserve receiver lifetime, snapshots, null checks and once-only
   write-back. Cover the nine curl fields, including vector<string>; keep static
   properties, reference escape and compound/indexed updates separate.
2. Concrete transport for prepared mixed-to-int/string checked extraction,
   preserving the existing required-cast semantics. Use the compiled conversion
   owner, not curl-specific conversions or a new metadata registry. Wider boxed
   types and conversion forms remain separate.

The user approved these two prerequisites with “Yes, please implement”.
Remote synchronization found no newer commits before implementation.
No runtime implementation or semantic changes were made.

`CPP_Provider_Properties` owns sequencing over the settled property facts.
The existing runtime-operation bridge retains property read, target validation
and plain write requests, keyed by property identity/binding and physical types.
Provider headers stay behind the compiled boundary. The receiver is retained and
checked before evaluating the RHS, then the converted snapshot is written once.
The vector<string> field adapter copies elements between the provider's native
vector and application's composed representation; scalar payload adaptation
reuses the existing wrapper transport owner. No alternate metadata registry exists.

The bounded extraction selection adds only mixed-to-canonical-int/string to the
existing compiled conversion owner. Runtime `required_cast` remains authoritative,
including null and wrong-kind errors and its existing numeric-to-string behavior.
Other boxed extraction targets remain explicitly rejected.

## Transport verification and remaining blockers

Default-prelowered cold/warm tests on dev002 pass for all nine response fields,
receiver replacement during the RHS, once-only write-back, vector copy isolation,
mixed int/string extraction, numeric mixed-to-string conversion, null property
reads/writes, null extraction and wrong-kind int extraction. Null-target writes
fail before RHS output. Warm runs compile and link zero units.

`20261020-transport-final-03` contains six passing supported cases and one
deliberately isolated mixed-index failure; its aggregate result is not a pass.
The final read-only header indexing extension is verified separately in
`20261020-properties-final`. Seven rejection/retry/repair/no-op cases and the
unsupported-boundary, runtime inventory and take regressions pass in
`20261020-regressions-final`. Earlier intermediate failures remain evidence,
including a write initially targeting a read snapshot; the final write dispatch
uses its distinct operation instead. Compiler-native portability was not rebuilt.

All 5,344 current source inputs match dev002. The
[source delta](curl_evidence/source_delta.json) overlays the frozen S11/S13
manifest; the audit is included in the
[transport evidence archive](curl_evidence/curl-transport-evidence.tar.gz).
Archive SHA256:
`02c3794dae7c61a0b237e9925a83f2162de900b8c11d55b6441beb59f035c986`.
The archive includes intermediate failures and generated publications, not just
successful logs. Its final cases are scoped as above; there is no aggregate
full-curl acceptance pass.

## Final approved transport and acceptance

The user subsequently approved all three remaining bounded prerequisites:

- Concrete registered provider value/read-only `vector<string>` arguments reuse
  the native/concrete element adapter. Inferred generic contracts retain their
  substituted physical types; mutable provider references explicitly reject.
- Prepared canonical int/string-to-mixed construction uses the compiled conversion
  owner. Wider boxed construction remains separate.
- Mixed null-index reads adapt both receiver and key and return an owned mixed
  result through the existing index/operation bridge. Receiver/key evaluation stays
  once-only and ordered; this does not invent a deep receiver snapshot before key
  side effects. Index writes, references, iteration and dynamic remain separate.

On dev002, `20261020-acceptance-final` passes all **15 cases** under default
prelowering, cold and warm. All five original strict fixtures compile; four local
fixtures execute with their original exact stdout oracles, matching the independent
v0.1 replay. HTTPS is compile-only because external execution was not requested.
Original fixture files are unchanged. `cases.json` records declaration migrations,
explicit argv setup/string stabilization and the intentional `json_decode_mixed`
family migration separately; these are not all declaration-only adaptations.

Controls cover all nine names/13 forms, 12 options, seven selectors, all nine
response fields, shared handle states, errors/reset/close, receiver replacement,
once-only write-back, read-only header copying (including NUL bytes), empty headers,
null-target ordering and checked mixed extraction. Mixed reads prove int/string key
kind, numeric-string normalization, missing/null results, embedded NUL bytes,
receiver/key order and owned child survival. Mutable vector references reject;
source repair then succeeds. Every warm build compiles and links zero units.

`20261020-config-final` passes three CMake discovery checks: enabled with installed
libcurl, enabled with discovery deliberately unavailable (the documented clear
failure), and disabled with unavailable discovery (success). No packages changed.
`20261020-regressions-closeout` passes transport rejection/recovery, unsupported
boundaries, runtime inventory and take checks. The earlier independent runtime
ASan/UBSan proof remains valid: this goal changes no runtime implementation.

All **5,346 source inputs** match the tested dev002 tree, with zero mismatches.
[Final source delta](curl_evidence/source_final_delta.json) overlays the frozen
S11/S13 manifest. [Acceptance evidence](curl_evidence/curl-acceptance-evidence.tar.gz)
contains source audit, publications, commands, cold/warm results, exact outputs and
configuration/recovery logs, excluding caches and binaries. SHA256:
`97a0be6b2f05503f310e3d0f546f9e78e3ad6600f73ca773c4f284d45f60feca`.

V-R03 and V-E02 are closed within this scope. Legacy curl, general project CLI work,
external HTTPS execution, wider boxed/provider domains, mutable provider arguments,
indexed writes and source exception transport remain separate. No full matrix or
native compiler rebuild ran. Historical failures and earlier partial checkpoints
are retained below and in the archives; this closeout supersedes their blockers.

## Original v0.1 baseline replay

The independent baseline harness now resolves the old baseline/provider uncertainty.
`tests/curl_baseline.py` replays the original sources without migrations against
`75de94c71e37060c564b6ade19850b7686bd457a`. On dev002,
`/home/alex-ai/curl/20261020-baseline-01` proves all five strict fixtures compile;
the file, local GET, POST and JSON fixtures execute with their original exact
stdout oracles and no leftover application files. The external HTTPS fixture is
compile-only, not an execution pass. The final v0.2 comparison above now closes V-E02 in this same local/compile-only scope.

The reused baseline is checked against 1,289 Git blobs from the pinned revision
across bin, generators, runtime, specs and curl fixtures, with zero mismatches.
The shared local HTTP router and payload match the baseline bytes. Baseline
runtime composition was rebuilt with curl enabled; no old object was reused.
The shared runner used CLI fallback because available PHP CLI/FPM versions differ.

[Baseline evidence](curl_evidence/curl-baseline-evidence.tar.gz) includes generated
C++, commands, exact outputs, per-fixture results and the source audit, excluding
executables/objects. Archive SHA256:
`35526a226f719832906c7ee845b3185c5f89a584cd47fc36659919f14f21662c`.
The tested `curl_baseline.py` SHA256 is
`79e0d6ea42806019aeaccbe479a89bf4a5c19fae0de0cbb67a5caf069e7ec177`.

## Evidence

[Probe archive](curl_evidence/curl-probe-evidence.tar.gz) retains logs and generated
probe source, excluding native caches and executables. Companion scripts are
investigation checkpoints, not acceptance runners: their aggregate invocation
intentionally fails on the recorded blockers. `curl-publish.php` is the corrected
probe-03 version (strings module included); earlier runs used the smaller module
selection described above. Final fixture migration and harness acceptance are recorded above.

## Independent runtime HTTP proof

The independent `tests/curl_runtime.py` runs the unchanged
native curl test and `tests/curl_runtime.cpp` through the shared task pool, both
with ASan/UBSan. The HTTP harness serves the repository's existing PHP router and
payload on loopback, records its process command/log, checks readiness and always
reaps the server. It does not access external HTTPS.

Both tasks pass on dev002 in `/home/alex-ai/curl/20261020-runtime-01`. Coverage:
file transfer and errors/reset from the original test; HTTP GET, binary POST,
JSON body, all info selector kinds, alias-visible errors/reset/close, invalid and
closed handles, and response ownership surviving reset/close. These are runtime
proofs, not source snapshot/write-back or v0.1/v0.2 compatibility proofs.

[Runtime evidence](curl_evidence/curl-runtime-evidence.tar.gz), excluding binaries:
SHA256 `d08b4d5b95287bb347447d56cfd2daf980d4a507f87802ace9afd7da128656d2`.
Test source SHA256:
`c96a8992d923a886fa6477b90ad9e6535ecb62155d327db4a54eeb340f66a488`
(`curl_runtime.cpp`) and
`8525566cd6cb2b4c89f1d03ad1563956f973ef26bb64899fbc7deb80e2ab0ab8`
(`curl_runtime.py`).
