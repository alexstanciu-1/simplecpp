# Test runners
Doc Status: supporting

`python3 compiler/my-try/tests/prelower_accounting_acceptance.py --results FRESH_DIRECTORY`
publishes ordinary and prelowered accounting through the public CLI, builds both
at O0 without debug/PCH in fresh native caches, and requires identical output with
998 checks and zero failures. It checks warm reuse and automatically records the
actual native commands for the complete manifest-driven application closure audit.
Runtime/middleware and layout probes are explicitly excluded from that application
audit. Source hashes, revision, toolchain, options and results are retained in the
requested directory. This is correctness/closure acceptance, not a timing benchmark.
Any emission, native policy or closure failure fails the run; no fallback is selected.

`python3 compiler/my-try/tests/prelower_null_output.py --results FRESH_DIRECTORY`
also proves public CLI/API artifact and manifest parity using only
`prelower_templates`, mode switching without semantic repreparation, deprecated
selector neutrality, strict CLI failure/repair, ordinary opt-out, and native PCH
rejection. The CLI-published application is compiled and audited without PCH.
The fixture builds a fresh concrete application with null interleaved with side-effecting echo
operands. It checks ordered bytes, warm reuse and the complete no-PCH template closure
using recorded native commands and the existing audit. The header-boundary suite
supplies direct/concrete counters and output failure-prefix equivalence.

`python3 compiler/my-try/tests/prelower_contracts.py --results FRESH_DIRECTORY`
checks concrete rejection for callable locals, parameters, results, fields and nested
containers, plus object tests/casts, weak creation/locking, source throw/catch/finally,
`take`, match without a default, boxed extraction, runtime-backed conditions/value
probes and enum helpers. Repair cases include supported integer/boolean
conditions and explicit-default match emission and replay.
It verifies ordinary emission, no partial in-memory output on rejection,
mode-switch recovery, source repair and replay through the existing shared runner.
Each attempt uses a fresh `Output_Publication`: rejected revisions are not ready on
disk, ordinary recovery restores the manifest, and repaired replay preserves it.
This is a PHP-host backend contract proof, not a native compiler rebuild or complete
application closure audit. The same runner also exercises closure-recording coverage
checks: missing/unknown units, conflicting commands, failed compiles, PCH use and
unclassified compilation artifacts.

`python3 compiler/my-try/tests/prelower_header_boundaries.py --results FRESH_DIRECTORY`
reuses the output, indexing and compound fixtures at O0 only. It checks native scalar
output bridge demand, broad indexing-header exclusion, direct/concrete behavior and
allocation/copy parity, nested references, source evaluation order and warm reuse.
Output emission also exercises request retirement, replay and option switching.
Its byte proof covers empty, short, embedded-NUL and long literals, copy/move
allocation parity, borrowed identity and native/concrete string-hash parity, including
an alternate metadata-selected byte adapter.

`tests/prelower_template_closure.py --commands RECORDED_JSONL --published APP_DIRECTORY --results FRESH_DIRECTORY`
replays recorded app object commands as preprocessing and full-granularity Clang
traces. It rejects PCH use, template declarations in transitive input, or class/function
instantiations. The audit obtains the ready manifest through `Output_Publication`, checks published
artifact digests and requires every application unit, including entry.cpp, in the
recording. Conflicting commands and unclassified compilation artifacts reject.
`coverage.json` records application units and explicit middleware/layout exclusions.

`python3 compiler/my-try/tests/prelower_substitution.py --source-arguments --results FRESH_DIRECTORY`
checks source payload layout, generated source-class append/store/read calls, shared
identity/reference counts, allocation/copy parity, source header cycles and incremental
body/layout cache behavior. Nested storage must match ordinary signatures; native
source-class shared wrappers remain exposed in this checkpoint.

`python3 compiler/my-try/tests/runtime_template_demand.py --output --alternate-storage --results FRESH_DIRECTORY`
adds scalar echo transport, borrowed/temporary operands, exact ordered bytes and
failure suppression to the source-demand proof. It compares direct/concrete allocation
and payload counters and checks that the alternate storage binding was actually enabled.

`python3 compiler/my-try/tests/runtime_template_demand.py --results FRESH_DIRECTORY`
proves ordinary source emission selects concrete types from runtime recipes, including
a closed native template behind a nominal type. It checks exact used-type closure,
unused metadata, nested references, shared pointee identity, source-copy behavior,
request removal/restoration, fragment replay and option switching. Direct/concrete
O0/O2 and sanitizer runs must report zero boundary allocations and tracked payload
lifecycle counters; warm native builds compile/link nothing. Primitive numeric
copies are not instrumented payload copies.

`python3 compiler/my-try/tests/runtime_type_bindings.py --results FRESH_DIRECTORY`
checks malformed/duplicate type recipes, existing-identity targets, argument reordering,
closed native templates, reset and explicit demand. It compiles the generated layout
probes and concrete lifecycle code at O0/O2 and under ASan/UBSan without PCH. This
proves generated applications, not a native compiler rebuild. The existing mutation
substitution suite supplies the broader allocation/copy and incremental replay proof.

`python3 compiler/my-try/tests/runtime_inventory.py --results FRESH_DIRECTORY`
checks all production catalogs together, their type/protocol contracts and demand-only
concrete preparation. Loading every catalog emits no concrete type headers; requesting
a nested container emits only its required concrete type closure. Repeated requests
preserve output and semantic identity. This is a PHP-host emission proof, not native
compilation or full runtime metadata coverage.

`python3 compiler/my-try/tests/prelower_substitution.py --mutations --results FRESH_DIRECTORY`
compares ordinary and concrete prefix/postfix increments and decrements, local
aliases, field receiver evaluation, ordered expressions, native float passthrough
and nullable-float success/empty failure. It checks request removal/replay, option
switching and body/layout cache reuse at O0, O2, ASan/UBSan and controlled O2.
Mutation counters must be zero in both paths. Primitive integer copies are not
instrumented payload copies; see the [dated evidence](../../../specs/planning/results/prelower_concrete_mutations_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_substitution.py --iteration --results FRESH_DIRECTORY`
compares ordinary and concrete iteration over vectors, hashes, nested containers and
an independently metadata-registered `tray<T>` provider. It covers source evaluation
once, key order, empty loops, break/continue/return, throwing payload copies, inline
protocol storage, demand removal/replay and layout-cache reuse. Instrumented O0,
ASan/UBSan and controlled O2 compare all counters; stock O2 differences are reported
separately. The tracked-payload loop must allocate zero times and perform exactly the
ordinary three copies/destructions, including early return.

`python3 compiler/my-try/tests/prelower_substitution.py --observations --results FRESH_DIRECTORY`
checks concrete runtime free calls: counts on several physical container types,
byte-oriented strlen, temporary-owner observation, value argument sequencing and a
provider returning an already-concrete payload. It retains the controlled allocator
comparison and records ordinary O2 deltas separately. See
[runtime-call evidence](../../../specs/planning/results/prelower_concrete_runtime_calls_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_substitution.py --compound --results FRESH_DIRECTORY`
extends the cast proof with existing-element compound updates, RHS growth/replacement,
nested paths, string concatenation, missing/deleted entries and division failure.
It verifies demand removal and incremental reuse. O0, sanitizer and O2 with allocator
assumptions disabled compare full direct/concrete counters. Stock O2 compares payload
lifecycle counters and records its allocation delta separately: inline native code
can elide allocations that survive the out-of-line concrete boundary.
See [compound evidence](../../../specs/planning/results/prelower_concrete_compound_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_substitution.py --casts --results FRESH_DIRECTORY`
extends the binary proof with string/integer, numeric, boolean and narrowing/widening
casts plus implicit text conversion. It checks source effects and failed-cast
suppression in binary operands and keyed initializers, helper demand removal/replay,
and direct/concrete allocation/copy parity. See [cast evidence](../../../specs/planning/results/prelower_concrete_casts_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_substitution.py --binary --results FRESH_DIRECTORY`
extends the initializer/indexing proof with existing string concatenation and strict
identity, boolean identity, integer arithmetic/power/bitwise/three-way operations,
enum equality, snapshot order, short-circuiting and division-failure order. It checks
binary-helper demand removal/restoration and compares successful workload counters
across direct/concrete O0/O2 and sanitizer builds. Runtime-error stack allocations
are outside the counter window. See [binary evidence](../../../specs/planning/results/prelower_concrete_binary_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_substitution.py --initializers --results FRESH_DIRECTORY`
extends the indexing proof with empty source-field defaults, nonempty list/keyed
initialization, nested concrete elements, 40-element buffer allocation, key/value
order and throwing-copy budgets through successful completion. It verifies initializer
request removal/restoration, cached replay, native/layout reuse and direct/concrete
counter parity. See [initializer evidence](../../../specs/planning/results/prelower_concrete_initializers_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_substitution.py --indexing --results FRESH_DIRECTORY`
adds ordinary indexed reads/writes, append and nested `unset` to the concrete proof.
It checks key/RHS order, root replacement, missing-prefix short-circuiting, concrete
integer hash keys, negative-key append/overflow behavior, internal reference identity,
and throwing-copy cleanup. Counters compare direct/concrete builds within each
configuration: disabling optional elision adds the same source-return move to both.
See [indexing evidence](../../../specs/planning/results/prelower_indexing_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_substitution.py --members --results FRESH_DIRECTORY`
extends the concrete source-field proof with ordinary compiler-emitted `append` and
`replace` calls across integer, nested-vector and tracked-payload arguments. It
compares lvalue snapshots and consumed results at O0/O2 and with ASan/UBSan, checks
member-demand removal/restoration, cached-fragment replay and native/layout reuse.
See [member forwarding evidence](../../../specs/planning/results/prelower_members_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_substitution.py --source-arguments --results FRESH_DIRECTORY`
adds `vector<Record>`, an independently registered `parcel<Record>`, and
`vector<MyClass>` using the shared class handle representation. It compiles actual
published source definitions with concrete fields, compares native/composed counters,
checks nested reference identity and class sharing, and proves that a method-body edit
leaves shared headers/support bytes unchanged and rebuilds only the source object.
The sibling cycle fixture proves explicit generated-header-cycle rejection and recovery.
See [source argument evidence](../../../specs/planning/results/prelower_source_arguments_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_substitution.py --source-fields --results FRESH_DIRECTORY`
adds compiler-emitted inline source records, nested fields, method calls, parameter
and return bindings, reference aliases, fragment replay, method-body edit restoration
and lowering-option switching to the existing concrete-argument proof. Published
specialization artifacts must match the demand set, and generated source artifacts
must not retain tested native integer/vector spellings. The C++ harness drives the
emitted declarations and supplies container seeding/mutation; runtime-container member
forwarding is not covered. See [source-field evidence](../../../specs/planning/results/prelower_source_fields_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_substitution.py --results FRESH_DIRECTORY`
proves production type-binding and lifecycle generation with concrete runtime
arguments, including nested containers and an independently registered template.
It checks direct/composed allocation/copy parity, actual nested references, throwing
copy cleanup, request-order replay, layered layout caching and edit restoration at
`-O0`, `-O2` and ASan/UBSan. The source record and operation adapters are fixture C++;
this does not prove ordinary source-field emission or a native compiler rebuild.
See the [binding integration checkpoint](../../../specs/planning/results/prelower_substitution_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_composition.py --results FRESH_DIRECTORY`
compares a source record containing actual concrete fields against original native
template fields. It checks field/reference identity, record and nested-container
copy/move/growth, shared/unique ownership, partial-copy cleanup, layout/lifecycle
traits, sanitizer behavior and zero extra allocation/payload-copy counts. A negative
control verifies counter sensitivity. `--timing-repeats 5` adds paired separate-unit
compile timings. This is a native representation experiment, not compiler lowering;
see the [composition proof](../../../specs/planning/results/prelower_composition_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_expressions.py --results FRESH_DIRECTORY`
compares ordinary generated source with the runtime ABI mode at O0/O2 and under
ASan/UBSan with optional copy elision disabled. It checks payload allocation/copy
counters, throwing-copy cleanup, nested mutation across RHS root replacement,
provider/source-function boundaries, cached-fragment replay, edit/removal recovery,
configuration switching, fresh-output equivalence and warm native reuse. See the
[expression checkpoint](../../../specs/planning/results/prelower_expressions_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_expressions.py --initializers --results FRESH_DIRECTORY`
uses the same harness for nonempty list/keyed/nested initialization, heap-backed
inputs, per-entry lifecycle traces, copy-failure sweeps, allocation-before-evaluation,
key/value conversion failures and duplicate-key factory cleanup. It also verifies
that literal-length edits do not change specialization support.
The initializer fixture runs with native stack capture and with a test-only
`backtrace` interposition returning no frames. It retains unfiltered counters;
exact allocation parity is asserted with capture isolated, while unfiltered
conversion failures must preserve payload lifetimes and evaluation traces.
Runtime error stack-frame allocations depend on bridge depth and optimization.

`python3 compiler/my-try/tests/prelower_expressions.py --source-types --results FRESH_DIRECTORY`
checks source payload definitions, requested shared/unique/value representations,
nested containers, tracked payload lifecycle and copy failure, pointer identity,
unique moved-from state, independent value copies, unused representation removal,
and native layout reuse on method-body edits versus rebuilding on field-layout edits.
It also exercises source instance methods: receiver replacement during argument
evaluation, clearing the caller's handle inside a method, ordered value arguments,
aliased writable/readonly references, inline receivers, defaults and owned results.
Throwing copies cover both argument snapshots and native parameter construction.
The fixture adds identical tracked members to emitted payloads solely for counters;
construction, methods, destruction and application operations are compiler-generated.
Direct/ABI counters are compared within each build configuration: disabling optional
elision may add a return move equally in both modes.

`python3 compiler/my-try/tests/prelower_expressions.py --constructors --results FRESH_DIRECTORY`
extends that proof with ordered positional constructor arguments, explicit/default
parameters, shared/unique/inline class storage, struct property-backed parameters,
and injected copy failures at argument, native parameter and constructor-body stages.
It retains the same replay, cache, allocation/copy and sanitizer checks.

`python3 compiler/my-try/tests/prelower_layout.py --results FRESH_DIRECTORY`
checks real prepared observation/type bindings through opaque lifecycle generation,
publication and native layout caching, including provider dependency edits, recovery,
allocation/copy counters and sanitizers. This is API integration, not ordinary source
expression adoption; see the [checkpoint](../../../specs/planning/results/prelower_layout_integration_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_boundary.py --results FRESH_DIRECTORY`
compares direct and in-place opaque runtime objects with allocation/lifecycle counters,
nested-reference identity, failure cleanup and a deliberately copying negative control.
This is a native boundary experiment, not compiler integration; see the
[dated proof](../../../specs/planning/results/prelower_boundary_2026_10_19/README.md).

`python3 compiler/my-try/tests/prelower_templates.py --results FRESH_DIRECTORY`
checks the opt-in concrete integer/vector slice, incremental emission/native reuse,
error parity, sanitizers and matched no-PCH native timings. See
[coverage and ownership](architecture/cpp_template_prelowering.md).

`python3 compiler/my-try/tests/records.py --suite duplicate_types --no-pch --results FRESH_DIRECTORY`
checks duplicate type admission before emission, all source type kinds, cross-file
retry/rename/removal recovery and valid namespace/import controls. See
[the DR-03 proof](planning/duplicate_type_admission_2026_10_18/README.md).

`python3 compiler/my-try/tests/records.py --suite ownership_targets --no-pch --results FRESH_DIRECTORY`
checks shared/unique void target rejection across source contexts, retry/recovery,
and generated shared/unique controls plus unchanged weak<void> admission.
See [the bounded DR-02 proof](planning/ownership_void_targets_2026_10_18/README.md).

`python3 compiler/my-try/tests/records.py --suite constructor_parameters --no-pch --results FRESH_DIRECTORY`
checks abstract inline constructor input rejection before emission, inherited
signature invalidation/recovery, and four generated concrete/shared input controls.
See [the bounded DR-04 repair proof](planning/constructor_parameter_admission_2026_10_18/README.md).

`tests/scalar_admission.py --php /usr/bin/php8.2 --jobs 32 --results FRESH`
checks bool equality and float arithmetic, conversions, deferred boundaries and
seven generated programs. Run on dev002. The agreed contract preserves
`division_by_zero`. `--ieee-zero` retains the historical counterfactual IEEE probe;
it is expected to fail and is not an acceptance check or an open blocker.

For the v0.1 compatibility milestone, use the [pinned original-source corpus and
differential runner](planning/v0_1_compatibility_testing.md). It runs both compiler/
runtime generations, distinguishes baseline failures from v0.2 gaps, and excludes
operator matrices for a separate semantic-review pass.

CH14-EMIT-01 enables `-Wshadow` in shared fixture compilation (including runtime/PCH)
and native build actions. `tests/project_cpp.py` covers qualified lookup and legal
block/parameter/foreach-key/catch shadowing with PCH/no-PCH execution and standalone
headers. `tests/native_invalidation.py` checks the flag on compile/PCH paths and
successful warning delivery alongside its existing exact rebuild matrix. Warnings
remain diagnostics, not errors; these runners do not rebuild the compiler itself.

Chapter 14 native closeout uses the same conversion/build setup:

```sh
python3 compiler/my-try/tools/native_validate.py --target-checkout "$PWD" \
  --candidate-revision "$(git rev-parse HEAD)" --results /tmp/ch14-native-FRESH \
  --types-only --no-stan --chapter14
```

The converted driver compares exported multi-file projects with PHP, compiles native
artifact bundles with and without PCH, and checks standalone headers. It also invokes
the native compiler's actual publication and native-build workers for byte-identical
manifests, no-op/body-edit reuse, failure/repair, successful warning delivery and an
optional provider's source/definition/library invalidation. Build consumption is tested
with the source catalog removed. These bounded checks do not sweep every historical
publication interruption, locking or toolchain case. `--resume` retains attempt logs
and objects; a successful summary is required before closeout. Source, harness,
runtime-catalog and candidate hashes identify the exact tested overlay.

Chapter 13 native closeout uses the shared candidate validator:

```sh
python3 compiler/my-try/tools/native_validate.py --target-checkout "$PWD" \
  --candidate-revision "$(git rev-parse HEAD)" --results /tmp/ch13-native-FRESH \
  --types-only --no-stan --chapter13
```

This converts the compiler to PHS, installs the existing portable framework, builds
it natively and compares all exported error-value/exception/finally fixtures against
PHP before compiling and running their C++. It also checks rejection/recovery,
retained-session edits, production CLI failures and a no-change native rebuild.
The mode uses no fixture PCH; the native compiler build retains its normal cache.
`--resume` refreshes the source snapshot while preserving native objects and prior
attempt logs. `source_hashes.json`, `harness_hashes.json`, `candidate.json` and the
executable hash distinguish the tested source/overlay from an earlier revision.
Test-owned native instrumentation executes only after byte equality of its complete
uninstrumented base. A missing successful summary keeps the closeout gate open.

`python3 compiler/my-try/tests/records.py --suite finally --no-pch --results FRESH`
checks scalar/void delayed returns, nested exception propagation/replacement, cleanup
order, local versus crossing transfers, source closure isolation and incremental
rejection/repair. It compiles generated programs; no native compiler rebuild is implied.

`python3 compiler/my-try/tests/records.py --suite exceptions --no-pch --results FRESH`
checks the bounded Exception factory, canonical throw/catch admission, shared identity,
handler lifetime, reverse exceptional cleanup, partial construction, outer reporting
and incremental rejection/repair. It compiles generated programs, not the compiler.

`python3 compiler/my-try/tests/records.py --suite error_values --no-pch --results FRESH`
checks metadata error factory/defaults/getters, Result failure boundaries, state and
copy behavior, no-error rejection with assignment preservation, preparation rejections
and incremental repair/fresh agreement. It compiles generated programs; it does not
rebuild the compiler.

`python3 compiler/my-try/tests/indexed_properties.py --results FRESH_DIRECTORY`
checks plain indexed shared-object property writes, reference/place boundaries,
key/RHS replacement lifetime and bounds/null failures. Three generated programs
execute alongside preparation-dispatch and indexing regressions.

`python3 compiler/my-try/tests/eager_binary.py --results FRESH_DIRECTORY` checks
left-to-right eager operands/conversions, scalar and reference-return snapshots,
lazy subexpressions, failure order and existing operator preparation regressions.
It executes four generated programs, not a native compiler rebuild.

`python3 compiler/my-try/tests/field_container_probes.py --results FRESH_DIRECTORY`
checks keyed field-root `isset`/`empty`/`unset`, lazy keys, prefix re-resolution,
shared-owner lifetime, inline/read-only fields and non-creating failures. It executes
four generated C++ programs; this is not a native compiler rebuild.

`python3 compiler/my-try/tests/field_container_writes.py --results FRESH_DIRECTORY`
checks the generalized indexed receiver for vector/hash/mixed/dynamic fields,
struct copy independence, retained shared owners, effect ordering and runtime failure
boundaries. Existing indexing preparation checks run alongside generated programs.

`python3 compiler/my-try/tests/runtime_assets.py --results FRESH_DIRECTORY`
checks production JSON-document, file-lock and managed-process metadata, rejection
boundaries, shared lifecycles and automatic provider linkage through the native
builder. Linux integration proof; not a native compiler rebuild or full race matrix.

`python3 compiler/my-try/tests/runtime_exports.py --results FRESH_DIRECTORY`
checks root runtime aliases, namespace isolation and incremental source shadowing,
plus two generated programs. No native compiler rebuild.

`python3 compiler/my-try/tests/runtime_module_build.py --results FRESH_DIRECTORY`
checks metadata provider publication, native object/link inputs and PCH invalidation
with six ordered build/execution scenarios. It does not rebuild the compiler.

Shared runtime-storage checkpoint:
`python3 compiler/my-try/tests/runtime_handles.py --results FRESH_DIRECTORY`.
The focused set covers noncopyable provider payloads behind copyable handles,
registered static/instance calls and existing source-method regressions. Extended
matrices are Chapter 15 testing debt.

Owning source-module registration and generated-program checks:
`python3 compiler/my-try/tests/runtime_source.py --results FRESH_DIRECTORY`.
This uses the shared runner and proves copying, transfer, index independence and
checked ranges; it is not a native compiler rebuild or borrowed-span safety proof.

`python3 compiler/my-try/tests/runtime_catalog.py --results FRESH` checks strict typed
module decoding, dependency ordering/cycles, failed startup configuration preservation,
reset reuse after catalog files are removed, stale-binding rejection, and byte-counting
`strlen`/source shadowing through generated native programs. The existing `json_api.py`
suite checks the migrated JSON signatures and executions. Neither rebuilds the compiler.

`python3 compiler/my-try/tests/records.py --suite derived_data --no-pch --results FRESH`
checks derived class/struct data construction through authored and inherited public
constructors and composed synthesized inputs: parameter/property keys, base execution,
defaults, ordering, nested inputs and storage forms. Rejections preserve explicit
base/child opt-in, access and required-input boundaries. Cross-file edits prove signature
and synthesized-schema invalidation, retry/repair and reset/fresh
agreement. This compiles generated programs, not the compiler itself.

`python3 compiler/my-try/tests/records.py --suite late_static --no-pch --results FRESH`
checks called-type selection, self/parent forwarding, explicit reset, two contexts,
recursion, shared-class factories/constructors, inherited storage and declared-type
qualifiers. It also checks eager signatures versus demanded bodies and cross-file
call removal, failure/retry/repair, no-op/reset and fresh/incremental agreement.
This compiles generated programs; it does not rebuild the compiler natively.

`python3 compiler/my-try/tests/records.py --suite static_redeclarations --no-pch --results FRESH`
checks compatible static redeclarations in classes/structs, fixed named/expression/self/parent
selection, reference returns, variadics, visibility, finality and virtual-only Override
assertions. Ten generated programs execute; this is not a native compiler rebuild.

The model runner also includes `callable_instances.php`: called-type key reuse,
independent ordinary method preparation, lexical declaring-type preservation,
private token buffers, no-op identity/output and source-edit/reset retirement.
It now uses ordinary compiler preparation to prove private-body scheduling, no-op
version stability, third-file dependency changes and retained failure/retry/repair.
Explicit test-only roots are seeded after source preparation consumes pending edits.
The separate late-static suite proves source call selection and emitted execution.

`callable_demands.php` seeds private roots, then proves analysis-to-coordinator
suspension/materialization through their real calls: self/parent forwarding, explicit
type reset, recursive identity, no-op versions and standalone preparation. An unrelated
exception with identical text must not be mistaken for the suspension signal.

The model runner includes `trait_model.php`: trait source identities, simple use
syntax, lexical spellings, incremental parsing/recovery and automatic composition.
`trait_instances.php` additionally proves consumer-owned materialization, independent
ordinary method preparation, lexical imports, class/struct receivers, token snapshots
and explicit retirement. It also checks ordinary contributed layout/defaults, member
calls, category collisions, eligibility, reference cycles and C++ consumer-unit
placement. `traits.php` adds automatic selection, edits, retry/reset/fresh agreement.
`python3 compiler/my-try/tests/records.py --suite traits --no-pch --results FRESH`
also executes contributed static methods/properties/constants for classes and structs,
independent consumer versus inherited storage, lexical constant lookup, late-static
overrides, reference returns/variadics and static trait-edit recovery. Constants retain
private fragment scopes and use ordinary eager preparation/collision validation.
The suite compiles/runs generated programs; it does not rebuild the compiler natively.

`python3 compiler/my-try/tests/records.py --suite interface_handles --no-pch --results FRESH`
checks interface shared storage/upcasts, static-contract defaults, abstract-body
calls, reference cycles, receiver lifetime and cross-file edits/recovery. It compiles
generated programs; it does not rebuild the compiler natively.

`python3 compiler/my-try/tests/records.py --suite shared_upcasts --no-pch --results FRESH`
checks shared derived/base identity, absence, cleanup, typed boundaries and exact
reference restrictions, including cross-file ancestry edits/retry/repair. This is
generated-program execution, not a native compiler rebuild.

`python3 compiler/my-try/tests/records.py --suite interfaces --results FRESH`
checks interface contracts and inherited forwarding for classes/structs, native dispatch
and cleanup through interface views, invalid signatures/finality, and incremental
relationship changes. Add `--no-pch` to prove separate-unit header dependencies.
Source interface values/upcasts have their separate `interface_handles` suite above;
this does not rebuild the native compiler.

`python3 compiler/my-try/tests/records.py --suite abstract_dispatch --results FRESH`
covers abstract class/struct contracts, exact overrides/finality, virtual calls/cleanup,
reference returns, bodyless transitions, cross-file invalidation and fresh/incremental
agreement. It compiles generated programs and checks move/copy traits; it does not
rebuild the compiler natively.

`python3 compiler/my-try/tests/records.py --suite inheritance --results FRESH`
also covers explicit parent access, inherited public constructors/defaults, leading
base initialization, argument ordering, noncopyable payloads, cross-file body/default
edits, signature/header stability, failure/retry/repair and fresh/incremental agreement.

CH10 plain-record checks: `python3 compiler/my-try/tests/records.py --results FRESH`.
Uses the shared runner and fixture PCH for class/struct storage, copy/weak behavior,
rejections and incremental recovery. This compiles generated programs, not the compiler.
Use `--suite property_defaults` with the same runner for class/struct constant, enum,
nullable and boxed defaults, storage overrides, independent instances and cross-file
default dependency edits/recovery. It also covers nested vector/hash/mixed/dynamic
construction defaults and per-instance mutable-default independence.
Use `--suite data_records` for synthesized class/struct keyed construction, required
inputs, source ordering, suppression of supplied-property defaults, storage variants,
nested record defaults and cross-file schema/default invalidation. Authored constructor
bodies and methods remain outside these proofs.

Both `tests/run.py` and `tools/native_validate.py` use the reusable
[`tools/test_runner.py`](../tools/test_runner.py) worker pool. The default is **12
concurrent tasks**; `--jobs N` overrides it and `--jobs 1` runs serially. A completed
task immediately frees its slot for the next queued task. There is no batch barrier
between groups of 12.

A task owns its sequential dependencies: source comparison, compilation and execution
stay ordered inside one fixture task. Real phase dependencies still apply: suites
produce manifests before their generated programs run, and native compiler construction
precedes native fixture execution. Do not create nested worker pools inside a task.
The limit applies to tasks in one runner invocation, not compiler-internal threads or
separate simultaneous runner invocations. Assertions inside each PHP suite retain their
existing order; independent suites run concurrently.

## Commands

For CH10 or CH11 source parity using an already-built native validation driver:

```bash
python3 compiler/my-try/tools/native_records.py --executable /absolute/path/to/native/main --results /tmp/FRESH_NATIVE_RECORDS
python3 compiler/my-try/tools/native_records.py --chapter 11 --executable /absolute/path/to/native/main --results /tmp/FRESH_NATIVE_CH11
```

This runs the 15 CH10 suites (default) or 14 CH11 suites, captures successful source programs with
their PHP-hosted artifacts, compares native artifacts, then compiles/runs instrumented
fixtures through the shared pool/PCH path. `SCPP_RECORD_EXPORT` is a test-only host
export hook; ordinary suite behavior is unchanged when it is absent. Rejections and
incremental checks inside these suites remain host checks, not native proofs. The
separate cumulative native driver supplies its existing rejection/incremental scope.
The runner requires that driver; it does not build a compiler by itself.
`--repeat-parity N` repeats source comparisons in separate processes and directories,
including fresh multi-file parse schedules; generated fixtures execute once afterward.
This is a focused concurrency regression check, not an exhaustive race-safety proof.

For bounded class/struct constructors, `set:` inputs and named data calls:

```bash
python3 compiler/my-try/tests/records.py --suite constructors --results /tmp/FRESH_CTOR_RESULTS
```

Use `--no-pch` to verify generated header completeness. This exports partitioned
programs and checks ordering, defaults, visibility, storage variants, contextual
`set` spelling and incremental signature/body separation, retry/repair, reset and
retirement. It compiles generated programs, not the compiler itself. Existing
`--suite records`, `property_defaults` and `data_records` select adjacent proofs.

Use `--suite methods` for fixed instance calls, receiver retention/order, private
access, same-named properties/methods, defaults, storage variants and cross-file
signature/body invalidation, retry/repair and retirement. The absent-receiver fixture
adds a test-only native exception boundary to prove checked failure, not a crash code.
`--no-pch` verifies emitted headers without implicit fixture includes.

Use `--suite static_members` for class/struct static calls, member constants,
type-owned property reads/plain writes, defaults, visibility, disjoint member names,
data/constructor exclusion and cross-file invalidation, retry, reset and retirement.
It uses the same runner; `--no-pch` checks actual emitted header dependencies.

Use `--suite method_parameters` for shared writable/read-only reference and typed
variadic admission across class/struct and instance/static methods. Covers alias
observation versus value snapshots, empty/default packs, source-order evaluation,
stable fields, container mutation, receiver retention, rejection and cross-file
signature invalidation/recovery. No native compiler rebuild is performed.

Use `--suite method_returns` for same-origin returns across class/struct and
instance/static methods: copying versus aliasing, COW/shared/unique storage,
forwarding, receiver release, invalid escapes/cycles, cross-file edits, retry/repair
and fresh/reset agreement. `--no-pch` checks generated header completeness.

Use `--suite qualified_static_calls` for expression-qualified static methods:
class/struct storage variants, absent/read-only qualifiers, effect ordering,
projections, shared parameter/return handling, visibility and incremental recovery.
These are generated-program proofs, not a native compiler rebuild.

Use `--suite qualified_static_members` for expression-qualified property reads/plain
writes and constant reads. Includes effect preservation, lazy reads, write sequencing,
failure/no-assignment probes, constant-context rejection, storage variants, access and
incremental constant/visibility/storage-kind invalidation with repair/reset checks.

Use `--suite destructors` for automatic cleanup across class/struct storage variants,
last-owner/weak behavior, inline copies/aliases, early exits, member order, data and
authored construction, native move/copy traits, unwinding and termination boundaries.
Includes cross-file body edits, invalid-signature retry, removal/readdition and resets.

Use `--suite nullable_handles` for `?Handle`/`nullable<Handle>` normalization, exact
reference compatibility, shared/unique/weak behavior and preserved inline-wrapper
identity. It deliberately does not claim nullable inline-record value operations.

Use `--suite nullable_records` for struct/inline-class optional construction,
copy independence, checked extraction, fields/calls/returns, null comparisons and
noncopyable-payload rejection. Also covers reset cleanup, empty noncopyable values,
cross-file capability edits, failed retries, repair and fresh/reset agreement.

Use `--suite collection_probes` for collection/string-only `empty`, static rejection
of scalars/records/wrappers, runtime mixed/dynamic kind/path errors, lazy key stopping,
and noncopying inline/nullable record presence and truthiness.

For the CH10 shared declaration model and CH11 member-owner/visibility migration
(including relationship/lookup lifecycle, interface identity and execution-gate proofs):

```bash
python3 compiler/my-try/tests/declaration_model.py --results /tmp/FRESH_DECLARATION_MODEL_RESULTS
```

Runs focused PHP-host model, parser, incremental, constant and emission checks through
the shared pool. Covers typed member ownership, optional syntax, bodyless completion,
inspection/maintenance agreement, cross-file ancestry edits, cycles/final rejection,
missing/present member invalidation, fresh/incremental agreement, reset/retirement,
and rejection of unsupported execution. No
conversion or native build is performed; CH10's test-duration review precedes its
native validation.

Use `records.py --suite inheritance --results FRESH` for concrete class/struct bases,
protected receiver admission, original static ownership, copy/cleanup behavior and
cross-file invalidation/repair. This compiles generated programs, not the compiler.

For source enums, exact backing/storage and incremental case references:

```bash
python3 compiler/my-try/tests/enums.py --results /tmp/FRESH_ENUM_RESULTS
```

Runs focused PHP suites, then compiles/runs partitioned generated programs
without PCH. Independent native assertions verify exact enum widths and values.
Also covers enum equality, typed switch/match selection, same-enum ternary/match
results, copy independence, lazy results and unmatched failure. Enum parameter
defaults and `enum_name` cover exact names, once-only argument evaluation,
cross-file helper sharing, source/import shadowing, incremental metadata edits,
cached generation, helper retirement and generation purity. This proves generated
programs, not native portability of the compiler itself. Numeric enum helpers add
exact backing/ordinal results, checked cross-width conversion, signed/unsigned
limits, invalid-case and truncation rejection, type-marker imports, shadowing,
backing/value edits and unused-helper retirement.

For source namespace-body ownership and entry/lifecycle integration:

```bash
python3 compiler/my-try/tests/namespace_bodies.py --results /tmp/FRESH_NAMESPACE_BODY_RESULTS
```

Checks the implicit global parser path and explicitly assembled multi-body inventories,
one-body entry admission, failed-preparation output guards and join-ordered retirement.
Compiles the selected/empty entries without PCH and converts parser/preparation source.
This is a model-focused proof, not a native compiler rebuild. Authored namespace
syntax has its own runner:

```bash
python3 compiler/my-try/tests/namespaces.py --results /tmp/FRESH_NAMESPACE_RESULTS
```

Checks semicolon/braced namespaces, reopened bodies, same-namespace cross-file
functions/structs, duplicate rejection, absent global/parent fallback, unchanged
identity, edits, namespace moves and failed-parse recovery. Compiles the resulting
units without PCH and converts tokenizer/parser source. Qualified calls, struct type
paths (locals, fields, signatures and construction), and a registry-supplied constant
exercise rooted/current-relative resolution. The retained qualified-lookup fixture
uses authored syntax and checks missing namespace additions, moves, ambiguity and
consumer cleanup. Import fixtures cover plain/function/constant bindings, aliases,
homogeneous groups, reopened-body/file isolation, unused-import rejection, no-op
identity, value/type retargeting, target-addition recovery and stale-output guards.
Those namespace fixtures retain registered-constant coverage. Source declarations
have a separate focused runner:

```bash
python3 compiler/my-try/tests/source_constants.py --results /tmp/FRESH_CONSTANT_RESULTS
```

Checks the four scalar families, signs/exact bounds, forward and cross-file lookup,
imports, switch/match labels, unused duplicates, cycles, no-op/equal-value edits,
value/type changes, missing-name additions, namespace moves, deletion and failed-parse
recovery. Includes CH08 non-admission probes for nested namespace source forms,
include/require and multiple executable bodies. Runs focused PHP regressions and
compiles/executes generated fixtures without PCH. No native compiler rebuild or
portable-source conversion is claimed; the user deferred the compiler build until
after the intended Chapter 09 pass.

For the namespace identity/backend model checkpoint:

```bash
python3 compiler/my-try/tests/namespace_identity.py --results /tmp/FRESH_NAMESPACE_IDENTITY_RESULTS
```

Supplies namespace scopes explicitly to the parser API and checks canonical identity,
same-short-name types/functions, language visibility, reset and noncolliding C++ artifacts.
Compiles separate generated units without PCH and converts the scope model. This does
not claim authored namespace syntax, qualified path parsing or a native compiler rebuild.

For persistent native object/PCH/link caching:

```bash
python3 compiler/my-try/tests/native_cache.py --results /tmp/FRESH_NATIVE_CACHE_RESULTS
```

Checks real Clang cold/warm/body-edit builds, PCH on/off, optimization changes,
runtime-header invalidation, missing binaries/objects, executable permissions,
source removal, reservation contention and preparation/compile failure recovery.
Also converts the native owner. This exercises a PHP-hosted builder and generated
native programs, not a native rebuild of the compiler. See the
[bounded build contract](architecture/native_cpp_build.md) for deferred coverage.

For the broader native rebuild-set and phase-timing matrix:

```bash
python3 compiler/my-try/tests/native_invalidation.py --results /tmp/FRESH_INVALIDATION_RESULTS
```

Tests body/default/signature/layout changes, unrelated source insertion/removal,
source relocation and compiler identity changes against exact compiled-unit sets.
A host-only publication fixture times bootstrap, initialization, frontend sync,
preparation and combined emission/publication through existing coordinator methods.
A test-only Clang proxy records version queries, dependency scans, PCH validation/
creation, object compilation and linking. `native_other` includes PHP bookkeeping,
hashing/parsing and proxy startup; it is not labelled pure hashing time. Each action
sequence runs serially in one shared-runner task, with executable results checked.
The proxy changes compiler identity and adds overhead: compare these observations
within this run, not as an optimization benchmark against uninstrumented history.
Every publication starts a fresh PHP compiler; timings do not claim retained semantic
incremental speed. Cache reuse persists across processes. No native compiler rebuild.

For persistent output publication and its shared filesystem adapter:

```bash
python3 compiler/my-try/tests/output_publication.py --results /tmp/FRESH_PUBLICATION_RESULTS
```

Checks PHP/native binary IO and SHA-256, unchanged bytes/inode/mtime, safe paths,
ownership refusal, add/delete/interruption recovery, pending failures, coordinator
handoff retry, CLI restart and shared/exclusive lock contention. Builds the actual
published units under a shared lock. Also converts/builds a small portable facade
against this checkout (`--no-stan`), without rebuilding the compiler. PHP fixtures
under `tests/fixtures/` need runner-provided setup. No persistent native/PCH cache
or power-loss recovery is claimed by that publication suite; native caching has its
own suite above. Staged promotion, alias consumption, permissions and streaming SHA
boundaries are included in its PHP/native adapter and converted-facade checks.

For cross-file C++ generation and artifact isolation:

```bash
python3 compiler/my-try/tests/project_cpp.py --results /tmp/FRESH_PROJECT_RESULTS
```

Checks explicit entry, declaration-only non-entry sources, named modules, body-only
artifact isolation, defaults, references, source records, containers, ownership,
insertion/deletion, signature changes, recovery and relocation. Programs compile as
separate translation units with and without the run-local PCH. Persistent native
caching and rebuilding the compiler itself remain separate milestones.
Also checks bare-record forward declarations, reference-only forwarding, value/field
layout dependencies, inferred copies and template payloads. Generated headers compile
individually without PCH; clang-reported transitive dependencies distinguish forwarding
from layout consumers. Layout edits preserve unrelated final artifact bytes.

`tests/cpp_probe.php` supplies an editable artifact-section transport for existing
native assertions. The shared fixture compiler preserves each section's file and
compiles units separately. Prefix includes/macros apply to the probe files. Instrumented
entries include the bundle's declaration headers, since inserted assertions and appended
native harness code can name declarations unused by the original entry. Production
bundles and uninstrumented transport roundtrips retain their precise dependencies.
`content()` is inspection-only, not compilable output.

For the Chapter 08 artifact-delivery foundation:

```bash
python3 compiler/my-try/tests/cpp_artifacts.py --results /tmp/FRESH_ARTIFACT_RESULTS
```

Checks artifact identity/kinds, JSON transport, rejection without partial fixture
publication, single-unit probe guards and retained generation. Compiles generated
single-source output plus a handcrafted header/two-unit bundle as separate objects,
with and without the run-local PCH. This is not multi-source lowering, persistent
PCH caching or a native compiler rebuild. Existing source-instrumentation fixtures
now preserve partitioned files through the editable artifact transport described above.

For ownership classification and direct type-composition admission:

```bash
python3 compiler/my-try/tests/ownership.py --results /tmp/FRESH_OWNERSHIP_RESULTS
```

Runs model, registration, capability, allocation, local transfer, empty/reset/presence
and source recovery checks, then compiles generated programs without PCH. A native
lifetime harness calls generated local/call/return transfer code to observe identity,
replacement and unwinding release, including failure of a later argument before
callee entry. Weak derivation/locking checks include empty and expired states,
single evaluation, temporary lifetime, noncopyable pointees and native identity/
strong-owner counts/destruction. Allocation is bounded to default source structs.
Source user-destructors and a native compiler rebuild are not proved.

For whole-storage unset/clean and their alias effects:

```bash
python3 compiler/my-try/tests/cleanup.py --results /tmp/FRESH_CLEANUP_RESULTS
```

Runs focused PHP/recovery checks and generated C++ executions for default states,
alias persistence and dynamic binding replacement. `--php-only` skips C++;
`--no-pch` checks emitted include completeness. This does not rebuild the compiler.

For local aliases and bounded reference returns:

```bash
python3 compiler/my-try/tests/references.py --results /tmp/FRESH_REFERENCE_RESULTS
```

Runs focused preparation/model/recovery suites, including cross-file forwarding
and cycle checks, and compiles/executes generated alias/reference-return programs
with PCH. Explicit const-reference coverage includes alias/field propagation,
no-copy noncopyable records/unique handles, live aliases, read-only forwarding,
write rejection and access-only signature-change recovery.
Block-local coverage includes branches, loop bodies, switch groups, typed shadowing,
const propagation, scope-escape rejection and failed-edit/cleanup recovery. Ordinary
foreach value-local aliases do not enable reference foreach.
Same-origin return coverage includes early/exhaustive branches, loop/switch returns,
forwarding, unreachable-return validation, missing-return rejection and incremental
origin/completion failure recovery.
`--php-only` skips C++ execution.
Neither mode rebuilds the compiler itself natively.

For the compiler's tagged-family boundaries and container-signature checkpoint:

```bash
python3 compiler/my-try/tests/dynamic_families.py --results /tmp/FRESH_FAMILY_CAST_RESULTS
```

Runs retained model, preparation, indexing, iteration, call and boundary/recovery
checks and compiles generated family-cast, scalar-boundary, same-family container,
dynamic condition, lazy-selection, shared boxed-operator and boxed Result programs against a rebuilt runtime.
The operator matrix uses the same source/probe cases for mixed and dynamic, with
explicit family-specific expectations only for container identity and text labels.
`--no-pch` checks header completeness.
This is a focused export, not a full S2S suite or native compiler rebuild.

For the broader Chapter 05/06 mixed/dynamic and container regression:

```bash
python3 compiler/my-try/tests/container_regression.py --results /tmp/FRESH_CONTAINER_RESULTS
```

Runs 22 retained PHP suites, including the full S2S fixture export. It compiles the
selected families' state probes, each containing the original entry program plus
independent assertions, without also compiling a redundant uninstrumented copy.
The exporter marks these entries explicitly with `state_probe`; fixture name
suffixes are not the selection protocol. `--no-pch` is also available. This uses a
rebuilt snippet runtime, not a rebuilt native compiler.

For registered JSON source calls and checked runtime results:

```bash
python3 compiler/my-try/tests/json_api.py --results /tmp/FRESH_JSON_RESULTS
```

Runs focused callable/preparation/recovery suites and generated JSON programs.
Builds matching core runtime and `modules/json/json.cpp` objects before linking;
emitted JSON headers alone do not supply the out-of-line implementation. PCH is
enabled by default; `--no-pch` checks header independence. This is separate from
the generic fixture export because JSON needs its module object at link time.

For the mixed field-layout and shared packed-table storage checkpoints:

```bash
python3 compiler/my-try/tests/mixed_layout.py --results /tmp/FRESH_MIXED_LAYOUT_RESULTS
```

Rebuilds matching debug and release snippet runtimes and JSON objects, then runs
layout/lifetime, array-semantics, hash, value-independent packed-storage and internal
tagged-dynamic and JSON programs
through the shared pool. Dedicated layout and packed-storage behavioral checks remain
active under NDEBUG. This does not rebuild the compiler.
The [storage plan](planning/mixed_dynamic_storage.md) records separate baseline
failures in the older mixed operator suite; those are not hidden by this checkpoint.

For the tagged MySQL row adapters:

```bash
python3 compiler/my-try/tests/mysql_rows.py --results /tmp/FRESH_MYSQL_ROW_RESULTS
```

Requires an installed Connector/C development package discoverable through
pkg-config (libmariadb, mariadb or mysqlclient). Builds the module in enabled and
disabled configurations against matching debug/release runtimes; executes offline
stub-result wrapper proofs, with checks active under NDEBUG. No server connection,
live database test or native compiler rebuild is performed.

For whole-container parameters/returns and indexed call arguments:

```bash
python3 compiler/my-try/tests/container_calls.py --results /tmp/FRESH_CONTAINER_CALL_RESULTS
```

Five focused PHP suites check shared call facts, signature-change recovery and
reference rejection. `container_call_*` C++ programs check value/COW/shared identity,
non-creating arguments, typed conversion failures and ordered evaluation. PCH is
default; `--no-pch` checks header completeness. Uses the shared 12-slot runner and
does not rebuild the compiler natively.

For non-mutating value `foreach` across vector/hash/mixed/dynamic:

```bash
python3 compiler/my-try/tests/iteration.py --results /tmp/FRESH_ITERATION_RESULTS
```

Five focused PHP suites check loop integration, scope, rejection, retained-body
recovery and exports. `foreach_*` generated programs cover typed keys/values,
runtime kind and conversion failures, temporary lifetimes, empty sources and nested
transfers. PCH is default; `--no-pch` checks header completeness. Uses the shared
12-slot runner. This is not a native compiler rebuild or proof of mutation semantics.

For lazy keyed `isset`/`empty` and associative `unset`:

```bash
python3 compiler/my-try/tests/keyed_paths.py --results /tmp/FRESH_KEYED_RESULTS
```

Five PHP suites exercise syntax, preparation, retained-body recovery and exports.
`probe_path_*` / `unset_path_*` generated programs prove skipped keys, key exceptions,
root replacement, nullable/Result leaf states, no creation, no-op purity and COW/shared
identity. PCH is default; `--no-pch` checks header completeness. Uses the shared 12-slot
runner, not a native compiler rebuild.

For common value reads and direct/nested writes/appends:

```bash
python3 compiler/my-try/tests/container_reads.py --results /tmp/FRESH_READ_RESULTS
python3 compiler/my-try/tests/container_writes.py --results /tmp/FRESH_WRITE_RESULTS
```

Seven focused PHP suites check selection, rejection, attached facts and incremental
recovery; the read runner executes `read_*`, and the write runner executes both
`read_*` and `write_*` exports through the shared 12-slot
pool. PCH is the default, with `--no-pch` for include completeness. No native compiler
rebuild is implied. Construction fixtures remain part of the common S2S export suite.
Nested proofs include ordering, no partial creation on validation failure, COW/shared
identity, post-RHS root resolution, contextual literals and deferred cross-family gates.

For common data construction across vector/hash/mixed/dynamic:

```bash
python3 compiler/my-try/tests/data_initialization.py --results /tmp/FRESH_DATA_RESULTS
```

Five PHP suites export and check preparation/bindings, followed by `data_*` generated
programs and a direct runtime input-lifetime proof, using 12 continuously refilled
slots. PCH is enabled by default; `--no-pch` checks include completeness. Coverage
includes source ordering, duplicates, nested defaults, identity, checked failures,
large input and partial input/factory cleanup. This does not rebuild the compiler.

Run the focused branch/body slice, its related PHP controls and generated C++ programs:

```bash
python3 compiler/my-try/tests/control_flow.py --results /tmp/FRESH_CONTROL_FLOW_RESULTS
```

This uses the same 12-slot FPM/OPcache pool, with CLI fallback. The ordinary full
runner also consumes the control-flow execution manifest. This does not rebuild
the compiler itself natively.

For the focused ordinary-function checks, run:

```bash
python3 compiler/my-try/tests/functions.py --results /tmp/FRESH_FUNCTION_RESULTS
```

This runs the S2S preparation/export suite and eleven related PHP suites, then compiles
and executes its `function_*` programs through the shared 12-slot pool. The full
S2S and requested native runners also consume these fixtures. It does not rebuild
the compiler natively.

For nullable values, conditional/coalescing results and single-value `isset`/`empty` probes:

```bash
python3 compiler/my-try/tests/nullable.py --results /tmp/FRESH_NULLABLE_RESULTS
```

This runs related PHP suites and compiles/executes `nullable_*` S2S exports, including
checked-failure probes, through the same 12-slot pool. It does not rebuild the compiler
natively. The S2S manifest also makes these programs available to requested native runs.

For Result construction, state-preserving copies, checked scalar extraction and take:

```bash
python3 compiler/my-try/tests/results.py --results /tmp/FRESH_RESULT_RESULTS
```

This runs focused PHP suites and compiles/executes the `result_*` S2S exports,
including independent native state probes, using the shared pool and optional PCH.
Native bridge inputs test copying/extraction of structured failures; source construction
of error values remains deferred. Extraction probes cover all scalar payloads, error
and legacy sentinel states, single evaluation and unchanged targets on failure.
Take probes cover both spellings, every wrapper state, error truthiness/reset, owned
outputs, lazy skipping, source exceptions and incremental rejection/recovery.
Result condition/probe fixtures distinguish falsey successful payloads from sentinels,
include string `"0"`, and check every condition consumer plus single evaluation.
Conditional Result fixtures cover symmetric wrapper/scalar joins, state-preserving
copies, falsey Elvis payloads, true sentinels, lazy alternatives and owned results.
Result coalescing checks unwrapped scalar result types, retained falsey successes,
lazy fallback and exception propagation, unchanged sources, boolable rejection on
both sides, and incremental invalidation/recovery after a family change.
The runner also executes the focused wrapper-state and take runtime tests. `--no-pch`
checks generated include completeness without precompiled-header assistance.
This does not rebuild the compiler natively.

For mixed scalar boundaries, explicit casts, conditions and single-value probes:

```bash
python3 compiler/my-try/tests/mixed.py --results /tmp/FRESH_MIXED_RESULTS
```

This runs fourteen focused PHP suites and the `mixed_*` generated source/state fixtures.
It compiles the existing snippet runtime (`runtime/include/core/runtime.cpp`) once
per run and links it into each fixture; mixed is not header-only. `--no-pch` verifies
include completeness. PCH and fixture compilation share PHP-target and thread flags.
Native bridge probes supply shared dynamic/table inputs to verify copy identity and
wrong-kind failures without enabling source cross-carrier construction. The ordinary
full runner and requested native source/probe sweep use the same runtime-object helper.
Operation fixtures distinguish explicit casts from required extraction and condition
truthiness from `empty`/`isset`, including `"false"`, `"0"` and null. They cover condition
consumers, single evaluation, unchanged targets on failed casts, propagated source
exceptions, non-mutating table/object probes and runtime-rejected boxed conditions.
Selection fixtures cover mixed/mixed and mixed/scalar ternary, Elvis and coalescing
joins, including owned results, falsey presence, selected null fallback, lazy errors,
table-handle identity and deferred bare-null admission. Wrapper-left/mixed-fallback
coalescing fixtures cover nullable, structured Result and falseable success/absence,
all four scalar payloads, falsey successes, mixed-null/object fallback, lazy source/
fallback failures and the separate prepared extraction/normalization steps.
This builds generated programs, not the native compiler.

Run all PHP suites with retained evidence:

```bash
python3 compiler/my-try/tests/run.py --php-only --results /tmp/FRESH_PHP_RESULTS
```

Run lint, Python runner/style tests, the style gate, all PHP suites, generated LLVM/C++
programs and the host CLI checks:

```bash
python3 compiler/my-try/tests/run.py --results /tmp/FRESH_RESULTS --jobs 12
```

`--skip-style` explicitly omits the repository style gate and records that omission;
it does not suppress behavioral failures. `--php-only` omits the extra lint/style and
generated-program sweep (some PHP suites themselves execute native samples).

Native compiler validation remains an explicit checkpoint:

```bash
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /absolute/path/to/simplecpp \
  --candidate-revision FULL_COMMIT \
  --results /tmp/FRESH_NATIVE_RESULTS --no-stan --types-only --jobs 12
```

`--types-only` skips parked LLVM validation. Its default fixture pool covers PHP/native
source comparison plus C++ compilation/execution for `s2s.php`, `control_flow.php`,
`json.php`, `references.php` and `ownership.php`, supplementary probes and
rejection/recovery cases. Chapter 07 also exports ownership destruction/identity
probes whose unmodified base output must match the native compiler first. The suites
export their authored source cases so native coverage cannot silently omit the new
control-flow and reference/ownership chapters. Runtime-error probes first compare uninstrumented PHP/native
output, then compile/run the instrumented program. `S2S_Proof::control_flow()` adds a
shared portable span-move identity and unselected-arm dependency failure/retry/repair
proof, invoked separately through the native driver. The optional LLVM comparison
path also uses the same pool.

Generated fixture compilation uses the shared PCH helper by default; `--no-pch`
disables it. Each resumed attempt builds a fresh matching PCH and snippet runtime,
plus the JSON module object, while retaining native compiler objects for incremental
rebuilds. Compiler build flags and fixture flags are separate. The focused host
suites also cover callable/data/index/iteration contracts and boxed families.

Native evidence includes compiler source hashes, target toolchain hashes and
`harness_hashes.json` for the runner, fixture exporters and portable proof/driver.
When testing uncommitted adaptations, these hashes identify the tested overlay;
the candidate base revision alone is not the full tested source identity.

## Optimization and size measurements

Record the actual compiler command/build manifest, not an assumed optimization
default. The Chapter 04 native checkpoint used Clang 18 with `-O0 -g`; its later
`-O2 -g` size experiment is only a build plus smoke/parity proof, not a full optimized
regression checkpoint. Generated fixture flags are separate from compiler-build flags.

For comparisons, preserve the validated binary and use an isolated build directory
with the same sources, toolchain, runtime and flags except the intended variable.
Report exact bytes and MiB, debug/stripping status, LTO and shared-library boundaries.
Strip copies, not the proof binary. Shared-library compiler drivers can be tiny:
compare required compiler libraries as well, while distinguishing general system
dependencies and any external toolchain needed to finish source-to-native compilation.
Measured evidence belongs in the [portability review](portability/conversion_review.md),
not in a new default optimization policy or a performance claim.

## Isolation and evidence

Each native fixture writes `request.txt` in its own working directory. The single
native driver binary and PHP driver read that relative file; each invocation owns its
in-process compiler state. Recovery inputs are shared read-only. No shared mutable
request file serializes or races concurrent fixtures.

Each command gets a private temporary directory through `TMPDIR`, `TMP` and
`TEMP` (and request-specific `sys_temp_dir` under FPM), including PHP/native suites that inspect temporary-file cleanup. Output paths
and log names must be unique within a run. Stdout/stderr are retained per command;
`commands.json` is updated atomically under a lock and records command, working
directory, elapsed time, exit code, expected exit code, executor and selection reason. Timeouts kill the command's
process group, including compiler children, before collecting output and cleaning up.

The pool reports each completion immediately and collects all failures in the current
phase. Successful jobs do not hide failures; failed phases stop dependent phases.
Returned outcomes retain input order, while command journals show completion order.
The full runner writes `summary.json` even on failure and `php-summary.json` for PHP
suite outcomes. Native validation retains per-attempt command logs, source/toolchain
hashes and a success summary as described in the [portability review](portability/conversion_review.md).

## Generated C++ precompiled headers

`tests/run.py`, `tests/nullable.py`, `tests/results.py`, `tests/functions.py` and `tests/control_flow.py`
use `tools/cpp_fixture.py` to build one runtime PCH before scheduling generated C++
programs. The PCH lives in the fresh results directory under `cpp-pch/`; its build
uses the same compiler and flags as the fixtures and is recorded as `cpp-pch-build`
in the command journal. A failed PCH build stops the phase. There is no persistent
cache or silent uncached retry. Workers share the completed artifact read-only.

Pass `--no-pch` to any of these runners to compile with the original header visibility.
Use that mode when validating emitted include completeness: preloading runtime headers
can hide missing includes or affect overload visibility. The summary records `cpp_pch`.
PHP-only runs do not prepare a PCH. LLVM, native compiler construction, PHP suites'
internal native commands and `tools/native_validate.py` are unchanged.

Reducing cold compilation cost through compiler-selected runtime headers remains
[explicit dependency debt](planning/incremental_strategy.md#debt-precise-runtime-includes-for-faster-c-compilation).

## Reusing the runner

For compound RHS effects, run
`python3 compiler/my-try/tests/compound_effects.py --results /tmp/FRESH_COMPOUND_RESULTS`.
The shared pool checks compound/operator/logical/boxed preparation and three
generated programs for ordered snapshots, owner retention and failure preservation.
No PCH or native compiler rebuild is involved.

For same-type strict string and boolean comparisons, run
`python3 compiler/my-try/tests/string_identity.py --results /tmp/FRESH_STRING_IDENTITY_RESULTS`.
The shared pool checks preparation/rejections and existing operator, conversion and
boxed regressions, then builds two programs without PCH: ordered value comparisons
and embedded-NUL comparisons. Boolean coverage includes all truth pairs and eager
snapshot ordering. This does not rebuild the compiler natively.

For same-signedness fixed-width integer arithmetic and comparisons:

```bash
python3 compiler/my-try/tests/integer_widths.py --results /tmp/FRESH_INTEGER_RESULTS
```

Checks prepared result/conversion identities, rejection boundaries, existing
operator/conversion/compound/power regressions, and two generated programs for
field/call arithmetic, unsigned range, ordering and zero-divisor failures.
Uses the shared pool without PCH; does not rebuild the compiler natively.

For both metadata-registered string builder families:

```bash
python3 compiler/my-try/tests/runtime_builders.py --results /tmp/FRESH_BUILDER_RESULTS
```

Six focused PHP suites cover the builder contracts plus strings, runtime types,
core registration, callables and references. Ten generated programs exercise all
twenty registered helper signatures, independent copies, explicit aliases, mutable
and readonly reference boundaries, temporary observations, replacement during argument
evaluation, exact binary/UTF-8 bytes and negative-capacity errors. The two existing
native runtime builder tests also run. No PCH or native compiler rebuild is implied;
byte-span append is not admitted by this metadata slice.

For ordinary runtime type metadata and the vector/hash registration migration:

```bash
python3 compiler/my-try/tests/runtime_types.py --results /tmp/FRESH_TYPE_RESULTS
```

The focused runner checks typed decoding, private rejection/recovery, forward defaults,
registry capability dependencies, retained metadata across resets and existing catalog,
generic, observation and Result paths. Five generated programs use metadata-only
receipt/parcel providers plus production vector/hash, without parser/preparation/emitter
special cases. C++ runs without PCH to check header completeness; this is not a native
compiler rebuild. Methods, protocol registration and handle-lifetime contracts remain
outside this proof.

For environment and shell registrations:

```bash
python3 compiler/my-try/tests/runtime_environment.py --results /tmp/FRESH_ENVIRONMENT_RESULTS
```

Two PHP suites check metadata call admission/rejection and catalog regressions.
Generated programs distinguish missing/empty environment values, preserve failed
take outputs, test coalescing and evaluation-once, and prove source shadowing.
Fixed POSIX shell fixtures check text, empty, embedded-NUL and multi-buffer output,
plus nonzero child exit. A direct runtime fixture substitutes a failing pipe opener
without exhausting host resources. The child environment is supplied explicitly;
no host environment values or user commands are used as expected data. This Linux/
POSIX proof does not build the compiler natively or use PCH.

For executable CLI startup and registered argument helpers:

```bash
python3 compiler/my-try/tests/runtime_cli.py --results /tmp/FRESH_CLI_RESULTS
```

The focused runner checks real argv with no user arguments, empty strings, spaces,
UTF-8 and flag-like text; functions/methods in separate generated units observe the
same startup state. It checks snapshot independence and exactly one initialization,
source shadowing, rejected calls, S2S goldens and representative renamed/function-try
entrypoint probes. The runtime process regression is also executed. Startup uses
only the small CLI-context header; public helpers retain their ordinary binding
header. No PCH or native compiler rebuild is implied.

For the core process-memory utility catalog:

```bash
python3 compiler/my-try/tests/runtime_memory.py --results /tmp/FRESH_MEMORY_RESULTS
```

Three PHP suites check signatures, rejected/held utility calls, catalog reset and
catalog/generic regressions. Three generated programs cover both memory helpers,
all arities and bool choices, evaluation-once effects and source shadowing. Results
are checked as nonnegative process-byte observations (positive on Linux with /proc),
not equal across successive samples or equal to PHP allocator measurements. No PCH,
native compiler rebuild or cross-platform proof is implied.

For the opt-in strict regex catalog:

```bash
python3 compiler/my-try/tests/runtime_regex.py --results /tmp/FRESH_REGEX_RESULTS
```

Five PHP suites cover explicit selection, rejection without selection, fresh
identities with retained metadata, held signatures and catalog/generic/capability
regressions. Nine generated programs exercise all 18 signatures, named/nested
captures, byte offsets, UTF-8 matching, empty success versus failure, Result
coalescing, split flags, source shadowing and ordered input snapshots. The runner
also executes the existing native regex regression. PCRE2-8 development files and
pkg-config are required; regex.cpp is compiled once and linked explicitly. This
does not build the compiler natively, use PCH or enable regex by default.

For the strict filesystem module catalog:

```bash
python3 compiler/my-try/tests/runtime_filesystem.py --results /tmp/FRESH_FILESYSTEM_RESULTS
```

Eight PHP suites cover registry capability rules, catalog selection/reset, rejected types/arities, unavailable
legacy/internal/handle APIs and shared Result/callable regressions. Five generated programs exercise all 19 bindings using
private fixture directories: overwrite/empty/error results, sorted scans, paths,
mutations, wrong-kind rmdir preservation and checked snapshot reads. The runner uses
filesystem/snapshot providers bundled in the snippet runtime and runs the existing Linux snapshot
race/error regression. No PCH or native compiler rebuild; this is a Linux proof,
not cross-platform validation or automatic module-linker integration.
Eleven additional generated programs cover all three Result families with vector/hash
payloads, copy isolation, nested containers, full ternary joins, lazy coalescing and
sentinel states, including throwing required extraction. PHP rejects unsupported truthiness/empty, mismatched payloads and
noncopyable contents; incremental element-copy edits reject and recover to clean output.
An unfamiliar registered `parcel<T>` provider proves Result transport without new
preparation or lowering branches. Registry-only tests distinguish argument formation
constraints from selected-parameter copy dependencies and handle-copy independence.

For the complete strict datetime module catalog:

```bash
python3 compiler/my-try/tests/runtime_datetime.py --results /tmp/FRESH_DATETIME_RESULTS
```

Three PHP suites check registry selection/reset, strict-only names and argument
rejection alongside catalog/generic regressions. Six generated programs cover all
11 bindings: UTC epoch/leap-day output, local formats under explicit `TZ=UTC0`,
parse-error Results, clocks/sleep, ordered argument snapshots and source shadowing.
Unsigned monotonic outputs are checked in Python because source uint64 binary
comparisons remain unsupported. The runner builds and links the datetime provider
once, also runs the existing native runtime datetime regression, and uses no PCH.
This is not a native compiler rebuild or automatic module-linker integration.

For runtime-module string bindings:

```bash
python3 compiler/my-try/tests/runtime_strings.py --results /tmp/FRESH_RUNTIME_STRING_RESULTS
```

Four PHP suites cover registration/rejection and catalog/generic/source-constant regressions. Thirty
generated programs check exact bytes, empty/non-ASCII inputs, all three predicates,
purity, shadowing, checked boxed conversion failure, trim overloads/byte masks,
binary-safe hex/replacement, ordered value snapshots, padding modes/errors/byte truncation,
all int/float formatting arities, constant shadowing/labels and exact signed-64 limits,
typed split/join, code-point slices, byte comparison, false-result adapters and explicit
byte/Unicode helpers. One additional direct runtime test runs split limits under UBSan,
including signed-minimum negative limits. All C++ tasks share the same bounded pool.
Uses the shared pool without PCH to
check header completeness; does not rebuild the compiler natively.

For runtime observation boundaries:

```bash
python3 compiler/my-try/tests/runtime_observation.py --results /tmp/FRESH_OBSERVATION_RESULTS
```

Four PHP suites check metadata, shared admission and ordinary reference regressions.
Five generated programs exercise local/readonly inputs, temporaries, temporary-owner
fields, noncopyable vectors and both boxed families. Concrete test-only metadata
isolates the boundary independently of generic registration. AddressSanitizer checks
invalid accesses; leak detection is disabled for sandbox compatibility. This does
not rebuild the compiler natively; production `count` has its separate proof below.

For production `count` bindings and reusable generic runtime inference:

```bash
python3 compiler/my-try/tests/runtime_generics.py --results /tmp/FRESH_RUNTIME_GENERIC_RESULTS
```

The runner checks metadata failures, exact/repeated/defaulted pattern matching,
ambiguous overlaps, inferred returns, provider identity across calls, stale bindings,
source shadowing and incremental rejection/recovery. Generated programs cover typed
and boxed counts, noncopyable payloads, temporary owners, argument effects, generic
return applications and checked runtime kind errors. It also runs existing catalog,
observation, JSON and callable PHP checks. AddressSanitizer is enabled without leak
detection; this is generated-program validation, not a native compiler rebuild.

Import from `tools/test_runner.py`; the scheduler is independent of test language:

```python
commands = CommandRunner(result_path / 'logs', repository_root, jobs=12)

def check_fixture(source, executable, expected):
    commands.run(source.stem + '-compile', ['clang++', source, '-o', executable])
    return commands.run(source.stem + '-execute', [executable], expected=expected)

outcomes = run_tasks([
    Task(name, lambda source=source, executable=executable, expected=expected:
         check_fixture(source, executable, expected))
    for name, source, executable, expected in fixtures
], jobs=12)
commands.close()  # Prefer a with block or try/finally in real callers.
```

`tests/runner_test.py` proves the default concurrency bound and continuous slot refill
using synchronization events, stable result ordering, aggregated failures, command
isolation, expected nonzero exits, launch-failure evidence and child-process timeout
cleanup.

## PHP execution

The shared `CommandRunner` defaults to **FPM with OPcache** for PHP scripts and
`php -r` snippets. Both test entrypoints accept `--php-executor auto|fpm|cli`:

- `auto` (default): try matching FPM, then use CLI if discovery/startup/health checks fail.
- `fpm`: require FPM for eligible commands; unavailable FPM is an error.
- `cli`: explicitly retain subprocess execution for all PHP.

Install matching `php`/`php-fpm` versions and `cgi-fcgi` to use FPM. The runner discovers
versioned FPM binaries via PATH or `/usr/sbin`, matches the exact PHP version, and uses
the CLI INI and scanned extension directories. Unix sockets and PHP's POSIX extension
must be available. Startup fallback is printed and recorded in the command journal;
failed requests are **never retried through CLI**.

Each runner lazily owns a private FPM master with `jobs` single-worker pools. A slot
is leased until its request finishes or is cancelled, preventing a timeout from
killing a worker reused by another command. Pools share OPcache; timestamp checks and
fresh-file protection remain enabled, JIT and Xdebug are disabled for FPM. Existing
system FPM services are untouched. `close()` / a context manager stops the private
master; both entrypoints close it in `finally`, with an exit cleanup as a backstop.

The host adapter supplies script arguments, working directory, binary stdin and separate
stdout/stderr capture and private temporary files. Every request starts fresh PHP
state. Returning normally means success; uncaught exceptions/fatal errors mean failure.
`exit()`/`die()` cannot provide process exit status through FPM and are rejected as
incomplete execution. This is host test infrastructure, not portable compiler source.

CLI-specific commands stay explicit: PHP interpreter options such as `-l`, compiler
CLI behavior checks, build/conversion tools using exit status, and `tests/native.php`
which launches `PHP_BINARY` as a child CLI. Call `run(..., cli_reason='...')` for such
contracts; the reason is recorded even in FPM-required mode. FPM does not emulate
`PHP_SAPI` or `PHP_BINARY`. The style checker also uses the shared executor (one persistent worker for its
sequential tokenizer requests). `MY_TRY_PHP_EXECUTOR=cli|fpm|auto` selects its backend;
the main runner passes its selection to child Python tools. Direct shell invocations
of `php` are unchanged.

Run the executor's focused integration proofs (private sockets required):

```bash
MY_TRY_TEST_FPM=1 python3 compiler/my-try/tests/php_executor_test.py
```

These prove parallel isolation, argument/byte preservation, worker-state reset,
OPcache refresh, unavailable-FPM fallback, no failed-request retries, and timeout
cleanup of PHP child processes followed by worker recovery. Ordinary runner tests
also cover the shared scheduler and subprocess paths.

The 2026-10-02 checkpoint retained evidence in `/tmp/my-try-fpm-default-final-20261002/`
and `/tmp/my-try-fpm-parity-final-20261002/`: 41/43 PHP suites passed, with the existing
`model.php` and `structure_access.php` failures unchanged; 341 source fixtures and
37 rejections matched CLI/native bytes, and 74 supplementary probes passed at 12 jobs.
This reused the existing native binary; it is not a new native portability checkpoint.

The [CLI/FPM comparison](portability/php_cli_fpm.md) records measured PHP-only benefits
and limitations. `tools/benchmark_php.py` preserves explicit benchmark modes independent
of the runner default.

`python3 compiler/my-try/tests/index_compound.py --results FRESH` checks shared
indexed compound preparation and generated C++: key/RHS sequencing, vector resize
and replacement, nested COW/shared identity, and missing/deleted/wrong-kind paths.
It does not rebuild the compiler natively.


V-L04 focused float comparisons/mutations: run
`python3 compiler/my-try/tests/float_operators.py --results FRESH` on dev002.
This proves six exact float/float comparisons and four value-snapshot mutations,
including preparation cleanup/recovery and native special values. It is not a
whole operator-matrix run; see [the bounded report](planning/float_operators_l04.md).


V-L05 numeric logical checks:
`python3 compiler/my-try/tests/numeric_logical.py --results FRESH` on dev002.
The bounded proof covers canonical numeric !/AND/OR, word aliases and mixed numeric
pairs, with independent counts/traces, exceptions, truth snapshots and preparation
recovery. See [scope and results](planning/numeric_logical_l05.md).

`python3 compiler/my-try/tests/string_comparisons.py --results FRESH` verifies
V-L03 on dev002: seven exact string comparisons, unsigned-byte expected ordering,
embedded NUL/high bytes, once-only ordered effects and exceptions, local/property
snapshots, excluded types, cleanup and failure/repair/fresh equivalence. See the
[bounded report](planning/string_comparisons_l03.md). No full matrix or native compiler rebuild.

`python3 compiler/my-try/tests/guarded_operators.py --results FRESH` verifies the
[four-carrier guarded-wrapper slice](planning/guarded_wrappers_02.md) on dev002: 228 binary
and 16 unary signatures, checked states, eager snapshots, lazy guards, errors,
condition/selection preservation, exclusions and retained-session repair. The
runtime extraction controls use existing APIs; no runtime implementation changes,
full matrix run or native compiler rebuild is implied.

`python3 compiler/my-try/tests/guarded_float.py --results FRESH` verifies the
[guarded-float slice](planning/guarded_float.md) on dev002: 144 binary and 12 unary
signatures across four carriers. It checks signed zero, NaN/infinities, missing
states, exact scalar results, snapshots/lazy effects, division_by_zero, separate
selector/take semantics, excluded types/operators and retained-session recovery.
Use `guarded_operators.py` for the companion int regression; no full matrix or
native compiler rebuild is implied.

`python3 compiler/my-try/tests/guarded_mixed.py --results FRESH` verifies the
[mixed-numeric guarded slice](planning/guarded_mixed.md) on dev002: 192 binary
signatures, independent integer/float states, arithmetic promotion, lazy truth
conversion, errors, snapshots, alias precedence and retained repair. Companion
int/float guarded runners retain mixed-ordering and other unsupported-form rejection checks;
only newly admitted negatives were retired. No full matrix or native rebuild.

`python3 compiler/my-try/tests/guarded_bool.py --results FRESH` verifies the
[guarded-bool slice](planning/guarded_bool.md) on dev002: four unary and 48 binary
signatures, payload/sentinel distinctions through source construction/assignment,
lazy effects, truth snapshots, separate selector/take semantics, exclusions and
retained recovery. Numeric guarded suites remain companion regressions; no runtime
change, full matrix or native compiler rebuild is implied.

`python3 compiler/my-try/tests/guarded_string.py --results FRESH` verifies the
[guarded-string ordering slice](planning/guarded_string.md) on dev002: 48 signatures,
independent unsigned-byte ordering, missing states, ordered effects, local/property
snapshots, selector/take preservation and retained recovery. Equality and string
logical operators remain separate. No full matrix or native compiler rebuild.

`python3 compiler/my-try/tests/guarded_cross.py --results FRESH` verifies the
[cross-family slice](planning/guarded_cross.md) on dev002. It runs the five payload
fixtures in `--cross` mode through the shared runner, builds one shared snippet
runtime object, and checks exactly 660 distinct binary signatures. Independent
state/value oracles check family-specific errors, left precedence, lazy effects,
local/property snapshots and 72 retained repair sequences. Ordinary fixture modes
remain regression controls. No full matrix or native compiler rebuild is implied.

`python3 compiler/my-try/tests/guarded_bool_xor.py --results FRESH` verifies all 24
guarded-bool XOR signatures using `guarded_bool.php --xor`, with exact signature-set
validation, eager source/error products, local/property snapshots, assignment
precedence and 24 retained recovery sequences. It reuses the independent bool state
oracle and snapshot helper. AND/OR and cross-family fixture modes retain their
existing scope; only newly valid bool-XOR negatives are retired. See the
[dev002 proof](planning/guarded_bool_xor.md). No runtime changes or full matrix run.

`python3 compiler/my-try/tests/guarded_spaceship.py --results FRESH` verifies 48
canonical-int/string spaceship signatures using the existing payload fixtures in
`--spaceship` mode. It checks exact signature sets, int -1/0/1 results, integer
boundaries, complete byte strings, errors, eager effects, local/property snapshots
and 48 retained recovery sequences. Exhaustive focused type exclusions retain
fixed-width, float, bool and mixed-payload boundaries. See the [dev002 proof](planning/guarded_spaceship.md).
This is a bounded suite, not a full matrix or native compiler rebuild.

`python3 compiler/my-try/tests/numeric_xor.py --results FRESH` verifies all 100
canonical numeric XOR signatures through `guarded_mixed.php --numeric-xor`.
Preparation proofs retain each truth conversion's actual payload type; native
oracles check eager reach, signed zero, NaN/infinities, large integers, source errors,
local/property snapshots and assignment precedence. The exact signature set and
100 retained recovery sequences are required. Shared recovery now uses an unresolved
call as its invalid edit so ordinary plain equality need not be misclassified.
See [dev002 evidence](planning/numeric_xor.md); no runtime changes or full matrix run.

`python3 compiler/my-try/tests/nullable_int_equality.py --results FRESH` verifies
all 12 nullable/plain canonical-int equality and identity signatures on dev002.
It checks state-preserving preparation, integer boundaries, empty versus zero,
eager RHS reach on empty left, distinct source errors, local/property state
replacement, 12 retained recovery sequences and 136 excluded sources. Literal-null
presence and throwing guarded arithmetic/ordering are controls. See the
[proof](planning/nullable_int_equality.md); no runtime changes or full matrix run.


### Nullable bool/string equality — 2026-10-19

`python3 compiler/my-try/tests/nullable_bool_string_equality.py --results FRESH`
verifies 24 `== != === !==` signatures over nullable/nullable and nullable/plain
matching bool/string payloads. The shared `wrapper_equality_fixture.php` also
serves the existing nullable-int runner. It checks complete-state decisions,
eager error ordering, empty versus false/empty-string distinctions, full byte
comparison, local/property replacement snapshots and retained recovery. Native
oracles use presence plus exact payload equality, independently of compiler helpers.
See the [dev002 proof](planning/nullable_bool_string_equality.md). No runtime changes,
full matrix run or native compiler rebuild.


### Nullable-float equality — 2026-10-19

`python3 compiler/my-try/tests/nullable_float_equality.py --results FRESH` verifies
12 matching nullable/plain float equality and identity signatures. The shared
nullable equality fixture distinguishes value comparison (NaN unequal, signed zeros
equal) from bit-preserving storage checks. It covers eager source errors, presence,
local/property replacement and retained recovery. Plain float identity, mixed
payloads and Result equality remain negative controls. See the
[dev002 evidence](planning/nullable_float_equality.md).


### Structured-error Result equality — 2026-10-19

`python3 compiler/my-try/tests/result_equality.py --results FRESH` verifies 48
signatures across int/float/bool/string. The shared `wrapper_equality_fixture.php`
(formerly nullable-only) uses separate state equality and identity oracles, five
valid error payloads with independently varied fields, eager source failures,
bit-preserving float storage observers, local/property replacement and recovery.
`error::$no_error` construction rejects. Sentinel families, mixed payloads,
cross-family comparisons and direct `error` comparisons remain negative controls.
See [the dev002 proof](planning/result_equality.md); no runtime change/full matrix.


### False-sentinel bool equality — 2026-10-19

`python3 compiler/my-try/tests/false_bool_equality.py --results FRESH` proves 12
signatures over `result_or_false<bool>` and plain bool. The shared wrapper fixture
uses state-sensitive equality for two carriers and visible-bool comparison for
identity and wrapper/plain pairs. It proves sentinel/payload-false distinction,
eager errors, snapshots, recovery and literal bool construction; explicit sentinel
comparisons and non-bool payloads remain negative controls. See the
[dev002 report](planning/false_bool_equality.md).


### Two-sentinel bool equality — 2026-10-19

`python3 compiler/my-try/tests/bool_sentinel_equality.py --results FRESH` verifies
12 `result_or_bool<bool>` equality/identity signatures across four carrier states
and two plain bool values. The shared bool-state oracle distinguishes carrier
equality from visible identity; false/true literal construction, explicit sentinel
construction, eager errors, local/property snapshots and recovery are checked.
Both absent-payload states still fail guarded negation. See the
[dev002 report](planning/bool_sentinel_equality.md).


### Non-bool sentinel equality — 2026-10-19

`python3 compiler/my-try/tests/nonbool_sentinel_equality.py --results FRESH` proves
72 signatures over two carriers and int/float/string, with states, scalar edges,
eager errors, local/property snapshots, construction and retained recovery.
`nonbool_sentinel_deferred.py` separately observes 24 native wrapper/plain equality
instantiations, recording compiler outcomes and diagnostics. Its task success means
an observation was collected, not that the comparison compiled. See the
[dev002 report](planning/nonbool_sentinel_equality.md).


### Sentinel/plain equality repair — 2026-10-19

`nonbool_sentinel_equality.py` now verifies the 24 newly admitted ordinary
carrier/plain comparisons alongside the previous 48 (72 total, 72 recoveries).
`sentinel_plain_runtime.py` verifies their direct runtime behavior, preserved bool
and boxed delegation, and 20 scalar/mixed-payload/fixed-width compile rejections.
The historical `nonbool_sentinel_deferred.py` remains an observation tool: its
24 instantiations now compile. The earlier failure archive remains unchanged.
See [the repair report](planning/sentinel_plain_equality.md).


### Carrier updates and V-R10 — 2026-10-19

`python3 compiler/my-try/tests/carrier_updates.py --results FRESH` verifies 32
canonical numeric carrier updates, native prefix/postfix contracts, source snapshots,
state/error preservation, references, local/instance/static storage, once-only static
qualifiers, null owners, error ordering and retained recovery.
`carrier_updates_runtime.py` runs the original nullable native test and 80 negative
native update controls. See [dev002 evidence](planning/carrier_updates.md).


### Canonical-int arithmetic compounds — 2026-10-19

`python3 compiler/my-try/tests/int_carrier_compounds.py --results FRESH` verifies
48 same-carrier/plain `+= -= *= /=` signatures. It checks preparation, recovery,
state/error precedence, RHS replacement effects, source snapshots, aliases, nested
updates, locals/references, stable property owners and static qualifiers.
See [dev002 evidence and proof boundaries](planning/int_carrier_compounds.md).


### Canonical-int remainder/bitwise/shift compounds — 2026-10-19

`python3 compiler/my-try/tests/int_carrier_compounds.py --group bitwise --results FRESH`
verifies 72 same-carrier/plain `%= &= |= ^= <<= >>=` signatures. The shared fixture
retains arithmetic mode separately and uses safe shift pairs, signed remainder,
bit patterns, distinct RHS/computation errors and the existing state/snapshot/owner
proof. See [dev002 evidence](planning/int_bitwise_compounds.md).


### Canonical-int cross-family compounds — 2026-10-19

`python3 compiler/my-try/tests/int_carrier_compounds.py --group cross --results FRESH`
verifies all 120 ordered distinct-carrier signatures. The shared fixture independently
models left/right states and checks ordered extraction kinds, destination wrapping,
error precedence, RHS effects, snapshots, stable owners, write-back and recovery.
Arithmetic and bitwise modes retain the preceding 120 same-carrier/plain signatures.
See [dev002 evidence](planning/int_cross_compounds.md).


### Exact-float compounds — 2026-10-19

`python3 compiler/my-try/tests/float_compounds.py --results FRESH` runs four scalar
prerequisites first, then all 96 carrier signatures. It shares
`numeric_compound_fixture.php` with the retained int modes and adds float state/value
oracles: NaN classification, signed zero, bit-preserving untouched storage and
replacement snapshots. See [dev002 evidence](planning/float_compounds.md).


### Mixed numeric ordering O1 — 2026-10-19

`python3 compiler/my-try/tests/mixed_ordering.py --results FRESH` runs eight scalar
prerequisites first, then 192 carrier signatures in 24 family-pair tasks. It checks
precision boundaries, NaNs/zeros/infinities, ordered errors and conversions, snapshots,
property-owner rebinding and 50 retained recovery sequences. See [dev002 evidence](planning/mixed_ordering.md).


### Float-destination mixed compounds C2W — 2026-10-19

`python3 compiler/my-try/tests/widening_compounds.py --results FRESH` verifies four
scalar prerequisites before all 96 carrier signatures. The shared numeric fixture
retains independent float destination and int RHS representations through precision
boundaries, extraction/conversion facts, errors, snapshots and storage/recovery checks.
See [dev002 evidence](planning/widening_compounds.md).


### Cross-family strict identity E3I — 2026-10-19

`python3 compiler/my-try/tests/cross_identity.py --results FRESH` first verifies
runtime normalization over all carrier pairs, then all 96 source signatures with
state, exception-order, local/property snapshot and retained recovery proofs.
`--runtime-only` selects the prerequisite gate. Run on dev002; see
[the dated evidence](planning/cross_identity.md).


### Mixed numeric strict identity E4I — 2026-10-19

`python3 compiler/my-try/tests/mixed_identity.py --results FRESH` verifies unchanged
runtime support, then four scalar prerequisites and 96 carrier signatures using
the shared identity state/snapshot fixture. `--runtime-only` selects the prerequisite
gate. Run on dev002; see [the dated proof](planning/mixed_identity.md).


### Bool/numeric strict identity E5BNI — 2026-10-19

`python3 compiler/my-try/tests/mixed_identity.py --group bool-numeric --results FRESH`
verifies runtime support first, then eight scalar prerequisites and 192 carrier
signatures with independent state normalization and source snapshots. Add
`--runtime-only` for the prerequisite gate. Run on dev002; see
[the dated proof](planning/bool_numeric_identity.md).
