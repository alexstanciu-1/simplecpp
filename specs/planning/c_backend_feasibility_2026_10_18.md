# Additional C lowering: feasibility and work assessment
Doc Status: planning

Current direction (2026-10-19): the agreed next implementation is concrete
specialization inside the existing C++ backend, not an additional C target. See the
[architectural decision](cpp_template_prelowering_decision_2026_10_19.md) and
[implementation plan](cpp_template_prelowering_plan_2026_10_19.md). The assessment
below preserves the earlier alternatives and their evidence.

Date: 2026-10-18. Inspection only; no implementation authorization implied.
Scope: current `compiler/my-try` shared preparation with an additional C target,
retaining the C++ runtime. The earlier money benchmark used legacy S2S snapshots;
it proves a native boundary/performance opportunity, not my-try C-emission support.

## Finding

An additional C backend is feasible without a new parser or second semantic analyzer.
It is a substantial backend/runtime-boundary project rather than a spelling switch.
The useful first direction is hybrid output: C application bodies plus reusable
C++ runtime/type adapters. A fully C runtime, direct LLVM work, a new build cache,
full source-language expansion and concurrent dual-backend publication are non-goals.

The existing C++ backend comprises about 4,221 lines across 19 PHP files at inspection.
That is orientation, not an estimate of replacement size: C must explicitly implement
work currently delegated to C++ expression temporaries, RAII, templates and dispatch.

## Reusable ownership and concrete gaps

| Owner inspected | Reuse | Required C work |
| --- | --- | --- |
| `04_analyze/prepare/data/structures.php` | Resolved types, places, calls, conversions; copy/consume/reference/observe boundary decisions | Consume these facts, never re-resolve source symbols or duplicate semantic admission |
| `04_analyze/prepare/data/lifetimes.php` | Lexical regions, storage establishment order and borrowing origins | Derive explicit target cleanup paths; these facts are not an executable destruction schedule |
| `04_analyze/prepare/semantics/` | Constructor selection, inheritance/dispatch contracts, defaults, captures, value boundaries | Audit each supported family for sufficient facts; repair genuine semantic gaps in shared preparation |
| `05_backend/cpp/types.php`, `callable_bindings.php`, `runtime_catalog.php` | Existing separation of canonical type identity from target binding | Separate C representations and C ABI adapters; existing `cpp_types`/callable/property bindings are not C-compatible |
| `05_backend/cpp/declarations.php:528` and `syntax.php` | Existing required evaluation order and prepared operation selection | Statement-producing C expression lowering with ordered temporaries and lazy branches; no C++ lambdas/references/auto |
| `05_backend/cpp/structures.php`, `generate.php` | Fragments keyed by preparation owners and completion versions, stable artifacts, live dependency requirements | C-owned fragments, operation-specialization requests and precise dependencies; do not store C spelling in semantic identities |
| `compiler/compile.php:294` | Publish-before-acknowledge ordering | Current success clears the one shared change handoff; multiple retained consumers require separate acknowledgment/retirement design |
| `compiler/output_publication.php:62` and `:219` | Locked, content-aware publication and recovery | Currently accepts C++ artifact records and `.cpp`/`.hpp` paths only; add target/language-aware artifact admission |
| `06_native/cpp_build.php:65` | Native dependency checks, object reuse and explicit runtime-provider linkage | Currently forces g++ driver mode/C++23 and applies C++ PCH to generated units; separate C17 and C++ actions and link together |

The standalone native consumer above is not the legacy project's Ninja builder.
Mixed-language support must be added to the build path actually selected for the
slice; do not claim changing one automatically updates the other.

## First architectural choice: hybrid C output

Keep complex runtime implementation in C++. Emit C functions for application
bodies, with concrete C declarations or opaque handles where appropriate. Compile
C++ adapters/type shells separately and reuse their objects. The bridge must not
simply call the old C++ implementation of the entire application method; its job
is representation, runtime operations and dispatch, while the application algorithm
really moves to C.

For an initial class slice, existing C++ source-type layouts can remain in generated
type shells. Construction, shared ownership, field access and destruction use a
C ABI. Later, virtual-method shells can forward to C bodies. This retains a real
layout owner and enables existing runtime specializations to consume the original
C++ type. Generating these shells needs an intentional split of declaration/layout
and body emission, not copying the entire C++ backend or postprocessing its text.

A second alternative is C-owned source-record layouts plus a runtime lifecycle
interface. That can reduce accessor calls, but cannot pretend a C struct is an
arbitrary existing C++ class: virtual bases, shared-self support, member lifetimes,
ABI, and container payloads need explicit adapters. Defer that larger representation
change until the hybrid path has a proof. Plain scalar-only structs can use direct
C layouts in the first scalar slice without making this general class promise.

## Work not solved by shared semantic preparation alone

1. **Evaluation order and temporary lifetimes.** Produce statements plus a value/place
   rather than rendering every expression as one text string. Preserve left-to-right
   conversions, short-circuiting, selected arms, nested calls and loop conditions.
   C's argument evaluation order is not the source contract. Do not use GNU statement
   expressions as a substitute for a clear lowering owner.
2. **Copy/move/drop.** Emit explicit retain/copy/move/reset/release for source storage
   and temporaries. Cover return, break, continue, branch exits, self-assignment,
   partially constructed values and failure exits. Existing `clean`/`unset` admission
   is not a general automatic-cleanup plan. Prefer one C lowering cleanup owner.
3. **Runtime failure channel.** C++ runtime operations may throw even without authored
   throw/catch. Catch failures at C++ boundaries, retain the error identity/details,
   propagate through C and perform cleanup, then deliver the specified outer error
   behavior. The benchmark's catch-all `-1` is not a production error contract.
   Authored throw/catch/finally remain Chapter 13 pending work and must not silently
   become a prerequisite for the initial supported C subset.
4. **Arithmetic and conversion semantics.** Reuse settled decisions, but preserve
   overflow/range/conversion/division/shift behavior through appropriate helpers.
   The benchmark's bounded integers do not authorize raw signed-C arithmetic for
   arbitrary language integers. Follow the semantic and runtime family contracts.
5. **Containers and wrappers.** Strings, vector/hash, mixed/dynamic, nullable/result,
   unique/shared/weak handles need concrete C-facing operations. Preserve copy versus
   shared identity, reference invalidation and result states, not just output values.
6. **Dispatch and closures.** Inheritance, checked casts, interfaces, bound receivers
   and closures need shells or explicit function/environment tables with destruction
   and copy operations. C has no automatic substitute for the current `std::function`,
   generated lambdas and C++ virtual layout. Treat these as subsequent slices.

`specs/expression_evaluation.md`, `specs/array_semantics.md`,
`specs/destructors_v0_2.md`, `specs/error_values_and_take.md` and
`specs/runtime_module_metadata.md` remain authorities when implementation begins.
No semantic change is proposed by this assessment.

## Retained specializations and invalidation

Caching `vector<MyClass>` work is plausible, but first name the owned artifacts:

- Semantic application identity and resolved operation/copy/lifetime contracts:
  already the shared type/preparation domain.
- Backend representation, ABI declarations and demanded adapter operations:
  C-backend-owned derived artifacts, with dependencies on the relevant facts.
- Compiled adapter object: native build reuse using actual inputs/toolchain/flags.

Emit demanded concrete operations (for example append/read/drop), not indiscriminately
all members of every template. A specialization key needs canonical type arguments,
representation/ABI version and relevant operation/layout contracts. Native reuse
also depends on runtime headers, compiler and flags. Keep specialization dependencies
precise: a method-body edit need not invalidate a type layout or vector adapter;
a field/layout/copy-policy change can. Preserve shared class-handle semantics inside
containers; a vector of inline structs is a different case.

For the first backend, select C or C++ for a compilation session, resetting backend
output state on target changes. This is a bounded option for avoiding simultaneous
consumer state. To retain both backends across target switches, design per-consumer
progress and deletion/retirement handling before either clears the shared handoff.
Version checks alone do not recover every deleted owner after its notification is lost.

No new native cache is proposed. Compiler retained fragments and native build reuse
solve different problems; compose them through stable published artifacts.

## Suggested delivery slices and effort

The earlier calendar estimates are withdrawn: they did not adequately account for
this project's demonstrated development pace or the existing data-driven protocol
and capability foundations. Establish pace from one compiler-emitted vertical slice.
The milestones below express scope, not a calendar forecast.

| Milestone | Proof boundary |
| --- | --- |
| Scalar C backend | Literals, exact supported scalar operations, plain structs, calls, ordered expressions, branches/loops and stable multifile C artifacts |
| Runtime-backed C slice | One class plus vector specialization, constructor/method calls, strings, explicit copy/release, early returns and failure cleanup; retained native adapter reuse |
| Broad current-backend coverage | Extend common recipes to other registered families, then cover remaining inheritance/dispatch and callable/closure operations with focused invalidation proofs |

A runnable scalar demo can be much smaller than the first milestone; it must not be
presented as a completed backend. Full accounting is not the first acceptance target:
it combines containers, inheritance, closures, object lifetimes and multiple modules.
Current unsupported language features remain unsupported on both targets.

Minimum proofs for a useful slice: compile emitted `.c` as C17; same behavioral
fixtures against both backends; evaluation-order effects; copy/alias/move/lifetime
checks; failure cleanup; unchanged/body/signature/layout/deletion edits; unchanged
adapter objects after body edits; failure/recovery publication; compiled C++ runtime
linkage. Compiler implementation PHP execution and generated C execution are separate
from portability/native rebuilding of the compiler itself.

## Decision boundary

Implementation crosses backend representation, runtime ABI, publication and native
build owners. Existing instructions require agreement before that redesign. This
assessment makes those areas explicit without changing them. Recommended next
agreement: hybrid C output, one active target per session initially, scalar first,
then one runtime-backed object/container slice, retaining existing C++ output.

## Registry/protocol audit — 2026-10-18 follow-up

**Yes: the existing registry and attached prepared facts are usable as the semantic
foundation for data-driven C lowering.** Do not introduce another capability registry
or rediscover source semantics in the C backend. This is stronger evidence of reuse
than the initial high-level assessment captured.

### Confirmed reusable mechanisms

- `compiler/my-try/03_parse/types/model.php:65`: `generic_contract` contains
  `copyable_value`, `value_storable`, `hashable`, `comparable`, `result_payload`.
  These are semantic admission contracts, not a complete target ABI descriptor.
- `model.php:416`: parameter recipes distinguish formation requirements from
  capability dependencies and defaults. A generic application can exist without
  being copyable; copying depends only on the designated argument positions.
- `model.php:1233`: `intern_application` interns a definition plus ordered canonical
  arguments and value modifiers. Validation runs before returning an existing
  application. Reuse these identities in-session; numeric allocation IDs alone
  are not durable cross-process cache keys.
- `model.php:1349`: registered capability rules recursively delegate argument
  queries. They explicitly refuse to answer unresolved source-record capabilities.
- `04_analyze/prepare/semantics/capabilities.php:12`: source capability queries
  require completed declarations and register preparation dependencies. Shared
  handle copying is independent of payload-field copyability. Source facts remain
  attached to prepared declarations rather than cached a second time in the registry.
- `runtime/metadata/modules/core.json:8`: vector formation requires storable
  elements; copyability depends on element copyability. Its construction, indexing,
  append and iteration contracts are metadata. Hash additionally records key
  requirements and separate parameter dependencies.
- `compiler/my-try/compiler/runtime_catalog/protocols.php`: generic registration
  substitutes owner parameters into initialization, indexing and iteration recipes.
  It has no vector/hash-name dispatch. Current references admit a concrete type or
  one owner parameter, not unrestricted nested open type expressions.
- `04_analyze/operators/model.php:120`: an `operator_decision` already carries the
  selected semantic operation, operand conversions and result type. Calls and
  value boundaries likewise carry resolved targets and copy/consume/bind/observe
  decisions. These are the correct inputs for operation-family C emitters.
- `05_backend/cpp/index_write.php:42`: even current C++ indexed mutation already
  uses one family-independent lowering path and delegates storage details to runtime
  overloads. C can replace that target binding with a concrete generated ABI call.

### Concrete vector example

For an admitted `vector<MyClass>` append, preparation has already established the
canonical application, source storage policy, append protocol, element type and
conversion/value boundary. The C backend should select a target recipe by the
prepared operation and bound representation, request the concrete append adapter,
and emit the ordered operands plus call. The adapter's object can be reused by all
callers with the same ABI contract. Copy operations separately select handle-retain
versus value-copy from the actual storage/representation, not from a guessed type name.

This is a recipe specialized by supplied facts, not a hand-authored implementation
for each `MyClass`. Concrete C ABI names, layouts and execution contracts still need
to be supplied; permissions alone do not synthesize correct C implementations.

### What is not already in the registry

The inspected registry does not provide C layout/alignment, C call signatures,
copy/move/drop entrypoints, bitwise-copy safety, no-op-drop guarantees, failure ABI,
or executable exit cleanup schedules. Do not interpret `copyable_value` as permission
to use `memcpy`, or lack of a source destructor as proof that a value needs no cleanup.
Those target facts belong in C representation/operation bindings derived from the
semantic type and runtime provider contract. Add shared semantic facts only when
an actual language-level decision is missing; do not put C implementation properties
into the semantic capability enum merely for convenience.

Proposed architecture (not implemented): retain existing semantic registry and
protocols; add C representations plus typed operation recipes and a specialization
request collector, following the existing separation of `types` and `cpp_types`.
Named workers execute recipes and own sequencing/cleanup; metadata supplies contracts
and bindings. Data-driven does not mean embedding arbitrary C snippets in semantic
metadata or creating a universal string-template interpreter.

### Focused verification

Ran five existing PHP suites through the shared `CommandRunner` / `run_tasks` pool:
`type_capabilities`, `capabilities`, `runtime_types`, `runtime_protocols`, `type_catalog`.
All five passed. PHP-FPM was unavailable because its local socket could not bind;
the runner's supported CLI fallback executed the suites. No native compiler rebuild
or generated-program execution was performed in this audit.

Especially relevant passing checks:

- `type_capabilities.php` registers an unfamiliar two-argument template and proves
  only its designated argument controls copyability, including nested applications.
- `capabilities.php` changes a nested source field to `unique<int>`, proves cached
  specialization revalidation and invalidation of unchanged consumers, then recovers
  without duplicating the earlier specialization identity.
- `runtime_protocols.php` registers the metadata-only `tray<T>` provider, exercises
  construction/indexing/iteration independently, and checks prepared/generated output
  without parser/preparation/emitter special cases for that provider.

The evidence supports a C backend built on existing data-driven infrastructure.
It does not yet prove C bindings or automatic cleanup, and it supplies no reliable
calendar estimate until that first target-specific slice exists.

## PCH and Ninja assessment — 2026-10-18

Recommendation: generated C starts without PCH; C++ adapters may use a runtime-only
PCH when actually compiled; retain Ninja as a native execution option rather than
making it part of semantic preparation or a prerequisite of the C backend. No build
implementation changes or new performance measurements were made for this assessment.

### PCH is an optional per-language compilation optimization

The C handle experiment compiled application units without any PCH: median 55.70 ms
at O0 and 112.29 ms at O2. Its C headers contain ABI declarations rather than runtime
C++ template definitions. This establishes a working no-PCH starting point, not that
no future generated-C workload could benefit from a C PCH.

Keep three cases separate:

1. Existing C++ application backend: preserve its working PCH option.
2. C application units: no PCH by default, narrow headers, no dependency on the C++
   PCH. A C++ PCH is not a C-language input. A later C PCH would be independently
   built/configured and justified by measurements of actual repeated header cost.
3. C++ runtime/type/specialization adapters: optional stable runtime PCH for build
   misses. Already-current adapter objects need no Clang invocation and hence no
   compiler PCH loading. Avoid placing volatile application layouts in a global PCH.

PCH creation is neither always beneficial nor always expensive enough to reject.
For N actual C++ compile misses with compatible flags, compare PCH creation plus
N uses against N ordinary compilations; if the PCH already exists, compare use costs.
The first experiment observed original generated-C++ object compilation at about
4.99 s without PCH versus 1.89 s with PCH at O0. That is evidence to keep the option
for C++ work, not a measured result for the new handle adapter without PCH.

Clang loads serialized AST structures lazily. The earlier discussion's object-copy
analogy does not establish PCH-loading cost, and our traces did not isolate loading
from instantiation and other frontend work.
Source: https://clang.llvm.org/docs/PCHInternals.html

The inspected `06_native/cpp_build.php` currently builds/checks a runtime PCH before
walking generated objects and has a separate syntax-only validation invocation.
A C-aware action plan must not make C-only work require that PCH. When every C++
object is current, avoid introducing a gratuitous Clang PCH check merely to compile
an independent C unit; preserve correctness through the chosen native freshness owner.

### Ninja is replaceable, but its responsibilities remain

Semantic invalidation and native build invalidation are distinct:

- Compiler: which prepared bodies/types/adapters need new generated bytes.
- Native build consumer: which object/PCH/link actions are stale for those bytes,
  native headers, flags, runtime/provider changes, toolchain changes and missing outputs.
- Executor: schedule ready actions with bounded concurrency and propagate failures.

Ninja supplies native graph scheduling, command-change tracking and compiler depfile
consumption. It does not analyze source-language capabilities, choose specialization
recipes, or make one Clang invocation execute faster. It is not by itself a historical
content-addressed object cache. Its default timestamp/dependency model also does not
replace explicit toolchain identity inputs or a stronger content-validation contract.
Source: https://ninja-build.org/manual.html

Current checked-out paths are different and must not be conflated:

- `bin/project_services.php:17739` emits Ninja rules for the public project flow.
- `compiler/my-try/06_native/cpp_build.php` is a separate native action/cache consumer.
  It already hashes dependencies/commands, manages ready/pending state and links;
  its inspected action loop is sequential and each compile lookup invokes a fresh
  Clang dependency scan. That overhead was not measured here and could matter beside
  a 56 ms C compilation. It cannot be assumed to be eliminated by semantic caching.
- The October 16 accounting checkpoint documents a separate native Ninja integration
  at its recorded revision. That is historical tested evidence, not proof that its
  worker exists unchanged in this checkout. It recorded zero compile/link invocations
  on unchanged builds, and eight concurrent changed-object builds. Its roughly
  one-second unchanged total included compiler/publication/validation work and must
  not be described as Ninja overhead.

For the C backend's initial integration, reuse the selected existing build owner and
add explicit language per action: C17 application objects, C++ adapter/provider objects,
optional PCH edges only for eligible C++ units, then one mixed link. Preserve unchanged
file mtimes and stable identities. Record exact live object membership so deleted
specializations cannot remain accidentally linked.

For a Ninja-based production path, let the compiler publish the native graph and
content-stable files and let Ninja own native freshness/execution. Do not put the
existing per-action rescan/cache-decision loop in front of every Ninja edge by default.
For a direct native-consumer path, Ninja is unnecessary if that consumer deliberately
owns dependency correctness, flags/toolchain invalidation, bounded scheduling, recovery
and link freshness. It must preserve those obligations across compiler restarts too.
This is an implementation choice, not a reason to invent a second semantic graph.

### Decision and next measurable checks

Keep PCH configurable for retained C++ support; omit it for initial C application
units. Keep Ninja available and favor reusing it for production scheduling instead
of simultaneously building a scheduler during C-backend work. A bounded direct
consumer remains suitable for the first correctness proof.

After actual C artifacts exist, measure no-edit, one-body, several-body, type-layout,
runtime-header, flags and deletion/recovery cases. Separate graph/freshness checks,
dependency scans, process startup, PCH creation, application/adapter compilation and
linking. Do not include lowering timing unless requested. Use those measurements to
decide whether a persistent direct executor or a C PCH is worth adding.


## Selective C++ follow-up — 2026-10-18

The [selective comparison](results/accounting_c_vs_cpp_2026_10_18/SELECTIVE.md)
refines the earlier C-first recommendation: prioritize concrete specialization and
operation boundaries while retaining ordinary C++ where useful. In this sample,
concrete C++ RAII compiled in 65/110 ms (O0/O2), versus 56/104 ms for C and
1843/2196 ms for original generated C++ with PCH. Eight sequencing lambdas added
only a few milliseconds. Re-exposing runtime/generated headers with PCH restored
1689/1702 ms compile cost without changing the concrete algorithm. A separate O0
trace placed 1645 of 1698 frontend milliseconds under pending instantiations.

Our compiler can own source generic/metaprogram resolution and publish concrete
operations without eliminating C++ as its native output language. The experiment
still uses precompiled C++ template implementations behind an adapter; complete
ownership of their lowering remains implementation work, not a measured outcome.
Container/callable and larger-program evidence remains necessary before calling
this the only expensive feature across the backend. No production code changed.


## Concrete vector and class specializations — 2026-10-18

The [vector specialization experiment](results/vector_specialization_2026_10_18/README.md)
extends the selective-lowering proof to construction, vector copy independence,
shared pointee mutation, replacement, growth, moves, early returns and destruction.
A fourth variant emits both vector and class providers without per-type C++ templates,
using a single compiled type-erased standard shared-ownership service. It retains
ordinary C++ automatic cleanup. This is a fixture emitter, not integration with the
compiler's semantic registry or artifact publisher, and its handles are not established
as ABI-compatible with existing scpp runtime handles.

At O0, fully concrete native compile+link costs were 220 ms for a body edit,
209 ms for private payload-layout changes and 353 ms for a new class/vector
specialization; template-exposed controls cost 2034, 3810 and 3898 ms. O2 concrete
costs were 253, 229 and 455 ms. Native depfile/content checks proved the intended
rebuild sets; private shared-payload layout edits retained vector/application objects.
All 120 edit-state executions passed, with exact payload destruction counts. LeakSanitizer was unavailable due to a
reported ptrace limitation; separate ASan/UBSan validation passed with leak detection disabled. No lowering
cost, runtime-throughput claim or production incremental integration is established.

The evidence supports owning specialization emission while retaining concrete C++
lifetime machinery. A shared-payload private-layout edit can avoid invalidating the
vector only while public handle layout and copy/move/drop contracts remain stable.
The next production slice must consume existing prepared identities/capabilities,
with clear backend ownership of concrete representations and specialization artifacts.


## Whole small accounting app conversion — 2026-10-18

The [whole-app experiment](results/accounting_template_free_2026_10_18/README.md)
converts the existing 15 generated C++ application units, emitting 44 concrete shared
handle types and 24 vector/hash representations. All 998 accounting checks pass in
every measured executable. Both the app and three support units preprocess without
C++ template declarations. Original/concrete container/string probes, full concrete
ASan/UBSan and balanced shared-owner counts pass. The result remains C++23 with
classes, virtual dispatch, closures and automatic cleanup; no production compiler
or runtime code is changed.

Three-run median application compilation times at O0/O2 were 41.844/65.031 seconds
for ordinary runtime PCH, 21.655/45.201 seconds with -fpch-instantiate-templates, and
2.677/4.578 seconds for concrete code without PCH. Including all newly compiled
concrete support and linking, concrete totals were 3.518/7.098 seconds. Existing
original runtime-library construction and PCH creation are excluded from those
totals; PCH setup is recorded separately in the [results](results/accounting_template_free_2026_10_18/RESULTS.md).

The eager-PCH result corrects any interpretation that the previous ordinary-PCH
baseline exhausted C++ optimization options. Even against this improved baseline,
full concrete native work was about 6.3–6.4x faster in this serial experiment. Other
PCH/codegen/explicit-instantiation configurations remain unmeasured. Lowering time,
parallel build wall time and runtime throughput are not measured.

The conversion changes concrete storage and support implementation, so the timing
ratio is not an isolated measurement of template syntax alone. It preserves this
deterministic app's checks/output, not the entire production runtime's diagnostic
metadata, reflection, weak-reference, allocation-failure or ABI behavior. The bounded
artifact converter is evidence for a production specialization backend, not that backend.
