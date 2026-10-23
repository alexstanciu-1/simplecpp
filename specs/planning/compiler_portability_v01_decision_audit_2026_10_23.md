# Compiler portability decisions versus v0.1 direction
Doc Status: planning

Read-only documentary audit, 2026-10-23. No implementation, native execution or
new conformance test. Scope: accepted decisions in
[the backlog](compiler_portability_decisions_2026_10_23.md), plus explicitly open
proposals that could change compatibility. Bugs and missing v0.2 implementations
are excluded from the design-conflict findings.

## Baselines and evidence limits

Use released v0.1.77 (`3940141f`) and development `75de94c7`, as selected by the
[compatibility report](../../compiler/my-try/docs/planning/v0_1_compatibility_report.md).
These are locally available pinned historical baselines, not a claim about today's
remote head. Read their documents using `git show REV:path`; current working-tree
implementation is not automatically the historical contract. Relevant rules.md
differences between these baselines concern rooted construction, not the findings
below. Both retain the pertinent nullable/dispatch contracts; compact layout and
first-pass tasks have no differences between these two baselines.

Primary evidence, paths at `75de94c7` unless stated otherwise:

- `generators/php/specs/rules.md`, Passing and Return Conventions (lines 156–166):
  T/?T share object-handle lowering, but non-null intent and future injected checks
  are explicit. Deferred Intent Metadata (lines 505–510) repeats that direction.
- Same file, sections 7.1, 16.5–16.8: typed local predeclaration is admitted;
  defaultless typed fields are admitted; this is valid in instance contexts;
  abstract/interface methods establish virtual forms, Override is explicit and
  ordinary methods are not automatically virtualized.
- `generators/php/specs/catalog.md`, CLASS-CALL-006: explicit virtual-related cues,
  no inferred virtual dispatch. `StrLComp.md` repeats these rules.
- `specs/compact_layout_types.md`, sections 2.1–2.3: inline structs; first-slice
  methods, inheritance and nullable fields explicitly excluded.
- `specs/native_reference_safety.md`: no escaping native references into heap-owned
  interior storage; copy-stable shared handles have separate value semantics.
- `specs/builtins/tasks/first_pass.md`, opening contract: shared mutable transfer is
  outside alpha scope. unordered_publication.md does not exist at this baseline.
- Current `specs/type_use_modifiers.md` and portability guides supply later direction,
  clearly distinguished below from historical v0.1 promises.

## Decision coverage

| Decision | Documentary classification | Disposition / consequence |
| --- | --- | --- |
| Source/converter-first boundary; CH15 registry audit | Aligned architectural direction | Keep structural conversion and registered semantics. Compiler semantic preparation is not the type-blind v0.1 generator; do not force identical architecture. |
| SELF-01 class self values | Compatible extension/clarification; full owning-self escape contract not established by inspected historical docs | Instance self use is documented; current retain_self code is supplementary evidence, not a historical spec for all escapes. Document live-owner/constructor/destructor restrictions explicitly. No identified historical promise requiring unsafe ownership promotion. |
| SELF-02 struct self | Explicit extension beyond historical struct scope | Historical structs reject methods. Core struct self support cannot be described solely as restoring missing v0.1 support. Existing value semantics remain aligned. |
| COPY-01 portable copies | New portable protocol, aligned with independent native values | PHP marker must not become a native interface. Generated struct methods would exceed historical v0.1 admission; either extend the v0.1 route deliberately or lower the protocol to existing value operations/helpers there. Common behavior does not require identical lowering. |
| INIT-01 promotion and explicit defaults | Source/converter adaptation; no identified contradiction | Use established target initialization constructs; preserve order. Do not imply historical PHP promotion syntax or current v0.2 set: spelling was always supported in v0.1. |
| INIT-02 hidden staged state | New portable guarantee, not removal of a promised value | Existing required_fields guide explicitly excludes native read-before-init parity. Checked accessors strengthen the portable contract without a new native initializer. Historical nullable struct-field restrictions matter if the backing field is inside a struct. |
| TASK-01 shared joined tasks | Explicit scope extension | Historical task contract excludes shared mutable transfer. Current unordered-publication contract permits synchronized parser registration; reconcile these contracts explicitly, bounded to the agreed API. Not merely missing metadata/v0.2 code. |
| TASK-02 lookup synchronization | Bug repair; excluded from design-conflict findings | Retain separately in backlog; it does not redefine task semantics. |
| AST-01 Virtual/Override | Additive explicit source contract aligned with documented dispatch direction | Virtual is new. Historical docs explicitly favor source cues, unlike current broad generator virtualization. Do not infer a v0.1 semantic requirement that all ordinary methods be virtual from that implementation. Preserve defaults and source intent across routes. |
| NULL-01 Node/?Node | Aligned with deferred historical non-null intent; contradicts later v0.2 semantic normalization | Historical rules explicitly anticipate non-null checks. This corrects our earlier inconclusive assessment. Amend current type_use_modifiers semantic identity/default rules; do not count deferred enforcement as a v0.1 design contradiction. |
| NULL-01 checked value conversions | Consistent enforcement direction; exact new checks need a bounded contract | Known null rejects; potentially nullable values check at required boundaries. Same representation is not evidence of no-check semantics. Do not claim historical end-to-end enforcement. |
| NULL-01 invariant references | Compatible with native typed-reference direction; explicit nullability refinement | Preserve existing no-interior-reference limits. PHP's more permissive reference behavior is not a v0.1 native promise. Same-representation T/?T requires new semantic checks; exact historical nullability-aware reference rules were not found. |
| NULL-01 directional by-value overrides | Compatible extension/clarification, not proved historical blanket variance | Shared-handle variance and nullability must use substitutable contracts. Historical generator delegates hierarchy checking to C++; that does not specify every PHP variance case as supported. No container covariance follows. |
| NULL-02 capability-based nullable | Aligned general model; broader admitted compositions can extend historical scope | Generic nullable mapping exists. Historical structs expressly exclude nullable fields; capability support must not silently erase unrelated field/source restrictions. This is not a type-by-type policy whitelist. |
| CONVERT-02 ?array/?vector | Deliberate portable source convention migration | Existing converter takes nullability from ?array and a vector payload comment. Requiring a complete ?vector comment changes that documented authoring convention; it is not a historical native type-semantics contradiction. Migrate source/guides/tests explicitly. |
| OWN-01 explicit handle emptiness | Aligned historical lowering | Historical rules show weak/unique null locals and direct handle mappings. Node's required-value contract need not redefine explicit ownership handles. |
| OWN-01 weak locks / move / runtime contracts | Preserve ownership behavior, refine source presence guarantees | Empty weak-lock results become optional frontend values; current unique empty-after-move contract stays. No historical full cross-route weak expiry guarantee established; PHP facade currently retains strongly. |
| LOCAL-01 definite assignment | New enforcement/explicit restriction, not a proved historical guarantee | Keep predeclarations and valid struct defaults; diagnose unassigned required class-local reads. Direct required-field initialization is now accepted; do not describe this as historical behavior. |
| SOURCE-01 private instance fields | Relaxes current v0.2 restriction; compatible with C++ storage/lookup direction | Historical rules emit ordinary C++ members but do not explicitly promise every private-shadow case. PHP lexical-private lookup through a child-typed receiver differs; use an explicit base view in portable PHP. |
| SOURCE-01 numeric discipline | No new numeric contract | Algorithm-specific checks belong in PHP when logically necessary. Compiler-enforced overflow/conversion contracts remain independent of the converter. |
| CONVERT-01 typed empty construction | Existing vector/hash precedent plus a generalized v0.2 container contract | Both pinned rules.md versions explicitly lower annotated empty vector/hash literals. General admission for registered container definitions is broader; use existing data-initialization ownership, never a name whitelist. |
| CONVERT-01 empty parameter defaults | Extends current v0.2 default forms and portable converter scope | Historical general default support and typed empty literals do not prove their complete composition for arbitrary runtime containers. Preserve typed targets and fresh construction on omitted arguments; prove both routes. |
| BIND-01 runtime factory/exposure | Binding/API addition; no new core lifetime contract indicated | Existing shared-returning calls and Result transport supply the model. Small native reader factory plus both-route exposure; generic shared-provider new remains CH15 debt. |
| CURSOR-01 | Existing runtime/v0.1 constructor behavior exposed via a new reusable v0.2 binding constraint | Storage<U> to cursor<T> construction only; no automatic cursor covariance or mutable Storage widening. |
| VERIFY-01 / DOC-01 | Validation and contract reconciliation | No independent language feature; update normative owners with accepted changes, not merely planning notes. |

## Reconciled dispositions — 2026-10-23

The following supersedes earlier open questions in this audit. Implementation remains
paused. No normative specification is changed by this planning document.

1. **Initialization:** required ordinary class fields use direct constructor
   initialization; explicit portable staged fields use nullable backing/accessors.
   Required class locals admit predeclaration with bounded definite assignment;
   structs preserve valid default construction, otherwise use direct initialization.
   This strengthens admission relative to historical defaultless declarations and
   must be documented explicitly, not advertised as a proved old guarantee.
2. **Structs:** ordinary struct receiver/value support is accepted for v0.2, an
   extension beyond historical v0.1 struct methods. Portable _struct_copy_ is
   converter-owned and must lower to existing value/helper operations on v0.1.
   Nullable backing inside a historically restricted struct still needs an admitted
   representation; do not silently extend v0.1 struct fields. Shared-self retention
   is deferred review debt, not a prerequisite for value copies.
3. **Tasks:** bounded shared admission for the joined unordered-publication API is
   accepted; amend first-pass exclusions explicitly. Synchronization remains the
   programmer's responsibility. No blanket shared-graph/thread-safety promise.
4. **Nullability/ownership:** Node and ?Node become distinct frontend contracts,
   aligning with historical deferred intent but replacing current v0.2 normalization.
   Explicit admitted shared/unique/weak handles remain empty-capable; their redundant
   ? forms reject rather than normalize. This spelling rejection changes current
   accepted source forms. Shared<Class> remains redundant/rejected. Invariant refs
   and directional value overrides are agreed. Do not silently conflate these rules
   with native pointer representation or normalize away required-field contracts.
5. **Weak parity:** exact expiration/cleanup timing emulation is deferred. Preserve
   required behavior; justified PHP-only cleanup is allowed. No new parity gate.
6. **Private fields:** allow distinct same-named private instance fields. Resolve by
   static receiver type, then access-check, without fallback to a base field after
   a private-access failure. This amends current inheritance_v0_2.md rejection.
   Portable PHP uses explicit base-typed views where lexical lookup differs.
   Same-named methods/constants, static fields and public/protected hiding are not
   admitted by this decision.
7. **Empty containers:** explicit PHP comments supply target intent; typed [] works
   for registered container targets, including parameter defaults, by the same
   metadata-driven construction rule. Current data_initialization.md already owns
   typed bracket/brace forms and explicit participation; the default-empty guarantee
   must be attached to a truthful container-construction contract, not inferred from
   indexing/iteration alone. No current opt-out flag. This is broader than historical
   named vector/hash support and current scalar/enum/null default admission.
8. **Bindings/cursors:** metadata-first output APIs and a shared-reader allocation
   factory are accepted; generic shared-provider constructor support stays deferred.
   Cursor constructor widening uses an explicit metadata relationship checked by
   existing ancestry/ownership rules. It does not imply generic covariance.

### Additional historical evidence checked in this reconciliation

`git show 3940141f:generators/php/specs/rules.md` and the same path at `75de94c7`:
line 259 admits declaration-owned default arguments; lines 360 and 374 specify
annotated empty vector/hash construction. These are separate statements, not an
end-to-end default-container proof. Section 16.6 describes emitted member fields;
it does not explicitly define every private-shadow lookup edge. The contemporary
`specs/data_initialization.md` does not exist at the development baseline, so its
current generic factory contracts must not be attributed to historical v0.1.

Current contrast: `specs/inheritance_v0_2.md` Layout and lookup rejects inherited
private-name collisions; `specs/type_use_modifiers.md` opening section normalizes
nullable handle semantic identities and accepts redundant nullable spellings;
`specs/runtime_module_metadata.md` Scalar parameter defaults excludes container
metadata defaults; portable authoring guidance also excludes empty container defaults.
`Parameter_Defaults::prepare` is implementation evidence of current source-default
limits, not the normative authority. Current source data notation already supports
explicit target selection, including [] for admitted vector/hash families.

### Implementation implications

There is no remaining general approval question from the original audit. Accepted
contract amendments require owning-spec edits and migration/rejection proofs during
their slices. Missing v0.2 implementations and bug repairs remain distinct from
these design changes. Full historical execution parity is not claimed by this audit.

The [per-entry feasibility/workload/order review](compiler_portability_implementation_review_2026_10_23.md)
provides proposed sequencing and the cross-owner scope gates. Newly discovered
protocol changes or a wide redesign still require discussion before implementation.
