# Incremental and v0.2 follow-up plan
Doc Status: planning

The incremental path is implemented through C++ fragment generation. Current
behavior belongs to [lifecycle](../lifecycle/incremental.md), [model](../architecture/MODEL.md)
and [preparation](../../04_analyze/prepare/README.md). The
[original discussion](../archive/incremental_strategy_history.md) is historical;
its superseded proposals and completed tasks are not an implementation backlog.

## Multithreading and incremental correctness

The active MT design, agreed stage boundaries, shared-write inventory, proposed
collection mechanisms and canonical type publication options belong to the
[multithreading plan](multithreading.md). Implementation remains unauthorized.

Incremental constraints on that design remain: preserve declaration/body work
identities and effective-change invalidation; file batching must not widen body-only
edits into whole-file invalidation. Missing/ambiguous lookup dependencies, deletion
notification before unlinking, and failure/recovery retry obligations must survive.
Parse/collect must join before semantic lookup, and affected declarations must settle
before parallel bodies and fragment rendering; the MT plan records these as agreed
boundaries rather than open questions.

## Completed bounded abstract-property review (2026-09-30)

Reviewed retained fields, collection elements/keys and trait fields in input,
tokenization, parsing, collection, preparation, C++ generation and coordinator data
at `e9e826cf`. Followed producers and consumers; transient worker fields were checked
for accidental retention. Parked LLVM and future language structures were excluded.
No architecture blocker was found. This review does not prove complete lifetime safety
or approve a property redesign. Nullable concrete facts remain absence, not polymorphism.

| Boundary | Actual roles and ownership | Decision |
| --- | --- | --- |
| AST declarations/statements | File owns functions/structs; bodies and blocks own executable statement variants. | Keep `declaration_node` / `statement_node` collections. Bodies remain processing units. |
| AST expressions/targets/types | Named fields own expression variants; assignment targets admit variables, fields and indexes; type fields admit named and array syntax. Optional return expressions/initializers represent absence. | Keep `expression_node`, `assignable_expression_node`, `type_node`; parser coverage and preparation support are separate. |
| Inspection cursors | Iterator retains its typed source/cursor and optional current `ast_node`, across heterogeneous children. | Keep broad inspection view; no worker stored in syntax. |
| `Collected_Occurrence` trait | Optional weak backlink before collection/after retirement. Roles follow the node: functions, structs, fields, parameters, explicit variables, references and writes. A variable-reference syntax can have read or write occurrence. | Keep shared trait; concrete collected records already retain concrete syntax fields. No per-node duplicate registry or trait specialization. |
| `collected_file.entries`, scope variables | File owns heterogeneous declarations/references/writes; scope indexes refer to relevant entries. Variable pool contains fields, parameters and explicit variables. | Keep current common identity boundary. `Key_Storage_List` indexes currently retain handles; documentary weak intent does not implement weak storage. |
| Prepared storage/reference declarations | Required weak links to explicit variables, parameters, fields or inferred first writes. First writes extend `collected_name`, not `collected_declaration`. | Keep `collected_name`; narrowing to declarations would exclude valid storage identities. Concrete expression facts and call targets are already specialized. |
| `preparation_context.locals` | Invocation-local aliases of binding and parameter facts through `prepared_storage`; retained facts never own the context. | Keep common storage capability. |
| Definition/body work | Definitions hold optional function-signature/record work; body nodes hold optional function-body/file-body work. Dependencies hold declaration work; dependents, change handoffs and backend keys admit all work roles. | Keep `declaration_work`, `body_work`, `preparation_owner`; shared bookkeeping is intentional. Strong graph links use explicit unlinking. |
| Concrete roots/metadata | Source, parsed-file root/scopes, canonical type definitions and specialized facts already use concrete records. | No additional abstract-field specialization needed. |

The three local narrowing follow-ups are now implemented:

1. `scope.functions` and function lookup snapshots use `collected_function`.
   Registration/removal narrow at the existing base-identity boundary. Duplicate
   names remain separate; combined declaration inventories still use `collected_name`.
2. `preparation_lookup.candidates` uses `collected_definition`. Typed function and
   source-struct snapshots project individual handles into that common vector,
   preserving order, identity and duplicates without assuming container covariance.
   Empty pools still represent missing names; built-ins remain separate type metadata.
3. `cpp_fragment.records` and `assemble_record()` use `collected_struct`.
   Dependency order and cached fragment ownership are unchanged. Focused incremental
   C++ coverage checks duplicate dependencies, exact identities and removal after edits.

Parked LLVM consumers only adapt typed locals and compare scalar pool counts rather
than combining different vector types in a ternary. No LLVM semantics or runtime
collection capabilities changed. Revisit permitted roles when adding declarations,
call targets or type forms; these follow-ups no longer block or precede feature work.
The broader [inheritance-contract review](../catalog/11_inheritance.md#v02-contract-review-debt)
remains attached to that language chapter.

## Scheduled review — type and conversion scaling (2026-10-01)

Review the type model before adding another substantial type or explicit-cast family.
The goal is to prevent two forms of combinatorial growth:

1. one new AST/facts structure for every semantic type; and
2. one bespoke preparation/backend branch for every source-type × destination-type
   cast or implicit-conversion pair.

The review must trace the current path through `type_node`, `named_type_node`,
`array_type_node`, `type_definition`, prepared expression/binding facts,
`Type_Preparation::require_assignable()` and `CPP_Declarations::convert()`. Separate
these responsibilities explicitly:

- **Source syntax:** nodes represent genuinely different grammar and owned operands,
  not each canonical type identity. A future explicit cast should normally be one
  cast-expression shape carrying target type syntax plus its operand, rather than a
  node class per target type. This does not prejudge whether literal forms with
  genuinely different lexical/value data should share a node.
- **Canonical type meaning:** resolved types and constructed type applications need
  one composable semantic representation. Review whether extending `type_kind` and
  adding fields remains truthful, when a type constructor/application is needed, and
  which distinctions are identity, representation or capability.
- **Conversion policy:** preparation must be the single semantic owner for identity,
  implicit conversion, explicit cast and rejection. Explore a small conversion
  classification/plan derived from type families and properties instead of scattered
  pairwise cases. It must preserve direction, narrowing/range behavior, explicitness,
  runtime checks, value/reference behavior and diagnostics; a generic table must not
  erase real exceptions.
- **Lowering:** C++ generation consumes the prepared conversion decision and chooses
  only its target spelling/helper. It must not rediscover compatibility from AST
  subclasses, source tokens or an independent source/destination matrix.

Required output is a bounded design note with the current model, pressure points,
candidate ownership shape, migration boundary and representative proofs. Exercise at
least identity, existing integer-family conversion, a rejected cross-family implicit
conversion and representative explicit casts; include nullable/wrapper, record and
container boundaries as future pressure tests without implementing them speculatively.
State how calls, returns, assignments and operators reuse the same conversion owner.

Non-goals for the review: no LLVM semantic work, no STAN resumption, no universal
runtime variant, no flattening of genuinely different grammar nodes, and no broad
type-system refactor before the ownership decision is agreed. If the truthful model
requires changes across parser, canonical types, preparation and lowering, present
the options and validation cost before implementation.

## Remaining incremental work

| Area | Deferred work / boundary |
| --- | --- |
| Change detection | mtime + size can miss equal-size edits with unchanged timestamps; notifications force reads. Stronger detection is deferred. |
| Error classification | Separate internal `RuntimeException` bugs from expected source diagnostics. Unexpected escaping exceptions already force a rebuild. |
| Error continuation | Review read/lexical failures and downstream boundaries case by case. Parsing already finishes other files then blocks preparation after join errors. |
| Lifetime | Explicit reverse unlinking is implemented. Review broader AST/scope cycles, obsolete source/module tombstones and strong observers; weak dependency storage is not required now. |
| Compaction | Sparse occurrence and duplicate-key positions grow without reuse; compact only with an observer/identity policy. |
| Token buffers | Appending and deferred remapping are implemented. Review cleanup scheduling, retained memory under repeated failures and latency after measurement. |
| Work selection | Selection still scans declarations and observed lookup pools. Consider changed-only inputs without losing missing/ambiguous-name dependencies. |
| Invalidation | Refine conservative record invalidation only after the existing behavior is well tested. |
| Output | Partition retained fragments into headers/implementation units, independent of source-file boundaries; preserve stable names and content-based publication. Current output is one `main.cpp`, without a disk writer/Ninja integration. |
| Validation | Rigorous incremental and native testing remains deferred. Focused tests do not establish exhaustive correctness or performance. |

For the later validation pass, compare fresh/incremental output and retained identities
across no-op notifications, moved declarations, body/signature/layout edits, missing
and ambiguous lookups, deletion/reappearance, failure/recovery and repeated increments.
Measure scanned/parsed files, prepared work and rerendered fragments separately from
output equality. Native concurrency/lifetime behavior requires native evidence.

## C++ incremental follow-up

Deferred after the backend review (2026-09-29):

1. Replace full fragment-selection scans with explicit pending work from
   `preparation_changes`. Preserve scheduling for initial generation, cache recreation,
   missing fragments and failed-render retries, even without new semantic changes.
   Keep the current single-file output layout for this step.
2. Introduce retained output units with fragment membership, output/include dependencies
   and per-unit assembly/publication state. Rebuild only affected units; preserve
   unchanged file bytes/timestamps and remove obsolete generated files. Semantic
   dependency links do not replace output-unit dependencies. Partitioning and the
   disk writer are later implementation, not part of the current cache.

## Agreed debts

- Keep general validation/STAN and reserved-name enforcement in the later pass.
  Ordinary parent lookup currently reaches LANGUAGE+RUNTIME without a special
  reserved-name rule; this is not unrestricted-shadowing language policy.
- Templates await review. Add explicit `use` captures to normal functions/methods
  in a future slice aligned with lambda capture semantics; retain the
  [v0.2 catalog item](../catalog/04_functions.md#deferred-v02-planning-explicit-captures-for-functions-and-methods).
- Reconcile nested declaration relationships such as extends/implements as their
  syntax arrives. Preserve file execution/namespace ordering rather than assuming
  all future declarations can always be grouped without consequence.
- Extend calls with typed target/argument forms when indirect/member/static calls
  arrive; add keyed container entries and append targets when needed. Workers must
  model lazy evaluation and distinguish assignable syntax from reference eligibility.
  The [expression](../archive/expression_model_audit.md) and
  [non-expression](../archive/non_expression_structure_audit.md) audits retain the
  detailed capability inventories; their migration prerequisites are historical.
- Review punctuation representation, inspection allocation, dispatch ordering versus
  lookup tables, native devirtualization and memory layout as optimization work.
- Keep narrowing `object_cast` even after matching type/kind guards for the current
  toolchain; revisit with the new S2S lowering. Traits currently group repeated
  helpers; reevaluate only when a better concrete representation is needed.
- Parked LLVM token-indexed name/template maps remain debt. Do not consolidate them
  now or add semantics independently; adapt LLVM to shared facts before resuming it.

Missing catalog kinds do not require speculative AST classes today. Each new slice
must preserve typed ownership, stable names, operation-specific traversal and the
single incremental processing path. The catalog remains the feature discussion queue.
