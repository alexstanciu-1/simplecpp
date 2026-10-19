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
| `runtime_metadata` | Typed startup module catalogs, independent of canonical identities; retained across resets without rereading JSON |
| `runtime_constants` | Settled module constant identities/values; separate from callables, reset with canonical types, indexed by language scope |
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

Runtime metadata loading is startup IO, not parse/collect/preparation work. The
coordinator validates configuration privately before retention. Ordinary runtime type
recipes install through the existing registry before resolving separate private
callable/constant inventories. Typed recipes own no canonical identities; a transient
startup worker resolves forward defaults and rejects cycles. Source publication reads
registered exposures, not a duplicate name list. Backend installation consumes separate
binding records. Registry resets rebuild identities from the same immutable-by-contract
metadata. Live replacement is rejected; see the [catalog contract](../../../../specs/runtime_module_metadata.md).

## Tagged boxed storage

Runtime callable membership observes installed callable identities and canonical
owners; it does not create collected declarations. The callable catalog indexes
owner/name overload pools separately from free names. Metadata membership never
owns C++ spelling. Source and registered instance calls share prepared receiver
lifetimes and argument admission; static factories are ordinary callable contracts.

Runtime definition storage policies are registry-owned facts, installed once from
typed metadata before publication. Inline/shared selection is independent of nominal
kind and payload capabilities. Shared values reuse the ordinary nominal-handle
query and backend representation; copying them copies identity, not payload state.
Registry reset rebuilds policies with definitions. Opaque payload ownership
transformations remain unavailable without their own contracts.

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

`type_lifecycle_work` shares that work base but remains independent of layout and
callable signatures. Its declaration-only `prepared_type_lifecycle` snapshot records
destructor visibility (implicit destruction is public). Preparation observes local
destructor name pools and base lifecycle contracts; no method body or dispatch work
is demanded. Storage/allocation checks consume this snapshot through the scheduler,
avoiding cycles for `unique<Self>` fields and return signatures. Equivalent snapshots
preserve versions; reset and retirement clear facts and dependency registrations.

Interfaces now use that same contract work directly, without record-layout work or
record preparation facts. `interface_requirements` references original interface
methods (deduplicated by identity), including satisfied requirements needed by
descendants. `forwarders` contains local dispatch introductions for unchanged inherited
implementations. Each method decision records its dispatch owner, distinct from the
source declaring owner. `bindings` pairs required declarations/slot owners with selected
implementations/dispatch owners and prepared signature adaptations. Trait bindings are
semantic only; they introduce no extra runtime base. These are not collected methods or
AST bodies. Signatures remain shared. C++ assembles direct base/interface spelling
from current relationship facts, outside cached layout fragments, so relationship-only
edits cannot leave stale header inheritance. Bare interfaces and classes share the
`is_nominal_handle` storage query; interface identity still confers no record layout.

`collected_trait` preserves an authored composition source as another member owner,
with a distinct nominal kind and no value capabilities. Simple `use_traits` syntax
owns named references, not resolved or cloned members. Consumer-owned independent
member instances are [materialized explicitly](../planning/trait_member_instances.md):
`collected_record::trait_instances` owns private fragment syntax, collection/scopes
and copied tokens. Each member pairs its origin with an independent working occurrence.
Source `use` selection/materialization is coordinator-owned; origin nodes never receive
consumer-specific facts. The parser only replays supplied member spans; composition
supplies the consumer and lexical context without adding authored AST membership.
`Member_Inventory` builds transient local-member views for analysis and emission;
it neither owns members nor caches inherited lookup results. Ordinary layout and
callable processing consume those views. Member lookup observes each private scope.
Bodyless trait contributions have a separate requirement view and signature work;
`prepared_type_contract::trait_requirements` retains their independent identities.
Effective method decisions select an implementation or unresolved abstract slot;
requirements do not enter layout's ordinary method inventory as duplicates. Emission
uses selected slots, and cycle analysis follows requirement-to-implementation edges.
Reference-cycle analysis includes private occurrence collections. C++ places a
working method in its declaring consumer's unit, independently of lexical provenance.
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

Synthesized `record_initialization_contract` facts retain a settled base contract and
its inline type, separately from local inputs. `inputs_in_order()` is a transient
base-first view of original input/default identities, not a flattened member inventory.
Preparation owns eligibility and dependency tracking; backend construction forwards
the base prefix directly into the base's tagged constructor. Base-contract identity
participates in record equivalence so schema/default edits propagate to callers.
Parameters own optional default expressions; normalized scalar/enum default snapshots
belong to their callable contracts and participate in signature equivalence. Calls
retain only explicit arguments; emission supplies omitted defaults from the signature.
Variadic syntax marks one trailing parameter independently of passing mode; its
prepared element type is distinct from its ordinary vector storage type. Prepared
calls retain the fixed-prefix/pack boundary without rewriting source arguments.

### Callable contracts

Anonymous signature work owns an optional canonical expected-type input supplied by
creation-site expression preparation. Query-only signature/capability demands do not
erase it. Changed inputs schedule signature rebuilding; ordinary signature comparison
and dependencies invalidate the body/consumers. Retirement clears input and facts.
Shared parameter preparation can fill absent types from the expected signature, while
authored AST types remain nullable/unchanged. Expected types arrive through the existing
consumer path for storage, selected arguments and returns; no inferred syntax, extra
anonymous identity or backend type inference is introduced.

Explicit callable returns reuse the ordinary return conversion and value boundary.

Reference capture admission uses a borrowable place's root rather than requiring
an unprojected local. Stable inline-field aliases and enclosing captures therefore
share the ordinary `prepared_place`/`storage_borrow` path. Lifetime lookup follows
the containing root: borrowed captures reuse their original lifetime; owned captures
use their existing invocation-local environment membership. Access remains inherited,
so borrowing an owned value capture never makes it writable. No new retained facts,
capture syntax or backend lowering path are introduced.

Recursive callable contracts can return other callable contracts; their canonical
identity and C++ spelling already compose. Concrete-to-erased conversion observes
the anonymous body, verifies exact signatures/copyability and rejects transitive
borrows. Returning an already-erased value preserves its contract. No factory-specific
prepared result, retained environment owner or lowering path exists.

Receiver captures reuse `prepared_capture::borrow` with an existing `prepared_receiver`
origin; the typed `receiver()` query derives the role without a second kind flag.
Instance-body local membership establishes the receiver's invocation lifetime, but
ordinary expression preparation still rejects using `$this` as a value. Anonymous
body preparation restores the captured receiver and its original declaring access
context. Nested captures preserve the original lifetime; C++ emits `[this]`, not
an object copy or owning handle. Existing destination and erasure checks apply.

Authored parameter/return syntax is composed through `callable_signature_syntax`.
Each function/method owns one component containing its original parameter nodes,
optional return type and explicit reference-return marker. It has no source name,
scope, collected identity, work record or prepared facts. Parameter names remain
on the parameter nodes. The owning AST node exposes these children directly in
grammar order for inspection/maintenance; the component is not another AST node.
This is the first [closure foundation checkpoint](../planning/closure_foundation.md),
not anonymous callable execution. `Callable_Syntax` now shares the remaining
body/scope construction state and neutral structural traversal. `closure_node`
uses it as an expression without named-declaration inheritance or collection.
The inspection-only `callable_syntax_owner_i` exposes signature/body queries, not
processing hooks. Anonymous source identities now belong to
`collected_file::anonymous_callables`, independently of named lookup. Each identity
owns its original closure syntax, weakly observes its collection and has a non-reused
source-local Storage position. The syntax backlink is weak. Shared token-range
reconciliation preserves identities in reused bodies and retires replaced/speculative
occurrences through ordinary successful-join disposal. Positions are not emitted
names. Anonymous signatures and bodies now have independently retained work,
initially demanded by expression preparation and subsequently selected by the
existing dependency scheduler. Named and anonymous callables share parameter
admission/binding and body return-path checks. Capture-free closures now receive
distinct concrete types. `Source_Closure_Types` weakly associates those types with
anonymous identities, independently of the named type-declaration index. Local
invocation uses an expression callee on `call_node` and shared call admission.

Independently settled dependencies use `contract_work`. Named declaration work is
one specialization; source-callable signature work uses the base directly and
exposes its current named identity only through the optional declaration query.
The preparation worker owns contract queueing, direct demand, version observation,
failure/retry and retirement. Named lookup resolves a contract, not the reverse.
Existing named backend fragment categories are unchanged. Anonymous contracts have
their own category and do not publish a named declaration fragment.
They render as inline C++ lambdas with `auto` local storage. Anonymous changes
conservatively invalidate executable C++ fragments in their owning source.

Explicit `function<R(Args...)>` syntax uses `callable_type_node`, with ordered
name-free `callable_input_type_node` children owning type syntax, passing mode,
read-only access and a variadic marker. These are not parameter declarations and
publish no names or occurrences. Preparation shares parameter-shape admission with
ordinary declarations and produces existing `callable_parameter` contracts.
Its structural `callable_type_definition` is interned by canonical return/input
types, modifiers, passing/access modes, variadic element identity and optional
reference-return origin index. Syntax owns an explicit return-reference marker;
analysis shares whole-parameter origin selection with ordinary source signatures;
the registry-owned contract has no parameter names, defaults or source observers.
Interning copies input records, never retaining mutable caller-owned parameter facts.
Variadic storage is the ordinary vector type, but its element contract distinguishes
it from a fixed vector input. Contextual completion supplies the element type for
variadics without rewriting authored markers. C++ declarations and erased types
share parameter spelling; invocation reuses reference admission and pack construction.
Return spelling is shared too. `Callable_Contracts::matches_erased` compares exact
signatures/origins independently of capture/copy admission. Conversion and the transient
reference-cycle audit reuse it; erased calls conservatively add edges to compatible
prepared anonymous targets, including value-returning intermediaries. There is no
retained points-to graph or source link on an interned callable type.
The existing call target interface lets value invocation reuse ordinary argument
admission. Dependency-aware conversion preparation checks a concrete closure's
signature before publishing `callable_wrap`; only the backend chooses `std::function`.

Closure syntax owns ordered `closure_capture_node` entries between parameters and
return syntax. Each owns value/reference passing mode and a variable-reference child;
ordinary references do not acquire capture-only flags. Creation-site preparation
resolves them using the enclosing local environment.
Anonymous body work owns independent `prepared_capture` storage facts, observing the
original collected names. Optional `storage_borrow` pairs a borrowed place with its
required lifetime; absence denotes an owned value capture. Captures are not parameters
and do not affect the public signature. Body preparation installs them alongside
parameters; fresh creation-site inputs rebuild the body, while independent contract
invalidation reuses established inputs. Retirement clears the captured environment.
Value capture bindings are read-only; prepared projection access distinguishes a shared
pointee from its owning binding without changing ordinary const-parameter contracts.
Reference captures preserve source access. Copyability and safe copy destination are
separate checks; borrowing a noncopyable payload does not copy that payload.

`local_environment` membership establishes `storage_lifetime` points in a neutral
`storage_lifetime_region`, including parameters without mutating their shared signature
facts. Entering a child environment records parent establishment order. Blocks, loop
headers/bodies and switch groups use their existing environment boundaries; repeated
runtime entries instantiate those lexical regions afresh. These regions are neither
lookup scopes nor a runtime lifetime tracker, and never compare token generations.
`Storage_Lifetimes` owns ancestry/order and destination checks. Aliases resolve to
original places. Capture environments retain the needed lifetime points; membership
indexes and worker environments remain transient. Every callable body gets an independent
invocation root. Anonymous body contexts derive explicit lifetime guarantees from their
captures (including transitive value-captured closures); these justify invocation-local
copies only, never writes into external longer-lived storage. There is no artificial
parent edge from an invocation to its creation scope or registry-owned lifetime cache.

Arrow spelling is `closure_syntax_kind::arrow` on that same node. Its expression is
normalized to an ordinary return inside the common body. Automatic capture discovery
belongs to `Capture_Preparation`, which reads collected variable occurrences and
lexical scopes using a transient callable-boundary index. It respects nested parameter
bindings and explicit closure boundaries. Discovered names are semantic inputs, not
synthetic source capture nodes. The C++ backend reads the same prepared capture fields
for arrows and explicit closures; no separate callable type or emission path exists.

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

`runtime_function_definition` is the common catalog identity for concrete
`registered_function` and argument-dependent `registered_generic_function`. The
latter implements only `callable_definition_i`: its registry-resolved contract owns
variable/concrete/application type patterns, not a dummy fixed signature. Startup
validation resolves names and completes default type arguments. Scope and C++ binding
indexes use the common identity without gaining semantic processing responsibilities.
`Runtime_Call_Preparation` owns exact matching, call-local variable bindings and
selected-signature materialization. Repeated variables require identical canonical
uses. Only the selected contract forms types through `Capability_Preparation`, then
uses ordinary argument admission. Prepared calls own their concrete signatures and
reference the original provider identity. Overlaps reject; no conversion/specificity
ranking, source parsing special cases or provider specialization cache is introduced.

Free functions have provider-qualified keys and source names, but no fabricated
receiver, AST or construction owner. Scope `runtime_functions` indexes catalog-owned
identities; `functions` remains the source-only retirement inventory. `callables_named`
combines them with language-scope-owned `compiler_function_definition` identities
for ordinary nearest-scope lookup and analysis lookup observations.
Installed pools are immutable under retained consumers. Runtime overload selection
admits exactly matching fixed, required value or observation parameter signatures;
an overload set cannot mix these modes. It is not general conversion ranking.
JSON is the first installed free-function provider.

Runtime-only `passing_mode::observation` is a read-only, non-retaining unary boundary.
Shared preparation records `observe_value` without manufacturing an escaping place
or requiring copyability. Ordinary reference admission is unchanged. C++ emits one
full-expression const-reference call so temporary values and temporary property
owners remain alive through observation. Provider metadata promises no mutation or
borrow escape; multi-argument observation and source syntax remain disabled. Concrete
test providers exercise this path; generic vector/hash count and concrete boxed count
now use the same boundary through production module metadata.

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

`prepared_callable_adaptation` describes required-to-provided parameter conversions
and the reverse provided-to-required result conversion. `Callable_Adaptation` owns
directional comparison, accepting only identity or observed shared upcasts. It retains
no signature owner, worker or backend spelling. Type contracts use observed directional
comparison for virtual/interface/trait contracts and invariant comparison for static
redeclarations. Dispatch bindings own these adaptation snapshots; equality includes
their conversion types/operations so signature and ancestry edits propagate.
C++ owns facade/body/slot spelling and emits bridge definitions in implementation
units, not type headers. Reference-cycle analysis consumes these same bindings.
See the [checkpoint](../planning/callable_variance.md).

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
`prepared_source_storage` owns source-local declaration links; invocation environments
retain only that family. `prepared_property_storage` instead describes typed instance
or static storage. Its source-field and registered-property implementations retain
their actual identities, without invented collected declarations. Property assignments
own `prepared_property_assignment` with selected property storage and conversion/boundary
facts, not a local `prepared_binding`. Registry-owned runtime members and backend-owned
property bindings are rebuilt together on reset from retained metadata.
Stable receiver places supply ordinary access checks. Plain writes may also select
a property on an indexed shared value, whose runtime handle is retained during the
write; this does not manufacture a place for that value or make it borrowable.
`runtime_property_definition` retains the single owner-definition/type-pattern recipe.
`Runtime_Pattern_Preparation` substitutes it using the same worker as callable
signatures. `Runtime_Members` retains stable `registered_property` storage per selected
owner application; each access still checks capabilities and observes dependencies.
Backend bindings key the recipe identity, not these materialized storage instances.
Runtime callable membership indexes semantic definition identity, not concrete
applications. Generic contracts reserve leading variable positions for owner
parameters; preparation seeds these from the selected application and then runs
ordinary argument matching. Concrete signatures remain call-owned. `prepared_call`
retains the selected runtime owner for backend qualification of static template
members, without retaining metadata specs or backend spellings in semantic facts.

An optional `reference_return_contract` identifies a whole reference parameter by
its immutable index; absence denotes a value return. Structural validation requires
an exact-type fixed reference parameter. This is a symbolic contract, not a source
body proof or a caller lifetime. Signature comparison includes it. Source signatures
derive it from exactly one fixed reference parameter; body preparation verifies each
return against that whole parameter independently of statement position. Structured
completion checks missing returns. Calls substitute the actual argument's place.
Named declarations, concrete closures and arrows share this admission and body
obligation. Captured fields never become reference-parameter origins, and erased
callable conversion rejects reference-return contracts rather than discarding them.
Runtime reference-return execution remains gated; initialization factories require
value returns. Nonrecursive forwarding reuses the same argument-place substitution
and per-return whole-parameter proof, including local aliases.

After all bodies settle, `Reference_Cycles` builds an invocation-local graph from
current collected named/anonymous callable inventories, call occurrences and prepared
targets. Expression calls have occurrences without named lookup. Its transient
`reference_cycle_vertex` records hold a target, optional body and diagnostic label,
with integer edges, not mutually owning handles. Lexical body scopes keep anonymous
calls separate from their enclosing callable; creation alone is not a call edge.
Strongly connected components containing reference-returning callables reject through
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
a `prepared_index_target` and element conversion. The target owns root, ordered
path, selected store/append decision and nested construction type; compound updates
reuse it without inventing an addressable interior element. Registry signatures bind
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
Registered constants may retain a runtime definition owner. `Model::$runtime_members`
indexes those same identities by owner and member name; ownerless constants alone
enter language scope. Type resets rebuild the index without rereading JSON. Member
constants reuse settled values, not synthetic source declarations or backend names.
All template applications share the definition's constant identity and value;
lookup does not allocate a constant per application.
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
intake supports metadata integer constants; broader constructed types remain future work. Reserved-name validation
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

Registered capabilities distinguish unconditional provider opt-ins from capabilities
dependent on template arguments. `template_type_parameter::contracts()` constrains
type formation; `capability_dependencies()` gates the applied owner's declared
capabilities without preventing formation. For example, a vector's copyability
depends on its Value argument's copyability, while a shared handle's does not.
`Type_Registry::has_registered_capability()` evaluates those rules using a transient
`type_capability_query`. Analysis supplies completed source facts through its normal
dependency-aware query; the registry retains neither workers nor source-derived
capability caches. Pure registry queries reject source facts they cannot establish.

`result_payload` is an unconditional provider opt-in for exact Result transport,
not a parameter-dependent capability, copy permission or truthiness contract.
Result admission reads that opt-in rather than naming payload families; individual
copy operations separately query effective copyability. New runtime providers use
the same registration protocol and separate backend bindings. Ordinary nominal/template
definitions now come from typed JSON records, including vector/hash. Language-semantic
roles remain compiler-owned. Metadata registration does not grant every source record
Result support automatically or confer initialization/indexing/iteration protocols.

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
`conversions/results.php`; explicit casts and profile availability remain separate.
Guarded operators have the bounded owner described below. Explicit
boolean sentinel constants use ordinary internal runtime nominal tag identities
with no storage capabilities/exposure; singleton constant definitions carry those
identities through existing lookup. They are not boolean payload values. `nullopt`
uses the existing null identity. No additional AST or template class is introduced.
Conversion/comparison/truthiness policy remains worker-owned.
Guarded operator admission lives in `operators/guarded.php`. The existing
`operator_decision` retains `operand_extractions`: empty for ordinary operations,
otherwise one checked extraction (or plain identity) per source operand, before
its existing payload conversion or truthiness decision. This is a bounded
extraction stage, not a general implicit-conversion chain. Preparation delegates
payload admission to ordinary operator owners. C++ composes extraction inside each
operand expression, preserving eager snapshot order and lazy RHS checks. Compound
preparation passes a distinct `compound_assignment` context so value lifting cannot
authorize write-back. The first slice is canonical int in nullable, Result and
falseable Result; state-aware equality and mutation remain separate.

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

Called-type implementation instances are owned separately by the original collected
method. Each owns independent working syntax and ordinary signature/body work, without
changing declaring-type identity or authored class membership. Materialization and
retirement belong to `Callable_Instances`, a coordinator; `Source_Fragments` shares
snapshot/retirement support with trait composition. Static source calls select concrete
instances; original signatures are checked eagerly, bodies per demand. The backend
emits stable static helpers in the original declaring record without adding source
members. See [called-type instances](../planning/called_type_instances.md).

`Callable_Inventory` is a transient read-only view of source and private implementation
collections. Preparation uses it to select ordinary private method work and cycle
analysis uses it to include private call edges. It does not publish private sources
as program files. Transient preparation contexts carry the called type separately
from the lexical member owner; no worker or context is retained by an instance.

`Preparation_Run` is the shared coordinator for compiler and standalone analysis.
Worker-local instance requests and an identity-tagged suspension signal return control
to it before parsing private fragments. The suspended body remains pending; fresh
analysis rounds reuse settled work and resolve calls to interned working identities.
Only body execution may suspend. No request/worker is retained in the source model,
and the coordinator cannot publish a partial preparation result.

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
