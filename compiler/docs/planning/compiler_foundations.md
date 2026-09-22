# Compiler foundations tracker
Doc Status: supporting

## Project objective

Target a useful direct LLVM compiler at `-O0` and `-O1`, with optimization supplied
by LLVM and first-class STAN (static analysis). Compiler independence is not
required: Clang-assisted preparation and the Simple C++ runtime are accepted
dependencies, and S2S can serve broader compilation needs. This is the agreed
objective, not a claim of implemented optimization modes or automatic S2S routing.
See the [shared objective](../README.md#project-objective).

The [ownership decision](../details/clang_lifecycle_composition.md#ownership-decision)
assigns runtime implementation to Clang preparation and complete source lifecycle
to our compiler. The [source/native contract](../details/source_native_contract.md)
defines their integration boundary; compiler integration of native provider
specializations works for declared runtime scalars, ordinary managed runtime types
and nested prepared native families:
construction, copy/assignment/cleanup, methods, const integer borrowing and owned
results share existing contracts. Automatically composed source records now participate
through explicit project modules and compiler-owned lifecycle exports, including
nested source/native combinations. Custom lifecycle exports now require accepted
body and ownership evidence before emission; see the
[implementation boundary](../details/custom_source_exports.md).

## Purpose of the foundations

Build a small set of difficult, reusable capabilities before expanding language
coverage. The aim is to establish a compiler skeleton that can scale: clear
ownership, compact shared contracts, reusable processing, explicit worker/join
boundaries and incremental replacement. Choose small executable proofs that
resolve difficult design questions before those questions spread across more
features. Consumed template types are a deliberate architectural proof of family
materialization, shared instance identities and recursive type composition.
Completion requires a reusable path within the declared scope as well as working
output; concrete sample types must not select special compiler behavior.

Work in the [PHP prototype](../../README.md); consuming a
Simple C++ provider does not authorize porting the compiler back to PHP++.
The [pipeline](../compiler_pipeline.md), [organization](../code_organization.md)
and [type model](../type_model.md) continue to own the design rules.

## Current status and next focus

**Required capability proofs for steps 1–8 are complete within their documented
subsets.** This includes source structures and lifecycle composition, concrete-element
source lists, native family consumption, owned source results and nested native
ownership. Step 7 covers results; owned parameters remain explicitly deferred.
The [closing boundary review](../details/nested_family_consolidation.md)
records the reviewed owners, one local preparation cleanup and remaining exclusions.

Source/provider type contracts share `type_model`; backend preparation is separate
from lowering; package import uses private adapter handlers. Ownership analysis
separates flow facts, validation observations and call-effect application while
preserving fixed workers, joins and retained summaries. See the
[organization guide](../code_organization.md#navigation-and-ownership)
and [ownership consolidation](../details/owning_storage_fields.md#flow-and-call-application).

**Source/native consolidation:** the [gates 1–4 review](../details/source_family_consolidation.md)
is complete. Packages retain normalized source imports; backend preparation shares
one current layout dependency capture across their roots. Scheduling and retained
ownership need no further blocking refactor within the supported subset. The follow-up [custom lifecycle export slice](../details/custom_source_exports.md)
is implemented: native preparation consumes declarations; selected post-analysis
checks verify current body/ownership evidence before export emission. A custom body
increment reuses native artifacts when its exported contract stays compatible.

**Managed element storage is implemented:** [implementation and proof](../details/managed_element_storage_plan.md).
Typed push/pop now use the same selected copy/destruction instructions as local
objects, with checked internal destinations and native live-count commits. Runtime
objects and source records containing managed fields are proved, including assignment,
transfer, reverse cleanup and types without default-construction permission.
**Concrete managed growing lists are proved:** source growth, copy/assignment,
self-assignment, owned reads, and balanced element/buffer cleanup use the same pipeline.
Body checking now retains implicit element dependencies even in a pop-only body.
[Evidence and limits](../details/source_list_plan.md#managed-growing-list-proof).
**Next discussion:** indexed allocation ownership and its generic-baseline boundary.
Generic acceptance remains separate: indexed compiler-tracked allocation ownership is
still unsupported and must not be bypassed by favorable specializations. The
[agreed semantic direction](../details/lifecycle_contracts.md#indexed-ownership-agreed-semantic-direction)
permits different valid ownership states across live elements; neither legacy S2S
limitations nor our analysis representation may invent additional language restrictions.
That direction is recorded; the indexed-owner representation and implementation remain open.

**Current validation:** all 110 compiler fixtures passed in one ten-worker run
(168.3 seconds). The managed growing-list proof covers runtime/source-record elements,
source growth/copy/assignment/self-assignment, owned reads, early cleanup, twenty
matched backing allocations/releases, no construction of spare capacity, and rejected
element-borrow invalidation. Reordered ownership/lowering workers, one body increment,
retained snapshot/artifact reuse, and O0/O1/ThinLTO pass. The earlier managed-storage,
scalar/plain-record, ordinary lifecycle and owned-result proofs remain in the suite.
PHP syntax, changed-file brace/doc-comment, whitespace and documentation file-link
checks pass.
The preceding standalone preparation checkpoint passed 170 ordinary and 54 family
checks; preparation-tool code is unchanged in this slice, so those suites were not
repeated. These are correctness/reuse checks, not performance-equivalence claims.

**Completed slice: owned source-function results (step 7).** The
[implementation record](../details/owned_source_results_plan.md) describes
caller storage, construction selection, cleanup ordering and result resource summaries.
Fresh forwarding, named locals, borrowed copies, managed/nested source records, real
strings, prepared native families and custom-copy source lists have execution proofs.
Owned parameters and general movement remain deferred; nested native family arguments
are covered by the subsequent step-8 proof below.

The [family demand and reuse model](../details/source_native_contract.md#accepted-family-demand-and-reuse-model)
is accepted: family definitions and prepared specializations are separate;
runtime/language-only specializations are shared across projects, while those
depending on source types are project-scoped. The coordinator selects preparation
work and joins accept fixed results before dependent work continues.
The [generic parameter contract](../details/generic_type_contract.md)
is now agreed as a prototype extension: bare `<T>` means a generally usable,
copy-constructible and copy-assignable type with a valid automatic cleanup contract,
not every possible type. Unique owners and other types lacking the baseline are
excluded by capability checks, not a name blacklist. Template bodies use only
authorized operations; concrete substitution grants no extra permissions.
Default construction and additional capability contracts remain deferred.
**Bounded generic enforcement is implemented.** The
[implementation record](../details/generic_contract_implementation_plan.md)
describes definition-level permission checks, lifecycle-based argument eligibility,
private workers/joins and retained results. Native copy/assignment/forwarding and
one ordinary body increment are proved; unused forbidden definitions fail before
specialization. Additional capability syntax, `new T` and reflection remain deferred.

The approved migration preserves growing-list storage/ownership proofs with concrete
element records; restoring its generic form remains deferred because dynamically
indexed compiler-tracked allocation ownership is unsupported. Managed storage now
supports runtime objects and source records without those indexed owner obligations. Array proofs use concrete elements and
templated capacities. Generic fixed-array initialization remains
[parked review debt](../details/generic_type_contract.md#deferred-review-debt),
alongside non-baseline types such as mutexes. No unchecked-template exemption remains.

The typed-record and publication boundary is implemented for runtime scalar
arguments, ordinary managed types and nested prepared native specializations.
Semantic signatures are independent of prepared ABI and shared by ordinary
imports and family declarations. Compiler-driven type preparation and method
consumption now work through shared ABI contracts, including call-scoped const integer
borrowing. Specialization identity excludes requested
operation coverage. One stable package per specialization is privately replaced to
extend coverage, preserving accepted readers and the old package on validation failure.
See the [request and replacement model](../details/source_native_contract.md#accepted-specialization-request-and-package-replacement-model).
The [family requirement and signature model](../details/source_native_contract.md#accepted-family-requirements-and-operation-signatures)
now separates type-formation requirements from operation requirements, with only
the default generic baseline initially supported. Copy-based append uses borrowed
receiver/element inputs and a void result; concrete callables reuse shared contracts.
The [append-alias investigation](../details/provider_append_aliasing.md)
now supports native copy-based self-append under forced growth at O0/O1 and with
ASan/UBSan. Safe input overlap and old-element borrow invalidation need distinct
imported facts; compiler exposure of interior references and their invalidation remains
pending. Ordinary external-element append is now integrated. The [concrete implementation plan for parts 1–3](../details/provider_family_implementation_plan.md)
records the completed semantic-signature migration, typed family contracts, preparation
acceptance before publication, stable coverage replacement and validation gates.
Production preparation proves the configured vector with integer elements and a two-type
holder; source-dependent arguments are now covered by the gate-4 checkpoint below. Nested prepared native arguments
are covered below. Compiler-driven family
demand now has a [coordinated integration plan](../details/provider_family_compiler_integration.md):
explicit source exposure, semantic versus physical readiness, common const scalar
borrowing and a runtime input set for multiple packages. **Gate 1 is implemented:**
explicit prepared packages compose through selected reads and a validated join;
ordinary, full/ThinLTO and one-body-increment proofs cover two packages.
**Gate 2 is implemented:** typed family/member exposure, owner-scoped registration,
ordered argument binding and symbolic permission checking, including unused definitions.
**Gate 3 type/method-demand checkpoints are implemented:** semantic specialization
identities, coalesced native type/operation demands, selected preparation/joins and
measured layout/lifecycle/callable acceptance. Construction, cleanup and methods with
existing value/object-borrow ABI contracts execute from ordinary/generic source beside
ordinary packages. Proofs cover two-parameter families, non-leading receivers, one
increment adding a specialization or method coverage, exact type/callable reuse and
full/ThinLTO lifecycle artifact links. Early/final leases protect preparation/linking.
**Gates 4–5 are implemented:** imported const integer-address parameters use existing
places or typed storage for already evaluated expressions/conversions. The real native
vector append proof covers ordinary/generic calls, one increment, fixed worker inputs
and compatible O1/full/ThinLTO links. No new source reference declarations are enabled.
No fake layout makes a declaration ready. Local template permissions remain fixed by
declared contracts, including when arguments are forwarded.
**Consolidation completed:** required coverage is now explicit at the preparation
join, including previously available methods still needed by current callers. The
[review and regression proof](../details/provider_family_compiler_integration.md#bounded-integration-consolidation)
retain the existing owners and fixed worker boundaries.

**Owned-result consolidation:** reviewed result construction, caller storage,
cleanup and ownership-summary acceptance; corrected stale support claims, call maps
and diagnostics. No wider model refactor was indicated by this review.

**Runtime copy assignment is implemented:** ordinary definitions and provider
families can expose a native assignment operation through the same preparation and
lifecycle adapter. Existing checking, field composition, ownership, lowering and
emission consume it unchanged. Proofs cover real strings, a second configured
runtime type, generic copy/assignment, source fields, native vectors, replacement,
self-assignment and one body increment. Missing bindings and invalid native/metadata
contracts reject. See [the assignment contract](../details/lifecycle_contracts.md#copy-assignment).

**Nested native ownership (step 8) is implemented:** prepared specializations become
ordinary concrete argument types through exact metadata imports. The
[nested proof](../../tests/integration/provider_family_nested.php) covers real
`vector<vector<int>>`, a second two-argument native family, independent copies,
assignment, owned early/normal returns, observed allocation balance, fixed joins and
one body increment with exact package reuse. O1 and ThinLTO use the same artifacts.
AddressSanitizer checks invalid accesses; leak detection is disabled under the traced
harness, with explicit counters checking the test family's owned allocations.
[Boundary and reuse rules](../details/provider_family_compiler_integration.md#nested-native-argument-boundary).

**Closing consolidation:** reviewed concrete identity, native dependency freshness,
import/lifecycle ownership, selected work, joins and retained reuse. Shared inner
recipes and header scans now execute once per fixed preparation batch; no mutable
cache enters retained state. No additional correctness blocker was identified in
these reviewed paths. This is not a whole-compiler audit or a scalability benchmark.

**Ordinary runtime family arguments are implemented:** accepted runtime packages
and prepared family results share native descriptions and canonical argument imports.
The [managed argument proof](../../tests/integration/provider_family_runtime_types.php)
uses `vector<string>` and a second configured heap-owning type through copy,
assignment, owned reads/returns, cleanup, one body increment and O1/ThinLTO.
Source-dependent native arguments now retain compiler-owned operations through
explicit project modules and stable compiler export entry points.

**Source-family migration — gates 1–4 implemented:** [source-defined native family arguments](../details/source_family_integration_plan.md).
Code inspection identified shared early layout preparation, exact source exports,
project modules with compiler imports, and final link closure as coordinated work.
The approved first gate now uses one backend-owned layout coordinator for ready
record batches and final preparation. Fixed dependency views replace mutable
type-store worker inputs; accepted layouts validate nested dependencies and lineage.
The selected source export boundary now supplies exact project/declaration/argument
identities, six explicit lifecycle states and prepared import ABI associations through
private workers and a join. Body edits reuse contracts; compatible fresh builds rebind
stable symbols to current complete implementations. No syntax/type stores enter native
export tasks. Custom bodies now require separately accepted post-analysis evidence;
source movement remains unsupported there.
Native project preparation now generates real payload adapters and copy-in/out bridges
only when metadata authorizes each profile/crossing. All LLVM variants report and
validate authorized compiler imports. Ordinary packages retain no-undefined linking;
project modules are rejected by ordinary import and require explicit source bindings.
Compiler demand routing, project-result import, source export emission and final link
closure are now implemented. The [execution proof](../../tests/integration/source_family_execution.php)
covers managed/nested/cleanup-free records, nested native families, balanced allocations,
one body increment with native reuse, and O0/O1/ThinLTO. Source arguments/results keep
their canonical record definitions; existing lifecycle emission owns complete operations.
The [custom-body proof](../../tests/integration/source_family_custom.php) adds nested custom
construction/copy/assignment/destruction and exact current verification evidence.
General moves, classes/smart pointers,
interior references and hashing/equality remain explicit later work. Review this
completed boundary before choosing another foundation extension.

### Historical checkpoints and contract decisions

The [2026-09-18 consolidation audit](../details/foundations_consolidation_audit.md)
describes an earlier baseline. Its expression-depth, ordinary-method checking and
preparation-selection findings were resolved through iterative index checking,
ordinary-method validation independent of use, and dependency-driven concrete
preparation owned by `resolve_types`. Its 84/84 validation result and then-future
nested/managed-field work are historical; subsequent implementation is summarized
above and in the progress notes below.

The [2026-09-19 lifecycle investigation](../details/lifecycle_contracts.md)
established operation roles, field ordering and runtime ownership effects before
the later lifecycle implementation. It also recorded discrepancies in legacy S2S
behavior. The agreed `new Struct()` semantics are by value, as confirmed by the
upstream clarification relayed by the user; the exact syntax remains an upstream
documentation gap. Unsupported construction must not acquire class-style shared
ownership.

The accepted [conditional interior-borrowing direction](../details/lifecycle_contracts.md#agreed-direction-shared-analysis-and-conditional-interior-borrowing)
allows a safe subset when validity is established for the whole borrow and a
future integrated S2S generator can preserve the same guarantees. The current
prototype proves bounded call-scoped borrowing and allocation-invalidation checks;
broader interior-reference rules and S2S integration remain future work.

### Ongoing consolidation criteria

Review real paths through parsing/binding, concrete instance preparation, type
and callable contracts, checked locations, lifetimes, lowering and provider import:

- Are concepts named clearly and owned by the appropriate process? Do source and
  provider inputs join at truthful common contracts without duplicating rules?
- Do data structures remain compact, with clear identities and retained ownership,
  rather than accumulating feature flags, special cases or repeated facts?
- Is the processing flow easy to follow? Can the next capabilities extend existing
  owners without parallel paths, hidden dependencies or fixture-specific behavior?
- Are work selection, fixed inputs, private outputs, joins and replacement
  boundaries coherent for future MT scheduling and incremental compilation?
- Do implementation, diagnostics, documentation and executable proofs describe
  the same supported contracts and limitations?

Report concrete findings and their evidence, separate required consolidation from
optional improvements, and discuss the resulting work before expanding features.
There is no requirement to find problems or invent abstractions. Follow the
repository's existing approval rule for coordinated refactors across owners.

**Semantic rule:** satisfy Simple C++ contracts. Investigate only unclear cases;
do not routinely re-prove settled contracts through the S2S compiler. Record
explicitly agreed prototype extensions, including bounded template authoring,
without treating them as permission to diverge from established semantics.

## Accepted remaining implementation sequence

ABI calls, structured values/layout, source/consumed templates and ownership/cleanup
are interdependent. The capability rows below remain a coverage summary; this
single sequence records the agreed order across those categories. Steps 1–5 are
implemented within their stated subsets; step 6 now also has its bounded scalar-family
proof and consolidation. The owned-result portion of step 7 is implemented; owned
parameters remain deferred. Step 8 has its bounded nested-native proof and
closing boundary review. Settle each
new slice's contracts before coding and consolidate its touched owners afterward.

| Order | Work | Required outcome |
|---|---|---|
| 1 | Agree field ownership and dynamic-storage lifecycle contracts. | Define how containing structures derive construction, copy, transfer and cleanup requirements from fields, and which owner implements each operation. A pointer alone does not imply resource ownership. |
| 2 | Compose nested and managed fields. | Prove a source structure containing another structure and an existing managed runtime value. Reuse existing runtime lifecycle contracts to establish recursive construction/cleanup before new allocation behavior. |
| 3 | Dynamic element storage and replacement. | Define allocation, target alignment, initialized elements, bounds, release and explicit ownership transfer. Initially use elements requiring no cleanup. |
| 4 | Growing source-defined list. | Compose source-written append/read/length behavior through these contracts. Preserve the agreed initially noncopyable list and prove two eligible element types. |
| 5 | Resolve general list-copying debt. | Prove independent copied storage, assignment releasing previous contents, defined self-assignment and exactly-once cleanup. Follow element-copy and ownership contracts. |
| 6 | Consume provider template families. | Materialize demanded specializations through runtime preparation and its adapter, joining shared concrete type/callable contracts. Prove `vector<int>` and a two-type-parameter family. |
| 7 | Owned source-function boundaries. | Start with returning an owned value: result storage, ownership handed to the caller and cleanup of other live values. Add owned parameters where the agreed proof needs them. General native C++ aggregate ABI coverage is not implied. |
| 8 | Nested ownership composition and final consolidation. | Prove `vector<vector<int>>`, real element use and an owned nested-vector early return. Reconcile coverage and resolve implementation debt within the agreed scope. |

**Early design dependency for step 6 — model accepted:** specialization demands
reach runtime preparation through coordinator-selected work and validated joins,
with the shared/project reuse scopes described above. Concrete metadata and
artifact retention/publication are implemented for the runtime-scalar subset through
the [preparation boundary](../details/provider_family_implementation_plan.md#implementation-outcome).
Follow Simple C++ contracts and investigate only unclear cases. Preparation remains
associated with the selected runtime version/target/configuration; do not regenerate
unchanged bindings per application or source file.

**Required integration validation — focused execution-performance proof:** compare
the first source/imported-type combination against equivalent direct C++ with the
same runtime and matched build settings. Check execution time, generated code,
allocations and ownership operations; extend to ownership wrappers as they enter
scope. The [isolated source-record/vector proof](../details/source_specialization_proof.md)
now passes correctness, allocation parity and package reuse/invalidation checks.
The user accepted its measured 20–31% small-vector lifecycle overhead at `-O1`,
including LTO, for the fast development compiler. Reducing this gap is not an
integration prerequisite. Ownership-wrapper performance remains unproved.
[Measurement scope](../details/performance_watchlist.md#focused-execution-performance-proof-for-imported-combinations)
and [agreed type ownership direction](../details/clang_lifecycle_composition.md#type-ownership-and-imported-combinations).

**Lifecycle preparation investigation:** the
[agreed ownership boundary](../details/clang_lifecycle_composition.md#ownership-decision)
assigns runtime implementation to Clang preparation and complete source-type
lifecycle implementation to our compiler. Mixed native templates must call those
compiler-owned operations without duplicating field lifecycle. The earlier
[isolated whole-structure proof](../details/lifecycle_preparation_proof.md)
passes native automatic composition and custom LLVM constructor/destructor-body
linkage at O0/O1 and full/ThinLTO, including body replacement with unchanged shell
bitcode. This is not source compiler integration. The source/native design now
defines project-specific modules with declared application imports and final-link
acceptance while preserving the self-contained runtime-package contract. That isolated proof used native witnesses; source custom lifecycle bodies are now
implemented separately through the checked-body path described below.

The subsequent [source-operation adapter proof](../details/source_operation_adapter_proof.md)
tests the selected ownership: a real runtime vector calls compiler-owned complete
source lifecycle operations through a native adapter, including explicit payload
copy-in/copy-out for LLVM-owned storage. All six operation roles and one body edit
pass O0/O1/full/ThinLTO. This is a candidate representation, not compiler support;
the [source/native contract](../details/source_native_contract.md) now
defines exact source identity, accepted layout, capabilities, complete-operation
imports, payload crossings, joins and reuse. **Implemented next slice:**
[bounded field composition](../details/lifecycle_contracts.md#first-compiler-implementation-bounded-composition)
now derives default construction, copy construction and destruction from eligible
source fields. The [integration proof](../../tests/integration/lifecycle_composition.php)
covers nested records, over-aligned prepared container fields, fixed-array element
loops, copied subrecords, temporaries, early returns, private workers/joins and one
body increment reusing generated operations. Emitted artifacts also pass O1/full/
ThinLTO checks. Native specialization requests and project-module imports remain
separate. The six-operation isolated proof does not authorize moves or managed
assignment in the compiler. **Complete prerequisite for step 3:** [custom source lifecycle bodies](../details/lifecycle_contracts.md#custom-source-lifecycle-bodies)
now supplement automatic field composition through the normal checked-body pipeline.
The proof covers early returns, managed receivers, nested/template records, private
joins and one custom-body increment. **Implemented (step 3, bounded element subset):** the first
[allocation-ownership slice](../details/allocation_ownership.md) now proves
local empty/acquire/transfer/release states through native bridges and the existing
lifetime workers/joins. The approved [typed-storage migration](../details/typed_storage_plan.md)
now proves metadata-defined families, scalar/plain-record elements, checked prefix
operations, target layout, transfer, borrowing, native ABI joins, one body increment
and O1/ThinLTO. The approved [owning-field migration](../details/owning_storage_fields.md)
now composes static subobject obligations, source receiver summaries and complete
construction/destruction through dependency-ready tasks and joins. Its proof covers
replacement, two independent fields, nested source records/templates, temporary
cleanup, native release exactly once, O1/ThinLTO and summary-dependent increments.
**Implemented (step 4):** the [growing source list](../details/source_list_plan.md)
uses source-written allocation/copy/replacement loops for two scalar element types.
The approved [binary-operation migration](../details/binary_operations.md)
adds integer `<` and a configured boolean result through the same path as addition.
The proof covers native execution, O1/ThinLTO, thirteen matched allocations/releases,
private ownership work/joins and one method-body increment with stable runtime artifacts.
**Implemented (step 5, construction):** generic custom source copy construction
initializes fields through their defaults, then runs the checked copy body against
a const source. Two list specializations, nested composition, independent storage,
const owning-record parameters, fixed ownership tasks/joins and one copier-body
increment are proved. [Contract and proof](../details/lifecycle_contracts.md#custom-copy-construction).
**Implemented (step 5, assignment):** independent assignment capabilities, custom
source assignment of live objects and automatic field/array composition. The growing
list allocates/copies before release and transfer, proving self-assignment without
compiler special cases. Stable object borrows and allocation-backed element borrows
have separate validity rules; source access-order constraints protect aliased calls
and participate in incremental invalidation. [Contracts and proof](../details/lifecycle_contracts.md#copy-assignment).
**Consolidation after step 5:** location access is selected once by its consumer;
initialization and assignment have separate checking selectors. Shared lifecycle
composition rules now supply field roles and ordering to source composition,
ownership preparation and LLVM emission. Fixed task/join boundaries and retained
checked-value formats are preserved. [Consumption model](../details/lifecycle_contracts.md#consolidated-consumption-model).
Ownership analysis now separates propagated flow facts from validation observations;
call effects check a fixed pre-call state before applying each aliased writer once.
Path/endpoint encoding is shared by inference, composition and acceptance. Existing
worker/join and retained summary contracts remain unchanged.
[Analysis consolidation and proofs](../details/owning_storage_fields.md#flow-and-call-application).
**Step 6 bounded integration is implemented:** compiler-driven provider types, methods
and const integer-address calls consume the tested preparation boundary. Consolidation
is complete; see the current-status section above. Concrete managed elements in the
source-written growing list are now proved; generic migration remains deferred.
Native families already consume eligible managed runtime and source-record elements.

The ownership discussion selected source-written list lifecycle methods plus an
explicitly owning storage descriptor (address, slot count, constructed-prefix count).
Source methods perform element cleanup and release; allocation/release use prepared
native bridges. No automatic storage cleanup may duplicate those source methods.
Static allocation ownership and active-call-borrow effects are implemented for
local opaque owners and statically named resource fields, including typed prefix storage. See the [agreed storage direction](../details/lifecycle_contracts.md#dynamic-storage-direction-and-implementation-status).
Allocation ownership and element lifetime are distinct responsibilities; list/vector
names must never select compiler rules.
Source and provider inputs join shared contracts, while provider ABI/layout facts
retain their proper boundary. Consolidate touched owners after each slice rather
than postponing structural cleanup to step 8.

Completion means the agreed architectural proofs, not every deferred feature or
unchecked future item in this document. Exception unwinding, smart-pointer
families, general constant evaluation, actual threading and recovery machinery
remain separate unless explicitly brought into scope. Existing native-port gates
also remain separate from completing the PHP prototype foundations.

**Validation cadence:** use focused checks during development; broaden validation
when shared contracts change and run the full suite at completed integration
checkpoints, not after every small edit. Preserve each slice's relevant native,
diagnostic and fixed-worker/join evidence and the required full rebuild plus one
incremental attempt. Documentation-only changes need document/link checks, not a
compiler test run. The test runner defaults to ten concurrent fixture jobs.

### Deferred lifecycle debt

- [ ] **Failure cleanup and recoverable exceptions — deferred for now.** Agreed
  current contract: a failure crossing a runtime bridge is fatal. Guaranteed
  cleanup during failure and recoverable exceptions remain future work, outside
  the current foundations completion requirements. This includes partial-construction
  cleanup and caller locals/temporaries on exceptional exits; native cleanup inside
  a bridge does not prove cleanup of its LLVM caller.
  [Scope to revisit](../details/runtime_cleanup.md#deferred-debt-failure-cleanup-and-recoverable-exceptions).

## Pre-template consolidation

Historical checkpoint before template and owned-source-result implementation.
The current-status section above and capability table below include later extensions.
The bounded audit consolidations at that checkpoint were:

- Backend policy fingerprints cover the preparation owner's PHP file membership and
  contents, including layouts and binding contracts; execution services remain separate.
  [Invalidation proof](../../tests/05_generate_code/prepare_backend/backend_preparation.php).
- Unsupported owned source object parameters/results report their type annotations, even
  in unused functions. Explicit plain-record references are now supported through the
  [common source/provider passing contract](../details/source_record_borrowing.md).
- The [mixed record/string proof](../../tests/integration/runtime_record_strings.php)
  covers owned results from borrowed records, independent copies, control flow and one
  body replacement with retained-snapshot purity and backend reuse.
- Audited missing method documentation in `src` is filled; the doc-comment
  requirement and five-line brace rule both remain in force.

This checkpoint consolidated the then-implemented surface without adding aggregate
value ABI or managed/nested fields. The next direction at that time was compile-time
metaprogramming, starting with source templates and then provider-family consumption.
[Parsing and semantic-owner plan](../details/metaprogramming_parsing.md).

## Established baseline

Git checkpoint: `6b164bf` — **Baseline compiler before variables and scopes**
(2026-09-10, initial commit on `main`). This preserves the working compiler,
tests and design documents before implementing the foundations below.

- [x] Three-file source-to-native compilation: integer literals, parameterless
  cross-file calls, declared return types, void calls and returns, including the
  manifest entry. [Executable proof](../../tests/integration/native_executable.php).
- [x] Resident body-edit reuse, common full-rebuild fallback for unsupported
  changes, and failure/repair. Inspection and native requests use the same gate.
  [Policy proof](../../tests/compile/incremental_policy.php).

Identity conversions, scalar copying/no-cleanup contracts, native entry
adaptation and compound-type storage already exist. They do not complete the
broader proofs below. No actual multithreading is implemented.

## Capability progress

Each row stays **open** until its source-to-executable proof passes. On completion,
change the status and link the focused test; record any remaining restriction.
The summaries below include later extensions to the original proofs. Completion
is scoped to the stated contracts; deferred capabilities remain explicit.
Metadata or a representation alone is insufficient evidence.

| Status | Foundation | Minimum completion proof |
|---|---|---|
| Complete (scalars and inline local initialization) | Variables and scopes | [Scalar proof](../../tests/features/local_lowering.php): initialized typed locals, reads/assignments, nested scopes/shadowing, independent copies and early returns. Later proofs add [inline construction/borrowing](../../tests/integration/runtime_inline.php), [cleanup](../../tests/integration/runtime_cleanup.php) and [same-type local copy construction](../../tests/integration/runtime_copy.php). Source-record assignment is proved below; runtime opaque assignment is proved through explicit metadata; general move expressions remain unsupported; expiring-local result construction is supported. Integer widening is proved below. |
| Complete (scalar values and bounded record references) | Function parameters | [Native/update proof](../../tests/features/parameter_lowering.php): both argument positions, nested calls, mutable parameters and copy isolation, cross-file contracts and body-edit reuse. Scalar copy isolation remains unchanged. [Record-reference proof](../../tests/integration/source_record_borrows.php) adds call-scoped const/mutable source parameters, template specializations, alias forwarding and one body increment. Custom-copy proofs add const managed/owning record parameters. Owned record parameters remain deferred; owned results use the shared caller-storage contract. Integer widening is proved separately below. |
| Complete (request/selection model, widening and one provider conversion) | Conversions | [Native/update proof](../../tests/features/integer_conversions.php): same-family, same-signed widening through arguments, returns, initialization and assignment; signed/unsigned full-width native checks, reuse and repair. [Widening policy](../details/integer_conversions.md). The [conversion model](../details/conversion_selection.md) selects by source/destination/purpose; [native/update proof](../../tests/integration/runtime_conversions.php) covers a named explicit integer-to-string conversion and a second configured owned type. Narrowing, signedness changes and cast expression syntax remain unsupported. |
| Complete (exact-type integer addition and comparison) | One operator | [Native/update proof](../../tests/features/addition.php): addition composes with literals, variables and call results through shared operand traversal and selected contracts. Explicit provider support; same canonical operand types; wrapping overflow. [Comparison proof](../../tests/features/integer_comparisons.php) adds signed/unsigned `<` with an independent boolean result through the same binary path. [Rules](../details/operations_and_control_flow.md). |
| Complete (scalar flow and inline cleanup) | Control flow | [Original native/update proof](../../tests/features/control_flow.php): `if/else`, `while`, branch/loop assignments, repeated initialization, joins, early returns and fixed workers. Later [cleanup](../../tests/integration/runtime_cleanup.php) and [copy](../../tests/integration/runtime_copy.php) proofs cover managed inline values across branches, loops and returns. Integer conditions; braced bodies; no break/continue. [Flow contract](../details/operations_and_control_flow.md#typed-control-flow-boundary). |
| Complete (supported scalar/address/span contracts) | ABI calls | [Scalar proof](../../tests/integration/runtime_abi.php): direct integer arguments/results, widening and narrow ABI attributes. Later proofs add [caller-storage construction and const borrowing](../../tests/integration/runtime_inline.php), implicit [destruction](../../tests/integration/runtime_cleanup.php)/[copy](../../tests/integration/runtime_copy.php), and [byte-span arguments and void results](../../tests/integration/runtime_strings.php), through shared stages and native linking. [Owned inline results from runtime functions](../../tests/integration/runtime_conversions.php) now use the shared caller-storage path. [Const record arguments](../../tests/integration/runtime_record_borrows.php) reuse the pointer ABI. Owned source results now share caller storage and explicit construction. Owned source parameters and general native aggregate ABI remain open. |
| Complete (bounded named-call surface) | Runtime strings and console | [Literal/copy/echo proof](../../tests/integration/runtime_strings.php), [owned conversion proof](../../tests/integration/runtime_conversions.php) and [console composition proof](../../tests/integration/runtime_console.php): strict string-to-integer conversion, line input, concatenation, byte length and input→parse→calculate→format→echo. Invalid input/EOF/read errors stop clearly; LF/CRLF and final-line behavior are explicit in the [contracts](../details/runtime_console.md). Fixed workers, one body increment and ordinary/full/ThinLTO execution are checked. |
| Complete (bounded source/provider records and managed field composition) | Structured values and layout | [Source/native proof](../../tests/integration/source_structs.php) and [provider/native proof](../../tests/integration/provider_records.php): public eligible integer fields, zero construction, independent copies/assignment and verified target layout through shared definition/location contracts. Fixed workers, rejected batches, snapshot purity and body-edit reuse are proven. [Syntax proof](../../tests/03_parse/struct_parsing.php) covers common field roles and definition changes. [Call-scoped const record borrowing](../../tests/integration/runtime_record_borrows.php) is proved through metadata, shared signatures and existing local storage, including body-edit reuse. Nested source fields and metadata-eligible managed fields now compose default/copy/destruction through the [lifecycle proof](../../tests/integration/lifecycle_composition.php). Source custom/automatic assignment is now proved; opaque runtime assignment is also proved through metadata; aggregate value ABI remains deferred. |
| Complete (bounded source and native families) | Source and consumed template types | [Explicit instances](../../tests/04_analyze/instantiate/explicit_instances.php) prove source type/value specializations and literal constants. Fixed-array proofs use concrete elements and templated capacities; growing source lists now prove concrete scalar and managed elements through the [shared storage/lifecycle path](../../tests/integration/managed_growing_list.php). [Nested native families](../../tests/integration/provider_family_nested.php) prove real vector elements, two ordered type parameters and shared concrete identities through metadata. [Source-dependent native arguments](../../tests/integration/source_family_execution.php) now execute through project modules and compiler-owned lifecycle exports, including nested source/native types. Generic source-list migration and broader capabilities remain deferred; general constant evaluation is outside this version. |
| Complete (bounded nested ownership) | Ownership and cleanup | [Nested native proof](../../tests/integration/provider_family_nested.php): construct, append/copy, assign, discard and return owned containers through early and normal exits; contents remain independent and observed allocations balance. Existing inline/source-field composition and caller-storage results remain shared. [Concrete managed source lists](../../tests/integration/managed_growing_list.php) additionally prove growth, copy/assignment, self-assignment and balanced element/buffer cleanup. Dynamically indexed compiler-tracked allocation owners, owned parameters, broader move semantics, smart-pointer families and exception unwinding remain deferred. |

Nested vectors prove recursive **type composition**. Their concrete type graph
is finite; arbitrary cyclic type definitions are a separate concern. A runtime
handle for a vector does not establish aggregate layout or aggregate ABI support.

### Variables and scopes: stage progress

- [x] Tokenization: ASCII `$name` and assignment `=`, using the existing flat
  token rows, source snapshots and per-file workers. Braces already exist.
  [Token proofs](../../tests/02_tokenize/tokenization.php) and
  [stage/update proofs](../../tests/02_tokenize/variable_tokens.php).
- [x] Parsing: initialized typed locals, variable reads, plain assignments and
  nested blocks in the existing flat AST. Role accessors, comparison and exports
  preserve those forms. [Parser proofs](../../tests/03_parse/parsing.php) and
  [stage/update proofs](../../tests/03_parse/variable_parsing.php).
- [x] Local binding and block scopes: callable-owned flat declarations, scopes
  and read/write bindings, nearest lookup, shadowing and source diagnostics.
  [Rules](../details/symbol_resolution.md#locals-and-block-scopes) and
  [stage/update proofs](../../tests/04_analyze/resolve_symbols/local_resolution.php).
- [x] Declared local types: shared annotation lookup/materialization, fixed worker
  inputs and per-callable local-to-type associations. [Boundary and proof](../details/local_type_resolution.md).
- [x] Initializer/assignment compatibility through the shared conversion resolver,
  typed local reads/writes and nested-block body checking. [Stage/update proof](../../tests/04_analyze/check_bodies/local_bodies.php).
- [x] Scalar local lifetimes: initialization, copy reads/writes, scope exits and
  early-return unwind, with unreachable declarations excluded. [Stage/update proof](../../tests/04_analyze/analyze_lifetimes/local_lifetimes.php).
- [x] Local storage and read/write lowering: one slot per reached binding, shared
  store path for initialization/assignment, loads for reads, LLVM emission and
  native execution. [Native/update proof](../../tests/features/local_lowering.php).

### Function parameters: stage progress

- [x] Frontend: typed parameter declarations and ordered positional arguments,
  including nested calls, in the existing flat AST. Parameter syntax contributes
  to the function definition; argument expressions contribute to its body.
  [Stage/update proof](../../tests/03_parse/parameter_parsing.php).
- [x] Parameter names and root-scope binding share the callable-local table and
  lookup rules, with an ordered parameter prefix. [Stage/update proof](../../tests/04_analyze/resolve_symbols/parameter_binding.php).
- [x] Argument-expression name resolution: nested calls and local/parameter reads
  share existing binding tables and dependencies. [Stage/update proof](../../tests/04_analyze/resolve_symbols/argument_resolution.php).
- [x] Declared parameter types: ordered signature types supply the local binding
  prefix; definitions are shared. [Stage/update proof](../../tests/04_analyze/resolve_types/parameter_types.php).
- [x] Parameter entry initialization and calls checking arity, argument types and
  left-to-right evaluation through shared callable contracts. The original
  [stage/update proof](../../tests/04_analyze/check_bodies/parameter_bodies.php)
  uses identity conversions; integer widening is proved by the later conversion slice.
- [x] Scalar parameter/argument lifetimes: incoming bindings start at entry;
  argument temporaries survive until their consuming call; assignments and exits
  share the local lifetime path. [Stage/update proof](../../tests/04_analyze/analyze_lifetimes/parameter_lifetimes.php).
- [x] Parameter/argument lowering, LLVM emission and native proof, including
  unchanged caller/object reuse and failure/repair. [Proof](../../tests/features/parameter_lowering.php).

Scalar value parameters and explicit bounded record reference parameters complete
the source-to-native pipeline within their supported contracts.
Arguments also use the shared [integer-widening path](../details/integer_conversions.md).
Source functions, including template specializations, support call-scoped const
and mutable plain-record borrowing through the [shared passing contract](../details/source_record_borrowing.md).
Const managed/owning source references additionally use accepted ownership summaries.
Runtime ABI calls support const borrowing of records and opaque inline objects
through the separate proofs below. Source returns include owned records/objects through
[caller storage and explicit construction](../details/owned_source_results_plan.md);
owned record/object parameters remain open.
The [three-file sample](../../examples/function_arguments/README.md) demonstrates
the scalar value path.
Source const opaque borrows now share the supported object-reference path. Defaults,
variadics, named arguments, scalar references, mutable opaque references and reference
returns remain unsupported.

## Sequence and composition gates

This section records the earlier preparation/consumption sequence and its completed
proofs. The [accepted remaining sequence](#accepted-remaining-implementation-sequence)
now orders future work. Locals, parameters, integer widening/addition and scalar
control flow establish the baseline; preparation, scalar ABI calls, inline
construction/borrowing, destruction, copying and bounded strings/console are implemented:

1. **Prepare the Simple C++ provider package.** Develop the
   [runtime preparation tool](../../src-runtime-preparation/README.md) in
   `src-runtime-preparation/` until the compiler becomes part of
   Simple C++. Follow the [recorded design decisions](../details/runtime_package_preparation.md).
   The tool consumes the selected Simple C++ version's headers and implementations,
   our local exposure/semantic JSON definitions and target/toolchain configuration.
   Importing existing Simple C++ metadata is deferred. Export type/layout metadata,
   operation/ABI metadata, matching compiled implementations and a package
   manifest. Clang supplies declarations, layout facts and compiled code; local
   definitions supply exposed operations, language meaning, ownership and
   error policies. Validate advertised bindings against actual implementations.
   Publish metadata and artifacts together; reuse outputs while their relevant
   inputs are unchanged. Preparation belongs to the selected Simple C++ version
   and configuration, not each application or source file. Validate the package
   with an isolated consumer before compiler integration; this alone completes
   none of the compiler capability rows. The initial string package is now
   [implemented and independently tested](../../src-runtime-preparation/README.md),
   including construction, byte length, destruction, metadata/artifact agreement
   and unchanged-input reuse. Generic copy preparation is implemented below; the
   default string copy binding, literal/echo consumption and the bounded console
   operations are implemented through the proofs below.
2. **Consume one scalar ABI call through the compiler.** Import and validate
   package identities, compatibility, callable contracts and artifact dependencies.
   Resolve a metadata-declared external call through the common checking,
   preparation, lowering and linking stages. Prove arguments and a result,
   including an integer-widening conversion. Compiler consumers must not select
   implementations by hard-coded runtime names or fixture-specific tables.
   **Implemented:** the [package adapter and scalar consumer](../details/runtime_package_consumption.md)
   expose compiler-owned storage, callable and module contracts. The compiler
   validates and reserves the package, joins imported declarations with source
   symbols, checks/converts direct integer arguments, emits matching ABI calls and
   links ordinary bitcode. Source-body reuse and provider-change invalidation are
   proved. Full/ThinLTO remain isolated feasibility tests.
3. **Support runtime-defined inline storage and basic lifetimes.** Split this into
   the following implementation tasks, following the [agreed first-slice scope and model review](../details/inline_runtime_storage.md):
   - **Complete — inline storage, construction and call-scoped borrowing:** the
     [native/update proof](../../tests/integration/runtime_inline.php) uses two
     metadata-defined types, including aligned noncopyable storage. It covers
     locals, temporaries, nested borrows, repeated loop construction and early
     return. That initial proof uses verified no-cleanup contracts; cleanup and
     copying are established by the follow-up slices below.
   - **Complete — basic cleanup:** [analysis obligations and explicit destruction](../details/runtime_cleanup.md)
     cover scope exits, early returns, branches, repeated loops and full-expression
     temporaries for metadata-defined inline objects. The [native/update proof](../../tests/integration/runtime_cleanup.php)
     compares construction/read/destruction traces with native scoped lifetimes.
     Strengthening covers destruction-order validation at shared boundaries and
     separate full-build/one-increment proofs of unchanged cleanup reuse and
     managed-body cleanup replacement under unchanged callable contracts.
     Copy construction is implemented below; move operations and exception unwinding
     remain separate capabilities.

   **Complete — lifecycle preparation work units:** select implicit destruction/copy
   tasks before execution, with fixed operation/configuration inputs and private
   outputs. `Lifecycle_Join` validates selected results, reuses current targets and
   omits removed operations before backend-context acceptance. Tool verification
   stays in the coordinator; execution remains serial. Proof covers reversed
   completion, full/empty selection, partial replacement, purity and rejected batches.

   **Complete — inline runtime copy construction:** [the coordinated model refactor](../details/runtime_copy_construction.md)
   replaces the initialization boolean with explicit modes and generalizes implicit
   destruction preparation to lifecycle operations. Metadata-authorized same-type
   local declarations copy into separate aligned storage, preserving source ownership
   and giving the destination independent cleanup. The native proof covers two types,
   fixed workers/joins, source reuse, control flow and one managed-body increment.
   Owned source results now use these contracts plus explicit expiring-source construction. Runtime assignment exposure, general moves, owned parameters and smart pointers remain open. Source-record assignment is implemented separately below.

   Reserve storage
   using authoritative size/alignment and keep value identity separate from its
   address. Prepare the required direct-value, borrowed-address and caller-provided
   result-storage contracts. Prove string construction, borrowing, copying when
   required, and destruction on normal scope exits and early returns. Metadata
   availability does not implement these compiler actions. Neither passing an
   address nor a nonnumeric type implies separate heap allocation. Runtime-private
   fields need not become source-visible; arbitrary record syntax, field access
   and general aggregate passing by value are not prerequisites for this step.
4. **Consume the small string/console surface.**
   **Complete — literals, local copies and echo:** [the metadata-driven implementation](../details/runtime_string_literals.md)
   adds span arguments, void runtime results and language-role bindings. The
   [native/update proof](../../tests/integration/runtime_strings.php) uses real Simple C++
   strings and a second type added through definitions, including binary bytes,
   typed/default literal construction, ordered echo, cleanup and literal-body replacement.
   Interpolation and Unicode escape syntax remain explicitly unsupported.
   **Complete — conversion selection and owned runtime results:** the
   [request/selection model](../details/conversion_selection.md) migrates
   implicit widening and proves a named explicit integer-to-string conversion,
   with a second configured type using the same result-storage and cleanup path.
   **Complete — bounded console composition:** [provider contracts and proof](../details/runtime_console.md)
   add strict string-to-integer conversion, line input, concatenation and byte length
   through existing generic calls. Input removes LF/CRLF, accepts a final unterminated
   line, and stops clearly on empty EOF/read errors. Invalid integers also stop.
   Recoverable errors, retries and exception machinery remain outside this slice.
   Ordinary runtime functions and source functions now produce owned inline results
   through shared caller storage. The [owned-result migration](../details/owned_source_results_plan.md)
   supplies source return semantics; cast expression syntax remains deferred.
   Add only the source syntax and operation contracts needed for these uses.
5. **Continue the broader foundations.** The source two-field record/layout
   proof is implemented; continue toward consumed template families and nested ownership. Vector consumption
   and ownership must be designed together, even if delivered in smaller steps.
   The agreed scope now includes bounded source-template definitions and use before
   provider-family consumption; broad C++ template authoring remains deferred.
   Enumeration of special instances remains excluded.
   Source-defined structs now use [shared structural contracts](../details/structured_values_layout.md):
   normalized definitions, root/field locations and selected target-layout preparation.
   Complete plain provider records now enter through JSON preparation and the same
   normalized producer boundary, with native layout constraints checked before use.
   Call-scoped const record borrowing is also complete through the shared runtime-call
   path, with exact named signature references resolved after record acceptance.
   Nested source records and metadata-eligible managed fields now compose default
   construction, copying and destruction. Zero-argument custom constructor/destructor
   bodies supplement that composition through ordinary checked source methods. Custom
   copy bodies now use field defaults and an exact const source; implicit copies
   continue to compose member copies. Source-record assignment is now supported
   through independent field/custom contracts. Opaque private-field exposure,
   runtime assignment exposure and aggregate value ABI remain separate follow-ups.
   Follow the accepted remaining sequence.

The preparation tool's metadata format is isolated behind the compiler adapter.
The compiler now supports scalar values and opaque inline construction/borrowing,
with no-cleanup or validated destruction contracts, explicit copies and
metadata-selected literal construction/output. The bounded console surface is
complete through provider JSON and local implementations, without new compiler
adaptations. Future operations must fit supported semantic/ABI contracts or expose
a concrete reusable gap; this sequence does not authorize unrelated broad refactors.

### Metaprogramming: stage progress

Checkpoint before this slice: `e627912` (foundations consolidation). The worktree
was clean before parser implementation began.

- [x] **Parsing:** general ordered template applications, type/value parameter
  declarations, constants, `constexpr`/`consteval` function specifiers and
  `if constexpr`/`if consteval` syntax. Tokenizer vocabulary, flat structural views,
  exports/comparison and one frontend replacement are proved. Unsupported required
  semantic work reports diagnostics rather than being skipped.
  [Design, grammar and proof](../details/metaprogramming_parsing.md).
- [x] **Collection/resolution:** definitions, parameter scopes and stable bindings,
  retaining the original AST unchanged. Instantiation consumes these results;
  definition permissions precede concrete operation preparation. A template definition has its own
  identity, separate from concrete instance identities that refer back to it.
  Ordinary annotations now follow accepted declaration bindings into concrete
  preparation. Fixed workers, joins, formal-parameter dependencies and ordinary
  native execution plus one increment are proved. Template definition edits retain
  the existing full-rebuild fallback.
  [Contracts, limits and proofs](../details/template_bindings.md).
- [x] **Explicit instantiation and literal constants:** demand-driven instances
  with exact keys and prerequisite requests; global integer constants initialized
  directly by integer literals. No expression evaluation in this compiler version.
  Follow the C++ model:
  separate concrete specializations per demanded argument set, reused across
  objects and call sites. Required unsupported compile-time computation is a
  source error. `constexpr`/`consteval` syntax constrains the design; execution
  and compile-time branch selection are deferred beyond this version.
  [Implemented contracts and concrete-identity migration](../details/explicit_instantiation.md).
- [x] **Concrete checking and coordination (explicit source subset):** existing
  type/body owners consume instance contexts through selected fixed work, private
  outputs and joins. Record prerequisites precede signature/local preparation;
  one body edit replaces demands while retaining unchanged concrete bodies.
- [x] **Fixed-array source-family proof:** the minimal source list implements append
  and element reads with public methods, inline arrays, const/mutable receivers,
  concrete element types, templated capacities and explicit receiver calls. Bounds checks use
  common indexed locations. Private member workers/joins and a selective method-body
  replacement are proved. [Contracts and proof](../details/fixed_array_list_plan.md).
- [x] **Growing source-list proof:** source-written append/read/count and lifecycle
  methods compose typed storage with replacement on each append. Two scalar element
  types, allocation/release balance, O1/ThinLTO and one method increment are proved.
  [Contracts and remaining sequence](../details/source_list_plan.md).
- [x] **General list copying (initial element subset):** custom copy construction,
  assignment releasing old contents and direct/forwarded self-assignment are proved
  through shared lifecycle contracts. Two concrete scalar-element records preserve independent
  ownership and exactly-once cleanup. Managed slot lifecycle and the concrete managed growing-list extension are now proved; generic migration remains deferred. [Scope and completion evidence](../details/source_list_plan.md#follow-up-debt-general-list-copying).
- [x] **Provider-family proof (runtime-scalar subset):** shared instance contracts
  drive adapter/preparation, type/method demands and real native vector append. Two
  ordered type arguments, generic forwarding, fixed workers and one increment are
  proved. [Integration evidence](../details/provider_family_compiler_integration.md#gates-4-and-5-const-scalar-borrowing-and-integrated-append).
- [x] **Nested native provider combinations:** accepted native recipes and exact type
  imports prove recursive ownership in [step 8](../../tests/integration/provider_family_nested.php).
- [x] **Source-dependent provider combinations (bounded payload profile):** explicit
  project modules consume compiler-owned operations for automatic and verified custom
  source lifecycles, including nested source/native combinations. [Execution proof](../../tests/integration/source_family_execution.php)
  and [custom-body proof](../../tests/integration/source_family_custom.php).
- [x] **Generic parameter permissions (bounded extension):** check permitted uses
  at the definition boundary and preserve them through concrete specialization.
  The initial scope is the implicit copy/assignment/cleanup baseline for bare
  `<T>`, including type-argument eligibility checks and retained definition results.
  [Native/worker/increment proof](../../tests/04_analyze/check_templates/generic_contracts.php). Additional explicit capability
  contracts and `new T` remain deferred.
  [Revised default contract, exclusions and migration needs](../details/generic_type_contract.md).

Parsing, binding, literal integer constants and explicit source instantiation are
implemented within their documented subsets. Fixed-array source list behavior is
proved alongside the growing list and its copy/assignment operations. Provider
templates work for declared runtime scalars, ordinary managed runtime types and
nested prepared native families through the shared preparation/import path.
Eligible source-dependent arguments now use project modules and verified compiler-owned
lifecycle exports. General constant evaluation is outside this compiler version.

**Future versions — code sharing:** separate concrete specializations first;
possible code sharing through optimization afterward, preserving observable
behavior and distinct semantic identities. This is deferred and is not required
for the collection/resolution or initial instantiation proofs.
[Recorded decision](../details/metaprogramming_parsing.md#agreed-specialization-and-identity-model).

### Provider artifacts and LTO feasibility

Prepare compatible LLVM artifacts with the provider package; distinguish readable
`.ll`, ordinary bitcode and bitcode prepared for ThinLTO in its manifest. Keep
runtime version, target, toolchain/configuration and relevant content dependencies
with the artifacts. Reusing provider IR does not imply reusing its final optimized
machine code; application linking and LTO optimization remain application work.

- [x] Run an isolated ordinary-link, full-LTO and ThinLTO consumer proof against
  the prepared package. Check execution and evidence of cross-module optimization,
  then one implementation-body change with retained unchanged module inputs.
  Supply all current modules and let LLVM manage optimized backend dependencies.
  [Standalone package proof](../../src-runtime-preparation/tests/run.php).
  This is a feasibility test, not production
  LTO integration, cache-performance work or a change to the default build mode.
- [x] Include compiler-generated consumer modules, verify native results and
  cross-module call elimination under full/ThinLTO, then replace the provider body
  while retaining caller bitcode. [Proof](../../tests/integration/runtime_abi.php).

### Composition proofs

- [x] Assign an operator result to a variable. [Proof](../../tests/features/addition.php).
- [x] Pass a converted value into a [language function](../../tests/features/integer_conversions.php)
  and an [ABI call](../../tests/integration/runtime_abi.php).
- [x] Assign in both branches, then use the resulting variable after the join. [Proof](../../tests/features/control_flow.php).
- [x] Read a console line, convert it to an integer, perform arithmetic, convert
  the result to a string and echo it. Prove cleanup of reached string values and
  clear termination for invalid input, without a retry/recovery flow.
  [Proof](../../tests/integration/runtime_console.php).
- [x] Return an owned nested vector through an early-return path; prove cleanup
  of other live owned values and continued validity of the returned contents.
  [Nested native proof](../../tests/integration/provider_family_nested.php).

For each slice, prove real compilation/execution, relevant diagnostics, and the
[current required flow](#current-required-flow). Unsupported incremental changes use the common full
fallback; new incremental categories require their own impact rules and proof.
Keep fixed worker inputs, separate outputs and coordinator joins, executing
serially. Refactor within the owning process when the model needs it; stop and
discuss complexity or changes spanning ownership areas.

Exceptions, closures, inheritance, overload sets, broader template features,
actual threading, optimization and broader performance/memory profiling remain
deferred apart from the isolated LTO feasibility proof above.
[LLVM LTO readiness](../details/llvm_lto_readiness.md) records future
parallel and incremental backend considerations; keep normal native compilation
at `-O0` for now. The [first scalability run](../details/scalability_first_run.md) records an early
baseline through 10 MiB. This tracker does not schedule further performance work
or require it for these capability proofs.

## Native-port preparation gate

Before an explicitly authorized Simple C++ port, replace PHP mixed and every
unparameterized/unclear array with concrete result and typed container contracts.
The [join interface](../../src/compile/join.php) deliberately uses a mixed PHP
return envelope today; its implementations preserve concrete returns and document
all task/result array element types. The [join inventory](../details/join_organization.md)
records the array-returning contracts. Verify native container and record behavior
through the configured toolchain as required by [AGENTS.md](../../reference/source-repository/working_rules.md).
This preparation remains open; interface adoption does not claim native readiness.

## Failed increments: rebuild first, rollback later

### Current required flow

The required prototype scenario is exactly:

1. One full rebuild establishes the baseline.
2. One incremental attempt uses that baseline and either succeeds or reports failure.

Further increments, recovery after failure and rollback are outside the current
required proof. Future workers must not make those scenarios prerequisites for a
feature slice or add recovery frameworks without explicit scope expansion. Keep
identities, ownership, fixed inputs, outputs and joins reusable for later updates.
This limits required development work, not runtime capability: preserve existing
broader behavior and tests; do not introduce a one-increment restriction.
Failures still need honest diagnostics and cleanup of locks, running tools and
private outputs; partial results must not be reported as completed.

### Deferred recovery direction

Keeping the previous compilation usable after a failed attempt is not a required
prototype capability. Recovery may abandon incremental reuse and rebuild through
the same stages after the input is repaired; do not retry failing source forever.
The recovery items below are future work, not requirements for the current flow.

This is a planning direction, not a change to current runtime behavior. The PHP
implementation currently preserves accepted snapshots through separate candidates
and publishes native output separately. There is no general row rollback engine.
Those existing guarantees and tests remain until an explicit implementation slice
changes the recovery policy.

- [ ] Define the coordinator's recovery boundary: a failed update must never be
  used as an incremental baseline. If retained state may have been modified,
  discard/reset the affected state or start with fresh stores before rebuilding.
  Setting full_rebuild alone does not repair corrupted stores or indexes. Recovery
  must bypass invalid reuse shortcuts and use the common full-selection algorithm.
- [ ] When recovery is implemented, a failed recovery rebuild must leave the
  session requiring another rebuild rather than enabling incremental reuse.
- [ ] Later, investigate rollback for incremental updates only. Row-oriented
  storage provides a useful basis for recording additions, replacements and
  removals, but restoration must also cover indexes, identity allocation state,
  dependency links and change flags. Start with one bounded update category and
  prove that every affected owner returns to a coherent baseline. If rollback is
  unsupported or fails, fall back to rebuilding. Native filesystem publication
  and tool cleanup require their own handling beyond row restoration.

Keep fixed worker inputs, explicit outputs, owned stores and joins: these also
support ordinary incremental correctness and future workers. Relaxing recovery
requirements does not by itself authorize shared in-place mutation or a broad
storage redesign. Review actual simplifications in a focused implementation slice.
