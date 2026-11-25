# Audit â€” implementation vs `specs/dynamic_types.md`
Doc Status: planning
> Transitional implementation note: see `specs/mixed_boundary_transitional.md`.

Project used as source of truth: attached ZIP project.  
Scope: current implementation vs current `specs/dynamic_types.md`.

| # | Area | Spec expects | Current implementation | Gap | Evidence |
|---|---|---|---|---|---|
| 1 | Dynamic type naming / user syntax | User-facing dynamic type is `mixed` / runtime type `mixed_t` | Implementation still uses `mixed_t`; `TypeMapper` maps `array -> mixed_t`; there is no dedicated `mixed` mapping path | High | `TypeMapper.php:431-439`; runtime uses `mixed_t.hpp` |
| 2 | `/** mixed */` support | `/** mixed */` should be the opt-in language surface | `mixed` would currently fall through as an unmapped user type name, not as the dynamic runtime type | High | `TypeMapper.php:431-439`, `484-490` |
| 3 | Typed local / variable destination casts | Generator should emit explicit cast when destination type is visible | Typed locals are emitted as raw `typed name = expr;`; no `cast<...>()` insertion for `mixed_t -> native` | High | `Generator.php:1799-1848` |
| 4 | Typed by-value parameter casts | Generator should emit explicit cast at call site | By-value params are passed through unchanged; only by-ref params get special handling | High | `Generator.php:342-357` |
| 5 | Typed return casts | Generator should emit explicit cast on typed return | Return path only inserts cast for `nullable<T> -> T`; no general `mixed_t -> native` cast insertion | High | `Generator.php:4177-4198` |
| 6 | Typed property assignment casts | Spec treats typed property assignment as a cast site | Generic assignment path emits raw `target = expr;`; no dedicated cast insertion for typed property targets | High | `Generator.php:1813-1815`, `1888-1893`; property access rendered raw in `Generator.php:3728-3738` |
| 7 | `mixed -> native` escape control | Dynamic values should become native only at explicit typed boundaries or explicit narrowing points | `mixed_t` exposes implicit conversion operators to `bool_t`, `int_t`, `float_t`, `string_t`, and `bool`; C++ can convert outside generator-owned boundary rendering | High | `support/mixed_t.hpp:127-141`; `support/mixed_t.cpp:366-372` |
| 8 | No auto-cast on any by-ref boundary | Spec forbids automatic cast insertion for by-ref | Scalar typed by-ref params are auto-wrapped via `int_ref` / `float_ref` / `bool_ref` / `string_ref`; runtime then coerces/borrows through `mixed_t::as_*_ref()` | High | `TypeMapper.php:149-163`; `Generator.php:342-357`, `1698-1709`; `support/mixed_t.cpp:317-352`, `944-962` |
| 9 | Array-read typed destinations | `$a[$k]` is runtime `mixed`; typed destinations should get generated explicit casts | Array reads infer as `mixed_t`, but typed assignment/call/return still rely on raw assignment/passing, not injected casts | High | `Generator.php:4272-4315`; plus rows 3/4/5 above |
| 10 | `foreach` by-reference policy | Spec says generator-dependent / evolving; explicit cast logic still to be defined | Current lowering always emits `auto& value = elementExpr;` with no cast/type-disambiguation step; behavior is fixed, not policy-driven | Medium | `Generator.php:2044-2073` |
| 11 | Operator lowering strategy | Spec runtime matrix describes explicit boxing/helper-based boundary handling | Generator mostly emits raw C++ operators (`+ - * / % && || < <= > >= == !=`) and relies on constructors / implicit conversions / overloads instead of explicit helper calls | Medium | `Generator.php:3682-3705` |
| 12 | Explicit PHP casts on dynamic values | Explicit casts should go through centralized explicit cast behavior | `(int)`, `(float)`, `(bool)` lower to `static_cast<...>`; only string cast uses `cast<string_t>(...)`; this bypasses the centralized `cast<...>()` path for dynamic values | Medium | `Generator.php:3708-3716` |
| 13 | Spec text defect (`||`) | Spec should list logical-OR rows clearly | Current spec file has three malformed rows where `mixed || mixed`, `mixed || native`, `native || mixed` should be; implementation does support `||` lowering | Spec bug | `specs/dynamic_types.md:104-109`; `Generator.php:3694-3695` |

## Bottom line

| Category | Count |
|---|---:|
| High-gap items | 9 |
| Medium-gap items | 3 |
| Spec-text bug | 1 |

## Practical reading

- The largest mismatch is still typed-destination enforcement: the clarified spec allows typed call / return / property / local boundaries, but the implementation still leans heavily on implicit C++ conversions from `mixed_t` instead of generator-owned explicit boundary rendering.
- The second largest mismatch is by-reference: the spec now forbids auto-cast on by-ref boundaries, but the implementation still auto-wraps scalar by-ref cases.
- Arrays are closer to spec than references, but typed destinations from array reads are still affected by the missing cast-injection logic above.
