# Compiler portability: feasibility, workload and implementation order
Doc Status: planning

Review date: 2026-10-23. Documentation/source inspection only; no implementation,
bootstrap, new native proof or performance measurements in this review.

Owners: [accepted decisions](compiler_portability_decisions_2026_10_23.md),
[reconciled historical audit](compiler_portability_v01_decision_audit_2026_10_23.md).
Current normative specifications still apply until amended with the accepted slices.
This review covers every row in the portability backlog, plus its associated debts;
it does not estimate unrelated entries elsewhere in Chapter 15.

## Reading the estimates

1x is one small, already-understood runtime binding/source adaptation with focused
proofs. These are relative work bands, not days, deadlines or sums. They include
relevant documentation, source/native/converter integration and ordinary/prelowered
proofs. Shared foundations mean the rows overlap. Source adaptation counts and
unknown dependency failures can change the bands; no native performance forecast
follows from an implementation-volume estimate.

All accepted bounded directions are technically feasible in principle. Feasible
is not equivalent to current support, a proven native bootstrap, or permission to
expand a slice. The heaviest work is model/contract integration, not syntax parsing.

## Per-entry assessment

| Entry | Feasibility and implementation owner | Relative volume | Change from current Simple C++ contract |
| --- | --- | --- | --- |
| CH15-RUNTIME-TYPE-METADATA-01 registry audit | First-class blocking work: inventory and classify all compiler/my-try type consumers, then repair findings by owner. Feasible audit; repair scope depends on evidence. | 5–10x discovery; remediation unknown, potentially 20–50x+ | Architectural enforcement, not a new language feature. Final-catalog blocker. Start discovery before capability/nullable implementation; do not mark closed after inventory alone. |
| SELF-01 class self | Ordinary owner-retention reuse passes; prelowering rejects the inherited template-bearing shared_self header. Requires a template-free observer facade plus shared allocation/adoption registration, awaiting wide-refactor approval. See shared_self_reuse evidence. | Previous 4–8x local estimate excludes the now-proven runtime redesign; larger workload, re-estimate after state/adoption design audit | Extend current receiver/borrowed-capture-only `$this` admission to owning assignment/argument/return values. No inline/unique promotion. |
| SELF-02 struct self | Feasible through receiver places and normal copy/value conversion; prelowering must preserve original storage for mutation. Shared retention excluded. | 3–6x | Allow struct self values at copyable value boundaries; current methods contract excludes copying/passing/returning self. Historical v0.1 struct-method scope remains narrower. |
| COPY-01 portable copies | Feasible for the bounded explicit protocol, first scalar cpp_specialization_ref proof. Converter must preserve single receiver evaluation and independent value semantics; v0.1 uses helper/value lowering. | 3–6x | Portable authoring/conversion contract only. No native _struct_ interface or magic _struct_copy_ method. Arbitrary deep-copy or reference-consuming contexts excluded. |
| INIT-01 constructor promotion/defaults | Feasible in structural converter: direct promotion maps to existing initialization, computed assignments keep honest defaults. Native contracts already own ordering. | 2–4x | No new core construction semantics. Portable promotion mapping and explicit default discipline; required-class enforcement is accounted under NULL-01/LOCAL-01. |
| LOCAL-01 definite assignment | Feasible by extending existing Body_Preparation/Completion_Preparation and statement_completion with per-local assignment facts. Reuse sequence/alternative/loop/switch/finally composition, not a second AST traversal. Read/write tracking and execution-order/exception joins remain new. No heap alias or whole-program analysis. | 6–10x | Strengthen required-class local read admission while keeping unused declarations legal. Preserve struct defaults and direct initialization when no default exists. This is not just default-null emission. |
| INIT-02 staged fields | Feasible in the bounded converter-recognizable field subset, or explicit source accessors. Use admitted nullable backing, checked reads/writes and explicit struct copies. Historical struct field restrictions require a route-safe representation. | 6–12x (retained after reuse audit) | Portable staged-required-field guarantee; no new native uninitialized feature. Nullable/core work is a dependency, not reimplemented here. Arbitrary alias/property-chain inference could make this 20x+ and is excluded. |
| TASK-01 shared joined tasks | Feasible reusing executor, publication mutex, join/error protocol and callback transport. Add API-scoped shared admission and policy-preserving erased callable permission; current single bool is insufficient. No global race analysis. | 8–12x (revised reuse audit) | Bounded extension of shared object admission for the agreed API; synchronize shared mutations manually. Not a global relaxation of task transfer capabilities. |
| TASK-02 lookup locking | Feasible source synchronization repair at retained-type lookup using the existing publication mutex; audit nested lock/caller context and incremental use. | 2–4x | No language change. Correct the compiler implementation's shared access discipline before native concurrent bootstrap. |
| AST-01 explicit virtual defaults | Feasible with existing method-contract/dispatch owners plus PHP attribute conversion and v0.1 cue handling. Verify inherited slot reuse, default bodies and template dispatch. | 4–8x | Add explicit Virtual to concrete methods; preserve Override intent and existing access/signature rules. No implicit virtualization of all methods. |
| NULL-01 required vs optional | Feasible cross-owner integration: reuse nullable applications, interning, retained comparisons, member/ref/override owners. Main changes are presence/ownership composition and physical wrapper transport. Bounded design review remains required. | 12–20x (revised after consumer audit) | Replace current Node/?Node semantic equivalence. Reject known null at required boundaries; check optional-to-required values; invariant refs and directional value overrides; required constructor state. Same native pointer representation remains. |
| NULL-02 capability nullable | Feasible using existing generic formation, recursive capability dependencies, optional runtime storage and nullable-record proofs. Migrate operation gates/presence/take/selections without adding truthiness or borrowing. | 4–8x incremental after shared NULL-01 model (revised audit) | Generalize supported nullable compositions beyond current family gates. No universal copyability, no new borrow permission and no automatic admission of unrelated source constructs. |
| OWN-01 handle boundaries | Feasible sharing NULL-01 type/conversion work and existing weak/unique operations. Verify empty/moved-from state, weak lock results and honest runtime guarantees. | 3–6x incremental to NULL-01 | Reject redundant ?shared/?unique/?weak rather than normalize; explicit handles remain empty-capable. Required class names differ from optional ones. No move invalidation or lifetime emulation. |
| OWN-DEBT-01 weak parity | Deferred; no implementation of exact expiration parity. Targeted source cleanup is feasible if a concrete need appears. | 1–3x investigation; implementation unbounded until need known | Portability does not promise identical cleanup/expiration timing. Do not remove required identity/error/resource behavior. Not a new bootstrap gate. |
| SOURCE-01 private fields + numeric sites | Private instance fields fit per-owner layouts/declaration identity; change collision admission and verify static lookup/access/emission. Numeric work is per actual boundary, not mechanical checking. | 3–6x private fields; 1–3x initial numeric audit, fixes site-dependent | Relax inherited private-field collision rejection; C++ lookup then access, no fallback. Portable explicit base views preserve common behavior. No new numeric policy; native overflow contracts remain intact. |
| CONVERT-01 empty construction/defaults | Feasible reusing metadata initialization, property construction defaults already carried by synthesized parameters, and call-local omitted arguments. Generalize authored empty-default admission and converter output; bind missing container empty factories. | 4–8x (revised reuse audit) | Registered containers admit typed [] and parameter defaults by the same rule. Generalizes current opt-in construction/default support; no implicit mixed fallback, no arbitrary record admission or per-family list. |
| CONVERT-02 nullable annotations | Feasible structural consistency checks and source migration; no global inference. Full execution waits for nullable contracts. | 1–3x | Portable convention changes to complete ?vector/etc comments matching ?array. No independent native type change. |
| BIND-01 runtime exposure | Feasible: existing scalar/Result leaves, small fresh shared-reader factory plus v0.1/v0.2 bindings and native facade change. Trace existing helper exports before inventing bindings. | 2–4x output APIs; 3–6x reader; 1–3x facade triage, unknown fixes separate | API/metadata exposure, not a new core lifetime contract. Preserve two-step start and failure/absence/unchanged distinctions. Generic shared-provider new deferred. |
| CURSOR-01 safe read widening | Feasible via a reusable relation between constructor owner T and inferred source U, registered call preparation, existing ancestry/ownership checking and native constructor binding. | 5–10x | Add binding constraint expressiveness for an existing runtime constructor. No mutable Storage covariance or conversion between existing cursor<U>/cursor<T> values. Missing-contract diagnostic required. |
| VERIFY-01 measured bootstrap | Feasible validation process. Replay changed blockers, then report conversion/preparation/native stage, elapsed time and peak memory. New blockers stay separately classified. | 2–5x setup/triage; repair volume unknown | None. A successful conversion is not a self-host proof; a PHP bootstrap is not a rebuilt native compiler proof. |
| DOC-01 guidance reconciliation | Feasible as part of each slice, followed by final sweep. Preserve dated evidence and distinguish accepted/planned/current. | 2–4x final sweep; per-slice updates included above | No independent feature. Publish accepted amendments in normative owners and migrate portable instructions when behavior changes. |

## Associated debt details and scope limits

| Debt | Feasibility / relative volume | Contract significance / timing |
| --- | --- | --- |
| CH15-RUNTIME-TYPE-METADATA-01 | 5–10x inventory and classification; remediation unknown until findings are grouped by owner. Broad repair could reach 20–50x+. | Architectural final-catalog blocker, not permission to redefine legitimate intrinsic roles. Start discovery early; repair relevant consumers with their slices; inventory LLVM without implementing it. |
| SELF-DEBT-01 shared struct self retention | Likely feasible only for an existing shared owner; 2–4x review, implementation not estimated without a consumer/operation. | Deferred new owning-self operation, distinct from struct copies. |
| CH15-SHARED-PROVIDER-CONSTRUCTION-01 | Feasible direction; 2–4x review, roughly 4–8x bounded implementation if existing factory model can generalize. | Deferred generic runtime new support; does not gate accepted reader factory. Estimate is conditional, not implementation authorization. |
| CH15-READONLY-FIELDS-01 | Technically feasible; 2–4x scope review, implementation unestimated until shallow/deep and construction/mutation boundaries are fixed. | Deferred language enforcement, not implicitly resumed by private-field work. |
| Wider INIT-02 operations | Possible with restrictions/source adaptations; general transparent rewriting can become 20–50x+ and conflict with the structural converter boundary. | Deferred nested mutation, alias/ref and dynamic-property behavior; no global PHP inference approved. |

## Contract deltas that must not be called mere bug fixes

- `$this` value admission (class and struct), concrete explicitly virtual bodies,
  bounded shared-task admission and private-field redeclaration expand current scope.
- Node/?Node identity, redundant ownership nullable spellings and definite assignment
  deliberately change source admission. Historical non-null intent supports the
  direction but does not prove prior enforcement. Current null handle defaults need
  reconciliation with required Node, not a blanket removal from all handle types.
- Generic empty container construction/defaults expand admitted metadata/default
  contracts. Typed [] already has named vector/hash precedent; no need for a second
  data notation parser. Runtime metadata defaults have a narrower scalar schema;
  extending provider-declared defaults is a separate integration surface to identify,
  not something proved by source parameter support. Do not quietly broaden to
  arbitrary expression defaults or reference defaults.
- CURSOR adds a declarative relation checked by core type machinery. Metadata cannot
  grant an unsafe downcast; it selects a safe operation the provider actually supplies.
- Portable copies, staged fields, annotation conventions and constructor promotion
  primarily change portable authoring/conversion, not the native language surface.
- Binding exposure, source locking and algorithm-specific numeric checks do not
  redefine core language semantics. Compiler-required checks remain independent.

Metadata must truthfully distinguish a constructible container from a cursor/view
that merely iterates or indexes. Any provider currently described as a container
but unable to produce a valid empty instance is a concrete contract mismatch to
report, not permission to insert a hidden whitelist or silently add the deferred
opt-out flag. Likewise historical nullable struct-field restrictions remain a real
common-route constraint for INIT-02, despite general v0.2 nullable capability work.

## Proposed order and proof boundaries

User authorized this dependency-led sequence on 2026-10-23. Perform a reuse
analysis before each entry and stop dependent work for discussion on new semantics
or wide redesigns while continuing independent work. Unrelated deferred debts need
not be finished before the first native progress check.

0. **Discovery/design first:** inventory registry/type-use debt and bound NULL-01
   identity/conversion redesign plus LOCAL-01 control-flow owner. Inventory only is
   not closure of CH15. Record current focused baselines; do not repeat the old
   whole-bootstrap diagnostic list as current defects. Present a wide redesign
   before code as required by repository rules.
1. **Remove independent conversion/exposure blockers:** CONVERT-01 typed empty
   construction then parameter defaults, CONVERT-02 annotation migration, INIT-01
   direct constructor mapping; BIND-01 output leaves then reader factory and facade
   tracing. Each is a separate proof-sized slice; nullable-dependent execution may
   stay explicitly blocked pending phase 3 rather than be patched ad hoc.
2. **Object model foundation:** SOURCE-01 private fields; AST-01 explicit dispatch;
   SELF-01 class retention and SELF-02 struct value operations as separate slices.
   Use SOURCE-01 numeric adaptations only at demonstrated boundaries. These can be
   validated in isolation without waiting for the whole nullable migration.
3. **Presence/ownership spine:** NULL-01 semantic identity first with coordinated
   affected consumers; NULL-02 operation capabilities and OWN-01 handle dispositions;
   NULL-01 checked value/ref/override boundaries; LOCAL-01 path facts and required
   field admission. These overlap: design the integration before splitting commits,
   never temporarily normalize away requiredness to obtain a green test.
4. **Portable value/state protocols:** COPY-01 first scalar proof, then INIT-02
   bounded checked backing/accessors and struct copy-update-replace proof. COPY-01
   can move earlier if helper lowering proves it independent of SELF-02/nullable
   work. Preserve historical v0.1 restrictions through route-specific admitted code.
5. **Inspection and concurrency:** CURSOR-01 metadata relation and prelowering;
   TASK-02 lookup synchronization before native parallel execution; TASK-01 bounded
   joined shared admission. TASK-02 can be repaired earlier independently. Ordinary
   upcasts must already be settled; full nullable migration is not a prerequisite
   for every cursor proof.
6. **Integration:** run VERIFY-01 after meaningful milestones with time/memory and
   stop/classify new blockers under the agreed scope. Complete DOC-01 reconciliation
   and CH15 registry audit remediation before claiming final-catalog closure. Keep
   unrelated deferred debts out. Do not promise a bootstrap completion date or infer
   memory feasibility from static code size.

Each slice should record the owning contract amendment (if any), one positive proof,
one relevant rejection or alias/lifetime proof, ordinary/prelowered behavior, and
PHP/v0.1 compatibility where the slice affects the portable compiler. Tests should
prove behavior, not mirror implementation. Workload should be reassessed when an
owner proves inadequate rather than silently growing the slice.

## NULL-01 / LOCAL-01 clarification — 2026-10-23

INIT-02 _uninitialized_ remains converter/source work. Its generated nullable backing
and checked accessors consume ordinary language operations; no core uninitialized
field state, annotation awareness or delayed-object protocol is authorized. Explicit
PHP accessors/source adaptation remain the fallback where structural conversion cannot
resolve access safely. NULL-01 does not absorb this portable feature.

NULL-01 answers whether a value may be absent at a typed boundary. LOCAL-01 answers
whether a local has been assigned before a read. Declared required class locals can
have an empty native backing before assignment without exposing that backing as an
initialized source value. Assigned nullable locals can validly contain null. Checked
nullable-to-required conversion succeeds before the destination is marked assigned;
a throwing conversion does not establish successful assignment on a catch path.
Definite assignment uses bounded control flow, not arbitrary predicate reasoning,
heap aliases, converter annotations or whole-program analysis. Discuss precise
flow coverage and conservative diagnostics before implementing its fact owner.

## Reuse audit — LOCAL-01 and NULL-01

User accepted LOCAL-01 reuse direction and revised 6–10x estimate. Existing
Completion_Preparation already composes sequence, alternatives, loops, switches,
return/break/continue and finally. Extend that owner with per-local assignment facts;
existing booleans alone do not establish assignment. Approximate sub-slices are
3–5x reads/writes/sequences/branches and 3–5x loops/exceptions/proofs. Preparation
order is not necessarily execution order (notably do-while condition preparation).

NULL-01 source inspection finds substantial existing foundations: nullable_semantics
and nullable_type_definition, canonical generic applications, Nullable_Conversions
(empty/wrap/extract), shared Value_Boundary_Preparation, invariant reference checks,
Callable_Adaptation directional ancestry, and prepared conversion lowering in both
ordinary and concrete/prelowered paths. The core issue is explicit normalization in
Type_Preparation::application: nullable payloads with an ownership role return the
payload itself. Ownership_Conversions also admits null assignments to ordinary class
handles. Both implement current contracts and must change deliberately.

Recommended model direction: extend the existing nullable semantic role to preserve
optional class identity while selecting existing shared-handle storage, not a second
optional runtime layer. Reuse registry interning/substitution; do not invent a parallel
nullable flag registry. Final retained-model choice requires the bounded design gate.
Existing nullable extraction emits require_value on value wrappers; shared-handle
presence checks need representation-aware lowering. Existing exact payload conversion
must compose with safe ancestry, and override adaptation must admit only non-failing
substitutability (never a checked narrowing bridge). Exact reference checks must use
source semantic identity even when native representation is identical.

The subsequent [four-consumer audit](compiler_null01_consumer_audit_2026_10_23.md)
supersedes the preliminary 15–30x estimate with 12–20x: substantial retained-key and
semantic-owner reuse, with physical wrapper transport the largest adaptation. NULL-02 capability widening
remains separately scoped but shares these owners. No implementation.

## NULL-02 reuse and incremental workload — 2026-10-23

The [NULL-02 consumer audit](compiler_null02_consumer_audit_2026_10_23.md) confirms
existing value_storable formation, dependent copyability, optional storage and
retained inline-record coverage. Revise 6–12x to 4–8x incremental to the shared
NULL-01 model. NULL-01 owns optional-class identity/physical representation; NULL-02
owns operation-specific payload admission and generic/nested value-payload proofs.
Do not count registry/transport foundations twice. Condition truthiness, take output
assignment and noncopyable default admission need explicit existing-policy checks;
removing the whitelist alone is insufficient. No implementation or tests run.

## TASK-01 reuse and remaining work — 2026-10-23

The [task reuse audit](compiler_task01_reuse_audit_2026_10_23.md) confirms existing
executor/join/publication/synchronize machinery and generic metadata/capture/transport
owners. Revise TASK-01 from 8–15x to 8–12x. The meaningful remaining model gap is
API-scoped admission carried through erased callbacks: the current transferable bool
cannot distinguish joined shared permission from isolated transfer. Extend existing
owners, not the scheduler or a second callable system. TASK-02 source locking remains
separate and precedes native concurrent compiler bootstrap. Native identity/lifetime,
failure/join, erasure and out-of-scope rejection proofs remain. No implementation.

## CONVERT-01 reuse correction — 2026-10-23

The [CONVERT-01 audit](compiler_convert01_reuse_audit_2026_10_23.md) finds construction
parameter defaults already supported internally for synthesized property inputs.
General authored parameter admission is the missing path, not an entirely new default
fact system. Metadata list/keyed selection, empty factory emission, prelowering and
call-local omitted temporaries are reusable. Revise 6–12x to 4–8x, with factory-role
integration and converter cross-route proofs. Provider metadata's scalar-only default
schema and general source-written initialization factories are separate surfaces,
not silently expanded by this slice. No implementation or tests executed.

## INIT-02 reuse audit — 2026-10-23

The [INIT-02 audit](compiler_init02_reuse_audit_2026_10_23.md) confirms reusable local
declaration/trait indexing, property/method emission, diagnostics and existing nullable
storage. Access nodes retain only a field spelling; no resolved receiver/read-write
model exists. Keep **6–12x**, incremental to nullable/copy foundations. Bounded access
classification, hygiene, rejection and route proofs remain real converter work.
Use explicit PHP accessors for unresolved receivers. First prove class owners with
class and copyable struct payloads; struct-owner layout remains constrained by the
existing converter/v0.1 record contract. No native feature expansion is authorized.

## LOCAL-01 implementation checkpoint

The accepted 6–10x integration now has a bounded implementation and proofs. Existing
statement completion carries per-exit pending storage identities; the existing body,
loop, switch and exception owners compose them without a second traversal or CFG.
Do-while ordering and conditional-expression isolation are included. The proof includes
PHP/native execution of the production composition owner, both-mode source behavior,
retained recovery and the existing native control-flow programs. No struct lifetime
redesign, constant-condition solver or interprocedural exception analysis was added.
Remaining required-handle cleanup/runtime guarantees stay with NULL-01/OWN-01.

LOCAL-01 continuation: the first measured bootstrap exposed runtime-facade outputs
initialized by successful `take`. Output binding now uses write-only lookup, but
truth-conditioned assignment facts are not implemented yet. Do not mark LOCAL-01
complete: integrate the already documented nullable/Result success rules, including
negation/lazy conditions and early failure exits. In particular, result_or_bool true
sentinels do not assign the payload. No implicit ordinary out-parameter protocol or
general boolean-variable correlation should be inferred. The file-lock empty factory
is a separate runtime ownership/default-contract audit item.

Conditional take follow-up: direct success, logical negation and lazy operators now
carry transient true/false assignment facts to if/loop/conditional branches. Both-mode
behavior/retained tests and production helper parity pass. This supersedes the pending
take-success item; runtime empty-handle contracts and generic nullable take remain
separate OWN-01/NULL-02 work. No new extraction semantics or retained model was added.

NULL-02 nullable-take execution: the old payload whitelist is replaced by existing
storage/copy capabilities, with exact mutable outputs. Reused runtime optional-value
assignment plus an erased shared-handle overload; prelower output pointers now use
the existing physical-wrapper query. Nested generic provider, inline/shared records,
noncopyability and retained recovery proofs pass. Nested Result<nullable<T>> is now implemented through nullable transport registration,
existing recursive wrapper transport and prepared outer-state probes. The converter's
generic nullable local-comment grammar remains unresolved.
