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
- `??` produces the first defined value and usually unwraps `nullable<T>` to `T`
- `?:` chooses between two branches and may preserve wrapper shape such as `nullable<T>`

## Initial `??` matrix

| Left | Right | Result | Status | Notes |
|---|---|---:|---|---|
| `T` | `T` | `T` | supported | exact same non-wrapper type |
| `nullable<T>` | `T` | `T` | supported | unwrap left when set; cast fallback to `T` |
| `nullable<T>` | `nullable<T>` | n/a | rejected for now | would require a distinct result policy |
| mixed/other cross-type joins | n/a | n/a | rejected for now | add explicitly later |

## Initial `?:` matrix

| Then | Else | Result | Status | Notes |
|---|---|---:|---|---|
| `T` | `T` | `T` | supported | exact same type |
| `nullable<T>` | `nullable<T>` | `nullable<T>` | supported | exact same wrapper type |
| `nullable<T>` | `T` | `nullable<T>` | supported | else branch is wrapped into `nullable<T>` |
| `T` | `nullable<T>` | `nullable<T>` | supported | then branch is wrapped into `nullable<T>` |
| mixed/other cross-type joins | n/a | n/a | rejected for now | add explicitly later |

## Condition rule for ternary

The ternary condition is evaluated once inside the runtime helper.

Initial supported truthiness path:
- plain scalar / dynamic values through existing `cast<bool>(...)` behavior
- `nullable<T>` as false when empty, otherwise truthiness of the contained `T`

## Elvis rule

`$x ?: $y` lowers through a temporary plus `php::ternary_eval(...)` so `$x` is evaluated exactly once.

## Test intent

Seed tests should prove that the helper path behaves identically for:
- direct nullable expressions
- nullable values first assigned to locals
- ternary / elvis over the same runtime types

As new combinations are approved, extend this matrix first, then add fixtures.
