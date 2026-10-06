# Portability status and evidence
Doc Status: supporting

PHP behavior, conversion to PHS, native compiler execution and generated-program
execution are separate claims. The current bounded native checkpoint passes all four.
The latest checkpoint covers the implemented Chapter 05/06/07 slices, including
registered JSON, separated mixed/dynamic families, references and ownership.
The [Chapter 07 closeout](../catalog/07_references_lifetime.md) and Chapter 15 deferrals still
bound the supported language surface.
This does not establish exhaustive language, lifetime, incremental or performance coverage.

## Chapter 08/09 native progress — 2026-10-06 partial

Candidate base `3d47e9288bde404720fcba726872a5af0b6af357`, working-tree overlay,
evidence `/tmp/my-try-ch0809-native-20261006/`. The compiler now builds natively;
the complete post-repair validation sweep is still pending (details below).
Attempts 1–2 stopped at conversion (string casts; readonly enum artifact field).
Attempt 3 converted but rejected the required `process` module. A diagnostic fourth
attempt temporarily omitted that setting and reached C++ compilation, exposing
source-portability issues; it did not supply the missing runtime. The module setting
is restored and the harness now checks required process/file-lock headers up front.

CH08 native build/publication uses the OS facades described in
[os_helpers.md](../../../../specs/portability/os_helpers.md). Following user approval,
the bounded process/file-lock APIs from `2f0d667f` are integrated with strict
registration, alias declaration ownership and module/build composition. Focused
native safety tests, normal-STAN strict source builds, multi-file handle signatures,
PHP lifecycle tests and catalog checks pass. No old mixed/dynamic bridge or unrelated
collection implementation was imported.

Attempts 5–9 use base `ebe91f39` plus the recorded working-tree overlay. They pass
conversion and progressively compile the source, finding more authored portability
repairs: typed Storage receivers, leading base construction without promotion,
checked narrowing of declaration occurrences, typed flag initialization and a local
command copy before mutation. Attempt 9 reaches the framework and proof driver but
fails because the **shape-preserving JSON document API is absent**: `json_document`,
`json_parse_error`, `json_node` and `json_document_*`/`json_node_*` operations.
The proof driver's old file-root accesses are also updated to namespace-body access.
That file-root driver repair is included in the successful native build below.

Following user approval, candidate `9b4b33f35f053b487e018c94d6a4a7888d77c64a`
was retrieved and its bounded document API integrated. Its former last-value-wins
policy is replaced by mandatory duplicate-key rejection. The document parser and
current mixed/dynamic decoder share lexical reading without reverting family
separation. Native JSON/document suites, the shared 23-case unique-key corpus,
strict multi-file API build/run with normal STAN, and runtime declaration/catalog
checks pass. The [document contract](../../../../specs/builtins/json/document.md)
owns shape, raw numbers, checked access, diagnostics and retained-node lifetimes.

Attempts 10–11 compiled the compiler but failed linking: the harness installed the
JSON facade without enabling the `json` runtime module. A forced runtime refresh
alone did not fix that configuration error. The harness now enables the module.
Attempt 12, on base `93e31799` plus the integration overlay, builds and links the
native compiler, passes all 23 host suites, the initial type/control-flow proofs,
and **1,111** PHP/native output comparisons and generated-program executions.
It then stops at the new namespace projects: their weak scope observers exposed
a native-only ownership gap before the selecting method returned.

The parser now retains new namespace scopes before publishing weak observers;
reused bodies select the same owner. The focused native retry also exposed the
proof driver's omitted multi-file entry selection; it now selects `main/main.phs`.
After both repairs, a rebuilt native compiler passes all **44 partitioned projects**
(13 namespace, 7 source-constant, 24 enum), exact PHP/native artifacts, independent
generated-program outcomes and the final incremental build. Evidence:
`/tmp/my-try-ch0809-focused-20261006-2/`, runner
`/tmp/my_try_ch0809_focused.py`; its summary records the repaired parser hash.
The first focused retry is retained in `/tmp/my-try-ch0809-focused-20261006/`.
The namespace regression separately passes 7 PHP suites, 24 generated programs
and tokenizer/parser conversion in `/tmp/my-try-namespace-lifetime-20261006/`.

**Remaining:** run the complete native harness again with the final parser/driver
repairs, including supplementary probes and rejection/recovery, which attempt 12
never reached. The 1,111-case result predates those two repairs; do not combine
these separate checkpoints into a full final pass. CH07 remains the latest complete
cumulative checkpoint. The native harness checks the
required strict JSON symbols and process/lock headers before conversion/build.
Ordinary mixed/dynamic decoding remains unsuitable for schema consumers; no facade
weakening, target switch or dropped CH08 checks are authorized.

Authored repairs use supported integer-to-text concatenation, a private enum-kind
field with read-only accessor, explicit typed Storage receivers, explicit nullable
lookup, a qualified type name hidden by an inherited field, and an unpromoted
constructor field so the base constructor stays first. These follow the portable-PHP
skill; the JSON prerequisite is now proved, and final cumulative revalidation remains.
The harness is extended to compare and execute partitioned namespace/constants/enum
projects with independent enum assertions; that native leg now passes in the
focused post-repair run above.
The stale tokenizer byte expectation now includes the CH08 namespace separator;
the complete tokenizer suite passes without dropping rejection assertions.

After attempt 9 repairs, six focused PHP suites pass (tokenizer, namespace bodies,
namespaces, source constants, enums, cleanup), under
`/tmp/my-try-ch0809-final-php-20261006/`. The native-cache regression also passes in
126.79 seconds under `/tmp/my-try-ch0809-cache-recheck-20261006/`, covering real
generated-program builds and cache lifecycle from the PHP-hosted builder, not a
native compiler execution. The combined sweep's newer partial outcome is recorded above.

After these source repairs, the focused enum, namespace and constant runners pass:
20/7/10 PHP suites respectively and 24/24/10 generated programs; namespace tokenizer
and parser conversion also pass. Evidence is under
`/tmp/my-try-ch0809-portable-{enums,namespaces,constants}-20261006/`.
These overlapping suites are not 37 distinct suites and are not native compiler proof.

## Latest native checkpoint — 2026-10-06 Chapter 07

The worktree based on `0aab4b9f6f1ea99869c335dc0de0fbc4cb281686`, with the authored
portability and harness adaptations below, passes native compiler construction,
PHP/native C++ byte parity, generated-program execution and rejection/recovery.
The source and harness manifests identify the tested overlay, not the base alone:
143 authored compiler/proof files (excluding the generated driver), 98 harness files.

The **12-job, PCH-enabled** run passes:

- 1,109 source comparisons/executions, including 36 reference and 24 ownership cases.
- 526 supplementary value/error/state probes, two control-flow error probes and two
  ownership identity/destruction probes with PHP/native base-output parity.
- 376 rejection/recovery cases, including 90 reference and 88 ownership cases;
  all 15 floating-spelling assertions.
- 23 focused host PHP suites, separate emission/type and control-flow incremental
  proofs, repeated compilation/recovery and the final incremental native rebuild.

Portable adaptations preserve existing contracts: initialize nullable fields to
null and negative sentinels in constructors; select enum defaults through named
entry methods delegating to one shared worker; compute ownership capabilities in
a typed helper so the base-constructor call stays first. The cycle checker's dense
scratch stack now appends new slots and replaces only existing slots, rather than
depending on PHP indexed-array growth. No runtime or legacy generator patch was
needed. The harness now exports Chapter 07 source/rejection cases and lifetime probes.

Attempts 1–4 stopped during conversion; attempt 5 exposed the base-initializer issue.
Attempt 6 built the compiler, then found 17 reference-related native failures from
the scratch-stack write. After repair, a focused 37-case native output comparison
passed, followed by the full attempt 7 in **818.924 seconds**, reusing build objects.
This is an observed run, not a clean-build benchmark. A separate 12-suite PHP check
also passed after the initial portable adaptations.

Evidence: `/tmp/my-try-ch07-native-20261006/summary.json`, `source_hashes.json`,
`harness_hashes.json`, `candidate.json`, journals and `logs` through `logs-7`;
focused checks under `/tmp/my-try-ch07-reference-repair-20261006/` and
`/tmp/my-try-ch07-portable-20261006/`. Reproduce with the standard native command
and `--no-stan --types-only --jobs 12`; `--resume` reuses that proof workspace.
CLI fallback was used after sandbox FPM socket denial. Existing compiler warnings
remain; LLVM and legacy STAN were skipped. Chapter-specific incremental edit proofs
remain host PHP evidence except for the explicitly named portable control-flow
and generic repeated-compilation/recovery checks. Chapter 15 features remain deferred.

## Earlier native checkpoint — 2026-10-05 Chapter 06

The worktree based on `6553e6aedfcdf178f5e616ba7c2d46b28808f46c`, with the authored
source, target-tooling and proof repairs below, passes conversion, native compiler
construction, PHP/native C++ byte parity and generated-program execution. All 132
compiler/proof source hashes (excluding the generated driver) and 92 recorded harness
hashes match the tested checkout. `candidate.json` identifies the target overlay;
the base revision alone is not the full tested identity.

The **12-job, PCH-enabled** run passes:

- 1,049 source comparisons/executions: 912 S2S, 130 control-flow and seven JSON cases.
- 526 supplementary state/value/error probes and two control-flow error probes.
- 198 rejection/recovery cases and 15 floating-spelling assertions.
- 19 focused host PHP suites, separate emission/type and incremental control-flow
  proofs, repeated compilation/recovery and the final incremental native rebuild.

Authored portability repairs add explicit Storage construction types, stable keyed
lookup locals, non-keyword/non-reused locals, a checked function-declaration cast and
an explicit enum selector local. The assignment-fact family now exposes the truthful
`destination_type()` query, consumed by both write emitters, making checked native
specialization access possible without a dummy virtual hook or polymorphizing every
expression fact. No language semantics or deferred Chapter 15 capabilities changed.

Two target-tooling defects were repaired at their owners: generated header ordering
now accounts for multiple/virtual bases and prioritizes base/enum definitions over
cyclic handle references; enum switch lowering consults the existing project type-kind
catalog rather than only local enum declarations. The converter itself required no
new syntax rule. Focused native cross-file enum, header-order and broader project
header-pack regressions pass. Stale model proofs now measure application-count deltas
after runtime function installation and expect dynamic's current tagged value storage.

Attempts 1–4 stopped during conversion; attempts 5–9 exposed native build errors.
Attempt 10 first built the compiler and exposed stale host proof expectations;
attempt 11 exposed remaining absolute-count assertions. Attempt 12 passed the full
checkpoint in **621.944 seconds**, reusing native objects. This is an observed run,
not a clean-build benchmark. Generated fixtures now use the shared PCH helper and
link the JSON module; `--no-pch` remains available for include-completeness checks.

Evidence: `/tmp/my-try-ch06-native-20261005/summary.json`, `source_hashes.json`,
`harness_hashes.json`, `candidate.json`, journals and `logs` through `logs-12`.
Tooling proofs: `/tmp/my-try-native-tooling-final-20261005/` and
`/tmp/my-try-external-enum-20261005-3/`. Reproduce with the standard native command
and `--no-stan --types-only --jobs 12`; add `--resume` only to reuse an existing proof
workspace. The runner used CLI fallback after sandbox FPM socket denial. LLVM and
legacy STAN remain skipped, and existing native warnings remain. The native incremental
proof is the stated control-flow scope; other chapter-specific recovery tests retain
their separately identified PHP-suite evidence.

## Earlier native checkpoint — 2026-10-04 nullable and Result slices

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
- Dense vector scratch stacks append when creating a new slot and use indexed
  assignment only to replace an existing slot. PHP indexed array growth is not a
  native vector contract; the reference-cycle checker keeps its active length
  separate from allocated scratch entries.
- Keep base-constructor calls first; compute their arguments through typed helpers
  when setup would otherwise precede the call. Negative integer field sentinels
  use explicit constructor assignments; nullable optional fields initialize to null.
- Enum-valued method defaults remain outside the converter subset. Use named entry
  methods that select the default and delegate to one explicitly parameterized worker
  (`prepare_for_consumer`, `decide_value`); do not make required enum contracts nullable
  solely to bypass the conversion limit.
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
