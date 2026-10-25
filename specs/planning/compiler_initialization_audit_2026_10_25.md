# Remaining compiler initialization audit — 2026-10-25
Doc Status: planning

Baseline: 0f8f2703e. User requested batch audit, grouping and implementation order;
no fixes. Latest bootstrap stops during preparation at Model.global_scope; the
aggregate contains 85 initialization strings, not 85 independent fields. Earlier
failures mask later fields and equal names collapse across owners.

## Scope and evidence

Reviewed the current constructor bodies and the lifecycle/parser/metadata/fact
construction paths below. Supplemented that review with a lexical declaration scan
restricted to files in the latest bootstrap source manifest. The scan finds 331
uninitialized, non-scalar-looking declarations in 195 owners, including enum, weak,
provider and parked LLVM controls. It excludes promoted, nullable and explicitly
defaulted declarations. This is a source-shape inventory, not semantic rejection
analysis, and does not establish that every candidate is a blocker.

[Declaration triage](results/compiler_initialization_audit_2026_10_25/declarations.json)
and [counts/limitations](results/compiler_initialization_audit_2026_10_25/summary.json)
record file/line, owner, field, type, proposed group and constraints. The scan is
supplementary: constructor/producer behavior determines the final implementation.
54 direct-input candidates; 17 factory candidates plus 2 registry-order-sensitive
factory candidates; 15 clearly staged roots (12 Model + 3 Parser_Run); 156 remaining
staged-or-private-builder candidates; 28 enum-default-or-input candidates; 33
existing weak fields; 9 provider cursor fields; 17 parked LLVM declarations.
The last controls must not be mechanically converted to strong nullable storage.
The 156 candidate fields need producer-by-producer checks during their owner slice;
this report does not claim an exhaustive escape/publication proof for every one.

## Can we just assign a default?

Yes when it is a valid value or an explicitly checked internal absence state:

- int/bool/string and genuine value records can use their supported defaults.
- Existing enum cases can be explicit defaults when the stored tag is initialized
  before meaningful observation. Do not invent an unset case. Tags already supplied
  as constructor arguments should normally be promoted instead. Missing-required-JSON
  checks must remain even if an enum gets a default.
- Empty collections are valid when the owning algorithm begins with no entries.
  Use the existing registry/provider construction contract; PHP object-backed
  Storage must not be replaced with a PHP array merely because both look empty.
- Required class fields cannot use public T = null. Public ?T = null changes the
  contract and exposes absence to every consumer. Private ?T = null plus a required
  checked accessor preserves the consumer contract and fails on premature reads.
- A new scope/type/AST/fact object is not a neutral default: it may have the wrong
  identity, registry membership or semantic meaning. canonical_type_use requires
  a valid registered identity; fabricating one is especially incorrect.
- PHP property defaults and v0.2 static defaults do not provide a general portable
  new-object initializer. For legitimate fresh objects, construct them through an
  explicit factory and pass them to promoted inputs.

Using private null backing everywhere is technically a possible source strategy
for strong object fields, but it would introduce unnecessary incomplete states,
accessor traffic and maintenance work for values already available at construction.
Use the three groups below by ownership, not as a blanket textual rewrite.

## A — Constructor inputs already available

| Owner family | Fields / evidence | Recommendation and boundary |
| --- | --- | --- |
| Native build workers | Native_CPP_Build.config; Native_Ninja_Build.config | Promote existing arguments; preserve validation and filesystem work in order. |
| Preparation workers and work records | Preparation_Worker.language; preparation_owner.source; signature/constant/enum/record work definition fields | Promote existing inputs. Derived constructors keep leading parent calls and the established no-child-observation contract. Explicit weak fields stay weak. |
| Registry identity/configuration records | Namespace_Registry.language; source_type_modifier_exposure.modifier_kind; runtime_type_provider.provider_identity; semantic_type_definition.definition_origin; floating/nominal/template/result/ownership kind fields | Promote supplied values, including enum arguments. Keep parameter names where callers may depend on them; use explicit factory/input adaptation when property names differ. |
| Registry catalog | registered_type_catalog type_registry, source_exposures, runtime_providers, null/false/true marker inputs | Existing parameters already carry the exact canonical objects. Its two allocated indexes belong in B. |
| Runtime callable/binding records | registered_function.call_contract; registered_generic_function.pattern_contract; registered_static_factory.owner_type/call_contract; runtime_boxed_result_requirement.family_type; cpp bindings semantic_target/native_recipe; cpp_artifact.artifact_kind | Pass the same semantic identity/recipe. No new allocation or copy. |
| Preparation helpers | reference_cycle_vertex.target; preparation_lookup.lookup_scope/kind; callable_instance.parsed/working; trait_instance.origin/parsed | Direct inputs. Keep weak backlinks and derived collection links separate. |
| Parser already-parsed inputs | template_application_type_node.definition; binary left; assignment target; coalesce left; conditional condition | Source values exist at allocation. Promote those inputs only; RHS/fallback and other later children remain staged or need a separately audited complete builder. |
| Compact decisions | conversion_decision source_type/target_type/result_type/context/operation | Conversion_Preparation.operation already receives every value and fills a private result immediately. Promote the inputs via that existing owner. Audit other construction sites before consolidation. |

Representative evidence: parser.php:1791–1815 and 2707 onward; conversions/preparation.php
operation(); prepare/data/work_records.php constructors; types/model.php and catalog.php
constructors; compiler/callables/structures.php; cpp binding constructors.

Workload: small per owner, medium for the batch. Mostly signatures/call sites and
focused cross-route proofs. No new Simple C++ contract.

## B — Factory-created or selected inputs

| Owner family | Fields | Recommendation and boundary |
| --- | --- | --- |
| AST-owned helper objects | type_declaration_node.owned_member_scope; namespace_body_node.body | Existing constructors create fresh local objects. Fresh factories can construct the same scope/body, pass it in, then preserve span/setup order. Do not replace an existing retained scope. |
| Runtime/catalog setup | Runtime_Catalog_Loader.input; runtime_module_spec.cpp_build; Type_Registry.application_index; registered_type_catalog.closure_index/source_declaration_index | Fresh objects are legitimate. Factories supply them; empty data containers inside these objects remain valid defaults. |
| Callable signatures | prepared_source_callable.signature; callable_type_definition.call_contract | Build/preserve the signature through its existing owner. Resolve callable_signature.return_type staging first or in the same bounded source slice. No dummy return type. |
| Optional constructor inputs | Compiler.cpp_lowering; CPP_Generator.program/policy | Preserve caller-supplied objects; allocate defaults only for omitted/null inputs. Keep prelowering mode checks and fragment invalidation unchanged. |
| Preparation-local helpers | Preparation_Worker.materialization_signal; local_environment.region | Preserve signal identity and parent-region/entered-at calculation. A factory must perform calculations in the same order. |
| Native output resources | Native_CPP_Build.report; Output_Publication.reservation | Report creation is local. Reservation construction/acquisition and release-on-error need focused lifetime proof; no eager acquisition or empty fake reservation. |
| Registry-generated definitions | Initialization_Contracts.sequence_definition/entry_definition | Calls mutate the registry and establish two identities in order. Use an ordered factory through the existing owner, or checked staging if moving creation would alter publication. Not independent argument expressions with unspecified ordering. |

Workload: small for fresh pure objects, medium for callable signature dependencies,
and medium-to-large for resource/registry side effects. Stop if a factory demands
cross-owner redesign. This grouping is not permission to reorder effects.

## C — Staged storage with required checked access

| Owner family | Fields / flow | Recommendation |
| --- | --- | --- |
| Model lifecycle roots | global_scope, namespaces, language_scope, type_catalog, cpp_type_bindings, runtime_callables, runtime_members, initialization_contracts, indexing_contracts, iteration_contracts, cpp_callable_bindings, cpp_output_program | Same pattern as runtime_metadata: private nullable storage, required getters, assignments remain at the existing reset/publication points. Audit reset failure/retry and lifetime, not just first initialization. Do not clear old values earlier as an incidental change. |
| Parser_Run setup | source_scope, parsed, collector | Scope selection, previous-root revival, collection reuse, owned-scope insertion and revision selection occur in a deliberate sequence. Preserve the body and assignment points; checked access is smaller than extracting a whole setup transaction. |
| Parser-created syntax | switch/match selectors; match arm result; switch group body; if/loop/try/catch bodies; callable result type; object-construction target type; RHS, fallback, operands and other later children | Nodes often exist before recursive parsing completes. Preserve collection/parse order with checked backing. A complete private builder may be better for an individual node only after proving it is unobserved until complete. Do not move collection to justify promotion. |
| Metadata decoding | runtime_initialization_spec value/nested; iteration/indexing key/value; runtime parameter/signature types; property owner/type; callable pattern/generic return types | JSON property order is arbitrary. Existing seen flags and missing-field rejection remain. Checked staged records are straightforward; collecting locals and building once at the end is an optional owner-local alternative, not a converter requirement. |
| Prepared facts | prepared_expression.type; binding/parameter/field facts; prepared_call signature/type; conversion/operator/adaptation details; enum storage after case scan | Stage owners fill these facts over several steps. Start with conservative checked backing, or promote complete private results through existing builders. Inherited fields and mutable speculative facts require all consumers to be reviewed. Do not change preparation scheduling, publication or cleanup protocols. |

Important mixed case: prepared_call.target is already weak; signature/type are
required strong values. Do not infer all fields of one record have the same storage
contract. prepared_enum.type is computed early but its storage depends on scanning
cases. One constructor strategy need not apply to both.

Workload: Model and Parser_Run are medium source adaptations. AST families are
medium in bounded groups. Prepared-fact base fields/metadata are the largest volume
(roughly 5–10 small promotion slices, possibly more), because readers and producers
are spread across owners. This is source API volume, not evidence that a new semantic
or lifetime protocol is necessary. Do not implement a new fact-builder framework by
accident; discuss if existing owners cannot accommodate a bounded slice.

## Defaults, provider controls and parked code

The declaration scan includes 28 enum fields not already classified as direct inputs.
Give them explicit existing defaults only when observing the default is legitimate
or forbidden until the authoritative assignment. Operations/tags in partially built
facts must retain their publication guards. Prefer promotion where the operation is
already known. Scalar/default-constructed struct declarations are not part of this
required-object inventory.

33 weak-annotated declarations already have an inherently empty native ownership
form; absence of a PHP initializer does not establish this bootstrap error. 9 cursor
fields require their provider default/construction metadata to be consulted; they
are not ordinary nominal shared objects. No Storage_Cursor widening is proposed.
The current conversion manifest includes 17 parked LLVM candidate declarations.
Inventory them separately; do not silently resume LLVM development or remove it from
the bootstrap source set to claim success. Required route-scope decisions are separate.

## Recommended implementation order

1. Model required static roots as one lifecycle-owned batch. It addresses the first
   blocker and reuses the just-proved runtime_metadata pattern; prove reset/retry.
2. Direct constructor inputs by owner family: registry/binding records, work records,
   native configs and already-parsed AST inputs. Enum constructor inputs join their
   owner batch. Parent-constructor rules remain in force.
3. Pure factory-created inputs: scope/body helpers, registry indexes, metadata input
   and build-spec records, then optional compiler/generator defaults.
4. Parser_Run's three staged fields, then AST staged fields grouped by syntax family.
   Preserve no-op identities, malformed-input recovery and deletion/reappearance.
5. Metadata readers and prepared facts in dependency order: type/parameter/signature
   records, compact conversions, inherited expression facts, then specialized call/
   control/enum facts. Reuse existing builders where complete inputs are available.
6. Ordered registry/resource factories after their dependencies: registration ordering,
   output lock acquisition/failure cleanup and native report construction.
7. Resolve parked-LLVM scope separately if its included source blocks the required
   bootstrap. Do not mistake a lexical audit control for an authorized LLVM task.

At each batch: focused PHP and legacy/ordinary/concrete native proofs, then measured
bootstrap. Stop for a new semantic/lifetime protocol or broad cross-owner redesign.
This order is a recommendation for discussion, not implementation authorization.

No source fixes, new probes, bootstrap rerun or language changes made by this audit.
The previous measured preparation failure remains the execution baseline. CH15
registry audit stays separate and blocking for final catalog closure.
