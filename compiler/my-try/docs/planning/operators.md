# Operator preparation and resolution
Doc Status: planning

Discussion started: 2026-10-01

## Purpose and authority

This document records the agreed implementation direction for operators in
`compiler/my-try`. It is an implementation plan, not a user-visible language
specification. Root specifications remain authoritative for operator meaning. The
catalog records each selected source example independently.

The active delivery path is the shared frontend plus C++ S2S. LLVM and legacy STAN
remain parked. Native compiler validation is an explicit checkpoint rather than a
default implementation step.

## Resolution order

Operator resolution follows the useful shape of C++ overload resolution without
silently inheriting every C++ operator semantic:

```text
prepare actual operands in source order
-> discover candidates for the source operator and arity
-> consider candidate-specific operand conversions
-> discard non-viable candidates
-> rank viable candidates
-> require one unique best candidate
-> retain its operand conversions, semantic operation and result type
```

Operands are not first converted to a guessed common type. Conversions are
considered against each candidate's expected operand types. A missing candidate or
an ambiguous best candidate is a hard diagnostic; registration order must never be
a semantic tie-breaker.

Each currently supported operator has one hard-coded language candidate, so it
needs neither a candidate registry nor conversion ranking yet. Those mechanisms should be added
only when a second viable candidate makes them real.

## Shared model

Separate syntax nodes represent genuinely different grammar shapes: unary, binary
and ternary. All of them will call the same semantic owner:

```php
Operator_Preparation::decide(
	operator_kind $operator,
	array $operands, // ordered canonical_type_use values
	operator_context $context,
): operator_decision;
```

The retained decision contains:

```text
operator_decision
    operator_kind source_operator
    operator_operation selected_operation
    ordered conversion_decision operands
    canonical_type_use result_type
```

`operator_kind` identifies normalized source meaning such as addition.
`operator_operation` identifies the selected semantic behavior such as integer
addition. Built-in operations initially use an enum. Runtime- and source-declared
overloads may later use compact numeric identities without changing the decision's
role.

The expected type of each operand is already the target type in its retained
`conversion_decision`; it is not duplicated on the operator decision. Backend
spelling is also absent. A backend maps `operator_operation` to its lowering and
renders the retained operand conversions.

The conditional `?:` operator will eventually have a ternary syntax node and three
ordered operand decisions. Its non-overloadable C++ status affects candidate
discovery only; it does not create a parallel preparation or lowering path.

## Organization

Operator algorithms live directly under analysis:

```text
04_analyze/operators/
|- model.php
|- preparation.php
|- integers.php
|- floating_points.php       # future
|- booleans.php              # future
`- strings.php               # future/current-language-contract dependent
```

The initial implementation creates only files with implemented responsibilities.
Family owners compute rules algorithmically rather than enumerating concrete type
pairs. Runtime and source overload registries remain future candidate providers.

## Conversion boundary

Operator operands use `conversion_context::operator_operand`. Explicit-cast
permission does not imply implicit operator conversion permission. Once a candidate
has been selected, each operand receives an ordinary `conversion_decision`, even
when its conversion is identity.

When real overload sets arrive, conversion preparation will need a non-throwing
candidate query that returns non-viable or a decision plus rank. The existing
throwing `Conversion_Preparation::decide()` remains the correct API for the selected
candidate and for required typed boundaries. This first exact-match slice does not
introduce speculative ranking structures.

## Integer promotion contract

No current top-level specification defines mixed-width signed/unsigned operator
promotion. Before mixed integer arithmetic is implemented, the owning normative
language contract must define it. If no Simple C++-specific rule is adopted, the
fallback decision is to adopt the exact C++ usual arithmetic conversions rather
than accidentally depending on emitted C++ behavior.

This requirement is dormant in the current arithmetic slice: canonical
`int + int -> int`, subtraction, multiplication, division and remainder are admitted.
Canonical-int comparisons are also supported; see the [current scalar guide](../s2s_scalar_operators.md).
`uint32 + int`, other fixed-width pairs and floating/integer pairs remain hard rejections.

## Legacy S2S evidence

The old S2S catalog generalizes arithmetic and records useful edge cases, but it is
type-blind evidence rather than semantic authority. The applicable behavior to
preserve now is:

- recursively prepared operands and normalized literals;
- left-associative addition chains without per-chain-length rules;
- variable copies and self-reassignment retaining their established identities;
- hard rejection when an operand is outside the selected numeric shape;
- no unsafe assumption about C++ operand evaluation order for effectful operands.

Parenthesized grouping is now covered by
[EXPR-PAREN-001](../catalog/02_expressions.md#expr-paren-001), normalizing to the
existing expression tree. Subtraction and multiplication are covered by
[EXPR-SUB-001](../catalog/02_expressions.md#expr-sub-001) and
[EXPR-MUL-001](../catalog/02_expressions.md#expr-mul-001) through the same integer
binary policy. Division, remainder, integer comparisons, string concatenation and
boolean logical operations now have bounded catalog proofs. Broader numeric
compatibility remains a separate decision.

## First implementation slice (completed addition checkpoint)

1. Add the shared operator model and central preparation owner.
2. Add the hard-coded integer language policy.
3. Migrate only the existing canonical `int + int -> int` behavior.
4. Retain two identity `conversion_decision` operands and the canonical `int`
   result on `operator_decision`.
5. Attach the decision to `prepared_binary_expression`; remove the old standalone
   `binary_operation` fact.
6. Make C++ emission consume the selected operation and operand conversions while
   preserving the exact current `(left + right)` output and header.
7. Preserve the existing order-independence restriction and exact diagnostics.
8. Add focused decision tests plus the exact `EXPR-ARITH-001` fixture.
9. Update the expression catalog with frontend and generated-C++ evidence.
10. Run focused and broad PHP-host regressions, then review the retained shape.

## Explicit debt and non-goals

- mixed-width integer promotion and its normative contract;
- conversion viability ranking and ambiguity resolution machinery;
- runtime and source-declared overload registries;
- floating-point operators, broader comparison types and assignment operators;
- implicit conversions for PHP++ string concatenation;
- JS++ `+` string concatenation and possible future operator carrier changes;
- broader unary operand types and signed-literal magnitude handling;
- ternary syntax and the shared three-operand call;
- keyword logical and assignment precedence;
- effect analysis that can safely admit calls as operands;
- LLVM and legacy STAN. Native compiler validation remains an explicit post-slice
  checkpoint rather than an automatic implementation step.

## Implementation result — addition checkpoint

The first slice is implemented:

- `operator_decision` retains normalized source kind, selected semantic operation,
  ordered operand conversion decisions and canonical result type;
- `Operator_Preparation` owns token normalization, operand preparation, candidate
  selection and the retained binary facts;
- `Integer_Operators` owns the exact canonical `int + int -> int` language rule;
- both operands pass through `Conversion_Preparation::decide()` with
  `operator_operand` context after the exact candidate is selected;
- C++ lowering consumes only the retained operation and conversions;
- legacy-relevant chains, recursive preparation, self-reassignment, unsupported
  operand and effectful-operand behavior remain intact;
- focused operator, full PHP-host S2S, conversion and PHP-host incremental C++
  generation tests pass.

The subsequently requested native checkpoint also passes. The compiler converts
and builds with Clang 18 and `--no-stan`; PHP-host and native-compiler output is
byte-identical for all 105 valid S2S fixtures, all generated programs compile and
execute, all 32 rejection/recovery cases agree, and the 15 specialized floating
spelling assertions remain exact. The only portability correction was replacing the
reserved C++ local/parameter name `$operator` with `$source_operator`. See the
[portability evidence](../portability/conversion_review.md).

No deferred operator family, syntax form, overload source or promotion rule was
implicitly activated by this migration.

## Current scalar extension

The existing binary syntax and decision records now support canonical-int arithmetic
and comparisons, canonical-string concatenation and canonical-bool `&&`/`||`/`and`/`or`/`xor`.
`Integer_Operators::decide_binary` owns the shared exact integer operand policy;
`Operator_Preparation` owns the bounded string and boolean candidates. Each selected
operand still carries its conversion decision. No retained structures or fields were added.

The parser uses precedence climbing and normalizes grouping into the existing tree.
The backend consumes selected operations, including lazy native-bool logical lowering
and integer three-way comparison with single-evaluation temporaries. Existing
expression statements now accept ordinary scalar expressions.

The [scalar operator guide](../s2s_scalar_operators.md) owns the precise contracts,
precedence, proofs and remaining decisions. The native compiler checkpoint above
predates grouping and these operator extensions; it does not certify the current source.

The 2026-10-02 unary slice adds the agreed unary syntax and its attached fact record,
reusing `operator_decision` with one operand conversion. It covers integer `+`, `-`,
`~` and boolean `!`; see the [unary contract](../s2s_scalar_operators.md#unary-operators)
for grammar, literal limits and proof.

The subsequent mutation slice uses a dedicated target-owning node and
`Mutation_Preparation`. It supports established integer locals/parameters and
old/new value snapshots through the existing operator decision. Fields/indexes and mutations inside binary expressions remain future work; see the
[mutation contract](../s2s_scalar_operators.md#increment-and-decrement).

Arithmetic/string compound updates now use their agreed dedicated node, the shared
mutation target validator, the existing binary computation decision and an explicit
write-back conversion. The [compound contract](../s2s_scalar_operators.md#compound-assignment)
records proof and the remaining target and effect restrictions.

Assignment expressions now share the precedence ladder with symbolic and keyword
operators. Existing assignment facts describe the stored-value snapshot; a transient
preparation flag confines inferred declarations to standalone assignment chains.
Logical paths admit existing-local writes with explicit sequencing. General effect
analysis, conditional declarations and nested field/index assignment remain deferred.
See the [assignment/logical contract](../s2s_scalar_operators.md#assignment-expressions-and-keyword-logic).

Canonical integer power now reuses the binary model and runtime-owned checked
calculation. The [power contract](../../../../specs/integer_power.md) owns its domain,
result range and errors; floating-point power and `**=` remain deferred. See the
[implementation/proof](../s2s_scalar_operators.md#integer-exponentiation).

## Debt: checked shift counts

Decision 2026-10-02: preserve the current runtime's native C++20 shift contract.
For canonical signed 64-bit `int`, counts 0 through 63 are valid; negative counts
and counts at least 64 remain undefined behavior. Neither my-try nor the runtime
adds a guard, masks the count, or promises a diagnostic for invalid counts.

A future checked-shift proposal must be owned by the runtime/language contract,
not introduced only by this frontend. It must decide failure behavior and error
codes, review the currently `noexcept` shift helpers, apply equally to binary and
compound forms and wrapper delegation, and prove dynamic invalid counts without
executing undefined shifts. Compile-time constant diagnostics can then follow the
same agreed policy. This debt does not authorize a runtime behavior change now.

Owner references: `runtime/specs/spec.md`, current operator-phase decision;
`runtime/include/scpp/generated/operator_detail.hpp`, `shl`/`shr`;
C++20 draft N4861 `[expr.shift]`.
