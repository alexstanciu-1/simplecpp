# Analyze lifetimes call map
Doc Status: supporting

Caller: Phases::run_lifetimes(). body.php owns Lifetime_Worker with handlers/ for statements,
values and locals. flow.php owns definite initialization; allocation_flow.php owns explicit resource
flow. join.php accepts lifetime results; data/
owns contracts and retained sets.

The following are ordered lifecycle calls, not calls between siblings.

```text
Lifetime_Analyzer                                  main_analyze_lifetimes.php
  init() -> [action] establish phase readiness
  run() -> Ownership_Preparation::run()
        -> [action] select bodies against checked and ownership inputs
        -> Lifetime_Worker::analyze() [each task]
  finalize() -> Lifetime_Join::join()
  result() => Lifetime_Set
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

Value_Lifetimes distinguishes copied values from call-scoped borrowed access;
Local_Lifetimes distinguishes zero initialization, value writes, direct/copy
construction and assignment. Copy construction ends a copy_source borrow while
starting independent destination ownership. Assignment ends assignment_source
access while the destination remains live; it checks assignment permission
independently of initialization's copy permission.
Both consume shared lifetime contracts. Argument-access ends remain separate from
local scope/return ends and full-expression destruction obligations.

Cleanup extension: Value_Lifetimes tracks owned temporary construction order;
Statement_Lifetimes ends their full expression; Local_Lifetimes adds destruction
at existing scope/return ends. Analyzed_Body retains a flat ordered cleanup list
and validates it against established local/value lifetimes. Shared boundaries
require temporaries before locals and reverse construction order within each
group, using checked call order and local initialization statements.

Byte-span arguments use the same argument_borrow access boundary. Literal object
construction and output are ordinary checked calls, so the existing temporary
and scope cleanup paths apply without provider-specific rules.

Root/field/index places validate liveness of the containing local. Index operands
have explicit consumption ends, separately from the owning storage lifetime. Scalar field projections
never become independent cleanup owners; struct copies use the value-copy contract.

Ownership_Preparation selects dependency-ready body/lifecycle tasks, executes
Ownership_Worker::prepare(), and accepts each batch through Ownership_Join::join().
Complete lifecycles compose accepted source-body and nested-field summaries.
Ownership_Preparation selects child roles and orders dependencies from the shared
lifecycle_order, including semantic obligations without executable destructors.
Ownership_Worker consumes children in that accepted order and places the body using
body_before_members. Resource transition algebra stays in lifetime analysis; it does
not become part of the shared lifecycle-order contract.
Resource_Locations::parameters() supplies shared parameter/path coverage. Summaries
index transitions by parameter position; copy tracks destination and const source.
Allocation_Flow::source_construction_fields() consumes the complete copy summary for a local
initialization. Resource_Aliasing::apply_summary() maps source-call and assignment
parameters to caller locations, checks all preconditions and applies each aliased
resource writer once. record_access()/exclude() infer and discharge distinctness constraints;
stable object references are separate from allocation-backed element borrows.
Unchanged summary meaning retains identity; changed meaning invalidates dependants.
Cycles are diagnosed. The scheduler does no body analysis.

Allocation_Flow::analyze() converges sparse field-local transfer relations and
finite parameter-mutation histories, then validates entries, exits and expression
effects. Private resource_flow_state holds states and preceding mutations; block()
clones its fixed entry before traversing statement()/expression()/leave(). Solving
passes no observation owner. Only validation collects ownership_observations;
summary() turns those facts into the existing retained ownership_summary contract.
Ownership_Join validates alias endpoints as part of accepted summaries;
alias/access changes participate in dependent-work invalidation. Resource_Effects applies
native and source contracts; Resource_Locations owns descriptor paths and overlap.
Resource_States owns the two-state transfer algebra. Raw local allocation bodies
without source ownership dependencies run this same flow directly in Lifetime_Worker.
Other bodies consume the accepted ownership result without rerunning allocation flow.
Analyzed_Body and Lifetime_Join validate its exact provenance. Complete source
cleanup must prove discharge; object destruction alone is not evidence of release.

## Resource call application

These are methods composed on Allocation_Flow, not separately scheduled stages.

```text
Allocation_Flow::apply_summary()                    resource_aliasing.php
  -> map_summary() [parameter paths to caller locations]
  -> check_summary() [all requirements/accesses against unchanged pre-call facts]
     -> require_state(); record_access(); exclude(); check_mutation()
  -> apply_transition() [once per distinct resource, after checks]

Allocation_Flow::effect(); apply_fields()            resource_effects.php
  -> require_state(); record_access(); check_mutation(); apply_transition()
```

require_state() infers/checks ownership without recording access. record_access()
observes preceding mutations without changing them. apply_transition() commits
state and mutation history; it does no validation. Consequently callee parameter
ordering cannot invent cross-parameter execution order. Native transfer retains
its explicit source/destination semantics while using the same primitives.

Resource_Locations owns path projection, canonical parameter endpoints and alias
pair keys, shared by inference, lifecycle composition and join acceptance. Exact
keys remain independent of hashes. Resource_States names EMPTY/OWNED/EITHER masks
separately from the compact transfer relations. See the
[ownership model](../../../docs/details/owning_storage_fields.md#flow-and-call-application).

Const integer argument borrows use the existing `argument_borrow` consumption:
matching places must be live and initialized, and scalar expression/conversion values
stay live through the call. The end of this access does not destroy an owning local.
Lowering may materialize a cleanup-free scalar result into stack storage; this adds no
semantic owner or destructor. Allocation-backed borrow validity and call effects still
use the same analysis path.

Owned source results use `return_construct`, independently of local writes.
Statement_Lifetimes establishes return ownership before full-expression cleanup;
Analyzed_Body::validate_cleanups() excludes directly forwarded results from callee
obligations and still checks cleanup completeness/order for every remaining owner.

Allocation_Flow::statement() constructs result resource facts before local cleanup.
Its expression() reads accepted callee result fields; source_construction_fields()
applies the selected source contract and establishes a fresh destination.
Ownership_Preparation selects those callee/copy/move dependencies in fixed tasks.
Ownership_Join requires exact result-path coverage and deterministic empty/owned
poststates, separate from parameter transition relations.

## Source export verification

After ordinary lifetime analysis, backend `Export_Verification` binds declared
complete operations to current accepted body/lifetime records and ownership summaries.
It captures/selects `export_task` rows from `data/exports.php`; these are references
to fixed inputs, not a second body analysis.

```text
prepare_backend\Export_Verification::prepare()
  -> Export_Worker::prepare()                     export_worker.php [each selected operation]
     -> ownership() [complete resource effects versus the native profile]
  -> Export_Join::join()                          export_join.php
  => current export_verification records [retained separately from native contracts]
```

The worker checks construction/live input states, cleanup postconditions, const
source preservation and authorized alias exclusions. The join accepts exact selected
provenance, retains unchanged evidence and drops removed operations. Changed custom
body evidence cannot reuse verification merely because its signature stayed equal.
