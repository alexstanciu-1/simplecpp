# Diagnostic review inventory — 2026-10-18
Doc Status: planning

Status: 31 case groups reviewed; two remain deferred. No compiler fixes made.
See the [review results and eight-issue catalog](../../../../../specs/planning/results/compiler_diagnostic_review_2026_10_18_followup/README.md).
This is a bounded first review, not exhaustive language validation.

33 case groups: 31 have proposed/agreed expectations and two are deferred by the user.
All five inventory questions have a decision or explicit deferral.
Each group has a source and a nearby valid control; recovery groups add ordered states.

[Full sources, controls, locations, owners, proof requirements and status](cases.json).
[Input hashes and checkout provenance](provenance.json).

## Proposed list

| ID | Review item | Expected outcome | Question |
| --- | --- | --- | --- |
| TYPE-01 | Unsupported typed hash key: float | reject-preparation | — |
| TYPE-02 | Unsupported typed hash key: bool | reject-preparation | — |
| TYPE-03 | shared<void> target admission | reject-preparation | — |
| TYPE-04 | unique<void> target admission | reject-preparation | — |
| NAME-01 | Duplicate qualified type across files | reject-preparation | — |
| NAME-02 | Unused missing import | reject-preparation | — |
| NAME-03 | Conflicting aliases | reject-preparation | — |
| NAME-04 | Private member access across files | reject-preparation | — |
| CALL-01 | Missing required argument | reject-preparation | — |
| CALL-02 | Non-void fallthrough | reject-preparation | — |
| CALL-03 | Ordinary void forwarding remains valid | accept | — |
| REF-01 | Escaping local reference | reject-preparation | — |
| REF-02 | Temporary reference argument | reject-preparation | — |
| REF-03 | Write through readonly parameter | reject-preparation | — |
| OP-01 | Canonical int ++$x snapshot | accept | — |
| OP-02 | Canonical int $x++ snapshot | accept | — |
| OP-03 | Canonical int --$x snapshot | accept | — |
| OP-04 | Canonical int $x-- snapshot | accept | — |
| OP-05 | Readonly decrement rejection | reject-preparation | — |
| CONT-01 | Container interior alias | reject-preparation | — |
| CONT-02 | Boolable Result coalescing | reject-preparation | — |
| CONT-03 | Record empty versus presence | reject-preparation | — |
| OBJ-01 | Abstract inline constructor parameter | reject-preparation | — |
| OBJ-02 | Interface result mismatch | reject-preparation | — |
| OBJ-03 | Inheritance cycle | reject-preparation | — |
| LOW-01 | Captured closure reassignment | accept | — |
| LOW-02 | Self-value constructor shape | deferred | — |
| LOW-03 | Void-expression return in constructor | reject-preparation | — |
| LOW-04 | Void-expression return in destructor | reject-preparation | — |
| LOW-05 | Mutual shared-object container payloads | accept | — |
| REC-01 | Notified semantic failure and repair | reject-preparation | — |
| REC-02 | Notified syntax failure and repair | reject-frontend | — |
| REC-03 | Scan-only unchanged metadata edit | deferred to CH15-SOURCE-DETECTION-01 | — |

## Decisions and deferrals

Explicit user decisions recorded on 2026-10-18:

- **Q1 — Decided:** reject `shared<void>` and `unique<void>` during preparation.
- **Q2 — Decided:** same-concrete-type captured closure reassignment must work;
  preserve supported copy initialization and capture/lifetime semantics.
- **Q3 — Deferred:** self-value constructor policy and LOW-02 are outside this
  review execution until a later discussion.
- **Q4 — Decided:** constructors/destructors reject `return expression;`, including
  void expressions. Bare `return;` and fallthrough remain allowed. Use
  `cleanup(); return;` for an operation followed by early exit. Required initialization
  and automatic cleanup obligations remain. Ordinary void-function forwarding is unchanged.

These are user decisions, not inferred language rules. Align the owning normative
contracts before implementing repairs; review execution is complete, with no fixes.

- **Q5 — Deferred to CH15:** scan-only equal-size/equal-mtime change detection is
  tracked as [CH15-SOURCE-DETECTION-01](../../catalog/15_blocking_debt.md#ch15-source-detection-01--scan-only-edits-with-unchanged-file-metadata).
  REC-03 is excluded from this review execution. Explicitly notified failures and
  recovery (REC-01/02) remain in scope. No fix or new execution is authorized.

## Execution boundary and interpretation

The user authorized continuation after resolving all inventory questions. Execution
and issue classification are recorded in the linked review. The two deferred cases
were excluded. No repair goal has been started; repair categories are proposals.

Execute families as small review batches through the existing shared runner. Keep
12 task slots with ordered per-case steps; return concise IDs/outcomes and store
full relevant evidence on disk. Follow the parent plan for Daybreak handling.
A blocked tool operation is not a compiler failure and must never be disguised.

Controls must reach the intended phase. If an incidental parser/configuration error
prevents that, record inconclusive and correct the fixture transparently; never count
it as proof of semantic rejection. Sources are intentionally minimal; token spans are recorded in cases.json.
NAME-04 and REF-03 deliberately use later lines to check actual source-line attribution. Do not require exact diagnostic prose or columns.
A useful related declaration location is desirable, but not a blanket new contract.

Clang checks are downstream cross-checks. Source-invalid cases should stop before
emission; valid cases must not be made invalid to avoid backend failures. Historical
results are candidate provenance only. Integer mutation cases use small values and
their return codes are deliberate expected results, not process-success defaults.

## Coverage decisions and deferrals

- Names, types, calls/completion, references, operators, containers/wrappers, objects,
  backend boundary and explicit-notification recovery all have concrete cases.
- Numeric ++/-- symmetry is included because both already have catalog coverage.
  Broad operator/type Cartesian products and overflow behavior are deferred.
- Nullable absence and fallible Result extraction runtime-guard execution are deferred
  to a focused follow-up using existing wrapper fixtures; CONT-02 covers static admission
  only. Do not infer missing runtime guards from this inventory.
- Closure escape analysis beyond established reference contracts, full exception
  cleanup, traits, overload matrices and diagnostic recovery across multiple errors
  are follow-ups, not implicit completeness claims.
- Native compiler self-build, legacy STAN, LLVM, native-cache races, performance,
  unsupported generic expansion and existing unrelated work are out of scope.
- Existing catalog/operator guidance contains older delivery limits; this inventory
  uses canonical-int local mutation only and does not reassert old field/index limits.
- The strict authoring skill supplies syntax guidance. Current normative v0.2 contracts
  outrank older skill statements about struct constructors, visibility and legacy STAN.

## Original inventory validation

Inventory creation used only metadata checks: JSON shape, unique IDs, nonempty
controls, referenced file existence, source hashes and token/line coordinates. These
were document consistency checks. The later authorized execution is recorded separately.
