# Portability status and evidence
Doc Status: supporting

PHP behavior, conversion to PHS, native compiler execution and generated-program
execution are separate claims. The current bounded native checkpoint passes all four.
This does not establish exhaustive language, lifetime, incremental or performance coverage.

## Current checkpoint — 2026-10-02 parallel test runner

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
