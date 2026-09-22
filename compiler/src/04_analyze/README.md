# Analysis order
Doc Status: supporting

1. collect_symbols/main_collect_symbols.php - collect and reconcile declarations.
2. resolve_types/main_prepare_entry.php - prepare the manifest-selected language entry contract.
3. resolve_symbols/main_resolve_symbols.php - bind declarations, annotations, template scopes, calls and local uses.
4. collect_symbols/main_compare_symbols.php - compare definitions/bodies; coordinator decides fallback.
5. resolve_types/main_resolve_types.php - prepare constants/records/instances; resolve concrete signatures and local types.
6. check_bodies/main_check_bodies.php - check operations and construct typed control flow.
7. analyze_lifetimes/main_analyze_lifetimes.php - establish initialization and lifetime facts.

Entry preparation belongs to resolve_types but runs before name resolution.
Comparison belongs to collect_symbols but runs after name resolution. Unsupported
increments repeat the same frontend work with full selection before proceeding.
The type-cache validity query also runs earlier, during input preparation.
Name resolution consumes the fixed catalog before concrete type preparation.
Type resolution runs [concrete preparation](resolve_types/calls.md) after selecting
ordinary records. Its initialization runs [definition permission checks](check_templates/calls.md)
before any concrete demand; [instantiation](instantiate/calls.md) supplies its specialization workers. Literal constants precede record extents; application, record and
member batches establish demanded prerequisites before concrete signatures/locals.
Accepted native family arguments feed a coordinator preparation frontier between
application and record work; measured package results join before their types become ready.
The coordinator owns this order; these processes do not call the next stage.

Call maps: [collection/comparison](collect_symbols/calls.md),
[names](resolve_symbols/calls.md), [template permissions](check_templates/calls.md), [types](resolve_types/calls.md),
[bodies](check_bodies/calls.md), [lifetimes](analyze_lifetimes/calls.md).
Overall: [compiler](../compile/calls.md).

Shared contracts live in [type_model](type_model/calls.md). It is a data owner,
not an additional phase: import and resolution produce its contracts; later
phases consume them.

Ownership preparation within `analyze_lifetimes` schedules source-body and complete
lifecycle summaries before dependent lifetime work. Static resource paths live in
the type definitions; entry requirements and transitions live in accepted analysis
results. See [owning storage fields](../../docs/details/owning_storage_fields.md).

During concrete preparation, accepted record batches use the backend-owned
Layout_Coordinator before their readiness is published. This target-layout work is
interleaved with dependency resolution; final backend preparation uses the same
accepted facts. Semantic workers do not run target probes or read a mutable layout cache.

`resolve_types/Source_Identities` projects exact portable source/provider/language
identities for selected native exports. `Lifecycle_Composition::complete_operation()`
uses the existing field-composition algorithm for exportable complete operations,
including primitive plans; it does not broaden source permissions. The export ABI
boundary remains backend-owned and is not an extra semantic compilation path.

For source arguments, the same ready frontier prepares accepted layouts and selected
source lifecycle exports through backend-owned coordinators before native work runs.
The family result binds payload parameters/results to those existing source types.
Method coverage retains the fixed exports; later body checking and lifetime analysis
use ordinary borrow and owned-result contracts.
