# 05. Null, mixed, wrappers and conversions
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires typed values and callable contracts. Review each conversion boundary and its runtime failure behavior.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Positive-pass conclusion — 2026-10-04

The bounded chapter 05 pass is complete, with explicit debt below. This is not full
wrapper/ownership/container support. Current facts cover nullable and Result scalar
boundaries, take/no-error handling, conditions/probes, selection/coalescing joins,
mixed scalar boundaries and operators, explicit mixed/dynamic bridges, and text
conversion/write-back. Dated sections below are checkpoints, not cumulative promises
that every earlier non-goal remains unimplemented.

The closeout audit adds proofs, not new language behavior. Unknown explicit types
reject at local, parameter, return and field sites; invalid template arity/payload
contracts reject without publishing output. No fallback to mixed/auto is permitted.
Existing type preparation, registry contracts and conversion workers own these rules.

### Reconciled imported notes

| Cards | Current disposition | Proof / remaining owner |
| --- | --- | --- |
| TYPE-VAR-006B | Strict explicit intent: `$p weird<int>;` rejects | `tests/constructed_type.php`; legacy annotations remain separate |
| NOTE-005, NOTE-052 nesting | Split: redundant value modifiers normalize; effective repeats reject; ownership nesting is debt | `specs/type_use_modifiers.md`, type/constructed tests; CH05-OWN-01 below |
| NOTE-010, NOTE-037 | Scalar mappings, typed signatures and canonical runtime-template formation covered | Chapters 01/04 and type tests; object handles, closures, references and container operations belong to 06/07/10/12 |
| NOTE-015 | Scalar explicit casts use prepared conversion decisions | `tests/conversions.php`; no constructor fallback; `(object)` dependency below |
| NOTE-036 | Typed predeclarations, nullable spelling, null returns and untyped-null rejection covered | `tests/nullable.php`, constructed types; callable locals/default expansion deferred |
| NOTE-045 | Bounded nullable/Result/mixed conditional matrices implemented | Nullable/Result/mixed suites; remaining joins are CH05-VAL-01 |
| NOTE-052 interpolation | Simple scalar variable interpolation already covered | Chapter 02 interpolation; paths/calls require their own owners |
| NOTE-063 | Explicit Result identity, sentinel distinctions and existing-output take covered | `tests/results.php`, `tests/take.php`; structured errors remain chapter 13; output declarations below |
| CAST-OBJECT-001 | Deferred with dynamic/container construction, not unrestricted casting | Chapter 06 handoff; existing `(mixed)`/`(dynamic)` bridges are distinct |

### Consolidated remaining debt

- **CH05-OWN-01 — ownership classification and nesting (chapter 07).** `shared`,
  `weak` and `unique` are ordinary registered templates with no semantic ownership
  role. Nested ownership applications and `value<shared<T>>` are not comprehensively
  rejected in preparation. Before adding those checks, agree a definition-identity/
  semantic-role-based policy across template arguments and effective modifiers;
  do not patch source-name tests into the registry or emitter. This is an open
  validation gap, not supported ownership behavior. `value<value<int>>` is valid
  normalization under the newer v0.2 contract; `value<value<string>>` still rejects.
  Nullable/Result template formation must not acquire a blanket nesting ban.
- **CH05-VAL-01 — broader value boundaries.** Nullable defaults; cross-payload
  conversions; non-scalar/nested wrapper operations and conditional template
  capabilities; remaining wrapper/mixed joins, bare-null branches and wrapper
  operator families; fixed-width mixed boxing/extraction/peers and additional
  mixed operator families. Existing runtime support does not imply compiler admission.
- **CH05-DYN-01 — dynamic/container integration (chapter 06).** Construction via
  supported object forms, index reads/writes, keyed probes, count and iteration.
  Runtime kinds must decide value-dependent failures, including non-iterable mixed
  values. Dynamic `->` requires a separate language discussion; current Phase 1
  source contract uses `[]`. Native references into dynamic interiors stay forbidden.
- **CH05-TAKE-01 — take binding expansion.** Current take uses established output
  variables. Output declarations/typed output bindings and branch-sensitive
  availability remain deferred; tuple-assignment syntax remains postponed.
- **CH05-PROFILE-01 — legacy availability.** The Result kind model preserves all
  three families, but strict-vs-legacy availability enforcement remains deferred.
- **RSL-ERROR-01** stays in [chapter 13](13_errors.md#structured-error-value-debt-from-chapter-05).
  Error construction/members are not invented by take or Result preparation.
- **MIXED-TRUTH-001 / MIXED-COMPARE-001** remain runtime-contract reconciliation:
  broader table/object truthiness, weak equality wording versus implementation,
  and recursive/cyclic table comparison hardening. Nullable/null generic identity
  helper repair also remains separate; compiled nullable checks use presence tests.

### Verification and next chapter

Latest generated-program checkpoint: 15 PHP suites and 322 programs, plus 14 new
no-PCH text-update cases, in the preceding text write-back slice. The last native
compiler checkpoint predates the mixed slices; no new native portability claim is
made. The closeout type audit is PHP-hosted preparation/model evidence only.
Its 10 focused suites pass in `/tmp/my-try-ch05-audit-20261004` (type catalog,
constructed types, conversions, nullable, Result, mixed, take, capabilities,
preparation recovery and incremental C++). A separate read-only probe confirms the
two ownership-admission gaps in `/tmp/my-try-ch05-ownership-audit-20261004`; it is
evidence of debt, not a regression test enshrining that behavior. No compiler/runtime
behavior changed in closeout, and no native rebuild or broad generated sweep was run.

Next: [chapter 06 grouped discussion](06_containers.md#handoff-from-chapter-05--2026-10-04),
starting with typed container construction and reads. Reuse the existing canonical
templates and index AST; establish read/write and lifetime contracts before mutation
and foreach. Chapter closeout does not authorize an ownership-model redesign.

## Text concatenation write-back — 2026-10-04

Implemented `.=` for established string or mixed locals/parameters. Right operands
reuse binary concatenation admission: mixed, ordinary bool/int/float/string or
literal null. Preparation selects `converted_assignment` write-back, with string
identity or mixed boxing, rather than numeric `mixed_stored_kind` validation.
The existing compound AST and C++ target-binding/computation/write-back path are
unchanged; no runtime helper or retained structure was added.

Important consequences:

- A mixed int `42` followed by `.="!"` becomes a mixed string `"42!"`. Table/object
  payloads similarly become `"Array!"`/`"Object!"`; shared table contents are not
  mutated. Numeric `+=` and related stored-kind restrictions remain unchanged.
- The target is bound once; both original values are converted before assignment.
  Self-concatenation and aliased reference parameters use the original value twice.
  Computation failure leaves the target unchanged.
- The expression returns an owned updated-target snapshot: string for string
  storage, mixed containing string for mixed storage. Later writes do not alter it.
  A failed outer typed conversion does not roll back a completed update.
- Known int/bool/float targets, wrappers, direct dynamic handles, fixed-width peers,
  property/index targets and effectful RHS expressions remain preparation errors.
  No implicit declaration is introduced. Logical short circuit may skip the whole
  update; existing eager-parent effect checks remain.

Proof owners: `tests/mixed.php`, `tests/compound_assignments.php` and
`tests/fixtures/mixed_concat_updates.php`, including facts, cleanup, incremental
failure/recovery, source aliasing, snapshot ownership and runtime failures.

Verification passed first attempt: 15 PHP suites and 322 generated programs in
`/tmp/my-try-mixed-append-20261004-1`; all 14 new programs also pass without PCH in
`/tmp/my-try-mixed-append-20261004-no-pch`. This is generated-program evidence,
not a native compiler rebuild. Numeric update regressions remain in the same run.

## Mixed and scalar text concatenation — 2026-10-04

Implemented binary `.` for mixed, ordinary bool/int/float/string and literal null,
in either order. `String_Conversions` owns text-context admission through the named
`concatenation` context; retained `text_conversion` decisions call the existing
`scpp::php::to_string` helper. String operands retain identity conversion. The
existing binary node and primitive string concatenation operation are reused.

Important consequences:

- The result is an owned string, even for mixed/mixed operands. A mixed destination
  separately boxes that result; inputs are not changed.
- This is neither required string extraction nor an explicit string cast. Null and
  false stringify to empty text, true to `"1"`; boxed tables stringify to `"Array"`
  and boxed dynamic objects to `"Object"`. Existing helper formatting owns numbers.
- Known wrappers, direct dynamic handles, fixed-width peers and storage modifiers
  reject in preparation. Boxed runtime kinds are interpreted by the text helper.
- Existing eager operand-order restrictions remain: calls/mutations in binary
  concatenation must first be stabilized in separate statements.
- This binary-only checkpoint retained string/string `.=`; the text write-back
  slice above supersedes that limit. Interpolation and explicit casts are unchanged.

Proof owners: `tests/mixed.php`, `tests/fixtures/mixed_concat.php`, operator and
compound-assignment suites. Legacy concatenation lowering was reviewed; v0.2 keeps
semantic admission in preparation instead of asking the emitter to infer types.

Verification: 15 PHP suites and 308 generated programs pass in
`/tmp/my-try-mixed-concat-20261004-2`. All 18 new programs pass without PCH in
`/tmp/my-try-mixed-concat-20261004-no-pch-2`. The first PHP attempt exposed a stale
compound-context assertion; the first no-PCH attempt exposed a missing `scpp/cast.hpp`
dependency in `php_string.hpp`, now explicit. The header-first runtime regression
is `tests/runtime/native/test_php_text_header.cpp`; its supplementary proof also
covers owned/shared/weak tables. Native compiler rebuilding remains on demand.

## Mixed short-circuit logical operators — 2026-10-04

Implemented `&&` / `||` and their `and` / `or` spellings when at least one operand
is mixed. Peers may be mixed, bool, ordinary int or float, in either order. The
result is bool. `Boxed_Operators` owns admission; each operand retains a separate
`Condition_Truthiness` decision on the existing binary operator facts. Emission
uses native-bool C++ short circuit, never overloaded runtime logical operators.

Important consequences:

- Evaluation is left-to-right, once per selected operand. Skipped RHS calls,
  assignments, mutations and runtime failures remain skipped. Sequenced boolean
  logical expressions also admit these effects; eager parents still reject unsafe
  operand-order combinations.
- Mixed strings follow condition truthiness: `""` and `"0"` are false, `"false"`
  is true. This is not explicit boolean conversion.
- Boxed table/object kinds still throw if evaluated (MIXED-TRUTH-001); short circuit
  can bypass an invalid RHS. Both source operands still undergo preparation.
- Directly typed string peers, fixed-width peers, literal-null peers, direct dynamic
  handles, mixed `xor` and wrapper logical operands remain outside this slice.
  In particular, wrapper logical operations require their own guarded-unwrapping
  contract, not automatic admission through condition truthiness.

Proof owners: `tests/mixed.php` (facts, admission, cleanup, incremental recovery)
and `tests/fixtures/mixed_logical.php` (truth tables, spellings, effects, runtime
errors and scalar peers). No native compiler rebuild is implied.

Verification: 15 PHP suites and 290 generated programs pass in
`/tmp/my-try-mixed-logical-20261004-3`; all 16 new programs also pass without PCH in
`/tmp/my-try-mixed-logical-20261004-no-pch`. Initial PHP attempts exposed the existing
sequenced-call restriction and stale rejection expectations; these were repaired
before the first generated-program run, which passed.

## Mixed numeric updates — 2026-10-04

Implemented `+=`, `-=`, `*=`, `/=`, `%=` and prefix/postfix `++`/`--` for established
mixed locals and parameters. Compound right operands reuse numeric arithmetic
admission (matching mixed, ordinary int/float; no statically known float for `%=`).
No new AST node is needed. `Mutation_Preparation` owns storage validation and
records a `compound_write_back` mode alongside computation/conversion facts.
The backend binds the target once, computes once, then commits through the shared
runtime stored-kind guard. Prefix/postfix return owned updated/previous snapshots.

Important consequences:

- The top-level contract and runtime config require assignment to the original
  stored kind. Current numeric assignments are int-to-int and float-to-float.
  Mixed int `+=` float therefore throws, including an integral float result;
  mixed float `+=` int succeeds because the binary result is float.
- Runtime compound operators previously replaced the kind unconditionally. Both
  those operators and generated compiler updates now use `assign_compound_result`.
  This repair intentionally changes that behavior to follow the agreed contract.
  Ordinary assignment, including `$v = $v + 0.5`, can still change the mixed kind.
- Invalid numeric payloads, zero divisors and invalid stored-kind write-back fail
  before changing the target. Postfix validates the numeric kind before cloning,
  avoiding access to absent table payloads on an operation that must reject anyway.
- A later consumer conversion is a separate step: `return ++$v` from an int-returning
  function may update a mixed float successfully and then fail the required-int
  return conversion. Likewise a compound result's failed outer conversion does not
  roll back the already committed update.
- Value parameters keep their existing copy semantics; reference parameters update
  the caller. Result snapshots do not alias subsequent target changes. Eager binary
  operand-order restrictions and stable-reference requirements remain unchanged.

Non-goals: property/index targets, direct dynamic-handle mutation, mixed bitwise or
concatenation updates, power updates, fixed-width peers and native-typed targets
with mixed right operands. Weak comparison debt remains separate.

Proof owners: `tests/mixed.php`, `tests/fixtures/mixed_updates.php`, existing mutation/
compound suites, and the runtime's `runtime_mixed_t_003_compound_unary_logical.cpp`.
The focused runner now includes `compound_assignments.php`. This does not establish
a new native compiler checkpoint.

Verification: 15 PHP suites and 274 generated programs pass in
`/tmp/my-try-mixed-updates-20261004-2`. All 20 new update programs plus the focused
runtime regression pass without PCH in `/tmp/my-try-mixed-updates-20261004-no-pch`.
Generated-C++ checks passed first attempt; the initial PHP run exposed a stale
compound-suite helper named `take`, corrected to an ordinary function name before
the final run. The older monolithic native mixed test's widening expectation was
updated, but that whole historical smoke test is not part of this focused proof.

## Mixed comparisons and empty-carrier repair — 2026-10-04

Implemented `==`, `!=`, `===`, `!==`, `<`, `<=`, `>` and `>=` through the existing
binary AST and `Boxed_Operators`. All return owned bool values. Equality/identity
admit matching mixed or mixed with bool, ordinary int, float, string and literal
null in either order. Ordering admits mixed/mixed and mixed with ordinary numeric
peers. Known invalid peer types reject during preparation; invalid boxed payloads
remain runtime failures. Fixed widths, wrappers, `<=>` and direct dynamic-handle
comparisons remain deferred.

Important consequences:

- Ordinary equality boxes scalar/null peers and uses existing mixed dispatch.
  Strict identity preserves operand types, including literal null, and calls the
  shared identity helper. Neither path extracts a scalar from mixed first.
- Boxed int `1` and float `1.0` compare equal but not strictly identical. Numeric
  string equality follows the runtime equality contract; strict identity does not
  perform numeric-string conversion.
- Literal-null identity normalizes empty handles. Two mixed operands compare their
  runtime kinds first: an empty boxed dynamic can be identical to literal null but
  not to a mixed containing null. Do not replace this with a raw tag test or box
  literal null before strict identity.
- Existing table behavior is operation-specific: ordinary dynamic/shared equality
  compares target identity, while same-kind boxed table strict identity compares
  contents. Different boxed kinds remain non-identical. Owned-table ordinary
  equality and table ordering throw; this slice does not introduce deep equality.
- The runtime strict-identity helper previously dereferenced null table pointers.
  With user approval, its same-kind table branch now compares absent handles before
  dereferencing: both absent is true; exactly one absent is false. Nonempty content
  comparison is unchanged. An allocated empty table is not an absent handle.
- The identity header now includes the operator declarations used by its scalar
  helper instantiations. Those dependencies previously arrived indirectly through
  umbrella headers/PCH; standalone mixed/scalar identity exposed the missing include.
- Eager binary operand-order restrictions remain. Strict literal-null comparisons
  retain the existing once-only effectful-operand allowance. Failed comparisons
  leave their destination unchanged.

The original empty-dynamic repro and expanded
`tests/runtime/native/test_strict_identity_null_dynamic.cpp` pass with UBSan in
`/tmp/my-try-mixed-comparison-runtime-20261004`. Compiler proof owners are
`tests/mixed.php` and `tests/fixtures/mixed_comparisons.php`. This is a focused
runtime repair and generated-program proof, not a native compiler rebuild.

Final verification: 14 PHP suites and 254 generated programs pass in
`/tmp/my-try-mixed-comparisons-20261004-2`. The 20 comparison programs plus the
empty-handle regression, shared-identity regression and original failure probe pass
without PCH and with UBSan on their translation units/inline helpers in
`/tmp/my-try-mixed-comparisons-20261004-no-pch-2`. The shared snippet runtime object
is not sanitizer-instrumented. First PHP checks passed; the first generated pass
exposed test-only name-mangling expectations and the missing identity-header
dependency. Both were corrected before the final passing runs.

**MIXED-COMPARE-001 debt:** reconcile the normative weak-table equality wording
(both `==`/`!=` described as false on expiration) with the existing runtime's
`!=` implementation as the negation of `==`. Weak-carrier semantics are not changed
by this slice. Recursive/cyclic table content-comparison hardening is also deferred.

## Mixed numeric arithmetic — 2026-10-04

Implemented binary `+`, `-`, `*`, `/`, `%` and unary `+`/`-` for mixed values.
`Boxed_Operators` owns candidate selection on existing expression nodes; retained
operator decisions distinguish mixed runtime dispatch from native integer operations.
Operand conversion keeps matching mixed unchanged and boxes ordinary int/float peers.
No operand is implicitly extracted to a scalar and the result remains an owned mixed.

Important consequences from the [runtime operator contract](../../../../specs/dynamic_types.md#23-operator-matrix-runtime):

- `+`, `-`, `*`, `/` accept mixed/mixed and mixed with ordinary int/float in either
  order. `%` admits mixed/mixed or mixed with ordinary int; known float peers reject
  during preparation because no runtime payload could make that pairing valid.
- Actual numeric payloads follow existing numeric runtime rules: int/int division
  keeps an integer payload; a float participant in the other numeric combinations
  produces a float payload. No PHP numeric-string, bool or null coercion is added.
- Nonnumeric boxed payloads, float payloads for `%`, and zero divisors throw at
  runtime. Merely having a mixed static type is not a reason for rejection.
- Typed destinations use the existing checked boundary after arithmetic. A float
  result assigned to required int fails even when numerically integral. Failed
  computation/conversion leaves the destination unchanged.
- Direct dynamic handles remain nonnumeric. Future reads of dynamic properties
  that produce mixed values will reuse this path; property access is not enabled.
- Existing eager binary operand-order restrictions remain. Unary calls evaluate
  once. Comparisons, bitwise/logical expansion, power, compound updates, increment/
  decrement and fixed-width peers are deferred; compound updates have an explicit
  gate so shared binary candidate selection cannot enable unchecked write-back.

Legacy/runtime review: numeric operator overloads already dispatch mixed kinds and
delegate to native numeric rules; runtime mixed unary/compound and zero-divisor
tests corroborate that behavior. No runtime changes are required for this slice.
Proof owners: `tests/mixed.php` (facts, cleanup, rejections and incremental recovery),
`tests/fixtures/mixed_arithmetic.php` (generated runtime behavior). This does not
establish a new native compiler checkpoint.

Verification passed first attempt: 14 PHP suites and 234 generated programs in
`/tmp/my-try-mixed-arithmetic-20261004-1`; the 16 new arithmetic programs also passed
without PCH in `/tmp/my-try-mixed-arithmetic-20261004-no-pch`. Existing scalar,
wrapper, conversion and mixed behavior remains covered by the focused runner.

## Explicit mixed/dynamic bridges — 2026-10-04

Historical checkpoint, superseded for v0.2 by the
[separated-family contract](../../../../specs/dynamic_types.md#v02-migration-and-repair-rules).
Both explicit casts now use checked scalar/null conversion; no shared-container
bridge or empty-handle tag survives. Current generated tests follow that replacement,
not the historical expectations below.

Implemented `(mixed)$dynamic` and `(dynamic)$mixed` using the existing cast AST
and role-based conversion preparation. `Mixed_Conversions` owns bridge permission;
the retained `mixed_dynamic_box` operation preserves the dynamic carrier tag, while
extraction uses the existing explicit runtime-cast operation.

Important consequences, following [the source contract](../../../../specs/dynamic_types.md#phase-1-first-class-source-dynamic):

- Boxing preserves shared identity and `dynamic_v`, including an empty handle.
  An empty boxed dynamic is not a null mixed value.
- Extraction accepts null (an empty handle), shared-table and dynamic payloads.
  Scalar, owned-table and weak-table payloads fail at runtime; they are not cloned
  or promoted. Source values are not consumed.
- Cast operands execute once. Failed extraction does not overwrite the destination.
- Assignment, argument and return boundaries do not gain automatic bridges.
  Scalar-to-dynamic and arbitrary nominal casts remain preparation errors.
- Object construction, `(object)`, indexing, `->`, containers and iteration remain
  separate slices. No new AST or semantic type definition was needed.

Proofs: `tests/mixed.php` covers role-based decisions, facts/cleanup and rejected
implicit bridges; `tests/fixtures/mixed_dynamic_casts.php` covers generated-C++
identity, null/empty tags, runtime rejection and once-only evaluation. This is not
a native compiler rebuild checkpoint. Focused verification passed 14 PHP suites
and 218 generated programs in `/tmp/my-try-mixed-dynamic-casts-20261004-2`;
the 8 new programs also passed without PCH, with updated incremental PHP checks,
in `/tmp/my-try-mixed-dynamic-casts-20261004-no-pch`. The first PHP attempt required
one test-only AST-field correction; generated-C++ verification passed first attempt.

## Handoff from chapter 04 — 2026-10-02

[Chapter 04's bounded first pass is complete](04_functions.md#positive-pass-conclusion--2026-10-02).
Reuse explicit signature preparation, parameter storage, argument/return conversions,
value/reference boundaries and default ownership. Nullable syntax/values and wrapper
boundaries are not implied by successful scalar or variadic tests. Chapter 04's
[native compiler checkpoint](../portability/conversion_review.md) now passes,
including PHP/native C++ parity and generated-program execution. LLVM and legacy
STAN remain excluded; broader nullable/wrapper behavior is not certified.

Proposed discussion groups (not implementation approval or new semantic decisions):

1. **Typed null and nullable foundation:** LIT-NULL-001, TYPE-VAR-002,
   FUNC-NULLABLE-001, NULL-CHECK-001/002 and relevant NOTE-036/037 rules. Agree
   contextual null typing, present/absent values and callable conversions together.
2. **Presence and fallback:** VAR-ISSET-001, VAR-EMPTY-001, EXPR-COALESCE-001 and
   NOTE-045. Keep null checks distinct from truthiness; scalar Elvis already works.
3. **Wrapper/mixed boundaries:** TYPE-VAR-006B, NOTE-005/052/063 and applicable
   NOTE-010 rules. Review nesting, extraction and failure behavior from owning specs;
   do not assume all wrapper families belong in one implementation slice.
4. **Conversion reconciliation:** retain proved scalar CAST-INT/FLOAT/BOOL/STRING
   behavior, then discuss CAST-OBJECT-001 and remaining NOTE-015/010 mappings with
   the relevant type owners. No automatic expansion of scalar conversion rules.

Start discussion with group 1. The imported `$a = null` example is not an instruction
to infer a nullable type from an untyped null. No Chapter 05 code or new support claim
is introduced by this handoff.

## Nullable model foundation — 2026-10-03

Implemented the agreed model-only slice: `abstract_template_type_definition` owns
the shared recipe; final `template_type_definition` remains the ordinary case and
final `nullable_type_definition` implements `nullable_semantics`. Its payload query
requires an application of that same definition. Registration, template preparation,
arity/capability validation and canonical application interning share the abstract
path. Source spelling alone grants no nullable role; backend bindings are unchanged.

The five focused PHP suites (`type_catalog`, `constructed_type`, `conversions`,
`incremental_cpp`, `preparation_recovery`) and the portable type proof pass; the
proof's emitted C++ also compiles and executes successfully. Checks use
the shared runner with CLI fallback after sandbox FPM socket startup failed.
Evidence: `/tmp/my-try-nullable-model-20261003/` and
`/tmp/my-try-nullable-model-proof-20261003/`.
The portable proof is retained for future explicitly requested native checkpoints;
the compiler itself has not been rebuilt natively for this refactor.

That checkpoint was model-only; the following slice adds value operations.

## Nullable value boundaries — 2026-10-03

The agreed first pass supports scalar payloads (`bool`, integer families, `float`,
`string`). `?T` and `nullable<T>` resolve to the same canonical application.
An internal, non-storable null sentinel supplies literal identity; it has no source
type exposure or storage binding. `$x = null` still rejects without a destination.

- Typed empty construction/reset and exact-payload wrapping use destination conversions.
- Same-payload copying preserves presence and independent value state.
- Required assignment/argument/return extraction checks presence and throws
  `invalid_nullable_unwrap_empty`; failed assignment preserves its target.
- Explicit same-payload casts retain the runtime contract, including empty nullable
  string to `""`. Required string extraction still throws on absence.
- `=== null` / `!== null` work in either order, evaluate the nullable operand once,
  and test presence rather than payload truthiness. Reference parameters retain
  exact-storage rules; extraction never manufactures a reference.

Owners: `conversions/nullable.php`, `operators/nullable.php`, shared type/expression
preparation and prepared decisions consumed by C++ emission. Tests: `tests/nullable.py`
and the `nullable_*` exports in `tests/s2s.php`; incremental recovery and syntax/fact
cleanup are covered by `tests/nullable.php`. This is PHP-hosted compiler plus generated
C++ execution evidence, not a native compiler rebuild.

Focused checkpoint: nine PHP suites and 33 generated programs/probes pass in
`/tmp/my-try-nullable-values-20261003-final/` (12 tasks, CLI fallback because sandbox
FPM socket creation was unavailable).

Debt/non-goals: cross-payload lifting/conversion and wrapper-valued coalescing fallback,
keyed/property-path probes, nullable parameter defaults, flow narrowing, mixed and object/container
payload operations. Existing generic type formation does not imply those operations.
The runtime generic identity helper currently fails to instantiate nullable/null
comparison (its fallback attempts `null_t == null_t`). This slice emits the equivalent
prepared presence test; runtime-helper repair belongs to a separate owner/task.

## Nullable truthiness and coalescing — 2026-10-03

`Condition_Truthiness` now admits nullable scalar values for every existing condition
consumer: empty is false, otherwise the runtime applies payload truthiness. Thus
present false, zero, `""` and `"0"` are falsey without being absent. Explicit boolean
casts and required extraction are unchanged; there is no flow narrowing or new
nullable arithmetic/logical-operator admission.

Dedicated `coalesce_expression_node` syntax owns `left` and `fallback`. Shared
`Coalesce_Expression_Preparation` selects exact scalar result/conversion facts:
`nullable<T> ?? T -> T` and `T ?? T -> T`. The expression is an owned value, not a
reference. Parsing is right-associative, stronger than ternary and weaker than `||`.
Both operands are prepared and validated; execution evaluates the left once and
evaluates fallback only when the nullable left is absent. Present falsey payloads
are selected, never replaced. `$a ?? $b ?? 7` works when the inner expression yields
the required nonnullable scalar fallback.

The C++ backend renders explicit lazy control flow from these facts and reuses
checked extraction. Nullable conditions reuse the runtime condition helper with
its cast-header dependency. No runtime or legacy-generator changes are required.
Focused proofs live in `tests/nullable.php`, `tests/s2s.php` and `tests/nullable.py`:
condition consumers, falsey values, effect counts, lazy failures, precedence,
owned results, rejected fallback types, cleanup and incremental fallback changes.

Checkpoint: ten focused PHP suites and 62 generated C++ programs/probes pass in
`/tmp/my-try-coalesce-fourth-20261003/`, using the shared 12-slot runner with CLI
fallback after sandbox FPM startup failed. This is not a native compiler rebuild.

Still deferred: nullable fallback operands, cross-type joins, bare-null coalescing,
mixed/other wrapper families and `??=`.

## Nullable conditional results — 2026-10-03

Full ternary and Elvis share the existing conditional node and prepared branch
conversions. Conditional preparation joins both alternatives symmetrically: `T/T`
stays `T`; `nullable<T>/T`, `T/nullable<T>` and `nullable<T>/nullable<T>` yield
`nullable<T>` for exact supported scalar payloads. Selected scalar values are wrapped;
selected nullable values are copied with their presence intact. Empty selected results
do not throw until consumed at a required-value boundary.

Elvis captures the original condition type once, tests its truthiness, and converts
only the selected branch. This also covers scalar-left/nullable-fallback expressions.
All results are owned values, not references. `match` and `??` admission remain
unchanged. Bare-null branches, cross-payload joins and other wrappers/mixed remain debt.

Proofs in `tests/nullable.php` and `tests/s2s.php` cover both branch orders, exact result
types, independent copies, empty results, wrapping, scalar-left Elvis, lazy exceptions,
effect counts, downstream required extraction and incremental join failure/recovery.

Checkpoint: ten focused PHP suites and 81 generated C++ programs/probes pass in
`/tmp/my-try-nullable-joins-20261003-final/` using the shared 12-slot runner (CLI
fallback after sandbox FPM startup failed). The compiler was not rebuilt natively.

## Single-value probes — 2026-10-03

`value_probe_node` owns one operand and an `isset`/`empty` kind; these are language
expressions, not ordinary function-name lookups. `Value_Probe_Preparation` admits
supported scalar values, nullable scalars and literal null, retaining the selected
operation and boolean result. C++ calls the corresponding PHP runtime probe helper.

`isset` is false for null/empty nullable and true for present scalar values, including
false, zero and empty strings. `empty` is true for null/empty nullable, false, numeric
zero and `""`; **`empty("0")` is false**, the documented Simple C++ deviation from PHP.
Present nullable payloads use those same emptiness rules, never negated truthiness.
The operand is evaluated once; probes introduce no mutation, but explicit operand
side effects and exceptions retain normal expression behavior. No blanket exception
catching or implicit extraction is added.

Zero/multiple arguments and undeclared names reject. Direct keyed/property-path
operands, containers, mixed and other wrappers remain deferred; ordinary value
expressions do not acquire missing-path suppression. Supporting those families later
must follow the normative non-mutating probe contract, not ordinary required reads.
Tests cover the scalar matrix, sentinel states, one evaluation, non-mutation, runtime
error propagation, arity, syntax traversal/cleanup and incremental admission recovery.

Checkpoint: eleven focused PHP suites and 99 generated C++ programs/probes pass in
`/tmp/my-try-probes-20261003-final/` (shared 12-slot runner, CLI fallback after sandbox
FPM startup failed). No native compiler rebuild was performed.

## Result-wrapper model checkpoint — 2026-10-03

One final `result_type_definition` extends the shared template abstract and implements
`result_semantics`. Its immutable constructor-set `result_kind` distinguishes
`structured_error`, `false_sentinel` and `boolean_sentinels`. The accessor is
`result_family()` because inherited `result_kind()` already means class/struct output.
Each registered source family (`result`, `result_or_false`, `result_or_bool`) retains
its own definition identity, application interning and runtime C++ binding.
Payload queries reject applications belonging to other definitions, even of the same
family. The initial recipe requires one value-storable payload, like nullable;
`void` specializations and broader payload contracts are not added by this checkpoint.

This is model/registration work only: no family-specific construction, extraction,
operators, probe/condition policy, `take`, structured-error model or profile gates.
Existing generic type infrastructure is reused; registered shape is not a claim of
completed Result expression support. The strict guide currently also mentions the
compatibility wrappers; deciding profile availability remains separate.

`tests/result_model.php` proves family/definition separation, repeated application
identity, boolean payload identity, arity/default and wrong-owner rejection, and
the three C++ spellings/headers. Six focused PHP suites pass through the shared
12-slot runner in `/tmp/my-try-result-model-20261003-2/` (CLI fallback). No native
compiler rebuild or Result runtime execution is claimed.

## Result construction — 2026-10-03

The next bounded slice enables all three registered Result families in the source
type scope. `conversions/results.php` owns exact scalar payload construction and
same-family/same-payload copying at declaration, assignment, argument and return
boundaries. Boolean, integer families, float and string are admitted. Copies preserve
the active state; value parameters remain independent and explicit reference
parameters retain exact-storage rules.

Legacy non-value construction uses explicit `false_sentinel`, `true_sentinel`
(only `result_or_bool`) and `null`/`nullopt` (false state). Bare bool values are
payloads for bool specializations, never implicitly rewritten into sentinel states.
Non-bool payload specializations currently require explicit sentinel forms; the
legacy generator's destination-sensitive literal-false rewrite is not reproduced.

The two boolean markers have distinct canonical runtime nominal tag identities,
no source storage-type exposure, no storage capabilities and no storage C++ binding.
Existing constant-reference syntax/lookup carries singleton sentinel definitions;
`nullopt` uses the existing null semantic identity. No new AST node, Result application
class, or type-definition subclass is needed. Construction facts select wrap/false/true
operations; emission constructs the target wrapper explicitly with the prepared
operand (`scpp::result_or_bool<scpp::bool_t>{scpp::true_sentinel}`, for example).
Exact copies use ordinary value initialization/assignment. Narrow runtime headers
are recorded by the existing type/constant emitters; no umbrella-header expansion or
new runtime helper is needed. Compilation-cost tuning is outside this slice.

Tests: `tests/results.php`, `tests/fixtures/result_construction.php` exported by
`tests/s2s.php`, and the shared-pool `tests/results.py` runner. Native state probes
at this construction checkpoint inspect payloads and sentinel states independently
of source extraction.
One C++-supplied error value tests copying through a generated source function;
it does not enable source error construction. Preparation facts, syntax/emission
purity, single evaluation, copy independence, failures and incremental repair have
focused coverage.

Checkpoint: nine focused PHP suites and 30 generated programs/state probes pass
with PCH in `/tmp/my-try-result-construction-20261003-final3/` and without PCH in
`/tmp/my-try-result-construction-20261003-final2/`, using the shared 12-slot pool
with CLI fallback after sandbox FPM startup failed. A supplementary `model.php`
invocation passed the updated type-root checks, then reproduced the already recorded
[parked LLVM recovery failure](../portability/conversion_review.md); it is not counted
as a passing suite. No native compiler rebuild was performed.

Debt/non-goals:

- **RSL-ERROR-01:** structured `error` construction and getters belong
  to [chapter 13](13_errors.md#structured-error-value-debt-from-chapter-05).
  `error -> result<T>` failure construction is blocked on that prerequisite. Use a
  reusable runtime-type construction/member-call path, not an error-specific AST.
  Do not fabricate an empty error to bypass this dependency.
- Explicit casts, logical/arithmetic operators, broader selection/coalescing joins and
  profile-availability changes remain follow-up work.
- Implicit default construction, cross-payload/family conversion, non-scalar/nested
  payload operations (including `result<error>`), and legacy implicit bool-sentinel
  admission remain deferred. Current Result locals require an explicit initializer.

## Checked Result extraction — 2026-10-04

All three Result families now support exact scalar payload extraction at required
assignment (including initialization), by-value argument and return boundaries.
`Result_Conversions` records `result_extract`; C++ copies the checked payload with
`T((expression).require_value("Result required-value extraction"))`. This extends the
existing conversion owner and adds no AST, runtime helper or new header dependency.
The legacy runtime's guarded typed bridge is reused explicitly; implicit C++ casts
do not decide source admission. No compile-cost tuning is part of this slice.

Success values include false, zero and empty string. Error, false-sentinel and
true-sentinel states all throw through the runtime guard, including true/false
sentinels in bool-payload specializations. Required string extraction also throws;
there is no empty-string fallback. Sources evaluate once, values are copied rather
than borrowed, and failed stores leave their targets unchanged. An argument failure
prevents the called function from running. Reference parameters still require exact
storage compatibility and never extract a payload implicitly.

Explicit casts, cross-payload conversion, wrapper-to-nullable conversion,
logical/arithmetic operators and broader payloads remain deferred. Real structured error values
are supplied only by the native proof bridge; RSL-ERROR-01 remains owned by chapter 13.
Take and minimal no-error support are implemented in the next section.
`tests/results.php` covers attached decisions, exclusion diagnostics, cleanup and
incremental payload-change failure/retry/repair. The shared Result fixture export
covers success and failure at every boundary, scalar types, independent copies and
single evaluation through generated C++ execution.

Checkpoint: nine focused PHP suites and 90 generated programs/state probes pass in
`/tmp/my-try-result-extraction-20261004-final/` with PCH disabled, through the shared
12-slot runner (CLI fallback after sandbox FPM startup failed). The first PCH-backed
run also passed its 88 programs, before adding the byte/uint8 alias fixture.
This proves the PHP-hosted compiler and generated C++ behavior, not a native compiler
rebuild. LLVM and source error construction remain deferred.

## Conditional take and no-error values — 2026-10-04

Contract: [error values and take](../../../../specs/error_values_and_take.md).
Primary `take($value[, $alternative]) = source` and compatibility helper spelling
normalize to one dedicated node. `Take_Preparation` validates wrapper role, exact
scalar payload/output types, existing locals/parameters and source copy capability.
Named AST edges support cleanup and incremental work; C++ snapshots the source once
before calling the existing runtime extraction helper. Take returns bool and preserves
the existing restrictions on effects inside eager operator operands.

Nullable/falseable missing states preserve the value output. Structured Result success
copies the value and clears the nonnullable error output; failure copies the error and
preserves the value. Boolable value states preserve the flag, while sentinel states
write/return the flag and preserve the value. False/zero/empty payloads are successes.
Source exceptions propagate, not turn into false.

The registered nominal `error` has default/copy semantics and readonly
`error::$no_error`. Conditions and `!$error` use explicit presence. A real empty-message
error stays true. Runtime Result failure construction/assignment rejects no-error;
const inspection prevents bypassing that invariant. Source constructors/getters and
`error -> result<T>` remain chapter 13 debt. Qualified constant lookup is not general
static-member support.

Deferred: inline typed/inferred take declarations and their branch availability/scope,
tuple spelling, aggregate payloads, fields/index outputs, reference-alias analysis and
reserved intrinsic declaration validation. The parser currently reserves take use;
it does not inherit the older generator's user-function override behavior. No LLVM,
profile gate or native compiler rebuild is included.

Proof: `/tmp/my-try-take-20261004-final/` passed 11 PHP suites, 104 generated C++
programs/state checks and two runtime tests with PCH disabled, using the shared
12-slot runner (CLI fallback after sandbox FPM socket denial). The earlier PCH run
passed before the final string/float and exception probes were added. Coverage includes
all wrapper states, both spellings, error reset/truthiness, single evaluation, lazy
skipping, unchanged outputs, cleanup and incremental failure/retry/repair. The v0.3
auxiliary-data idea is recorded only in top-level `docs/v0.3/result_auxiliary_data.md`.

## Result conditions and value probes — 2026-10-04

All three Result families now admit supported scalar payloads in ordinary conditions
(`if`/`elseif`, loops and full ternary conditions) and single-value `isset`/`empty`.
`Condition_Truthiness` records `result_condition`; emission calls the existing shared
runtime authority. `Value_Probe_Preparation` admits the same role/payload domain and
keeps the existing distinct presence and emptiness helpers. No new AST or runtime
semantics are introduced, and no source-name check grants Result behavior.

The [documented consequences](../../../../specs/conditional_expression_matrix.md#result-truthiness-is-not-success-or-presence)
are preserved: falsey successful payloads remain present and extractable; string
`"0"` is falsey but not empty; a true sentinel is truthy/present without carrying a
payload; a failed Result is falsey while its contained real error is truthy.
The [probe state table](../../../../specs/count_empty_isset_contract.md#result-wrapper-states)
is explicit. These rules must not be collapsed into a generic success flag.

The following slice adds matching Result/scalar ternary and Elvis joins; approved
Result coalescing is implemented in its checkpoint below.
Wrapper logical operators (`!`, `&&`, `||`) remain separate contract work; do not
silently replace their documented guarded-unwrap semantics with condition truthiness.
Aggregate/nested payloads, path probes and profile availability remain deferred.
Full ternary with a Result condition and already-supported scalar branches works;
Result-valued branch joins and shorthand Result Elvis are not added in this slice.

Proof: `/tmp/my-try-result-probes-20261004-1/` passed 11 PHP suites, 130 generated
C++ programs/state checks and two runtime tests with PCH disabled. The shared
12-slot runner used CLI fallback after sandbox FPM socket denial. New probes cover
all Result families with bool/int/float/string payloads, non-value states, string
`"0"`, single evaluation and all existing condition consumers. PHP checks cover
role-based admission, prepared facts/cleanup, exclusions and incremental invalidation,
retry and clean-build agreement. No native compiler rebuild or LLVM work is claimed.

## Result-valued ternary and Elvis joins — 2026-10-04

`Conditional_Expression_Preparation` now admits `W<T>/W<T>`, `W<T>/T` and `T/W<T>`
as `W<T>` for each Result family independently, with an exact supported scalar payload.
The join preserves canonical wrapper identity and uses existing copy/wrap conversions.
No AST, runtime or emitter changes were needed. Match retains its separate scalar-only
admission; no cross-family, cross-payload or nullable/Result join is inferred.

Full ternary preserves the selected wrapper's state, including structured errors and
legacy sentinels. Scalar false stays a value payload, not a false sentinel. Elvis saves
its left operand once and selects fallback for falsey successful payloads (`false`,
zero, empty string and `"0"`); true sentinels remain selected without creating payloads.
Only the selected branch executes; both branches are validated during preparation.
Results are copied owned values, never conditional references into the source.

Bare-null/sentinel branches, aggregate payloads, mixed and broader joins remain debt.
Approved Result `??` with a required same-payload scalar fallback follows below.

Proof: `/tmp/my-try-result-joins-20261004-final/` passed 11 PHP suites, 148 generated
C++ programs/state checks and two runtime tests without PCH. The earlier PCH run
also passed before the numeric join fixtures were added. Shared 12-slot execution
used CLI fallback after sandbox FPM socket denial. Tests cover both join orders,
all four scalar categories, preserved error/sentinel states, false-payload wrapping,
single Elvis evaluation, lazy alternatives, independent copies, prepared conversions,
cleanup and incremental payload-change failure/retry/repair. This is PHP-hosted
compiler/generated-C++ evidence, not a native compiler rebuild.

## Result coalescing and early diagnostics — 2026-10-04

The [v0.2 diagnostic policy](../../../../specs/v0_2_preparation_diagnostics.md) now
requires preparation-time rejection when resolved types/structure prove an operation
invalid, even if the v0.1 type-blind generator needed runtime rejection. Valid
operations with value-dependent failures retain their runtime guards.

`result<T> ?? T` and `result_or_false<T> ?? T` now produce an owned scalar `T` for
exact supported payload types. Success preserves falsey values (false, numeric zero,
empty string and `"0"`); error/false-sentinel selects the lazy fallback. Source
evaluation occurs once, sources are not modified, and ordinary exceptions evaluating
either selected expression propagate. Both operands are prepared before emission.

`Coalesce_Expression_Preparation` owns admission and records selected left extraction
with `coalesce_value`; `Result_Conversions` reuses checked payload extraction. The
existing AST, presence-based lazy emitter and runtime are unchanged. Boolable operands
on either side reject during preparation, even behind an always-present scalar left.
This is a language-invalid family, unlike deferred wrapper-valued fallbacks, nested
payloads, mixed, bare-null and cross-payload joins. Logical operators remain separate.

At this checkpoint mixed support was next; the subsequent mixed slices and the
positive-pass conclusion above now own that status. Profile/object expansion remains deferred.

Proof: `/tmp/my-try-result-coalesce-20261004-final/` passed 11 PHP suites, 166 generated
C++ programs/state checks and two runtime tests with PCH. The initial no-PCH run
passed all except a probe whose handwritten bridge used an unmangled function name;
that probe was corrected and passed without PCH in
`/tmp/my-try-result-coalesce-20261004-supplement/`, together with the final PHP Result
suite. Coverage includes all scalar categories, falsey successes, lazy exceptions,
owned unwrapped results, unchanged sources, early boolable rejection and incremental
family-change failure/retry/repair. Shared 12-slot execution used CLI fallback after
sandbox FPM denial. No native compiler rebuild or LLVM work was performed.

## Native portability checkpoint — 2026-10-04

The implemented nullable and Result slices through coalescing now have a passing
[native compiler checkpoint](../portability/conversion_review.md#latest-native-checkpoint--2026-10-04-nullable-and-result-slices).
Two authored-source repairs were required: portable decrement in the take parser
and explicit `null_type` field initialization after its parent constructor. Neither
changes language semantics or expands the agreed chapter scope.

The run passes 702 PHP/native source comparisons and generated-program executions,
179 supplementary probes, two control-flow error probes, 198 rejection/recovery
cases and 15 float-spelling assertions, plus the separate type/emission and
control-flow incremental proofs. Nine focused PHP suites also pass. Evidence:
`/tmp/my-try-ch05-native-20261004/` and
`/tmp/my-try-ch05-portability-php-20261004/`.
Earlier slice notes describe their original PHP-hosted checkpoints; this checkpoint
adds native coverage. LLVM, legacy STAN and warning cleanup remain excluded.
Mixed and the remaining chapter debt are unchanged; this is not chapter closeout.

## Mixed and dynamic model foundation — 2026-10-04

Implemented the agreed model-only slice following
[`dynamic_types.md`](../../../../specs/dynamic_types.md). `mixed` is a broad boxed
value; `dynamic` is shared object/table storage whose ordinary copies preserve
identity. Neither is an unknown/unresolved compiler type, and they are not aliases.

`abstract_nominal_type_definition` now owns the common nominal code, with final
ordinary, mixed and dynamic definitions. Separate `mixed_semantics` and
`dynamic_semantics` marker interfaces identify language roles without replacing
nominal classification. The existing canonical nominal type, source exposure,
language scope and backend binding paths are reused. Mixed uses value storage;
dynamic uses class-like shared storage. Both register copyable/storable contracts;
broader operation capabilities are not promised. C++ bindings are `scpp::mixed_t`
and the concrete `scpp::dynamic_t<>` spelling; no source template parameters are
introduced for dynamic.

Eight focused PHP suites pass, including `tests/dynamic_model.php`, plus the shared
portable type proof and its generated C++ execution. The dynamic declaration sample
compiles without PCH, links against the runtime library from the preceding native
checkpoint and executes successfully. Evidence:
`/tmp/my-try-dynamic-model-20261004-verified/` (shared 12-slot runner, CLI fallback).
This is PHP-hosted compiler/model and generated-program evidence, not a rebuild of
the native compiler. The first end-to-end attempt exposed missing language-scope
publication; the next exposed the header-only fixture's missing runtime linkage.
Both are accounted for in the final proof.

The next slice below implements scalar boxing, copying and checked typed boundaries.
Required extraction must remain distinct from explicit casts. Runtime-kind dispatch
must handle admitted value-dependent operations; uncertainty alone must not trigger
preparation rejection. By-reference normalization remains prohibited. Existing
default declaration and identity paths do not imply completed dynamic operations.

Deferred at the model checkpoint: indexing, operators, conditions/probes, iteration (Chapter 06), and dynamic
`->`. Discuss fixed-name property reads/writes, computed property names and method
calls separately; current Phase 1 uses `[]` and does not promise method dispatch.
The following slice supplies normal snippet-runtime linkage for mixed execution
fixtures; generated semantics do not change to work around missing linkage.

## Mixed scalar value boundaries — 2026-10-04

`Mixed_Conversions` owns role-based admission through the existing conversion path.
`mixed_box` constructs a box from null, bool, ordinary int, float or string;
`mixed_extract` emits `scpp::required_cast<T>` at typed assignment, by-value argument
and return boundaries. Same-type copies reuse identity conversions and existing
copy-capability checks. No new AST, runtime tag model, runtime semantic change or
backend type inference is introduced. Untyped copies retain mixed identity.

Required int accepts only integer payloads. Required bool/float/string use their
configured scalar conversion rules after rejecting null: boolean strings are the
documented five literals, numeric strings require valid full parses, and string
destinations use the existing text rules. Runtime-incompatible kinds throw; the
compiler does not reject a valid boundary merely because its payload may fail.
Explicit mixed casts use a separate path in the subsequent operation slice below.

Scalar/string copies preserve independent values; boxed dynamic handles and direct
dynamic copies preserve shared identity. Native bridge fixtures supply dynamic/table
inputs without enabling source cross-carrier construction. Failed extraction and
source exceptions preserve the destination; source evaluation occurs once, arguments
are sequenced through existing snapshots, and failing arguments do not enter the
callee. This is not a blanket strong exception guarantee for allocation failures.
Actual mixed locals can bind to mixed reference parameters; implicit reference
boxing/extraction and interior references remain prohibited.

The focused `tests/mixed.py` checkpoint passes ten PHP suites and 84 generated
programs/state probes with PCH. Six selected probes also pass without PCH, and four
fixture-compiler tests cover runtime/PCH setup. Evidence:
`/tmp/my-try-mixed-boundaries-20261004-verified/` and
`/tmp/my-try-mixed-boundaries-20261004-no-pch/`.
Earlier attempts exposed a test's inferred-statement inspection mistake, missing
headers in a native-only bridge probe and inconsistent PCH thread flags; these were
test setup issues, not changed language rules. PHP uses the shared 12-slot runner
with CLI fallback after sandbox FPM denial. The compiler itself was not rebuilt
natively; incremental rejection/retry/repair and cleanup evidence is PHP-hosted.

The focused and general fixture compilers build `runtime/include/core/runtime.cpp`
once per run, then link that object into generated programs. Requested native
validation uses the same helper for its source/probe sweep and records the new
fixture/helper hashes. No expired `/tmp` library dependency is required.

Remaining debt/non-goals: fixed-width boxing/extraction, mixed/dynamic cross-carrier
bridges, nullable/Result lifting, general operators,
conditional joins, indexing and iteration. Dynamic `->` remains a separate property
versus method-dispatch discussion. Older config/implicit runtime bridge descriptions
do not fully distinguish required int extraction from explicit conversion; follow
`dynamic_types.md` and the explicit required helper, not those broader fallbacks.
The following slice adds explicit scalar casts and mixed truthiness/probes.

## Mixed casts, conditions and probes — 2026-10-04

Agreed scope follows [dynamic typed extraction](../../../../specs/dynamic_types.md#explicit-typed-extraction),
the [condition matrix](../../../../specs/conditional_expression_matrix.md) and
the [probe contract](../../../../specs/count_empty_isset_contract.md). No runtime
semantic changes, new AST nodes or payload inference are needed.

- `Mixed_Conversions` admits explicit bool/ordinary-int/float/string casts using
  the existing `explicit_runtime_cast` fact and `scpp::cast<T>` lowering. Required
  typed boundaries still use `required_cast<T>`. Explicit int accepts configured
  string/float conversions; explicit string accepts null as an empty string.
  Failed value-dependent casts throw at runtime, not during preparation.
- `Condition_Truthiness` records `mixed_condition`. If/elseif, while/do/for and
  ternary conditions reuse the shared runtime helper. Mixed unary `!` records
  `condition_not` with that same truthiness decision, not a boolean cast.
  Null and scalar kinds follow current rules: `"false"` is truthy although its
  explicit boolean cast is false; `"hello"` is truthy although its bool cast fails.
- `Value_Probe_Preparation` admits mixed on existing `value_probe_node` syntax.
  The existing separate runtime helpers preserve null-sensitive presence and
  countable/scalar emptiness, including `empty("0") === false`. These queries do
  not mutate or create containers. Single-value operand exceptions propagate.

Legacy review: `Generator::renderGeneratedCast`, `renderRequiredTypedBoundaryCast`,
`renderConditionExpr` and AST_ISSET/AST_EMPTY lowering already separate these
authorities. Runtime condition-truthiness and scalar-empty regression examples
confirm the string distinctions. The compiler reuses those helpers but performs
static type/structure admission in shared preparation; it does not copy the legacy
generator's type-blind policy. Generated code adds only the helper calls and narrow
headers already owned by these paths; no new runtime template framework is introduced.

The single-operand preparation owner no longer applies a binary order-independence
gate to unary operands. Calls such as `!produce()` evaluate once; nested assignment
still cannot introduce a binding. Eager binary parents retain their subtree checks,
so this is not general effect-order support or broader binary operator admission.

**MIXED-TRUTH-001 debt:** the normative condition matrix explicitly tracks partial
mixed kind support. Boxed table/shared-table/dynamic/weak-table conditions currently
throw through the runtime helper. Admitting a mixed expression does not promise
every payload succeeds. Broader truthiness needs its own runtime/contract slice;
do not reject mixed statically or silently substitute handle-presence semantics.
Direct dynamic conditions/probes are not added here.

Non-goals of that operation slice (direct joins follow below): mixed-valued
ternary/Elvis/coalescing joins, binary mixed operators,
fixed-width casts, mixed/dynamic bridges, wrapper lifting, count, keyed/property
probes, indexing/iteration and dynamic `->` dispatch. These remain separate slices.

Proof owners: `tests/mixed.php` covers prepared facts, role-based admission,
rejections, cleanup and incremental failure/retry/repair with clean-output parity.
`tests/fixtures/mixed_operations.php` adds generated-program/state checks for
scalar casts/probes, every condition consumer, source exceptions, single evaluation,
unchanged targets after failed casts and non-mutating native table/object bridges.
The shared `tests/mixed.py` runner exports these into ordinary and requested native
sweeps; generated-program execution is not a native compiler rebuild.

Verified: ten focused PHP suites and all 144 generated programs/state probes pass
without PCH in `/tmp/my-try-mixed-operations-20261004-verified/`. Four additional
operator/mutation/take/control-flow PHP suites pass in
`/tmp/my-try-mixed-operations-20261004-controls-final/` and are now retained in the
focused runner. Both use the shared 12-slot pool with CLI fallback after sandbox
FPM denial. The first attempt exposed the unary operand gate; subsequent attempts
corrected a test expectation for empty boxed dynamic tables (countable emptiness),
stale unary rejection expectations, a test AST index and a reference fixture whose
ordinary function name collided with the newer `take` syntax. Runtime semantics
were not changed to accommodate those tests. No native compiler rebuild was run.

The next slice implements the direct mixed-valued selection rows below.

## Direct mixed selection joins — 2026-10-04

Agreed and implemented: `mixed`/`mixed`, `mixed`/`T` and `T`/`mixed` branch pairs
for ternary, Elvis and coalescing, with `T` limited to bool, ordinary int, float and
string. The result is owned `mixed`; payload kinds are not statically inferred or
converted to match the other branch. Selected scalars box; selected mixed values
copy, preserving scalar/string independence and shared table/object identity.

The [authoritative conditional matrix](../../../../specs/conditional_expression_matrix.md)
keeps condition truthiness separate from presence. Elvis saves its left value once
and uses shared truthiness. Coalescing retains false, zero, empty string and `"0"`,
and falls back only for the mixed null kind. A selected fallback that is itself
`mixed(null)` is a valid mixed result, not a missing-wrapper error. Full ternary and
coalescing can select boxed tables/objects without testing their truthiness; Elvis
still throws for those kinds under MIXED-TRUTH-001.

Ownership/target: the existing conditional/coalesce nodes, workers and lazy typed
lambda emitter remain in place. `Selection_Result_Preparation::boxed_join` admits
the bounded shared result domain and copy capabilities without widening scalar-only
match. `Mixed_Conversions` admits identity/scalar-box conversions at selection sites,
not required-value extraction or bare-null construction. `prepared_coalesce_expression`
now records `coalesce_presence` independently of conversions: always present,
wrapper value-state, or mixed non-null. This replaces the emitter's old assumption
that identity conversion implies presence. Existing scalar/nullable/Result rows keep
their prior emitted behavior; a scalar selected into mixed is boxed even though its
fallback can never execute.

Legacy review: `renderConditionalExpr` and `renderCoalesceExpr` use lazy runtime
helpers; the helper's mixed-presence rule tests only the null kind and normalization
permits a selected mixed null. v0.2 preserves those rules with prepared types,
conversions and presence rather than C++ helper deduction. No new runtime helpers,
AST classes or runtime semantic changes are needed. Target code reuses one typed
lambda and at most one saved left value; no compilation-speed claim is made.

Both branches are prepared, even when statically unselected. Bad type/structure
combinations reject before emission; source exceptions and invalid runtime condition
kinds still propagate at evaluation. Tests cover single evaluation, lazy fallback,
unchanged assignment targets on failure, scalar/string result ownership, table-handle
identity, prepared result/presence facts, cleanup and incremental invalidation/retry/
repair with clean-output parity.

Deferred at that checkpoint: bare-null branches, fixed-width scalar boxing, direct
dynamic bridges, mixed-valued match, wrapper/mixed joins and general mixed operators.
The following slice implements the valid language rows `nullable<T> ?? mixed`,
`result<T> ?? mixed` and `result_or_false<T> ?? mixed`.
`result_or_bool` remains invalid on either side of `??` and rejects
during preparation, including an unselected fallback.

Proof owners: `tests/mixed.php`, `tests/fixtures/mixed_selections.php` and the shared
`tests/mixed.py` runner. The requested native sweep exports the same source/probe
cases and records the added fixture hash. Native compiler rebuilding remains separate.

Verified on the `f741c7f4` base plus this slice: all 14 focused PHP suites and 180
mixed generated programs/state probes pass in `/tmp/my-try-mixed-selections-20261004-1/`.
A fresh export plus all 36 new selection programs/probes and 34 existing nullable/
Result coalescing programs pass without PCH (70 total) in
`/tmp/my-try-mixed-selections-20261004-no-pch/`. Both runs passed on their first
attempt using the shared 12-slot runner with CLI fallback after sandbox FPM denial.
This is PHP-hosted compiler and generated-program evidence, not a native compiler
rebuild or a compilation-performance measurement.

The next agreed slice supplies those wrapper-left/mixed-fallback rows.

## Wrapper-left mixed coalescing — 2026-10-04

Agreed scope: `nullable<T> ?? mixed`, `result<T> ?? mixed` and
`result_or_false<T> ?? mixed` yield owned mixed, with `T` limited to bool, ordinary
int, float and string. This follows the authoritative conditional matrix, not a new
general wrapper-to-mixed conversion right.

### Important consequences

- Successful false, zero, empty string and `"0"` payloads are retained and boxed.
- An empty nullable, failed Result or false sentinel selects the mixed fallback.
- A selected mixed fallback containing null is valid.
- A failed Result does not box or propagate its error as the result value; `??`
  selects the fallback. This does not suppress exceptions evaluating either operand.
- The source evaluates once; the fallback evaluates only when needed.
- The result is owned. Neither source is consumed or modified; scalar/string
  values copy independently, while selected boxed object/table handles preserve identity.

Implemented preparation first selects the left value domain and presence rule, then the result
domain. Retained facts separate exact payload extraction from result normalization,
reusing the existing conversion decisions. The emitter composes these two prepared
steps only inside the present branch. No new AST node, presence mode, runtime helper,
or combined wrapper-to-mixed operation is needed. This local preparation refactor
also serves existing direct/scalar coalescing rows. Legacy `normalize_coalesce_branch`
already unwraps before boxing; v0.2 makes those steps explicit before emission.

Non-goals: reverse `mixed ?? wrapper`, wrapper/mixed ternary or Elvis, bare-null
branches, fixed-width boxing, nested/container payloads and general wrapper-to-mixed
assignments, arguments or returns. Boolable remains rejected on either side during
preparation, including an unselected branch.

Proof owners: `tests/mixed.php` checks the two prepared steps, owned-result typing,
cleanup, deferred boundaries and incremental invalidation/retry/repair when the
producer changes to boolable. `tests/fixtures/mixed_wrapper_coalesce.php` supplies
all four scalar payloads for all three wrapper families, falsey successes, native
failure states, null/string/object fallbacks, unchanged inputs and failed assignment
targets, single source evaluation and lazy fallback/source exceptions. The shared
mixed runner and requested native source/probe sweep include these fixtures.

Verified on base `893858be` plus this slice: 14 PHP suites and all 210 mixed
generated programs/state probes pass in `/tmp/my-try-wrapper-mixed-coalesce-20261004-1/`.
All 30 new wrapper/mixed programs/probes plus 34 existing nullable/Result coalescing
programs pass without PCH (64 total) in
`/tmp/my-try-wrapper-mixed-coalesce-20261004-no-pch/`. Both passed on the first
attempt. Tests use the shared 12-slot pool; PHP fell back to CLI after sandbox FPM
denial. No native compiler rebuild, runtime semantic changes or native-compiler
portability claim is included.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| MIXED-WRAPPER-COALESCE-001 | agreed | `$v ?int = 0; $f mixed = null; $out = $v ?? $f;` | proved | proved | deferred | Nullable/result/falseable left, mixed fallback; separate extraction and normalization; `tests/mixed.py` |
| MIXED-JOIN-001 | agreed | `$a = $condition ? $m : 7; $b = $m ?: 7; $c = $m ?? 7;` | proved | proved | deferred | Direct mixed/mixed and mixed/scalar joins in either order; `tests/mixed.py`; wrapper/mixed and bare-null branches deferred |
| MIXED-OPS-001 | agreed | `$v mixed = "false"; $b = (bool)$v; if ($v) {} $e = empty($v);` | proved | proved | deferred | Explicit scalar casts, shared truthiness/negation and one-value probes; `tests/mixed.py`; broader runtime kinds tracked as MIXED-TRUTH-001 |
| MIXED-BOUNDARY-001 | agreed | `$v mixed = 7; $n int = $v;` | proved | proved | deferred | Scalar/null boxing, copying and checked required scalar boundaries; `tests/mixed.py` |
| RSL-JOIN-001 | agreed | `$a = $condition ? $r : 7; $b = $r ?: 7;` | proved | proved | deferred | Exact Result/scalar and same-wrapper joins; owned state-preserving lazy selection; `tests/results.py` |
| RSL-PROBES-001 | agreed | `if ($r) {} $p = isset($r); $e = empty($r);` | proved | proved | deferred | Scalar payloads across all three Result families; state/payload distinction and single evaluation; `tests/results.py` |
| RSL-TAKE-001 | agreed | `take($value, $error) = source();` | proved | proved | deferred | Existing exact scalar outputs; all four wrapper families; `tests/take.php` and `tests/results.py` |
| RSL-NO-ERROR-001 | agreed | `$e error = error::$no_error; if (!$e) {}` | proved | proved | deferred | Explicit presence, success clears error, invalid Result failures reject; full authoring: RSL-ERROR-01 |
| RSL-CONSTRUCT-001 | agreed | `$a result<int> = 7; $b result<int> = $a;` | proved | proved | deferred | Exact scalar construction/copy; `tests/results.py`; structured failure: RSL-ERROR-01 |
| RSL-SENTINEL-001 | agreed | `$a result_or_bool<bool> = false; $b result_or_bool<bool> = true_sentinel;` | proved | proved | deferred | Explicit sentinel states stay distinct from bool payloads; `tests/results.py` |
| RSL-EXTRACT-001 | agreed | `$r result<int> = 7; $v int = $r;` | proved | proved | deferred | Exact scalar required boundaries; checked non-value failures; `tests/results.py` |
| [LIT-NULL-001](#lit-null-001) | agreed | `$a = null;` | proved | proved | deferred | Untyped null rejects; typed nullable destination works |
| [VAR-ISSET-001](#var-isset-001) | agreed | `$a = isset($b);` | proved | proved | deferred | Single scalar/nullable/Result/null value; path probes deferred |
| [VAR-EMPTY-001](#var-empty-001) | agreed | `$a = empty($b);` | proved | proved | deferred | Single scalar/nullable/Result/null value; `"0"` is not empty |
| [EXPR-COALESCE-001](#expr-coalesce-001) | agreed | `$a = $b ?? $c;` | proved | proved | deferred | Scalar/nullable/result/falseable left, exact scalar fallback; boolable rejects in preparation |
| [EXPR-ELVIS-001](#expr-elvis-001) | agreed | `$a = $b ?: $c;` | proved | proved | deferred | Scalar, nullable/scalar, Result/scalar and direct mixed/scalar joins; wrapper/mixed deferred |
| [CAST-INT-001](#cast-int-001) | agreed | `$a = (int)$b;` | proved | proved | deferred | [Prepared scalar matrix](../../tests/conversions.php); [PHP/native bytes and Clang execution](../portability/conversion_review.md) |
| [CAST-FLOAT-001](#cast-float-001) | agreed | `$a = (float)$b;` | proved | proved | deferred | [Prepared scalar matrix](../../tests/conversions.php); [PHP/native bytes and Clang execution](../portability/conversion_review.md) |
| [CAST-BOOL-001](#cast-bool-001) | agreed | `$a = (bool)$b;` | proved | proved | deferred | [Prepared scalar matrix](../../tests/conversions.php); [PHP/native bytes and Clang execution](../portability/conversion_review.md) |
| [CAST-STRING-001](#cast-string-001) | agreed | `$a = (string)$b;` | proved | proved | deferred | [Prepared scalar matrix](../../tests/conversions.php); [PHP/native bytes and Clang execution](../portability/conversion_review.md) |
| [CAST-OBJECT-001](#cast-object-001) | deferred-dependency | `$a = (object)$b;` | deferred | deferred | deferred | CH05-DYN-01 / chapter 06; no unrestricted object cast |
| [FUNC-NULLABLE-001](#func-nullable-001) | agreed | `function f(?int $a): ?int { return $a; }` | proved | proved | deferred | Same scalar payload; nullable defaults deferred |
| [TYPE-VAR-002](#type-var-002) | agreed | `$x ?string = null;` | proved | proved | deferred | Scalar nullable value boundary slice |
| [NULL-CHECK-001](#null-check-001) | agreed | `$x === null` | proved | proved | deferred | Both orders; single evaluation and presence |
| [NULL-CHECK-002](#null-check-002) | agreed | `$x !== null` | proved | proved | deferred | Both orders; false/zero/empty-string remain present |
| [TYPE-VAR-006B](#type-var-006b) | agreed | `$p weird<int>;` rejects | proved | output withheld | deferred | `tests/constructed_type.php`; no mixed/auto fallback |
| [NOTE-005](#note-005) | split/debt | `value<value<int>>` normalizes | bounded proof | bounded proof | deferred | CH05-OWN-01; newer value-modifier contract supersedes blanket prose |
| [NOTE-010](#note-010) | reconciled/debt | Typed scalar/wrapper signatures | bounded proof | bounded proof | deferred | Object/reference/closure subrules assigned to chapters 07/10/12 |
| [NOTE-015](#note-015) | reconciled | `(string)$value` | proved subset | proved subset | deferred | Prepared cast matrix; object forms deferred |
| [NOTE-036](#note-036) | reconciled/debt | `$x ?int;` | proved subset | proved subset | deferred | Nullable/null rules; callable/default expansion deferred |
| [NOTE-037](#note-037) | reconciled/debt | `$x vector<int>;` | formation proved | representation proved | deferred | Source container operations belong to 06 |
| [NOTE-045](#note-045) | reconciled/debt | `$nullable ?? $mixed` | proved subset | proved subset | deferred | CH05-VAL-01 for remaining joins; boolable coalesce rejects early |
| [NOTE-052](#note-052) | split/debt | `"value=$v"` | bounded proof | bounded proof | deferred | Scalar interpolation covered; ownership CH05-OWN-01; path interpolation deferred |
| [NOTE-063](#note-063) | reconciled/debt | `take($v, $e) = source();` | proved subset | proved subset | deferred | Existing outputs; CH05-TAKE-01, CH05-PROFILE-01 and RSL-ERROR-01 |
## LIT-NULL-001

**v0.2 decision / target C++:** Non-storable null sentinel; an explicit scalar nullable
destination constructs an empty value. Untyped direct null assignment rejects.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:34](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = null;
```

**Existing C++ lowering / result**

`ERROR` for untyped direct null assignment

**Category:** Literal

**Rule kind:** rejection-with-exception

**Source support status:** supported-with-precondition

**Preconditions:** null must appear in a context whose emitted type supports null

**Normalized pattern:** `<var> = null`

**General rule:** Untyped direct `null` assignment remains rejected. Typed nullable value contexts and object-handle contexts may emit runtime `null` according to the explicit typing rules.

**Diagnostics:** Emit an error when `null` is used without an explicit nullable/value-supporting or object-handle typing context.

**Notes:** Null support is runtime-defined; this row covers the untyped direct-assignment case only.


## VAR-ISSET-001

**v0.2 decision / target C++:** One-value probe node; prepared scalar/nullable/null
admission and runtime `php::isset` semantics. Missing-name/path behavior is not added.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:51](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = isset($b);
```

**Existing C++ lowering / result**

```cpp
auto a = php::isset(b);
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** valid expression in scope

**Normalized pattern:** `<var> = isset(<expr>)`

**General rule:** `isset(expr)` must be mapped to the PHP runtime via `php::isset(...)`, and assigned using `auto` on first assignment in scope.

**Diagnostics:** Behavior delegated to runtime.


## VAR-EMPTY-001

**v0.2 decision / target C++:** One-value probe node; prepared scalar/nullable/null
admission and runtime `php::empty` semantics. `empty("0")` is false, unlike PHP.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:52](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = empty($b);
```

**Existing C++ lowering / result**

```cpp
auto a = php::empty(b);
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** valid expression

**Normalized pattern:** `<var> = empty(<expr>)`

**General rule:** `empty(expr)` must be mapped to the PHP runtime via `php::empty(...)`, and assigned using `auto` on first assignment in scope.

**Diagnostics:** Behavior delegated to runtime.


## EXPR-COALESCE-001

**v0.2 decision / target C++:** Dedicated lazy expression with prepared exact scalar
result/conversions: `nullable<T> ?? T`, `result<T> ?? T`, `result_or_false<T> ?? T`
and `T ?? T` yield owned `T`. Boolable operands reject in preparation on either side.
Direct mixed/mixed and mixed/scalar pairs in either order yield owned mixed, with
prepared null-kind presence testing rather than truthiness. Approved nullable/result/
falseable left with mixed fallback unwraps the selected scalar and boxes it.
Wrapper-valued fallback and reverse mixed/wrapper combinations remain deferred.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:89](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b ?? $c;
```

**Existing C++ lowering / result**

```cpp
auto a = php::coalesce_eval([&]() -> decltype(auto) { return b; }, [&]() -> decltype(auto) { return c; });
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** left/right pair must be covered by the runtime coalesce matrix

**Normalized pattern:** `<var> = <expr> ?? <expr>`

**General rule:** Null coalescing lowers through `php::coalesce_eval(...)` so wrapper unboxing and fallback normalization are resolved centrally in the runtime. The current matrix explicitly supports `mixed_t ?? T`, `T ?? mixed_t`, and `nullable<T> ?? mixed_t`, all normalized to a `mixed_t` result.

**Diagnostics:** Error if the runtime coalesce matrix does not define the operand pair.

**Notes:** Uses `isset`-style guard semantics inside the runtime helper.


## EXPR-ELVIS-001

**v0.2 decision / target C++:** The matching scalar/nullable-scalar subset shares
`conditional_expression_node` with full ternary; its true branch is absent.
A typed lambda saves the original left value once, tests shared truthiness, and
converts only the selected branch to the joined result type. Results are owned values.
See the [Chapter 03 slice](03_control_flow.md#agreed-conditional-expression-slice--2026-10-02)
for parser bounds. Matching Result/scalar joins are now included by the Result join
slice above. Direct mixed/mixed and mixed/scalar joins are included by MIXED-JOIN-001.
Wrapper/mixed joins remain deferred; this does not close
the wider dynamic-boundary work. Imported lowering below is historical evidence.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:90](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b ?: $c;
```

**Existing C++ lowering / result**

```cpp
auto a = ([&]() -> auto { auto __scpp_cond_value = b; return php::ternary_eval([&]() -> decltype(auto) { return __scpp_cond_value; }, [&]() -> decltype(auto) { return __scpp_cond_value; }, [&]() -> decltype(auto) { return c; }); }());
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition/branch combination must be covered by the runtime ternary matrix

**Normalized pattern:** `<var> = <expr> ?: <expr>`

**General rule:** The elvis operator lowers through a temporary plus `php::ternary_eval(...)` so the left operand is evaluated once and branch normalization is centralized in the runtime.

**Diagnostics:** Error if the runtime ternary matrix does not define the resulting branch pair.

**Notes:** This removes the old duplicate-evaluation restriction for the current supported helper matrix.


## CAST-INT-001

**v0.2 decision / target C++:** `(int)` is one use of the general explicit-cast
expression, not an integer-specific AST node. Preparation resolves `int` through
the canonical type registry and asks the integer conversion policy for an explicit
decision after preparing the operand. Exact `int` identity emits only the operand;
other supported scalar sources emit:

```cpp
scpp::cast<scpp::int_t<>>(operand)
```

The runtime owns parsing, truncation, range and failure behavior. Unsupported
source families are rejected during semantic preparation. Fixed-width registered
integer targets use this same path.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:97](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (int)$b;
```

**Existing C++ lowering / result**

```cpp
auto a = cast<int_t>(b);
```

**Category:** Cast

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** expression must be convertible to `int_t`

**Normalized pattern:** `<var> = (int)<expr>`

**General rule:** PHP `(int)` cast must be mapped to `cast<int_t>(...)`. C-style casts are not allowed.

**Diagnostics:** Error if generator emits C-style cast or conversion is not allowed.


## CAST-FLOAT-001

**v0.2 decision / target C++:** `(float)` uses the common explicit-cast expression,
canonical registry lookup and the floating-point target-family policy. Exact
floating identity emits only the operand; another supported scalar source emits:

```cpp
scpp::cast<scpp::float_t>(operand)
```

This intentionally resolves the imported row's stale `static_cast` example in
favor of its own stated runtime-cast rule. The runtime owns conversion behavior;
unsupported source families are rejected during preparation.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:98](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (float)$b;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<float_t>(b);
```

**Category:** Cast

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** expression must be convertible to `float_t`

**Normalized pattern:** `<var> = (float)<expr>`

**General rule:** PHP `(float)` cast must be mapped to `cast<float_t>(...)`.

**Diagnostics:** Error if cast is dropped or conversion is not allowed.


## CAST-BOOL-001

**v0.2 decision / target C++:** `(bool)` uses the common explicit-cast expression,
canonical registry lookup and the boolean target-family policy. Exact boolean
identity emits only the operand; another supported scalar source emits:

```cpp
scpp::cast<scpp::bool_t>(operand)
```

This intentionally resolves the imported row's stale `static_cast` example in
favor of its own stated runtime-cast rule. Truth conversion behavior belongs to
the runtime; unsupported source families are rejected during preparation.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:99](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (bool)$b;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<bool_t>(b);
```

**Category:** Cast

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** expression must be convertible to `bool_t`

**Normalized pattern:** `<var> = (bool)<expr>`

**General rule:** PHP `(bool)` cast must be mapped to `cast<bool_t>(...)`.

**Diagnostics:** Error if syntax is invalid or conversion is not valid.


## CAST-STRING-001

**v0.2 decision / target C++:** `(string)` uses the common explicit-cast expression,
canonical registry lookup and the string target-family policy. Exact string
identity emits only the operand; another supported scalar source emits:

```cpp
scpp::cast<scpp::string_t>(operand)
```

Formatting and failure behavior belong to the runtime. No implicit constructor
fallback is permitted, and unsupported source families are rejected during
semantic preparation.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:100](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (string)$b;
```

**Existing C++ lowering / result**

```cpp
auto a = cast<string_t>(b);
```

**Category:** Cast

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** expression must use a runtime-supported explicit string cast pair

**Normalized pattern:** `<var> = (string)<expr>`

**General rule:** PHP `(string)` cast must be mapped to explicit `cast<string_t>(expr)`. The runtime owns the supported conversion pairs. Unsupported pairs must fail rather than falling back to implicit construction.

**Diagnostics:** Error if implicit `string_t(...)` construction is used for non-string inputs or conversion is unsupported.

**Notes:** Type-system exception rule.


## CAST-OBJECT-001

**v0.2 decision / target C++:** Deferred with CH05-DYN-01 to chapter 06. Supported
object construction forms need container/runtime-construction preparation first;
this is not permission for unrestricted object casting.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:102](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (object)$b;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Cast

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `<var> = (object)<expr>`

**General rule:** Only `(object)[...]` is supported and lowers to runtime `dynamic_t` storage inside `mixed_t`. Other object casts remain unsupported.

**Diagnostics:** Emit `mixed_t{dynamic_(...)}` only for `(object)[...]`; reject all other `(object)` casts.

**Notes:** Source-language `stdClass` lowering is supported only through `new stdClass()` and `(object)[...]`.


## FUNC-NULLABLE-001

**v0.2 decision / target C++:** Explicit nullable scalar parameters/returns use the
shared exact-payload conversion path. Required extraction is checked; defaults deferred.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:144](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(?int $a): ?int { return $a; }
```

**Existing C++ lowering / result**

```cpp
nullable<int_t> f(nullable<int_t> a) { return a; }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** inner type supported

**Normalized pattern:** `function <name>(?<type> <param>): ?<type> <block>`

**General rule:** PHP nullable types map to `nullable<T>` in Prism++. Parameter and return nullable types must be transformed consistently.

**Diagnostics:** Error if inner type unsupported or nullable mapping inconsistent between signature and body.

**Notes:** Variable names still follow `$` removal.


## TYPE-VAR-002

**Strict-mode PHP input example:** `$x ?string = null;`

This spelling is proved for scalar payloads and is canonically equivalent to
`nullable<string>`. Imported examples below remain reference material.

**v0.2 decision / target C++:** Explicit nullable scalar storage with destination-typed
empty construction, exact-payload wrapping and value copying.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:323](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** ?string */ = null;
```

**Existing C++ lowering / result**

```cpp
nullable<string_t> x = null;
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit nullable value-like PHPDoc variable type present

**Normalized pattern:** `typed nullable value local variable`

**General rule:** Nullable value-like local variables lower to `nullable<T>` and may be initialized with runtime `null`.

**Diagnostics:** Error if a nullable wrapper is omitted for a nullable value-like type.

**Notes:** Canonical null form is runtime `null`.


## NULL-CHECK-001

**v0.2 decision / target C++:** Prepared nullable presence test, negated for `=== null`;
either operand order evaluates the nullable expression once.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:344](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x === null
```

**Existing C++ lowering / result**

```cpp
php::is_null(x)
```

**Category:** Null

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** comparison is strict-null check

**Normalized pattern:** `<expr> === null`

**General rule:** Strict null checks must lower to the configured runtime helper rather than direct C++ comparison syntax.

**Diagnostics:** Error if the generator emits `== null` or `== nullptr` for this normalized form.

**Notes:** Applies to object handles and nullable value-like wrappers.


## NULL-CHECK-002

**v0.2 decision / target C++:** Prepared nullable presence test for `!== null`;
present false/zero/empty-string payloads are not null.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:345](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x !== null
```

**Existing C++ lowering / result**

```cpp
php::not_null(x)
```

**Category:** Null

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** comparison is strict-not-null check

**Normalized pattern:** `<expr> !== null`

**General rule:** Strict non-null checks must lower to the configured runtime helper rather than direct C++ comparison syntax.

**Diagnostics:** Error if the generator emits `!= null` or `!= nullptr` for this normalized form.

**Notes:** Mirrors the strict-null check rule.


## TYPE-VAR-006B

**Strict-first scope:** `$p weird<int>;` and equivalent unknown explicit parameter,
return and field types reject during preparation. Legacy annotations below are reference only.

**v0.2 decision / target C++:** Explicit intent either resolves through the canonical
type model or rejects without output; it never degrades to mixed/auto. Proved by
`tests/constructed_type.php`. Ownership nesting remains CH05-OWN-01, not covered by
this unknown-type rejection.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:357](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$p /** weird<Box> */ = null;
```

**Existing C++ lowering / result**

generator diagnostic

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit local type intent is present but unsupported or malformed

**Normalized pattern:** `explicit typed local rejection`

**General rule:** Explicit local type intent must either lower exactly according to the supported type grammar or fail generation. The generator must not silently fall back to `mixed_t` when the user wrote an explicit wrapper or annotated type form it does not understand.

**Diagnostics:** Error if unsupported explicit type syntax is accepted by degrading to `mixed_t` or `auto`.

**Notes:** The only remaining permissive null fallback is an untyped assignment such as `$x = null;`.


## NOTE-005

**Source:** [generators/php/specs/rules_catalog.md:369](../../../../generators/php/specs/rules_catalog.md)

**v0.2 decision / examples:** Split under CH05-OWN-01. Redundant `value<value<int>>`
normalizes per `specs/type_use_modifiers.md`; repeated effective modifiers reject.
Ownership nesting needs semantic classification in chapter 07. Imported blanket
wording below does not override the newer v0.2 modifier contract.

> ## Wrapper nesting constraints
>
> - Ownership/value wrappers may not be nested. The following are invalid and must fail generation: `value<value<T>>`, `value<?value<T>>`, `shared<shared<T>>`, `unique<unique<T>>`, and any mixed wrapper-inside-wrapper form such as `value<shared<T>>` or `value<?shared<T>>`.

## NOTE-010

**Source:** [generators/php/specs/rules.md:58](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Reconciled by owning area. Typed functions and scalar/
nullable/Result/mixed boundaries are covered; container operations, references,
objects and closures belong to chapters 06/07/10/12. Legacy annotation placement
and ownership shortcuts are not part of this strict-first closeout. See the closeout
table and named debt above; imported C++ spellings are not v0.2 lowering requirements.

> ## 2. Type System
>
> ### Mandatory
> - function and method parameters must be typed explicitly
> - parameter typing must come from exactly one source: native PHP type or supported doc-comment type
> - native PHP type plus supported doc-comment type on the same parameter/property is an error
> - class properties must be typed explicitly
> - return types must be explicit
>
> ### Mapping
> - `int` â†’ `int_t`
> - `float` â†’ `float_t`
> - `bool` â†’ `bool_t`
> - `string` â†’ `string_t`
> - `?T` â†’ `nullable<T>` for value-like types
> - class / interface / abstract object types â†’ `shared_p<T>`
> - `?ClassType` / `?InterfaceType` / `?AbstractType` â†’ `shared_p<T>`
> - object nullability does not currently change the emitted C++ type; `A` and `?A` both emit `shared_p<A>` for now
>
> ### Returns
> - non-void functions must return a value on all paths
> - void functions cannot return a value
>
> ### Closures and callable locals
> - Closure expressions are concrete callable values and lower to native C++ lambdas.
> - Explicit strict callable locals such as `$f function<int()> = function () use ($a) { return $a; };` lower to `std::function<int_t()>` storage and provide the expected closure signature when the initializer omits a return type.
> - A direct local assignment from a closure with a complete syntactic signature may synthesize the callable storage type, for example `$f = function (int $x): int { return $x; };` lowers to `std::function<int_t(int_t)>`.
> - Closure `use ($a)` captures the current value at closure creation time; `use (&$a)` captures by reference.
> - Closure return annotations owned by the scanner, including forms such as `: vector<int>`, are valid closure return types and must not depend on php-ast doc-comment ownership.
> - When a closure return type maps to `vector_t<T>`, a returned positional array literal lowers as a typed `vector_t<T>{...}` literal.
> - Untyped closure assignment with a value return is rejected unless the closure has an explicit return type or the target provides an expected callable signature.
> - Closures cannot be stored in dynamic/untyped containers or array slots; assign them to concrete callable locals instead.
> - A mismatch between an explicit callable local signature and an explicit closure signature is a STAN-owned diagnostic before build; the S2S generator should not perform broad semantic callable compatibility inference.
>
>
> ### Variable Typing
> - explicit scanner-owned inline slot type annotations are authoritative when present
> - accepted inline comment forms are limited to recognized typed slots, not generic PHPDoc tags
> - local variables keep the strict immediate-after-variable form only
> - valid local form example: `$x /** string */ = "test";`
> - parameters and properties additionally support the leading attached form such as `function f(/** vector<int> */ $list): void {}` and `public /** int */ $x;`
> - properties also support the immediate trailing form such as `public $items /** vector<int> */ = [];`
> - function-like returns support the immediate post-signature form such as `function build() /** vector<int> */ { ... }` and `fn(/** int */ $x) /** function<int(int)> */ => ...`
> - detached forms such as `/** @var vector<int> */ $items = [];` are not accepted as typed-slot metadata
> - class constants support the leading attached form such as `const /** int */ X = 1;`
> - constant declarations fall back to initializer-based type deduction in emitted C++ (`const auto ... = ...`)
> - detached or non-adjacent type comments remain invalid
> - `$x /** string */ = "test";` â†’ `string_t x("test");`
> - `$x /** ?string */ = "test";` â†’ `nullable<string_t> x("test");`
> - `$x /** ?string */ = null;` â†’ `nullable<string_t> x = null;`
> - `$x /** A */ = new A();` â†’ `shared_p<A> x = create<A>();`
> - `$x /** ?A */ = null;` â†’ `shared_p<A> x = null;`
> - `$x /** value<Point> */ = new Point(1, 2);` â†’ `value_p<Point> x = value<Point>(static_cast<int_t>(1), static_cast<int_t>(2));`
> - `$x /** weak<A> */ = null;` â†’ `weak_p<A> x = null;`
> - `$x /** weakref<A> */ = null;` â†’ `weak_p<A> x = null;`
> - `$x /** unique<A> */ = null;` â†’ `unique_p<A> x = null;`
> - `$x /** shared<A> */ = null;` â†’ `shared_p<A> x = null;`
> - `$x /** ref int */ = &$y;` â†’ `int_t& x = y;`
> - `/** ref Point */` locals lower directly to `shared_p<Point>&` when `Point` lowers to an object handle
> - `ref` lowering is intentionally a reduced write-through alias feature built on native C++ references; rebinding-through-alias and PHP-style alias-preserving `unset` are out of scope
>
> - explicit inline object/value storage is opt-in only through the PHPDoc forms `value<T>` and `value<?T>`
> - object-handle local wrappers are expressed canonically as `shared<T>` and `unique<T>`
> - `value<T>`, `value<?T>`, `shared<T>`, and `unique<T>` are currently supported for typed local variables only
> - legacy `value T` is still accepted temporarily for compatibility, but `value<T>` and `value<?T>` are the canonical forms going forward
> - strict local wrapper shortcuts are supported only for direct constructor assignment: `/** value */`, `/** shared */`, and `/** unique */` must appear on a typed local whose initializer is exactly `new ClassName(...)`; the generator must immediately normalize them to `value<ClassName>`, `shared<ClassName>`, or `unique<ClassName>`. After normalization, explicit wrapper forms such as `value<T>`, `shared<T>`, and `unique<T>` initialized from `new U(...)` must validate that `T` and `U` match exactly.
> - bare local wrapper shortcuts must be rejected when the initializer is not a direct `new ClassName(...)` expression, when the class target is not statically known, or when the assignment shape is not a normal direct local assignment
> - when a `value<T>` local is initialized from `new T(...)`, generation must use `value<T>(...)` instead of `create<T>(...)`
> - when a `value<?T>` local is initialized from `new T(...)`, generation must lower to `nullable<T>{T(...)}`; this is the canonical nullable inline-object lowering and must not silently degrade to `mixed_t`
> - when a `unique<T>` local is initialized from `new T(...)`, generation must use `::scpp::unique<T>(...)` instead of `create<T>(...)`
> - explicit wrapper locals initialized from `new ...` must reject constructor-target mismatches; for example, `/** value<A> */ = new B()` is a generator error and must not silently default-initialize `A`
> - `value<T>` locals remain object-like at the usage surface: property and method access must continue to lower through `->`, for example `$x /** value<MyClass> */ = new MyClass(); $x->property_1 = 10;` lowers conceptually to `value_p<MyClass> x = value<MyClass>(); x->property_1 = static_cast<int_t>(10);`
> - `value<?T>` locals remain object-like at the usage surface through nullable dereference: for example `$x /** value<?MyClass> */ = null; $y /** value<?MyClass> */ = new MyClass();` lowers conceptually to `nullable<MyClass> x = null; nullable<MyClass> y{MyClass()};`, and `$x->prop` must fail at runtime while `$y->prop` must behave like `MyClass`
> - `shared_p<MyClass> x = null` is not a valid nullable-wrapper test because it exercises handle-null semantics, not `nullable<T>` semantics
> - explicit type intent must never silently fall back to `mixed_t`; unsupported or malformed explicit type syntax such as an unknown wrapper form must fail generation with a diagnostic
> - explicit reference lowering over handle-like wrappers must emit a native handle reference (`shared_p<T>&`, `unique_p<T>&`, `weak_p<T>&`) instead of creating nested pointer/reference layers
>
> ### Untyped Variable Initialization
> - untyped variables may still lower to explicit runtime-wrapped expressions
> - `$x = "test";` â†’ `auto x = string_t("test");`
> - constructor selection, conversion resolution, and overload resolution remain the C++ compiler's responsibility
>
> ### Passing and Return Conventions
> - `int_t`, `float_t`, and `bool_t` use normal value semantics for parameters and returns unless explicit `&` is present
> - `string_t` and `vector_t` default to `const &` for parameters and return by value
> - explicit PHP `&` disables the default `const &` convention and must be emitted as a mutable reference
> - class/interface/abstract object types are emitted as `shared_p<T>` handles and are passed and returned by handle value
> - object nullability intent (`T` vs `?T`) does not currently change the emitted object-handle type
> - class/interface object types remain pointer-like in use (`->`)
> - user PHP classes must not be stored by value in generated code
> - runtime nullability enforcement for non-nullable object parameters/properties is deferred; current code generation keeps `T` and `?T` identical for object-handle types and relies on future injected checks
> - raw `&`, `&&`, and `*` must not appear inside source type definitions or PHPDoc type comments; explicit references are represented only by PHP reference syntax and typed local `ref T` annotations
>
> ---

## NOTE-015

**Source:** [generators/php/specs/rules.md:225](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Prepared scalar cast matrix is proved by conversions
and generated programs. Use semantic conversion decisions, not the imported
constructor fallback. Object construction remains CH05-DYN-01; explicit casts and
text-context conversion remain distinct.

> ## 7. Casting
>
> - scalar casts use `static_cast<T>(...)`
> - string conversion uses explicit `cast<string_t>(...)` only for supported pairs
> - in all other cases string conversion uses `string_t(...)`
> - C-style casts are allowed only for non-literals when required as a temporary form
>
> ---

## NOTE-036

**Source:** [generators/php/specs/rules.md:708](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Typed predeclarations, nullable mapping, typed null,
nullable null returns and untyped-null rejection are covered. Nullable defaults
remain CH05-VAL-01; callable/closure expansion belongs to chapter 12. Imported
comment-annotation placement remains outside the strict-first proof.

> ## 7. Null and nullable rules
>
> ### 7.1 Typed local predeclaration
>
> Safe v1 supports explicit local declarations without initialization when the local has an explicit type annotation.
>
> Example:
>
> ```php
> $f /** function<int(int)> */;
> ```
>
> This form exists so an outer block can predeclare a local that will later be assigned inside child blocks while still respecting block-local visibility. Bare `callable` is not sufficient; use a concrete `function<return_type(arg_types)>` annotation.
>
> The same concrete `function<return_type(arg_types)>` annotation form is also accepted on closure parameters when PHP syntax cannot express a native callable signature directly, for example `function (/** function<int(int)> */ $fn, int $x): int { ... }`.
>
> Safe v1 also accepts scanner-owned shorthand type sites that are normalized before `php-ast` parsing, for example:
>
> ```php
> $count int = 0;
> public $items vector<string> = [];
> function build($items vector<string>): vector<string> { ... }
> $make = fn($x int) function<function<int(int)>(int)> =>
> 	fn($y int): int => $x + $y;
> ```
>
> The pre-tokenizer normalizes those surfaces into parseable PHP source while separately preserving explicit site metadata for locals, properties, params, and function-like return slots. Because return-site ownership is scanner-owned, nested closure or arrow return annotations no longer rely on accidental raw `php-ast` doc-comment attachment. Arrow functions (`fn (...) => expr`) are also supported in Safe v1. php-ast exposes them as `AST_ARROW_FUNC` without an explicit `use (...)` list, so the generator infers implicit by-value captures from referenced outer locals and lowers them to native C++ lambdas with value captures.
>
> ### 7.2 Untyped null assignment
> Direct untyped `null` assignment is not allowed:
> ```php
> $a = null;
> ```
> -> error
>
> ### 7.2 Nullable mapping
> - `?T` -> `nullable<T>`
>
> Examples:
> - `?int` -> `nullable<int_t>`
> - `?string` -> `nullable<string_t>`
>
> ### 7.3 Typed null
> Allowed:
> ```cpp
> nullable<int_t> a = null;
> ```
>
> ### 7.4 Nullable return of null
> Allowed.

## NOTE-037

**Source:** [generators/php/specs/rules.md:759](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Scalar identities and canonical runtime-template
formation/rendering are covered. Vector/hash source operations belong to chapter 06;
type formation does not certify indexing, mutation or iteration.

> ## 8. Type mapping
>
> - `int` -> `int_t`
> - `float` -> `float_t`
> - `bool` -> `bool_t`
> - `string` -> `string_t`
> - `vector` -> `vector_t`
> - `void` -> `void`
>
> Not implemented yet:
> - PHP `array`

## NOTE-045

**Source:** [generators/php/specs/rules.md:997](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Bounded conditional/coalesce matrices are implemented
through dedicated nodes, prepared decisions and lazy evaluation. Boolable coalesce
rejects in preparation, even when unselected. Remaining joins/payloads are CH05-VAL-01;
imported helper spellings do not override the preparation/emission boundary.

> ## 16. Null coalescing and ternary-family lowering
>
> Accepted lowering for null coalescing:
> ```php
> $b ?? 1
> ```
>
> becomes:
> ```cpp
> php::coalesce_eval(
> 	[&]() -> decltype(auto) { return b; },
> 	[&]() -> decltype(auto) { return static_cast<int_t>(1); }
> )
> ```
>
> Accepted lowering for ternary:
> ```php
> $a ? $b : 0
> ```
>
> becomes:
> ```cpp
> php::ternary_eval(
> 	[&]() -> decltype(auto) { return a; },
> 	[&]() -> decltype(auto) { return b; },
> 	[&]() -> decltype(auto) { return static_cast<int_t>(0); }
> )
> ```
>
> Accepted lowering for elvis:
> ```php
> $a ?: 0
> ```
>
> becomes:
> ```cpp
> ([&]() -> auto {
> 	auto __scpp_cond_value = a;
> 	return php::ternary_eval(
> 		[&]() -> decltype(auto) { return __scpp_cond_value; },
> 		[&]() -> decltype(auto) { return __scpp_cond_value; },
> 		[&]() -> decltype(auto) { return static_cast<int_t>(0); }
> 	);
> }())
> ```
>
> Rules:
> - current lowering emits helper calls here rather than solving branch/result typing inline
> - `??` and `?:` use different runtime result matrices, but they do share one wrapper-normalization rule for PHP-visible null / bool / dynamic semantics
> - the current `??` matrix includes explicit dynamic-carrier entries for `mixed_t ?? T`, `T ?? mixed_t`, and `nullable<T> ?? mixed_t`; these normalize to `mixed_t` rather than attempting a typed payload result
> - `??` auto-unpacks only the approved wrapper families (`nullable<T>`, `result<T>`, and `result_or_false<T>`) to their usable value domain; in the current version `result_or_bool<T>` is rejected by the runtime helper on either side of coalesce because current lowering does not resolve that row statically here, while `?:` follows its own truthiness-based wrapper policy.
> - helper lambdas preserve lazy right/branch evaluation
> - elvis lowering must evaluate the left operand exactly once
> - unsupported operand/branch combinations must fail deterministically at compile time in the runtime helper layer
> - fallback literals still follow normal literal conversion rules
>
> See also: `specs/conditional_expression_matrix.md`.

## NOTE-052

**Source:** [generators/php/specs/rules.md:1196](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Split: ownership nesting is CH05-OWN-01, subject to
the newer modifier contract. Simple scalar variable interpolation is implemented in
chapter 02; indexed/property/call interpolation needs the corresponding access owners.

> ## Wrapper nesting constraints
>
> - Ownership/value wrappers may not be nested. The following are invalid and must fail generation: `value<value<T>>`, `shared<shared<T>>`, `unique<unique<T>>`, and any mixed wrapper-inside-wrapper form such as `value<shared<T>>`.
>
>
> ### String interpolation limitations
>
> Inside interpolated strings ("..."), only simple expressions are allowed.
>
> Supported:
> - `$var`
> - `$obj->prop`
> - `$arr[index]`
> - `$obj->method()`
>
> Not supported:
> - arithmetic expressions (`{$a + $b}`)
> - ternary expressions (`{$a ? $b : $c}`)
> - null coalescing expressions (`{$a ?? $b}`)
>
> This matches PHP behavior.

## NOTE-063

**Source:** [generators/php/specs/rules.md:1317](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Explicit Result family identities, scalar payload/sentinel
distinctions and existing-output take are covered. Main v0.2 syntax is
`take($value, $error) = source();`; helper spelling shares its node. Output declaration
and profile availability remain CH05-TAKE-01/CH05-PROFILE-01. General error members/
construction remain RSL-ERROR-01 in chapter 13; imported member examples are not
current my-try support claims.

> ## Explicit result_or_false<T> and result<T> type intent
>
> - `result_or_false<T>`, `result_or_bool<T>`, and `result<T>` are explicit type-intent wrappers only; the generator must not infer them from PHP unions automatically in this pass.
> - For `result_or_false<bool_t>`, plain `false` remains a wrapped payload value; the explicit false-sentinel forms are `false_sentinel`, `null`, and `nullopt`. The generator must not rewrite a typed `false` payload into the sentinel for this specialization.
> - explicit type intent must never silently degrade to `mixed_t`; unsupported or malformed `result_or_false<T>` / `result_or_bool<T>` / `result<T>` syntax is a generator error.
> - wrapper lowering uses the canonical mapped inner runtime type, so `result_or_false<MyBox>` lowers to `result_or_false<shared_p<MyBox>>`, `result_or_bool<MyBox>` lowers to `result_or_bool<shared_p<MyBox>>`, while `result<int>` lowers to `result<int_t>`.
> - `$result->error()->...` is the supported error-access surface for `result<T>` and must lower to the wrapper method rather than a payload property named `error`.
> - `take(...)` is a reserved runtime helper name in the PHP-facing source subset. It lowers to `php::take(...)` when no user-defined function named `take` is resolved.
> - `take($value, $source)` is valid only for source expressions typed as `nullable<T>` or `result_or_false<T>`.
> - `take($value, $error, $source)` is valid only for source expressions typed as `result<T>`.
> - `take($value, $bool, $source)` is valid only for source expressions typed as `result_or_bool<T>`.
> - `take(...)` output arguments must be simple local variables in v1. Wrong arity, wrong output type, or a non-wrapper source is a compile-time generator error when the source or output type is known.
> - `take(...)` evaluates its source expression exactly once and returns `bool_t`; for `result_or_bool<T>`, the helper returns `true` for both wrapped-value and bool-true states so mysqli-style APIs remain representable.
> - `take(...)` is the preferred explicit payload-extraction form for `result*<T>` wrappers because the generator does not perform symbol-resolution-driven wrapper inference.
