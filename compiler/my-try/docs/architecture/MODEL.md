# Retained compiler model
Doc Status: supporting

The compiler has one incremental path. A fresh build starts with empty retained
state and processes all work; later runs select affected work through the same
owners. `Model` holds static roots shared by Compiler instances. Workers are transient.

| Root/owner | Retained data |
| --- | --- |
| `Model::$modules` | Ordered keyed modules; each owns sources keyed by module-relative path |
| `source_record` | File input, current token/parse results, scan revision and pending change state |
| `parsed_file` | Typed file AST, scope owners, collection, completion state and token snapshot |
| `collected_file` | Canonical occurrences and semantic/generation handoffs for that source |
| `language_scope`, `namespaces`, `global_scope` | Language definitions; canonical namespaces own published member scopes; global_scope aliases the registry's global member scope |
| `runtime_callables`, `cpp_callable_bindings` | Installed free-function/static-factory identities/contracts and separate C++ bindings; reset with canonical types |
| `initialization_contracts` | Runtime initialization declarations, internal input types and concrete bindings; reset with canonical types |
| `indexing_contracts` | Registry-lifetime read/store/append contracts keyed by exact receiver definition; independent of construction opt-in |
| `iteration_contracts` | Registry-lifetime key/value signatures keyed by exact receiver definition; independent of bracket/initialization capability |
| AST/collected definitions | Specialized facts and retained declaration/body work |
| `prepared_files` | Successfully completed file records referencing retained syntax and the analysis-selected executable body |
| `entry_selection`, `prepared_entry` | Logical module/path configuration and the optional validated executable entry; not another source owner |
| `cpp_output_program` | Retained declaration/body fragments and cached text/includes |
| `cpp_files`, `llvm_files` | Published backend artifacts |

`Model::tokens()`, `syntax_files()` and `collected_files()` are snapshots derived
from source records, not parallel retained indexes. `Compiler_Lifecycle` owns root
initialization/reset; Model does not call processing code.

## Tagged boxed storage

The mixed and dynamic semantic roles remain distinct nominal definitions. Both are
registered as value-stored tagged bindings; dynamic's shared container identity is
a payload-copy contract, not class-like storage for every value. The registry's
normal modifier normalization therefore removes redundant `value<dynamic>`.
Construction, indexing and iteration contracts independently publish same-family
value types; their int/string key carrier does not confer cross-family containment.

## Source and symbol identity

Modules save their reconciliation key/name, logical name, declared path, resolved path and input position. Any
configuration/order change forces a full rebuild. Files save module-relative paths;
IO boundaries compute full paths. A source record observes its owning module.
The unnamed single module has logical output identity `main`; multiple modules
require explicit logical names. Those names do not introduce source namespaces.
`select_entry` invalidates prepared-program publication without replacing syntax or
work identities. `Program_Preparation` validates entry membership and declaration-only
non-entry sources after semantic work settles, before publishing a prepared program.
Standalone `prepare()` can analyze multiple sources without an entry; executable
entrypoints use `prepare(true)`. C++ never invokes preparation or selects an entry.

The parser reuses matching declaration nodes and their canonical collected identities.
Files own namespace-body occurrences, not flattened declarations or a second entry
body. Each occurrence owns its declarations and executable body. Current source
parsing constructs implicit global, semicolon and braced namespace occurrences.
Reconciliation matches namespace path and occurrence order, never moving a collected
definition into a different namespace. Reopened bodies keep distinct environments.
The guarded `single_body()` query supports explicitly single-body clients and parked
LLVM; general analysis and C++ generation traverse the body inventory. Analysis
publishes the sole executable body (or the first empty body for declaration-only
files) in `prepared_file`. More than one nonempty body rejects before emission.
Failed preparation clears that selection so a saved result cannot bypass admission.
Previous lexical scopes remain in the collection's retirement inventory until the
successful whole-parse join has retired semantic work and removed frontend entries.
Failed parsing retains old and partial body/scope identities for retry. Namespace
interning and declaration publication use the existing synchronized parser protocol.

Only applicable name-bearing syntax has an occurrence backlink. Retained names are
independent of token indexes. Duplicate names remain distinct candidates. The
collector registers symbols during parsing; there is no collection pass or second
symbol model. See [analysis](../../04_analyze/README.md) for typed collected roles.

Function signatures and bodies have separate work identities. Fields/parameters
settle with their definition; locals settle with their body. A body is a processing
unit, not a symbol. Replacing body syntax transfers its retained work identity.
Work records explicitly observe the selected executable body rather than deriving
it from the source file; dirty-state queries and C++ body rendering share that query.
Unchanged bodies retain nodes, scopes, occurrences and facts. Parsing does not
mutate preparation-work state: declaration change flags and body `syntax_changed`
persist until analysis successfully consumes them. Repeated parsing cannot clear
pending source changes. Read-only source-change queries also guard saved prepared
results against premature emission.

Member-owning types additionally retain `type_relationship_work`, independently of
their ordinary declaration/layout work. It owns direct ancestry facts and shares the
existing scheduler, dependency versions, failures and retirement. Its acknowledged
parse revision is independent of the declaration dirty flag: settling relationships
never settles layout. Equivalent facts preserve their version. Record preparation
requires relationships; ancestry queries never require record layout or method bodies.
The analysis-only relationship checkpoint does not publish executable prepared files.

`type_contract_work` shares the independent `type_semantic_work` revision/lifecycle
base. It depends on record layout, local/inherited signatures and base contracts;
none of those earlier facts depend back on it. `prepared_type_contract` holds local
method dispatch decisions and outstanding original abstract declarations, not copied
inherited inventories. Backend assembly reads complete contracts alongside cached
layout/signature fragments. Bodyless methods have signature work only. A transition
to bodyless syntax retains the old body in `retired_bodies` until analysis retires its
work and clears facts, then frontend disposal releases it. Parsing does not schedule
analysis or manufacture replacement bodies.

Interfaces now use that same contract work directly, without record-layout work or
record preparation facts. `interface_requirements` references original interface
methods (deduplicated by identity), including satisfied requirements needed by
descendants. `forwarders` contains prepared method decisions observing an inherited
implementation and its immediate dispatch base; these are not collected methods or
AST bodies. Signatures remain shared. C++ assembles direct base/interface spelling
from current relationship facts, outside cached layout fragments, so relationship-only
edits cannot leave stale header inheritance. Bare interfaces and classes share the
`is_nominal_handle` storage query; interface identity still confers no record layout.

`collected_trait` preserves an authored composition source as another member owner,
with a distinct nominal kind and no value capabilities. Simple `use_traits` syntax
owns named references, not resolved or cloned members. Consumer-owned independent
member instances are [approved but not materialized yet](../planning/trait_member_instances.md).
Execution remains gated rather than attaching consumer-specific facts to origin nodes.
Inherited lookup observes each consulted category/name pool and returns original
declarations plus their owner, without an effective-member cache. Implements edges
are contracts, not fallback implementations. Concrete records may retain one
`base_type` for their inline base subobject; layout completion depends on the base's
layout, supported construction and copy contract. C++ reads its bare payload mapping.
Declared inventories remain local; static references retain original storage ownership.
Protected access is analysis-owned and receiver-aware. Interface calls share method
selection and receiver retention. A call-only fallback admits outstanding interface
requirements inside abstract bodies without polluting implementation lookup.
The effective `prepared_record::constructor` may observe the original ancestor method;
the local method inventory is unchanged. Inherited construction observes the ancestor
signature through ordinary dependencies. Authored constructor bodies own optional
`prepared_base_initialization` facts on their function body; they contain the inline
base type, ordinary admitted call and optional weak source-call observer. Cleanup clears
these with the body. The leading parent statement remains in source syntax but is
consumed as initialization, with no receiver available during argument preparation.
C++ caches the entire initializer list with the body fragment, not the public signature.
Braced base initialization preserves left-to-right argument boundaries without copying
a temporary base. Explicit parent instance calls retain a current-instance dispatch fact
and original target; no fabricated `$this` AST or inherited-member copies are needed.
Parameters own optional default expressions; normalized scalar/enum default snapshots
belong to their callable contracts and participate in signature equivalence. Calls
retain only explicit arguments; emission supplies omitted defaults from the signature.
Variadic syntax marks one trailing parameter independently of passing mode; its
prepared element type is distinct from its ordinary vector storage type. Prepared
calls retain the fixed-prefix/pack boundary without rewriting source arguments.

### Callable contracts

Source functions and methods share `collected_source_callable`; final
`collected_function` and `collected_method` preserve distinct lookup roles. Members
observe their declaring type and are indexed separately from free functions and
properties. Common signature/body work and `prepared_source_callable` use the
shared callable syntax accessor. The common AST owns attached signature facts.
Selection, reset and retirement cover both roles without a second member lifecycle.
Constructors and bounded instance methods use this lifecycle. `call_node` owns an
optional receiver and shares `collected_call_reference`/`call_references` with free
calls. `prepared_record` indexes collected member identities; member signatures own
visibility and contracts. Member-set changes invalidate lookup consumers without
making a record depend on its methods' signatures. Prepared calls retain an optional
receiver-lifetime decision (shared retention, inline borrowing or current instance).
The existing reference-cycle analysis includes methods without another retained graph.
Record syntax changes describe property/modifier/member membership independently of
method contents. Each method's signature and body retain their own change flags.
This prevents body-only edits from rebuilding record defaults and invalidating callers.

`callable_definition_i` is the ordinary lookup identity role. Its fixed-signature
child `callable_target_i` is implemented by `collected_source_callable` identities
and by `registered_function` and `registered_static_factory`. Their `signature()` accessor only reads attached
contracts. `Call_Preparation::require_signature` completes source declarations and
registers the consumer dependency; installed runtime targets already own a complete
contract. Runtime catalogs do not cache source declaration facts. Registered factory
keys are provider-qualified semantic identities, independent of C++ names; their
owner is the exact canonical constructed type and equals their return type.

Free functions have provider-qualified keys and source names, but no fabricated
receiver, AST or construction owner. Scope `runtime_functions` indexes catalog-owned
identities; `functions` remains the source-only retirement inventory. `callables_named`
combines them with language-scope-owned `compiler_function_definition` identities
for ordinary nearest-scope lookup and analysis lookup observations.
Installed pools are immutable under retained consumers. Runtime overload selection
admits exactly matching fixed, required, by-value parameter signatures; it is not
general conversion ranking. JSON is the first installed free-function provider.

Compiler definitions describe an immutable operation kind, not a preselected
signature. `Compiler_Call_Preparation` selects argument-dependent contracts and
uses common `Call_Preparation::admit`; `prepared_call` weakly references the
definition and owns the concrete signature plus optional specialization facts.
`prepared_enum_call` shares the settled enum snapshot after analysis registers
its declaration dependency. No new AST node, runtime registry entry per enum,
general template cache or earlier-stage backend callback is required.
C++ owns helper spelling/emission. Cached fragments retain read-only helper
requests; assembly deduplicates requests from live fragments into separate helper
headers/translation units, keeping string metadata out of enum type headers.
The same facts describe name, numeric-value and checked reverse-conversion helpers.
`Type::class` uses existing static-member syntax and is resolved as a compile-time
input by analysis. Each admitted runtime argument records its original source
position; the common emitter follows that projection and never emits type markers.
Concrete call signatures describe runtime parameters only. No dummy string/value
parameter or general reflection type is manufactured for compile-time inputs.

`callable_signature` and `callable_parameter` have no source-storage links.

Shared upcast conversion decisions retain canonical source/target types and a named
operation, never a worker or ancestry cache. The optional transient
`conversion_relationship_query` is supplied by storage-boundary preparation;
`Conversion_Relationships` observes the existing relationship work through the
consumer's preparation context. Pure scalar conversion callers need no such query.
Without one, nonidentity ancestry conversions remain rejected. C++ consumes the
decision and requires complete source/target headers for the runtime conversion.

Parameter `storage_access` is independent of `passing_mode` and canonical type
identity. Syntax records explicit const access; declaration preparation publishes it
in the shared parameter contract and signature equivalence observes changes.
`prepared_source_callable` owns the signature plus its source parameter facts;
`prepared_parameter` links its collected storage identity to the same parameter
contract (no duplicated type/mode/default state). `prepared_storage::storage_type()`
provides the common local/field/parameter storage query. Contracts are populated
before publication and treated as immutable afterward, like other prepared facts.

An optional `reference_return_contract` identifies a whole reference parameter by
its immutable index; absence denotes a value return. Structural validation requires
an exact-type fixed reference parameter. This is a symbolic contract, not a source
body proof or a caller lifetime. Signature comparison includes it. Source signatures
derive it from exactly one fixed reference parameter; body preparation verifies each
return against that whole parameter independently of statement position. Structured
completion checks missing returns. Calls substitute the actual argument's place.
Runtime reference-return execution remains gated; initialization factories require
value returns. Nonrecursive forwarding reuses the same argument-place substitution
and per-return whole-parameter proof, including local aliases.

After all bodies settle, `Reference_Cycles` builds an invocation-local graph from
current collected function/call indexes and prepared call targets. Its transient
`reference_cycle_vertex` records use integer edges, not mutually owning handles.
Strongly connected components containing reference-returning functions reject through
the worker's normal failure/retry protocol before prepared-file publication. By-value
recursive components remain allowed. This linear check includes unchanged bodies and
is rerun for incremental updates; it does not re-prepare bodies, retain a second graph,
or turn body relationships into declaration dependencies. Interface requirement
vertices link to original settled implementation identities, including inherited
forwarders; bodyless contracts never receive body-failure state.

Expression facts carry optional `prepared_place`, with `addressable()` derived from
presence. Places select original local declarations or parameters and may project
through direct stable struct fields. `root_storage()` preserves that identity;
parameter facts distinguish value and reference origins. Values, indexed reads and
temporary-derived fields have no place. Place queries do not decide whether a borrow
may escape; that analysis belongs to workers. See [ownership](ownership.md).

`prepared_place::access()` queries the root parameter contract, so native aliases
and field projections preserve read-only access without a second const-type identity
or cached mutability flag. Addressability remains independent. Analysis-owned
`Reference_Preparation::require_writable` is used by assignments, mutations, indexed
stores/removal, resets, moves, take outputs, iteration reuse and mutable-reference
call boundaries. Read-only reference arguments use the same exact/stable-place
admission but do not require writable access. C++ reads settled access for parameter,
argument-reference and alias qualifiers; it never decides source mutation admission.

`prepared_binding::alias_target` retains a shared original place for an alias
declaration. Alias reads keep the alias's own collected identity for naming but
select that original place; alias chains cannot manufacture a new root. The existing
assignment and explicit declaration nodes record `binds_reference`, and function
syntax records `returns_reference`. `Reference_Preparation` admits these forms and
checks the transient body obligation. Verified returns retain `reference_target`
instead of a value conversion. C++ call sequencing preserves `T&` in its lambda
return type; ordinary value destinations still copy normally.

`prepared_call` weakly references the selected target and shares its completed
signature. `Call_Preparation` admits arguments independently of target selection;
`CPP_Declarations::selected_call` sequences their lowering. `CPP_Callables::reference`
owns target dispatch entirely inside the backend; callable identities and their
interface expose no C++ operation. Dispatch uses source mangling or a separately
registered free-symbol or static-member binding, with exact target
identity checks and header collection. Bindings support direct static members and
adapter templates applied to the exact owner type. Initialization instantiates semantic
factory identities independently; backend-only definition metadata selects an adapter
and lazily installs concrete C++ callable bindings during emission. Method lookup and
closures remain deferred. No adapter spelling lives in semantic facts.

Callable roots survive preparation reset, but syntax/type reset replaces them
together. Installed contracts/bindings must not be changed under retained consumers;
configuration changes require a full compilation reset.

### Initialization contracts

Public class/struct property defaults retain the common `prepared_property_default`
typed boundary. Constant defaults own immutable value snapshots; construction defaults
observe their property-owned data-construction syntax and reuse its attached facts.
Shared constant evaluation admits leaves; ordinary construction preparation owns typing,
conversions and nested targets. No second expression evaluator or container-family
algorithm is introduced. Record work owns dependencies; construction-default rebuilds
conservatively republish facts rather than comparing mutable AST attachments as snapshots.
C++ emits per-instance member initializers through existing construction lowering.

Explicit `data` declarations retain their opt-in separately from construction contracts.
Without an authored constructor, the record owns a synthesized
`record_initialization_contract`; otherwise the prepared source callable owns it.
Named `record_initialization_input` records share ordinary `callable_parameter`
contracts. Synthesized inputs additionally observe the selected public property;
authored contracts weakly observe their member identity. Source `set:` parameter
facts observe their initialized property separately from the input-name schema.
These heterogeneous inputs are not a runtime sequence binding or a registry cache.
The common construction AST retains either
`prepared_sequence_construction` (runtime factory binding) or
`prepared_record_construction` (source input contract, payload and storage). Record
entries retain their resolved input position and ordinary typed boundary. Analysis
checks opt-in, literal names, uniqueness, conversions and required omissions; C++ emits
ordered input snapshots and either an authored constructor invocation or a tagged
synthesized constructor. Both initialize members directly. Supplied `set:`/synthesized
properties never execute their defaults. Default facts distinguish existing literal
function defaults, typed constant/construction defaults, and synthesized type defaults.
General methods and source sequence-factory execution remain gated.

`prepared_receiver` is constructor-owned storage, not a fabricated local or source
parameter. The invocation-local body context observes it and its declaring record.
Only direct `$this` property access is admitted; projected storage is writable but
non-borrowable. Preparation checks private access against the declaring record.
No receiver copy, return, rebinding or escape is enabled by this representation.

`contract_type_reference` under `compiler/types/` represents target, template-argument
or concrete-type references for both initialization and index signatures.
`Contract_Type_Substitution` owns the shared analysis-time substitution; this is not
a C++ spelling or a second canonical type identity.

`initialization_declaration` names a registered target definition, type references
(target, template argument or concrete type), exact argument constraints and a semantic
factory key. Optional key type distinguishes the two recognized interface roles.
`initialization_binding` stores the concrete target/value/key/nested-target arguments
and shares a callable identity; it does not duplicate the callable signature.
`Initialization_Preparation` owns selection, substitution and signature validation.
Vector/hash/mixed/dynamic are declarations, not separate preparation algorithms.

`Initialization_Contracts` owns runtime declarations and concrete bindings for one
registry lifetime. It also owns internal ordinary `initializer_list`/`initializer_entry`
definition identities; read-only queries identify the call-scoped view and immutable
input records independently of spelling. Views declare no owning copy/storage
capability. Both input templates require copyable storable elements; entry reads and
source lifetime enforcement remain deferred, so neither type is source-exposed.

Capability checks use analysis completion/dependency queries before every binding
reuse, including nested source-record changes. This catalog never caches source
interface facts. Installed declarations freeze once a binding is published; config
changes require reset. Preparation reset keeps these roots, syntax/type reset replaces
them. No worker or backend spelling is retained. See the [Chapter 06 checkpoint](../catalog/06_containers.md#initialization-binding-and-input-model--2026-10-05).

Construction syntax owns a specialized `prepared_data_construction` with its selected
sequence binding or record contract and inherited result type. Each existing entry owns `prepared_data_entry` conversions;
no prepared entry inventory duplicates syntax order. The expected construction type
is a transient explicit argument from a typed consumer, not mutable ambient context
or another retained type field. Explicit target syntax takes precedence. Cleanup and
incremental re-preparation follow the ordinary attached-fact lifecycle. The backend
consumes these facts for built-in construction execution; source input-view lifetime
operations remain gated.

## AST and scopes

Shared declaration syntax is defined in `structures_declarations.php`: one concrete
type-declaration shape and separate typed member owners; common callable and constant
abstracts supply reusable syntax. Existing struct/function/namespace-constant semantic
paths share `collected_record` for plain classes/structs. `collected_member_owner`
is the common abstract for records and distinct `collected_interface` identities.
Methods expose their original `declaring_type()`; constants have an optional weak
`member_owner`. Interface registration uses the existing nominal source index,
without record layout/capabilities or source execution. Declared inventories remain
local; future inherited lookup must return original declarations, not copied members.
Prepared fields/callables/constants use `member_visibility` (public/protected/private).
Analysis owns access decisions; protected execution remains gated until ancestry
facts exist. Backend spelling reads the enum without resolving relationships.
See [declaration model](../planning/type_declarations.md#plain-record-execution--2026-10-07).

`object_construction_node` owns the constructed type syntax and ordered arguments.
Parsing records those edges without ownership decisions. `Object_Construction_Preparation`
resolves the type, proves the bounded field-default contract with declaration dependencies,
and selects declaration-directed or direct-local contextual storage.
`prepared_object_construction` retains the constructed payload, result type and
`record_storage_kind`. Bare class uses shared storage; bare struct uses inline storage.
Redundant `shared<Class>`/`value<Struct>` reject. C++ payload naming is separate
from stored-value naming. The shared `expression_value_origin` fact marks proven fresh
construction separately from ordinary values; its query does not rely on subtype dispatch.
This distinguishes new construction from copying;
the shared value-boundary decision uses that query only for matching storage.
It does not grant new type capabilities or contextual construction outside explicit locals.
The backend chooses the runtime allocation helper from the prepared ownership decision.
Authored constructor arguments retain an ordinary `prepared_call`, whose signature
describes initialization (internal void result). The construction expression separately
owns its result/storage type. Ordered call lowering receives the backend-selected
allocation spelling; it does not treat a constructor as a free-function symbol.
Overloads and runtime value construction remain outside this slice.

`move_expression_node` owns one operand. Its `prepared_move` retains the selected
source place but has no result place: extraction produces an owned temporary, not
a borrow. The shared expression origin distinguishes explicit transfer from fresh
construction and ordinary values. Initialization can consume an exact owned temporary
without changing the type's copy capability. `Move_Preparation` checks unique whole
storage and the immediate `expression_consumer`; consumer admission is an explicit
transient argument and never leaks into child expressions. Local destination aliases
are checked through the existing original-place query. C++ materializes an exact
handle from `std::move(source)`, rather than publishing an rvalue reference or adding
source-derived semantic checks. Cleanup drops the facts and the shared source-place
reference through ordinary node maintenance.

`Value_Boundary_Preparation` selects copy, owned-temporary consumption, reference
binding or empty construction independently of conversion. Local/member binding,
call-argument and return facts retain that operation. Copy capability checks remain
in `Capability_Preparation`; conversion facts still own type adaptation. Fixed
value arguments and value returns supply immediate consuming context; reference
arguments do not. Unique-valued calls publish `owned_call_result`, while reference
calls preserve their source place and ordinary origin. No AST rewriting or type-name
test is used to distinguish them. The C++ call sequencer reads argument boundary
facts to move owning temporaries into parameters, never referenced source storage.
Returns already emit owned prvalues directly, without adding `std::move` to prvalues.
Field transfer, unique variadic packing and contextual `new` expansion remain gated.

`ownership_observation_node` owns one operand and its derive-weak/lock-shared operation.
`Ownership_Observation_Preparation` validates the operand's definition-owned role and
interns the opposite handle family with the exact same payload identity. Attached
`prepared_ownership_observation` carries the admitted operation and inherited result
type, without a place, retained worker or extra syntax inventory. Ordinary copyable
handle boundaries apply; neither operation is fresh pointee construction. Cleanup
uses the owning operand edge. C++ emits one runtime derivation call or one lock,
without duplicating the operand, copying the pointee or retaining a hidden strong owner.
This does not add generic callable instantiation or source member-call dispatch.

`index_node` retains `prepared_index_expression` with a normal `operator_decision`
and inherited non-addressable value result. `Indexing_Contracts` supplies exact
receiver-definition signatures; `Indexing_Preparation` resolves key/result types,
checks conversions/copy capability and selects checked-access or null-on-miss reads.
One CPP handler sequences receiver/key and calls the selected runtime helper. Stable
receiver wrappers are locally const-bound (not copied wholesale), temporary receivers
remain alive through result copying, and no interior reference is exposed to source.
Writes and reference binding do not follow from the node's syntactic assignable base.
Cleanup and incremental preparation own these facts through the existing node lifecycle.

`prepared_assignment_expression` shares assignment value-result facts and the
`destination_type()` query: ordinary assignments read their binding's storage type,
while indexed writes read their prepared value-conversion target. This meaningful
abstract query also makes the assignment fact family natively polymorphic for
checked specialization access; it is not a stage-processing hook. Its storage
specialization retains `prepared_binding`; `prepared_index_assignment` instead owns
a selected store/append decision and element conversion. Registry signatures bind
key, value, nested-literal target and optional write-path element type for bracket operations.
Writes require stable local/parameter root storage, not an exposed interior reference. One CPP
write handler emits ordered receiver/key/value temporaries and a runtime operation;
statement writes use a plain scope, value-producing writes a result lambda. Runtime
overloads own storage details, bounds, null bootstrap, COW and shared identity.

Nested assignments additionally retain an ordered `Storage<prepared_index_path_step>`
and the existing root syntax reference. Each step observes its bracket node and owns
key conversion/traversal-result facts, not a copied value-read decision or a worker.
The registry's traversal result is independent of its read result (dynamic traversal
remains dynamic, while its reads return mixed). Analysis owns root-to-leaf preparation;
the CPP worker consumes the settled path. Cleanup drops the assignment-owned plan and
ordinary syntax traversal clears root/key/RHS facts. No extra AST shape is introduced.
After all keys/RHS are evaluated, emission probes the path in a read-only scope and
then descends for mutation. Probe references expire before COW/autovivification;
mutation references never cross source calls. Runtime overloads implement these
steps without a variadic path-template framework or copying the entire root.

`prepared_keyed_path` is the common non-creating recipe for keyed probes/removal.
`prepared_value_probe` optionally owns it; an `unset_node` owns it through
`prepared_keyed_removal`. Ordered steps observe existing bracket nodes and retain key conversions
and canonical element types. Runtime operation selection remains in stage workers,
not in these structures. Root/key syntax facts follow ordinary cleanup/invalidation.
Registry probe/removal opt-ins do not follow implicitly from write capability.

`cleanup_node` shares the single target edge, maintenance and fact slot between
final unset/clean statements. `Cleanup_Preparation` owns their distinct admission.
Its alternative `prepared_storage_reset` retains only the exact admitted reset type;
target syntax already owns the resolved variable/place. Reset facts do not retain
environments or workers, duplicate provenance, or grant arbitrary default construction.
Emission uses exact-type default assignment; aliases stay bound to the same storage.

The CPP worker emits lazy key evaluation with prefix-resolver closures capturing only
the bound root and saved keys. Resolvers acquire interior pointers only during one
lookup call; none survives a source key expression. Every reached prefix is checked
again after key effects. Missing paths skip later keys, and removal only descends
mutably after confirming an existing final slot. Leaf probes call the established
PHP runtime presence/emptiness helpers, including wrapper semantics; ordinary throwing
reads and legacy keyed membership shortcuts are not reused as probes.

The property-free `ast_node` base supplies structural contracts; specialized node
families own typed syntax fields and uint32 spans. `statement_body_node` shares
ordered body storage/traversal between executable roots and nested blocks.
Conditional arms own explicit body/next-arm edges. The common `loop_node` owns a block
body; concrete while/do-while/for/foreach nodes own typed headers. Foreach owns an
iterable expression, optional key and required value syntax. Its loop-owned
`prepared_iteration` holds the selected registry contract, substituted types and
ordinary binding/conversion facts. `Iteration_Preparation` owns admission and lexical
binding; the loop worker still owns body/transfer traversal. C++ generation borrows
the once-evaluated source, extends temporary lifetime and consumes const entry views
through a shared checked runtime adapter. It never snapshots the collection.
Mutation/replacement semantics remain unsupported; CH06-ITER-01 now blocks final
catalog completion under [Chapter 15](../catalog/15_blocking_debt.md). Reference
foreach stays disabled pending the runtime safety foundation tracked there as
CH06-REF-01. Break/continue facts observe
their prepared enclosing target; transient completion composes four exit possibilities
without a CFG. Conditional expressions own condition, optional true branch and
fallback; absence of the true branch represents shorthand without duplicating the
condition edge. Prepared facts retain truthiness and the value-result join.
Binary logical expressions retain ordered `operand_truthiness` decisions on
`operator_decision` for condition-based AND/OR. These replace operand conversions
for that operation family; the emitter consumes the decisions without type discovery.
`value_probe_node` owns one operand and an `isset`/`empty` enum kind. Prepared probe
facts retain the admitted operation and boolean value type; workers own admission
and the runtime helpers own the distinct presence/emptiness semantics.
`take_expression_node` owns source, value binding and optional alternative binding.
Assignment and helper spellings share this node. `prepared_take` records the admitted
wrapper operation; preparation validates existing output places and copy capability.
Its named edges participate in normal cleanup, traversal and incremental maintenance.
The runtime helper owns selected-output behavior, including clearing error on success.
Match expressions own a selector and ordered arms; each arm owns expression labels
and one result. Empty arm labels mean default. Arm facts retain typed constant labels
and a result conversion. Match and plain ternary share exact scalar/same-enum value
result admission through `Selection_Result_Preparation`; results are non-addressable
copies and do not grant enum truthiness or coalescing. Conditional preparation also
owns symmetric scalar/nullable-scalar and matching Result/scalar joins and records
both branch conversions. Result joins preserve a single exact wrapper identity;
they neither extract a selected payload nor merge wrapper families.
Switches own selectors and ordered label groups; each group owns
labels and one ordinary block body. Exact case values are attached to labels after
bounded constant resolution, before duplicate checking. There are no payload objects, generic sibling chains
or inspection parents. [AST layout](ast_layout.md) owns detailed traversal rules.

Parsed files retain file/executable scopes. Functions own signature scopes; structs
own member scopes. Occurrences observe their enclosing scope. Global pools index
the same declarations; built-ins live in the language/runtime scope. Scope methods
encapsulate membership; `Scope_Lookup` performs plain lexical/publication lookup.
Analysis-owned `Observed_Lookup` traverses those same memberships and registers each
visited pool with preparation, including missing and ambiguous names. Plain lookup
never accepts a preparation context or creates subscriptions.

`Source_Name_Path` interprets rooted/current-relative qualified spelling independently
of symbol kind and C++ naming. Qualified lookup selects exactly one namespace member
pool; it does not perform lexical or tail-name fallback. Plain lookup only finds
existing namespace identities. Sequential analysis, after the whole parse join, may
intern an empty namespace for an observed miss. Later parser publication fills that
same pool, so the existing per-name/per-kind subscriptions detect additions, removals
and ambiguity without another dependency graph. These empty identities persist until
syntax reset, like other interned namespaces. Bare-name language availability is
unchanged. The parser preserves qualified spelling in the existing call,
constant-reference and named-type nodes, using one source-name scanner for lookahead
and consumption. It neither interns reference namespaces nor resolves targets.
Type grammar consumers share that path.

Namespace bodies own normalized `import_node` inventories (absolute target spelling,
local alias, plain/function/constant restriction). Their local scopes index the same
nodes without publishing them. Plain and observed lookup inspect local imports before
following namespace publication; explicit qualified paths bypass aliases. Plain
imports expose each supported kind independently; target identities are never copied
or renamed. `Import_Preparation` validates every import, including unused bindings,
on each preparation invocation before declaration processing. It checks per-kind
conflicts and missing/wrong-kind targets; this bounded pass does not cache resolutions.

Analysis observes `import_name` membership on body-local scopes as well as the
qualified target's ordinary name pool. Parser reconciliation retains matching binding
identities across no-op edits; replacement/addition/removal invalidates consumers via
the existing lookup graph. `imports_changed` is a persistent syntax handoff consumed
only on successful preparation. The backend's read-only status guard rejects saved
output after import-only edits. No import work is disguised as a function, no C++
`using` declarations implement source semantics, and no alias is re-exported.

Type definitions live under `compiler/types/`; constant identities and value records
live under `compiler/constants/`. Built-ins use `registered_constant`; source constants
use `collected_constant`. Both implement `constant_target_i`, exposing a source name
and read-only settled-value query. The interface invokes no processing or backend.
`constant_declaration_node` owns its initializer and attached `constant_value` facts.
`constant_definition_work` reuses the existing declaration scheduler, dependency
graph, failure/retry and retirement protocol. Analysis requires completion before
consuming a source value, and propagates changes in type or exact value key.

`Constant_Preparation` owns namespace declaration policy; the shared
`Constant_Expression_Preparation` evaluates only the agreed scalar forms. Constant references
weakly observe their named target and share its immutable value snapshot. Registered
sentinels/no-error retain their special values; source declarations admit only the
four scalar families. C++ substitutes values using the same scalar emission helpers
as literals: no runtime global storage, initialization order or constant header.
Source integer values feed existing switch/match label queries. Runtime-library JSON
intake and broader constructed types remain future work. Reserved-name validation
is deferred; ordinary parent lookup currently applies.

Concrete nominal definitions share `abstract_nominal_type_definition`; the ordinary
`nominal_type_definition` remains final. Final siblings `mixed_type_definition` and
`dynamic_type_definition` implement separate marker interfaces `mixed_semantics` and
`dynamic_semantics`. Both reuse `nominal_type` identity and registry interning, without
source-name tests or new top-level families. Mixed has value-box storage; dynamic is
the shared object/table form. Neither role is an unknown compiler type or a blanket
operation capability. Registered copy/storage contracts do not claim deep copying,
hashability, comparison, iteration or member dispatch. Runtime payload tags and
value-dependent validation remain runtime-owned; backend bindings are independent.

`enum_type_definition` is another final nominal sibling with the `enumeration` kind.
It reuses `nominal_type` interning and default value-storage normalization without
becoming an integer or a record. The registry owns no case inventory, backing syntax,
computed storage width or source-derived capabilities. Declaration preparation owns
these facts, not the registry.

`collected_type_definition` is the common source-declaration role for records and
enums. `Source_Type_Declarations` weakly associates exact semantic IDs with that
role; collection/publication/retirement share the existing paths. `Type_Queries::source_declaration`
reads that general association, whereas `source_record` returns a `collected_record`
(plain class or struct). Record reconciliation and backend consumers narrow the role.
There is no parallel enum registry or new Model root. `enum_definition_work` uses
the ordinary scheduler. `prepared_enum` retains canonical identity, optional source
backing, selected integer storage and ordered exact-decimal case snapshots. Capability
queries require completion and read these facts. `static_member_reference_node`
owns named owner-type syntax; preparation selects an enum case and retains its
snapshot. Cases are not namespace constants. C++ owns scoped-enum spelling and
type-header artifacts; shared exact-integer validation never stores C++ strings.

`prepared_selection_constant` is the common switch/match label role, specialized
as exact integer or enum-case facts. Enum labels retain canonical enum type and a
shared case snapshot, not an integer coercion or syntax backlink. `Selection_Preparation`
owns selector and label admission; the existing switch/match workers still own
duplicate/fallback policy, traversal and completion. C++ owns native selector and
label spelling. `Enum_Operators` selects same-enum equality/inequality through the
ordinary operator-decision path, producing a language bool.

`prepared_constant_expression` is the shared default-value fact role for scalar
literals and enum case references. It supplies value identity alongside canonical
type without classifying enum references as literals. Parameter defaults retain
immutable case snapshots; normal declaration dependencies invalidate signatures and
omitted calls. C++ defaults use the same enum-reference spelling as expressions.

`ownership_type_definition` is another final template sibling implementing
`ownership_semantics`, with an immutable shared/unique/weak kind and exact target
query. Its fixed handle-copy capabilities do not depend on pointee copyability.
The registry validates immediate ownership nesting and disallowed value modifiers
before publication; ordinary source preparation reports these contract failures.
These are type-composition invariants, not lifetime algorithms or implicit permission
for allocation/moves/weak access. Backend bindings and existing class-like storage
classification remain unchanged. Named holders/container composition are not
recursively prohibited; no worker or source-derived capability cache is added.

Template definitions share `abstract_template_type_definition` for ordered parameters,
arity/defaults and result kind. The ordinary `template_type_definition` remains final;
its final sibling `nullable_type_definition` implements `nullable_semantics` with
typed payload access for an application of that exact definition. Applications and
registry validation/interning depend on the abstract base. The interface identifies
language interpretation without a role flag, spelling checks or a second application
hierarchy. Result wrappers similarly share one final `result_type_definition`
implementing `result_semantics`, with constructor-set `result_kind` distinguishing
structured errors, false sentinels and boolean sentinels. `result_family()` exposes
that immutable family; inherited `result_kind()` still classifies template output
as class/struct. Separate registered definitions and backend bindings preserve each
family's canonical identity. Payload access validates the owning application.
Result construction and checked required-value extraction decisions live in
`conversions/results.php`; explicit casts, other operators and profile availability
remain separate follow-up work. Explicit
boolean sentinel constants use ordinary internal runtime nominal tag identities
with no storage capabilities/exposure; singleton constant definitions carry those
identities through existing lookup. They are not boolean payload values. `nullopt`
uses the existing null identity. No additional AST or template class is introduced.
Conversion/comparison/truthiness policy remains worker-owned.
Concatenation retains ordinary operand conversion decisions with a dedicated text
context and `text_conversion` operation; no new AST or conversion-chain structure
is needed. Text normalization is distinct from explicit casts and required extraction.
Compound preparation retains computation, conversion and `compound_write_back` mode:
converted assignment (including boxed text replacement) versus mixed stored-kind validation. The latter needs
the destination's current runtime kind, so it is not an ordinary source/target type
conversion. Prefix/postfix mutations retain their selected operation and return an
owned old/new snapshot, not an addressable reference into the target.
The nominal runtime `error` is a storable value; its no-error state is a registered
`no_error_constant_value`, not a singleton type (the same type holds real errors).
Its qualified source name uses the existing constant lookup and prepared identity path.
`coalesce_expression_node` owns left/fallback syntax and receives selected scalar
or mixed result/conversion facts; it is distinct from truthiness-based conditional syntax.
Prepared `coalesce_presence` separates always-present values, wrapper value states
and mixed non-null testing from conversion identity. Direct mixed/scalar selections
share bounded join admission, preserve the mixed carrier and copy the selected value;
they do not admit wrappers into match's scalar/enum results. Coalescing facts separate `left_extraction`
(source to selected payload, identity for direct values) from `left_conversion`
(selected value to result). Approved wrapper-left/mixed-fallback joins compose
existing extraction and boxing decisions, without a general conversion chain model
or implicit wrapper-to-mixed boundary rights.
`null_type_definition` / `null_type` give null literals a canonical,
non-storable sentinel identity, without source exposure or a storage backend binding.
`nullable_type_node` owns `?T` syntax and resolves through shared application interning;
`null_literal_node` carries the sentinel fact until a typed destination conversion.

## Structure and processing boundary

Structures may initialize data, enforce local invariants, expose typed access and
clear attached facts. Workers own stage dispatch/traversal, semantic algorithms,
scheduling, dependency maintenance and publication. Retained structures
do not keep processing contexts or workers.

Preparation dispatch belongs to expression, type, body and specialized analysis
workers. They consume typed fields with one invocation-local context and attach
facts; nodes expose no `prepare`, completion, loop-processing or constant-evaluation
hooks. `Preparation_Worker` selects declaration and collected-definition work itself.
Analysis-internal work records retain typed identities, state and queries only.
`Preparation_Worker` owns queueing, rebuild, member-settlement and retirement
dispatch; records neither invoke nor retain processing workers/contexts.
`File_Preparation` is the standalone entry adapter to that scheduler. Facts are
attached to syntax, not token-indexed maps. See [preparation](../../04_analyze/prepare/README.md).

C++ emission has no node-owned operation hook. `CPP_Syntax` selects typed handlers
through backend-owned expression/statement/interpolation dispatch; handlers control
their own child traversal. Shared syntax exposes no backend worker protocol or
universal visitor. The [stage-owned processing plan](../planning/stage_owned_processing.md)
records the completed stage-boundary batches and their evidence.

Maintenance is node-owned traversal through named owning fields. Workers implement
`enter`, `edge` and `token_index`; edge handling chooses recursion. Inspection
iterators are not used to run preparation, generation or cleanup.

## Retained C++ generation

Successful semantic changes/deletions accumulate in `collected_file.preparation_changes`.
The backend retains fragments keyed by existing work owners, with completion versions,
dirty state, text, includes and record dependencies. It renders selected fragments;
unchanged bodies are not traversed. Generation reads but never clears the handoff.
`Compiler::cpp()` acknowledges all its prepared sources only after successful output
publication. Standalone `CPP_Generator::generate` returns output without acknowledging
shared changes; repeated calls can reuse fragments while the handoff remains pending.
`Preparation_Status` and `Type_Queries` provide read-only work guards and established
type/source-association queries, separately from preparation workers.
Function signature fragments retain separate prototype and definition spellings and
include requirements. Default values appear only on prototypes; definition-only
layout includes do not leak into callable headers. Assembly does not rerender syntax.
Failures retain pending work and withhold completed output; unexpected failures
request a full rebuild.

`CPP_Generator::generate` returns `Storage<cpp_artifact>` for one common output
contract. Each final artifact owns immutable identity, kind, relative path and bytes,
without semantic or worker references. The coordinator publishes the complete
collection before acknowledging consumed work. `CPP_Output::encode` serializes all
artifacts with duplicate identity/path rejection; it does not generate or write files.
The CLI/native transport is versioned independently from semantic identity.

`generate_program` emits one header per callable and record, initial per-source
implementation groups, and `entry.cpp`. The single-file `generate` adapter delegates
to that same path. Includes come from live fragment dependencies recorded while
rendering resolved calls and type representations, including nested template arguments.
There is no all-project declaration header or combined-output renderer.

`cpp_type_requirement` describes backend use-site visibility, not semantic capability.
Bare source records in callable declarations need only forward declarations. Reference
parameters/returns, reference call temporaries and local aliases also only name their
record. Value definitions, local copies/replacements (including inferred `auto`),
embedded record fields and member access require layouts. These decisions belong to
the existing typed emitters, using settled types; no second AST walk or lexical scan
discovers dependencies. Member access explicitly records its base layout, including
nested field expressions, even when its result is scalar.

`cpp_type`, generation contexts and retained fragments carry name-only source
requirements separately from include paths. These are scalar names, not additional
semantic owners or worker references. The callable header supplies definition-signature
forward declarations; `definition_headers` retains the extra complete includes for
implementation units. Final preambles deduplicate names and omit a forward declaration
when the same artifact directly includes that type's definition.

Runtime/template bindings still include their registered headers and complete source
payload definitions recursively. A template spelling alone is not a promise that its
runtime implementation supports incomplete payloads. Per-binding relaxation and broader
future inline/template dependency roles require their own proofs. No source semantics
or preparation invalidation rules change with native include precision.

`CPP_Artifacts` owns reversible filesystem-safe encodings, callable/type header paths
and logical-module/relative-source unit paths. Collected definitions expose qualified
source names separately from local lookup spelling; canonical namespace identity
feeds type registration, artifact keys, source symbols and forward declarations.
Global spellings remain stable. Namespace-body syntax/lookup integration is still
pending; the registry/backend checkpoint alone does not admit authored namespaces.
Names never use object IDs, absolute checkout paths or traversal counters. Temporaries
remain body-local. Optional disk publication belongs to the coordinator's invocation-local
`Output_Publication`, not these records or the backend. Its scalar before/after
inventory owns recovery authorization without retaining semantic objects or workers.
See the [publication protocol](output_publication.md). The [native build owner](native_cpp_build.md)
consumes that settled disk boundary with invocation-local configuration, action
results and build counts. Its action/dependency cache is filesystem state, never a
second semantic graph or a Model worker root. The Chapter 08 roadmap owns acceptance
and further native-cache performance work.
Test-only `CPP_Probe` preserves artifact boundaries in editable text; its reader rebuilds
separate files and compiles them separately rather than flattening into a unity unit.

## Lifecycle and legacy boundary

[Incremental lifecycle](../lifecycle/incremental.md) owns synchronization, appended
tokens, failure and deletion policy. [Ownership](ownership.md) owns reference intent;
[Storage](../storage/STORAGE.md) owns membership semantics.

The [LLVM experiment](../../05_backend/llvm/README.md) retains separate token-indexed
name/template preparation only for regressions. Its maps and native type choices
must not define shared semantics. Future LLVM development must consume shared facts.
