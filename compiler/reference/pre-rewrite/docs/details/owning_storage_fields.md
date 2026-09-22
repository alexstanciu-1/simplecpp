# Owning storage fields
Doc Status: supporting

Status: implemented after the approved coordinated migration. This extends
[typed element storage](typed_storage_plan.md), within foundations step 3.
The [source-written growing list](source_list_plan.md) now proves their composition.

## Ownership and representation

Runtime preparation explicitly advertises field eligibility. The compiler composes
copy capabilities and static resource paths from source fields. Raw allocation
descriptors remain noncopyable; an enclosing source custom copier can create fresh
ownership through supported field default initialization and its checked body. A resource location is
a local binding plus field ordinals; a whole local has an empty path. For
`$holder->buffer[$index]`, the allocation obligation belongs to `$holder->buffer`.
Element indexes never become descriptor identities. Nested records compose the
same field paths; distinct fields remain distinct obligations.

Object lifetime and allocation ownership remain separate. A constructor initializes
fields, then runs its source body. A destructor runs its source body, then reverse
field destruction. Source methods own their allocation policy; the compiler does
not add an allocation release. A borrowed receiver can return with owned fields.
Its containing object must discharge them before its complete destruction ends.
The mere presence of a destructor does not prove this.

Source methods currently require a local receiver. Projected runtime arguments,
nested field access and nested complete lifecycle are supported; calls such as
`$outer->field->method()` retain their existing diagnostic.

## Compact source contracts

Each borrowed parameter resource leaf has a required incoming state, a transition,
and flags for resource access and potentially borrow-invalidating mutation. Incoming states are empty or
owned. Two two-bit lanes represent possible outputs for each input state. This is
a field-local transfer relation, not enumeration of complete paths or combinations
of fields. Control-flow joins union lane bits; validation follows convergence.

Borrowed parameter analysis infers requirements. Locals have concrete states and cannot gain
caller preconditions. Conflicting local states and ambiguous accepted parameter
poststates are rejected. The analysis deliberately does not infer relationships
between integer conditions, element counts and ownership states. A caller must
satisfy every consumed field requirement.

Complete construction composes field initialization and the source constructor
summary. Complete destruction composes the source destructor and reverse nested
cleanup, then requires every remaining allocation leaf to be empty. These semantic
contracts also exist for records needing no executable destructor. Temporary
construction and destruction use the same summaries at full-expression boundaries.

Mutation checks compare ancestor/descendant paths as well as exact leaves. Pending
element borrows prevent replacement through runtime operations or source calls.
A borrowed owning object retains its address while its backing storage changes;
only allocation-backed element borrows pin that storage. Mutable borrowing checks
permissions along the entire field path.

`Resource_Aliasing` infers parameter-relative distinctness constraints from resource
access order. A read through one parameter after a possible mutation through another
requires distinct leaves. Multiple possible writers require distinct leaves too.
Calls map these exact paths onto caller locations, reject known overlap and propagate
unresolved incoming-parameter constraints. All preconditions use the pre-call state;
aliased leaves then receive the single writer's result once, so a const alias cannot
overwrite it with an identity transition. Later argument evaluation may replace
storage behind an earlier stable object borrow before the callee starts.

CFG work converges a finite set of previously mutated parameter leaves alongside
resource states. It does not enumerate execution paths or caller alias partitions. Mutation histories
contain parameter leaves only; the distinctness set can contain at most a quadratic
number of parameter-leaf pairs. Local resource counts do not enter that pair scan.
This is deliberately conservative: reading through a second alias after rebuilding
storage and multiple writers through aliases can be safe in specific programs, but
remain rejected when their distinctness requirements cannot be satisfied. Explicit
managed mutable parameters and general pointer alias analysis are still deferred.

Custom copy summaries have destination parameter 0 and const source parameter 1.
The destination begins in empty storage; its field plans select default initialization
for a custom copier or constituent copies for automatic composition. The source
must satisfy its inferred requirements and remain unchanged. Checked copy-local
initialization consumes this same complete contract and starts independent local
ownership. Ordinary const borrowed owning records use these parameter summaries
too; explicit mutable owning parameters and owner-valued function boundaries remain
deferred. Dependency selection follows the accepted field operation roles.

Assignment summaries also use destination 0 and const source 1, but start from a
live destination. Custom assignment consumes only its source body; automatic
assignment composes field assignment. Neither implies destruction/reconstruction.
Requirements and final deterministic states are checked, together with propagated
alias constraints. Allocation/copy failure remains fatal, without cleanup guarantees.

## Work, joins and incremental replacement

`Ownership_Preparation` builds a dependency graph of body and complete-lifecycle
requests. A ready batch captures immutable subjects and accepted dependency
summaries. `Ownership_Worker` produces private results; `Ownership_Join` validates
exact task provenance, parameter/field coverage, alias endpoints, const-source preservation and complete batch membership. The scheduler
only manages readiness and acceptance. Unsupported recursive summary dependencies
produce an explicit diagnostic.

Body identity, lifecycle definition identity and consumed summary identities govern
reuse. Equal semantic summaries retain their identity after a producer body edit;
provenance remains in the replacement result. Changed meaning selects dependent
caller work even when its checked body did not change. Access flags and distinctness
constraints participate in summary equality, including when final states are unchanged. Normal lifetime selection
and its join include the accepted ownership result. Deleted contributions are
excluded from the current set. Retained snapshots remain immutable.

The implementation executes serially with the same tasks intended for future
workers; it adds no actual threading. Required update scope remains one full build
and one incremental attempt.

## Flow and call application

Resource analysis keeps three distinct forms of data:

| Data | Scope and writes |
| --- | --- |
| Fixed locations and parameter membership | Indexed once for a checked body; read by both passes. |
| `resource_flow_state` | Resource relations plus preceding parameter mutations. Each block traversal clones its input; the solver replaces entry snapshots when a join changes their meaning. |
| `ownership_observations` | Validation-only requirements, accesses, mutations, exit relations and alias exclusions. Solving receives no observation owner. |

Both passes use the same checked statement/expression traversal. Worker scratch
records remain private; retained block-state exports and `ownership_summary` keep
their existing shape. The native representation can keep these records inline
with typed containers; no per-instruction observer or callback framework is needed.

A source call first maps every semantic parameter/path onto a caller location.
It checks all requirements against the state before the call, validates the
callee's alias exclusions, selects one writer for each aliased resource and checks
active element borrows. Only then does it apply the selected transitions and
accumulate preceding mutations. Parameter-map order is not callee execution order;
a read-only alias must not overwrite a writer's poststate.

The worker primitives have explicit effects: `require_state()` checks or infers states;
`record_access()` observes prior mutations; `check_mutation()` protects active
element borrows and records possible writes; `apply_transition()` changes flow
facts. Requirements never implicitly record accesses, and summary application
does not save/replace/restore worker-wide mutation history. Runtime effects retain
their declared transfer semantics through these same primitives.

`Resource_Locations` owns exact path projection, endpoint encoding/validation and
unordered alias-pair keys. Local roots and zero-based parameter positions remain
different domains. `Resource_States` owns only the finite relation algebra; named
state masks distinguish incoming requirements from two-lane output relations.
Analysis still avoids execution-path enumeration. Alias-pair count may grow
quadratically with resource-bearing parameter leaves; this refactor makes no
claim of a new asymptotic bound or measured performance improvement.

The [resource-contract proof](../../tests/04_analyze/analyze_lifetimes/resource_contracts.php)
compares all compact relations with independent finite-set composition and checks
canonical endpoints/projections. The [growing-list proof](../../tests/integration/growing_list.php)
also permutes dependency parameter/path maps, checks unchanged flow/summary meaning
and private inputs, and verifies equal-summary identity reuse through the join.
Existing native, borrow-invalidation and body-increment proofs remain the behavior gate.

Consolidation validation: all 92 fixtures passed with 10 jobs (87.9 seconds),
including PHP syntax checks. Focused comment/brace, navigation-reference and
documentation-link checks also passed. This timing describes the test run, not
a compiler-performance comparison.

## Code owners

Paths below are relative to `src/04_analyze/`.

| Owner | Responsibility |
| --- | --- |
| `type_model/data/definitions.php`, `resolve_types/record_definitions.php` | Shared static resource paths and explicit copy capabilities. |
| `check_bodies/handlers/places.php`, `handlers/expressions.php` | Projected argument access and complete-path permissions. |
| `analyze_lifetimes/data/ownership.php` | Locations, transitions, private flow/observation records, fixed tasks and accepted results. |
| `analyze_lifetimes/resource_states.php`, `resource_locations.php` | Transfer algebra, exact locations and path overlap. |
| `analyze_lifetimes/allocation_flow.php`, `resource_effects.php`, `resource_aliasing.php` | CFG convergence, source requirements, access order, aliases, calls, temporary and local obligations. |
| `analyze_lifetimes/ownership_worker.php` | Private body analysis and complete lifecycle composition. |
| `analyze_lifetimes/ownership_preparation.php`, `ownership_join.php` | Dependency-ready selection, acceptance and summary reuse. |
| `analyze_lifetimes/main_analyze_lifetimes.php`, `join.php` | Lifetime selection and acceptance against current ownership inputs. |

The existing lowering, native layout and LLVM lifecycle paths consume checked
locations and complete operations. No source implementation is duplicated in Clang.

## Proof and boundaries

[The integration proof](../../tests/integration/owning_storage_fields.php)
renames the metadata-defined storage family and exercises source construction,
buffer replacement, two independent fields, a nested source template and temporary
cleanup. A native observer verifies every allocation is released exactly once.
The emitted program runs at O0 and O1/ThinLTO. Tests cover private/reordered/missing/
stale results, stable-summary reuse, changed-summary caller invalidation, retained
snapshot purity, noncopyability, missing cleanup, transfer aliasing, const access,
borrow invalidation, safe disjoint-field mutation, early-return cleanup rejection
and rejected dependency cycles. The full suite passed 89/89 tests with 10 workers
(135 seconds), followed by focused checks after final contract/diagnostic tightening;
standalone runtime preparation passed 163 checks.

Deferred: arrays of allocation owners or dynamically selected owner subobjects,
owned source parameters, recursive ownership-summary inference, smart pointers
and exceptional cleanup. Later slices now prove [owned source results](owned_source_results_plan.md),
[managed storage/list elements](source_list_plan.md#managed-growing-list-proof) and
[runtime assignment metadata](lifecycle_contracts.md#copy-assignment). Existing plain
fixed arrays and scalar/plain-record storage elements remain supported. The indexed
owner representation remains under discussion; the [semantic direction](lifecycle_contracts.md#indexed-ownership-agreed-semantic-direction)
must not be mistaken for implemented analysis.
