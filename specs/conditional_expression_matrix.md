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
- `??` produces the first defined value and usually unwraps `nullable<T>` to `T`, except for explicit `mixed_t` result-matrix entries that preserve the dynamic carrier
- `?:` chooses between two branches and may preserve wrapper shape such as `nullable<T>`

## Initial `??` matrix

| Left | Right | Result | Status | Notes |
|---|---|---:|---|---|
| `T` | `T` | `T` | supported | exact same non-wrapper type |
| `nullable<T>` | `T` | `T` | supported | unwrap left when set; cast fallback to `T` |
| `mixed_t` | `mixed_t` | `mixed_t` | supported | explicit exact-match path avoids partial-specialization ambiguity in chained coalesce expressions |
| `mixed_t` | `T` | `mixed_t` | supported | preserves dynamic carrier and applies PHP null fallback semantics |
| `T` | `mixed_t` | `mixed_t` | supported | fallback is normalized into `mixed_t` explicitly |
| `nullable<T>` | `mixed_t` | `mixed_t` | supported | unwrap left when set; otherwise use dynamic fallback |
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
| mixed/other cross-type joins | n/a | n/a | rejected for now | add explicitly later |

## Condition rule for ternary

The ternary condition is evaluated once inside the runtime helper.

Initial supported truthiness path:
- plain scalar inputs continue through the existing explicit boolean bridge
- `mixed_t` in ternary / elvis condition context uses PHP-style truthiness inside the helper (`null`, `0`, `0.0`, `""`, and `"0"` are false; non-empty arrays are true; dynamic object handles are true when present)
- `nullable<T>` is false when empty, otherwise it reuses the truthiness rule of the contained `T`, including the `mixed_t` helper path when applicable

## Elvis rule

`$x ?: $y` lowers through a temporary plus `php::ternary_eval(...)` so `$x` is evaluated exactly once.

## Test intent

Seed tests should prove that the helper path behaves identically for:
- direct nullable expressions
- nullable values first assigned to locals
- ternary / elvis over the same runtime types

As new combinations are approved, extend this matrix first, then add fixtures.
