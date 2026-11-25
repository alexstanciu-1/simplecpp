# Running the v0.1 compatibility corpus
Doc Status: planning

The runner compares unchanged sources from pinned v0.1 development commit
`75de94c71e37060c564b6ade19850b7686bd457a` with the working v0.2 compiler/runtime.
The [original manifest](../../tests/legacy_compatibility.json) retains the 50-case
first pass. `--all` expands discovery to every non-matrix PHP/runtime metadata
case, native runtime assertion tests, and standalone generator/example sources.
See the [expanded evidence report](v0_1_compatibility_expanded.md) for results and
semantic dispositions. The [first pass](v0_1_compatibility_first_pass.md) remains
historical evidence, not the current coverage boundary.

## Commands

For the current strict-only focus, use
`--manifest compiler/my-try/tests/legacy_compatibility_strict.json` without `--all`.
See the [grouped strict causes](v0_1_compatibility_strict_causes.md).

Use a fresh result directory each time:

```sh
python3 -B compiler/my-try/tests/legacy_compatibility.py --all --results /tmp/compat-FRESH
python3 -B compiler/my-try/tests/legacy_compatibility.py --all --inventory-only --results /tmp/compat-inventory-FRESH
python3 -B compiler/my-try/tests/legacy_compatibility.py --all --filter closures --results /tmp/compat-closures-FRESH
```

Omit `--all` for the original 50 cases. `--manifest PATH`, `--cxx COMPILER` and
`--jobs N` are supported. Default: Clang/C++23 and the shared continuously refilled
12-task pool. PHP with ext-ast and the pinned local Git object are required. The
runner does not fetch, switch branches, implicitly migrate sources or use old build caches.
Snapshot extraction uses Python's safe tar data filter.

## Explicit migration companions

A manifest entry may name a repository-relative `source_variant` file. Both
versions compile those exact retained bytes against the original pinned metadata;
resources still come from the original fixture. Results retain `source_variant`,
the variant `sha256`, and `original_sha256`. Such results prove only the named
migration companion, never unchanged-source compatibility. Ordinary manifests
continue to use unmodified baseline source.

The [six S02 companions](../../tests/legacy_compatibility_strict_s02.json) replace
only inline comment type declarations with strict declarations:

```sh
python3 -B compiler/my-try/tests/legacy_compatibility.py --manifest compiler/my-try/tests/legacy_compatibility_strict_s02.json --results /tmp/compat-strict-s02-FRESH
```

## Test lanes and oracles

| Lane | What is checked | Limits |
| --- | --- | --- |
| Source run | Generate, compile/link, run, check metadata exit/stdout/stderr, then compare versions | First failure only; later operations may remain untested. |
| Source compile-only | Generate and compile every emitted unit without linking or running | Acceptance proof only, no behavior claim. |
| Expected rejection | Verify the baseline's rejection stage and diagnostic fragments; record v0.2 rejection independently | A different or earlier rejection needs semantic review; it is not automatically compatible. Timeouts, signals and launch failures cannot satisfy rejection. |
| Runtime C++ | Compile original baseline test/helper source against each version's runtime and execute assertions | Runtime contract evidence, not proof that PHS exposes that API. C++ API/lowering changes are not automatically source regressions. |
| Generation probe | Generate standalone sources without metadata on both versions | No compile/run oracle: even matching generation is not compatibility proof. Includes rejected fixtures and examples. |
| Blocked | Record baseline-disabled tests and optional provider/environment dependencies | No fabricated language failure. |

All 375 PHP and 105 runtime metadata files are discovered. Baseline-disabled
fixtures and curl-provider tests remain explicit entries. Native `test_*.cpp`
assertion programs and the standalone source-vector assertion are additionally discovered; optional curl/MySQL harnesses
remain blocked. Unannotated standalone PHP/PHS sources under generator samples,
generator tests, PHP tests and examples receive generation probes. Project fixture
sources and HTTP server scripts require their owning project/harness adapter;
they are inventoried without treating them as independent programs. Tool harnesses,
JSS, STAN/CLI/cache tests, benchmarks and generated C++ goldens are not executed as
PHS tests. `inventory.json` lists those remaining files; file counts are not test
or feature counts.

The snapshot contains the baseline compiler, runtime, specs, tests and examples.
The v0.1 source adapter uses the old test harness's natural single-file assembly.
v0.2 uses its source-directory entry and partitioned artifact consumer. Explicit
module selections use the same compiler with a small host adapter (notably the
source-buffer module). Local `fixtures/` resources are copied unchanged into each
private source directory and the corresponding repository-relative run path. Full project resource/dependency semantics are not
claimed. Programs run in private directories with bounded timeouts.

Each version builds its own runtime objects. PHP source tests use the PHP language
define; runtime C++ tests use a separate object without it. Runtime metadata build
flags and run timeouts are honored, with the recorded `--cxx` toolchain override.
Source fixtures use the adapter's C++23 flags rather than the old harness's `-O3`
build profile. Sanitizer/environment-specific metadata is blocked pending an
adapter. Exact and all-substring output policies are supported; newline changes
occur only when the metadata declares them.

PHP-FPM/OPcache is used where available, with the shared CLI fallback. The
production entry is deliberately exercised through CLI. Both compilers are
PHP-hosted; these tests compile generated programs, **not the compiler itself**.
No native compiler rebuild, STAN, LLVM or compiler fixes are part of this audit.

## Evidence and result meaning

Results include `manifest.json`, `inventory.json`, `source_hashes.json`,
`summary.json`, `report.md`, per-case results, source copies, generated artifacts,
executables and the command journal. They identify baseline/current commits,
dirty state, actual compiler/runtime/adapter bytes, fixture hashes and diagnostics.
Use the manifest snapshot with `--manifest` to reproduce an exact selection.

| Result | Meaning |
| --- | --- |
| `compatible-proved` | Both versions meet the run oracle and match observations. |
| `compile-compatible` | Both meet the compile-only oracle. |
| `compatibility-gap` | Baseline passes, v0.2 fails. Requires separate disposition: missing support, intentional change, harness issue or unresolved contract. |
| `behavior-difference` | Both meet their oracle but observations differ. |
| `baseline-unproven` | Baseline did not meet its oracle; not a v0.2 regression claim. |
| `rejection-needs-review` | Baseline meets its rejection oracle; v0.2 rejects, possibly for another reason. |
| `rejection-not-preserved` | Baseline meets its rejection oracle; v0.2 did not produce a normal rejection. Inspect its state/diagnostics. |
| `probe-generated-both` | Both generate; compilation and behavior unverified. |
| `probe-difference` | Only baseline generates; candidate for a real oracle, not yet a confirmed compatibility gap. |
| `probe-baseline-unproven` | Baseline generation failed; may be an intentionally rejected sample. |
| `adapter-blocked` | Explicit metadata/environment/provider reason; not executed. |

Exit zero requires every selected row to be `compatible-proved`; exit one means
there are other results to inspect, including compile-only and probe results. A
shared-pool `PASS` line means the audit task recorded a result, not that its
language case passed. Infrastructure errors retain a separate summary field.
Semantic dispositions are reviewed evidence in the report, not inferred by the
runner from a diagnostic string alone.

## Operator second pass

Only the operator matrix trees (`tests/php-matrix/`, `tests/runtime-matrix/` and
any `operators/matrix/` subtree) are excluded. Ordinary `tests/php/operators/`
fixtures are included in the expanded run. Unrelated names containing “matrix”,
such as a nested-array test, are not excluded. This narrows the first pass's
broader operator-directory exclusion to the user's requested matrix boundary.

The separate [full matrix run](v0_1_compatibility_operator_matrix.md) now records
22,816 generated cases on dev002 and provides its runner/reproduction commands.
Matrix cases remain excluded from the non-matrix manifests above.

When interpreting matrices, reconcile baseline expectations with current expression,
scalar/operator, integer-domain and mixed/dynamic contracts. Earlier rejection of
statically invalid operations is an approved v0.2 policy; rejected valid baseline
operations need separate disposition. Do not revive generated-C++ goldens as the
cross-version oracle.
