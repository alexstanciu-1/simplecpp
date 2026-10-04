# Portability status and evidence
Doc Status: supporting

PHP behavior, conversion to PHS, native compiler execution and generated-program
execution are separate claims. The current bounded native checkpoint passes all four.
The latest checkpoint covers the implemented Chapter 05 nullable and Result slices
through Result coalescing, plus the source adaptations below. It is not a Chapter 05
closeout: mixed and the remaining catalog debt are still deferred.
This does not establish exhaustive language, lifetime, incremental or performance coverage.

## Latest native checkpoint — 2026-10-04 nullable and Result slices

The worktree based on `917bcb0252b84db4203d1706d2e516ff1588883e`, with two
authored-PHP portability repairs, passes conversion, native compiler construction,
PHP/native C++ byte parity and generated-program execution. All 105 compiler/proof
source hashes (excluding the generated driver) and six harness hashes matched the
checkout after testing; these identify the tested overlay, not the base alone.

The **12-job** run passes 702 source comparisons/executions (572 S2S and 130
control-flow fixtures), 179 supplementary probes plus two control-flow error probes,
198 rejection/recovery cases, and 15 floating-spelling assertions. Separate
emission/type proofs, the control-flow incremental proof, repeated compilation and
recovery, and the final incremental native build pass. Nine focused PHP suites also
pass: results, take, nullable, result_model, type_catalog, constructed_type,
conversions, incremental_cpp and preparation_recovery.

Attempt 1 stopped at unsupported postfix decrement in the take parser; use `-= 1`.
Attempt 2 converted all 106 source/driver files but failed native construction:
promoted `null_type` fields were assigned before `parent::__construct`, preventing
recognition of the C++ base initializer. An explicit field and assignment after the
parent call preserve the existing model. No generated output, generator/runtime
behavior or language semantics were changed. Attempt 3 passed in **471.222 seconds**,
reusing build objects; this is an observed run, not a performance benchmark.

Evidence: `/tmp/my-try-ch05-native-20261004/summary.json`, `source_hashes.json`,
`harness_hashes.json`, `candidate.json`, command journals and `logs` through `logs-3`.
Focused PHP evidence: `/tmp/my-try-ch05-portability-php-20261004/logs/`.
Reproduce with the standard native command and `--no-stan --types-only --jobs 12`.
The shared runner used CLI fallback after sandbox FPM socket denial. LLVM and legacy
STAN remain skipped; native build warnings remain, so this is not a warning-clean
proof. Chapter 05-specific incremental negative scenarios retain their PHP-suite
evidence; the native incremental proof remains the separate control-flow scope.

## Earlier native checkpoint — 2026-10-02 function closeout

The worktree based on `f0aca78d42ef7e1baa5c1fd70823bd6bedb108f8`, with this
checkpoint's five authored-PHP adaptations, passes conversion, native compiler
construction, PHP/native C++ byte parity and generated-program execution. Source
and harness hashes identify the tested overlay, not the base revision alone:
all 97 compiler/proof source hashes (excluding the generated driver) and five
harness hashes matched the checkout after testing.

The **12-job** run passes 528 source comparisons/executions (398 S2S and 130
control-flow fixtures), 88 supplementary probes plus two control-flow error probes,
170 rejection/recovery cases, and 15 floating-spelling assertions. Separate
emission/type proofs, the control-flow incremental proof, repeated compilation and
recovery, and the final incremental native build pass. The focused Chapter 04
recheck also passes all 12 PHP suites and 77 function programs/probes.

Attempt 1 converted successfully but failed native compilation. One corrective
cycle introduced a truthful `prepared_literal` family for default snapshots and
local value-key access, obtained literal facts through typed syntax accessors,
typed the vector type-argument local, and replaced two index-selection ternaries
with explicit assignment/branching. The latter avoids a target ternary value-category
limitation. No generated output, generator/runtime behavior or language semantics
were changed. Attempt 2 passed in **394.79 seconds**, reusing build objects; this
is an observed run, not a performance benchmark.

Evidence: `/tmp/my-try-ch04-native-20261002/summary.json`, `source_hashes.json`,
`harness_hashes.json`, `candidate.json`, command journals and `logs`/`logs-2`.
Focused evidence: `/tmp/my-try-ch04-portable-functions-20261002/summary.json`.
Reproduce with the standard native command and `--no-stan --types-only --jobs 12`.
LLVM and legacy STAN remain skipped. Native compilation still emits missing-override,
return-path, switch and unused-result warnings: this is not a warning-clean proof.
Default/variadic-specific incremental edit scenarios remain PHP-suite evidence;
the native incremental proof is the separately named control-flow/recovery scope.

## Size experiment — 2026-10-03, not a new full checkpoint

At `e33a2754`, source hashes still matched the Chapter 04 native snapshot. The
validated compiler used **Clang 18.1.3, `-O0 -g`**, x86-64 ELF PIE, mold and no LTO.
A copy of its build directory rebuilt all 104 Ninja steps at `-O2 -g`, changing only
application `cxxflags`; the shared runtime was held constant. Original binaries were
preserved, with separate `strip --strip-all` copies for measurement.

| Compiler executable | With debug/symbols (bytes) | MiB | Stripped (bytes) | MiB |
| --- | ---: | ---: | ---: | ---: |
| `-O0 -g` | 46,045,824 | 43.91 | 6,353,952 | 6.06 |
| `-O2 -g` | 58,096,480 | 55.41 | 3,781,752 | 3.61 |

The optimized stripped executable is 40.48% smaller, while the debug-bearing file
is larger. Both ran the S2S smoke and control-flow incremental proof with identical
1,080-byte C++ output. No full `-O2` regression sweep or runtime performance benchmark
was performed; neither optimization defaults nor warning debt changed.

For context, the installed stripped Clang 18 driver is 140,592 bytes, but depends on
`libclang-cpp.so.18.1` (64,796,088 bytes) and `libLLVM.so.18.1` (123,215,144 bytes):
179.44 MiB combined. Our `-O2` stripped executable plus the same runtime stripped
to 1,926,712 bytes totals 5.44 MiB. This is an installation-specific disk-footprint
comparison, not equivalent functionality: it excludes general system libraries,
headers, and our external C++ compilation/linking toolchain.

Evidence: `/tmp/my-try-size-20261003/README.md`, stripped copies and smoke outputs;
`/tmp/my-try-ch04-native-20261002/o2-build.log` and `phpp/.prism/build-o2/build.ninja`.
These temporary artifacts may expire; the measured values and scope are retained here.

## Earlier native checkpoint — 2026-10-02 control-flow closeout

The worktree based on `5c813f7de3cb1e17fdc252cb2785057ed1d8c6fc`, with the portable
source adaptations and harness extensions below, passes conversion, native compiler
construction, PHP/native C++ byte parity and generated-program execution. This covers
the Chapter 02 follow-up repairs and Chapter 03's bounded first pass. The changes were
uncommitted during testing: `source_hashes.json` and `harness_hashes.json`, not the base
revision alone, identify the tested overlay. All 96 compiler/proof PHP source files
(excluding the generated driver) and all recorded harness hashes matched the tested
checkout at that checkpoint; subsequent Chapter 04 edits have changed those sources.

The **12-job** run passes:

- 479 source comparisons and executions: 349 expression/declaration fixtures plus
  130 control-flow fixtures, compiled with `-Werror=return-type`.
- 74 existing supplementary value/type/error probes and two control-flow error probes
  (explicit bool-cast rejection and unmatched match without default). Probe base output
  is verified before the instrumented program runs.
- 135 rejection/recovery cases, including 98 exported control-flow cases, and all
  15 floating-spelling assertions.
- Separate emission/type proofs, a portable control-flow span-move identity and
  unselected-arm dependency failure/retry/repair proof, and the final incremental build.

The portable-PHP adaptations preserve the compiler model: initialize the optional
lexical parent to null; use `-= 1` for the two remaining-count updates; construct
record capabilities through an explicit `vector<generic_contract>` local; and extract
a required `breakable_node` from the checked nullable target before storing its weak
observer. No generator/runtime extension or language-feature expansion was needed.
The native harness now consumes Chapter 03's source/rejection manifest as well as the
older S2S manifest; previously it did not cover that chapter.

Evidence: `/tmp/my-try-ch03-native-20261002/summary.json`, `source_hashes.json`,
`harness_hashes.json`, `candidate.json`, journals and `logs` through `logs-5`.
Attempts 1–2 stopped at conversion (nullable field initialization, then decrement);
attempt 3 stopped at native compilation (capability container and nullable weak-target
assignment). Attempt 4 built the compiler but found a setup error in the new proof:
it edited a stale source snapshot. The proof now edits the registered current snapshot.
Attempt 5 passed in **395.55 seconds**, reusing build objects; this is an observed run,
not a performance benchmark. Six affected PHP suites also pass independently under
`/tmp/my-try-ch03-portability-php-2/`.

LLVM and legacy STAN remain explicitly skipped (`--no-stan --types-only`). This does
not certify all lifetime/effect behavior, parked LLVM, or a clean broad/style suite.
Reproduce using the standard [native command](../testing.md#commands) with the commit
containing these adaptations and a fresh results directory.

## Earlier native checkpoint — 2026-10-02 parallel test runner

The compiler sources at `4a8aa89ccf37f44da2a96092880c66bc0cd1676d` pass native
validation with the new shared runner and an isolated request file per fixture.
The harness changes were uncommitted during this proof; source/toolchain hashes and
the generated driver are retained in the result directory. The default **12-job**
run passes all 341 source comparisons/executions, 74 supplementary instrumented
programs, 15 float-spelling assertions and 37 rejection/recovery cases, plus the
separate emission/type proofs and final incremental rebuild. LLVM and legacy STAN
remain skipped (`--no-stan --types-only`).

Evidence: `/tmp/my-try-parallel-native-20261002/summary.json`, `commands.json`,
`source_hashes.json`, `candidate.json`, and per-command logs. Total measured wall
time is **259.9 seconds**. The preceding serial run recorded 1,012.346 seconds of
command time, dominated by generated-program compilation. These are observed runs,
not a controlled benchmark: the parallel run used a fresh native build directory,
while the earlier successful serial attempt reused objects from a failed attempt.

See [test runners](../testing.md) for reusable scheduling, `--jobs`, private temporary
directories, fixture isolation and failure evidence. The compiler build precedes the
pool; each fixture then owns its sequential comparison/compile/execute operations.
New tasks start as soon as individual slots become free.

The parallel PHP-suite check completed all 43 suites, with 41 passing. Two failures
also reproduce when invoked directly without the pool: `model.php` fails in parked
LLVM preparation (`Object not found`), and `structure_access.php` reads the type
catalog before initialization. These remain separate test/backend debt; this runner
change does not suppress them or claim a fully passing broad suite. Evidence:
`/tmp/my-try-parallel-php-20261002/php-summary.json` and `logs/`.

The ordinary runner's generated-program and CLI helpers were then checked separately
against the successfully emitted manifests: all 391 C++ and 19 LLVM fixture programs
passed with 12 jobs in 144.002 seconds, along with host CLI output/usage and sample
execution checks. This exercises generated LLVM fixtures without claiming that the
parked native-compiler LLVM comparison path or the failing `model.php` suite passes.
Evidence is `program-summary.json` and `program-logs/` in that same PHP result folder.
The standalone runner unit tests pass, including the 12-slot refill proof, aggregate
failures, temporary-file isolation and process-tree timeout cleanup.

## Earlier checkpoint — 2026-10-02 expression chapter closeout

The worktree based on `a17ec891af2f30bb7635bd0fe1290b4555aa8ab9`, with the two
portability adaptations below, converted and built with Clang 18 and
`--no-stan --types-only`. All 81 compiler source hashes match the tested snapshot.
The source changes were not yet committed when the run finished;
`source_hashes.json` identifies the exact tested compiler sources.

- Parser diagnostics use separate current-position and explicit-position methods,
  avoiding unsupported integer parameter defaults while preserving diagnostic text.
- Mutation and compound preparation bind operand arrays to explicitly typed
  `vector<canonical_type_use>` locals before calling the shared operator owner.

The first attempt stopped at conversion on the integer defaults; the second stopped
at C++ compilation on the untyped cross-class array arguments. The third passed,
including the final incremental rebuild. Focused PHP grouping, logical, operator,
mutation, compound, interpolation and indexing tests also pass.

Native and PHP compilers produced identical C++ for all **341 source fixtures** and
the separate emission/type proofs. Every source fixture compiled and executed with
its expected exit code. The harness now also consumes the existing execution
manifest after establishing base-output parity: **74 supplementary instrumented
programs** passed, checking exact scalar/string values, types and error/store behavior.
These include both additional probe files and instrumented replacements of base
programs; they are not 74 additional language examples. All **15 float-spelling
assertions** and **37 rejection/recovery cases** passed, including both bracket
arities in read and assignment positions. No bracket overload or lowering was enabled.

Evidence: `/tmp/my-try-ch02-native-20261002/`, including `summary.json`,
`source_hashes.json`, `candidate.json`, `commands.json`, attempt directories
`logs`, `logs-2`, `logs-3`, and generated/compiled programs. The successful attempt's
recorded command time totals 1,012.346 seconds: 932.763 seconds compiling generated
programs, 41.307 seconds building the native compiler, and 0.532 seconds for the
final incremental build. These are one-run measurements, not a benchmark claim.
At that checkpoint the harness ran fixtures serially and the native driver used a
shared request file. The subsequent parallel-runner checkpoint above removes both
limitations.

Reproduce from a fresh result path using the commit containing these adaptations:

```bash
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /home/alexv/__AI/simple_cpp/simple_cpp_01 \
  --candidate-revision FULL_COMMIT \
  --results /tmp/FRESH_DIRECTORY --no-stan --types-only
```

LLVM and legacy STAN remain explicitly skipped. This checkpoint proves the bounded
current source/fixture set, not all deferred operator, lifetime or incremental cases.

## Earlier checkpoint — 2026-10-01 operator closeout

The operator worktree based on `ac91df0d377afc2e8b6f0730c9ef5bdda67530b5`,
with the portability-only `$operator` to `$source_operator` identifier correction,
converted and built as the native compiler with Clang 18 and `--no-stan`.
`source_hashes.json` records the exact tested compiler sources; the correction was
not yet committed when the proof ran.

The native compiler and PHP host generated byte-identical C++ for the portable type
proof and every authored S2S fixture. All 105 valid programs compiled and executed
with their independent expected exit codes, including `EXPR-ARITH-001`. All 15
specialized floating-point spelling assertions and all 32 rejection/recovery cases
passed. Repeated compilation/recovery and the final incremental native rebuild also
passed.

This was the bounded types-and-S2S validation requested after the operator work.
LLVM and legacy STAN remained explicitly parked. Evidence is under
`/tmp/my-try-operators-native-20261001-k41xom/run/`: `summary.json`,
`commands.json`, `candidate.json`, `source_hashes.json`, attempt logs, and all
generated/compiled programs. Reproduce it from a fresh result path with:

```bash
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /home/alexv/__AI/simple_cpp/simple_cpp_01 \
  --candidate-revision FULL_COMMIT \
  --results /tmp/FRESH_DIRECTORY --no-stan --types-only
```

The first attempt correctly stopped at conversion because `$operator` is a reserved
C++ identifier. Renaming that local and parameter to `$source_operator` was the only
source correction needed before the complete pass.

## Earlier checkpoint — 2026-10-01 cast/conversion closeout

Commit `0746c2e46fb4f6b1be1de0e14d6125f2acdd0b95` converted and built as the
native compiler with Clang 18 and `--no-stan`. The native compiler and PHP host
generated byte-identical C++ for the portable type proof and every authored S2S
fixture. All 104 valid programs then compiled and executed with their independent
expected exit codes, including the scalar cast matrix fixtures, identity and alias
casts, fixed-width integer and field operands, and nested casts. All 15 specialized
floating-point spelling assertions and all 32 rejection/recovery cases passed. The
final incremental native rebuild also passed.

This was the bounded types-and-S2S validation requested for the cast work. LLVM and
legacy STAN remained explicitly parked. Evidence is under
`/tmp/my-try-casts-native-20261001-WiybOY/run/`: `summary.json`, `commands.json`,
`candidate.json`, `source_hashes.json`, command logs, and generated/compiled programs.
Reproduce it from a fresh result path with:

```bash
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /home/alexv/__AI/simple_cpp/simple_cpp_01 \
  --candidate-revision 0746c2e46fb4f6b1be1de0e14d6125f2acdd0b95 \
  --results /tmp/FRESH_DIRECTORY --no-stan --types-only
```

## Earlier checkpoint — 2026-10-01 type-model closeout

The type-model worktree based on `c853bcc9bb27435abaadc1c9a80bfec747b4b983`
converted and built as the native compiler with Clang 18 and `--no-stan`. Because
the compiler changes were not yet committed, `source_hashes.json` in the evidence
directory is the exact compiler-source identity; the candidate revision alone is
not presented as the tested source.

The portable type proof runs under both PHP and the native compiler and compares
their generated C++ byte-for-byte. It proves the `byte`/`uint8` identity, declared
capability queries and rejection, all registered runtime recipes, nested exact
application reuse, `value<T>` occurrence propagation and recursive C++ spelling
and headers. The resulting constructed-type C++ compiles and executes. The same
run compares, compiles and executes all 95 valid S2S fixtures, preserves all 15
floating-point spelling assertions, checks the current 29 S2S rejection/recovery
cases and completes an incremental native rebuild.

This was deliberately a type-and-S2S checkpoint. The harness records both parked
LLVM validation and STAN as skipped; it does not turn their absence into a full
compiler-validation claim. Evidence is under
`/tmp/my-try-types-native-20261001-gXHJnH/run/`: `summary.json`, `commands.json`,
`source_hashes.json`, `logs-29/` and the generated/compiled programs. Reproduce it
from a fresh result path with:

```bash
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /absolute/path/to/simplecpp \
  --candidate-revision FULL_COMMIT \
  --results /tmp/FRESH_DIRECTORY --no-stan --types-only
```

Without `--types-only`, the standard harness retains its parked LLVM/sample sweep.

## Earlier full checkpoint — 2026-09-30

Native source/toolchain: `8ec5f924`. This is a revision-specific checkpoint, not
certification of later changes. The compiler converted, built and ran successfully
with Clang 18 and `--no-stan`.

The harness compares PHP-host and native-compiler C++ bytes for every one of the 95
valid authored S2S fixtures, then compiles and executes every resulting program.
All pass with their independent expected exit codes. The 15 float-form fixtures also
assert exact emitted spelling, `scpp::float_t` type and native value. All 31 S2S
rejection/recovery cases pass. The separate parked LLVM/sample sweep passes all 142
comparisons: 48 valid programs execute and 94 invalid programs reject. The initial
S2S smoke proof, repeated compilation/recovery and incremental native rebuild pass.

Evidence under
`/tmp/my-try-catalog-native-20260930-eZs6tj/native-8ec5f924-all-valid/`:
`summary.json`, `commands.json`, `logs/` and the per-fixture `s2s-programs/` outputs.
Temporary paths may disappear; reproducible standard harness:

```bash
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /absolute/path/to/simplecpp \
  --candidate-revision FULL_COMMIT \
  --results /tmp/FRESH_DIRECTORY --no-stan
```

Native checks remain explicitly opt-in. By user decision, bypass v0.1 STAN for this
compiler work; its diagnostic catalog is parked in [review debt](REVIEW.md).
The harness retains its general default; use the explicit flag here. No verified
portability target pin has been changed. The earlier full-run style-gate failure
remains separate from this native pass; style/full PHP suites remain on demand.

## Current authoring boundaries

- Use the [portable PHP guide](../../../../specs/portability/authoring_guide.md).
  Scope locals correctly, preserve one meaning/type per name, and avoid type/member collisions.
- Keep explicit Storage element types and typed nested receivers; value containers
  are not aliases simply because PHP records share identity.
- Acquire actual weak fields through their supported binding; documentary annotations
  alone do not implement expiration. Required staged fields remain required.
- Required nullable extraction and shared derived/base upcasts are supported native
  boundaries. Keep actual narrowing casts distinct; do not add dummy initialization
  or casts to satisfy parked STAN diagnostics.
- Legacy lowering now handles the compiler's public inherited constructors, accessor
  bridges and file-local class imports. Shared identity works across compatible
  derived/base handles. Non-public constructor policy and unrelated-interface identity
  remain outside those bounded repairs; see [runtime rules](../../../../runtime/specs/spec.md).

The [incremental plan](../planning/incremental_strategy.md) records remaining model
and validation debt. Earlier failures and intermediate fixes are retained in the
[historical checkpoint ledger](../archive/conversion_review_checkpoints.md), not current blockers.
