# Resolve types call map
Doc Status: supporting

Compiler_Session runs entry preparation after collection, before name resolution. Type
resolution follows comparison. Type_Cache in utilities/type_cache.php owns candidate cache
preparation/materialization and early validity queries. signatures.php and locals.php own fixed
task selection and workers; signature_join.php and local_join.php accept their results in that
order. Record, signature and local batches are selected before materialization.
In Compiler_Session, each new record join is followed by the shared
Layout_Coordinator::prepare() for that batch before publishing record readiness.
Final backend preparation completes remaining layout roots through the same service.
The record join precedes annotation workers; local joins consume completed signatures. data/ owns worker requests and Type_Resolution. Shared definitions, type rows and
canonical storage belong to [type_model](../type_model/calls.md).

The following are ordered lifecycle calls, not calls between siblings.

```text
Entry_Resolver                                  main_prepare_entry.php
  init() -> [action] locate manifest entry symbol
  run() -> [action] validate top-level execution policy
  finalize() -> [action] complete entry_contract
  result() => entry_contract

Type_Resolver                                  main_resolve_types.php
  init() -> [action] validate candidate; select ordinary record tasks
  run() -> Concrete_Preparation::init(); run(); finalize(); result()
           [literals, ordinary declarations and demanded applications/records/members]
         -> Family_Preparation::prepare_methods() [one fixed operation frontier]
         -> [action] select concrete signature/local tasks; fix Definition_View
         -> Signature_Resolver::resolve(); Local_Type_Resolver::resolve() [each task]
  finalize() -> Signature_Join::join(); Local_Type_Join::join()
  result() => Type_Resolution / Type_Store

Concrete_Preparation                            main_prepare_concrete.php
  init() -> Template_Checker::init(); run(); finalize(); result() [definition permissions]
         -> [action] select literals and ordinary declaration roots
  run() -> Constant_Worker::resolve(); Constant_Join::join()
        -> admit_context(); schedule_record() [initial roots]
        -> [while ready] prepare_applications(); admit_introduced()
                         prepare_family_types(); prepare_records(); prepare_members(); admit_introduced()
        -> Instance_Store::snapshot() [one retained result]
  finalize() -> [action] release private selection state
  result() => Instance_Set

Preparation_Queue                               preparation_queue.php
  add()/wait_for() -> [action] index unavailable concrete prerequisites
  take_ready() => fixed application/record/member batch
  publish() -> [action] wake only dependents of the accepted fact
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

Signature_Resolver handles source annotations and imported provider declarations.
referenced_definition() resolves every imported signature name through the fixed
Definition_View after record acceptance. Signature_Join::matches_reference() checks
exact resolved definition identity against each ordered imported reference before
any signature materialization; structurally equal or cloned definitions do not match.
Signature_Join canonicalizes both through the same Type_Cache. Local type work
uses body_participates(); provider signatures have null syntax and no local work.

Type_Resolution indexes accepted language-bound signatures by role and canonical
type ID. language_callable() is a fixed-input query for checking; annotation lookup
rejects internal byte-span shapes as source storage/parameter/return types.

Type_Resolution also indexes accepted conversion signatures by purpose, canonical
source type and destination type. conversion_callable() reads this fixed index;
no per-worker shared cache or type materialization is introduced.

Record_Preparation (records.php) produces normalized declaration requests before annotations.
Record_Join (record_join.php) accepts source/provider results, then delegates canonical
materialization to Record_Definitions (record_definitions.php) in the private Type_Store.
Definition_View (definition_view.php) exposes those definitions alongside the provider
catalog. Type_Resolution::construction_type() performs read-only construction lookup.

Record_Preparation and Record_Join use the parser's struct/field accessors rather
than decoding child roles independently. The join checks the complete private
batch before materializing it. A source/provider name collision reports the source
declaration name; duplicate/stale worker results remain protocol errors.

`Parameter_Contracts::passing()` interprets explicit source const/mutable reference
annotations; `validate()` accepts const record and opaque-inline borrows, including
records with managed fields, and requires plain records for ordinary mutable reference parameters. Method
receivers have their own contract, including lifecycle bodies validated by Source_Lifecycle.
Signature workers and joins share these checks. `signature_representation` stores passing modes for source and provider
parameters, and interning includes them. `Parameter_Contracts` still rejects owned
record/opaque-inline source parameters. `Annotation_Types::definition()` resolves
source result types; `Type_Store::intern_signature()` records their result production
through `Result_Contracts`. Body checking selects owned-result construction.
Backend checks are not source diagnostics.
Parameter failures are anchored by `Parameter_Contracts::fail()` and use the shared
`diagnostics\Source_Error`; ordinary parameter validation does not call instantiation.

Source annotations arrive already bound in Resolution_Set. Annotation_Types follows
those declaration references through the accepted Definition_View; records,
signatures, locals and construction share this path. Representation checks remain
here. Template definitions participate only through demanded instance contexts;
Callable_Inputs supplies ordinary declarations and concrete instances to the shared
workers. Literal constants are prepared by [instantiate](../instantiate/calls.md).
Type_Resolution indexes signatures/locals by concrete callable ID and retains
definition provenance separately.

Concrete readiness belongs here; specialization workers remain in instantiate.
Each new context contributes bindings once. Waiting requests distinguish ordinary
nominal record readiness from context-scoped type applications. Ordinary and
specialized records share the queue and Record_Join. Ordinary nested fields also
contribute readiness dependencies. Each ready batch captures a fixed definition and
instance snapshot before workers run. Retained applications and members are validated before worker selection.
Workers read the private registry unchanged for their batch, then joins mutate it.
The final Instance_Set is immutable and separate from the working registry.

Record_Definitions and Type_Cache delegate aggregate lifecycle derivation to
Lifecycle_Composition (lifecycle_composition.php). It reads accepted constituent
contracts, selects direct field operations and retains compact fixed-array plans.
Field roles and ordering come from lifecycle_operation_kind::composition(); operation
lookup belongs to lifetime_contract::operation(). Resolution still owns capability
derivation and selection, with no backend or ownership-analysis dependency.
The type join owns canonical identities and materialization; the shared model
stores the resulting immutable contracts. Cyclic by-value dependencies cannot
become ready and produce a source diagnostic.

`Source_Lifecycle` (source_lifecycle.php) normalizes default/copy constructors,
destructors and assignment bodies to separate declaration IDs. It validates the
mutable receiver, void result and exact const source where the role requires one.
Concrete_Preparation::record_ready() schedules implicit lifecycle member demands
for accepted template records before downstream signature/body work, through the
existing member tasks and joins.

Storage_Definitions (storage.php) owns concrete typed-storage definitions and
role signatures. Materialization occurs at Instance_Join acceptance; readiness
only looks up the accepted type. Signature workers derive role parameters, and
Signature_Join validates exact family/element/type provenance before adoption.

Lifecycle_Composition derives construction, copy, assignment and cleanup independently.
Custom assignment has no automatic member plan; automatic assignment composes field
capabilities and retains the value-write path when all fields permit it.

Definition permission checks use the [check_templates lifecycle](../check_templates/calls.md).
Concrete preparation retains its accepted `Template_Set` in `Instance_Set`; all
application workers/joins see the same fixed acceptance view.

Native provider type demands share the semantic application registry. After argument
acceptance, `prepare_family_types()` submits one fixed frontier to
`load_runtime\Family_Preparation`, whose service executes native tasks and whose
`Family_Preparation_Join` validates the complete package/type batch. Only then does
`Type_Cache::materialize()` adopt measured opaque storage and lifecycle contracts.
`record_ready()` publishes the same type facts used by source records; it does not
compose a native implementation. `Type_Resolution::families` retains accepted
instance/package associations separately from the semantic `Instance_Set` and
exports their provenance. After instance discovery, `Family_Preparation::prepare_methods()`
coalesces missing operations and accepts coverage before signature selection.
`Callable_Inputs::external()` resolves ordinary imports and prepared methods to the
same runtime callable contract; original family symbols retain their ABI-free declarations.
Signature selection/join validation includes exact prepared callable identity.
`Callable_Signature::receiver_index` records semantic receiver position for both source
and imported methods, without changing the prepared physical parameter order.

Lifecycle_Composition::derive() retains the distinction between user lifecycle
declarations and generated field cleanup. It selects `expiring_construction`: custom
destructor/copy/assignment declarations use legal copy fallback; otherwise each field
supplies primitive behavior, an explicit prepared operation or missing support.
operation() retains each member's actual copy/move role in a compact complete plan.

Nested native family arguments wake through the same application/type readiness
facts. The preparation service sees previously accepted current family results;
no special container traversal or parallel type representation is introduced here.

## Portable source export identity

```text
explicit source export preparation
  -> Source_Identities::for_type()             source_identities.php
       -> [source] declaration(); [each argument] argument() -> for_type()
       -> [native] provider_keys()             one fixed-batch metadata index
  -> Lifecycle_Composition::complete_operation() lifecycle_composition.php
       -> operation()                          same complete field-composition owner
```

`data/export_identity.php` retains exact tagged components and their reversible key;
no AST or local numeric identity is serialized into portable keys. The projection
owner is temporary, bound to explicit `compile\native_project` configuration and
accepted semantic inputs. Backend preparation selects export roles/physical ABIs;
source resolution continues to own operation meaning and field composition.

## Source arguments at the concrete frontier

`Concrete_Preparation::prepare_family_types()` first delegates selected source arguments
to `Source_Export_Coordinator::tasks()` (backend preparation). It receives the current
canonical store, symbols and instance view, prepares ready layouts and fixed export
contracts, then returns family tasks carrying those accepted contracts. Semantic and
physical readiness still use the existing concrete queue. Method coverage requests
retain the same exports; no body worker runs Clang or native preparation.

Storage_Definitions::eligible() accepts supported value/constructor copying and
automatic cleanup for scalar, record and opaque runtime elements. Compiler-tracked
allocation owners (including record resource paths) remain rejected when indexed.
signature() and matches() share element_passing(): scalar inputs are values, object
inputs const-borrow live storage for copying. No default constructor is required.
