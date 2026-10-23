# Portability status and evidence
Doc Status: supporting

PHP behavior, conversion to PHS, native compiler execution and generated-program
execution are separate claims. The historical bounded native checkpoint below passes
all four for its pinned revision; accepted-backlog changes are not a new native
compiler rebuild. That focused gate covers retained reference advisory PHP/native parity;
the earlier gate covers native prelowering and accounting. The preceding
O2 ordinary gate covers Chapter 14, as detailed below. The historical
cumulative checkpoint covers the implemented Chapter 05–11 slices, including
registered JSON, separated mixed/dynamic families, references, ownership and
partitioned namespace/constant/enum programs, bounded class/struct behavior,
inheritance, traits, interface dispatch and shared callable variance.
The catalog's bounded closeouts and Chapter 15 deferrals still
bound the supported language surface.
This does not establish exhaustive language, lifetime, incremental or performance coverage.

## Accepted-backlog bootstrap checkpoint — 2026-10-23

The latest [facade qualification checkpoint](../../../../specs/planning/results/compiler_bootstrap_backlog_2026_10_23/facade_qualification/README.md)
roots global mapped source calls and preserves their root in legacy C++ emission.
PHP/legacy/both-v0.2 lookup and cache proofs pass, as does the full existing portable
string-splitting regression. Fourteen unresolved facade-function diagnostics disappear;
none are added. Bootstrap takes 214.69 s / 453.8 MiB RSS / 442.5 MiB PHP heap.
AST-01 remains first, with no fatal/OOM, emission or native compiler rebuild.

The earlier [shared-receiver checkpoint](../../../../specs/planning/results/compiler_bootstrap_backlog_2026_10_23/reader_prelowering/README.md)
closes the JSON reader prelowering gap. All fourteen methods now execute on legacy
and both v0.2 modes. An unrelated provider proves null-check ordering and receiver
retention during argument-side replacement. Existing runtime-handle regressions pass.
Bootstrap takes 199.64 s / 445.0 MiB RSS / 434.5 MiB PHP heap, with unchanged
diagnostic texts and AST-01 first. No fatal/OOM, emission or native compiler rebuild.

The earlier [JSON reader checkpoint](../../../../specs/planning/results/compiler_bootstrap_backlog_2026_10_23/json_reader/README.md)
adds the shared factory and fourteen method bindings. Legacy/ordinary v0.2 execute
the protocol; existing PHP/native facade and reader/document regressions pass.
Concrete prelowering remains blocked on shared runtime member argument sequencing.
Bootstrap takes 200.67 s / 444.8 MiB RSS / 434.5 MiB PHP heap. The unresolved reader
type diagnostic disappears, with no new diagnostic text. AST-01 remains first;
there is no OOM/fatal, emission or native compiler rebuild.

The earlier [output-binding checkpoint](../../../../specs/planning/results/compiler_bootstrap_backlog_2026_10_23/output_bindings/README.md)
registers eleven existing publication/digest leaves. All execute on legacy; ten
execute in ordinary/prelowered v0.2. Read extraction remains blocked by nullable's
missing result_payload capability (NULL-02), with its exact nested return preserved.
Bootstrap takes 191.17 s / 431.2 MiB RSS / 422.5 MiB PHP heap. Eleven unresolved
function diagnostics disappear; the expected capability diagnostic is added.
AST-01 remains first, with no OOM/fatal, emission or native compiler rebuild.

The earlier [constructor-promotion checkpoint](../../../../specs/planning/results/compiler_bootstrap_backlog_2026_10_23/constructor_promotion/README.md)
uses converter-emitted `set:` inputs and PHP promotion for switch_label's required
enum. Root-class construction/order/failure and typed-container defaults pass PHP,
legacy native and both v0.2 modes. Bootstrap takes 190.83 s / 430.2 MiB peak RSS /
420.5 MiB PHP heap; seven property-requires-set diagnostic texts disappear and none
are added. AST-01 remains first; no OOM/fatal or emission. Promotion with explicit
parent construction remains an open portable boundary, and no native compiler
rebuild is claimed.

The earlier [empty-only provider checkpoint](../../../../specs/planning/results/compiler_bootstrap_backlog_2026_10_23/empty_provider/README.md)
adds explicit registered `[]` construction through existing provider factories.
An unfamiliar provider and Storage-family fixtures pass ordinary/prelowered native
execution, fresh omitted defaults, admission and retained recovery. The measured
PHP-hosted bootstrap takes 198.90 s / 432.3 MiB peak RSS / 422.5 MiB PHP heap;
conversion takes 4.60 s. It still stops at AST-01, with no diagnostic text changes
from the prior empty-expression checkpoint. No OOM/fatal, emission or native
compiler rebuild. These results do not close the remaining accepted backlog.

The typed empty parameter-default slice now passes PHP, the current legacy S2S route
and v0.2 ordinary/prelowered native fixtures. The converter preserves explicit PHP
array annotations; both routes reuse their existing typed construction paths. A
counted generic provider also passes v0.2 default factory invocation tests.

The [measured checkpoint](../../../../specs/planning/results/compiler_bootstrap_backlog_2026_10_23/README.md)
converts/assembles 269 PHS files (1,958,956 bytes). Conversion takes 4.67 s / 189.2 MiB
peak RSS. PHP-hosted bootstrap takes 193.79 s / 431.5 MiB RSS, with 422.5 MiB peak PHP
heap. Sync/parsing completes at 4.081 s; preparation fails first on the known AST-01
nonvirtual inherited redeclaration. No fatal/OOM, emission or native compiler rebuild.
The accepted backlog is active; remaining diagnostics are not repaired by this slice.
Older exclusions below describe their historical checkpoints.

## Remote integration — 2026-10-22

The merge of `origin/v0.2/develop` through `96c86d3f6` preserves the local
source exception hierarchy, Storage payload transport and object identity keys
alongside the incoming broader `finally` storage and mixed/dynamic index paths.
The incoming handle/null presence transport passes the focused prelowered
controls fixture. That defect is therefore no longer an outstanding implementation
blocker, although full compiler scope lookup and self-bootstrap have not been
rerun after this merge. The AST/trait contract and null-return ownership blockers
from the checkpoint below remain unaddressed; Storage_Cursor widening stays out
of scope.

Merge validation passes the exception hierarchy, Storage and portable object-key
suites, selected cold/warm `finally` and async cases, handle/null controls,
mixed/dynamic/hash iteration and retained field-owner cases, and error-condition
transport. Results are under `/tmp/merge-20261022-*`. The async-handler-finally
sanitized build is unverified: its layout probe hits LeakSanitizer's ptrace
restriction in this environment, including a retry with outer leak detection
disabled. Its ordinary cold/warm prelowered builds and executions pass.

## PHP-hosted bootstrap after qualified-name dispatch — 2026-10-21

The latest retry uses `46d9e35e` plus the PHP qualified-name refactor.
`collected_definition::qualified_name()` is abstract. Functions, constants and
types call the protected `scope_qualified_name()` helper containing the unchanged
namespace algorithm. Methods preserve their `Type::method` implementation. This
uses existing abstract-method dispatch without changing language semantics.

`tests/portability/qualified_name_dispatch.py` passes actual parsed-name checks in
PHP and projected dispatch execution through PHP, v0.1 native and both v0.2 modes.
The projection extracts real dispatch methods and supplies controlled scope/accessor
inputs; it does not prove native execution of the full scope lookup or repair its
known handle/null comparison issue. Existing namespace, collected-role and
incremental parse/collection checks pass. No native compiler rebuild is claimed.

Conversion produces 269 assembled PHS files from 259 PHP files (1,926,457 bytes).
Isolated bootstrap takes **190.40 seconds**, peak RSS **418.8 MiB**, peak PHP heap
**410.5 MiB** under a 4 GiB limit. Parsing finishes at 4.175 seconds. It stops during
preparation without PHP fatal/OOM or C++ emission. Timing includes failure-owner
capture in the same process; these are partial-run costs, not full bootstrap costs.

The `collected_method` type-contract failure is gone. The first failing type
contract is now `loop_node`: its `Preparation_Facts` trait supplies
`clear_preparation()`, redeclaring the nonvirtual default on `ast_node`. The first
reported diagnostic remains `Nonvirtual inherited member redeclaration is deferred`,
propagated through `Symbol_Collector::previous_function`. This AST/trait contract
and null-return ownership failures are left unchanged.

Storage_Cursor widening and the known prelowered handle/null comparison defect
remain excluded. Readonly enforcement remains
[CH15-READONLY-FIELDS-01](../catalog/15_blocking_debt.md#readonly-instance-fields--ch15-readonly-fields-01).
The [checkpoint](../../../../specs/planning/storage_bootstrap_2026_10_21.md) records
snapshot, measurements and limitations. This is not a successful self-bootstrap;
the native checkpoints below describe earlier revisions.

## Native prelowering execution — 2026-10-20

The native driver can now select the public `prelower_templates` setting and run
production publication/build owners. Use `tools/native_validate.py` with
`--prelower-accounting --compiler-optimization O2 --no-stan --types-only --no-pch`
plus the normal checkout/revision/results arguments. The compiler executable is
O2 without debug information; accounting, middleware and application runtime
objects remain O0. The compiler's own PCH is independent of the strict application
PCH prohibition. The validation-only compiler proxy records actual Clang arguments
because the production release preset otherwise selects O3.

[Native execution evidence](../../../../specs/planning/results/native_prelower_accounting_2026_10_20/README.md)
records the bounded results and source/executable hashes. The proof compares PHP
and native artifact bytes/manifests, repeats concrete preparation/emission, checks
explicit unsupported-case and PCH rejection with publication recovery, and checks
accounting behavior, full application closure and warm reuse. It does not expand
runtime coverage or establish a compiler-performance benchmark.

Native execution exposed missing-key hash reads through `??` in specialization
artifact assembly. Explicit initialization and guarded optional reads repair that
source-portability issue in the existing owner; no fallback or new registry is added.

## Native refresh after prelowering — 2026-10-20

Candidate base `2a302b1a` plus authored portability repairs now passes conversion
of 233 compiler/proof PHP files and native compilation. The bounded Chapter 14
gate passes 14 byte-identical PHP/native artifact comparisons, 28 generated
program executions with/without PCH, 27 standalone header checks, native
publication parity and failure/recovery, five incremental build scenarios and
seven runtime-module build scenarios. The final compiler rebuild reports no work.

[Retained evidence](../../../../specs/planning/results/native_compiler_refresh_2026_10_20/README.md)
includes the result and source/harness hashes. Raw attempts remain under
`/tmp/prelower-native-compiler-20261020/`; attempt 13 passes. Repairs use explicit
typed collection/nullable/scope boundaries and declaration-local enum reflection.
Execution also exposed overloaded-boolean eager evaluation of a nullable guard;
explicit branches preserve PHP behavior. No prelowering fallback is added.

That earlier refresh includes the prelowering implementation, but its native
execution gate exercises ordinary emission. Native prelowering execution is
covered by the later focused gate above. STAN and parked LLVM were skipped; build warnings remain. The
separate O0 prelowering regression uses the PHP-hosted compiler. See the evidence
note for its result and the exact build configuration.

## Chapter 14 native gate — 2026-10-16

The [bounded closeout](../catalog/14_output_runtime.md#bounded-closeout-and-native-gate--2026-10-16)
passes conversion of 215 compiler/proof PHP files to PHS and native compilation,
14 multi-file artifact parity comparisons, 28 PCH/no-PCH executions and
27 standalone header checks. The converted native publisher and native builder
also pass publication parity, no-op/body-edit reuse, failed-publication repair,
successful shadow-warning delivery and optional provider source/definition/library
invalidation (5 incremental and 7 module build/execution scenarios).
The final no-change native compiler build passes.

Evidence: `/tmp/scpp-ch14-native-20261016/summary.json`, `logs-5/` and source/harness/
runtime-catalog/candidate hashes plus the executable hash. Base `29efc8ad` has the
retained validation overlay captured by those hashes. The converted driver replaces
the normal entrypoint. STAN and LLVM were skipped explicitly; PHP used CLI. Warnings
remain. The chapter page records the bounded scope and explicit deferrals; this is
not a cumulative CH01–14 sweep or expanded CH12 native certification.

## Chapter 13 native gate — 2026-10-16

The [bounded Chapter 13 closeout](../catalog/13_errors.md#bounded-closeout-and-native-gate--2026-10-16)
now passes compiler PHP-to-PHS conversion and native build, plus all 68 chapter
programs with byte-identical PHP/native output and successful generated execution,
52 rejection/recovery comparisons, four native edit/repair/fresh proofs and three
PHP production CLI failure checks. The final no-change native rebuild passes.

Evidence: `/tmp/scpp-ch13-native-20261016/summary.json` and `logs-5/`, with source,
harness, runtime-catalog and candidate hashes plus the executable hash. Candidate
base `b0e1d278` includes a portability/proof overlay captured by those hashes. All
215 compiler/proof PHP files converted. Resumed builds included the added edit-proof
driver and corrected the baseline emission proof to expect the implemented outer
exception handler. Native fixtures additionally required explicit nullable branches in
finally preparation/emission and a count-based generic-parameter check in metadata
registration. Those authored PHP repairs preserve the existing language contract.
The native validation driver is substituted for the normal entrypoint.
Legacy STAN and LLVM were skipped explicitly; the shared runner used CLI and 12 slots.
Generated fixtures ran without PCH. Build warnings remain.

This is the Chapter 13 focused native evidence. It does not repeat the historical
cumulative checkpoint below, prove every incremental/lifetime combination, or close
CH12's broader native coverage gap. Chapter 13 deferrals remain explicit in Chapter 15.

## Workspace smoke — 2026-10-16

Revision `8c7ac0e5b10faf8217cb31916da3d988d4e86ddc` passes a fresh native compiler
build using the existing validator's source snapshot/conversion/framework/build
setup, with `--no-stan` and parked LLVM excluded. Build time was 421.764 seconds;
the final no-change build took 1.188 seconds. Three focused proofs (`s2s-proof`,
`types-proof`, `control-proof`) produced byte-identical PHP/native output and their
generated programs exited with expected codes 10, 0 and 8. The control proof includes
its retained identity and dependency failure/retry/repair checks. Total elapsed time
was 431.715 seconds; this is an observed run, not a benchmark.

Evidence: `/tmp/scpp-v02-workspace-smoke-20261016/summary.json`, `commands.json`,
`source_hashes.json`, `candidate.json` and `logs/`; focused orchestration script:
`/tmp/scpp-v02-workspace-smoke.py`. These local artifacts were available during CH12
reconciliation. The generated validation driver intentionally replaces `main.php`;
other snapshotted compiler/proof PHP files matched the checkout. FPM socket creation
was unavailable and the shared runner used its CLI fallback. Native build warnings
remain; this is not warning-clean certification.

This run rebuilds the compiler itself but does not sweep CH12 fixtures. The
[CH12 closeout](../catalog/12_closures.md#bounded-closeout-and-current-handoff--2026-10-16)
combines its recorded focused feature proofs with this limited workspace proof;
complete chapter-native parity, rejection and incremental coverage remains unclaimed.
The older 2026-10-10 result directories were absent here, so their recorded summaries
below were reviewed without rechecking their raw logs or overlay hashes.

## Native lowering-report checkpoint — 2026-10-16

The [report checkpoint](../planning/native_lowering_report_20261016.md) rebuilds
`779f6f39` plus a declared-type correction at O2. Six accounting invocations cover
reports off/on at O0/O1/O2; both report modes match PHP and all 292 generated artifacts
match the previous checkpoint. The report uses a required `collected_definition`
local for its qualified name, preserving the method's actual declaring type.
The experiment driver uses JSON requests; this is not production native CLI coverage.
No new application benchmark or broad compiler regression was performed.

## Native Ninja/accounting checkpoint — 2026-10-16

Base `03cf89a0` plus the authored portability fixes in the accompanying
[measurement report](../planning/native_ninja_accounting_20261016.md). All 214 PHP
compiler/driver files convert; the compiler and Ninja worker build natively at `-O2`
with existing debug information and `--no-stan`. New Ninja code uses a non-keyword
byte-local name and supported integer-to-text concatenation. The measurement driver
passes an explicitly typed empty vector. The publication journal uses guarded typed
map reads instead of missing-key coalescing that the legacy S2S lowers through `at()`.

Three full/unchanged/body-edit trials pass on the 528,088-byte accounting fixture.
Each full native publication matches all 292 PHP reference artifacts; the application
passes 94 CLI cases plus its self-tests. Native publication addition/removal and the
existing PHP publication/recovery fixture pass. Detailed timings, memory observations,
source hashes, executable identity and the archived harness are linked from the report.
No PHP publication workaround is used. This bounded native integration proof does not
claim a repeat of the comprehensive language, retained incremental or MT checks below.

## Native linker configuration checkpoint — 2026-10-16

Base `86aed366` plus the shared linker configuration slice is rebuilt at `-O2 -g`
with `--no-stan` and executed on dev002. The [acceptance report](../planning/native_linker_configuration_20261016.md)
records native source/binary identities, full/unchanged/body-edit and eight-edit
accounting measurements, and 14 native Ninja linker-configuration/recovery cases.
The 26 focused real-tool cases cover both consumers with PHP-hosted orchestration;
that is distinct from native execution of the Ninja consumer. The production recipe
selects Mold directly, and link-only tool/configuration changes preserve objects/PCH.
No new whole-language, retained incremental or compiler-MT coverage is claimed.

## Native checkpoint — 2026-10-10

Candidate base `e6dad0db97ce1b629608bb772d6529917a45b151` plus authored portability
repairs; evidence `/tmp/my-try-native-20261010/`. Conversion first passes on attempt
16 (211 PHP files). Attempt 18 builds/links the native compiler (407.204s with
reused objects). Attempt 20 passes all 23 host prerequisites, native
emission/type/control-flow proofs, 1,114 source comparisons/generated executions,
44 partitioned projects, 997 supplementary programs, two control-flow error and
two ownership-lifetime probes, 366 rejection/recovery comparisons and the final
incremental native rebuild. Its elapsed time is 1,858.695s, including a 17.825s
native rebuild with reused objects; this is not a cold-build benchmark.
The run uses `--no-stan --types-only --jobs 12` and preserves each attempt's
logs; a keep-going Ninja diagnostic pass gathers independent C++ failures together.

Repairs use the existing portable profile: explicit generic construction and nullable
annotations, typed collection boundaries, base construction before field assignment,
checked nominal narrowing, explicit null branches and block-owned integer selection.
Named callable nodes own their capture-free iterator entrypoint while sharing the
same iterator with closures. Backend comma-list rendering and metadata byte splitting
avoid unregistered host-only PHP helpers. No language semantics are intentionally changed.
Native execution also exposed a borrowed foreach key erased before use in the
callable-work queue. The scheduler now retains the selected work handle before
removal; the native type proof passes without changing queue ownership or policy.

Six focused PHP suites pass in `/tmp/my-try-native-20261010-portable-validation/`:
closure model/behavior, runtime catalog/build contract, constructed types and indexing.
Generated PHP++/C++ remains diagnostic output, never the repaired source.
Resource reports from instrumented attempts record maximum per-process RSS, not
the sum of concurrent compiler-process memory. Attempt 20 records 637,472 KiB.

An additional accounting check exposed quadratic portable `json_quote` work and
timed out after 240s. The approved framework repair validates UTF-8 once, scans
bytes and joins escaped chunks once, preserving exact JSON spelling/error behavior.
`tests/portability/json_quote.py` passes 15 independent PHP/native parity cases,
including malformed UTF-8 and 2,163,442 output bytes, in 1.235s native execution.
Evidence: `/tmp/my-try-json-quote-20261010-2/`; repair commit `35bff901`.

The cumulative run above used the pre-optimization helper. The repaired helper is
then installed and the native compiler rebuilt in 12.639s. Follow-up evidence under
`/tmp/my-try-json-followup-20261010/` records both executable hashes and checks
emission/type/control-flow parity plus closure and indexed-write program execution.
Accounting artifact generation now takes 2.331s natively (host PHP: 4.311s) and
matches the PHP artifact bundle. Generated C++ compilation/linking takes 40.250s
with fixture PCH; execution passes all 998 checks in 0.016s. The final incremental
compiler build passes in 1.216s. The follow-up summary records a complete pass.
These targeted post-repair checks supplement, rather than claim a repeat of, the
full cumulative sweep. Both runs bypass legacy STAN and parked LLVM execution.

<a id="latest-native-checkpoint--2026-10-08-chapter-11"></a>

## Earlier native checkpoint — 2026-10-08 Chapter 11

Candidate base `65d96f7e6bd0af0c6a5420eb49447266d4c1fcd4`, with the current
portability overlay. Evidence: `/tmp/my-try-ch11-native-20261008/`.
Attempt 9 first built the native compiler. Attempt 10 passes the complete cumulative
frontend/C++ validation: 23 host suites; native emission/type/control-flow proofs;
1,111 native/PHP source comparisons and generated executions; 44 partitioned
namespace/constant/enum projects; 997 supplementary probes; two control-flow error
and two ownership lifetime probes; 370 rejection/recovery comparisons; repeated
compilation and the final incremental native rebuild.

The PCH-enabled, 12-job attempt took **2,357.066 seconds**, including a
**307.944-second** native build with reused build state. This is not a clean-build
benchmark. Evidence includes `summary.json`, source/harness hashes, candidate
metadata and `logs-10/commands.json`. PHP-FPM was unavailable; the supported CLI
fallback was used.

Repairs preserve the existing language contracts:

- Explicit integer arguments/default initialization, compact-to-wide token offsets,
  typed Storage receivers, method-node views and nullable-to-weak boundaries.
- Fixed exception-field mapping and rooted declared-type lowering in the legacy
  toolchain. The materialization signal predicate accepts the exception base and
  still compares object identity. Focused PHP/native identity and cross-file import
  proofs pass under `/tmp/my-try-exception-identity-01/` and
  `/tmp/my-try-ch11-import-regression-01/`.
- The syntax-purity fixture excludes independent lifecycle work alongside other
  semantic work records; it still compares syntax and scopes.
- Dedicated CH11 native comparison exposed copied trait traversal maps and unsafe
  concurrent source-type registry writes. Traversal state is now invocation-owned;
  source-type registration uses the existing parse/publication batch mutex.

The first dedicated CH11 attempt (`/tmp/my-try-ch11-records-native-01/`) passed
518 of 523 source comparisons; two trait-diamond and three intermittent multi-file
failures prevented a pass claim. All five now pass 20 repeated native comparisons
each (`/tmp/my-try-ch11-native-stability-01/`). The final dedicated run under
`/tmp/my-try-ch11-records-native-02/` passes all 14 host suites, **1,569 native
artifact comparisons** (523 successful source snapshots, three independent runs
each) and **518 generated-program executions**. Its summary records the executable
SHA-256; command journals retain individual timings.

The dedicated runner exercises native successful compilation and repeated no-op
preparation, not every CH11 rejection or edit/recovery assertion. Those chapter
assertions remain host checks; the cumulative native driver supplies its separately
listed rejection/incremental coverage. Repeated scheduling checks are not an
exhaustive concurrency or sanitizer proof. This clears the requested bounded CH11
native gate without enabling deferred CH15 extensions.
Legacy STAN and LLVM remain explicitly excluded (`--no-stan --types-only`).

## Latest native checkpoint — 2026-10-07 Chapter 10

Candidate base `a22db14484425425f492809c00d0c04d8367fd34`, with working-tree
portability/test-harness repairs. Evidence: `/tmp/my-try-ch10-native-20261007/`.
Attempt 16 passes the complete requested cumulative frontend/C++ checkpoint:

- Native compiler build, emission/type and control-flow incremental proofs.
- 23 host suites, 1,111 native/PHP source comparisons and generated executions,
  including 15 floating-spelling assertions.
- 44 partitioned namespace/constant/enum projects.
- 997 supplementary probes, two control-flow error probes and two ownership
  lifetime probes.
- 370 rejection/recovery comparisons, repeated compilation and the final
  incremental native rebuild.

Elapsed time: **1,544.541 seconds**, with 12 jobs, fixture PCH and reused compiler
objects. This is not a clean-build benchmark; the dedicated CH10 run overlapped.
Legacy STAN and LLVM execution were explicitly skipped (`--no-stan --types-only`).
PHP-FPM was unavailable; the cumulative runner used its supported CLI fallback.
Evidence includes `summary.json`, source/harness hashes, candidate metadata and
`logs-16/commands.json` in the results directory above.

Initial conversion repairs make helper arguments explicit, replace unsupported
postfix decrement, initialize a negative field default in its constructor, and
rename C++ keyword/type-hiding locals. Native compilation additionally requires an
explicit nullable-to-owner boundary before weak assignment, portable modifier
selection, and a meaningful polymorphic `prepared_parameter_default::result_type`
query for checked narrowing. No user-language contract is changed.

Native execution also exposed constructor backlink comparisons that bypassed
`weakref_get`. Declaration preparation now acquires that weak link explicitly
before deciding whether to synthesize data inputs or permit required properties.
Six obsolete CH03 record-condition rejection fixtures were removed: inline-record
truthiness is now intentional and covered by CH10 collection-probe tests.

The separate `tools/native_records.py` runner exports successful CH10 source
programs from 15 dedicated suites, compares native/host artifacts, then executes
the generated fixtures. Rejection and incremental assertions inside those PHP
suites remain host-only; do not claim their native coverage. Initial export proof:
`/tmp/ch10-native-host-exports/` (16 PHP suites including enum model), with 426
successful source snapshots and 428 generated execution fixtures.

The dedicated CH10 rerun passes under `/tmp/my-try-ch10-records-native-2/`:
all 426 native/PHP artifact comparisons and all 428 generated-program executions.
Its `summary.json` records the native executable hash. Together with cumulative
attempt 16, this clears the CH10 native gate. Deferred Chapter 11/15 capabilities
and native execution of CH10-specific incremental/rejection assertions are not
claimed by this proof.

Repair history: attempts 1–8 addressed conversion, 9–13 native compilation,
14–15 stale host-harness expectations, and 16 passed. The first separate CH10
comparison run exposed the weak-constructor issue; its second run passed.
Parked LLVM files received only the typed callable-accessor repair needed to
compile the compiler; no LLVM semantic development or execution was performed.

## Latest native checkpoint — 2026-10-07 Chapter 08/09

Candidate base `a377058cb7dbd8979dc0a82ef5300f12f031f055`, with the recorded
test-harness overlay, passes the complete requested frontend/C++ native validation.
Attempt 15 under `/tmp/my-try-ch0809-native-20261006/logs-15/` passes:

- Native compiler construction, emission/type and control-flow incremental proofs.
- 23 host PHP suites and 1,111 PHP/native output comparisons plus generated-program
  executions, including all 15 floating-spelling assertions.
- 44 separately compiled CH08/09 projects: 13 namespace, 7 source-constant and
  24 enum projects, with exact host/native artifacts and independent outcomes.
- 997 supplementary value/error/state probes, two control-flow error probes and
  two ownership lifetime probes with host/native base-output parity.
- 376 rejection/recovery cases, repeated compilation and the final incremental
  native rebuild.

The 12-job, PCH-enabled attempt took **1,589.296 seconds**, reusing native build
objects. This is an observed validation run, not a clean-build or PCH benchmark.
PHP-FPM was unavailable in the sandbox; the runner used its supported CLI fallback.
Legacy STAN and parked LLVM were explicitly skipped (`--no-stan --types-only`).
Evidence: `summary.json`, `source_hashes.json`, `harness_hashes.json`, `candidate.json`
and command journals in the same results directory. The artifact transport helper
is now included in the harness hash manifest.

Attempts 13–14 exposed test instrumentation dependencies, not a reason to merge
production translation units. Inserted native assertions can call declarations
unused by the original entry, so the shared probe compiler includes bundle headers
in its instrumented entry. Uninstrumented roundtrips and production bundles keep
their precise dependencies. A data-construction assertion also needed its own
`data_initializer.hpp` include; its fixture group now supplies it explicitly.
No compiler or language semantics changed in these harness repairs.

Focused evidence under `/tmp/my-try-probe-includes-20261007/` proves eight generated
program runs with/without PCH, including inserted and appended assertions.
`/tmp/my-try-probe-roundtrip-20261007/` proves 26 separate-unit program runs,
30 standalone headers and three transitive-dependency checks.
`/tmp/my-try-data-probe-20261007/` proves 48 data programs and one runtime-input
lifetime check without PCH. Four representative failed probes also pass under
`/tmp/my-try-probe-replay-20261007-2/`; its first replay omitted the runtime object
and is not a successful native proof.

This supersedes the partial checkpoints below and clears the requested CH08/09
native gate before CH10 discussion. Dedicated publication/cache lifecycle proofs
remain scoped as documented: compiling those owners into the native compiler is
not itself a native execution proof of every cache/publication workflow.

## Chapter 08/09 native progress — 2026-10-06 partial

Candidate base `3d47e9288bde404720fcba726872a5af0b6af357`, working-tree overlay,
evidence `/tmp/my-try-ch0809-native-20261006/`. This historical repair sequence is
superseded by the complete October 7 checkpoint above.
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

At this point the complete harness still needed to run with the final parser/driver
repairs, including supplementary probes and rejection/recovery, which attempt 12
never reached. Those separate results were not a cumulative pass; attempt 15 above
now supplies that full post-repair checkpoint. The native harness checks the
required strict JSON symbols and process/lock headers before conversion/build.
Ordinary mixed/dynamic decoding remains unsuitable for schema consumers; no facade
weakening, target switch or dropped CH08 checks are authorized.

Authored repairs use supported integer-to-text concatenation, a private enum-kind
field with read-only accessor, explicit typed Storage receivers, explicit nullable
lookup, a qualified type name hidden by an inherited field, and an unpromoted
constructor field so the base constructor stays first. These follow the portable-PHP
skill; the JSON prerequisite and final cumulative revalidation are now proved above.
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
native compiler execution. The combined sweep's complete outcome is recorded above.

After these source repairs, the focused enum, namespace and constant runners pass:
20/7/10 PHP suites respectively and 24/24/10 generated programs; namespace tokenizer
and parser conversion also pass. Evidence is under
`/tmp/my-try-ch0809-portable-{enums,namespaces,constants}-20261006/`.
These overlapping suites are not 37 distinct suites and are not native compiler proof.

## Previous native checkpoint — 2026-10-06 Chapter 07

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
  when setup would otherwise precede the call. Ordinary integer fields now support
  [signed decimal defaults](../../../../specs/portability/signed_integer_fields.md);
  audit initialization ordering before moving assignments across base construction.
  Nullable optional fields initialize to null.
- [Integer decrement](../../../../specs/portability/decrement.md) is now admitted
  through the ordinary expression-token path. Earlier postfix-decrement repairs
  describe historical converter limits; only the two for-condition counters have
  migrated in this slice.
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

## Focused reference advisory parity — 2026-10-21

[V-D15](../planning/collection_advisories.md) builds the current compiler at O2 and
compares exact structured-warning rendering through retained edits and recovery,
using default prelowering. Run `tools/native_validate.py --reference-advisories
--types-only --no-stan --no-pch --compiler-optimization O2` with the normal target,
revision and result arguments. The closeout records source hashes and portability
adaptations; this does not replace earlier broader checkpoints.
