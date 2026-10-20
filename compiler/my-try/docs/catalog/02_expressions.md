# 02. Expressions and scalar operations
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires locals. Establish precedence, operand types and evaluation order before composed expressions.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

The [preparation/emission audit](../planning/preparation_emission_audit.md) is complete;
bounded copy admission, redundant value-modifier handling and straight-line non-void
fallthrough are repaired. Duplicate declarations are explicitly deferred debt (PE-03),
alongside conditional runtime capability debt. The proved rows below describe bounded examples, not unrestricted
copying or declaration validity.

## Direct bool equality and float arithmetic — 2026-10-17

The agreed scalar admission slice is implemented and verified on dev002:
bool equality, canonical float arithmetic with canonical int peers, and float
unary signs. [Evidence and resolved zero-division contract](../planning/v0_1_compatibility_scalar_admission.md)
record ten PHP suites, seven generated programs, 48 original compile cases and
60 direct scalar matrix rows. Lifted wrappers, conditional selection, float
comparisons/updates, fixture migration and table semantics remain deferred.

## Compound RHS effects — 2026-10-10

The [compound contract](../../../../specs/expression_evaluation.md#compound-updates--2026-10-10)
now accepts ordinary prepared RHS values without the old recursive effect-shape
gate. Implicit locals remain forbidden. C++ reuses the ordered binary renderer
and shared field-owner binding; boxed numeric write-back checks the preserved
left kind before storing. Existing compound syntax/operation/conversion facts
remain intact, allowing future proven-equivalent direct `+=` lowering.

`tests/compound_effects.py` passed four PHP tasks (including the compound suite)
and three generated programs at `/tmp/compound-effects-20261010-1` (5.11–5.75s
compile/run). Proof includes RHS replacement of the destination/last field owner,
nested updates, lazy expressions, mixed/dynamic values and failure side effects.
The accounting retry at `/tmp/accounting-compound-20261010` now reports only indexed
compound mutation. No full application execution or native compiler rebuild yet.

## Guarded wrapper operators — 2026-10-19

The [current guarded contracts](../s2s_scalar_operators.md#guarded-wrapper-operators--current-through-2026-10-19)
cover canonical-int, float, mixed numeric arithmetic/logical, bool logical and
string ordering operations. All four carrier families use independent checked
payload extraction, ordinary scalar decisions and ordered value snapshots.
The [cross-family slice](../planning/guarded_cross.md) adds all 660 approved
signatures across 12 ordered distinct-family pairs, including local/property
snapshots, byte semantics, left-error precedence and lazy RHS checks.

The [guarded-bool XOR slice](../planning/guarded_bool_xor.md) adds all 24 bool
signatures with eager evaluation, family-specific errors and local/property snapshots.

The [guarded spaceship slice](../planning/guarded_spaceship.md) adds 48 canonical-int
and string signatures with exact int results, byte semantics and checked snapshots.
Other payload types and mixed pairs remain excluded.

The [numeric XOR slice](../planning/numeric_xor.md) adds four plain and 96 guarded
canonical int/float signatures with eager truth snapshots and checked extraction.
Bool/numeric mixing, strings and fixed-width expansion remain excluded.

The [nullable-int equality slice](../planning/nullable_int_equality.md) adds 12
`== != === !==` signatures over nullable/nullable and nullable/plain canonical int.
It snapshots complete states; empty/empty is equal, empty/payload is unequal, and
both operands remain eager. Guarded extraction and literal-null presence stay separate.

The [nullable bool/string extension](../planning/nullable_bool_string_equality.md)
adds 24 matching-payload signatures with the same state/snapshot contract. Empty
states remain distinct from false, empty string and zero string; string equality
uses complete bytes.

The [nullable-float extension](../planning/nullable_float_equality.md) adds the final
12 canonical nullable signatures, including NaN and signed-zero behavior.

The [structured-error Result extension](../planning/result_equality.md) adds 48
matching `result<T>`/plain signatures. Failure equality compares states; strict
identity compares error metadata. `Wrapper_Equality` owns both this and nullable admission.

The [false-sentinel bool extension](../planning/false_bool_equality.md) adds 12
`result_or_false<bool>`/plain-bool signatures with distinct equality/identity
normalization and preserved carrier snapshots. Other sentinel payloads remain separate.

The [two-sentinel bool extension](../planning/bool_sentinel_equality.md) adds 12
`result_or_bool<bool>`/plain-bool signatures, verifying both sentinels separately
from their matching payload values.

[Non-bool sentinel equality](../planning/nonbool_sentinel_equality.md) adds 48
same-carrier comparisons and carrier/plain strict identities for int/float/string.
The [subsequent sentinel/plain repair](../planning/sentinel_plain_equality.md)
resolves all 24 ordinary carrier/plain runtime blockers: sentinels differ from
matching plain non-bool values; present payloads compare normally.

[Carrier updates](../planning/carrier_updates.md) now cover all 32 numeric carrier
prefix/postfix forms, preserving state on failure and returning source snapshots.
The original V-R10 nullable runtime test passes. [Canonical-int arithmetic compounds](../planning/int_carrier_compounds.md) now cover
48 same-carrier/plain signatures with ordered extraction and once-only write-back.
[The remainder/bitwise/shift extension](../planning/int_bitwise_compounds.md) adds
72 more, completing the ten bounded same-carrier/plain canonical-int compounds.
[Cross-family int compounds](../planning/int_cross_compounds.md) add all 120 ordered
distinct-carrier forms with independent extraction and destination-typed wrapping.
[Exact-float compounds](../planning/float_compounds.md) add four scalar prerequisites
and all 96 carrier signatures, preserving signed-zero division errors and snapshots.
[Mixed ordering O1](../planning/mixed_ordering.md) adds 192 carrier signatures and eight
scalar prerequisites with explicit int-to-float conversion and eager snapshots.
[Float-destination C2](../planning/widening_compounds.md) adds 96 carrier signatures
and four scalar prerequisites with int RHS promotion and stable write-back.
Indexed updates, narrowing/indexed and other compounds remain separate.

V-D13 and nullable-int V-L08 are repaired; V-L07 remains partial. Equality/identity beyond matching nullable/result int/float/bool/string and both sentinel-result bool families,
mixed numeric ordering, updates/compounds, other-type spaceship and other
payload/operator domains remain separate. Bool/numeric and string logical pairs
are excluded. Direct conditions/selectors retain their separate contracts.
Historical broad lifting gaps below are narrowed only by these explicit scopes;
earlier evidence is linked from the current guide.

## Ordinary string comparisons — 2026-10-19

[V-L03](../planning/string_comparisons_l03.md) implements all seven exact
string/string comparisons with complete-byte equality, lexicographic byte ordering
and left-to-right value snapshots. Six results are bool; spaceship is canonical
int -1/0/1. The focused dev002 proof covers embedded NUL/high bytes, numeric-looking
strings, exceptions, mutation and retained-session repair. No numeric coercion,
Unicode normalization, wrapper expansion or runtime implementation change is implied.
This supersedes ordinary-string admission gaps in the earlier checkpoints below.

## String strict identity — 2026-10-10

Boolean follow-up: the selector and prepared operations are now shared scalar
identity for admitted same-type strings and booleans. Boolean identity is eager
value equality, not truthiness or short circuiting. The same focused runner also
checks all boolean input pairs, field-comparison results, ordered calls and RHS
mutation of the left source. Cross-type comparison remains rejected. Existing
canonical-int, enum and boxed paths are unchanged.
Evidence: `/tmp/scalar-identity-20261010-final` passes three PHP tasks and two
generated programs (7.23s/7.24s compile/run). The unchanged accounting retry at
`/tmp/accounting-bool-20261010` reports only compound RHS and indexed mutation
restrictions; comparison errors are gone. No native compiler rebuild was performed.

String identity follow-up: same-type `string === string` and `!==` now have
shared scalar prepared operation identities, ordinary identity conversions and the
shared ordered binary/helper lowering. Byte-exact contents include NUL and do
not normalize Unicode. Cross-type and ordinary/ordered string comparisons remain
outside this slice. Contract: [strict identity](../../../../specs/operator_matrix/profile_semantics_v1.md#v02-same-type-strings--2026-10-10).
Focused runner: `tests/string_identity.py` (prepared facts, operator/conversion/
boxed regressions, two generated programs for bytes, calls and snapshot ordering).

Evidence: `/tmp/string-identity-20261010-final` passes three PHP tasks and both
generated programs (6.32s/6.36s compile/run). An initial runner attempt named a
nonexistent strings suite; the corrected runner uses the existing conversions
suite. No runtime implementation change or native compiler rebuild was needed.
The accounting retry at `/tmp/accounting-string-20261010` now exposes boolean
strict identity in `GeneralLedger::validate`, alongside the previously recorded
compound-update restrictions. Full accounting emission/execution remains blocked.

## Fixed-width binary follow-up — 2026-10-10

The [agreed integer-domain contract](../../../../specs/compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons)
extends ordinary arithmetic and numeric comparisons to same-signedness widths.
`Integer_Operators` selects the wider registered operand domain and publishes
ordinary conversion decisions; existing ordered C++ lowering and runtime helpers
are reused without type-name/pair tables. Canonical `int` wins when present;
spaceship always returns canonical signed -1/0/1. Mixed signedness rejects.

Legacy runtime `generated/operator_detail.hpp` already selects the wider
same-signedness representation; `runtime_core_values_010_fixed_width_ints.cpp`
proves that behavior. This slice brings preparation into line with that bounded
policy, not with general C++ signed/unsigned promotions. No runtime rewrite or
native compiler rebuild is part of this checkpoint.

Proof: `tests/integer_widths.py` checks prepared type identities/conversions,
rejections, actual field/call arithmetic, full-range unsigned comparison,
sequencing and zero-divisor errors. Compound/unary updates, bitwise/shifts,
strict identity and power retain their old admission; broader work is
[CH15 debt](15_blocking_debt.md#ch15-integer-domains-01--integer-interoperability-and-overflow).

Evidence: `/tmp/integer-widths-20261010-final` passed four PHP tasks (including
the existing operator suite) and two generated programs, 5.04s/7.61s compile/run.
FPM was unavailable in the sandbox; the shared runner used its CLI fallback.
An initial run exposed an old conversion-test spelling expectation from the eager
sequencing change; that assertion now checks the ordered snapshots. Fixture fixes
removed an out-of-scope full-range string cast and a missing test-only error header.
The supplementary S2S PHP regression passed in 39.00s at
`/tmp/integer-widths-s2s-20261010`; its exported programs were not additionally built.

## Positive pass conclusion — 2026-10-02


**Current scope — 2026-10-19 (CS037).** October 02 closeout is revision-specific. Old bracket and child-block deferrals are superseded by later owners; this section cannot prove current completion or native-compiler parity.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [v0_2_preparation_diagnostics.md](../../../../specs/v0_2_preparation_diagnostics.md), [compact_layout_types.md](../../../../specs/compact_layout_types.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS037`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**Status: positive pass complete for the agreed bounded frontend + C++ S2S scope.**
Implementation checkpoint: `d3cedb46aee4b35dcd76763179535c0eef25dc2d`.

The pass prioritizes working functionality and sound structures, semantic ownership
and reusable processing paths. Grouping is represented by expression-tree shape;
preparation owns operator/conversion selection, storage and copy eligibility;
source-record capabilities belong to completed declarations; emission consumes the
prepared decisions. Incremental invalidation and recovery have focused proofs.
No further detail-hardening is required to conclude this pass.

Deferred work stays visible: concrete bracket overloads/lowering (both bracket forms
already share one syntax/preparation path), broader operand/effect combinations,
conditional runtime-template capabilities, checked shift counts, floating power and
other documented scalar extensions. Duplicate declarations remain PE-03 debt.
Branch/loop completion and child-block environments belong to the next control-flow
work. Revisit a debt item when an agreed feature depends on it.

The latest repairs have focused PHP and generated-C++ validation. The earlier native
compiler checkpoint remains revision-specific; this conclusion does not claim a
native rebuild of the latest implementation. Existing row-level proof/deferred
statuses remain authoritative for individual capabilities.

</details>

## Progress

### Ordered eager operands — 2026-10-10

The approved [language amendment](../../../../specs/expression_evaluation.md)
replaces the historical effect-shape gate for eager binaries: evaluate/convert left,
then right, then compute from saved values. Fields/calls use ordinary expression
preparation. The common C++ renderer sequences arithmetic, power, bitwise/shift,
comparisons, concatenation, xor and spaceship; logical/selection laziness remains.
No AST node or per-field/per-call operator implementation was added. The subsequent
compound RHS and scalar identity checkpoints above supersede this checkpoint's
former compound and same-type boolean restrictions; numeric type admission remains
separately defined.

`tests/eager_binary.py` checks the new behavior, former rejection cases, eight PHP
suites and four generated programs. `/tmp/eager-binary-20261010-verified` passed;
generated compile/run tasks took 7.39–10.15s concurrently without PCH. Initial checks
exposed a null-sentinel spelling issue (fixed by materializing the already-converted
C++ value with `auto`) and stale fixture assumptions; no runtime semantics were
changed to satisfy tests. The S2S PHP regression also passed in
`/tmp/eager-binary-s2s-20261010-final` (42.17s), including syntax purity,
incremental reuse and updated ordered-output expectations. Native compiler
rebuilding remains separate.


Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [EXPR-PAREN-001](#expr-paren-001) | agreed | `$b = 2; $a = ($b + 1);` | proved | proved | deferred | [Grammar, AST, spans and incremental proof](../../tests/grouping.php); [PHP-host generation and Clang execution fixtures](../../tests/s2s.php); native compiler execution not rerun for this slice |
| [EXPR-ARITH-001](#expr-arith-001) | agreed | `$a = 1 + 2;` | proved | proved | deferred | [Prepared operator decision](../../tests/operators.php); [PHP/native bytes and Clang execution](../portability/conversion_review.md) |
| [EXPR-SUB-001](#expr-sub-001) | agreed | `$a = 1 - 2;` | proved | proved | deferred | [Prepared decisions, rejection and incremental proof](../../tests/operators.php); [PHP-host generation and Clang execution fixtures](../../tests/s2s.php); native compiler execution not rerun for this slice |
| [EXPR-MUL-001](#expr-mul-001) | agreed | `$a = 2 * 3;` | proved | proved | deferred | [Prepared decisions, precedence and incremental proof](../../tests/operators.php); [PHP-host generation and Clang execution fixtures](../../tests/s2s.php); native compiler execution not rerun for this slice |
| [EXPR-DIV-001](#expr-div-001) | agreed | `$a = 4 / 2;` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-MOD-001](#expr-mod-001) | agreed | `$a = 5 % 2;` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-POW-001](#expr-pow-001) | agreed | `$a = 2 ** 3;` | proved | proved | deferred | [Checked integer power contract and proof](../s2s_scalar_operators.md#integer-exponentiation) |
| [EXPR-NEG-001](#expr-neg-001) | agreed | `$b = 3; $a = -$b;` | proved | proved | deferred | [Unary contract, limits and proof](../s2s_scalar_operators.md#unary-operators) |
| [EXPR-POS-001](#expr-pos-001) | agreed | `$b = 3; $a = +$b;` | proved | proved | deferred | [Unary contract, limits and proof](../s2s_scalar_operators.md#unary-operators) |
| [EXPR-BNOT-001](#expr-bnot-001) | agreed | `$b = 3; $a = ~$b;` | proved | proved | deferred | [Unary contract, limits and proof](../s2s_scalar_operators.md#unary-operators) |
| [EXPR-NESTED-001](#expr-nested-001) | agreed | `$b = 3; $a = ($b + 1) * 2;` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-CHAIN-001](#expr-chain-001) | agreed | `$b = 3; $c = 4; $a = $b + 1 + $c;` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-CONCAT-001](#expr-concat-001) | agreed | `$a = "a" . "b";` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-CONCAT-002](#expr-concat-002) | agreed | `$b = "b"; $a = $b . "x";` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-CONCAT-003](#expr-concat-003) | agreed | `$b = "b"; $a = "x" . $b;` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-COMP-001](#expr-comp-001) | agreed | `$b = 1; $a = ($b == 1);` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-NEQ-001](#expr-neq-001) | agreed | `$b = 1; $a = ($b != 1);` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-SEQ-001](#expr-seq-001) | agreed | `$b = 1; $a = ($b === 1);` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-SNEQ-001](#expr-sneq-001) | agreed | `$b = 1; $a = ($b !== 1);` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-LT-001](#expr-lt-001) | agreed | `$b = 1; $a = ($b < 1);` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-LTE-001](#expr-lte-001) | agreed | `$b = 1; $a = ($b <= 1);` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-GT-001](#expr-gt-001) | agreed | `$b = 1; $a = ($b > 1);` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-GTE-001](#expr-gte-001) | agreed | `$b = 1; $a = ($b >= 1);` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-SPACESHIP-001](#expr-spaceship-001) | agreed | `$b = 1; $c = 2; $a = ($b <=> $c);` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-LOGIC-001](#expr-logic-001) | agreed | `$a = true && false;` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-OR-001](#expr-or-001) | agreed | `$a = true \|\| false;` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [EXPR-NOT-001](#expr-not-001) | agreed | `$b = false; $a = !$b;` | proved | proved | deferred | [Unary contract, limits and proof](../s2s_scalar_operators.md#unary-operators) |
| [EXPR-ANDWORD-001](#expr-andword-001) | agreed | `$a = false; $b = true; $c = false; $a = $b and $c;` | proved | proved | deferred | [Assignment/logical contract and proof](../s2s_scalar_operators.md#assignment-expressions-and-keyword-logic) |
| [EXPR-ORWORD-001](#expr-orword-001) | agreed | `$a = false; $b = true; $c = false; $a = $b or $c;` | proved | proved | deferred | [Assignment/logical contract and proof](../s2s_scalar_operators.md#assignment-expressions-and-keyword-logic) |
| [EXPR-XORWORD-001](#expr-xorword-001) | agreed | `$a = false; $b = true; $c = false; $a = $b xor $c;` | proved | proved | deferred | [Assignment/logical contract and proof](../s2s_scalar_operators.md#assignment-expressions-and-keyword-logic) |
| [EXPR-PREINC-001](#expr-preinc-001) | agreed | `$b = 3; $a = ++$b;` | proved | proved | deferred | [Mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement) |
| [EXPR-POSTINC-001](#expr-postinc-001) | agreed | `$b = 3; $a = $b++;` | proved | proved | deferred | [Mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement) |
| [EXPR-PREDEC-001](#expr-predec-001) | agreed | `$b = 3; $a = --$b;` | proved | proved | deferred | [Mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement) |
| [EXPR-POSTDEC-001](#expr-postdec-001) | agreed | `$b = 3; $a = $b--;` | proved | proved | deferred | [Mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement) |
| [STMT-EXPR-001](#stmt-expr-001) | agreed | `$a = 1; $a + 1;` | proved | proved | deferred | [Bounded scalar decisions and PHP-host/generated-C++ proof](../s2s_scalar_operators.md) |
| [STMT-ASSIGNOP-001](#stmt-assignop-001) | agreed | `$a = 3; $a += 1;` | proved | proved | deferred | [Compound assignment contracts and proof](../s2s_scalar_operators.md#compound-assignment) |
| [STMT-ASSIGNOP-002](#stmt-assignop-002) | agreed | `$a = 3; $a -= 1;` | proved | proved | deferred | [Compound assignment contracts and proof](../s2s_scalar_operators.md#compound-assignment) |
| [STMT-ASSIGNOP-003](#stmt-assignop-003) | agreed | `$a = 3; $a *= 2;` | proved | proved | deferred | [Compound assignment contracts and proof](../s2s_scalar_operators.md#compound-assignment) |
| [STMT-ASSIGNOP-004](#stmt-assignop-004) | agreed | `$a = 3; $a /= 2;` | proved | proved | deferred | [Compound assignment contracts and proof](../s2s_scalar_operators.md#compound-assignment) |
| [STMT-ASSIGNOP-005](#stmt-assignop-005) | agreed | `$a = "a"; $a .= "x";` | proved | proved | deferred | [Compound assignment contracts and proof](../s2s_scalar_operators.md#compound-assignment) |
| [STMT-ASSIGNOP-006](#stmt-assignop-006) | agreed | `$a = 1; $a += 1;` | proved | proved | deferred | [Compound assignment contracts and proof](../s2s_scalar_operators.md#compound-assignment) |
| [STMT-ASSIGNOP-007](#stmt-assignop-007) | agreed | `$a = 3; $a %= 2;` | proved | proved | deferred | [Compound assignment contracts and proof](../s2s_scalar_operators.md#compound-assignment) |
| [STMT-ASSIGNOP-008](#stmt-assignop-008) | agreed | `$a = 7; $a &= 3;` | proved | proved | deferred | [Bitwise/shift contract and proof](../s2s_scalar_operators.md#bitwise-and-shift-operators); [checked-count debt](../planning/operators.md#debt-checked-shift-counts) |
| [STMT-ASSIGNOP-009](#stmt-assignop-009) | agreed | `$a = 7; $a \|= 3;` | proved | proved | deferred | [Bitwise/shift contract and proof](../s2s_scalar_operators.md#bitwise-and-shift-operators); [checked-count debt](../planning/operators.md#debt-checked-shift-counts) |
| [STMT-ASSIGNOP-010](#stmt-assignop-010) | agreed | `$a = 7; $a ^= 3;` | proved | proved | deferred | [Bitwise/shift contract and proof](../s2s_scalar_operators.md#bitwise-and-shift-operators); [checked-count debt](../planning/operators.md#debt-checked-shift-counts) |
| [STMT-ASSIGNOP-011](#stmt-assignop-011) | agreed | `$a = 7; $a <<= 1;` | proved | proved | deferred | [Bitwise/shift contract and proof](../s2s_scalar_operators.md#bitwise-and-shift-operators); [checked-count debt](../planning/operators.md#debt-checked-shift-counts) |
| [STMT-ASSIGNOP-012](#stmt-assignop-012) | agreed | `$a = 7; $a >>= 1;` | proved | proved | deferred | [Bitwise/shift contract and proof](../s2s_scalar_operators.md#bitwise-and-shift-operators); [checked-count debt](../planning/operators.md#debt-checked-shift-counts) |
| [STMT-PREINC-001](#stmt-preinc-001) | agreed | `$a = 3; ++$a;` | proved | proved | deferred | [Mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement) |
| [STMT-PREDEC-001](#stmt-predec-001) | agreed | `$a = 3; --$a;` | proved | proved | deferred | [Mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement) |
| [STMT-POSTINC-001](#stmt-postinc-001) | agreed | `$a = 3; $a++;` | proved | proved | deferred | [Mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement) |
| [STMT-POSTDEC-001](#stmt-postdec-001) | agreed | `$a = 3; $a--;` | proved | proved | deferred | [Mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement) |
| [STR-INTERP-001](#str-interp-001) | agreed | `$name = "Alex"; $a = "hello $name";` | proved | proved | deferred | [Interpolation contract and proof](../s2s_scalar_operators.md#string-interpolation) |
| [STR-INTERP-002](#str-interp-002) | agreed | `$count = 3; $a = "sum {$count}";` | proved | proved | deferred | [Interpolation contract and proof](../s2s_scalar_operators.md#string-interpolation) |
| [STR-INDEX-001](#str-index-001) | agreed | `$s = "abc"; $a = $s[0];` and `$s[];` | proved | deferred | deferred | [General bracket syntax and shared deferred-overload diagnostic](../../tests/indexing.php); no string overload or lowering selected |
| [NOTE-013](#note-013) | agreed | Recursive expressions and grouping | proved | proved | deferred | Existing bounded implementation; [proof](../../tests/grouping.php) and [contracts](../s2s_scalar_operators.md) |
| [NOTE-018](#note-018) | agreed | Expression statements and updates | proved | proved | deferred | Existing bounded implementation; [proof](../../tests/compound_assignments.php) and [contracts](../s2s_scalar_operators.md) |
| [NOTE-027](#note-027) | agreed | Preparation and emission responsibilities | proved | proved | deferred | Existing bounded implementation; [proof](../../tests/operators.php) and [contracts](../s2s_scalar_operators.md) |
| [NOTE-043](#note-043) | agreed | Recursive expressions and chained assignment | proved | proved | deferred | Existing bounded implementation; [proof](../../tests/logical.php) and [contracts](../s2s_scalar_operators.md) |
| [NOTE-044](#note-044) | agreed | Shared operator families | proved | proved | deferred | Existing bounded implementation; [proof](../../tests/operators.php) and [contracts](../s2s_scalar_operators.md) |
| [NOTE-054](#note-054) | agreed | Assignment snapshots and lowering | proved | proved | deferred | Existing bounded implementation; [proof](../../tests/logical.php) and [contracts](../s2s_scalar_operators.md) |
## EXPR-PAREN-001

**v0.2 decision / target C++:** Agreed 2026-10-01: normalize grouping parentheses
away during parsing. Return the existing inner expression node; its position in the
tree preserves grouping. No grouping AST node or prepared-fact structure is needed.
The existing preparation and C++ paths consume that tree unchanged. For:

```php
$b = 2;
$a = ($b + 1);
return $a;
```

the assignment remains:

```cpp
auto local_a = (local_b + static_cast<scpp::int_t<>>(1LL));
```

The result is `3`. Nested grouping preserves type, storage identity/reference
eligibility, and evaluation count. Grouping does not make a value addressable or
admit otherwise rejected operator operands.

**Grammar and ownership:** `Parser_Run` recognizes `(type) operand` using read-only
lookahead over the existing `name` / `name<type,...>` syntax, including nested
applications and type-use modifiers. A complete parenthesized type form selects a
cast only when the next token starts a currently supported primary operand: a name,
variable, scalar literal, `(` or `[`. Otherwise `(` parses an expression followed
by `)`. Lookahead neither resolves names nor allocates/collects speculative syntax.
`(PHP_INT_MAX)` is therefore grouping, `(Unknown) + 1` groups an unresolved constant,
and `(Unknown)$x` is a cast whose target is resolved during preparation.

Cast syntax takes priority for `(Name)(...)` and `(Name)[...]`; indirect invocation
and indexed bare constants are not activated by this slice. Grouped variable bases
such as `($items)[0]` retain the existing index syntax, whose semantic support remains
separate. Adding unary operators, indirect calls or broader type syntax requires
reviewing ambiguous forms and keeping lookahead aligned with the type grammar.

The inner node retains its own half-open source span. Enclosing binary, cast and
postfix-access nodes use the consumed source extent, including parentheses where
applicable. Parentheses do not acquire a retained identity. Collection and token
cleanup still visit the same concrete syntax nodes and occurrences once.

**Legacy review:** the old generator recursively renders binary AST children and
parenthesizes their output (`Generator::renderExpr`, `BINARY_OP`), with scalar casts
handled separately. Its `EXPR-PAREN-001` imported wording asks to preserve parentheses
to maintain evaluation order. The v0.2 rule preserves grouping, not redundant source
punctuation; parentheses alone do not establish operand evaluation order. Existing
operator ordering restrictions continue to apply. No new runtime helper, include,
temporary, output unit or compilation-cost benchmark is needed.

**Proof:** `tests/grouping.php` checks both addition tree shapes (their equal numeric
results alone would not distinguish them), cast/group disambiguation, one collected
constant occurrence, nested type syntax, source spans, malformed/out-of-slice forms,
retained-body token cleanup and fresh/incremental equivalence after regrouping.
The ten `group_*` fixtures in `tests/s2s.php` pass PHP-host preparation/generation and
Clang C++20 execution, including grouped casts, constants, booleans, reference and
field arguments, and a call evaluated once. The compiler itself was not rebuilt
natively for this slice; earlier native checkpoints remain revision-specific.

Non-goals: new operators/precedence levels, standalone expression statements,
assignments inside groups, wider index support, general effect analysis and LLVM.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:63](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b + 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b + static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** inner expression valid

**Normalized pattern:** `<var> = (<expr>)`

**General rule:** Parentheses must be preserved in the emitted code to maintain evaluation order. Inner expressions must follow all normalization rules.

**Diagnostics:** Error if inner expression violates normalization rules.

**Notes:** Structural rule.


## EXPR-ARITH-001

**v0.2 decision / target C++:** This entry currently selects only canonical
`int + int -> int`; the imported generalized arithmetic prose does not activate
subtraction, multiplication, division, modulus or mixed numeric pairs. Preparation
selects the hard-coded language operation `integer_addition`, retains two ordered
identity operand conversions and publishes canonical `int` as the result. C++
lowering consumes that decision and preserves:

```cpp
auto local_a = (static_cast<scpp::int_t<>>(1LL) + static_cast<scpp::int_t<>>(2LL));
```

This initial canonical-only checkpoint is superseded by the 2026-10-10
same-signedness width contract above and ordered eager evaluation. Calls are
admitted through ordinary expression preparation; explicit casts do not imply
permission for otherwise unsupported operator combinations.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:54](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1 + 2;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1) + static_cast<int_t>(2);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr>`

**General rule:** Binary arithmetic operations (`+`, `-`, `*`, `/`, `%`) must be emitted as arithmetic on normalized runtime-typed operands. Any literal operand must first be converted with the appropriate `static_cast<..._t>(...)`.

**Diagnostics:** Error if operand is non-numeric or a literal is emitted without normalization.

**Notes:** Replaces the separate ADD/SUB/MUL/DIV/MOD rows as the generalized rule.


## EXPR-SUB-001

**v0.2 decision / target C++:** Agreed 2026-10-01: canonical `int - int -> int`,
using the existing runtime subtraction operation. The example produces `-1`:

```cpp
auto local_a = (static_cast<scpp::int_t<>>(1LL) - static_cast<scpp::int_t<>>(2LL));
```

Addition and subtraction share one left-associative parser level: `10 - 3 - 2`
produces `5`, `10 - (3 - 2)` produces `9`, and `10 - 3 + 2` produces `9`.
Tokenizer recognition preserves `->` and signs inside floating exponents. Binary
minus does not enable unary negation, decrement or compound assignment.

**Ownership and retained facts:** reuse `binary_expression_node`,
`prepared_binary_expression` and `operator_decision` without new structures or
fields. Add `operator_kind::subtraction` and `operator_operation::integer_subtraction`.
`Integer_Operators::decide_binary()` (generalized as the binary families grew) shares the exact canonical-int operand check,
two identity conversions in `operator_operand` context and canonical result type
between addition and subtraction. Diagnostics name the shared integer binary operation. `Operator_Preparation` owns source normalization and the existing
order-independence restriction; C++ consumes the selected operation and conversions.

**Legacy/runtime review:** the legacy `Generator::renderExpr` lowers `AstKind::MINUS`
recursively as `(left - right)` on normalized operands. This slice reuses that target
form with explicitly prepared type permission. The runtime's generated `sub` helper
subtracts native representations; it does not check signed overflow. No wrapping,
checked overflow or PHP overflow-to-float guarantee is introduced. Proofs exercise
representable values, including the signed 64-bit minimum and maximum. Mixed widths,
floats and other numeric compatibility remain separate decisions.

**Proof and cost:** `tests/operators.php` checks selected decisions/conversions,
mixed additive AST shape, grouped subtraction, unsupported operands and syntax,
equal-length operator edits, token cleanup, signature retention and fresh/incremental
agreement. `tests/tokenizer.php` checks minus/arrow/exponent token boundaries.
The nine `sub_*` source fixtures in `tests/s2s.php` cover grouping, associativity,
reassignment, explicit cast operands, negative results and representable limits;
three additional generated-C++ probes assert exact signed values and canonical
result type independently of process exit codes. These twelve programs pass Clang
C++20 execution after PHP-host generation. Native compiler execution was not rerun.
The existing operator header and expression lowering are reused; no additional
temporary, runtime helper or output artifact policy is introduced.

Non-goals: other operator families, unary minus, `--`, `-=`, mixed-width promotion,
floating operands, broader effect analysis, arithmetic overflow redesign and LLVM.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:55](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1 - 2;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1) - static_cast<int_t>(2);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-ARITH-001

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr>`

**General rule:** Same generalized rule as EXPR-ARITH-001.

**Notes:** Redundant seed row.


## EXPR-MUL-001

**v0.2 decision / target C++:** Implemented 2026-10-01: canonical `int * int -> int`, extending the current
integer arithmetic policy. Multiplication binds more tightly than addition and
subtraction and associates left-to-right. Grouping overrides that precedence.
The example produces `6`:

```cpp
auto local_a = (static_cast<scpp::int_t<>>(2LL) * static_cast<scpp::int_t<>>(3LL));
```

Reuse the existing binary AST and prepared decision records; add only the
`multiplication` source kind and `integer_multiplication` selected operation.
The parser owns precedence through a shared precedence-climbing path. Integer
preparation shares exact operand checks, identity conversions and result typing
across the supported arithmetic operations; C++ renders the selected operation.
The shared policy is now named `Integer_Operators::decide_binary`; diagnostics
likewise name the shared integer binary operation.

Legacy `Generator::renderExpr` emits `AstKind::MUL` as parenthesized recursive
runtime arithmetic. The runtime `mul` helper multiplies native representations
without signed-overflow checks. Preserve that behavior, with representable-value
proofs and no new wrapping or checked-overflow guarantee. Reuse the existing
operator header without additional helpers or temporaries.

**Proof:** `tests/operators.php` checks mixed-precedence ASTs, left associativity,
grouping, canonical result/operand conversions, rejected operand families and
effectful expressions, and equal-length operator edits through token cleanup and
fresh/incremental agreement. Existing grouping tests continue to check source spans
and cast disambiguation. `tests/s2s.php` contains eleven `mul_*` source fixtures and
two additional exact-value/type probes, all passing PHP-host generation and Clang
C++20 execution. They cover grouping, mixed precedence, casts, reassignment, chained
assignment, zero, a negative product and a large representable signed product.
Native execution of the compiler itself was not rerun.

Non-goals: division, modulus, exponentiation, compound assignment, unary operators,
mixed-width or floating operands, broader effect analysis, runtime overflow changes
and LLVM. The later ordered-eager amendment above supersedes this checkpoint's
effectful-operand restrictions; compound RHS restrictions remain separate.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:56](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 2 * 3;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(2) * static_cast<int_t>(3);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-ARITH-001

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr>`

**General rule:** Same generalized rule as EXPR-ARITH-001.

**Notes:** Redundant seed row.


## EXPR-DIV-001


**Current scope — 2026-10-19 (CS044).** Integer division truncation, negative operands, grouping and zero guard have native evidence; current scalar-error-code proof preserves division_by_zero for float and mixed int/float order as well. Integer division remains distinct from PHP division typing. Minimum divided by minus one is outside promised checked arithmetic.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [compact_layout_types.md](../../../../specs/compact_layout_types.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [spec.md](../../../../runtime/specs/spec.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS044`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:57](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 4 / 2;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(4) / static_cast<int_t>(2);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported-with-known-incompatibility

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr>`

**General rule:** Same generalized rule as EXPR-ARITH-001.

**Diagnostics:** Division has a known PHP/C++ semantic mismatch; see incompatibilities.

**Notes:** Keep tracked in `incompatibilities.md`.


## EXPR-MOD-001


**Current scope — 2026-10-19 (CS045).** Remainder sign, precedence, grouping and zero guard have native evidence. Modulo_by_zero remains distinct from division_by_zero. Broad numeric-compatible prose does not admit float or lifted operands automatically.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [compact_layout_types.md](../../../../specs/compact_layout_types.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [spec.md](../../../../runtime/specs/spec.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS045`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:58](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 5 % 2;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(5) % static_cast<int_t>(2);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-ARITH-001

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr>`

**General rule:** Same generalized rule as EXPR-ARITH-001.

**Notes:** Redundant seed row.


## EXPR-POW-001

**v0.2 decision / target C++:** Agreed canonical `int ** int -> int`, lowered to
shared `scpp::pow`. Right-associative, above unary precedence; zero exponent returns
1, negative exponent and overflow throw runtime errors. Floats, other widths and
`**=` remain deferred. The existing binary AST and prepared operator decisions are
reused. See the [contract and proof](../s2s_scalar_operators.md#integer-exponentiation).
The legacy numeric-compatible wording below does not extend this exact-type slice.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:59](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 2 ** 3;
```

**Existing C++ lowering / result**

```cpp
auto a = scpp::pow(static_cast<int_t>(2), static_cast<int_t>(3));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> ** <expr>`

**General rule:** Exponentiation must be mapped to `scpp::pow(...)`. All operands must be normalized to runtime types before invocation.

**Diagnostics:** Error if operands are non-numeric or literals are not normalized.

**Notes:** `scpp::pow` must be created in runtime.


## EXPR-NEG-001

**v0.2 decision / target C++:** Implemented 2026-10-02 using the agreed unary
node and the shared operator decision with one operand conversion. See the
[unary operator contract and proof](../s2s_scalar_operators.md#unary-operators).
The strict working example is initialized in the progress table. C++ uses the
selected runtime unary operator; native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:60](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = -$b;
```

**Existing C++ lowering / result**

```cpp
auto a = -b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand numeric-compatible

**Normalized pattern:** `<var> = -<expr>`

**General rule:** Unary numeric negation is emitted directly as `-<expr>`. No extra cast is needed unless the operand itself requires normalization before use.

**Diagnostics:** Error if operand is non-numeric.


## EXPR-POS-001

**v0.2 decision / target C++:** Implemented 2026-10-02 using the agreed unary
node and the shared operator decision with one operand conversion. See the
[unary operator contract and proof](../s2s_scalar_operators.md#unary-operators).
The strict working example is initialized in the progress table. C++ uses the
selected runtime unary operator; native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:61](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = +$b;
```

**Existing C++ lowering / result**

```cpp
auto a = +b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand numeric-compatible

**Normalized pattern:** `<var> = +<expr>`

**General rule:** Unary plus is emitted directly as `+<expr>` and acts as a no-op on already normalized numeric expressions.

**Diagnostics:** Error if operand is non-numeric.


## EXPR-BNOT-001

**v0.2 decision / target C++:** Implemented 2026-10-02 using the agreed unary
node and the shared operator decision with one operand conversion. See the
[unary operator contract and proof](../s2s_scalar_operators.md#unary-operators).
The strict working example is initialized in the progress table. C++ uses the
selected runtime unary operator; native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:62](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ~$b;
```

**Existing C++ lowering / result**

```cpp
auto a = ~b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand integer-bitwise-compatible

**Normalized pattern:** `<var> = ~<expr>`

**General rule:** Unary bitwise NOT is emitted directly as `~<expr>`. Grouping must be preserved when it participates in a larger expression, for example `(~b) * 2`.

**Diagnostics:** Error if operand is not compatible with the current integer-bitwise runtime surface.

**Notes:** Matches the current generator/runtime path.


## EXPR-NESTED-001


**Current scope — 2026-10-19 (CS050).** Nested arithmetic source runs through recursive operator preparation; independent grouping tree assertions and eager mixed-precedence/native sequencing prove composition rather than numeric coincidence alone. No per-combination rule or literal-spelling obligation follows.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [compact_layout_types.md](../../../../specs/compact_layout_types.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [spec.md](../../../../runtime/specs/spec.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS050`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:64](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b + 1) * 2;
```

**Existing C++ lowering / result**

```cpp
auto a = (b + static_cast<int_t>(1)) * static_cast<int_t>(2);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-PAREN-001-and-EXPR-ARITH-001

**Preconditions:** recursive expression validity

**Normalized pattern:** `<var> = (<expr>) <arith-op> <expr>`

**General rule:** Apply recursive normalization bottom-up on the AST.

**Diagnostics:** Error if any literal at any depth is not normalized.

**Notes:** Illustrates recursive normalization.


## EXPR-CHAIN-001


**Current scope — 2026-10-19 (CS051).** Arithmetic chains reuse recursive preparation and preserve parsed left associativity. Native catalog result is supplemented by subtraction/multiplication associativity and grouping proofs; imported associative wording does not authorize algebraic reassociation.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [compact_layout_types.md](../../../../specs/compact_layout_types.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [spec.md](../../../../runtime/specs/spec.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS051`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:65](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b + 1 + $c;
```

**Existing C++ lowering / result**

```cpp
auto a = b + static_cast<int_t>(1) + c;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-ARITH-001

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr> ...`

**General rule:** Associative arithmetic chains stay under the same generalized arithmetic rule.

**Diagnostics:** Error if any literal in the chain is not normalized.

**Notes:** No per-length rule explosion.


## EXPR-CONCAT-001


**Current scope — 2026-10-19 (CS052).** Text concatenation owns a string result and prepared text conversion, not general explicit casting. Native string-content observers and mixed-state observers cover literals, both operand orders, null/bool/int/float/string and table behavior. The later [V-L11 / V-D14 proof](../planning/carrier_text.md) admits ready plain widths and canonical scalar carriers with explicit sentinel/error rules; uint64 and wider domains remain separate.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS052`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:67](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "a" . "b";
```

**Existing C++ lowering / result**

```cpp
auto a = string_t("a") + string_t("b");
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operands are valid string-compatible expressions

**Normalized pattern:** `<var> = <expr> . <expr>`

**General rule:** PHP string concatenation `.` maps to C++ `+` on `string_t` operands. Any non-string operand that is supported in string context must be made explicit by the generator using `cast<string_t>(...)`. Unsupported operand types must fail.

**Diagnostics:** Error if raw C string would cause pointer arithmetic / wrong overload resolution.


## EXPR-CONCAT-002


**Current scope — 2026-10-19 (CS053).** Variable-left concatenation follows prepared text conversion and owned string results, including exact content observers. Imported `cast<string_t>` is illustrative and cannot substitute checked extraction for text conversion. The later [V-L11 / V-D14 proof](../planning/carrier_text.md) resolves ready plain widths and canonical scalar carrier text; uint64 and wider domains remain separate.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS053`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:68](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b . "x";
```

**Existing C++ lowering / result**

```cpp
auto a = cast<string_t>(b) + string_t("x");
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-CONCAT-001

**Preconditions:** same as EXPR-CONCAT-001

**Normalized pattern:** `<var> = <expr> . <expr>`

**General rule:** Same generalized rule as EXPR-CONCAT-001.


## EXPR-CONCAT-003


**Current scope — 2026-10-19 (CS054).** Variable-right concatenation is observed by exact-content native tests and ordered eager text-call effects. No raw C-string pointer arithmetic is implied; non-string operands follow text context, not universal castability. The later [V-L11 / V-D14 proof](../planning/carrier_text.md) resolves ready plain widths and canonical scalar carriers, including both operand orders; wider domains remain separate.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS054`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:69](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "x" . $b;
```

**Existing C++ lowering / result**

```cpp
auto a = string_t("x") + cast<string_t>(b);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-CONCAT-001

**Preconditions:** same as EXPR-CONCAT-001

**Normalized pattern:** `<var> = <expr> . <expr>`

**General rule:** Same generalized rule as EXPR-CONCAT-001.

**Diagnostics:** Error if emitted as `"x" + b`, which would be wrong in C++.


## EXPR-COMP-001


**Current scope — 2026-10-19 (CS055).** Canonical integer equality and bool equality are observed; same-signedness width policy has a separate domain-pair ledger. V-L03 now verifies byte-exact ordinary string equality; carrier equality remains separate and imported loose-PHP prose does not broaden it. Exact float/float ordinary equality is now verified by V-L04; cross-type comparisons remain separate.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS055`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:71](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b == 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b == static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported-with-known-incompatibility

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Comparison operations (`==`, `!=`, `<`, `<=`, `>`, `>=`) must operate on normalized operands. Any literal operand must be converted using the appropriate runtime cast.

**Diagnostics:** Error if operands are incompatible or a literal is not normalized.

**Notes:** PHP loose comparison mismatch still open.


## EXPR-NEQ-001


**Current scope — 2026-10-19 (CS056).** Inequality is independently observed, with bool equality/inequality truth tables in the scalar suite and numeric width coverage separately retained. V-L03 now verifies byte-exact ordinary string inequality; carrier admission remains separate. V-L04 now admits exact float/float ordinary inequality; cross-type comparisons remain separate.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS056`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:72](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b != 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b != static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-COMP-001-with-known-incompatibility

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Same generalized rule as EXPR-COMP-001.

**Diagnostics:** Same loose-comparison mismatch concern.


## EXPR-SEQ-001


**Current scope — 2026-10-19 (CS057).** Strict identity returns the semantic bool type; string_identity.php checks noncoercing prepared facts plus complete byte contents/NUL, case and Unicode normalization distinction, bool truth table and eager snapshots. Imported valid-expressions/PHP semantics is broader than the proved slice; float/carrier gaps remain.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS057`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:73](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b === 1);
```

**Existing C++ lowering / result**

```cpp
auto a = php::identical(b, static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** valid expressions

**Normalized pattern:** `<var> = <expr> === <expr>`

**General rule:** Strict comparison (`===`) must be mapped to runtime helpers to preserve PHP semantics. The helper returns `bool_t`, not native `bool`.

**Diagnostics:** Error if generator emits `===` as native C++ `==`.

**Notes:** Part of strict-comparison family.


## EXPR-SNEQ-001


**Current scope — 2026-10-19 (CS058).** Strict nonidentity is separately asserted as negation of same-type value identity, with byte-exact string and bool cases and snapshot sequencing. Imported helper spelling is not a requirement for identical lowering. Float/carrier admission stays explicitly missing.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS058`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:74](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b !== 1);
```

**Existing C++ lowering / result**

```cpp
auto a = php::not_identical(b, static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** valid expressions

**Normalized pattern:** `<var> = <expr> !== <expr>`

**General rule:** Strict inequality (`!==`) must be mapped to runtime helpers to preserve PHP semantics. The helper returns `bool_t`, not native `bool`.

**Diagnostics:** Error if generator emits `!==` as native C++ `!=`.

**Notes:** Part of strict-comparison family.


## EXPR-LT-001


**Current scope — 2026-10-19 (CS059).** Integer less-than observes order and signed extremes without subtraction-based comparison. Same-signedness pair proof is separate; V-L03 now verifies lexicographic string ordering; the bounded guarded-int ordering slice above now passes; wider lifting remains separate. V-L04 now admits exact float/float ordering, without cross-type promotion.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS059`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:75](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b < 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b < static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-COMP-001

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Same generalized rule as EXPR-COMP-001.


## EXPR-LTE-001


**Current scope — 2026-10-19 (CS060).** Less-or-equal has independent canonical native observation and full same-signedness domain-pair proof, including equality boundaries. V-L03 now verifies lexicographic string operands; the bounded guarded-int slice above now passes; other lifted domains remain missing. V-L04 now admits exact float/float ordering, without cross-type promotion.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS060`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:76](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b <= 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b <= static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-COMP-001

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Same generalized rule as EXPR-COMP-001.


## EXPR-GT-001


**Current scope — 2026-10-19 (CS061).** Greater-than has independent native observation and domain-pair coverage. No universal PHP comparison/coercion promise follows; V-L03 now verifies lexicographic string ordering; bounded guarded-int ordering now passes; wider lifting remains missing. V-L04 now admits exact float/float ordering, without cross-type promotion.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS061`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:77](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b > 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b > static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-COMP-001

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Same generalized rule as EXPR-COMP-001.


## EXPR-GTE-001


**Current scope — 2026-10-19 (CS062).** Greater-or-equal has independent native observation with domain-pair and equality-boundary coverage. V-L03 now verifies lexicographic string ordering; bounded guarded-int ordering now passes; wider lifting remains missing under its configured contract. V-L04 now admits exact float/float ordering, without cross-type promotion.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS062`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:78](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b >= 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b >= static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-COMP-001

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Same generalized rule as EXPR-COMP-001.


## EXPR-SPACESHIP-001


**Current scope — 2026-10-19 (CS063).** Spaceship returns canonical signed -1/0/1, with an exact negative-value observer, equality, greater and signed-extreme cases. Integer width suite verifies unsigned extremes. Imported scpp::cmp helper creation and exact lowering are superseded by prepared ordered comparisons; V-L03 additionally verifies exact string/string byte ordering with the same -1/0/1 result contract; other comparable families remain separate.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS063`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:79](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b <=> $c);
```

**Existing C++ lowering / result**

```cpp
auto a = scpp::cmp(b, c);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operands must be comparable under runtime semantics

**Normalized pattern:** `<var> = <expr> <=> <expr>`

**General rule:** PHP spaceship must be mapped to a runtime helper returning `int_t` with values `-1`, `0`, `1`.

**Diagnostics:** Error if emitted as native C++ `<=>`, which returns a comparison category type, not PHP-style `int_t`.

**Notes:** `scpp::cmp` must be created in runtime.


## EXPR-LOGIC-001


**Current scope — 2026-10-19 (CS064).** Canonical bool AND/OR truth tables, precedence, grouping, skipped throwing RHS and reached throwing RHS are independently executed. Compile-time validation still inspects skipped operands. Canonical int/float logical admission, including mixed numeric pairs and word aliases, is now verified by V-L05. Lifted and wider-domain admission remain separate gaps, not a universal intentional exclusion.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS064`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:81](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = true && false;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<bool_t>(true) && static_cast<bool_t>(false);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operands boolean-compatible

**Normalized pattern:** `<var> = <expr> <logic-op> <expr>`

**General rule:** Logical operators (`&&`, `||`) are emitted directly. Literal operands must be normalized if they are raw literals; no extra casting is required for already boolean-compatible expressions.

**Diagnostics:** Error if operands are non-boolean.

**Notes:** Covers both `&&` and `||`.


## EXPR-OR-001


**Current scope — 2026-10-19 (CS065).** OR truth table and skipped/reached failure paths prove laziness through native bool bridging. V-L05 now verifies canonical numeric OR, including int/float pairs and its word alias. Lifted and wider-domain admission remain separate.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS065`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:82](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = true || false;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<bool_t>(true) || static_cast<bool_t>(false);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-LOGIC-001

**Preconditions:** operands boolean-compatible

**Normalized pattern:** `<var> = <expr> <logic-op> <expr>`

**General rule:** Same generalized rule as EXPR-LOGIC-001.

**Notes:** Redundant seed row.


## EXPR-NOT-001

**v0.2 decision / target C++:** Implemented 2026-10-02 using the agreed unary
node and the shared operator decision with one operand conversion. See the
[unary operator contract and proof](../s2s_scalar_operators.md#unary-operators).
The strict working example is initialized in the progress table. C++ uses the
selected runtime unary operator; native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:83](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = !$b;
```

**Existing C++ lowering / result**

```cpp
auto a = !b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand must be boolean-compatible

**Normalized pattern:** `<var> = !<expr>`

**General rule:** Logical negation is emitted directly as `!<expr>`. No extra cast is required if the operand is already boolean-compatible.

**Diagnostics:** Error if operand is non-boolean.


## EXPR-ANDWORD-001

**v0.2 decision / target C++:** Agreed canonical-bool operation, below assignment
precedence. The imported `$a`/`$b`/`$c` example requires established locals. Assignment
returns the converted stored-value snapshot. Use native bool short circuiting, then wrap the result.
See the [contract and proof](../s2s_scalar_operators.md#assignment-expressions-and-keyword-logic).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:84](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b and $c;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Expression

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `<var> = <expr> and <expr>`

**General rule:** The operators `and`, `or`, and `xor` are not supported.

**Diagnostics:** Emit an error if any of these operators are encountered.

**Notes:** Rejected because operator precedence in PHP is badly designed for these operators.


## EXPR-ORWORD-001

**v0.2 decision / target C++:** Agreed canonical-bool operation, below assignment
precedence. The imported `$a`/`$b`/`$c` example requires established locals. Assignment
returns the converted stored-value snapshot. Use native bool short circuiting, then wrap the result.
See the [contract and proof](../s2s_scalar_operators.md#assignment-expressions-and-keyword-logic).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:85](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b or $c;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Expression

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `<var> = <expr> or <expr>`

**General rule:** The operators `and`, `or`, and `xor` are not supported.

**Diagnostics:** Emit an error if any of these operators are encountered.

**Notes:** Rejected because operator precedence in PHP is badly designed for these operators.


## EXPR-XORWORD-001

**v0.2 decision / target C++:** Agreed canonical-bool operation, below assignment
precedence. The imported `$a`/`$b`/`$c` example requires established locals. Assignment
returns the converted stored-value snapshot. Snapshot both bool operands left-to-right and compare with native `!=`.
See the [contract and proof](../s2s_scalar_operators.md#assignment-expressions-and-keyword-logic).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:86](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b xor $c;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Expression

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `<var> = <expr> xor <expr>`

**General rule:** The operators `and`, `or`, and `xor` are not supported.

**Diagnostics:** Emit an error if any of these operators are encountered.

**Notes:** Rejected because operator precedence in PHP is badly designed for these operators.


## EXPR-PREINC-001


**Current scope — 2026-10-19 (CS070).** Native prefix increment observation distinguishes returned old/new values and final storage. Mutation PHP assertions prove non-addressable snapshots, established writable targets, spans, cleanup and incremental operator edits. Statement forms discard only the result. No overflow checking is promised. Fields/indexes/eager operands have later owners; old blanket target exclusions are stale. V-L04 now admits canonical float mutations with the same value-snapshot rule. Lifted mutation remains missing support, with nullable runtime increment separately incomplete.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [string_interpolation.md](../../../../specs/string_interpolation.md), [array_semantics.md](../../../../specs/array_semantics.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS070`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals and parameters. The dedicated mutation node retains the target and
prefix/postfix syntax; preparation selects the operation and a value snapshot
result. See [mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement).
Statements discard that result. Fields, indexes and mutation operands of binary
expressions remain outside this slice. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:92](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ++$b;
```

**Existing C++ lowering / result**

```cpp
auto a = ++b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand must be a mutable numeric variable

**Normalized pattern:** `<var> = ++<expr>`

**General rule:** Pre-increment is emitted directly as `++<expr>`. The result of the increment expression is assigned.

**Diagnostics:** Error if operand is not mutable or non-numeric.


## EXPR-POSTINC-001


**Current scope — 2026-10-19 (CS071).** Native postfix increment observation distinguishes returned old/new values and final storage. Mutation PHP assertions prove non-addressable snapshots, established writable targets, spans, cleanup and incremental operator edits. Statement forms discard only the result. No overflow checking is promised. Fields/indexes/eager operands have later owners; old blanket target exclusions are stale. V-L04 now admits canonical float mutations with the same value-snapshot rule. Lifted mutation remains missing support, with nullable runtime increment separately incomplete.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [string_interpolation.md](../../../../specs/string_interpolation.md), [array_semantics.md](../../../../specs/array_semantics.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS071`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals and parameters. The dedicated mutation node retains the target and
prefix/postfix syntax; preparation selects the operation and a value snapshot
result. See [mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement).
Statements discard that result. Fields, indexes and mutation operands of binary
expressions remain outside this slice. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:93](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b++;
```

**Existing C++ lowering / result**

```cpp
auto a = b++;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand must be a mutable numeric variable

**Normalized pattern:** `<var> = <expr>++`

**General rule:** Post-increment is emitted directly as `<expr>++`. The original value is assigned, then the increment occurs.

**Diagnostics:** Error if operand is not mutable or non-numeric.


## EXPR-PREDEC-001


**Current scope — 2026-10-19 (CS072).** Native prefix decrement observation distinguishes returned old/new values and final storage. Mutation PHP assertions prove non-addressable snapshots, established writable targets, spans, cleanup and incremental operator edits. Statement forms discard only the result. No overflow checking is promised. Fields/indexes/eager operands have later owners; old blanket target exclusions are stale. V-L04 now admits canonical float mutations with the same value-snapshot rule. Lifted mutation remains missing support, with nullable runtime increment separately incomplete.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [string_interpolation.md](../../../../specs/string_interpolation.md), [array_semantics.md](../../../../specs/array_semantics.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS072`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals and parameters. The dedicated mutation node retains the target and
prefix/postfix syntax; preparation selects the operation and a value snapshot
result. See [mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement).
Statements discard that result. Fields, indexes and mutation operands of binary
expressions remain outside this slice. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:94](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = --$b;
```

**Existing C++ lowering / result**

```cpp
auto a = --b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand must be a mutable numeric variable

**Normalized pattern:** `<var> = --<expr>`

**General rule:** Pre-decrement is emitted directly as `--<expr>`. The value is decremented first, then assigned.

**Diagnostics:** Error if operand is not mutable or non-numeric.


## EXPR-POSTDEC-001


**Current scope — 2026-10-19 (CS073).** Native postfix decrement observation distinguishes returned old/new values and final storage. Mutation PHP assertions prove non-addressable snapshots, established writable targets, spans, cleanup and incremental operator edits. Statement forms discard only the result. No overflow checking is promised. Fields/indexes/eager operands have later owners; old blanket target exclusions are stale. V-L04 now admits canonical float mutations with the same value-snapshot rule. Lifted mutation remains missing support, with nullable runtime increment separately incomplete.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [string_interpolation.md](../../../../specs/string_interpolation.md), [array_semantics.md](../../../../specs/array_semantics.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS073`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals and parameters. The dedicated mutation node retains the target and
prefix/postfix syntax; preparation selects the operation and a value snapshot
result. See [mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement).
Statements discard that result. Fields, indexes and mutation operands of binary
expressions remain outside this slice. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:95](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b--;
```

**Existing C++ lowering / result**

```cpp
auto a = b--;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand must be a mutable numeric variable

**Normalized pattern:** `<var> = <expr>--`

**General rule:** Post-decrement is emitted directly as `<expr>--`. The original value is assigned, then the decrement occurs.

**Diagnostics:** Error if operand is not mutable or non-numeric.


## STMT-EXPR-001


**Current scope — 2026-10-19 (CS074).** Expression statements execute admitted expressions and discard their result; initialized-local and grouped source cases preserve storage. Preparation still rejects unresolved names. Imported direct-emission spelling is not a semantic requirement.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [string_interpolation.md](../../../../specs/string_interpolation.md), [array_semantics.md](../../../../specs/array_semantics.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS074`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-01 within the existing binary
expression/statement structures. The [scalar operator guide](../s2s_scalar_operators.md)
defines the bounded operand contract, lowering, legacy differences and proof.
The initialized strict working example is in the progress table; imported examples
below remain historical evidence. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:104](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a + 1;
```

**Existing C++ lowering / result**

```cpp
a + static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** all variables used must be declared in scope

**Normalized pattern:** `<expr>;`

**General rule:** Expression statements are emitted directly. All sub-expressions must follow normalization rules (e.g., literal casting).

**Diagnostics:** Error if variable is used before declaration or literal is not normalized.

**Notes:** No assignment â†’ no `auto`.


## STMT-ASSIGNOP-001

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals/parameters, or canonical `string` for `.=`. The dedicated compound
node retains target and RHS. Preparation reuses the binary decision and records
the write-back conversion; C++ binds the target once and returns an updated-value
snapshot. See [contracts, limits and proof](../s2s_scalar_operators.md#compound-assignment).
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:105](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a += 1;
```

**Existing C++ lowering / result**

```cpp
a += static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> <assign-op>= <expr>`

**General rule:** Compound assignment operators (`+=`, `-=`, `*=`, `/=`, etc.) are emitted directly. All operands must be normalized (e.g., literals cast).

**Diagnostics:** Error if variable is not previously declared or literal is not normalized.


## STMT-ASSIGNOP-002

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals/parameters, or canonical `string` for `.=`. The dedicated compound
node retains target and RHS. Preparation reuses the binary decision and records
the write-back conversion; C++ binds the target once and returns an updated-value
snapshot. See [contracts, limits and proof](../s2s_scalar_operators.md#compound-assignment).
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:106](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a -= 1;
```

**Existing C++ lowering / result**

```cpp
a -= static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> <assign-op>= <expr>`

**General rule:** Compound assignment operators (`+=`, `-=`, `*=`, `/=`, etc.) are emitted directly. All operands must be normalized (e.g., literals cast).

**Diagnostics:** Error if variable is not previously declared or literal is not normalized.


## STMT-ASSIGNOP-003

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals/parameters, or canonical `string` for `.=`. The dedicated compound
node retains target and RHS. Preparation reuses the binary decision and records
the write-back conversion; C++ binds the target once and returns an updated-value
snapshot. See [contracts, limits and proof](../s2s_scalar_operators.md#compound-assignment).
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:107](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a *= 2;
```

**Existing C++ lowering / result**

```cpp
a *= static_cast<int_t>(2);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> <assign-op>= <expr>`

**General rule:** Compound assignment operators (`+=`, `-=`, `*=`, `/=`, etc.) are emitted directly. All operands must be normalized (e.g., literals cast).

**Diagnostics:** Error if variable is not previously declared or literal is not normalized.


## STMT-ASSIGNOP-004

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals/parameters, or canonical `string` for `.=`. The dedicated compound
node retains target and RHS. Preparation reuses the binary decision and records
the write-back conversion; C++ binds the target once and returns an updated-value
snapshot. See [contracts, limits and proof](../s2s_scalar_operators.md#compound-assignment).
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:108](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a /= 2;
```

**Existing C++ lowering / result**

```cpp
a /= static_cast<int_t>(2);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported-with-known-incompatibility

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> <assign-op>= <expr>`

**General rule:** Compound assignment operators (`+=`, `-=`, `*=`, `/=`, etc.) are emitted directly. All operands must be normalized.

**Diagnostics:** Error if variable is not previously declared or literal is not normalized.

**Notes:** Division keeps the same semantic mismatch as `/`.


## STMT-ASSIGNOP-005

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals/parameters, or canonical `string` for `.=`. The dedicated compound
node retains target and RHS. Preparation reuses the binary decision and records
the write-back conversion; C++ binds the target once and returns an updated-value
snapshot. See [contracts, limits and proof](../s2s_scalar_operators.md#compound-assignment).
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:109](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a .= "x";
```

**Existing C++ lowering / result**

```cpp
a += string_t("x");
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope; target must be string-compatible

**Normalized pattern:** `<var> .= <expr>`

**General rule:** PHP string-concatenation assignment `.=` maps to `+=` on `string_t`-compatible operands. The right-hand side must go through the same explicit string-cast path as `.`/interpolation, so non-string operands are converted through the string operand helper and string literals normalize as `string_t("...")`.

**Diagnostics:** Error if variable is not previously declared, target is not string-compatible, or string literal is not normalized.


## STMT-ASSIGNOP-006

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals/parameters, or canonical `string` for `.=`. The dedicated compound
node retains target and RHS. Preparation reuses the binary decision and records
the write-back conversion; C++ binds the target once and returns an updated-value
snapshot. See [contracts, limits and proof](../s2s_scalar_operators.md#compound-assignment).
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:110](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $a += 1;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); a += static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable declared by first statement; RHS type-compatible

**Normalized pattern:** `<var> = <expr>; <var> += <expr>;`

**General rule:** Compound assignments follow normal reassignment rules. The left-hand side must already be declared, and the right-hand side must be fully normalized.

**Diagnostics:** Error if variable not declared or literal not normalized.


## STMT-ASSIGNOP-007

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals/parameters, or canonical `string` for `.=`. The dedicated compound
node retains target and RHS. Preparation reuses the binary decision and records
the write-back conversion; C++ binds the target once and returns an updated-value
snapshot. See [contracts, limits and proof](../s2s_scalar_operators.md#compound-assignment).
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:111](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a %= 2;
```

**Existing C++ lowering / result**

```cpp
a %= static_cast<int_t>(2);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> %= <expr>`

**General rule:** C++-native numeric compound assignments that exist in the target language (`%=`, `&=`, `|=`, `^=`, `<<=`, `>>=`) must be emitted directly after operand normalization.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-ASSIGNOP-008

**v0.2 decision / target C++:** Implemented 2026-10-02 for canonical `int`
locals/parameters, reusing the existing binary and compound-assignment decisions.
See [bitwise/shift semantics and proof](../s2s_scalar_operators.md#bitwise-and-shift-operators).
Shifts preserve the runtime C++20 contract; invalid counts remain undefined behavior,
with [checked-count debt](../planning/operators.md#debt-checked-shift-counts) recorded.
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:112](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a &= 3;
```

**Existing C++ lowering / result**

```cpp
a &= static_cast<int_t>(3);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> &= <expr>`

**General rule:** Same generalized direct-emission rule as STMT-ASSIGNOP-007.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-ASSIGNOP-009

**v0.2 decision / target C++:** Implemented 2026-10-02 for canonical `int`
locals/parameters, reusing the existing binary and compound-assignment decisions.
See [bitwise/shift semantics and proof](../s2s_scalar_operators.md#bitwise-and-shift-operators).
Shifts preserve the runtime C++20 contract; invalid counts remain undefined behavior,
with [checked-count debt](../planning/operators.md#debt-checked-shift-counts) recorded.
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:113](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a |= 3;
```

**Existing C++ lowering / result**

```cpp
a |= static_cast<int_t>(3);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> |= <expr>`

**General rule:** Same generalized direct-emission rule as STMT-ASSIGNOP-007.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-ASSIGNOP-010

**v0.2 decision / target C++:** Implemented 2026-10-02 for canonical `int`
locals/parameters, reusing the existing binary and compound-assignment decisions.
See [bitwise/shift semantics and proof](../s2s_scalar_operators.md#bitwise-and-shift-operators).
Shifts preserve the runtime C++20 contract; invalid counts remain undefined behavior,
with [checked-count debt](../planning/operators.md#debt-checked-shift-counts) recorded.
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:114](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a ^= 3;
```

**Existing C++ lowering / result**

```cpp
a ^= static_cast<int_t>(3);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> ^= <expr>`

**General rule:** Same generalized direct-emission rule as STMT-ASSIGNOP-007.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-ASSIGNOP-011

**v0.2 decision / target C++:** Implemented 2026-10-02 for canonical `int`
locals/parameters, reusing the existing binary and compound-assignment decisions.
See [bitwise/shift semantics and proof](../s2s_scalar_operators.md#bitwise-and-shift-operators).
Shifts preserve the runtime C++20 contract; invalid counts remain undefined behavior,
with [checked-count debt](../planning/operators.md#debt-checked-shift-counts) recorded.
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:115](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a <<= 1;
```

**Existing C++ lowering / result**

```cpp
a <<= static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> <<= <expr>`

**General rule:** Same generalized direct-emission rule as STMT-ASSIGNOP-007.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-ASSIGNOP-012

**v0.2 decision / target C++:** Implemented 2026-10-02 for canonical `int`
locals/parameters, reusing the existing binary and compound-assignment decisions.
See [bitwise/shift semantics and proof](../s2s_scalar_operators.md#bitwise-and-shift-operators).
Shifts preserve the runtime C++20 contract; invalid counts remain undefined behavior,
with [checked-count debt](../planning/operators.md#debt-checked-shift-counts) recorded.
Native compiler execution was not rerun.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:116](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a >>= 1;
```

**Existing C++ lowering / result**

```cpp
a >>= static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> >>= <expr>`

**General rule:** Same generalized direct-emission rule as STMT-ASSIGNOP-007.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-PREINC-001


**Current scope — 2026-10-19 (CS087).** Native statement prefix increment observation distinguishes returned old/new values and final storage. Mutation PHP assertions prove non-addressable snapshots, established writable targets, spans, cleanup and incremental operator edits. Statement forms discard only the result. No overflow checking is promised. Fields/indexes/eager operands have later owners; old blanket target exclusions are stale. V-L04 now admits canonical float mutations with the same value-snapshot rule. Lifted mutation remains missing support, with nullable runtime increment separately incomplete.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [string_interpolation.md](../../../../specs/string_interpolation.md), [array_semantics.md](../../../../specs/array_semantics.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS087`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals and parameters. The dedicated mutation node retains the target and
prefix/postfix syntax; preparation selects the operation and a value snapshot
result. See [mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement).
Statements discard that result. Fields, indexes and mutation operands of binary
expressions remain outside this slice. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:117](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
++$a;
```

**Existing C++ lowering / result**

```cpp
++a;
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope; mutable and numeric-compatible

**Normalized pattern:** `++<var>`

**General rule:** Statement-level pre-increment is emitted directly as `++<var>`.

**Diagnostics:** Error if variable is not previously declared, not mutable, or non-numeric.


## STMT-PREDEC-001


**Current scope — 2026-10-19 (CS088).** Native statement prefix decrement observation distinguishes returned old/new values and final storage. Mutation PHP assertions prove non-addressable snapshots, established writable targets, spans, cleanup and incremental operator edits. Statement forms discard only the result. No overflow checking is promised. Fields/indexes/eager operands have later owners; old blanket target exclusions are stale. V-L04 now admits canonical float mutations with the same value-snapshot rule. Lifted mutation remains missing support, with nullable runtime increment separately incomplete.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [string_interpolation.md](../../../../specs/string_interpolation.md), [array_semantics.md](../../../../specs/array_semantics.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS088`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals and parameters. The dedicated mutation node retains the target and
prefix/postfix syntax; preparation selects the operation and a value snapshot
result. See [mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement).
Statements discard that result. Fields, indexes and mutation operands of binary
expressions remain outside this slice. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:118](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
--$a;
```

**Existing C++ lowering / result**

```cpp
--a;
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope; mutable and numeric-compatible

**Normalized pattern:** `--<var>`

**General rule:** Statement-level pre-decrement is emitted directly as `--<var>`.

**Diagnostics:** Error if variable is not previously declared, not mutable, or non-numeric.


## STMT-POSTINC-001


**Current scope — 2026-10-19 (CS089).** Native statement postfix increment observation distinguishes returned old/new values and final storage. Mutation PHP assertions prove non-addressable snapshots, established writable targets, spans, cleanup and incremental operator edits. Statement forms discard only the result. No overflow checking is promised. Fields/indexes/eager operands have later owners; old blanket target exclusions are stale. V-L04 now admits canonical float mutations with the same value-snapshot rule. Lifted mutation remains missing support, with nullable runtime increment separately incomplete.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [string_interpolation.md](../../../../specs/string_interpolation.md), [array_semantics.md](../../../../specs/array_semantics.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS089`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals and parameters. The dedicated mutation node retains the target and
prefix/postfix syntax; preparation selects the operation and a value snapshot
result. See [mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement).
Statements discard that result. Fields, indexes and mutation operands of binary
expressions remain outside this slice. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:119](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a++;
```

**Existing C++ lowering / result**

```cpp
a++;
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope; mutable and numeric-compatible

**Normalized pattern:** `<var>++`

**General rule:** Statement-level post-increment is emitted directly as `<var>++`.

**Diagnostics:** Error if variable is not previously declared, not mutable, or non-numeric.


## STMT-POSTDEC-001


**Current scope — 2026-10-19 (CS090).** Native statement postfix decrement observation distinguishes returned old/new values and final storage. Mutation PHP assertions prove non-addressable snapshots, established writable targets, spans, cleanup and incremental operator edits. Statement forms discard only the result. No overflow checking is promised. Fields/indexes/eager operands have later owners; old blanket target exclusions are stale. V-L04 now admits canonical float mutations with the same value-snapshot rule. Lifted mutation remains missing support, with nullable runtime increment separately incomplete.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [string_interpolation.md](../../../../specs/string_interpolation.md), [array_semantics.md](../../../../specs/array_semantics.md), [config.json](../../../../runtime/specs/config.json). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS090`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Implemented 2026-10-02 for established canonical
`int` locals and parameters. The dedicated mutation node retains the target and
prefix/postfix syntax; preparation selects the operation and a value snapshot
result. See [mutation contracts and proof](../s2s_scalar_operators.md#increment-and-decrement).
Statements discard that result. Fields, indexes and mutation operands of binary
expressions remain outside this slice. Native compiler execution was not rerun.

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:120](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a--;
```

**Existing C++ lowering / result**

```cpp
a--;
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope; mutable and numeric-compatible

**Normalized pattern:** `<var>--`

**General rule:** Statement-level post-decrement is emitted directly as `<var>--`.

**Diagnostics:** Error if variable is not previously declared, not mutable, or non-numeric.


## STR-INTERP-001

**v0.2 decision / target C++:** Implemented scalar local/parameter interpolation.
The dedicated AST owns ordered literal/value parts with precise token-relative ranges;
preparation retains ordinary string conversions and C++ appends parts left-to-right
into a value snapshot. Complex insertions remain deferred. See the
[contract and proof](../s2s_scalar_operators.md#string-interpolation).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:301](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "hello $name";
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** String

**Rule kind:** rejection-or-generation

**Normalized pattern:** `<var> = <interpolated-string>`


## STR-INTERP-002

**v0.2 decision / target C++:** Implemented scalar local/parameter interpolation.
The dedicated AST owns ordered literal/value parts with precise token-relative ranges;
preparation retains ordinary string conversions and C++ appends parts left-to-right
into a value snapshot. Complex insertions remain deferred. See the
[contract and proof](../s2s_scalar_operators.md#string-interpolation).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:302](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "sum {$a}";
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** String

**Rule kind:** rejection-or-generation

**Normalized pattern:** `<var> = <braced-interpolated-string>`


## STR-INDEX-001


**Current scope — 2026-10-19 (CS093).** String operator[] has no settled byte/code-point/result/write contract and is explicitly reserved. Fresh indexing.php execution proves both arities and specific rejection for read/write/compound/mutation uses, plus syntax and incremental recovery. This is deliberate string deferral, not a successful index lowering claim. Generic container reads/writes now have later contracts, so the old chapter-wide overload/lowering-deferred statement is stale.

Contracts and owners: [index_operator.md](../../../../specs/index_operator.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS093`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Both `base[expression]` and `base[]` use one
operator model and preparation path. Empty brackets mean zero explicit arguments,
not compiler-defined append or write-only syntax. The type's eventual overload
contract owns meaning and result access properties. String overloads remain deferred
under the existing runtime contract; preparation reports that specific blocker.
Frontend proof covers syntax, arity and deliberate rejection, not successful overload
selection. C++ lowering and general overload resolution are deferred. See the
[normative bracket model](../../../../specs/index_operator.md) and
[focused proof](../../tests/indexing.php).

</details>

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:303](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $s[0];
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** String

**Rule kind:** rejection-or-generation

**Normalized pattern:** `<var> = <string-expr>[<expr>]`


## NOTE-013

**Source:** [generators/php/specs/rules.md:190](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Agreed 2026-10-02. Recursive preparation resolves supported
operators and their operands through the shared operator path. Parsing preserves precedence and
grouping in tree structure and discards grouping parentheses; C++ emission adds parentheses as
needed to preserve that structure. Literals retain their resolved types. Compositions reuse
these paths, subject to existing type and effect restrictions, without per-combination rules.

This reconciles existing behavior; no compiler structures or semantics are added.
See [focused proof](../../tests/grouping.php) and the [bounded scalar contracts and
generated-program evidence](../s2s_scalar_operators.md). Native compiler evidence is tracked
separately in the [portability review](../portability/conversion_review.md). Imported prose
follows unchanged as provenance.

> ## 5. Expressions
>
> ### Supported operator families
> - arithmetic: `+ - * / %`
> - comparison: `== != < <= > >=`
> - logical: `&& ||`
>
> ### Rules
> - recursive AST normalization
> - literals are normalized at leaves
> - parentheses are preserved
> - no combinatorial expansion
>
> ---

## NOTE-018


**Current scope — 2026-10-19 (CS095).** Expression discard and compound computation/conversion/store are covered. Old locals/parameters-only and fields/brackets-await prose is stale: field and indexed compounds have separate passing owners. String-only concatenation prose is bounded historical guidance, not proof of the entire normative operand policy.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [v0_2_preparation_diagnostics.md](../../../../specs/v0_2_preparation_diagnostics.md), [compact_layout_types.md](../../../../specs/compact_layout_types.md), [profile_semantics_v1.md](../../../../specs/operator_matrix/profile_semantics_v1.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS095`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**Source:** [generators/php/specs/rules.md:263](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Agreed 2026-10-02. Supported expressions may stand alone with
their result discarded. Compound assignments reuse ordinary operator computation followed by
destination conversion and a store. Concatenation and `.=` require string operands; other scalar
values need an explicit `(string)` cast. Mutations and compound assignments require existing
writable storage, currently supported locals/parameters, and never declare variables. Field and
bracket targets await their access contracts.

This reconciles existing behavior; no compiler structures or semantics are added.
See [focused proof](../../tests/compound_assignments.php) and the [bounded scalar contracts and
generated-program evidence](../s2s_scalar_operators.md). Native compiler evidence is tracked
separately in the [portability review](../portability/conversion_review.md). Imported prose
follows unchanged as provenance.

> ## 10. Statements
>
> - expression statements are allowed
> - compound assignments are allowed after normalization; `.=` must normalize the right-hand side through the same explicit string cast path as `.`
> - `++` and `--` require a declared variable
>
> ---

</details>

## NOTE-027

**Source:** [generators/php/specs/rules.md:459](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Agreed 2026-10-02. Shared preparation owns type/operator
resolution, conversions and semantic rejection. C++ emission consumes those facts and preserves
grouping, single evaluation and required sequencing. Runtime helpers implement selected runtime
behavior and checks. Emission diagnoses missing or unsupported lowering without independently
rediscovering semantics. The imported type-blind policy remains legacy-generator provenance, not
the my-try architecture.

This reconciles existing behavior; no compiler structures or semantics are added.
See [focused proof](../../tests/operators.php) and the [bounded scalar contracts and
generated-program evidence](../s2s_scalar_operators.md). Native compiler evidence is tracked
separately in the [portability review](../portability/conversion_review.md). Imported prose
follows unchanged as provenance.

> ## 16. Expression Emission Policy
>
> - expression lowering must remain structural and simple
> - explicit grouping/parentheses must be preserved when the PHP AST encodes grouped binary expressions
> - the generator must not flatten grouped expressions in a way that changes PHP operator precedence
> - the generator must not try to behave like a semantic expression compiler
> - casts, operators, precedence-preserving grouping, wrapper-type behavior, and null checks are emitted into C++ according to the configured forms and are then handled by the runtime and the C++ compiler
> - the generator should only reject an expression when a generation rule explicitly marks that source form unsupported

## NOTE-043

**Source:** [generators/php/specs/rules.md:894](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Agreed 2026-10-02. Recursive preparation and grouping follow
NOTE-013. An assignment chain evaluates its rightmost expression once, converts and stores
right-to-left, and passes each stored-value snapshot to the next assignment. Inferred
declarations remain confined to supported standalone chains. Emission uses the established
statement or expression lowering appropriate to context; a universal decomposition into
standalone statements is not required.

This reconciles existing behavior; no compiler structures or semantics are added.
See [focused proof](../../tests/logical.php) and the [bounded scalar contracts and
generated-program evidence](../s2s_scalar_operators.md). Native compiler evidence is tracked
separately in the [portability review](../portability/conversion_review.md). Imported prose
follows unchanged as provenance.

> ## 14. Expression normalization rules
>
> ### 14.1 Recursive normalization
> Expression normalization is recursive and bottom-up on the AST.
>
> Every literal at any depth must be normalized.
>
> Example:
> ```cpp
> auto a = (b + static_cast<int_t>(1)) * static_cast<int_t>(2);
> ```
>
> ### 14.2 Parentheses
> Preserve parentheses to maintain evaluation order.
>
> ### 14.3 Chained assignment
> Chained assignments must be decomposed into sequential statements.
>
> Example:
> ```cpp
> auto b = static_cast<int_t>(1);
> auto a = b;
> ```

## NOTE-044

**Source:** [generators/php/specs/rules.md:918](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Agreed 2026-10-02. Each implemented operator family shares
preparation and lowering across nested expressions. Its current bounded contract determines
supported operand types and conversions; literals retain resolved types and appropriate runtime
representations. Imported C++ examples are illustrative rather than mandatory spelling, and
introduce no implicit coercions or extra type promotions. In particular, concatenation of a
string and an integer requires an explicit string cast.

This reconciles existing behavior; no compiler structures or semantics are added.
See [focused proof](../../tests/operators.php) and the [bounded scalar contracts and
generated-program evidence](../s2s_scalar_operators.md). Native compiler evidence is tracked
separately in the [portability review](../portability/conversion_review.md). Imported prose
follows unchanged as provenance.

> ## 15. Generalized operator families
>
> ### 15.1 Arithmetic family
> Covers:
> - `+`
> - `-`
> - `*`
> - `/`
> - `%`
>
> Rule:
> Binary arithmetic operations must be emitted as arithmetic on normalized runtime-typed operands. Any literal operand must first be converted with the appropriate `static_cast<..._t>(...)`.
>
> Examples:
> ```cpp
> auto a = static_cast<int_t>(1) + static_cast<int_t>(2);
> auto a = static_cast<int_t>(1) - static_cast<int_t>(2);
> auto a = static_cast<int_t>(2) * static_cast<int_t>(3);
> auto a = static_cast<int_t>(4) / static_cast<int_t>(2);
> auto a = static_cast<int_t>(5) % static_cast<int_t>(2);
> auto a = b + static_cast<int_t>(1) + c;
> ```
>
> ### 15.2 Concatenation family
> PHP `.` maps to C++ `+` on `string_t` operands.
>
> Examples:
> ```cpp
> auto a = string_t("a") + string_t("b");
> auto a = cast<string_t>(b) + string_t("x");
> auto a = string_t("x") + cast<string_t>(b);
> ```
>
> ### 15.3 Non-strict comparison family
> Covers:
> - `==`
> - `!=`
> - `<`
> - `<=`
> - `>`
> - `>=`
>
> Rule:
> Comparison operations must operate on normalized operands. Any literal operand must be converted using the appropriate runtime cast.
>
> Examples:
> ```cpp
> auto a = (b == static_cast<int_t>(1));
> auto a = (b != static_cast<int_t>(1));
> auto a = (b < static_cast<int_t>(1));
> auto a = (b <= static_cast<int_t>(1));
> auto a = (b > static_cast<int_t>(1));
> auto a = (b >= static_cast<int_t>(1));
> ```
>
> ### 15.4 Strict comparison family
> - `===` -> `php::identical(...)`
> - `!==` -> `php::not_identical(...)`
>
> Examples:
> ```cpp
> auto a = php::identical(b, static_cast<int_t>(1));
> auto a = php::not_identical(b, static_cast<int_t>(1));
> ```
>
> ### 15.5 Unary operators currently lowered directly
> Examples:
> ```cpp
> auto a = -b;
> auto a = +b;
> auto a = ~b;
> ```
>
> Notes:
> - unary minus lowers as `(-<expr>)`
> - unary plus lowers as `(+<expr>)`
> - unary bitwise NOT lowers as `(~<expr>)`
> - grouped unary/binary combinations must preserve AST structure, for example `(-a) * 2` and `(~a) * 2`

## NOTE-054

**Source:** [generators/php/specs/rules.md:1228](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Agreed 2026-10-02. Assignment lowering evaluates target and RHS
once in the required order, converts before storing, and returns a snapshot of the stored value
rather than an alias. Helper lambdas and direct assignment are lowering choices permitted only
where they preserve those guarantees; the note mandates neither spelling universally. Existing
lowering is retained. Imported append examples establish no meaning for `[]`; its eventual
overload contract owns result and writability.

This reconciles existing behavior; no compiler structures or semantics are added.
See [focused proof](../../tests/logical.php) and the [bounded scalar contracts and
generated-program evidence](../s2s_scalar_operators.md). Native compiler evidence is tracked
separately in the [portability review](../portability/conversion_review.md). Imported prose
follows unchanged as provenance.

> ## Assignment-expression lambda fallback
>
> - Default rule: assignment statements and simple assignment expressions lower directly and do **not** use a helper lambda.
> - Example statement: `$x[0]["name"] = "first";` lowers to `x[0]["name"] = "first";`.
> - Example simple expression: `$y = ($x[0]["name"] = "first");` lowers to `mixed_t y = (x[0]["name"] = "first");` when native C++ assignment-expression semantics already preserve the assigned value.
> - Fallback rule: emit a helper lambda only for complex expression contexts where the generator must guarantee single evaluation and return the assigned value explicitly, especially append expressions or larger composed expressions such as function-call arguments / concatenations that would otherwise duplicate work or lose PHP assignment-value semantics.


### Cross-family strict identity — 2026-10-19

[The E3I proof](../planning/cross_identity.md) verifies 96 matching-payload
cross-family `===`/`!==` signatures after the authorized runtime dispatch repair.
Whole-state snapshots and normalized identity are preserved. Ordinary equality
and mixed payloads remain separate.


### Mixed numeric strict identity — 2026-10-19

[The E4I proof](../planning/mixed_identity.md) verifies 96 carrier signatures and
four scalar prerequisites for int/float `===`/`!==`. Numeric kinds and whole-state
snapshots are preserved without promotion. Ordinary mixed equality remains separate.


### Bool/numeric strict identity — 2026-10-19

[The E5BNI proof](../planning/bool_numeric_identity.md) verifies 192 carrier
signatures and eight scalar prerequisites, preserving states and eager snapshots
without truth conversion. Ordinary equality and bool/numeric logical mixing remain
separate. Runtime implementation is unchanged.
