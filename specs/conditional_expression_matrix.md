# Conditional / Coalesce Runtime Matrix

This document defines the initial centralized runtime matrix for helper-based lowering of:
- `??` via `php::coalesce_eval(...)`
- `?:` / ternary via `php::ternary_eval(...)`

## Goal

Keep the generator structurally simple and mostly type-blind while ensuring that:
- direct expressions and assigned locals behave the same
- supported combinations are deterministic
- wrapper-aware selection behavior is explicit
- runtime-only rejection paths are documented where the current architecture cannot reject the row earlier

## Runtime helper split

The helpers are intentionally separate because the semantic target differs:
- `??` uses wrapper-state / usable-value presence for approved wrapper families and does not preserve wrapper carriers in the result
- `?:` chooses between two branches using the approved condition subset and may preserve wrapper shape where explicitly documented for that helper family
- both helpers share the same wrapper normalization rules for PHP-visible state inspection even though their result policies remain separate

## Current `??` rule

`??` is not modeled as a generic PHP-null-only operator once approved wrappers participate.

Current evaluation rule:
- evaluate the left operand exactly once
- if the left operand has a usable selected value, return its normalized selected value
- otherwise evaluate the right operand exactly once
- if the selected right branch has a usable selected value, return its normalized selected value
- otherwise runtime-reject the operation

Current v1 notes:
- approved wrapper families auto-unpack to their usable value domain
- approved wrapper families for `??` are:
  - `nullable<T>`
  - `result<T>`
  - `result_or_false<T>`
- `result_or_bool<T>` is rejected in the runtime helper path on either side of `??`
- the generator remains intentionally type-blind, so some profile-specific invalid rows are runtime-rejected in v1 rather than generator- or compile-time rejected

## Usable selected value vs selected mixed null

The following states must not be conflated:
- wrapper states with no usable selected value:
  - `nullable.empty`
  - `result.failure`
  - `result_or_false.sentinel.false`
- valid selected mixed values whose payload happens to be null:
  - `mixed_t(null)` / `mixed.null`

Practical effect:
- `mixed_t(null)` may trigger fallback when it is the current branch being tested for usability
- but if fallback selects a `mixed_t(null)` branch, the selected value domain is still valid and the coalesce result may be `mixed_t(null)`

## Initial `??` matrix

| Left | Right | Result | Status | Notes |
|---|---|---:|---|---|
| `T` | `T` | `T` | supported | exact same non-wrapper type |
| `nullable<T>` | `T` | `T` | supported | approved wrapper auto-unpacks when present; fallback uses `T` |
| `result<T>` | `T` | `T` | supported | approved wrapper auto-unpacks on success; failure falls through to fallback |
| `result_or_false<T>` | `T` | `T` | supported | approved wrapper auto-unpacks on success; false sentinel falls through to fallback |
| `mixed_t` | `mixed_t` | `mixed_t` | supported | exact-match mixed path uses selected value-domain semantics |
| `mixed_t` | `T` | `mixed_t` | supported | mixed carrier remains the explicit result domain for this join |
| `T` | `mixed_t` | `mixed_t` | supported | fallback is normalized into `mixed_t` explicitly |
| `nullable<T>` | `mixed_t` | `mixed_t` | supported | present left unwraps to payload; fallback uses mixed selected value domain |
| `result<T>` | `mixed_t` | `mixed_t` | supported | success unwraps to payload; failure falls through to mixed selected value domain |
| `result_or_false<T>` | `mixed_t` | `mixed_t` | supported | success unwraps to payload; false sentinel falls through to mixed selected value domain |
| `result_or_bool<T>` | any | runtime error | supported + throws | current version rejects `result_or_bool<T>` in `php::coalesce_eval(...)` |
| selected branch has no usable value domain | n/a | runtime error | supported + throws | current version runtime-rejects rows whose selected branch still has no usable selected value domain |
| other mixed/other cross-type joins | n/a | n/a | rejected for now | add explicitly later |

## Initial `?:` matrix

| Then | Else | Result | Status | Notes |
|---|---|---:|---|---|
| `T` | `T` | `T` | supported | exact same type |
| `mixed_t` | `mixed_t` | `mixed_t` | supported | explicit exact-match path kept alongside generic same-type deduction for symmetry with `??` |
| `mixed_t` | `T` | `mixed_t` | supported | truthy branch preserves the dynamic carrier; else branch is normalized into `mixed_t` |
| `T` | `mixed_t` | `mixed_t` | supported | else branch preserves the dynamic carrier; then branch is normalized into `mixed_t` |
| `nullable<T>` | `nullable<T>` | `nullable<T>` | supported | exact same wrapper type |
| `nullable<T>` | `T` | `nullable<T>` | supported | else branch is wrapped into `nullable<T>` |
| `T` | `nullable<T>` | `nullable<T>` | supported | then branch is wrapped into `nullable<T>` |
| `result_or_false<T>` | `T` | `result_or_false<T>` | supported | fallback `T` is wrapped into the guarded PHP `T|false` carrier |
| `T` | `result_or_false<T>` | `result_or_false<T>` | supported | present branch is wrapped into the guarded PHP `T|false` carrier |
| `result_or_bool<T>` | `T` | `result_or_bool<T>` | supported | fallback `T` is wrapped into the guarded PHP `T|bool` carrier |
| `T` | `result_or_bool<T>` | `result_or_bool<T>` | supported | present branch is wrapped into the guarded PHP `T|bool` carrier |
| `result<T>` | `T` | `result<T>` | supported | fallback `T` is wrapped into the structured result carrier |
| `T` | `result<T>` | `result<T>` | supported | present branch is wrapped into the structured result carrier |
| mixed/other cross-type joins | n/a | n/a | rejected for now | add explicitly later |

## Condition rule for ternary

The ternary condition is evaluated once inside the runtime helper.

Current approved condition domain:
- direct condition inputs are limited to the configured explicit condition subset
- no implicit string truthiness is supported
- `mixed_t` is allowed in ternary / elvis condition context only when its active runtime kind is `bool`, `int`, or `float`
- `mixed_t` holding `null`, `string`, or table / object-like carriers must fail instead of participating implicitly in condition evaluation
- if boolean intent is required for string or other non-approved carriers, the source must be normalized explicitly before the condition site

Practical implication:
- `"0" ? ... : ...` is not an approved implicit condition form
- `"yes" ? ... : ...` is not an approved implicit condition form
- `$mixed ?: $fallback` is valid only when `$mixed` carries runtime `bool`, `int`, or `float`, or has been normalized explicitly before the condition site

Initial supported condition path:
- plain scalar inputs continue through the existing explicit boolean bridge
- `mixed_t` in ternary / elvis condition context uses a narrow helper-owned rule: only runtime `bool`, `int`, and `float` kinds participate; all other kinds fail
- `nullable<T>` is false when empty, otherwise it reuses the condition rule of the contained `T`, including the narrow `mixed_t` path when applicable

## Elvis rule

`$x ?: $y` lowers through a temporary plus `php::ternary_eval(...)` so `$x` is evaluated exactly once.

Current helper interpretation:
- elvis reuses ternary condition evaluation for the left operand
- if the left operand is truthy under the helper-owned condition rule, the result is the normalized left branch value
- otherwise the result is the normalized fallback branch value
- elvis therefore shares the ternary condition domain and branch-normalization matrix rather than the coalesce usable-value rule

## Current ternary / elvis layering note

The current project has three distinct layers for `expr_1 ? expr_2 : expr_3` and `expr_1 ?: expr_3`:
- the generator lowers both operators through `php::ternary_eval(...)`
- the runtime helper currently implements wrapper-aware condition delegation for `nullable<T>`, `result<T>`, `result_or_false<T>`, and `result_or_bool<T>`, plus the narrow `mixed_t` condition rule already documented above
- the operator-matrix structured data is still narrower for the current emitted slice and must not be mistaken for the full runtime-helper capability

Current practical rule:
- runtime helper behavior is authoritative for already-lowered ternary / elvis code paths
- the operator-matrix dataset remains authoritative only for the rows it explicitly models and emits in the current slice
- current elvis compile-time rejected wrapper rows in `specs/operator_matrix/data/semantics.json` therefore describe a matrix-slice limitation, not a claim that `php::ternary_eval(...)` lacks wrapper-aware condition behavior

Current matrix-slice status:
- ternary structured data currently focuses on non-wrapper branch pairs plus the currently approved condition slice
- elvis structured data currently models same-type non-wrapper rows and explicit `mixed_t` rows
- wrapper-led elvis rows remain emitted as compile-time rejected in the matrix dataset pending a dedicated matrix expansion and reconciliation pass

## Test intent

Seed tests should prove that the helper path behaves identically for:
- direct nullable expressions
- nullable values first assigned to locals
- ternary / elvis over the same runtime types
- wrapper-aware coalesce rows, including runtime-only rejection rows that cannot be classified earlier by the current generator architecture

As new combinations are approved, extend this matrix first, then add fixtures.

## Shared wrapper normalization rule

The conditional helpers reuse one PHP-visible wrapper normalization rule:
- `nullable<T>` is null when empty and otherwise delegates to the wrapped payload
- `result_or_false<T>` is not a usable selected coalesce value in its false-sentinel state; on success it delegates to the wrapped payload
- `result_or_bool<T>` is never approved for `??` in the current version; its non-value states are `false` and `true` sentinels
- `result<T>` is not a usable selected coalesce value in failure state; on success it delegates to the wrapped payload
- `mixed_t` uses its active runtime kind (`null`, `bool`, `int`, `float`, `string`, table/object carriers)

Current version note:
- `??` uses wrapper-state / usable-value presence for approved wrappers
- `?:` / ternary use condition-truthiness rules instead
- helper result behavior must therefore be documented per helper family rather than inferred from a single PHP-visible nullability rule
