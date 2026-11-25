# Provider families: implementation plan for parts 1–3
Doc Status: supporting

Status: parts 1–3 implemented on 2026-09-20 after coordinated-refactor approval.
The [accepted design](source_native_contract.md#accepted-typed-records-and-semanticabi-separation)
owns semantics. This plan identifies the coordinated refactor, sequence and proofs.
This historical preparation milestone stopped before compiler-driven family demand
or source-dependent native imports. Those later boundaries are now implemented in
their bounded subsets: [compiler consumption](provider_family_compiler_integration.md)
and [source-dependent imports](source_family_integration_plan.md). The original
scope and validation below describe parts 1–3, not current whole-compiler limits.

## Outcome and scope

1. Ordinary imported calls use semantic signatures independent of physical ABI.
2. Typed family declarations express ordered type parameters, the default baseline,
   operation signatures, lifecycle requirements and bounded alias/invalidation facts.
3. Runtime-only requests prepare/reuse/extend a specialization package through private
   results, validation and stable publication, with native execution evidence.

Part 1 necessarily changes existing compiler consumers to preserve their current
behavior. The deferred compiler integration is **new family consumption**, not a
prohibition on migrating existing calls onto the corrected shared model.

No new source syntax, general constraints, reflection, constant evaluation, generic
list restoration, managed element storage, borrowed results or source aggregate ABI.
No source-type export/import integration, interior-reference acceptance, actual MT,
new compiler recovery categories or cache garbage-collection framework. Existing
broader behavior/tests remain intact. JSON is a boundary format; no JSON Schema
engine or arrays of ad hoc JSON fields enter the new semantic/request protocol.

## Pre-migration findings addressed by this refactor

| Current owner | Evidence and consequence |
|---|---|
| [runtime_callable](../../src/04_analyze/type_model/data/callables.php) | Contains semantic names/types and physical ABI together; `passing_for()` derives semantics from ABI classes. An unresolved family cannot truthfully use this record. |
| [Callable_Import](../../src/01_prepare_inputs/load_runtime/handlers/callables.php) / [Binding_Import](../../src/01_prepare_inputs/load_runtime/handlers/bindings.php) | Parse and validate both dimensions together. Echo/literal/conversion recognition also reads ABI shape. Separate semantic validation from physical compatibility without losing the existing checks. |
| [Local_Write_Checking](../../src/04_analyze/check_bodies/handlers/writes.php) | Uses `caller_storage` to recognize a fresh owned call result. Semantic checking must instead use result production/ownership; backend still selects physical destination passing. |
| [Specialization_Request](../../src-runtime-preparation/requests.php) | The isolated exporter accepts arrays and substitutes `$self`/parameter strings recursively through arbitrary rows. Introduce typed references and substitute only declared type positions; preserve native-binding fields as native-binding fields. |
| [Runtime_Preparation](../../src-runtime-preparation/prepare.php) | Owns reuse, private building, verification and publication in one `run()`. Reuse uses a whole-input fingerprint, not specialization identity plus operation coverage. |
| [Prepared_Request](../../src-runtime-preparation/request_adapter.php) | Request-specific acceptance occurs after publication in the isolated driver. That cannot preserve the old package when a new candidate fails request validation. Move acceptance of candidates before publication. |
| [Package_Adapter](../../src/01_prepare_inputs/load_runtime/package_adapter.php) / Runtime_Lease | Compiler holds a shared lease through linking; preparation takes an exclusive nonblocking lock for its entire run. A caller must not request replacement while retaining its own shared lease. |
| [Definitions](../../src-runtime-preparation/definitions.php) / [Bridge](../../src-runtime-preparation/bridge.php) | Address borrowing currently accepts runtime objects/plain records; integers may pass by value to a native const-reference parameter. That existing adaptation does not prove the agreed semantic scalar borrow. Part 3 needs a bounded preparation-side const scalar-address path. |

## Part 1 — shared semantic signatures

### Records and ownership

Keep shared records in `04_analyze/type_model/data/`, splitting semantic call records
from the existing physical ABI records in `callables.php`. Proposed filenames:
`semantic_calls.php` for parameter/result/signature records; `callables.php` for
prepared ABI and the imported callable association. Exact names may be adjusted
locally, but there must be one owner for each fact.

| Record/concept | Data and invariant |
|---|---|
| Semantic parameter | Type reference plus existing `argument_passing`; no width, ABI attribute or physical position. |
| Semantic result | Type reference and explicit production: void, scalar/value result, or fresh owned object. An owned object is a new lifetime, not permission to copy another object. Borrowed results are unsupported. |
| Semantic signature | Ordered parameters, result and semantic requirements/effects. Receiver exposure identifies a normal parameter position. Existing allocation effects retain their distinct resource meaning. |
| Prepared callable ABI | Link symbol, convention, result transport, physical parameter mappings and measured attributes. Each semantic input maps to its actual ABI positions; spans expand and caller storage contributes a hidden destination. |
| Imported callable | Provider/operation identity, source exposure and language/conversion bindings, plus references to its semantic signature and verified ABI. It does not duplicate mutable parallel parameter lists. |

Retain `result_passing::direct/caller_storage` for backend transport; correct its
semantic-looking documentation. Do not turn this split into support for new result
ABIs. Existing canonical `signature_representation` remains the concrete type-store
projection; source and imported signatures still meet there. No second concrete
signature store or copied function AST is needed.

### Migrate existing producers and consumers together

- `load_runtime/handlers/callables.php`: create semantic parameter/result records
  from declared metadata, then validate measured ABI against them. Preserve existing
  v1 concrete package support. Malformed semantics never become valid through ABI inference.
- `handlers/bindings.php`, `handlers/resources.php`: use semantic signatures for
  roles, conversion eligibility and effects; keep physical checks in the ABI validator.
- `resolve_types/signatures.php`, `signature_join.php`, `data/result.php`: resolve
  semantic references and passing, retain exact source/provider provenance and join checks.
- `check_bodies/handlers/writes.php` and `data/result.php`: use semantic owned-result
  production and effects. Lifecycle selection remains with existing owners.
- `prepare_backend/callable.php`, `utilities/callable_contract.php` and retained
  context validation/exports: read the prepared ABI association and validate it
  against the accepted concrete signature. Changed semantic or ABI contracts invalidate reuse.
- Lowering and emission should continue consuming existing `callable_binding`
  transport contracts. Change them only if a real representation dependency requires
  migration; do not redesign LLVM plans or lifecycle composition in this slice.
- Update affected tests, bootstraps, exports and navigation maps. Preserve identities
  of unchanged accepted records, selected work and previous-snapshot immutability.

**Gate 1:** ordinary scalar/void, const/mutable object borrow, byte-span, constructor,
owned-result conversion and allocation-effect paths execute unchanged. Prove semantic/
ABI disagreement rejection, missing physical positions, spans/hidden destinations,
fixed joins and one ordinary body increment. Then proceed to family declarations.

## Part 2 — typed family declarations and validation

### Shared records versus native preparation bindings

Add concrete responsibilities under `type_model/data/families.php` and, when needed,
`type_references.php`. A small records-only load entry can serve both the compiler
and standalone preparation bootstraps; it must not start compiler phases or load a
compiler session. Shared records must not depend on preparation/Clang classes.

- A family definition has exact provider/family identity, ordered parameter records,
  formation requirements, operation declarations and declared lifecycle-role bindings.
- Formal type references carry owner identity and slot, not a bare slot alone.
  Named types retain exact identity; family applications retain ordered arguments.
  Definitions have no fake canonical type ID or layout.
- Operation declarations reuse the semantic signature from part 1 and reference
  capabilities by existing lifecycle roles. The sole supported type-parameter
  contract is `generic_contract::copyable_value`. There is no arbitrary boolean
  constraint language; an operation requiring something outside the supported
  vocabulary must fail explicitly rather than infer it from a concrete instantiation.
- Formation requirements are independent of operation requirements. Do not infer
  default construction of the element merely because a container can be constructed.
  Declared family lifecycle capabilities also need real implementations before being
  exposed as concrete capabilities; C++ traits alone must not invent bindings.
- Represent safe input overlap and element-storage invalidation separately, using
  parameter positions and a defined element-storage relationship. Validate indices,
  receiver/input types, constness and allowed effects. These records are preparation
  contracts here; existing compiler consumers must reject unsupported effects rather
  than silently ignore them. Source interior-reference acceptance is part 4 work.

The private `check_templates/type_term` additionally carries source binding/constant/
array checking information. Do not expose it as a provider wire format or move that
worker state into `type_model`. Extract/reuse its named/formal/application identity
concept where meanings match; migrate the touched `Terms` construction/comparison
helpers if that shared representation changes. Constants, source locations, local
constness and traversal state remain with `check_templates`. This is representation
consolidation, not enabling provider calls in generic source bodies.

Native C++ family names, headers, callable adapters and C++ template arguments belong
to preparation-owned bindings, separate from compiler semantics. Parse explicit
references at the boundary; no recursive string replacement through unrelated
fields. Adding a family of supported shape must require only definitions/native
bindings, not branches on fixture or family names.

### Process locations

Use a focused `src-runtime-preparation/families/` process: `structures.php` for
preparation bindings/requests, `catalog.php` for parsing/validation and `requests.php`
for typed argument substitution and required-operation closure. Add processing files
only as their responsibilities are implemented. `store.php`, preparation workers
and `join.php` follow in part 3. Keep `calls.md` adjacent.

The compiler adapter owns normalized family declarations in a dedicated handler or
`Family_Adapter` within `01_prepare_inputs/load_runtime`; it may be tested directly,
but this plan does not register families in collection or schedule demands from
`Concrete_Preparation`. The compiler does not consume native C++ mapping strings.

Migrate the isolated `Specialization_Request` fixture path onto the typed family
parser/substitution owner. Keep its existing plain-source-record experiment isolated;
its source export does not become the production source-operation-import protocol.
Existing concrete `Definitions`/`Bridge`/`Metadata` remain the code generation route.
Any legacy array conversion is an explicit boundary projection into that existing
concrete definition reader, not another semantic model or new compiler-facing API.

The existing `storage_family`/`storage_function` describe compiler-known allocation
primitives with stronger element restrictions. Do not relabel them as general
native families or imply that their generic-list restriction has been resolved.

**Gate 2:** ABI-free signatures parse and validate; renaming a fixture family/operation
and changing supported type arguments needs no compiler/tool code change. Prove two
ordered type parameters, distinct formal identities, wrong arity, malformed references,
unsupported requirements/effects and missing lifecycle implementations. Existing
source generic permission proofs still pass, including rejection before specialization.

## Part 3 — selected preparation, reuse and publication

### Typed request and retained state

`families/structures.php` owns fixed `specialization_request`, preparation-task and
private-result records. Reference immutable family/binding/context snapshots and
ordered accepted type arguments; include selected operation IDs and derived lifecycle/
native dependencies. Requests for source-dependent arguments fail explicitly in this
first production path; the earlier isolated source experiments remain separate.

`families/store.php` owns the exact specialization index and accepted operation
coverage within a configured output root. Separate:

- Logical key: scope, provider/family, ordered complete argument identities and exact
  runtime/target/configuration identity. Compiler canonical IDs are not persistent keys.
- Freshness: dependency snapshots, tool/runtime/header input fingerprints and validated
  artifacts. Content hashes remain change/integrity checks, never entity identities.
- Coverage: available operation IDs plus required dependency closure. Coverage is not
  part of the logical specialization identity. Union compatible old coverage with
  new demand; do not carry an old operation contract across an incompatible revision.

For bounded path lengths, persist an exact-key-to-monotonic-directory-slot index under
this named store, protected by a short root-index lock. Slots are scoped to that root,
never recycled while retained references may exist; failed builds may leave reserved
slots but cannot alias another key. Store complete keys and compare them on every join.
Generated symbols encode complete logical identity/operation components with the existing
readable `Symbols` encoding; a root-local directory slot is not a globally unique link ID.
Each slot has one stable `package/` and pointer using the existing format. Configuration
controls the output root. No content-hash generation folders or per-use package copies.

### Repair the build/accept/publish boundary

Extract concrete responsibilities from `Runtime_Preparation::run()` rather than
building another generator. Both ordinary packages and family packages use:

1. Fixed configuration/definition inputs and dependency inspection.
2. `Bridge`, `Clang_Toolchain`, `Metadata` private generation and variant/link checks.
3. Candidate validation against the exact selected request, before publication.
4. Input revalidation, artifact sealing and the existing directory/pointer publication
   algorithm with cleanup/recovery behavior preserved.

Use a small candidate record and named validation/publication owners (proposed root
`structures.php`, `candidate.php`, `publication.php`); no arbitrary callback framework.
Keep one coordinator per request kind over these shared operations. Extract only the
needed pure candidate checks from `Prepared_Request`; its public lease-based reading
path must also revalidate publication. Candidate validation must not try to acquire a
shared lock inside an already-held exclusive lock. Ordinary `Package_Adapter::open()`
remains read-only and must never trigger preparation.

`families/prepare.php` selects work before generation. Workers build private outputs
from fixed requests; `families/join.php` validates task identity, family/argument/context
snapshots, expected coverage and complete normalized results. Joins reject duplicate,
unselected, missing and stale results. Result validation is distinct from filesystem
publication; publication rechecks the expected previous package and current inputs.
Accept contracts into the retained family store only after publication succeeds.

A prepared superset satisfies a smaller request without regeneration. A missing
operation rebuilds the union through the same generator at the same stable location.
No package-identity change or old-operation symbol churn follows from adding coverage.
A source/runtime contract revision is a separate invalidation, not mere added coverage.
No global multi-package atomic transaction is required: failure of one request must
not expose its partial package or invalidate another accepted specialization.

### Locking and failure policy

Keep the existing conservative nonblocking policy initially. Preparation holds one
specialization's exclusive lock through private generation, acceptance and replacement;
a busy package produces an explicit retryable/busy result or diagnostic, not a wait
on a lease held by the same caller. This preserves current reader safety without
introducing unlocked generation and optimistic merge/retry machinery.

The root-index lock covers allocation/lookup only and is released before acquiring
any package lock. Never upgrade a shared package lock or hold several package locks
while preparing dependencies. Requests and prerequisite closure are fixed before a
task runs. Release any caller-owned read lease before requesting coverage extension;
acquire/revalidate the published package afterward. Future compiler integration must
schedule missing preparation before taking its long-lived linking leases.

Existing readers keep their lease; a conflicting extension fails without modifying
their package. Once readers release it, a new attempt may extend coverage. Preserve
old package bytes/pointer on candidate rejection or failed publication. A failed
new request must not silently receive an incompatible old package. Temporary outputs
and backups use the existing cleanup rules. This package behavior is not an expansion
of compiler incremental recovery scope.

### Native proof and first consumers

Use configured runtime `vector` with the language integer mapped by the provider
catalog, plus a tiny two-type native holder fixture initialized by copying its two
arguments. The holder proves independent ordered slots without introducing hash/key
capabilities. It is test data, not a compiler intrinsic or new runtime library feature.

The native fixture requests construction/destruction and copy append (or equivalent
copy inputs for the two-slot fixture), then adds a read-only scalar observation.
Scalar results keep owned-element results and interior borrows deferred. Compile/run
through accepted generated ABI artifacts, with ordinary and full/ThinLTO variants;
the fixture must not call the native container directly to bypass the bridge.
For the agreed const element input, extend `Definitions::validate_function_parameter()` and the shared
borrow adaptation/metadata validators to accept supported scalar object storage by
const address. The native fixture supplies live typed scalar storage and the measured
pointer ABI. Do not silently substitute the existing direct-value-to-C++-const-reference
adaptation or change the family signature to make the fixture pass. Preserve that
existing adaptation for operations that declare value passing. Family-result validation
must distinguish this valid prepared contract from current compiler consumption limits;
no new source scalar-reference ABI is enabled by this standalone proof. Broader address
representations still require their own supported contract.
Preparation validates requested parameter eligibility before invoking Clang; missing
native adapters/implementations remain explicit generation/link failures.

The standalone adapter returns typed accepted specialization records for tests and
future compiler consumers. It is not a second `Compiler_Session`, and does not make
provider templates source-visible. Preserve the ordinary self-contained package
link guarantee; project modules with unresolved compiler imports are still rejected.

**Gate 3:** first build, zero-generation reuse, compatible operation extension with
stable type/symbol identities, subset reuse, and incompatible-candidate rejection
leaving the last accepted package executable. Prove reader-lock conflict, cleanup,
input edits during preparation, exact-identity rejection despite equal layout, wrong
context/ABI/coverage and reversed private-result order. No hashes become identity.

## Implementation order and validation cost

| Checkpoint | Validation before continuing |
|---|---|
| 1. Semantic/ABI split and consumer migration | Focused adapter/backend tests and native ABI, strings, conversions, records and allocation proofs; one ordinary full build plus one body increment; exports, joins and retained purity. |
| 2. Family records/parser/substitution | New focused declaration/requirement/identity negatives, two-slot and renamed-fixture positives; existing generic-contract and isolated specialization tests. |
| 3. Candidate-before-publication and specialization store | New coverage/replacement/locking fixture plus existing preparation failure/publication/recovery tests. Native generated-artifact execution and ordinary/full/ThinLTO. |
| Final consolidation | One complete compiler suite with ten workers, the focused standalone preparation suites, PHP lint/comment/brace checks, documentation links and process/header navigation. |

Run focused tests while editing; do not repeatedly run the entire suite. The current
93-fixture compiler runner does not replace the standalone preparation probes. Record
actual updated counts and selected work/Clang command counts. Reuse means no repeated
AST/IR generation, not necessarily zero version/dependency-inspection commands.
Execution timings here are test durations, not runtime-performance claims.

For every new task/result document producer, fixed reads, private writes, join and
invalidation. Share immutable schemas, retain compact identity/dependency references,
and discard generation/checking scratch. No persistent expanded AST/call graph. Actual
threads and a native port remain out of scope; serial execution uses these same units.

## Coordinated-refactor approval and stop boundary

This crosses shared type contracts, package import, existing type/body/backend consumers,
and preparation acceptance/publication. It is not an additive parser-only change.
The current model cannot provide ABI-free family semantics or request-specific
pre-publication rejection truthfully without these changes.

Main risks: semantic/ABI index drift, false capability claims, stale contract reuse,
losing operation coverage, source-template provenance changes and lock/lease mistakes.
The staged gates above cover each risk; preserve existing package behavior while
migrating its owner. The smallest coherent option is the three checkpoints here.
Part 1 alone is a useful preparatory refactor but does not complete the requested
family boundary. Project-native source imports would be a materially wider option
and are deliberately excluded.

After implementation, stop before changes that discover families in source, collect
family symbols, schedule preparation from `Concrete_Preparation`, install multiple
family packages into a compiler session, enable imported interior references or link
source-operation imports. Discuss part 4 using the proven typed boundary. Any need
to widen this plan across further owners must be reported before continuing.

## Implementation outcome

- Shared [semantic calls](../../src/04_analyze/type_model/data/semantic_calls.php)
  and [ABI association](../../src/04_analyze/type_model/data/callables.php)
  now serve ordinary imported calls. Result ownership and argument passing no longer
  come from backend transport classes. Existing lowering bindings remain unchanged.
- [Family declarations](../../src/04_analyze/type_model/data/families.php)
  and [shared validation](../../src/04_analyze/type_model/family_contracts.php)
  express ordered formals, default formation contracts, operation requirements,
  receiver positions and separate overlap/invalidation facts. The
  [Family_Adapter](../../src/01_prepare_inputs/load_runtime/family_adapter.php)
  accepts normalized semantic records without native binding strings or stage execution.
- [Catalog/Requests](../../src-runtime-preparation/families/calls.md)
  parse explicit type positions and project bound declarations into the existing
  concrete Definitions reader. The isolated source-record exporter uses this same
  binding path; recursive arbitrary-field substitution is removed.
- [Preparation](../../src-runtime-preparation/families/prepare.php)
  selects fixed requests and prerequisite coverage, executes private workers and
  joins complete results before publication. `Store` persists full keys and allocates
  monotonic root-local slots. `Package_Candidate` owns the exclusive lease and sealed
  artifacts; both ordinary and family preparation use its validation/publication path.
  `Publication` retains the previous recovery/replacement algorithm.

### Bounded representation choices

Only declared runtime scalar arguments enter production preparation in this slice.
The generic baseline describes permissions; it does not claim that all types satisfying
that baseline already have native adapters. The isolated source-record proof remains
separate. A family result referring to an unresolved formal uses `dependent_value`;
concrete callable ABI association rejects unresolved production. Compiler consumption
must bind it to value or owned-object production before checking/lowering.

The source checker's private terms retain source declaration/provenance, constants
and arrays; they are not moved into the provider wire format. Their meaning is wider
than the provider's exact named/formal/self references, so no forced unification or
source-worker migration was needed.

Coverage compatibility conservatively compares the full catalog/native-binding and
configuration snapshot. This may rebuild more than a per-family dependency comparison;
it cannot carry coverage across a changed contract. Input fingerprints remain separate
from identity. Package-local generated type IDs avoid existing catalog spellings;
link symbols encode the full logical identity, independently of directory slots.

Request receipts and measured rows are serialized at the preparation/artifact boundary;
the new semantic/request protocol uses owned typed records. No JSON Schema engine,
compiler session, generic constraint language, general scalar source borrow or native
source-operation imports were added.

### Verification

The complete compiler run passed 93/94 fixtures in 76.6 seconds with ten workers.
The sole failure was an old two-command reuse expectation; after updating it for the
extra pre-publication dependency check, that fixture passed in a focused rerun.
Thus all 94 current fixtures have passing results, including the new semantic/ABI
contract proof. Unchanged reuse performs three inspection commands and no AST/IR
regeneration.

The standalone ordinary preparation suite passed 163 checks. The new
[family proof](../../src-runtime-preparation/tests/families/README.md)
passed 54 checks covering typed declarations, ordered parameters, coverage extension/
subset reuse, fixed joins, reader exclusion and candidate/input rejection, and runs vector/holder ABI calls
with ordinary, full LTO and ThinLTO linking. The existing source-specialization suite
also passed: both source shapes, ordinary/O1/full/ThinLTO execution, incremental caller
and record edits, and seven request/acceptance rejections. Its first attempt correctly
rejected an implementation edit during generation; the clean rerun passed.

The next discussion is **part 4: compiler-driven family consumption**. In particular,
preparation must be scheduled before linking leases are acquired, formal permissions
must survive substitution, and concrete family calls must join existing type/callable
contracts. Managed/source-dependent elements remain a later focused integration.
