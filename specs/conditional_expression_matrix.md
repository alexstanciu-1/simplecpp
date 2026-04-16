# Conditional / Coalesce Runtime Matrix

This document defines the initial centralized runtime matrix for helper-based lowering of:
- `??` via `php::coalesce_eval(...)`
- `?:` / ternary via `php::ternary_eval(...)`

## Goal

Keep the generator structurally simple and mostly type-blind while ensuring that:
- direct expressions and assigned locals behave the same
- supported combinations are deterministic
- unsupported combinations fail clearly at compile time

## Runtime helper split

The helpers are intentionally separate because the semantic target differs:
- `??` produces the first non-null PHP-visible value and usually unwraps `nullable<T>` to `T`, except for explicit carrier-preserving entries such as `mixed_t` and guarded-result wrappers
- `?:` chooses between two branches using the approved condition subset and may preserve wrapper shape such as `nullable<T>` or guarded-result wrappers
- both helpers share the same wrapper normalization rules for PHP null-ness / approved condition handling even though their result matrices remain separate

## Initial `??` matrix

| Left | Right | Result | Status | Notes |
|---|---|---:|---|---|
| `T` | `T` | `T` | supported | exact same non-wrapper type |
| `nullable<T>` | `T` | `T` | supported | unwrap left when set; cast fallback to `T` |
| `mixed_t` | `mixed_t` | `mixed_t` | supported | explicit exact-match path avoids partial-specialization ambiguity in chained coalesce expressions |
| `mixed_t` | `T` | `mixed_t` | supported | preserves dynamic carrier and applies PHP null fallback semantics |
| `T` | `mixed_t` | `mixed_t` | supported | fallback is normalized into `mixed_t` explicitly |
| `nullable<T>` | `mixed_t` | `mixed_t` | supported | unwrap left when set; otherwise use dynamic fallback |
| `result_or_false<T>` | `T` | `result_or_false<T>` | supported | PHP `false` is not null, so coalesce preserves the guarded wrapper |
| `result_or_bool<T>` | `T` | `result_or_bool<T>` | supported | PHP bool sentinels are not null, so coalesce preserves the guarded wrapper |
| `result<T>` | `T` | `result<T>` | supported | structured result wrappers are not null and preserve their wrapper identity |
| `nullable<T>` | `nullable<T>` | n/a | rejected for now | would require a distinct result policy |
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

## Test intent

Seed tests should prove that the helper path behaves identically for:
- direct nullable expressions
- nullable values first assigned to locals
- ternary / elvis over the same runtime types

As new combinations are approved, extend this matrix first, then add fixtures.


## Shared wrapper normalization rule

The conditional helpers reuse one PHP-visible wrapper normalization rule:
- `nullable<T>` is null when empty and otherwise delegates to the wrapped payload
- `result_or_false<T>` is never null; its empty state is PHP `false`
- `result_or_bool<T>` is never null; its non-value states are PHP `false` and `true`
- `result<T>` is never null; non-success states remain wrapper states
- `mixed_t` uses its active runtime kind (`null`, `bool`, `int`, `float`, `string`, table/object carriers)
