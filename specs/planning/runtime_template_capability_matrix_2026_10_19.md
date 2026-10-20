# Runtime families and specialization capabilities
Doc Status: planning

Date: 2026-10-19. Baseline: `2f216d42` on `v0.2/prelower_templates`.
Scope: inventory and proposed extensions for capability-driven `prelower_templates`.
This is a review document, not executable configuration or a second type registry.
No new semantic capabilities, metadata fields or source features are installed by it.

## Current coverage contract — 2026-10-20

Initially reviewed against `b52a02a8`; transport/coverage reconciliation against
`19d54313`. This section supersedes the historical work-order and
gap descriptions below; dated evidence remains a record of its tested revision.
Public selection has since been consolidated: API `prelower_templates = true` and
CLI `--prelower-templates` both select the general concrete path. Former comparison
fields and superseded recipe/runtime-ABI source-lowering paths have been removed.
Shared physical storage/lifecycle workers remain; see the
[current architecture](../../compiler/my-try/docs/architecture/cpp_template_prelowering.md).
This table is evidence, not an admission registry or a promise of full-family support.

**Registered** means an existing semantic identity/protocol and its C++ binding.
**Concrete-compatible** means the indicated adapter and physical interface exist;
Clang must still accept the actual provider/type combination.
**Template-free proven** means a complete application-input closure audit, not just
absence of template spellings in generated text or a successful native build.
**Unsupported** means an explicit rejection; **unproved** is not an admission promise.

| Surface | Registered / existing owner | Concrete-compatible scope | Template-free proof and remaining boundary |
|---|---|---|---|
| Integer widths, boolean, float, string | Compiler-known identities; core `cpp_types`; prepared literal/operator/conversion facts | Scalar and byte interfaces, opaque storage and demanded operation forwarding | [Accounting closure](results/prelower_closure_coverage_2026_10_20/README.md) proves its used subset. [Binding/demand proof](results/prelower_runtime_template_demand_2026_10_19/README.md) is broader representation evidence, not all widths × operations closure coverage |
| Null output | Internal non-storable sentinel; intrinsic backend binding; prepared output | Generic output bridge borrows the sentinel; template-free `null_t.hpp` | [Complete two-unit closure and ordered output](results/prelower_null_output_2026_10_20/README.md). No new source storage type |
| Vector/hash, nested stored arguments | Runtime type/initialization/indexing/iteration contracts; existing registry | Recursive concrete arguments and native outer implementation; demanded interfaces only | Accounting covers its actual container uses. [Nested initialization](results/prelower_concrete_initializers_2026_10_19/README.md) and [indexing](results/prelower_indexing_2026_10_19/README.md) prove additional behavior/counters, not arbitrary nesting or every key/payload combination |
| Source records/classes as fields, signatures and payloads | Completed source facts; ordinary type/member emission; source declaration artifacts | Concrete fields/signatures, reference identity, payload declarations and construction | [Source-field proof](results/prelower_source_fields_2026_10_19/README.md); accounting closure includes its source classes. Forward-declarable recursion works; unresolvable generated header/layout cycles reject |
| Shared/unique/value storage | Existing ownership definitions/value modifier, storage policy and prepared transfers; core recipes | Stored/payload distinction; pointee access and trait-probed lifecycle; source construction/upcast adapters | [Source handles](results/prelower_source_handles_2026_10_19/README.md) and earlier [source storage](results/prelower_source_storage_2026_10_19/README.md) are bounded proofs. [Migrated lifecycle proof](results/prelower_source_lifecycle_2026_10_20/README.md) adds shared aliasing, unique moved identity, independent value copies, const access and five failure stages through the public path. No blanket pointer-wrapper coverage |
| Weak operations, object tests/casts | Existing prepared ownership/type-test decisions | Concrete weak creation/locking and object-test/cast adapters missing | Explicitly rejected in concrete mode; [rejection/recovery](results/prelower_operation_boundaries_2026_10_20/README.md). This does not reject all ownership storage |
| Nullable/Result, mixed/dynamic | Compiler-known identities with core recipes; existing presence/boxed/result facts | Representation demand exists; operation compatibility remains separate. Bounded nullable mutation proof exists | [Mutation evidence](results/prelower_concrete_mutations_2026_10_19/README.md) is not nested-carrier coverage. `take`, boxed extraction and runtime-backed truthiness/value probes explicitly reject. General composition, write-back and output remain unproved |
| Empty/nonempty initialization | Prepared factory/entry conversions; `CPP_Data_Construction` and `CPP_Initializer_Buffer` | Ordered entries and snapshots; factory constructs in final storage; existing inline/heap buffer policy | [Initializer proof](results/prelower_concrete_initializers_2026_10_19/README.md): throwing copies, order and matching counters. Accounting closure covers its initializers |
| Ordinary members and source calls | Prepared call/receiver/boundary facts; `CPP_Concrete_Members`, existing source emitters | Source member emission; demanded runtime void-returning nonvariadic members | [Member proof](results/prelower_members_2026_10_19/README.md). Runtime member value/reference results and variadics explicitly reject; source method results are a separate tested path; source-call transport rejects reference results, variadics and observation parameters |
| Index/read/write, append, unset, compound updates | Prepared place/keyed-path decisions; ordinary sequencing; `CPP_Concrete_Index` | Concrete overloads invoke native operations, preserving stored references and re-resolution | [Indexing](results/prelower_indexing_2026_10_19/README.md), [compound](results/prelower_concrete_compound_2026_10_19/README.md), accounting closure. No independent container semantics in engine |
| Iteration | Prepared iteration protocol; ordinary foreach lifetime/control flow; `CPP_Concrete_Iteration` | Concrete inline range/cursor/entry transport over substituted source | [Vector/hash/custom tray proof](results/prelower_concrete_iteration_2026_10_19/README.md); accounting closure. Unsubstituted adapter source explicitly rejects; broader mutation/provider protocols unproved |
| Binary operations, casts and mutation | Existing prepared decisions; native renderer reused by `CPP_Runtime_Operations` | Demand-driven forwarding with existing operand sequencing/result construction | [Binary](results/prelower_concrete_binary_2026_10_19/README.md), [casts](results/prelower_concrete_casts_2026_10_19/README.md), [mutation](results/prelower_concrete_mutations_2026_10_19/README.md), accounting closure. No exhaustive operator/type product or arbitrary ownership/provider conversions claim |
| Output | Prepared output admission, ordinary sequencing and runtime `echo_one` | Borrowed operand transport; one full expression per operand | [Output counters](results/prelower_concrete_output_2026_10_19/README.md), newer null proof and accounting closure. Nested wrapper formatters/provider composition unproved |
| Runtime free calls | Registered callable binding and prepared parameter/boundary/result facts | Owned results, ordinary value/reference arguments; single prepared observation path | [Runtime-call proof](results/prelower_concrete_runtime_calls_2026_10_19/README.md). Reference results/variadics reject; observation path rejects multiple inputs, conversions and parameter/argument count mismatch. Registration alone is insufficient |
| Callables, source exceptions, enum helpers, match fallback | Existing source facts and emitters | Concrete callback/exception/helper transport missing; explicit-default match remains supported | [Callable](results/prelower_callable_boundary_2026_10_20/README.md), [exception](results/prelower_exception_boundaries_2026_10_20/README.md), [value](results/prelower_value_boundaries_2026_10_20/README.md), [runtime](results/prelower_runtime_boundaries_2026_10_20/README.md) rejection/recovery proofs. Enum declarations/equality are not blanket rejected |
| Other module/provider types and transitive headers | Existing module catalogs and C++ bindings | Only matching established physical protocols; no family-specific engine extensions | Strict native builds validate active template tokens across transitive application inputs and reject broad runtime PCH; provider mutation/recovery is tested. Arbitrary provider operation compatibility remains **unproved**; valid metadata is not certification |

The [combined O0 acceptance](results/prelower_acceptance_2026_10_20/README.md)
freshly built and ran accounting in both modes (998/0 each), with complete 16-unit
closure validation. It does not execute every possible combination in this table. Focused native counter proofs
do not by themselves prove complete template-free input. O0 boundary parity is the
current target; recorded stock-O2 allocation-elision differences remain deferred.

### Contract checks and next enforcement boundary

`Runtime_Catalog_Reader` and `CPP_Runtime_Type_Bindings` validate recipe vocabulary,
targets, native argument mapping, stored/payload roles, pointee and literal/byte
interfaces. Candidate installation through `Compiler_Lifecycle` retains the installed
configuration on rejection. Clang validates provider implementations and probes native
layout/traits; valid JSON cannot prove those C++ implementations obey the protocol.
`concrete_boundary` selects representation, **not** transitive-header certification.

Fresh shared-runner checks on 2026-10-20: `runtime_type_bindings.php`,
`runtime_inventory.php`, `runtime_protocols.php`, `runtime_types.php`.
Results: `/tmp/prelower-coverage-contract-20261020/logs`. These are PHP contract
checks, not new native/closure or accounting execution evidence.

Follow-up implementation: the existing publication/native-build owners now carry and
enforce the concrete application policy, rejecting broad runtime PCH and active template
tokens in transitive application inputs before caching new objects. See the
[native contract](../../compiler/my-try/docs/architecture/native_cpp_build.md#dependencies-and-pch).
This closes the historical production-header-enforcement gap, not unproved
provider-operation coverage. Public option consolidation is now proved by
the [CLI/API fixture](results/prelower_public_options_2026_10_20/README.md).
Unified acceptance is implemented; remaining operation combinations stay open. No allowlist, parallel registry,
semantic re-analysis or ordinary-template fallback is introduced.

## Agreed ownership: extend the existing model

`vector` and `hash` already originate in runtime-owned metadata. Their definitions,
argument constraints, defaults and capability dependencies are installed in the
existing `Type_Registry` by `Runtime_Type_Registration`. The specialization engine
must consume those identities and facts; it must not register another copy.

| Information | Existing owner to reuse/extend | Boundary |
|---|---|---|
| Ordinary runtime family declarations | [Runtime module metadata](../../runtime/metadata/README.md), `types` records | One declaration per family, not rows for concrete applications |
| Canonical definitions/applications; formation constraints; registered capability propagation | [Type registry](../../compiler/my-try/03_parse/types/model.php) | The sole semantic type identity/interner; extend its vocabulary only for demonstrated semantic needs |
| Compiler-interpreted scalar, wrapper and ownership roles | [Type registration](../../compiler/my-try/03_parse/types/register.php) | Existing specialized definitions, not duplicate metadata identities |
| Source-derived capabilities and lifetime/operation decisions | Shared preparation; [prepared records](../../compiler/my-try/04_analyze/prepare/data/structures.php), `Capability_Preparation` | Consume completed facts; no backend inference or registry cache of source-derived facts |
| Read-only capability consumption | [Capability_Facts / Type_Queries](../../compiler/my-try/compiler/types/queries.php) | Registered recursive rules plus settled source facts; no scheduling |
| Runtime operations and generic signatures | Existing runtime callable/member/initialization/indexing/iteration contracts | Extend the owning operation contract, not a separate specialization method catalogue |
| C++ representation and invocation bindings | Existing `cpp_types`, `cpp`, `cpp_properties`, `cpp_initialization`; [CPP_Type_Bindings](../../compiler/my-try/05_backend/cpp/type_bindings_catalog.php) and callable bindings | Joined to existing definition/operation identities; C++ size/alignment/symbols are not language capabilities |
| Specialization requests and emitted artifacts | Existing C++ specialization engine, fragment requests, publication and native builder | Cache/output bookkeeping only; no independent admission rules or type discovery |

The existing backend binding maps are target-specific views of semantic identities,
not permission to introduce a parallel semantic registry. Metadata stays runtime-owned
and is loaded through the existing startup/configuration path, not rescanned per use.
Bindings remain structured names/contracts, not arbitrary executable C++ snippets.

## Current metadata audit and proposed next slice — `df056d90`

The [current audit](results/prelower_runtime_metadata_audit_2026_10_19/README.md)
finds 14 ordinary type declarations across eight runtime catalogs. Vector/hash are
the two metadata-declared templates. At that checkpoint compiler-known scalar, nullable/Result,
ownership, boxed and value-modifier representations still had PHP-installed C++
bindings. This is incomplete runtime metadata coverage, not missing semantic type
identities. The older matrix below remains the family review; the audit includes
`Exception`, subsequently added to core with shared storage and no payload-copy claim.

| Required information | Already available | Remaining gap / owner |
|---|---|---|
| Semantic identity, argument formation/defaults, copy/storage capabilities | Existing registry; JSON for ordinary types, compiler-interpreted definitions for language roles | Keep one identity and existing semantic-role owners; do not redeclare int/nullable/shared in ordinary `types` |
| Public member/free-function contracts and C++ names | Callable/member catalogs and bindings | Inventory omitted operations; do not mirror existing signatures in a specialization-method registry |
| Initialization, indexing and iteration | Registered protocols and settled source decisions | Keep these owners; connect physical adapters to their selected contracts |
| Concrete native symbol and include | `cpp_types` for ordinary definitions; PHP registration for compiler-known roles | Allow runtime-owned C++ bindings to attach to existing semantic identities |
| Native template shape and argument representation | Currently implicit in spelling, applied semantic type and ownership checks | Structured backend recipe: native symbol, explicit argument list, stored-value versus payload argument use |
| ABI size/alignment and lifecycle availability | Existing native layout/trait probes plus semantic copy permission | Probe only demanded concrete applications; do not duplicate host/target-dependent values in JSON |
| Required concrete operations | Prepared calls/operators/conversions and existing fragment requests | Type metadata alone cannot make echo/traits/member forwarding complete; consume these decisions, add only missing runtime interface contracts |
| Private helper-template closure | Native C++ instantiation in support units | Do not register every implementation helper as a source type; native dependency tracking owns their rebuilds |

### Representation-binding extension — first migration implemented

The [first migration proof](results/prelower_runtime_type_bindings_2026_10_19/README.md)
records the implemented schema, core binding migration and ordinary-output/native
checks. [Automatic preparation selection](results/prelower_runtime_template_demand_2026_10_19/README.md) now consumes the recipe for ordinary and nested uses. Implicit shared storage also
consumes the installed binding, retaining incomplete source-payload requirements.

Extend the existing `cpp_types` input and `CPP_Type_Bindings`, rather than create a
second registry. Preserve `id` joins for ordinary metadata types. Add an exclusive
reference to an already registered semantic type, or the existing value modifier,
for compiler-known bindings. The compiler still owns their language roles.

Bindings must distinguish a plain native type from a template application with
zero, fixed or semantic arguments. Each semantic argument states whether the native
provider consumes its stored representation or its payload representation. This is
physical C++ information, not a new source capability. Native fixed-width argument
names are validated qualified names; no arbitrary C++ snippets are accepted.

The implementation follows the [versioned metadata contract](../runtime_module_metadata.md#native-type-representation-recipes). Binding IDs are required; representative rows:

```json
{"id":"core.representation.int","type":"int", "symbol":"scpp::int_t", "header":"scpp/int_t.hpp",
 "arguments":[{"native":"std::int64_t"}]}

{"id":"core.representation.dynamic","type":"dynamic", "symbol":"scpp::dynamic_t", "header":"scpp/dynamic_t.hpp",
 "arguments":[]}

{"id":"core.representation.shared","type":"shared", "symbol":"scpp::shared_p", "header":"scpp/shared_p.hpp",
 "arguments":[{"parameter":"Target", "representation":"payload"}]}
```

Existing ordinary vector/hash bindings can retain their current shorthand: append
semantic arguments in declaration order using stored representation. The resolved
backend binding should expose the same structured recipe for both forms. Aliases
such as byte still share the existing canonical binding, never another declaration.
The value modifier needs the analogous reference to its modified type; ownership
storage policy and modifier normalization must remain unchanged.

First migrate representation descriptions and remove their equivalent PHP binding
rows only after ordinary emitted spellings match. Then make concrete selection and
nested substitution consume the resolved recipe. An explicit template application
is a preparation candidate; it is not an eager request. Missing recipes/interfaces
must be diagnosed, not silently treated as fully supported concrete output.

The user approved this coordinated extension after review. It crosses catalog
decoding/validation, installed C++ bindings and representation emission. The v1
contract now explicitly defines structured native arguments and existing-identity
targets; orphan rows and arbitrary source fragments remain rejected. Ordinary
`types` do not acquire fake language roles.

Validation must cover old catalog compatibility, duplicate/missing/invalid target
bindings, parameter references, default/native argument spelling, reset/replay,
ordinary-output equivalence, used-only nested demand, and native lifecycle/ABI
proofs for the changed representations. Complete operation/trait interoperability
remains subsequent capability-driven work; this slice alone cannot close accounting.

## Reading the matrix

Existing capability vocabulary is `value_storable` (S), `copyable_value` (C),
`hashable` (H), `comparable` (E), and `result_payload` (R). No `movable`,
`layout_known`, `abi_lowerable` or `trivially_copyable` capability currently exists
in this registry enum. Those words must not be presented as implemented facts.

Formation, storage and operation admission are separate. S does not imply C.
Copying a shared/weak handle does not require copying its pointee; unique storage
must not become copyable because its pointee is copyable. Source-derived capabilities
come from preparation, including recursively nested arguments.

Disposition labels below are proposed work order, not completed coverage or measured
cost rankings. Per-family compile cost remains unknown except for the aggregate
integer/vector first-slice experiment. Internal templates may remain in cached support
compilation; the target is to remove template exposure from generated application inputs.

## Source-visible families and compiler-interpreted runtime bindings

| Family / current registration | Existing argument and capability facts | Existing storage / operations to consume | Missing concrete binding work and proposed disposition |
|---|---|---|---|
| `vector<V>` — `core.vector`, runtime JSON | V requires S. Result declares S/C/R; C depends on V.C | Initialization, integer indexing, append, probes and value iteration are registered; count has an ordinary generic callable | General storage/lifecycle and operation bindings. Replace handwritten vector implementation with the shared forwarding mechanism. **First proof** |
| `hash<V,K=string>` — `core.hash`, runtime JSON | V requires S; K requires H/E; C depends on V.C and K.C; result declares S/C/R | Registered initialization/indexing/iteration; append for integer keys, removal, probes; generic count | Key hashing/equality bindings, ordered storage, key normalization, append policy and error behavior. **First cross-family proof** |
| `nullable<V>` — specialized runtime definition in type registration | V requires S; C depends on V.C; result declares S/C | Presence/extraction and conversion decisions already have semantic owners | Bind state construction, reset, extraction, destruction and conditional copy/move. **Next structural proof** |
| `result<V>`, `result_or_false<V>`, `result_or_bool<V>` — specialized runtime definitions | V requires S; C depends on V.C; explicit `result_kind` distinguishes branches | Prepared take/result operations; result payload admission also has its existing R checks | Bind payload/error/sentinel branches and lifecycle without collapsing them into truthiness. **Discuss with nullable** |
| `shared<T>`, `weak<T>`, `unique<T>` — specialized ownership definitions | Handle S; shared/weak C; unique lacks C. Target parameter has no generic formation-capability list; dedicated target validation exists | `ownership_kind`, record storage, borrowing/transfer/conversion facts and target identity | Bind retain/release, weak lock, destruction and unique transfer. **Early discussion**, needed for general storable coverage; do not invent raw-pointer semantics |
| `value<T>` — existing type-use modifier, not another template definition | Normalization uses existing type/storage facts; source class payload copyability is prepared | Canonical `by_value` flag and existing modifier binding to `value_p` | Bind payload copy/clone/lifecycle; preserve normalized identity. **Early discussion**; never create a second `value` family |
| Integer source types / runtime `int_t<Rep>` | Registered width/signedness; S/C/H/E/R. `byte` aliases uint8; ordinary int is registered separately | Prepared conversions/operators and integer literal facts; native Rep is a backend binding | General scalar representation/lifecycle/value-access bindings; Rep must derive from facts. **Keep included from the start**; remove handwritten family dispatch as the new path is proved |
| bool, float, string, error | Concrete registered identities. bool/float/string have S/C/H/E/R; error S/C | Existing literal, operator, conversion and error contracts | Audit transitive/member templates and primitive value access. `bool_t` includes detail machinery today. **Early exposure review**, not extra source template families |
| `mixed`, `dynamic` | Concrete language identities with S/C/R; independent boxed families | Existing prepared boxed operations, family-specific container/copy behavior | Bind tag/payload operations and internal table/ownership dependencies. `dynamic_t<>` currently accepts zero template arguments; neither source family means unrestricted `<T>`. **Early discussion**, before expanding accounting coverage |
| Source structs/classes/enums and callable values as arguments | Source identities and settled capabilities; callable definition declares S/C/R | Inline/shared/value storage; prepared fields, constructors, methods, signatures, capture/borrow facts | Reuse these as argument descriptors, never enumerate their names in configuration. Concrete callback/reference boundary bindings still need design. **Cross-cutting proof axis** |

The ownership Target parameter is not the same role as a container's stored Value.
Current shared/unique checks reject void targets and direct ownership nesting, with
additional provider/interface rules; they are not encoded as a generic S requirement.
Inventory this explicitly before consolidating constraints. Do not silently treat an
empty requirement list as permission for arbitrary source targets, nor replace the
existing target checks with a blanket S check that changes v0.2 behavior.

## Runtime metadata provider types

These are the remaining declared `types` rows in all eight current module JSON files.
They enter the same registry; optional modules are not automatically selected.

| Module / types | Declared facts | Specialization concern |
|---|---|---|
| filesystem: `file_lock_handle` | Shared storage; R | Generic shared/provider boundary; releasing the handle must preserve resource lifetime |
| json: `json_document`, `json_node`, `json_parse_error` | Shared storage; R | Shared ownership and property/call bindings; borrowed relationships cannot be copied as raw payload bytes |
| process: `process_handle`, `process_output` | Shared storage; R | Shared/provider boundary, output fields, process/resource lifetime |
| source: `source_buffer`, `source_line_index`, `source_location` | Structs; S/C | Inline provider values; existing opaque ownership/representation and return bindings |
| strings: `string_parts_builder`, `text_builder` | Structs; S/C | Inline lifecycle and existing builder operation bindings |

For shared runtime storage the registry supplies handle S/C behavior independently
of the provider payload's declared capabilities. Do not add repeated S/C declarations
solely to reproduce that rule. datetime and regex declare no `types` rows currently;
their callable signatures still introduce dependencies on existing types/wrappers.
Provider ABI conversion belongs to the existing C++ bindings, not a new semantic family.

## Internal templates and unregistered runtime surfaces

The [source discovery index](runtime_template_source_inventory_2026_10_19.md) covers
literal template syntax throughout `runtime/include`, including `.cpp` providers.
This table assigns review groups; it does not claim those templates are all visible
to my-try source or all need individually registered semantic identities.

| Runtime family / helper group | Current semantic owner or status | Generalization requirement / disposition |
|---|---|---|
| `initializer_list`, `initializer_entry`, initializer buffer/factories | [Initialization contracts](../../compiler/my-try/compiler/initialization/catalog.php) already register internal list/entry definitions; buffer/factory are implementation helpers | Use existing binding and prepared ordered entries; preserve partial-construction cleanup. **Early**, not another initializer registry |
| Index read/write/path, existing-slot operations, keyed probes/removal | Prepared indexing/keyed-path facts and runtime protocols | Describe selected operation signatures and bindings, including error/evaluation order and reentrancy. **Early**, necessary for accounting |
| Iteration ranges/cursors, typed iterator concepts | Existing iteration contracts; cursor types are mostly backend/runtime details | Concrete cursor/callback transport and lifetime rules; avoid exposing templated range helpers. **Early** |
| Arithmetic, comparisons/identity, casts, conditional selection, coalescing, take, count/empty/isset/unset | Existing prepared operators/conversions or registered callables | Forward the already selected operation with concrete signatures; register missing bindings at that operation owner. **Early** |
| `key_ops<K>`, hash indexes, mixed/dynamic tables and scalar references | Runtime-private algorithms and type-specific traits | H/E capability is insufficient to identify its C++ implementation. Current hash `key_ops<int_t<Rep>>` will not automatically match a new integer wrapper. Bind representation conversion or hash/equality explicitly. **Early boundary proof** |
| Memory/shared conversion, shared-self, object casts, memory accounting | Existing ownership/type identity plus private runtime helpers | Preserve ownership and identity; emit concrete access/transfer entry points. **Ownership review** |
| Callable adapters and `std::function`/closure transport | Existing callable signatures and capture/lifetime preparation | Concrete callback ABI, state destruction and reference results; standard-library dependencies stay behind support boundary where possible. **Discuss early** |
| `fixed_array_t<T,N>` | Runtime C++ surface; not a currently registered source template in my-try | Requires compile-time value argument N in addition to types; current application interner is type-argument based. **Discuss**, not implicitly enabled by this matrix |
| `row_arena_t<T,Id>`, `work_queue_t<T>` | Runtime C++ surface; not currently registered source families here | ID/storage policy, movement/concurrency and invalidation contracts. **Discuss**, registration needs an actual admitted source consumer |
| `async::task<T>`, promise/awaiter templates; tasks callback/result/publication helpers | Runtime implementations include callable, value and void-specialized forms beyond storable payload parameters | Preserve existing runtime semantics inside support; no automatic source exposure or blanket S restriction on helper template parameters. **Discuss separately** |
| Compiler `Storage`, `Keyed_Storage`, `Key_Storage_List`, cursors and traits | Runtime compiler-support surfaces, separate from ordinary application type catalog | Keep reference identity, holes/cursors and ownership behavior. **Discuss** for compiling compiler applications; do not import them into every source registry |
| JSON reader/writer/conversion, strings, tokenizer, filesystem snapshot helpers | Provider implementation and/or bound call surfaces | Audit actual header exposure and ABI; implementation-only templates can remain in cached provider objects. **Provider review** |
| Resource, PHP support/exception/string/value/take/mysqli, debug/var_dump, curl/webview helpers | Mixed current/legacy/module-specific runtime surfaces | Record admitted consumer and exposure before designing an adapter; retain privately where already isolated. **Review**, not automatic migration of legacy semantics |
| Standard-library templates and runtime trait machinery | C++ implementation dependencies; not source-language type definitions | Track transitive preprocessed exposure, not just generated text. **Review boundary**; do not duplicate the standard library in the semantic registry |

## Capability and binding gaps: proposed extensions at existing owners

| Need | Already available | Proposed action; avoid duplication |
|---|---|---|
| Storable formation | S and parameter `requires` | Reuse; distinguish Value, Key and ownership Target roles |
| Conditional copyability | C plus argument `capability_dependencies`, source facts | Reuse; remove the first slice's unconditional copyable-vector-element restriction only when operations/representation support the admitted case |
| Move, destroy and default construction | Ownership/constructor/destructor/transfer facts are distributed; no general enum capabilities for these | Inventory exact operation requirements first. Add semantic capability/query support to the existing model only where an operation needs a missing fact. S alone is not evidence that every such operation exists |
| Operation-specific requirements | Generic signatures, passing modes, member owners and structural container contracts | Extend those contracts to associate requirements with individual operations if needed. Do not require every method's capabilities just to form the type |
| Conditional result capabilities beyond current dependencies | Existing dependency propagation handles matching capability dependencies | Use it where sufficient; identify an actual unsupported rule before extending its representation. No second evaluator in specialization code |
| Native layout and representation | Storage category and backend spelling/header; no complete generic concrete-wrapper layout contract | Extend existing C++ type binding data with the selected representation contract, size/alignment provenance and lifecycle access as needed. ABI data is target-specific, not a duplicate semantic type or a semantic `layout_known` flag by default |
| Calling the original runtime implementation | Existing C++ symbols and member bindings | Extend existing bindings for construction/destruction, receiver access and concrete parameter/return transport. Capabilities do not supply function bodies |
| Nested types and provider ABI | Canonical nested applications, prepared conversions, provider bindings | Recursively request dependencies using existing identities; explicitly describe application/support representation conversion. Never assume equal size means interchangeable objects, or reinterpret/copy raw bytes from S/C alone |
| Internal implementation dependencies | Header/build dependency machinery, some compiler helper requests | Keep within generated support and native cache when hidden. Only semantic entities become registry definitions; no registry entry for each C++ trait/helper |

The initial generic engine should consume one assembled view of these existing facts
and bindings. That view may be an invocation-local descriptor; it must not own new
type identities, restate constraints, infer capabilities or retain preparation workers.
The compact specialization reference and retained canonical requests remain useful.

## General engine acceptance and next proof

1. Join each requested canonical identity to its existing definition, parameter facts,
   settled capabilities, selected operation contracts and existing C++ binding.
2. Use general mechanisms for native values, inline opaque storage or owning handles
   only where their explicit representation contracts justify them. No guessed layout,
   mandatory heap boxing or duplicate payload objects to make forwarding convenient.
3. Generate concrete application declarations and separately compiled forwarding
   implementations using the original runtime templates privately. Original runtime
   code owns internal helper instantiation. A facade must cover nested values,
   ownership and argument/result transport; wrapping the type name is insufficient.
4. Start with all **applicable admitted** operations; do not force invalid copy methods
   onto unique/noncopyable specializations. Selective-method optimization remains later.
5. Prove one mechanism with vector, hash and nullable/result plus scalar and ownership
   argument shapes. Test source structs with equal short names and nested applications.
   Runtime execution, error parity and ownership must pass alongside native timings.
6. Require that another runtime family using supported contracts needs metadata/binding
   rows and tests, not another family switch or handwritten implementation in the engine.
   A genuinely missing contract is extended at its existing owner before use.

The current `CPP_Concrete_Recipes::integer/vector` implementations remain the measured
baseline, not the pattern to multiply. This matrix authorizes no silent broad rewrite:
the exact representation/operation-binding extension is the next design slice.
No AST replacement, semantic rediscovery, new native cache or parallel type registry
is part of it.

[Decision](cpp_template_prelowering_decision_2026_10_19.md) ·
[Implementation plan](cpp_template_prelowering_plan_2026_10_19.md) ·
[First-slice evidence](results/prelower_first_slice_2026_10_19/README.md)
