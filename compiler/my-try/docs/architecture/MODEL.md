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
| `language_scope`, `global_scope` | Built-in/runtime definitions and shared source indexes; global parents to language |
| `runtime_callables`, `cpp_callable_bindings` | Installed free-function/static-factory identities/contracts and separate C++ bindings; reset with canonical types |
| `initialization_contracts` | Runtime initialization declarations, internal input types and concrete bindings; reset with canonical types |
| `indexing_contracts` | Registry-lifetime read/store/append contracts keyed by exact receiver definition; independent of construction opt-in |
| `iteration_contracts` | Registry-lifetime key/value signatures keyed by exact receiver definition; independent of bracket/initialization capability |
| AST/collected definitions | Specialized facts and retained declaration/body work |
| `prepared_files` | Successfully completed file records referencing the retained syntax |
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

Modules save their key/name, declared path, resolved path and input position. Any
configuration/order change forces a full rebuild. Files save module-relative paths;
IO boundaries compute full paths. A source record observes its owning module.

The parser reuses matching declaration nodes and their canonical collected identities.
Only applicable name-bearing syntax has an occurrence backlink. Retained names are
independent of token indexes. Duplicate names remain distinct candidates. The
collector registers symbols during parsing; there is no collection pass or second
symbol model. See [analysis](../../04_analyze/README.md) for typed collected roles.

Function signatures and bodies have separate work identities. Fields/parameters
settle with their definition; locals settle with their body. A body is a processing
unit, not a symbol. Replacing body syntax transfers its retained work identity.
Unchanged bodies retain nodes, scopes, occurrences and facts. Parsing does not
mutate preparation-work state: declaration change flags and body `syntax_changed`
persist until analysis successfully consumes them. Repeated parsing cannot clear
pending source changes. Read-only source-change queries also guard saved prepared
results against premature emission.
Parameters own optional default expressions; normalized scalar default snapshots
belong to their callable contracts and participate in signature equivalence. Calls
retain only explicit arguments; emission supplies omitted defaults from the signature.
Variadic syntax marks one trailing parameter independently of passing mode; its
prepared element type is distinct from its ordinary vector storage type. Prepared
calls retain the fixed-prefix/pack boundary without rewriting source arguments.

### Callable contracts

`callable_target_i` is implemented by the existing `collected_function` identity
and by `registered_function` and `registered_static_factory`. Their `signature()` accessor only reads attached
contracts. `Call_Preparation::require_signature` completes source declarations and
registers the consumer dependency; installed runtime targets already own a complete
contract. Runtime catalogs do not cache source declaration facts. Registered factory
keys are provider-qualified semantic identities, independent of C++ names; their
owner is the exact canonical constructed type and equals their return type.

Free functions have provider-qualified keys and source names, but no fabricated
receiver, AST or construction owner. Scope `runtime_functions` indexes catalog-owned
identities; `functions` remains the source-only retirement inventory. `callables_named`
combines them for ordinary nearest-scope lookup and analysis lookup observations.
Installed pools are immutable under retained consumers. Runtime overload selection
admits exactly matching fixed, required, by-value parameter signatures; it is not
general conversion ranking. JSON is the first installed free-function provider.

`callable_signature` and `callable_parameter` have no source-storage links.
`prepared_function` owns the signature plus its source parameter facts;
`prepared_parameter` links its collected storage identity to the same parameter
contract (no duplicated type/mode/default state). `prepared_storage::storage_type()`
provides the common local/field/parameter storage query. Contracts are populated
before publication and treated as immutable afterward, like other prepared facts.

An optional `reference_return_contract` identifies a whole reference parameter by
its immutable index; absence denotes a value return. Structural validation requires
an exact-type fixed reference parameter. This is a symbolic contract, not a source
body proof or a caller lifetime. Signature comparison includes it. Source signatures
derive it from exactly one fixed reference parameter; body preparation verifies the
whole-parameter return independently. Calls substitute the actual argument's place.
Runtime reference-return execution remains gated; initialization factories require
value returns. Nonrecursive forwarding reuses the same argument-place substitution
and final whole-parameter return proof, including local aliases.

After all bodies settle, `Reference_Cycles` builds an invocation-local graph from
current collected function/call indexes and prepared call targets. Its transient
`reference_cycle_vertex` records use integer edges, not mutually owning handles.
Strongly connected components containing reference-returning functions reject through
the worker's normal failure/retry protocol before prepared-file publication. By-value
recursive components remain allowed. This linear check includes unchanged bodies and
is rerun for incremental updates; it does not re-prepare bodies, retain a second graph,
or turn body relationships into declaration dependencies.

Expression facts carry optional `prepared_place`, with `addressable()` derived from
presence. Places select original local declarations or parameters and may project
through direct stable struct fields. `root_storage()` preserves that identity;
parameter facts distinguish value and reference origins. Values, indexed reads and
temporary-derived fields have no place. Place queries do not decide whether a borrow
may escape; that analysis belongs to workers. See [ownership](ownership.md).

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

Construction syntax now owns `prepared_data_construction` with its selected binding
and inherited result type. Each existing entry owns `prepared_data_entry` conversions;
no prepared entry inventory duplicates syntax order. The expected construction type
is a transient explicit argument from a typed consumer, not mutable ambient context
or another retained type field. Explicit target syntax takes precedence. Cleanup and
incremental re-preparation follow the ordinary attached-fact lifecycle. The backend
consumes these facts for built-in construction execution; source input-view lifetime
operations remain gated.

## AST and scopes

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
`prepared_value_probe` optionally owns it; the dedicated `unset_node` statement owns
it directly. Ordered steps observe existing bracket nodes and retain key conversions
and canonical element types. Runtime operation selection remains in stage workers,
not in these structures. Root/key syntax facts follow ordinary cleanup/invalidation.
Registry probe/removal opt-ins do not follow implicitly from write capability.

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
and one result. Empty arm labels mean default. Arm facts retain exact integer labels
and a result conversion. Match retains scalar-only admission; conditional preparation
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

Type definitions live under `compiler/types/`; constant definitions live under
`compiler/constants/`. Built-ins are initialized into independent type and constant
scope indexes at startup. Source records and prepared references retain canonical
definition identity rather than copying semantic values. New runtime-library JSON
intake, source constant declarations and broader constructed types remain future
work. Reserved-name validation is deferred; ordinary parent lookup currently applies.

Concrete nominal definitions share `abstract_nominal_type_definition`; the ordinary
`nominal_type_definition` remains final. Final siblings `mixed_type_definition` and
`dynamic_type_definition` implement separate marker interfaces `mixed_semantics` and
`dynamic_semantics`. Both reuse `nominal_type` identity and registry interning, without
source-name tests or new top-level families. Mixed has value-box storage; dynamic is
the shared object/table form. Neither role is an unknown compiler type or a blanket
operation capability. Registered copy/storage contracts do not claim deep copying,
hashability, comparison, iteration or member dispatch. Runtime payload tags and
value-dependent validation remain runtime-owned; backend bindings are independent.

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
`no_error_constant_definition`, not a singleton type (the same type holds real errors).
Its qualified source name uses the existing constant lookup and prepared identity path.
`coalesce_expression_node` owns left/fallback syntax and receives selected scalar
or mixed result/conversion facts; it is distinct from truthiness-based conditional syntax.
Prepared `coalesce_presence` separates always-present values, wrapper value states
and mixed non-null testing from conversion identity. Direct mixed/scalar selections
share bounded join admission, preserve the mixed carrier and copy the selected value;
they do not alter scalar-only match. Coalescing facts separate `left_extraction`
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
`Compiler::cpp()` acknowledges its prepared source only after successful output
publication. Standalone `CPP_Generator::generate` returns output without acknowledging
shared changes; repeated calls can reuse fragments while the handoff remains pending.
`Preparation_Status` and `Type_Queries` provide read-only work guards and established
type/source-association queries, separately from preparation workers.
Function signature fragments retain separate prototype and definition spellings so
default values appear only on prototypes, without rerendering syntax during assembly.
Failures retain pending work and withhold completed output; unexpected failures
request a full rebuild.

Assembly preserves the current single-`main.cpp` layout and orders record dependencies.
It reconstructs includes from live fragments. Generated names use role prefixes and
saved source names; temporary numbering is per body. Future namespaces, overloads
and richer shadowing require a naming review. No disk writer or Ninja integration
is implied. Partitioning is tracked in [debt](../planning/incremental_strategy.md).

## Lifecycle and legacy boundary

[Incremental lifecycle](../lifecycle/incremental.md) owns synchronization, appended
tokens, failure and deletion policy. [Ownership](ownership.md) owns reference intent;
[Storage](../storage/STORAGE.md) owns membership semantics.

The [LLVM experiment](../../05_backend/llvm/README.md) retains separate token-indexed
name/template preparation only for regressions. Its maps and native type choices
must not define shared semantics. Future LLVM development must consume shared facts.
