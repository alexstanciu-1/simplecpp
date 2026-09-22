# Provider families: compiler integration plan
Doc Status: supporting

Status: discussion decisions accepted; implementation sequence recorded on 2026-09-20.
This document plans the compiler-consumption slice following the completed
[preparation boundary](provider_family_implementation_plan.md#implementation-outcome).
Gates 1–2 compose explicit runtime packages and register source-facing family
contracts. Gate 3 now prepares demanded native types and method coverage, imports
concrete signatures, and executes methods through shared ABI contracts. Gates 4–5
now add const integer-address borrowing and a real native vector append proof,
completing this bounded runtime-scalar integration slice.

## Outcome and limits

Compile a source program using a metadata-exposed native family: construct it,
call its declared methods with integer arguments, observe a scalar result and
clean it up through its prepared lifecycle. A configured vector and a two-argument
holder must use the same path, alongside the existing ordinary runtime package.
Source template bodies may forward their generic parameters into those declared
family operations when the formal guarantees satisfy the requirements.

Keep the default generic baseline as the only implemented parameter contract.
No general constraint language, overload resolution, reflection, constant evaluation,
managed/source-dependent native arguments, nested container preparation, escaping
borrows, interior-reference API, new source reference declarations, record temporary
borrowing or source aggregate value ABI. Existing capabilities remain intact.
Actual threading, repeated-increment recovery and cache eviction are not this slice.

## Accepted rules

### Locally predictable templates

The [generic contract](generic_type_contract.md#checking-and-ownership) is authoritative:
check each definition using only declared guarantees, including unused definitions.
Forwarding an argument to a source function, family or provider operation is legal
only if those guarantees satisfy the receiver's requirements and passing contract.
Do not enter callee bodies, search execution paths or try favorable concrete types
to establish permission. Concrete eligibility is a separate check that a supplied
type actually implements the promised baseline.

New demands discovered during instantiation are compilation dependencies, not a
search for which types make a body legal. Do not introduce richer constraint syntax
merely to test this rule; reuse the supported baseline and existing rejection cases.

### Source exposure and identity

Keep three facts distinct:

| Fact | Example | Owner |
|---|---|---|
| Provider identity | `simple_cpp / sequence / append_copy` | Shared family contract |
| Source exposure | `vector<T>`, member `append` | Validated exposure metadata |
| Native binding | `scpp_provider::sequence_append` | Runtime preparation |

Reuse the `language_type` name/namespace convention for family exposure and
`expose_as` for ordinary methods. A member is scoped under its family declaration;
its receiver remains an ordinary semantic parameter identified by its existing
index. A native free function may implement a source method. Construction and
cleanup use lifecycle roles, without requiring user-callable method spellings.
Reject duplicate exposed members within one owner; do not add overload selection.
No family, element type or method name may become a compiler dispatch condition.

Source exposure must not become specialization identity. Retain original provider
identity separately from the generated package/link namespace. Ordered argument
identities refer to accepted declarations, never matching layout or source text alone.

### Two readiness facts

A specialization is first semantically known: its definition, accepted arguments,
method signatures and declared permissions are available. It becomes physically
prepared after layout, lifecycle implementation and callable ABI acceptance.
Keep a semantic application identity separate from its materialized type definition;
never use a fake size, guessed alignment or placeholder ABI to make it ready.

Resolve available semantic work, combine currently discoverable operations by exact
specialization key, and submit missing coverage in fixed batches. Include lifecycle
dependencies. Newly exposed prerequisites resume through the existing readiness
queue. Do not regenerate once per call occurrence or rescan all known instances.
Later demands may extend coverage through the same preparation protocol.

### Const scalar borrowing

The common argument boundary selects storage from semantic passing:

- Borrow an existing place when its type already matches and its validity is established.
- Evaluate a scalar expression once into a compiler-owned temporary and borrow it.
- Apply only an already-permitted conversion; converted values require their own storage.
- Reject incompatible values without native preparation deciding language validity.

Keep that storage valid through the complete call, preserve existing evaluation order
and alias checks, and end semantic argument access afterward. Cleanup-free temporary
stack slots may remain allocated until function exit. Append copies into runtime
storage; the call borrow grants no right to retain the caller's address. This adds
compiler consumption of prepared const scalar-address ABI, not new source reference
syntax, mutable scalar borrowing or permission to borrow record temporaries.
A prepared pointer ABI alone never proves borrowing or overlap safety.

### Multiple packages and leases

Retain individual `Runtime_Package` validation and add one fixed runtime input-set
owner. Migrate the ordinary optional package onto this path first. The set indexes
accepted declarations, operations, implementation dependencies and protected paths;
the compiler type store still allocates canonical type IDs.

Repeated scalar mappings must resolve to the same existing language definition only
through an explicit validated mapping. Equal width/layout is not type identity.
Every source-visible parameter/result needs a mapping, including native size types;
do not guess signedness or silently expose an unmapped result. Reject conflicting
source exposures, exact identities, targets, layouts, module variants or link context.
Select each required package module once, regardless of the number of call sites.

The coordinator owns leases. Early package reads may use short reservations to capture
validated metadata snapshots; release them before a package can need preparation.
Finish preparation, acquire the final read leases and revalidate the expected accepted
snapshots before artifact use. Keep final leases through linking and release on every
exit. No resolver/worker upgrades a shared lease or invokes preparation implicitly.
A changed incompatible package causes a diagnostic, not stale reuse or an implicit
multi-package recovery framework. Existing protected-output checks cover every input.

## Owners and implementation order

Names for new files/records are provisional; ownership is the constraint.

| Gate | Touched owners | Required change and proof |
|---|---|---|
| 1. Compose runtime inputs | `load_runtime/data/package.php`, `package_adapter.php`; `compile/compile.php`; collection; `prepare_backend` context, joins, storage and toolchain | Separate one-package validation from input-set composition. Preserve ordinary runtime compilation; prove two packages link together, shared scalar mappings, conflict rejection and complete lease cleanup. |
| 2. Register semantic families | `type_model/data/families.php`, shared validators; preparation `families/catalog.php`; `load_runtime/family_adapter.php`; `collect_symbols`, `resolve_symbols`, `check_templates` | Add explicit exposure records and symbol-owner relationships without fabricated source ASTs. Resolve members from declaration owners. Check symbolic calls/requirements without Clang, including unused invalid templates. |
| 3. Prepare demanded instances | `instantiate` records/registry/workers/joins; `resolve_types/main_prepare_concrete.php`, `preparation_queue.php`, definition/signature consumers; compiler preparation adapter | Represent semantic applications before layout. Coalesce demands, schedule the existing native worker, validate results and materialize definitions/signatures through shared owners. No family-name branches or recursive tool calls from checking. |
| 4. Complete calls and lifetime handling | runtime callable import; `check_bodies/handlers/expressions.php` and checked values; `analyze_lifetimes`; backend/lowering argument preparation | Consume const scalar-address contracts through ordinary call handling, with typed temporaries where needed. Retain effects, exact evaluation order and lifecycle cleanup. No source-reference broadening. |
| 5. Prove the integrated path | integration fixtures, retained dependency selection, exports and navigation docs | Source vector and two-argument holder, ordinary runtime coexistence, symbolic forwarding and one body increment with added operation demand. |

The current model cannot accept this as another special-case imported callable:
member resolution requires a source record, prepared packages previously owned catalog
composition individually, and backend/link owners accepted only one package.
The coordinated migration removes those assumptions in their owners. It does not
move native generation into the compiler type model or source lifecycle into Clang.

Gate 1 is the smallest independently useful implementation checkpoint. Gates 2–5
complete source consumption; stopping at package composition alone would not deliver
this slice. Keep one common full/incremental selection algorithm and fixed worker
inputs; joins alone adopt private outputs. Original ASTs and previous accepted
snapshots remain unchanged.

## Acceptance evidence

- Native source-to-output proof: construction, append from an existing integer and
  an expression, scalar observation/echo, and exactly the required cleanup.
- A generic source function uses a local provider family with its declared `T`,
  instantiated with two supported scalar types. Its permission result is shared;
  native specializations remain distinct. This need not add borrowed opaque source
  parameters or owned source-function boundaries.
- Two ordered family parameters, a renamed family/method exposure and a second
  specialization use the same path. Shared primitive mappings preserve canonical
  identity; unrelated equal-layout types are not substituted.
- Scalar temporary evaluation is observable once; supported conversion precedes
  borrowing. Wrong argument type, const receiver mutation and unsupported effects
  fail in their semantic owners. Existing record/opaque borrowing remains correct.
- Full build then one ordinary body increment requests an additional operation.
  Preparation extends the same package, preserves specialization identity and
  existing operation symbols, and does not mutate the previous compiler snapshot.
  A new source body can demand new coverage without being a declaration edit.
- Repeated call sites and an already prepared superset require no duplicate
  generation. Select work before computing it; count actual native preparation.
- Missing, duplicate, stale and reordered worker results exercise joins. Busy
  readers, package changes before final lease acquisition, wrong target/ABI and
  rejected candidates must not publish invalid compiler output.
- Preserve prepared ordinary/full/ThinLTO feasibility. The first integrated compiler
  proof uses its supported ordinary link path; do not claim a new compiler LTO mode.

Run focused tests per gate and one complete compiler suite at consolidation, using
ten workers. The standalone preparation suites remain separate. Broaden/repeat only
for changed behavior or unresolved failures. Update exports, doc comments, process
maps and tracker status with actual results and remaining unsupported cases.

## Main risks and bounded fallbacks

The main risks are granting concrete-only permissions during symbolic checking,
confusing a semantic instance with a completed layout, duplicating primitive types,
retaining a read lease during coverage extension, and losing call-temporary lifetime
or operation effects. The gates above target those risks directly.

Keep template/provider contract changes on the existing conservative full-rebuild
path where finer invalidation is not proved. A source-body edit with changed operation
demand still participates in the one-increment proof. Do not add declaration-edit
incremental categories or repeated-failure recovery to satisfy this integration.

If another cross-owner reality is discovered beyond these boundaries, report the
specific limitation and smallest options before widening the implementation.

## Gate 1 implementation checkpoint

`Runtime_Import` selects independent package reads against one base catalog;
`Input_Join` accepts complete results and composes `Runtime_Input_Set`. The ordinary
single-package configuration uses this same path. Backend operation identities are
provider-scoped; normalized catalogs share explicitly mapped scalar definitions and
keep unrelated records distinct. Packages must use distinct provider namespaces
because generated native symbols use that namespace. Repeated paths/aliases of one
package are deduplicated; arbitrary partitions sharing a provider are unsupported.

The coordinator reserves every configured package before analysis and keeps leases
through linking, releasing all on success or failure. No preparation is invoked at
this gate. The earlier short-read/final-lease protocol remains work for demand-driven
preparation in gate 3. Changed sets conservatively select a full rebuild.

The [multiple-package proof](../../tests/integration/runtime_inputs.php)
covers ordinary execution, shared scalar mappings, same-layout record distinction,
repeated local operation IDs, alias deduplication, reordered/invalid results,
conflicting exposures/targets/providers, reservation cleanup, one body increment,
repeated CLI flags and external full/ThinLTO linking. The compiler still has no LTO
mode; these link probes validate artifact compatibility.

Validation: 93/95 fixtures passed in the ten-worker full run (79.5 seconds).
The remaining failures were test maintenance: the new integration fixture compared
a snapshot to a compile-result wrapper, and the interface inventory needed the new
join owner. Both corrected fixtures passed focused reruns; all 95 fixtures now have
passing results. Changed PHP files pass syntax, brace/doc-comment and whitespace
checks. No execution-performance claim is made by these correctness probes.

## Gate 2 implementation checkpoint

Family definitions now retain optional `language_type` and operations retain
`expose_as`, independently of native identities. Shared validation rejects ambiguous
member names, exposed lifecycle roles and unsupported requirement guarantees.
The preparation catalog exports typed language mappings; `Family_Adapter` produces
fixed source declarations and validates them against the compiler catalog.

The session accepts these declarations through `family_declarations`. Collection
assigns family/member symbol IDs in the existing store; members retain an owner and
have no fabricated AST or concrete ABI. Name resolution binds ordered type slots,
including two-argument families. Symbolic checking resolves members by the declared
receiver owner, substitutes semantic references, checks argument/receiver permissions
and retains exact dependencies. An operation's declared receiver can occupy any
signature position. No concrete specialization grants additional permissions.

The [source proof](../../tests/integration/provider_family_declarations.php)
checks unused definitions using one- and two-parameter families, renamed methods,
shared member spellings under different owners, constness, dependent result forwarding,
wrong formal identity/arity, missing mappings, stronger requirements and metadata
replacement invalidation. Independent permission workers join in reverse order.
An ordinary source function compiles to a native executable alongside these declarations;
one body increment reuses the declarations and permission results.

**Limits at the gate 2 checkpoint:** this proved registration and symbolic permissions,
not execution of native family calls. The type-demand and lifecycle portion below
now supersedes its preparation-not-implemented limitation when a bridge is configured.
Whole dependent-container copying, assignment and by-value boundaries are rejected
during definition checking, including unused templates. Local dependent-family
construction, whole-container lifecycle permissions, demanded
instance preparation and const scalar call storage remain upcoming work. Forwarding a
dependent provider application as bare generic T is rejected: its element baseline
does not establish the container's own copy/assignment guarantees. Nested native
arguments remain outside the bounded slice. No family-file CLI option or compiler-side
C++ catalog interpretation was added; the normalized session input is the current
boundary for the coordinator integration to consume.

Validation: the 96-fixture compiler run passed 95/96 in 78.8 seconds with ten workers.
The remaining storage test expected the old one-type-argument diagnostic; its focused
rerun passes with the general arity diagnostic. Subsequent focused provider and source
generic checks pass after tightening whole-container value-use rejection. The native
family preparation suite passed all 54 checks. PHP syntax, brace/doc-comment and
whitespace checks pass. No new native-family execution or performance claim is made.

## Gate 3 type-demand checkpoint

Semantic applications now bind ordered family arguments and allocate/reuse an
instance before storage exists. The type coordinator collects each newly introduced
family instance once, sends fixed batches to the optional `Family_Preparer`, accepts
complete private results through `Family_Preparation_Join`, and materializes measured opaque
storage through `Type_Cache`. Source records and imported opaque instances publish
the same type-readiness facts without sharing implementation ownership.

`families\Compiler_Bridge` connects the session API to the tested preparation tool.
It accepts explicit native catalogs/configuration, matches the registered definition
snapshot and canonical scalar mappings, and runs selection, private execution and
native join before publication. Compiler instance names are passed as typed adapter
bindings; generated packages retain their shared native identity and are not rewritten.
Current requests include declared lifecycle dependencies. An empty declared constructor
permits default construction of the family, including inside a generic function;
it grants no default-construction permission to the element `T` itself.

The session retains ordinary configured packages in `Input_Snapshot`, and demanded
package associations in `Type_Resolution`. Backend input composition includes both.
Early readers release before native preparation. Final reservation revalidates exact
accepted snapshots, rejects intervening replacement, and holds all readers through
linking. Native preparation's per-package cache publication may precede a later compiler
failure; accepted compiler snapshots/output remain protected. A configured bridge
uses normal stage selection even on unchanged source so native input freshness is checked.

The [type-demand proof](../../tests/integration/provider_family_types.php)
executes two distinct family definitions (one with two ordered parameters), repeated
occurrences, generic source forwarding, native construction and reverse cleanup,
and an ordinary package with its own opaque type and callable. One body increment
adds a scalar specialization, reuses unchanged package/type objects and drops unused
membership without mutating the old snapshot. It checks exports, reordered/missing/
duplicate/stale joins, busy readers, replacement before final reservation and lease
cleanup. External full/ThinLTO links execute the same lifecycle behavior; this remains
artifact compatibility evidence, not a compiler LTO mode or performance result.

**At the type-only checkpoint**, operation demands, compatible coverage replacement
and concrete signatures remained open. The method checkpoint below now covers those
paths with existing supported ABI modes. The gates 4–5 checkpoint below completes
const scalar-address import/call storage for operations such as copy-based append. Nested/provider/source-dependent arguments, container
copy/assignment and owned source boundaries were outside that checkpoint's scope.
Owned source results and native copy construction were subsequently integrated;
see [the owned-result implementation record](owned_source_results_plan.md).
Native copy assignment subsequently joined the same lifecycle preparation/import
path; the [append fixture](../../tests/integration/provider_family_append.php)
now verifies vector copying, assignment, self-assignment and independent contents.
Owned parameters and nested/provider/source-dependent arguments remain deferred.

Validation: all 97 compiler fixtures pass in one ten-worker run (89.1 seconds).
The standalone family and ordinary preparation suites pass 54 and 163 checks,
respectively. Changed PHP syntax, brace/doc-comment and whitespace checks pass.

## Gate 3 method-demand checkpoint

Concrete member lookup now follows declaration ownership for both source records
and imported family instances. Provider member contexts retain the declared method,
ordered arguments and exact receiver, without creating a source body. Once instance
discovery completes, `Family_Preparation::prepare_methods()` groups missing operations
by specialization and submits fixed tasks to the same native preparation service.
Repeated calls share demands; retained package coverage can satisfy later requests.

`package_bindings` carries internal type and operation exposure separately from
published JSON. The bridge maps native IDs; `Family_Preparation_Join` accepts complete
coverage and package membership, while `Family_Operations` checks the original ordered
semantic parameters, passing and result contracts. It does not infer permissions from
successful native preparation. Source definition checking still precedes specialization
and grants no concrete-only capabilities. Element-effect declarations remain on the
method's family contract; interior references are not exposed in this bounded slice.

At the same exact package/type/binding identity, unchanged normalized type contracts
reuse their definition objects. Existing equal callables retain identity as coverage
grows; changed artifacts produce a new package. Incompatible type revisions diagnose
instead of rebinding retained types. Full rebuilds start with fresh compiler preparation
associations, and native package reuse still goes through its own freshness checks.

`Callable_Inputs::external()` joins the semantic method context to its prepared runtime
callable. Signature selection, workers and joins use this fixed association, and
`Callable_Signature::receiver_index` records the declared semantic receiver position.
Body checking fills this slot once and checks explicit arguments in source order;
source methods use the same model with receiver position zero. Native physical
parameter order stays unchanged. Backend/lowering use their existing call path.

The [method proof](../../tests/integration/provider_family_methods.php)
executes ordinary and generic source calls, repeated calls, a two-parameter family
whose result refers to its second parameter, and a receiver between two explicit
arguments including a nested call. One body increment adds a method to the same
package, preserves prior type/callable objects and the previous snapshot, and links
and executes the new coverage. Missing or wrongly associated operation results are
rejected; exports retain receiver position and prepared operation coverage.

**Remaining at the gate 3 checkpoint:** const scalar-address import and call-scoped
storage for existing scalar places, scalar expressions and permitted conversions.
The gates 4–5 checkpoint below completes integrated copy-based append. No new source
reference syntax, mutable scalar borrowing, record temporary borrowing, owned source
boundaries or nested native arguments were added by this checkpoint.

Validation: all 98 compiler fixtures pass in the ten-worker run (86.2 seconds).
The strengthened final operation-membership join also passes the focused method
proof. Changed PHP syntax, brace/doc-comment and whitespace checks pass. The native
preparation suites were not repeated: their unchanged standalone core previously
passed 54 family and 163 ordinary checks; this change exercises the optional compiler
bridge through the integration fixtures. No execution-performance claim is made.

## Gates 4 and 5: const scalar borrowing and integrated append

The common imported-call path now accepts `const_address` for integer storage,
with borrowed ownership and call scope. `Callable_Import::call_parameter()` validates
the metadata; `prepare_backend\callable_parameter` validates the supported storage
contract and reuses the existing pointer ABI. Mutable integer addresses remain
unsupported. The runtime preparation generator already supplied this ABI and did
not need a separate compiler-specific bridge.

Body checking applies existing conversions and selects matching places as borrows.
Lifetime analysis retains its existing call-consumption and invalidation rules.
`Expression_Lowering::argument_storage()` reuses an existing address or stores the
already evaluated integer result into a typed temporary. That address has a separate
lowered identity; it does not replace the original scalar operand. Emission uses its
ordinary slot/store/borrow instructions. There are no vector/append/type-name cases.

The argument access ends at the call. Integer temporary slots require no destructor
and use function-entry stack allocation; their physical storage remains until function
exit and loop executions reuse it. No new lifetime intrinsic, escaping address or
source scalar-reference declaration is introduced. Existing object borrowing and
managed-result cleanup remain on their original common paths.

The [append proof](../../tests/integration/provider_family_append.php) uses
the real Simple C++ `scpp::vector_t` family and its prepared sequence adapters. Source
exposure and primitive mappings are explicit fixture metadata, including native size
mapping; compiler behavior does not depend on these names. It proves:

- Construction, copy-based append, scalar reads and cleanup alongside an ordinary
  package, including generic source forwarding to a second scalar specialization.
- Existing locals/projected fields keep their addresses. Literals, arithmetic, call
  results and permitted widening get independent typed storage. Observable call
  output occurs once; native address comparisons distinguish reuse from temporaries.
- One body increment changes execution and reuses prepared packages. The old snapshot
  stays unchanged. Independent lowering workers join in reverse completion order and
  reproduce the accepted result without mutating either input snapshot.
- Wrong argument types, source scalar-reference declarations and mutable scalar-address
  metadata fail in their respective owners.
- External O1, full LTO and ThinLTO links execute the same output. These prove artifact
  compatibility, not a new compiler mode or an execution-performance claim.

Together with the type/method proofs above, this closes gates 1–5 for the agreed
runtime-scalar subset, including two ordered family arguments and incremental coverage
extension. It does not add argument-taking family constructors, container value copying,
managed/source-dependent native arguments, nested containers, interior-reference APIs,
record temporary borrowing or a general constraint language. CLI family catalog loading
remains outside this session-API integration.

Validation: all **99 compiler fixtures pass**, ten workers, **88.6 seconds**. The focused
append proof also passes independently; changed PHP syntax, brace/doc-comment and
whitespace checks pass. Native preparation is exercised by integrated compilation;
the unchanged standalone suites were not rerun (last results: 54 family, 163 ordinary).

**Next after the append checkpoint:** consolidate the bounded provider-family
boundary. The review below completes that consolidation; owned source-function
boundaries (sequence step 7) are next for discussion. Managed/nested combinations
remain separate step 8 work.


## Bounded integration consolidation

The review covered semantic permissions and type readiness, native demand selection,
package import/composition, operation acceptance, scalar call storage, and retained
session/lease ownership. It found one acceptance gap and no need for a new cross-owner
model within the implemented runtime-scalar subset.

| Boundary | Owner and review conclusion |
|---|---|
| Definition permissions | `Family_Contracts`, `Family_Adapter` and `check_templates` keep formal guarantees independent of concrete native success. Stronger requirements and unsupported dependent uses reject before specialization. |
| Semantic versus physical readiness | `Concrete_Preparation` owns dependencies and canonical materialization; `Family_Preparation` requests native work. No placeholder layout or native tool call enters a semantic worker. |
| Required coverage | Missing methods select specializations for preparation. Each selected task now carries **all methods required by current callers**, so `Family_Preparation_Join` can reject incomplete replacement coverage before adoption. |
| Package retention and publication | The optional native bridge owns preparation/publication; compiler input joins own compatible composition. Exact type/callable contracts retain identity. Early readers release before replacement; final readers revalidate and remain through linking. Publication is per package, not atomic across packages. |
| Call storage and lifetime | Matching scalar places retain addresses; evaluated integer expressions/conversions get private lowering slots. Existing analysis owns access ends and effects. No scalar destructor or new escaping-reference permission appears. |

### Corrected acceptance gap

Previously, a method-coverage extension requested only missing operations. If a
preparation service returned the new operation but omitted an already prepared method
still used by this compilation, the join accepted it and signature association failed
later with an undefined-key error. The ordinary native bridge preserved coverage, so
normal execution proofs did not expose this boundary failure.

`Family_Preparation::prepare_methods()` now separates selection from required output:
missing coverage selects work; complete current demands define acceptance. Native
preparation still unions cached coverage and does not regenerate for repeated calls.
The existing join checks completeness, and accepted associations change only after
all selected results pass validation. No new process or mutable retained state was
needed.

The method fixture injects authentic prepared packages with one required operation
omitted. It reproduced the old late failure, then verifies early coverage rejection,
unchanged accepted associations and one selected specialization despite several active
instances. Its wrong-operation test still checks identity with otherwise complete
coverage. Navigation maps and stale sequence descriptions were reconciled.

The remaining unsupported cases are the stated scope boundaries: owned source-function
results/parameters, managed or nested native arguments, interior-reference APIs and
additional generic constraints. This review does not establish those capabilities or
native execution-performance bounds. Step 7 should first settle result construction,
caller ownership and cleanup on each return path; it does not require beginning general
native aggregate ABI support.

Validation: all six targeted fixtures have passing final results: semantic call
contracts, family declarations, type preparation, method preparation, real vector
append and runtime input composition. The first focused batch passed five; the method
fixture needed its existing wrong-operation case to supply complete coverage under
the strengthened contract, then passed its focused rerun. PHP syntax, brace/doc-comment
and whitespace checks pass. The previous 99/99 full-suite result remains the full
checkpoint; the full suite and unchanged standalone native suites were not repeated.

## Subsequent owned-result integration

The [owned-source result migration](owned_source_results_plan.md) now lets concrete
prepared family values cross source return boundaries through common caller storage.
Optional `move_construct` preparation uses the ordinary lifecycle bridge and does not
change definition-level generic permissions. The earlier gates above remain historical
proof scopes; owned parameters, temporary record borrowing, managed/nested family
arguments and generic whole-provider-value lifecycle permissions remain deferred.

## Nested native argument boundary

A concrete family specialization is an ordinary concrete type. Its origin and
ordered arguments remain provenance; checking, ownership, lowering and emission
consume the shared type contracts. Semantic identity and physical readiness are
separate facts, not separate downstream type systems.

Runtime type/family identities, requirements, operations and native bindings come
from metadata. No compiler branch recognizes a container name. The bounded extension
accepts catalog scalar types, prepared ordinary opaque runtime types and native
family specializations. Source-defined native arguments remain separate work.

### Preparation and import owners

- `families/Arguments` normalizes catalog and prepared arguments to shared `native_type`
  records. Exact provider/type identities survive package-local row allocation.
  It combines prerequisite declarations and headers without preparing dependencies.
- `Native_Types` emits self-contained descriptions in the authenticated package
  `native_types.json` artifact for ordinary runtime types and family specializations.
  The recipe retains exact identity, declarations, preparation context and dependency
  contracts. Operation coverage is not type identity or a
  dependency contract. Clang still discovers and fingerprints actual header inputs.
- `families/Compiler_Bridge` reads accepted recipes under short package leases before
  reserving outputs. All requests in a batch are selected before workers execute.
  It rediscovers type-only header dependencies and checks them against the accepted
  inner manifest. Exact header snapshots join the selected worker inputs; method-only
  coverage does not change those prerequisites. It supplies explicit compiler type
  imports alongside newly introduced type bindings.
- `Package_Adapter` validates an imported type's exact provider/type identity, measured
  storage, generic capabilities and target, then reuses its accepted definition.
  The import contributes no second lifecycle implementation or type declaration.
- `Family_Preparation_Join` requires imports to refer to exact concrete argument
  definitions. Final `Input_Join` requires compatible owners in the package closure
  and composes each exposed definition/lifecycle once.

The existing concrete readiness queue prepares inner types before outer types.
Accepted ordinary runtime packages and current family results are explicit inputs
to subsequent preparation batches in that same compilation. Workers do not recursively prepare types or mutate compiler
state. The required flow remains a full build followed by one incremental attempt.

### Native dependencies and reuse

Native declaration dependencies differ from wrapper-call dependencies. Clang sees
the actual native inner type when instantiating an outer family; it need not call
that inner package's exported wrappers. Native template implementation may appear in
multiple modules, so executable/LTO linking is part of the proof.

One stable package remains associated with each exact specialization and context.
Native recipes are independent of operation coverage. Adding an inner method does
not change the argument's type contract. Catalog/native-binding revisions are
conservative invalidation inputs; measured storage and header content still require
validation. This slice requires equal preparation contexts for nested native recipes;
combining different toolchains/include contexts is explicitly unsupported.

Source-owned type operations, incomplete-type support, escaping interior references,
owned source parameters and broader capability contracts remain outside this slice.

### Execution and rejection proof

[provider_family_nested.php](../../tests/integration/provider_family_nested.php)
uses the actual Simple C++ vector and a configured two-argument native family with
observable heap ownership. It covers nested append/read/copy/assignment, early and
normal owned returns, independent contents, balanced token allocation/release and
one body edit retaining exact prepared packages. Reversed family/lowering results
prove join ordering and snapshot purity. O1 and ThinLTO link the same accepted
modules; AddressSanitizer checks memory accesses. LeakSanitizer is disabled because
the development tracing harness prevents it from running; allocation counters are
an independent, bounded check of the test family's ownership.

Negative checks reject wrong imported identity/layout/target, missing owner bindings,
incomplete family batches, missing link dependency owners, ineligible arguments and
header changes after inner acceptance. Preparation selection separately proves that
a dependency-contract revision changes freshness without changing type identity.

Validation checkpoint: 102/102 compiler fixtures passed with ten workers in 114.1
seconds. Standalone preparation passed 163 ordinary and 54 family checks. All
prototype PHP syntax checks, touched-file brace/doc-comment checks and documentation
file-link checks passed. No body-checking, lifetime-analysis or LLVM-lowering code
changed for this slice.

The subsequent [closing boundary review](nested_family_consolidation.md) checks these
owners and scopes. Shared native recipe/header reads now occur once per fixed
preparation batch; each selected task still validates its own dependency snapshots.
No memo survives into retained compiler state.


## Ordinary managed runtime arguments

Ordinary opaque runtime types and prepared family types now share `native_type`
descriptions, native dependency validation and canonical imports. The coordinator
supplies the accepted ordinary packages to the preparation service. Its batch-local
owner index excludes imported rows, so importing an argument never creates another
owner or duplicate lifecycle implementation. No type-name dispatch or downstream
body/lifetime/lowering changes are needed.

[The runtime argument proof](../../tests/integration/provider_family_runtime_types.php)
uses actual `scpp::string_t` and a second metadata-defined heap-owning type in the
same native vector family. It checks independent copies, assignment, owned reads,
early/normal returns, observed allocation balance, one body increment, exact package
and type reuse, stale-header rejection and O1/ThinLTO linking.

This remains bounded to eligible native opaque types under matching preparation
contexts. Source-defined native arguments still require their separately agreed
source-operation import boundary. Generalized context merging remains unsupported.


Validation after the shared-description migration: 103/103 compiler fixtures passed
with ten workers in 142.5 seconds; standalone preparation passed 170 ordinary and
54 family checks. PHP syntax, changed-file brace/doc-comment, whitespace and local
documentation link checks passed. The ordinary argument fixture also rejects missing
declared copy eligibility, inconsistent description identity and mismatched native
contexts before generation.
