# DERIVATION_RULES.md

## 1. Scope

This document is **normative**.

It defines the **deterministic derivation model** that maps:
- type families and traits from TYPE_FAMILY_REGISTRY.md
- semantic invariants from SPECIFICATIONS.md

into:

- normalized operational outcomes in SEMANTIC_MATRIX.md

It does NOT permit human interpretation at generation time.

The purpose of this document is to keep the source model compact while preserving a complete, explicit, generator-facing matrix.

---

## 2. Authority Relationship

1. SPECIFICATIONS.md — semantic core and invariants
2. TYPE_FAMILY_REGISTRY.md — family inventory and traits
3. DERIVATION_RULES.md — deterministic derivation logic
4. SEMANTIC_MATRIX.md — normalized resolved outcomes

---

## 3. Closed Outcome Rule

For every derivable operation tuple, the outcome MUST resolve to exactly one of:

- allowed with exact result type
- forbidden

When allowed, derivation MUST resolve:
- exact legality
- exact result type
- exact compatibility condition, if any
- exact special-case handling, if any

There is no interpretive category such as:
- “usually allowed”
- “similar to”
- “implementation-defined”
- “same as C++”
- “same as PHP”

---

## 4. Precedence Model

Derivation precedence is strict.

When multiple rules could appear relevant, the first matching rule in this order governs:

1. family-specific special rules
2. `null_t` interaction rules
3. template-compatibility rules
4. generic trait-based operator rules
5. forbidden by default

This precedence order is mandatory.

---

## 5. Normalized Compatibility Terms

### 5.1 same-`T`

For unary template families, same-`T` means the template argument type is exactly identical.

Example:
- `nullable<int_t>` and `nullable<int_t>` → same-`T`
- `nullable<int_t>` and `nullable<float_t>` → different-`T`

### 5.2 same family

Same family means the outer family constructor is identical.

Examples:
- `shared_p<int_t>` and `shared_p<int_t>` → same family
- `shared_p<int_t>` and `unique_p<int_t>` → different family

### 5.3 numeric pair

A numeric pair is any pair whose members are both in trait `arithmetic_numeric`.

In the current registry this means:
- `int_t`, `int_t`
- `int_t`, `float_t`
- `float_t`, `int_t`
- `float_t`, `float_t`

### 5.4 semantic boolean operand

A semantic boolean operand is any operand valid for logical or conditional usage under the current rules.

In the current system:
- `bool_t` is a semantic boolean type
- native C++ `bool` produced by comparison or logical operations is a control-flow bridge result only
- native C++ `bool` is not a general semantic family

---

## 6. Derivation by Operator Family

### 6.1 Arithmetic derivation

Arithmetic operators:
- binary `+`, `-`, `*`, `/`
- unary `+`, `-`

Binary arithmetic is allowed if and only if both operands form a numeric pair.

Result type:
- `int_t` op `int_t` -> `int_t`
- every other allowed numeric binary arithmetic pair -> `float_t`

Unary arithmetic is allowed if and only if the operand has trait `arithmetic_numeric`.

Result type:
- unary on `int_t` -> `int_t`
- unary on `float_t` -> `float_t`

No other family participates in arithmetic.

### 6.2 Equality derivation

Equality operators:
- `==`
- `!=`

Equality is allowed only under one of the following cases:

1. numeric pair
   - result type: native `bool`

2. `bool_t` with `bool_t`
   - result type: native `bool`

3. `string_t` with `string_t`
   - result type: native `bool`

4. `null_t` with `null_t`
   - result type: native `bool`

5. `nullable<T>` with `nullable<T>` where same-`T`
   - result type: native `bool`

6. `nullable<T>` with `null_t`
   - result type: native `bool`

7. `null_t` with `nullable<T>`
   - result type: native `bool`

8. `shared_p<T>` with `shared_p<T>` where same-`T`
   - result type: native `bool`

9. `unique_p<T>` with `unique_p<T>` where same-`T`
   - result type: native `bool`

10. `weak_p<T>` with `weak_p<T>` where same-`T`
    - result type: native `bool`

All other equality pairs are forbidden.

### 6.3 Relational derivation

Relational operators:
- `<`
- `<=`
- `>`
- `>=`

Relational comparison is allowed if and only if both operands form a numeric pair.

Result type:
- native `bool`

All other relational pairs are forbidden.

### 6.4 Logical derivation

Logical operators:
- unary `!`
- binary `&&`
- binary `||`

Logical operators are allowed if and only if all participating semantic operands are `bool_t`.

Result type:
- native `bool`

No other family participates in logical operators.

### 6.5 Assignment derivation

Simple assignment operator:
- `=`

Assignment is allowed only under one of the following cases:

1. `int_t = int_t`
   - result type: `int_t`

2. `float_t = float_t`
   - result type: `float_t`

3. `bool_t = bool_t`
   - result type: `bool_t`

4. `string_t = string_t`
   - result type: `string_t`

5. `nullable<T> = nullable<T>` where same-`T`
   - result type: `nullable<T>`

6. `nullable<T> = null_t`
   - result type: `nullable<T>`

7. `shared_p<T> = shared_p<T>` where same-`T`
   - result type: `shared_p<T>`

8. `unique_p<T> = unique_p<T>` where same-`T`
   - result type: `unique_p<T>`

9. `weak_p<T> = weak_p<T>` where same-`T`
   - result type: `weak_p<T>`

All other assignments are forbidden unless separately specified in SEMANTIC_MATRIX.md as a future extension.

### 6.6 Compound assignment derivation

Compound assignment operators:
- `+=`
- `-=`
- `*=`
- `/=`

Compound assignment is allowed if and only if the corresponding binary arithmetic operation is allowed and the result type is exactly the left-hand-side type.

Therefore, the allowed compound assignments in the current system are:

- `int_t += int_t`
- `int_t -= int_t`
- `int_t *= int_t`
- `int_t /= int_t`

- `float_t += int_t`
- `float_t += float_t`
- `float_t -= int_t`
- `float_t -= float_t`
- `float_t *= int_t`
- `float_t *= float_t`
- `float_t /= int_t`
- `float_t /= float_t`

All other compound assignments are forbidden.

### 6.7 Explicit conversion derivation

Explicit conversions are allowed only where the target pair is explicitly listed in SEMANTIC_MATRIX.md.

In the current model, traits classify conversion categories, but they do not automatically authorize any conversion pair.

This rule is intentional.

Reason:
- arithmetic trait membership alone is insufficient to define constructor, cast, or explicit conversion API obligations
- conversion surface must remain fully explicit for runtime generation

Therefore:
- TYPE_FAMILY_REGISTRY.md classifies conversion participation
- DERIVATION_RULES.md constrains how conversion authorization is interpreted
- SEMANTIC_MATRIX.md remains the complete resolved conversion surface

### 6.8 Conditional derivation

Conditional validity is allowed only for:

1. `bool_t`
2. native `bool` values produced by allowed comparison operators
3. native `bool` values produced by allowed logical operators

No other family is conditionally valid.

### 6.9 Family-specific rules

The following family-specific rules override generic trait derivation where relevant:

- `null_t` does not participate in arithmetic
- `null_t` does not participate in relational comparison
- `null_t` does not participate in logical operators
- `null_t` participates in equality only where explicitly listed
- `null_t` participates in assignment only where explicitly listed
- pointer-like families do not inherit any arithmetic, relational, logical, or conditional behavior from C++ defaults
- `nullable<T>` does not inherit arithmetic, relational, logical, or conditional behavior from the wrapped `T`

---

## 7. Matrix Obligation

SEMANTIC_MATRIX.md MUST be organized as the normalized derived outcome table for the current registry and derivation rules.

The matrix MUST remain:
- explicit
- exhaustive for the currently registered families
- generator-facing
- audit-friendly

The matrix is therefore not replaced by this document.
It is justified and stabilized by this document.

---

## Final Statement

This document defines deterministic derivation logic.

It exists so the language can grow without sacrificing explicit, exhaustive, generator-safe operational semantics.
