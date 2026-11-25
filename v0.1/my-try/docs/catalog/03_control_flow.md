# 03. Conditions and control flow
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires expressions. The agreed bounded first pass is complete; see the conclusion
and handoff below before reopening a deferred extension.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Positive-pass conclusion — 2026-10-02

Chapter 03 is concluded for the agreed frontend + C++ S2S first pass: shared statement
bodies and block scope, if chains, while/do-while/for, bare break/continue, canonical-int
switch, shared scalar truthiness, matching-scalar ternary/Elvis, and integer-label match
with optional default. [NOTE-017](#note-017) reconciles the imported overview and routes
foreach to Chapter 06. This is bounded functionality, not full language coverage.

The latest focused evidence remains 12 PHP suites and 132 generated-C++ programs
passing in `/tmp/my-try-match-20261002-second`; see the match slice for coverage.
Documentation closeout adds no execution evidence or native compiler checkpoint.

Subsequent user-requested native validation now covers this first pass: 479 total
PHP/native source comparisons and executions (including 130 control-flow cases),
76 supplementary probes, 135 rejection/recovery cases, portable incremental control-flow
proof and final incremental build passed. See the [native checkpoint](../portability/conversion_review.md#latest-native-checkpoint--2026-10-02-control-flow-closeout)
for exact source/harness identity and portability adaptations; LLVM/STAN remain skipped.

### Remaining debt and scope boundaries


**Current scope — 2026-10-19 (CS101).** Debt register is not a permanent incompatibility list. Brace-free strict compatibility is missing S06; numbered transfers, alternative grammar and constant-reachability/CFG extensions remain explicit bounds. Foreach, enum selection, record/shared joins and exceptions have later owners; old blanket deferrals are stale. LLVM/native compiler/performance remain outside this verification goal.

Contracts and owners: [conditional_expression_matrix.md](../../../../specs/conditional_expression_matrix.md), [expression_evaluation.md](../../../../specs/expression_evaluation.md), [array_semantics.md](../../../../specs/array_semantics.md), [v0_2_preparation_diagnostics.md](../../../../specs/v0_2_preparation_diagnostics.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS101`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

Explicitly flagged as **deferred debt** by user agreement. These entries include
intentional first-pass limits, not only defects. They are not implementation approval
or a requirement to support every PHP spelling; confirm the owning language contract
when reopening one. None blocks Chapter 04.

| Debt ID | Deferred item | Owner / reopening condition |
| --- | --- | --- |
| CF-01 | Unbraced loop bodies, empty semicolon bodies and alternative statement syntax; numbered break/continue | Chapter 03 parser and transfer preparation; agree the additional syntax first |
| CF-02 | Ternary grammar limits, including required parentheses around fallback assignments | Chapter 03 expression parser; preserve the documented precedence and nesting contract |
| CF-03 | Selector types beyond canonical int/enums, runtime labels and general constant folding | Enum-case selection added in [CH09](09_value_types.md#enum-equality-and-selection--2026-10-06); agree further domains separately |
| CF-04 | Broader ternary/Elvis/match result joins beyond matching scalars | Shared result preparation; coordinate wrapper/mixed work with Chapter 05 and object types with later chapters |
| CF-05 | Empty match and completion rules for exceptions/non-returning calls | Expression typing and completion preparation; coordinate with Chapter 13 |
| CF-06 | Constant reachability, definite assignment/branch merging and general effect analysis | Shared semantic preparation; conservative current checks remain in force; no CFG work is authorized |
| CF-07 | Executable foreach, including reference-iteration lifetime rules | Chapter 06 / Chapter 07; keep the reserved loop specialization and TODO |
| CF-08 | Native compilation-cost benchmarking | Backend/performance work when measurements justify it; no speedup claim for current lowering |
| CF-09 | LLVM adaptation to shared control-flow facts | Parked backend; only reopen on explicit request |

Inherited debt stays with its existing owner rather than becoming a second Chapter 03
implementation queue: PE-03 duplicate declarations, conditional runtime-template
copyability, bracket overload/lowering, and output partitioning. The portability
blockers found at closeout are **resolved**, with the native checkpoint linked above.
Future native validation remains an explicitly requested checkpoint, not an automatic
requirement for every feature slice.

</details>

### Handoff to Chapter 04

Continue with [functions, parameters and calls](04_functions.md#handoff-from-chapter-03--2026-10-02).
Function signatures/calls already exist. First reconcile working rows against focused
proofs, particularly string parameters, expression returns, multiple arguments and
terminating recursion. Do not rebuild the existing function/body model or treat stale
catalog cells as evidence of missing code. Discuss omitted type annotations, defaults,
variadics and overload selection separately before extending semantics.

## Handoff from chapter 02 — 2026-10-02

Historical starting context: the original prerequisites below were resolved by the
agreed slices that follow. They do not describe the current block or condition model.

Chapter 02 is [concluded for its positive pass](02_expressions.md#positive-pass-conclusion--2026-10-02).
Continue with working bounded functionality, sound model/ownership and focused proof;
keep peripheral hardening as explicit debt. Chapter 03 semantics and new structures
still require discussion. The imported rows below are not implementation approval.

Start the discussion with block environments and `if`/`else`:

- Decide condition admission/conversion explicitly. Existing boolean operators and
  explicit casts provide reusable owners; ordinary PHP truthiness is not an agreed
  implicit condition contract.
- Decide child-local visibility, shadowing, assignment to outer storage and what
  bindings survive a branch join. The current `preparation_context.locals` is one
  body-local table; internal blocks currently share it. Source block parsing remains
  deferred under [NOTE-033.b](01_literals_locals.md#note-033b--nested-statement-blocks).
- Extend completion composition before accepting branches. `Body_Preparation` currently
  reports straight-line fallthrough and composes internal blocks. It checks every
  statement, including those after a return, and rejects non-void fallthrough before
  body work settles. Merely finding a return in one branch cannot prove completion.
- Preserve lazy branch execution and prepare conditions in the semantic owner; C++
  generation consumes the selected facts. Do not loosen existing eager-effect
  restrictions merely to make a control-flow fixture compile.

Useful implementation owners: [body preparation](../../04_analyze/prepare/semantics/bodies.php),
[contexts/facts](../../04_analyze/prepare/data/structures.php),
[conversion preparation](../../04_analyze/conversions/preparation.php),
[scope lookup](../../03_parse/scopes/lookup.php) and the
[incremental scheduler](../../04_analyze/prepare/worker.php).
Existing [fallthrough](../../tests/fallthrough.php),
[internal-block dispatch](../../tests/preparation_dispatch.php) and
[recovery](../../tests/preparation_recovery.php) proofs provide starting controls.
Agree the first vertical slice before introducing a general control-flow graph.

Carry forward PE-03 duplicate-declaration debt, conditional runtime-template
copyability, bracket overload/lowering deferral and the preserved runtime arithmetic
contracts. Reopen only dependencies of the selected slice. Check the
[portability status](../portability/conversion_review.md): the latest chapter 02
repairs have PHP/generated-C++ proof, not a new native compiler checkpoint.

## Single-statement conditional bodies — 2026-10-20

[S06](../planning/s06_unbraced_conditionals.md) admits brace-free `if`, `elseif`
and `else` arms. Recursive conditional parsing binds clauses to the nearest
unmatched if. Each arm still owns an existing block and branch-local scope;
braced and unbraced bodies share their local-declaration guard. Source spans cover
the actual single statement without inventing brace tokens. Preparation and C++
emission retain their existing owners and behavior.

The [normative contract](../../../../specs/conditional_statements_v0_2.md) covers
binding, scope and lazy conditions. The original S06 fixture advances to the
separate unresolved `strpos` API (S09); it is not an end-to-end compatibility pass.
Unbraced loops, empty semicolon bodies, colon/endif syntax and numbered transfers
remain outside this slice. Earlier dated braced-only checkpoints below retain
their historical proof boundary and are superseded for conditional bodies only.

## Agreed first slice — 2026-10-02

CTRL-IF-001/002/003 and SCOPE-VAR-001 share one implementation slice, including
ordinary nested braced blocks. Conditions now use the shared scalar truthiness owner
(see the truthiness slice below), replacing the original bool-only admission.
Conditions execute once when reached, later
arms are lazy, and every arm is semantically prepared, including unreachable syntax.

`statement_body_node` is the shared abstract statement-body owner. It owns ordered
statements, spans and common traversal/dispatch. `block_node` is an empty final
subclass. `function_body_node` adds executable-unit scope/work identity and its
specialized dispatch. An `if_node` owns a required block body, an arm-kind enum,
an optional condition (absent only for else), and an optional owning `next_arm`
edge (absent for else). Only the initial arm belongs to the enclosing sequence.
This is a construct-specific syntax chain, not a general sibling list or CFG.

Preparation uses transient parent-linked local environments. Reads and untyped
writes resolve the nearest visible declaration; an untyped first write introduces
a local only if none is visible. Typed declarations belong to the current block,
reject current-block duplicates and may shadow outer locals. Their names hide outer
bindings during initialization; self-reads/writes reject. Branch-local names never
escape or merge at joins. Function parameters share the function's root environment.
The existing lexical-resolution contract and proof in
`specs/portability/lexical_resolution.md` and `compiler/tests/lexical_resolution/`
establish the explicit declaration/self-initialization behavior.

Statements report a transient `statement_completion` through typed dispatch.
Sequences compose normal fallthrough; conditional chains combine arm outcomes,
including an unmatched fallthrough path when else is absent. Non-void functions
reject normal exit. Each return uses the enclosing return-type contract. No
signature-dependent flag/backlink is retained on nested blocks. Constant-condition
reachability, definite-assignment analysis and branch value merging are deferred.

Target C++ is direct `if / else if / else`, braced bodies, and the prepared bool's
native value. Existing storage/conversion lowering handles all branch statements.
No extra runtime helper, condition temporary or lambda is needed for arm selection.
This straightforward form introduces no new compilation dependency family; output
partitioning and compilation benchmarks remain separate work.

Legacy review: `Generator::renderIfStatement` and `renderNestedStatements` already
emit lazy C++ branches and restore local visibility after each body. The retained
control-flow fixtures and `variables_003_inner_scope_shadow_basic` prove outer
assignment (despite that fixture's historical name). Legacy annotation/declaration
handling does not establish explicit typed-shadowing semantics for my-try; the
lexical-resolution evidence above does. Condition hints are not imported; the later
truthiness slice follows the established language/runtime contract explicitly.

First-slice non-goals: loops, switch/match, ternary, unbraced and colon/endif syntax, CFG, LLVM,
general effect analysis and the unrelated PE-03 duplicate-declaration debt.

Proof: [focused suite](../../tests/control_flow.php) and
[shared-pool runner](../../tests/control_flow.py). The PHP suites cover prepared
binding identity, condition facts, non-escape, duplicates/self-initialization,
return/fallthrough, unreachable checking, unchanged-body dependencies, no-edit
retries and clean/incremental recovery. Generated C++ programs prove branch choices,
side effects, condition evaluation count/laziness, shadowing and function returns
with `-Werror=return-type`. Final evidence: `/tmp/my-try-control-flow-20261002-final/`
(10 PHP suites and 24 generated programs passed).
This is PHP-host/compiler and generated-program proof, not a native compiler rebuild.

## Agreed loop slice — 2026-10-02


**Current scope — 2026-10-19 (CS105).** Loops prove init/test/update order, loop-local visibility, body-local exclusion, nearest transfers, do-continue test and for-continue update, break skipping and completion. PHP assertions inspect weak target identity, retained syntax and failed/repeated/fresh recovery. Reserved-only foreach prose is superseded by Chapter 06.

Contracts and owners: [conditional_expression_matrix.md](../../../../specs/conditional_expression_matrix.md), [expression_evaluation.md](../../../../specs/expression_evaluation.md), [array_semantics.md](../../../../specs/array_semantics.md), [v0_2_preparation_diagnostics.md](../../../../specs/v0_2_preparation_diagnostics.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS105`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

`loop_node extends breakable_node` owns one `block_node` body and shared preparation
dispatch. `while_node` and `do_while_node` own their condition; `for_node` owns ordered
initialization statements, condition expressions and update expressions. Named edges
drive maintenance and lazy inspection. `foreach_node` is an abstract reserved
specialization with a Chapter 06 TODO, not an accepted syntax form. Future iteration
must reuse this body/scope/transfer protocol; iterable/binding facts remain undecided.

`Loop_Preparation` establishes a loop-local environment and nearest break/continue
targets, then restores both on every exit. Body blocks have their own child environment.
For initialization supports ordinary first writes and explicit typed declarations,
including ordered comma lists; bindings do not escape the loop. Conditions/updates
cannot introduce locals or see body-local declarations. The final condition expression
uses shared condition truthiness; earlier expressions are evaluated
and discarded in order. An omitted for condition is unconditional. Braces are required.

`Completion_Preparation` composes normal fallthrough, return, break and continue.
Sequences discard unreachable exits (but still validate syntax); branches join exits;
loops consume their own bare transfers. A do-while whose body must return cannot fall
through. An omitted-test for has no normal exit unless its body can break. Explicit
constant conditions remain conservative; there is no CFG or constant reachability pass.
Return checking stays on the enclosing function, not a flag/backlink on loop blocks.

C++ uses native while/do/for and prepared native boolean tests. A surrounding block
owns for initialization, preserving ordinary declaration lowering. Native for updates
ensure continue executes the update; do-while continue reaches the test; break skips
both. Comma prefixes/updates are explicitly discarded to preserve built-in sequencing.
Transfers retain weak prepared target identities and emission checks those identities.
Breakable and continuable targets stay distinct for future switch support.

Legacy review: renderWhileStatement, renderDoWhileStatement, renderForStatement and
their clause helpers already use native loops and last-expression tests; one-level
break/continue fixtures establish their target behavior. This slice retains the shared
preparation boundary and lexical binding rules, not emitter-local truthiness
inference or its separate declaration-rendering shortcuts.

Non-goals: executable foreach, switch, numbered transfers, unbraced loops, CFG,
constant reachability, LLVM and new expression capabilities. Foreach continuation and
iteration lifetime need Chapter 06-specific facts/lowering, not a second loop family.

Proof: `tests/control_flow.php` / `tests/control_flow.py` cover execution order,
header/body scope, nearest transfer identity, lazy inspection, completion, moved body
identity, failed/no-edit retries and clean/incremental repair. Generated programs use
`-Werror=return-type`. These are PHP-host and generated-program proofs, not a native
compiler rebuild.

Final loop evidence: `/tmp/my-try-loops-20261002-final/` — 10 focused PHP suites
(shared FPM pool) and 43 generated-C++ programs passed.

</details>

## Agreed switch slice — 2026-10-02


**Current scope — 2026-10-19 (CS106).** Switch observes selector once, grouped labels, fallthrough/default anywhere, group-local shadowing, exact duplicate rejection and distinct break/loop-continue targets. Completion accounts for unmatched path. Canonical-int-only checkpoint is bounded; enum selection is a later verified owner, not unsupported.

Contracts and owners: [conditional_expression_matrix.md](../../../../specs/conditional_expression_matrix.md), [expression_evaluation.md](../../../../specs/expression_evaluation.md), [array_semantics.md](../../../../specs/array_semantics.md), [v0_2_preparation_diagnostics.md](../../../../specs/v0_2_preparation_diagnostics.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS106`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

`switch_node extends breakable_node` owns a selector and ordered
`switch_case_group` records. Each group owns explicit case/default labels and one
existing `block_node` body. Consecutive labels share a group. Group locals do not
escape to another group, even on fallthrough; outer locals remain assignable.

The first pass selects on canonical int, evaluated once. Case labels resolve exact
integer values before duplicate checking: decimal literals, resolved integer
constants, parentheses and unary signs. General constant-expression folding,
other selector types, enum/string cases and runtime labels are deferred. Default
is optional, unique and may occur anywhere; empty switches/bodies are valid.
Statements before the first label reject. Source braces delimit the switch;
case-group bodies do not require additional braces.

Preparation validates selection/labels and composes group completion in source
order, admitting direct entry at every group plus fallthrough from its predecessor.
The switch consumes break but propagates return and loop-targeting continue.
`Control_Transfer_Preparation` now owns target resolution shared by loops and switches;
the break kind is named `break_construct`, not the former loop-only spelling.
No default means a possible unmatched normal exit. All syntax is checked, including
unreachable bodies. No CFG or constant-selector reachability is introduced.

C++ uses a native switch of the prepared selector's native integer value, exact
resolved constant labels and a braced block per group. Native break exits the switch;
native continue passes through it to the enclosing loop. No new runtime helper,
lambda or dependency family is needed. Compilation-cost benchmarking is deferred.

Legacy review: `Generator::renderSwitchExpr` extracts the native value, while
`renderSwitchCaseValue` handles numeric literals/enums and emits an unsupported
comment for other forms. The basic retained switch fixture proves integer selection.
This slice instead validates labels centrally, rejects unsupported forms before
emission, and brackets group-local declarations explicitly. Legacy PHP loose matching
and its level-1 transfer spellings are not imported.

Proof: [control_flow.php](../../tests/control_flow.php) covers selection, fallthrough,
group-local scope, exact duplicate detection, nearest transfer identities, return
completion, lazy inspection, moved-body identity, failed/no-edit retries and clean/
incremental recovery (including malformed syntax). Final evidence:
`/tmp/my-try-switch-20261002-reviewed/` — 10 focused PHP suites through the shared
FPM pool and 65 generated-C++ executions passed with `-Werror=return-type`.
This does not prove a native compiler rebuild; that checkpoint remains on demand.

</details>

## Scalar truthiness update — 2026-10-02

All implemented condition consumers (`if`, `elseif`, `while`, `do-while`, and the
final for test) share `Condition_Truthiness` in `04_analyze/conversions/truthiness.php`.
It admits bool, integer, float and string values, retaining a `truthiness_decision`
on `prepared_condition`. Boolean identity and scalar runtime interpretation are
explicit operations. Conditions are no longer value conversions; Boolean_Conversions
continues to own explicit/typed boolean boundaries without condition-specific policy.

The existing normative [conditional contract](../../../../specs/conditional_expression_matrix.md)
and runtime `condition_truthy` own meaning: zero numeric values are false (including
negative floating zero); strings are false only for empty text and exactly `"0"`.
Thus `"false"` and `"yes"` are truthy, while explicit `(bool) "false"` is false and
`(bool) "yes"` rejects. Emission uses the existing shared helper, not copied rules.
Legacy S2S uses that helper for non-bool conditions; my-try selects the operation
in preparation rather than inferring condition semantics during emission.

Switch matching, logical-operator admission and explicit casts are unchanged.
Wrappers/mixed/object handles remain later slices. Ternary/Elvis consume this same
condition decision in the conditional-expression slice below;
single evaluation and lazy branch selection remain mandatory. No new runtime helper
or compiler-native rebuild is needed for this frontend/C++ slice.

Proof: `tests/control_flow.php` compares all five condition consumers across scalar
values, proves single evaluation/lazy arms, and checks that explicit `(bool) "yes"`
still raises the documented runtime error. Unsupported-record and void conditions
reject before emission. Incremental scalar signature changes refresh facts and
headers without replacing consumer syntax; failure/retry/recovery controls remain.
Evidence: `/tmp/my-try-truthiness-20261002-reviewed/` — 10 focused PHP suites and
83 generated-C++ programs passed. Compiler-native portability was not rerun.

## Agreed conditional-expression slice — 2026-10-02


**Current scope — 2026-10-19 (CS108).** Same-scalar ternary/Elvis prove one condition/left evaluation, lazy branches, snapshot value result, grammar precedence and unselected-branch validation/dependencies. PHP checks child edges, conversions and failed/repeated/repair equivalence. Later nullable/shared/record join ledgers extend this checkpoint; it is not a complete conditional matrix pass. Old effect gate is superseded.

Contracts and owners: [conditional_expression_matrix.md](../../../../specs/conditional_expression_matrix.md), [expression_evaluation.md](../../../../specs/expression_evaluation.md), [array_semantics.md](../../../../specs/array_semantics.md), [v0_2_preparation_diagnostics.md](../../../../specs/v0_2_preparation_diagnostics.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS108`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

Full ternary and Elvis share `conditional_expression_node`: required condition and
fallback, optional true branch (absent for shorthand), without a redundant kind flag.
Preparation uses `Condition_Truthiness`, prepares both branches, and accepts matching
scalar branch types under the existing conditional-expression matrix. Facts retain
the result type, truthiness and branch conversions. The result is a value, never
an assignable reference. Conditional writes cannot introduce branch-local bindings.

Emission uses a typed immediately invoked lambda: test once, return only the selected
branch by value. Shorthand first saves its left value in a uniquely named temporary,
then tests and returns that snapshot or evaluates fallback. This reproduces the
legacy `renderConditionalExpr`/`ternary_eval` single-evaluation and lazy-selection rules,
while shared preparation owns the now-known same-scalar result type. It introduces
no runtime helper or new semantic dependency family. Compilation benchmarking is
deferred; no performance claim is made for lambda spelling.

Conditional precedence is below logical OR and above assignment/keyword logic.
Full ternary nesting requires explicit parentheses outside the middle operand;
Elvis chains associate left. Parenthesize assignments in fallback operands in this
bounded parser slice. Eager surrounding operators still reject effectful subtrees.
Wrappers, mixed, non-scalar joins, general effect analysis and LLVM remain deferred.

Dated evidence: `tests/control_flow.py` passed 12 PHP suites and 108 generated-C++
programs on 2026-10-02 (`/tmp/my-try-ternary-20261002-final`). Focused checks cover
lazy alternatives, shorthand single evaluation, scalar condition consistency,
precedence, value-only results, distinct child edges, retained body identity,
dependency invalidation and failure/retry/repair. Generated programs compile with
`-Werror=return-type`. This is PHP-host and generated-program proof, not a native
compiler rebuild.

</details>

## Agreed match-expression slice — 2026-10-02

`match_expression_node` owns a selector and ordered `Storage<match_arm_node>`.
Each arm owns expression labels and one result expression; empty labels mean default,
without an extra flag. There is no statement body, break target or fallthrough.
The selector is evaluated once; the first strictly matching label selects its lazy
result. Default is considered only after all labels, irrespective of source position.
Repeated integer labels retain first-match behavior, unlike switch's duplicate check.

The agreed first pass accepts canonical int selectors and constant int labels using
the shared exact integer resolver. Grouped labels and trailing arm commas are supported.
Results must share one supported scalar type and are nonaddressable values, using
the same result-admission worker as ternary. Preparation checks every arm, records
normalized label decimals and explicit result conversions, and rejects new bindings
inside expressions. Multiple defaults reject. An empty match is deferred because it
needs a non-returning expression type rather than an invented scalar result.

Default is optional by explicit user agreement. An unmatched expression without it
throws `std::runtime_error("Unhandled match expression")`, matching the legacy failure
spelling; no implicit result is manufactured. The imported catalog's mandatory-default
rule is a recorded contract discrepancy, not the agreed v0.2 behavior.

Legacy `renderMatchExpr` and `tests/php/control_flow/level_02/control_flow_012_match_basic.phs`
were reviewed. The legacy emitter saves its selector but emits default at its source
position; this slice deliberately defers fallback until all ordinary labels fail.
C++ lowering uses a typed immediately invoked lambda, one native integer snapshot,
and ordered native comparisons against prepared constants. Within the admitted
canonical-int domain these implement strict equality without coercion. Typed lazy
returns avoid C++ result deduction and preserve value semantics. No new runtime helper,
dependency family or shared syntax superclass is needed. Compilation-cost benchmarking,
other selector types, runtime labels, broader result joins, exception syntax and LLVM
remain outside this slice.

Dated evidence: `tests/control_flow.py` passed 12 PHP suites and 132 generated-C++
programs on 2026-10-02 (`/tmp/my-try-match-20261002-second`). Match proofs cover grouped
and repeated labels, defaults at different positions, omitted default (successful
selection and a caught unmatched runtime error), scalar results, single evaluation,
lazy alternatives, exact signed constants, value-only results, retained AST identity,
and invalid-edit/retry/repair with unselected-arm dependency invalidation. Existing
ternary, switch, loop and truthiness controls also pass. This is PHP-host and
generated-program proof, not a native compiler rebuild.

## Progress


**Current scope — 2026-10-19 (CS110).** Progress table is routing evidence, not a substitute for per-rule verification. Its foreach deferred/parser-only status is stale; value iteration has independent executed proof and mutation policy remains separately deferred.

Contracts and owners: [conditional_expression_matrix.md](../../../../specs/conditional_expression_matrix.md), [expression_evaluation.md](../../../../specs/expression_evaluation.md), [array_semantics.md](../../../../specs/array_semantics.md), [v0_2_preparation_diagnostics.md](../../../../specs/v0_2_preparation_diagnostics.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS110`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CTRL-IF-001](#ctrl-if-001) | agreed | `$a bool = true; if ($a) { $b = 1; }` | proved | proved | deferred | [First slice](#agreed-first-slice--2026-10-02), control_flow.php |
| [CTRL-IF-002](#ctrl-if-002) | agreed | `$a bool = false; if ($a) { $b = 1; } else { $b = 2; }` | proved | proved | deferred | [First slice](#agreed-first-slice--2026-10-02), control_flow.php |
| [CTRL-IF-003](#ctrl-if-003) | agreed | `if (false) { } elseif (true) { } else { }` | proved | proved | deferred | [First slice](#agreed-first-slice--2026-10-02), control_flow.php |
| [CTRL-WHILE-001](#ctrl-while-001) | agreed | `while ($a) { $b++; }` | proved | proved | deferred | [Loop slice](#agreed-loop-slice--2026-10-02), control_flow.php |
| [EXPR-TERNARY-001](#expr-ternary-001) | agreed | `$a = $b ? $c : $d;` | proved | proved | deferred | [Matching-scalar slice](#agreed-conditional-expression-slice--2026-10-02), control_flow.php |
| [CTRL-SWITCH-001](#ctrl-switch-001) | agreed | `switch ($a) { case 1: break; default: break; }` | proved | proved | deferred | [Switch slice](#agreed-switch-slice--2026-10-02), control_flow.php |
| [CTRL-MATCH-001](#ctrl-match-001) | agreed | `$a = match ($b) { 1 => 10, default => 0 };` | proved | proved | deferred | [Integer-label slice](#agreed-match-expression-slice--2026-10-02), control_flow.php |
| [CTRL-DOWHILE-001](#ctrl-dowhile-001) | agreed | `do { $b++; } while ($a);` | proved | proved | deferred | [Loop slice](#agreed-loop-slice--2026-10-02), control_flow.php |
| [CTRL-FOR-001](#ctrl-for-001) | agreed | `for ($i = 0; $i < 10; $i++) { }` | proved | proved | deferred | [Loop slice](#agreed-loop-slice--2026-10-02), control_flow.php |
| [CTRL-BREAK-001](#ctrl-break-001) | agreed | `break;` | proved | proved | deferred | [Loop slice](#agreed-loop-slice--2026-10-02), control_flow.php |
| [CTRL-CONTINUE-001](#ctrl-continue-001) | agreed | `continue;` | proved | proved | deferred | [Loop slice](#agreed-loop-slice--2026-10-02), control_flow.php |
| [SCOPE-VAR-001](#scope-var-001) | agreed | `$x = 1; if (true) { $x = 2; } return $x;` | proved | proved | deferred | [First slice](#agreed-first-slice--2026-10-02), control_flow.php; output 2 |
| [NOTE-017](#note-017) | split | See NOTE-017.a/b | not-applicable | not-applicable | deferred | Summary reconciled below; no separate implementation |
| [NOTE-017.a](#note-017a--bounded-statement-control-flow) | agreed | Existing CTRL-IF/WHILE/DOWHILE/FOR/SWITCH examples | proved | proved | deferred | Existing bounded slices and tests/control_flow.php |
| [NOTE-017.b](#note-017b--foreach-belongs-to-chapter-06) | deferred | `foreach ($items as $item) { }` | deferred | deferred | deferred | Chapter 06 CTRL-FOREACH-001–004; reserved loop specialization only |

</details>

## CTRL-IF-001

**v0.2 decision / target C++:** [Agreed first slice](#agreed-first-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:122](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if ($a) { $b = 1; }
```

**Existing C++ lowering / result**

```cpp
if (a) { auto b = static_cast<int_t>(1); }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible

**Normalized pattern:** `if (<expr>) <block>`

**General rule:** `if` statements are emitted directly. The condition must be boolean-compatible. The body is emitted as a block, and all inner statements must follow standard normalization rules (including `auto` only on first assignment in scope and literal casting).

**Diagnostics:** Error if condition is not boolean-compatible, declaration rules are violated, or literals are not normalized.

**Notes:** Scope rule applies inside the block.


## CTRL-IF-002

**v0.2 decision / target C++:** [Agreed first slice](#agreed-first-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:123](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if ($a) { $b = 1; } else { $b = 2; }
```

**Existing C++ lowering / result**

```cpp
if (a) { auto b = static_cast<int_t>(1); } else { auto b = static_cast<int_t>(2); }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible

**Normalized pattern:** `if (<expr>) <block> else <block>`

**General rule:** `if/else` statements are emitted directly. Each branch is its own scope. Inner assignments follow normal scope rules, including `auto` only on first assignment in that branch scope, and literal normalization.

**Diagnostics:** Error if condition is not boolean-compatible, branch declaration rules are violated, or literals are not normalized.


## CTRL-IF-003

**v0.2 decision / target C++:** [Agreed first slice](#agreed-first-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:124](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if ($a) { } elseif ($b) { } else { }
```

**Existing C++ lowering / result**

```cpp
if (a) { } else if (b) { } else { }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** each condition must be boolean-compatible

**Normalized pattern:** `if / elseif / else chain`

**General rule:** PHP `elseif` chains are emitted as C++ `else if` chains. Conditions must be boolean-compatible.

**Diagnostics:** Error if a condition is not boolean-compatible or `elseif` is emitted literally into C++.


## CTRL-WHILE-001

**v0.2 decision / target C++:** [Agreed loop slice](#agreed-loop-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:127](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
while ($a) { $b++; }
```

**Existing C++ lowering / result**

```cpp
while (a) { b++; }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible; body statements valid in scope

**Normalized pattern:** `while (<expr>) <block>`

**General rule:** `while` loops are emitted directly. The condition must be boolean-compatible. The loop body follows normal statement and scope rules.

**Diagnostics:** Error if condition is not boolean-compatible or body uses undeclared variables.


## EXPR-TERNARY-001

**v0.2 decision / target C++:** Matching scalar branches use the shared conditional
node and a typed value-returning lambda; conditions use shared scalar truthiness.
See the [bounded slice](#agreed-conditional-expression-slice--2026-10-02) for grammar
limits and deferred joins. Imported lowering below remains historical evidence.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:88](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b ? $c : $d;
```

**Existing C++ lowering / result**

```cpp
auto a = php::ternary_eval([&]() -> decltype(auto) { return b; }, [&]() -> decltype(auto) { return c; }, [&]() -> decltype(auto) { return d; });
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition and branch combination must be covered by the runtime ternary matrix

**Normalized pattern:** `<var> = <cond> ? <expr> : <expr>`

**General rule:** The ternary operator lowers through `php::ternary_eval(...)` so condition truthiness and branch normalization are resolved centrally in the runtime instead of ad hoc in the generator.

**Diagnostics:** Error if the runtime ternary matrix does not define the branch pair.

**Notes:** This keeps direct expressions and assigned locals consistent.


## CTRL-SWITCH-001

**v0.2 decision / target C++:** [Agreed switch slice](#agreed-switch-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:125](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
switch ($a) { case 1: break; default: break; }
```

**Existing C++ lowering / result**

```cpp
switch (a) { case static_cast<int_t>(1): break; default: break; }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported-with-known-incompatibility

**Preconditions:** switch expression and case values must be compatible

**Normalized pattern:** `switch (<expr>) { case ... }`

**General rule:** `switch` is emitted directly as C++ `switch`, with normalized literals.

**Diagnostics:** Error if literals are not normalized.

**Notes:** PHP `switch` uses loose comparison; C++ `switch` uses strict matching.


## CTRL-MATCH-001

**v0.2 decision / target C++:** [Agreed integer-label slice](#agreed-match-expression-slice--2026-10-02).
Default is optional by explicit agreement; unmatched selection throws. A typed lambda
saves the selector once and returns one lazily selected same-scalar value. The imported
mandatory-default rule and abbreviated lowering below are historical evidence, not
this slice's implementation contract.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:126](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = match ($b) { 1 => 10, default => 0 };
```

**Existing C++ lowering / result**

```cpp
int_t tmp; if (php::identical(b, static_cast<int_t>(1))) { tmp = static_cast<int_t>(10); } else { tmp = static_cast<int_t>(0); } auto a = tmp;
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** all match arms must produce compatible types; default arm must exist

**Normalized pattern:** `<var> = match (<expr>) { <cases> }`

**General rule:** PHP `match` must be lowered to an `if / else if / else` chain using strict comparison (`php::identical`). A temporary variable must be introduced to preserve expression semantics.

**Diagnostics:** Error if no `default` arm, result types differ, or `match` is emitted directly.

**Notes:** Preserves PHP `===` semantics.


## CTRL-DOWHILE-001

**v0.2 decision / target C++:** [Agreed loop slice](#agreed-loop-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:128](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
do { $b++; } while ($a);
```

**Existing C++ lowering / result**

```cpp
do { b++; } while (a);
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible; body statements valid in scope

**Normalized pattern:** `do <block> while (<expr>)`

**General rule:** `do-while` loops are emitted directly. The body executes first, then the condition is evaluated. All inner statements must follow normalization and scope rules.

**Diagnostics:** Error if condition is not boolean-compatible or body uses undeclared variables.


## CTRL-FOR-001

**v0.2 decision / target C++:** [Agreed loop slice](#agreed-loop-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:129](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
for ($i = 0; $i < 10; $i++) { }
```

**Existing C++ lowering / result**

```cpp
for (auto i = static_cast<int_t>(0); i < static_cast<int_t>(10); i++) { }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** loop variable declared in init; condition boolean-compatible

**Normalized pattern:** `for (<init>; <cond>; <step>) <block>`

**General rule:** `for` loops are emitted directly. Initialization follows normal declaration rules (`auto` on first assignment). Condition and step follow expression normalization rules, including literal casting.

**Diagnostics:** Error if literals are not normalized, declaration rules are violated, or condition is not boolean-compatible.

**Notes:** Loop variable scope is limited to the `for` statement.


## CTRL-BREAK-001

**v0.2 decision / target C++:** [Agreed loop slice](#agreed-loop-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:134](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
break;
```

**Existing C++ lowering / result**

```cpp
break;
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** must be inside a loop or `switch`

**Normalized pattern:** `break`

**General rule:** `break` is emitted directly.

**Diagnostics:** Error if used outside of loop or `switch`.


## CTRL-CONTINUE-001

**v0.2 decision / target C++:** [Agreed loop slice](#agreed-loop-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:135](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
continue;
```

**Existing C++ lowering / result**

```cpp
continue;
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** must be inside a loop

**Normalized pattern:** `continue`

**General rule:** `continue` is emitted directly.

**Diagnostics:** Error if used outside of a loop.


## SCOPE-VAR-001

**v0.2 decision / target C++:** [Agreed first slice](#agreed-first-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:316](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x = 1; if (true) { $x = 2; } echo $x;
```

**Existing C++ lowering / result**

```cpp
auto x = static_cast<int_t>(1); if (...) { x = static_cast<int_t>(2); } php::echo_one(x);
```

**Category:** Scope

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** variable resolves to a declaration in the current block or an enclosing block

**Normalized pattern:** `block-local variable visibility`

**General rule:** Safe v1 uses block-local visibility. The first write in a block declares a new variable only when no visible variable of the same name exists in an enclosing block. Otherwise the write is an assignment to the visible outer variable. A variable is visible only in its declaring block and child blocks.

**Diagnostics:** Emit an explicit generator error when a variable is used outside the block where it was declared.

**Notes:** This intentionally rejects PHP-style block escape for locals and closures.


## NOTE-017


**Current scope — 2026-10-19 (CS123).** Overview splits statement control flow from container iteration; it adds no semantic feature. Individual branch/loop/switch and Chapter 06 owners carry proofs; imported rejection heading contradicts foreach lowering and is historical provenance.

Contracts and owners: [conditional_expression_matrix.md](../../../../specs/conditional_expression_matrix.md), [expression_evaluation.md](../../../../specs/expression_evaluation.md), [array_semantics.md](../../../../specs/array_semantics.md), [v0_2_preparation_diagnostics.md](../../../../specs/v0_2_preparation_diagnostics.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS123`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**Source:** [generators/php/specs/rules.md:249](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples — 2026-10-02:** Reconciled into the two bounded items
below. This is an overview of existing feature entries, not another implementation
task or a claim of full PHP control-flow compatibility. The imported text is retained
unchanged for provenance.

</details>

### NOTE-017.a — Bounded statement control flow

The supported list is covered by the existing working examples and proofs:

- `if / elseif / else`: CTRL-IF-001–003 and the [shared-body slice](#agreed-first-slice--2026-10-02).
- `while`, `do-while`, `for`: CTRL-WHILE-001, CTRL-DOWHILE-001, CTRL-FOR-001 and the [loop slice](#agreed-loop-slice--2026-10-02).
- `switch`: CTRL-SWITCH-001 and the [canonical-int switch slice](#agreed-switch-slice--2026-10-02).

The legacy “known mismatch” remark does not expand switch admission: canonical-int
selectors and constant-int labels are the proved scope. Other selector/label types
remain deferred. Conditions use the already agreed shared scalar truthiness policy.
Scope, completion and transfer rules remain owned by their respective slices.

Evidence is [control_flow.php](../../tests/control_flow.php) through the
[focused runner](../../tests/control_flow.py). The latest dated proof above passed
12 PHP suites and 132 generated-C++ programs; this reconciliation adds no test run
and makes no new native-compiler claim. Ternary and match remain separately tracked
by EXPR-TERNARY-001 and CTRL-MATCH-001, not implied by the imported list.

### NOTE-017.b — Foreach belongs to Chapter 06


**Current scope — 2026-10-19 (CS125).** Ownership correctly routes foreach to Chapter 06, but reserved/parser-rejected-only description is stale. Native foreach observers prove vector/hash/mixed/dynamic ordered value iteration, empty/nested/temp source, scope, conversions and transfers. Reference iteration/structural mutation policy remain separately deferred.

Contracts and owners: [conditional_expression_matrix.md](../../../../specs/conditional_expression_matrix.md), [expression_evaluation.md](../../../../specs/expression_evaluation.md), [array_semantics.md](../../../../specs/array_semantics.md), [v0_2_preparation_diagnostics.md](../../../../specs/v0_2_preparation_diagnostics.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS125`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

The imported “Rejected” heading contradicts its own lowering sentence. Treat that
as an inherited documentation discrepancy, not a language-level ban or an agreed
indexed-for lowering contract for this compiler.

Executable foreach remains deferred to [Chapter 06, CTRL-FOREACH-001–004](06_containers.md#ctrl-foreach-001).
That chapter already records the reserved `foreach_node extends loop_node` and its
TODO in [structures_loops.php](../../03_parse/structures/structures_loops.php).
Future work must reuse the shared loop/body/transfer protocol and establish container,
binding and reference behavior there. The placeholder is not parser, preparation or
emission support; current parser rejection does not complete the foreach feature.

</details>

### Imported prose

> ## 9. Control Flow
>
> ### Supported
> - `if / else / elseif`
> - `while`
> - `do-while`
> - `for`
> - `switch` (known mismatch remains documented separately)
>
> ### Rejected
> - `foreach` over `vector_t` lowers to an indexed C++ `for` loop
>
> ---
