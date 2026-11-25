# Native build adaptations for review
Doc Status: historical

Archived 2026-09-29. This records an earlier design/checkpoint, not current instructions.
See [the documentation index](../README.md) for active guidance.

Active goal: build and run the ported compiler with PHP/native parity. These are
source adaptations to the existing v0.1 toolchain, not new compiler features.

- Collection receiver locals make Storage/Keyed_Storage templates explicit across
  files. These wrappers retain shared membership; ordinary value containers are
  not treated as aliases.
- Constructor/per-invocation workers establish real initialized inputs. Public
  reusable facades retain their existing reset and failure-publication behavior.
- Parser `payload_node` accepts a required node_structure before forwarding it to
  the optional-payload constructor. This separates concrete-to-interface conversion
  from nullable wrapping without copying the payload.
- Parser `error_message` formats a string; call sites construct RuntimeException.
  This avoids returning the framework exception through a native signature whose
  global namespace qualification is lost. Error text and exception family stay the same.
- Enum dispatch uses explicit equality branches because cross-file enum switches
  emitted scalar-wrapper access and unsupported case placeholders. Diagnostic names
  use Node_Kind_Name beside the enum declaration.
- Missing required map entries use isset guards then typed reads. Optional decisions
  use branches instead of native-incompatible `?? null`/mixed ternaries. Declaration
  type and binding-declaration helpers return checked required handles.
- Scope parent traversal checks absence then uses an explicit identity-preserving
  cast to recover the required handle. Unsupported nullable object-local annotations
  were not added to the converter.
- Template transient container resets use typed empty locals before field assignment.
  An absent symbolic local uses an empty string only on the path that must reject it
  as a type mismatch (valid symbolic types are never empty); this merits review.
- Local module/file/token variables were renamed where they shadowed native record
  type names in auto initializers.

- Typed vector/hash emptiness uses count checks instead of comparing with bare `[]`,
  which lowers to a different native container type. Storage keeps `is_empty()`.
- Kind-dependent payload casts occur after a separate kind guard, avoiding eager
  evaluation through native overloaded boolean operators.
- Template formal keys and positions are captured before incrementing the parser
  position. PHP/C++ evaluation order must not change the key being inserted.

Validation completed: a fresh normal STAN-enabled build linked the native compiler.
All 142 PHP/native comparisons passed (48 valid, 94 rejected); every valid emitted
program also compiled with Clang and ran with its expected exit code. Repeated
compilation preserved earlier output handles, and every rejection was followed by
successful compilation in the same process. PHP helper/model suites passed too.

This uses PR #244 revision d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 plus the
focused toolchain fixes committed in 0c28b96e. The verified target pin is unchanged.
STAN reports zero build-blocking diagnostics, but 159 advisory errors and 33
warnings remain; this is not a clean static-analysis report. Most are unresolved
dependencies (120), including native collection types. Other categories still need
individual review rather than blanket dismissal as false positives.

The executable uses a serial test driver reading `build/native-01/request.txt`;
it is not a packaged command-line release. See `tools/native_validate.py` and the
saved `specs/planning/compiler_migration/results/my-try-native-success-01` evidence.

Token representation follow-up: offset and length now use required `uint32` field
annotations (PHP `int`, native unsigned 32-bit). The converter accepts that explicit
field shape. Protected `$_text` is initialized with both spans in the token
constructor and exposed by `text()`. No string-view representation is implemented
in this change. Callers must keep span values within 0..4294967295.

Validation: `build/native-token-uint32-01` passed all 142 PHP/native comparisons and
executed 48 emitted programs. The final private-field rename to `$_text` was then
checked by incremental native rebuild and sample parity. Required-field conversion
regressions and the portability regression suite also passed.

AST contract follow-up: the payload interface is `node_structure`.
`Syntax_Nodes` now rejects inconsistent binding optional fields/classification and
parameter reference-token presence before parser publication. Checks are local;
children are not traversed again. PHP tests enumerate 48 binding states and four
parameter states.

Three direct scope links opt into native `weak<scope>` fields: scope.parent,
block_structure.scope and collected_name.scope. Consumers explicitly acquire
through `weakref_get`; PHP's facade preserves ordinary strong references. This does
not convert every documentary weak tag or reclaim every model cycle. STAN now knows
the existing runtime weakref_get primitive. Candidate analysis reports zero blocking
diagnostics, 154 advisory errors and 45 warnings. PHP and conversion/emission tests
passed; no native build/run was performed for this change. See
`specs/portability/weak_fields.md` for the expiration/owner-lifetime contract.

Weak-scope native follow-up: renamed the function-body scope local to
`$function_scope`; `$scope` hid the native type in `checked_object_cast<scope>`.
No cast/runtime change was needed. A fresh normal build and incremental rebuild
passed all 142 PHP/native comparisons and executed all 48 valid emitted programs.
STAN reports zero blocking diagnostics, 159 advisory errors and 47 warnings.
See `specs/planning/compiler_migration/results/weak-scope-native-01` for evidence.

Historical linked AST checkpoint: abstract ast_node had one concrete subclass per kind,
optional node_structure, uint32 spans and private parent/previous/next/first-child
links plus a uint32 child position. The converter preserves literal inheritance
and accepts bounded decimal uint32 property defaults. Required signed lookup/argument
boundaries explicitly cast compact token indexes to int. Named structure child
lists remain retaining aliases; children() returns a membership snapshot.

`build/native-linked-ast-04` passed 142 PHP/native comparisons, including native
parent/sibling/position checks, and executed all 48 valid emitted programs.
STAN has zero blockers, 210 advisory errors and 78 warnings; those advisories have
not been eliminated. This checkpoint predates the final common ast_node and
specialization-owned facts. See docs/architecture/ast_layout.md for the current
representation and the final-node verification below for its native evidence.

Compiler parse-queue follow-up: each parser owns its file root scope. Compiler
publication exports root declarations under the unordered task executor's lock;
Scope_Lookup preserves shared-global lookup and duplicate rules. The native driver
uses three jobs, while PHP executes the same interface sequentially. A final join
restores retained input order before semantic preparation. No incremental policy
was introduced. See docs/lifecycle/work_queue.md and
specs/planning/compiler_migration/results/parse-queue-native-01 for the 142-case
native proof and dedicated concurrency/error-join tests. STAN retains 217 advisory
errors and 85 warnings, with zero build blockers.

Per-file frontend follow-up: discovery records paths; Tokenizer reads disk_source
files inside each worker. Compiler.exec now runs one read/tokenize/parse chain per
file, with the existing job limit and locked publication. Source_Work_Queue rejects
aliased source records and mismatched token snapshots. Standalone tokenize/parse
remain explicit stage entrypoints. The native test driver proves that an earlier
parse publishes before a later scan fails; all 142 PHP/native comparisons and 48
program executions pass. See source-pipeline-native-01 under the migration results.
STAN retains 223 advisory errors and 87 warnings, with zero blockers.

File-sync follow-up: existing file/collected_name records carry one changes field.
Candidate source bytes and syntax stay private until locked publication. Full builds
and updates share Compiler.sync; live-program preparation still reruns completely.
No toolchain/runtime patch was needed. The portable subset does not currently accept
bitwise expressions or nullable object local annotations: deletion is an exclusive
flag value, independent change bits are added once, and typed nullable lookup methods
provide optional previous records. Compiler.jobs has a zero field initializer for
the current STAN initialization check; its constructor installs DEFAULT_COMPILER_JOBS
(12) before dispatch. Native proof uses that default. See docs/lifecycle/incremental.md and
the file-sync-native-01 evidence under specs/planning/compiler_migration/results.


## Final common-node verification

On 2026-09-26, the user explicitly requested native verification after the final
`ast_node` refactor. Conversion and a normal STAN-enabled build passed on the
existing `/tmp/scpp-native-244` candidate (`unversioned-d8ddde93-overlay`); the
verified target pin and toolchain sources were not changed.

This pass found and repaired source-level portability issues:

- Publication uses the retained storage position to retrieve the previous parsed
  file, removing an unsupported nullable local annotation and redundant state.
- Parser_Run owns in-progress scopes and constructs parsed_file only when its
  required tokens, root, collection and scopes are ready. Block scopes and collected
  occurrence provenance are constructor inputs before accessors can read them.
- Block scope storage is private `scope_reference`, accessed through lexical_scope().
  This also avoids the native field/type name collision with `scope`.
- The syntax-only cleanup method has an explicit void return: the candidate's STAN
  misclassifies its empty body as abstract. This keeps the intended no-op behavior.
- Scope-index and C++-header resets use typed empty hash locals so lowering preserves
  their concrete container types.
- The native test driver uses children_snapshot(), matching the renamed traversal API.

All 142 PHP/native comparisons passed: 48 valid programs and 94 rejection/recovery
cases. All 48 valid emitted programs compiled and executed with expected results.
The native C++ S2S proof matched PHP bytes, compiled, and exited with the expected
code 10; it also checks repeated preparation, cleanup, incremental replacement and
failure recovery. The final incremental compiler rebuild passed. The ordinary PHP
suite passed separately, including its nine generated C++ execution cases.

STAN reports zero compile-blocking diagnostics, 299 advisory errors and 108 warnings;
this is not an assertion that static analysis is clean.

Evidence: `/tmp/scpp-final-ast-native-01/summary.json`, source hashes, candidate hashes
and per-attempt command/build logs. There were five harness attempts: the first
stopped at conversion, the second at STAN, the next two at C++ compilation, and the
fifth passed. Native-build commands took 1.448s, 33.776s, 22.610s and 25.005s; the
final incremental rebuild took 1.065s. These resumed-build timings are diagnostic
history, not a clean-build performance comparison. PHP regression evidence is in
`/tmp/scpp-final-ast-native-php-01/summary.json`.

## Specialization dispatch native verification

User-requested verification, 2026-09-26, after `d678d9d1`.

The first native attempt stopped at STAN with 13 required-field initialization
errors in specialization methods. Required child handles and the boolean value
now enter the affected records through constructors. The parser gathers function
children before constructing its specialization; it retains the same source and
collection order. No nullable substitutes or fake defaults were introduced.

The second attempt passed STAN but C++ header ordering exposed a dependency cycle:
AST operation signatures reference preparation records, whose fields need AST enums.
`03_parse/kinds.php` now owns the independent syntax enums and Node_Kind_Name helper.
The host bootstrap loads it before AST structures. No generated C++ or toolchain
source was patched.

The third attempt passed conversion, normal STAN-enabled native build and execution.
Evidence: `/tmp/scpp-specialization-native-01/summary.json`, source hashes and
per-attempt logs. Target: `/tmp/scpp-native-244`, candidate
`unversioned-d8ddde93-overlay`; target fingerprints are retained in candidate.json.
The verified target pin remains unchanged.

- 142 PHP/native comparisons: 48 valid programs executed, 94 rejection/recovery cases.
- Original integer S2S proof: PHP/native output parity and compiled program exit 10.
- 15 additional native-compiler S2S cases: boolean copy/reassignment, float
  copy/reassignment, decimal/exponent forms, high precision and normal/subnormal
  boundaries. Their emitted C++ matched PHP, compiled, and ran successfully;
  floating probes verified wrapper type and value without host float conversion.
- Zero compile-blocking errors. STAN still reports 331 advisory errors and 91 warnings.
- Native build commands: 1.584s (STAN failure), 29.841s (header-order failure),
  36.386s (successful build). Successful incremental rebuild: 0.920s.

The harness now accepts `s2s:<source-directory>` requests and retains scalar C++
execution evidence alongside the existing native comparisons. This extends test
coverage without enabling new source-language features or LLVM development.

## Data-model relationship consolidation — 2026-09-27

The stable source/attached occurrence/Key_Storage_List model passed a normal
STAN-enabled native compiler build and 142 PHP/native comparisons (48 valid,
94 rejection/recovery cases), the integer S2S proof and 15 scalar S2S executions.
The shared proof also checks duplicate-key insertion order, repeated record identity,
snapshot independence, collection aliasing and stable source identity across updates.
Evidence: `/tmp/scpp-model-native-01/summary.json` and its numbered attempt logs.
The PR #244 candidate remains an explicit overlay; the verified target pin is unchanged.

New native collection support is maintained in the compiler runtime module and
legacy S2S constructor/type/method bindings. A separate minimal application built
with this repository's toolchain and ran with output `0`, independently of that
candidate overlay (`/tmp/scpp-key-list-native-02`). The reproducible entry is
`tests/portability/key_storage_list_native.py`.

Native stabilization made occurrence attachment an explicit permanent boolean state,
independent of weak-observer presence/expiration. The parked LLVM preparation worker
now skips tombstones and non-variable declarations before acquiring a scope; it
must not rely on PHP short-circuit behavior or old parsed scopes remaining alive.
No parked LLVM index-map redesign was performed.

At this checkpoint STAN has zero blocking compile errors, with 362 advisory errors
and 108 warnings. Native behavior passes; this is not a clean static-analysis report.

First complete PHP behavior checkpoint: `/tmp/scpp-model-php-03/summary.json`;
final full PHP verification: `/tmp/scpp-model-php-final/summary.json` (80 linted PHP
files, mandatory style, 19 LLVM, 28 call and 37 C++ executions). Native first complete
behavior pass was attempt 8 (`logs-8`); attempt 9 (`logs-9`) was the final verification
build and rerun after path-alias normalization. Earlier numbered logs retain the
seven unsuccessful attempts and their corrective diagnostics. Native command timings
are in each `commands.json`; `source_hashes.json` and `candidate.json` identify the
final compiler inputs and selected toolchain files.

## Ordinary functions and value structs — 2026-09-27

User-requested native verification passed conversion, the normal STAN-enabled
compiler build, native compiler execution, and an unchanged-source incremental
rebuild. The same explicit `/tmp/scpp-native-244` candidate overlay was used;
the verified toolchain pin remains unchanged. Current source hashes match the
compiled source manifest in `/tmp/scpp-functions-native-01/source_hashes.json`.

Native compiler coverage:

- 142 existing PHP/native comparisons: 48 valid emitted programs executed and
  94 rejection/recovery cases.
- The original integer S2S proof and 15 scalar C++ execution cases.
- 36 function/struct C++ execution cases reused from `tests/s2s.php`, including
  reference aliasing, value copies, forward/nested calls, argument evaluation order,
  nested structs and exact fixed-width field representation assertions.
- 27 S2S rejection/recovery cases, including deferred templates, incompatible
  boundaries, unsupported field types, unknown members and recursive value layouts.
- Successful S2S requests reprepare retained syntax and compare fresh emitted
  artifacts. Failed requests prove output cleanup and compile a recovery source.

Native stabilization required three source fixes: function lookup now uses the
same loop-exit/result pattern as type lookup; the backend output-name local is
declared in its enclosing block; and member-write preparation accesses typed field
facts through the AST specialization instead of narrowing a non-polymorphic facts
base. No generated output or external toolchain was patched for this slice.

STAN reports zero blocking compile errors, 419 advisory errors and 126 warnings.
This proves successful native behavior, not clean static analysis.

Evidence: `/tmp/scpp-functions-native-01/summary.json`, `candidate.json`, source
hashes, and numbered command/build/test logs. Four attempts were retained: STAN
control-flow failure (1.671s), lowering block-visibility failure (3.598s), native
non-polymorphic-cast failure (38.794s), then successful build (15.145s). The final
incremental build took 1.010s. These resumed-build timings are diagnostic history,
not a clean-build performance comparison.

Final PHP regression evidence: `/tmp/scpp-functions-native-php-final/summary.json`
(82 linted PHP files, mandatory style check, 19 LLVM executions, 28 call executions
and 73 generated C++ executions). Conversion and execution used the authored PHP
source; no generated C++ fixes were retained.

## Incremental pipeline native investigation — 2026-09-28

Native verification was explicitly requested, with simple adaptations authorized and
new support gaps reserved for discussion. The candidate is the current repository
working tree (`79889f32` plus local changes), not the older `/tmp/scpp-native-244`
overlay or a change to the verified target pin.

Six conversion attempts exposed and corrected these source-level portability issues:

- Tokenizer's absent IO path now uses the supported nullable parameter/null default.
- Nullable fields explicitly initialize to null.
- Token-offset arithmetic uses ordinary assignment instead of unsupported compound
  assignment/decrement syntax.
- Optional scalar local annotations use the supported `nullable<int>` spelling.
- Native driver assertions now reflect phase barriers, retained declaration identity,
  and enum change state instead of the removed candidate-replacement lifecycle.

Conversion remains blocked by two new converter gaps, left for discussion:

1. The collector's `task_synchronize(function () use (...) : void { ... })` needs a
   zero-argument void closure. The converter currently only accepts one typed argument
   and a value return. PHP and runtime synchronization support do not prove this
   PHP-to-PHS conversion form.
2. Preparation lookup selection uses a nullable named-object local. The converter
   accepts `nullable<int>` locals and named nullable parameters/returns, but rejects
   the local annotation `nullable<preparation_lookup>`.

Recommendation: extend those two explicit structural conversion forms with focused
conversion/native proofs, retaining the current clear compiler source. No dummy
callback arguments, artificial return values, or extra lookup wrappers were added.
No STAN or C++ build has been reached, so further native issues remain unknown.

Evidence: `/tmp/scpp-incremental-native-20260928/`, numbered attempt logs, source
hashes and candidate fingerprints. Independent conversion inventory processed all 60
files (including expanded traits): 58 passed, two failed as listed above. Five focused
PHP tests passed (tokenizer, token generations, parse/collection, incremental preparation,
combined sync). The updated native driver also passed in PHP for `s2s-proof`.

### Converter extensions approved and proved

The user approved extending the converter for both missing forms. It now accepts
nullable named-object locals and zero/one-argument explicit closures with void or
value returns. Multiple closure parameters, by-reference captures/parameters and
dynamic callable invocation remain outside this slice. Conversion syntax/rejection
checks live in `tests/portability/nullable_locals_callbacks.php`; the reproducible
native entry is `tests/portability/nullable_locals_callbacks.py`.

Native focused evidence: `/tmp/scpp-converter-forms-proof/summary.json` and command
logs. Normal STAN-enabled conversion/build/execution passed on the current working
tree toolchain. PHP/native both produced `9`, proving captured object mutation under
task synchronization and nullable shared identity. Existing collection, task and
incremental preparation/combined-sync PHP checks also passed.

Whole-compiler attempt 8 converted all 60 source files successfully (0.758 seconds).
Its normal build stopped at STAN with 42 blocking diagnostics, including unresolved
`ast_node::payload`, `Parser::parse`, Storage methods and `fs_read_snapshot`.
See `/tmp/scpp-incremental-native-20260928/logs-8/native-build.stderr`.
This used the current repository toolchain; prior complete native checkpoints used
the separate `/tmp/scpp-native-244` overlay. The diagnostics require investigation
of toolchain/indexing differences before attributing them to compiler semantics.
No STAN bypass, whole-compiler native execution, or verified-pin update was made.


## Retained C++ generation checkpoint — 3b55d9e9

The implementation was committed and pushed before the requested guidelines/native
pass. Block layout was normalized with token-preservation checks; all 93 PHP sources
passed the style checker. The complete PHP suite passed all 27 test files after its
model graph checker was extended to visit identity-map keys and values.

Native conversion exposed three bounded syntax gaps: `<=`, annotated object-hash
method parameters, and matching boolean literal parameter defaults. These now convert;
`tests/portability/object_hashes.php` covers the added forms. A fragment's unused
negative initial version sentinel was replaced by zero; dirty state controls whether
its completion version is valid. Native probes now use the stable generated names.

All 60 compiler source files convert. The normal native build then stops at STAN with
37 compile-errors, predominantly unresolved calls to methods present in the converted
source (`ast_node::payload`, `Parser::parse`, Storage methods), plus `fs_read_snapshot`
and a downstream unknown enum operand. This resembles the earlier toolchain/indexing
blocker; its cause has not been established. No STAN bypass was used. C++ compilation,
native compiler execution and native parity tests were not reached.

Evidence: `/tmp/my-try-native-3b55d9e9/logs-5/` (conversion and build),
`/tmp/my-try-php-3b55d9e9-final/summary.json` (27 PHP suites). Formatting and the
native-pass fixes follow the implementation commit and are not part of that commit.

### Resolution investigation: missing candidate-toolchain capabilities

The unresolved methods are downstream diagnostics, not evidence that the methods
are absent from the compiler. Twenty cached file summaries contain
`STAN extraction failed: syntax error, unexpected token ">"` and empty class/function
inventories. Direct extraction of `03_parse/structures.phs` fails at the typed
`new Storage<ast_node>()` construction. A minimal InputLoader probe reproduces this
for both `Storage<Row>` and `Keyed_Storage<Row>`; `Key_Storage_List<Row>` parses.
The current constructor scanner recognizes only the latter collection family.

The current checkout also lacks native `storage.hpp`/`keyed_storage.hpp`, their type
mapping, and `fs_read_snapshot` support. Historical successful proof manifests name
`/tmp/scpp-native-244` with revision `unversioned-d8ddde93-overlay` and include those
runtime headers. That temporary checkout is no longer available here. Existing Git
history contains storage work (`7eee1b79`, with predecessors) and checked source reads
(`361b1e97`); neither is an ancestor of this branch. The overlay base `d8ddde93` is also
not an ancestor. Its uncommitted adaptations must not be assumed reconstructible by
cherry-picking just those commits.

An experimental namespace-context lookup change was discarded: it did not address
missing extraction inventories. No diagnostic suppression or STAN bypass is warranted.
The integration was approved; its outcome and remaining blockers follow below.


### Approved candidate integration and remaining initialization findings

Recovered PR #244 head `d8ddde93b04d0e23d295e30f662c3a81b0d50fd1` and integrated
its Storage/Keyed_Storage source bindings and runtime wrappers alongside the
current Key_Storage_List. Integrated `fs_read_snapshot` from `361b1e97` with its
runtime registration, concrete shallow signature, Linux implementation and tests.
Unrelated historical process/file-lock and generic callback-call changes were
excluded. `StanCollectionTypeResolver` owns the restored collection type/method
metadata and participates in both STAN cache fingerprints.

All converted compiler files now extract successfully. The original missing
inventories and unresolved-call diagnostics are gone. The strict Storage fixture
builds and runs, covering nested/static collections, aliases, retained handles,
holes, exact keys and ordering, runtime errors and compile rejections. Its assertion
helper uses debug-exit plus the harness's required success output, avoiding an
unrelated namespaced Exception-base lowering failure in the historical fixture.
The strict snapshot fixture also builds/runs, including bad argument rejection.

The normal whole-compiler build still stops at STAN, before C++ emission, with
23 findings (`/tmp/my-try-native-integrated/logs-3/native-build.stderr`):

- 22 initialization diagnostics. These include constructor delegation through
  `Parser::init`, required specialization fields populated by the parser before
  publication, and the Preparation_Worker/CPP_Generator constructor assignments.
  STAN's current method baseline tracks direct constructor assignments, not the
  parser's staged publication protocol. The map-construction and null-coalescing
  assignment cases need separate minimal extraction/analysis proofs.
- One unresolved `mixed` receiver while updating candidates through nested
  object-key maps in `Preparation_Worker::changed_lookups`.

These are a separate initialization/type-analysis slice. No dummy field defaults,
nullable weakening, diagnostic suppression or STAN bypass were introduced.
A truthful resolution needs agreement on required-field construction/publication
and the analysis it should support, plus focused regressions for valid and invalid
reads. Constructor delegation and expression-summary gaps may be bounded fixes;
modeling external staged initialization is a broader decision. Native compiler
execution and parity tests remain unverified.

Integration evidence: `/tmp/my-try-storage-integration-2.log`,
`/tmp/my-try-snapshot-integration-2.log`, and the native build log above.
These working-tree changes are separate from commit `3b55d9e9`.

Focused integration regressions passed: pre-tokenizer fixtures, strict runtime
catalog, Storage typing, STAN diagnostics session, and the native snapshot test
(including its injected race/error checks). The latter was compiled directly with
`clang++ -std=c++23 -O0 -g -Iruntime/include tests/runtime/native/test_snapshot.cpp
runtime/include/modules/filesystem/snapshot.cpp -o /tmp/my-try-test-snapshot`
and exited successfully. These checks supplement the earlier 27 passing PHP
compiler suites; they do not bypass the remaining whole-compiler STAN gate.


### Bounded STAN inference follow-up

Property assignment extraction now uses the existing expression descriptor model
instead of dropping writes whose RHS is not a simple alias, chain, literal or
constructor. Coalescing is distinguished from a ternary: only its left arm loses
null. RHS initialization checks visit nested arithmetic/conditional descriptors
before recording the write, preserving self-read diagnostics. Member receivers
accept authored `shared<T>` as well as lowered `shared_p<T>`, including writes
through nested object-key maps. No compiler records or defaults changed.

`tests/tools/test_scpp_stan_bounded_inference.php` proves constructor empty-map and
coalescing assignments, nested shared-key writes, rejected wrong property types,
nullable fallback/ternary boundaries, partial initialization and self-read errors.
The existing STAN diagnostics-session suite also passes.

The normal compiler build now reports 17 findings, down from 23:
`/tmp/my-try-native-integrated/logs-5/native-build.stderr`. The map-key receiver and
direct constructor assignment findings are resolved. Remaining findings concern
Parser's delegated init and specialization fields populated externally by parsing.
Delegation requires modeling method initialization effects; external field setup
requires the previously deferred publication-policy decision. Neither is bypassed.
Native compiler execution remains blocked at STAN.
