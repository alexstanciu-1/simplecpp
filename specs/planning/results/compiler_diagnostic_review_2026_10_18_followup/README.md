# Diagnostic review follow-up — 2026-10-18
Doc Status: supporting

## Result

Reviewed 31 case groups at HEAD bccaef7fb51b8e9287998cbd5e971ae7f905a573 with the
recorded pre-existing local changes. LOW-02 and REC-03 were not run. No compiler
fixes were made. The PHP-hosted compiler was used; no native compiler rebuild.

- 29 source/control pairs: all 29 controls compiled and linked.
- Source outcomes: 14 preparation rejections, five compile/link successes, nine
  Clang compile failures and one emission-time duplicate-type rejection.
- Four ++/-- source programs and their controls executed with expected return codes.
  The closure copy-initialization control also executed; reassignment failed compilation.
- Two ordered recovery sequences withheld ready output on failure, rejected retries
  and fresh invalid sources, then restored matching baseline artifacts after repair.
- All 14 ordinary preparation rejections omitted source location; semantic recovery
  did too. CLI probes confirmed missing locations for multiline/cross-file cases.
  Therefore phase-correct rejection alone is not a full diagnostic pass.
- Eight deduplicated issues: one simple fix, three light discussions, three normal
  discussions and one red flag. These are proposed repair categories, not repair authorization.

[Per-case assessment](assessment.json), [structured issue catalog](issues.json),
[raw summary](results/summary.json), [baseline](baseline.json), [integrity](integrity.json).

## Issue catalog

| ID | Issue | Category | Cases |
| --- | --- | --- | --- |
| DR-01 | Typed hash key constraints admit bool/float | normal discussion | TYPE-01, TYPE-02 |
| DR-02 | Void ownership targets admitted | light discussion | TYPE-03, TYPE-04 |
| DR-03 | Duplicate types rejected only during emission | light discussion | NAME-01 |
| DR-04 | Abstract inline constructor parameter missing admission check | simple fix | OBJ-01 |
| DR-05 | Captured closure assignment cannot be represented by direct lambda assignment | normal discussion | LOW-01 |
| DR-06 | Lifecycle bodies accept void-expression return | light discussion | LOW-03, LOW-04 |
| DR-07 | Mutual container payload headers form invalid include cycle | normal discussion | LOW-05 |
| DR-08 | Preparation errors lose source attribution | red flag | NAME-02, NAME-03, NAME-04, CALL-01, CALL-02, REF-01, REF-02, REF-03, OP-05, CONT-01, CONT-02, CONT-03, OBJ-02, OBJ-03, REC-01 |

### DR-01: Typed hash key constraints admit bool/float

Owner: Type application, generic capabilities and runtime metadata requirements.

Runtime limits keys to supported families while registration advertises broad hashable/comparable capabilities. Agree the reusable key-domain contract; do not add source-name branches.

Required proof: Pair supported integer/string keys with rejected bool/float applications, including signatures and incremental rebuilds.

Evidence: [TYPE-01](results/TYPE-01.json), [TYPE-02](results/TYPE-02.json).

### DR-02: Void ownership targets admitted

Owner: Shared ownership type-application admission.

User chose rejection. The registry currently installs empty Target requirements. Agree a narrow common no-value target check versus a reusable target capability without broadening this slice.

Required proof: Reject shared/unique void at the source type application; preserve valid ownership types; align normative contract first.

Evidence: [TYPE-03](results/TYPE-03.json), [TYPE-04](results/TYPE-04.json).

### DR-03: Duplicate types rejected only during emission

Owner: Collection identity and declaration preparation.

Duplicate source records survive until C++ artifact identity checks. Decide the existing declaration-validation point and related-location attribution, preserving distinct symbol kinds.

Required proof: Same-namespace duplicates fail before emission; distinct qualified types and notified add/remove recovery pass.

Evidence: [NAME-01](results/NAME-01.json).

### DR-04: Abstract inline constructor parameter missing admission check

Owner: Constructor_Preparation::signature / existing parameter admission.

Ordinary Declaration_Preparation::parameter_contract calls require_concrete_value; the constructor ordinary-parameter branch only calls require_value_type. Reuse the existing concrete-value admission path locally.

Required proof: Reject abstract inline constructor inputs, including inherited signatures; keep concrete structs and abstract shared handles valid. Do not broaden constructor syntax.

Evidence: [OBJ-01](results/OBJ-01.json).

### DR-05: Captured closure assignment cannot be represented by direct lambda assignment

Owner: Closure operation capabilities and C++ closure representation.

Copy initialization works; captured C++ lambda assignment is deleted. User requires same-concrete-type reassignment. Agree representation and capture/lifetime semantics; do not disable copying or substitute destroy/reconstruct without a lifetime design.

Required proof: Source/control compile and execute; cover self/nonself assignment and supported borrowed captures after design agreement.

Evidence: [LOW-01](results/LOW-01.json).

### DR-06: Lifecycle bodies accept void-expression return

Owner: Body return preparation and lifecycle callable context.

Ordinary void forwarding has no lifecycle-specific admission boundary in prepare_return. User decided to reject every return expression in constructors/destructors. Identify existing callable context or discuss a narrowly scoped fact if required; new retained structure promotes this to normal discussion.

Required proof: Reject value and void-expression returns; preserve bare returns, fallthrough, ordinary void forwarding, required initialization and automatic cleanup. Align normative contracts first.

Evidence: [LOW-03](results/LOW-03.json), [LOW-04](results/LOW-04.json).

### DR-07: Mutual container payload headers form invalid include cycle

Owner: C++ type completeness requirements and header assembly.

Both mutually referring vector fields use ordinary class handles, not recursive inline values. Generated header inclusion leaves record_AUqItem undeclared. Reconcile declaration versus completeness dependencies; detailed root-cause design remains open.

Required proof: Mutual and one-way payload controls compile every unit and link; prove dependency correctness without weakening completeness for actual value operations.

Evidence: [LOW-05](results/LOW-05.json).

### DR-08: Preparation errors lose source attribution

Owner: Semantic error creation, retained preparation failures, propagation, API/CLI rendering.

Worker failure stores only failure_message and require_success concatenates strings. Source file/line is absent in all 14 sampled preparation rejections and REC-01. Direct CLI probes confirm NAME-04/REF-03 also lack locations. An honest repair likely needs a structured diagnostic/source-origin contract across these boundaries; do not infer location from exception implementation lines or append guessed positions.

Required proof: Design source identity/span retention, propagation/deduplication, related declarations and stale-generation behavior. Prove multiline/cross-file locations, distinct identical-message errors and incremental retry/recovery. Assess affected areas and validation cost before implementation.

Evidence: [NAME-02](results/NAME-02.json), [NAME-03](results/NAME-03.json), [NAME-04](results/NAME-04.json), [CALL-01](results/CALL-01.json), [CALL-02](results/CALL-02.json), [REF-01](results/REF-01.json), [REF-02](results/REF-02.json), [REF-03](results/REF-03.json), [OP-05](results/OP-05.json), [CONT-01](results/CONT-01.json), [CONT-02](results/CONT-02.json), [CONT-03](results/CONT-03.json), [OBJ-02](results/OBJ-02.json), [OBJ-03](results/OBJ-03.json), [REC-01](results/REC-01.json).

## Decision and proof limits

TYPE-03/04 and LOW-03/04 are measured against explicit user decisions recorded in
the inventory, pending alignment of normative specs before implementation. Closure
assignment is required by the user; its compilation failure is not justification
to reject it. REC-02 contains an unclosed function body; EOF brace attribution is
appropriate, so its original return-token anchor is not a strict location oracle.

DR-04 can be a bounded semantic fix through the existing owner, but claiming complete
source-diagnostic acceptance also depends on DR-08. DR-08 is a cross-boundary design
problem: discuss the diagnostic record, source lifetime, propagation and API contract
before implementing. Smaller options are an agreed narrow structured-diagnostic pilot
or retaining message-only behavior as explicit debt; neither implies a complete fix.

## Reproduction and evidence handling

The archived run.py and PHP probes preserve their original bytes. They target this
checkout path; configure that path before replay elsewhere. Copy the harness into a
fresh directory and run there; run.py creates results/ and check_cli.py uses its
multiline sources. Use the current inventory only if intentionally making a new
review revision; inventory.json freezes the executed input. The runner records
commands and toolchain versions, compiles each emitted unit separately and links
against one freshly built runtime object. Programs execute only where specified.

The 12-slot shared runner tried its default PHP-FPM executor; sandbox socket binding
failed, so its documented CLI fallback was used. The CLI-only follow-up checked two
source diagnostics. No Daybreak block occurred during these calls.

[Archive manifest](archive_manifest.json) hashes copied text evidence. Objects,
executables and daemon state are omitted. Raw log paths identify the original run;
relative result links remain durable. Compiler/runtime fingerprints were unchanged
through the main run. Existing deferred work, including dependency_cleanup.php,
was not edited. Full regression, native compiler, runtime exception semantics,
race/cache checks and the two deferred inventory groups are outside this proof.

## Subsequent DR-04 repair — 2026-10-18

The missing constructor concrete-value admission is fixed through the existing
shared parameter-contract path. [Focused proof](../../../../compiler/my-try/docs/planning/constructor_parameter_admission_2026_10_18/README.md)
covers 12 rejected signatures, two recovery sequences, four compiled/executed
controls and existing constructor/abstract-dispatch PHP suites. The original review
results above remain historical evidence. DR-08 source-location acceptance remains
open; the remaining issue status is recorded in issues.json.

## Subsequent DR-02 repair — 2026-10-18

Shared/unique void applications now reject in the existing ownership registry
validation, before interning. The owning spec was updated; weak<void> is unchanged.
[Focused proof](../../../../compiler/my-try/docs/planning/ownership_void_targets_2026_10_18/README.md)
covers registry identity/alias checks, ten source rejections, two recovery sequences,
three compiled/executed controls and existing ownership/type-catalog PHP regressions.
The original review evidence above remains unchanged; DR-08 is still open.
