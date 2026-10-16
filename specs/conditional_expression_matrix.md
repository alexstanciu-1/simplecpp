# Conditional / Coalesce Runtime Matrix
Doc Status: normative

## v0.2 nullable inline-record selections — 2026-10-07

For an exact inline-record payload `T` (a struct or `value<Class>`), ternary and
Elvis joins of `nullable<T>` with `T` or the same `nullable<T>` preserve
`nullable<T>`. Coalescing `nullable<T> ?? T` produces an owned `T`, extracting the
present payload or selecting the fallback. No implicit move or reference result
is introduced: payloads must be copyable, including when one branch is known to
be unselected. Both branches are validated during preparation; runtime evaluation
is lazy and evaluates the condition/left operand once.

Bare-null branches, nullable coalescing fallbacks, cross-record conversions and
contextual data-literal branches remain outside this bounded slice. This does not
broaden match, Result payloads or direct nonnullable-record joins.

## v0.2 inline-record conditions — 2026-10-07

Ordinary inline structs and `value<Class>` records are always truthy, independently
of field values. A nullable inline record is false when absent and otherwise
delegates to its always-truthy payload. Observation evaluates its operand once but
does not copy or consume it; noncopyable payloads are supported. All condition
consumers share this rule. Explicit boolean casts remain a separate contract.
`isset` observes presence; `empty` now has the distinct
[collection-only contract](count_empty_isset_contract.md#v02-collection-only-emptiness--2026-10-07)
and must not be inferred from negated truthiness or absence.
This document defines the initial centralized runtime matrix for helper-based lowering of:
- `??` via `php::coalesce_eval(...)`
- `?:` / ternary via `php::ternary_eval(...)`

## Goal

Document the current helper-owned conditional behavior while ensuring that:
- direct expressions and assigned locals behave the same
- supported combinations are deterministic
- wrapper-aware selection behavior is explicit
- runtime-only rejection paths are documented where the current architecture cannot reject the row earlier

## Runtime helper split

The helpers are intentionally separate because the semantic target differs:
- `??` uses wrapper-state / usable-value presence for approved wrapper families and does not preserve wrapper carriers in the result
- `?:` chooses between two branches using condition truthiness and may preserve wrapper shape where explicitly documented for that helper family
- `??` must never reuse condition truthiness as its decision rule
- both helpers may reuse shared wrapper/value inspection helpers where appropriate, but their semantic entry rules remain distinct

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
- the current generator does not resolve these rows statically, so some profile-specific invalid rows are runtime-rejected in v1 rather than generator- or compile-time rejected

v0.2 semantic-compiler rule: reject a statically known `result_or_bool<T>` operand
on either side during preparation, even if that branch would not execute. This is
earlier diagnosis of the same invalid combination, not a change to usable-value
semantics. See [v0.2 diagnostic policy](v0_2_preparation_diagnostics.md).

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

## v0.2 shared-handle joins

Full ternary, shorthand Elvis and coalescing accept two shared-handle alternatives
when their types match or one supplied type can upcast to the other. In the latter
case the result is that supplied base/interface type, independent of branch order
and of the destination receiving the expression. Ordinary classes and explicitly
shared structs use the same rule; a record paired with an implemented interface
produces the interface handle. Preparation observes ancestry dependencies.

No unseen common ancestor/interface is inferred for siblings. Such branches must
first be explicitly normalized to a chosen shared type. Unique/weak joins, inline
record joins, bare-null branch inference and wrapper-payload widening are not
enabled by this amendment. Match retains its existing result admission.

The condition/left operand is evaluated once. Only the selected ternary branch or
needed fallback executes. Shared Elvis and coalescing both test handle presence;
an empty left selects the fallback, which may itself be empty. The selected handle
retains its object/control-block identity without allocating or copying a payload.
Results are values, not reference aliases. Statically invalid joins reject during
preparation even when the invalid branch is unselected.

This amendment extends the older exact-type matrices below, not their scalar,
Result, nullable or boxed-family behavior. It does not claim support in the legacy
type-blind generator or its portable-PHP authoring profile.

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
| other mixed/other cross-type joins | n/a | n/a | rejected in current contract | not part of the current matrix |

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
| mixed/other cross-type joins | n/a | n/a | rejected in current contract | not part of the current matrix |

## Condition rule for ternary

The ternary condition is evaluated once inside the runtime helper.

Current approved condition domain:
- direct condition inputs are governed by `condition_truthy(...)`, not by generic explicit-cast rules
- direct string truthiness is supported
- `string_t` truthiness is PHP-like: `""` -> false, `"0"` -> false, anything else -> true
- object-handle truthiness is presence/aliveness-based by project intent: live non-null handles are truthy; null/expired handles are falsy
- `mixed_t` participation is hybrid in the current implementation: approved inner kinds delegate to their condition rule; full kind coverage is still an implementation task
- if a real `bool_t` value is required rather than condition truthiness, explicit/typed boolean normalization uses the distinct string-to-bool rule documented below

String truthiness and boolean normalization are intentionally distinct:
- condition truthiness for `string_t`: `""` -> false, `"0"` -> false, anything else -> true
- `string_t -> bool_t` normalization: `"true"` / `"1"` -> true; `"false"` / `"0"` / `""` -> false; anything else -> runtime error

Practical implication:
- `"0" ? ... : ...` selects the false branch
- `"yes" ? ... : ...` selects the true branch
- assigning or casting `"yes"` to `bool_t` is a runtime error
- `$mixed ?: $fallback` currently remains limited by the active `mixed_t` kind support implemented in the shared condition helper

Current supported condition path:
- plain scalar inputs use the shared condition-truthiness rule
- `nullable<T>` is false when empty, otherwise it reuses the condition rule of the contained `T`
- `result<T>` is false on non-value/error state and otherwise reuses the condition rule of the carried `T`
- `result_or_false<T>` is false on its false sentinel and otherwise reuses the condition rule of the carried `T`
- `result_or_bool<T>` reuses the carried `T` on success; its sentinel `true` / `false` states evaluate as boolean success/failure states
- `mixed_t` currently delegates only for the approved implemented runtime kinds; broader delegation remains a tracked follow-up task

## Result truthiness is not success or presence

For a Result carrying a supported scalar, these distinctions are intentional:

- A successful payload `false`, `0`, `0.0`, or `""` is falsey in a condition, while
  `isset($result)` and successful `take(...)` return true. It is still a value state.
- A successful payload `"0"` is also falsey, but `empty($result)` is false. The
  project's documented string-zero emptiness exception applies through wrappers.
- A `result_or_bool<T>` true sentinel is truthy and present under probe semantics,
  but it contains no `T` payload. Take writes its boolean output, not its value output.
- A structured failure is falsey; its contained real `error` is truthy. These tests
  ask different questions and must not be conflated.
- `??` preserves a successful zero/false/empty-string payload; `?:` instead selects
  its fallback for those falsey payloads. Coalescing unwraps its approved wrappers,
  while the documented ternary/Elvis joins preserve their wrapper result type.

See [probe semantics](count_empty_isset_contract.md) and
[take semantics](error_values_and_take.md). Neither successful extraction nor
`isset` can be implemented as a condition-truthiness shortcut. Logical operator
admission is separate; the condition rules alone do not redefine wrapper `!`, `&&`
or `||` support or guarded-unwrapping behavior.

### v0.2 boxed Result payloads

The same Result rules apply when the exact payload type is `mixed` or `dynamic`.
Wrapper state and boxed payload kind are separate: a successful boxed null is
extractable by `take`, present to `isset($result)`, and selected by `$result ?? $fallback`.
Direct boxed-null `??` instead uses the box's null state. `empty($result)` delegates
to payload emptiness, while conditions/Elvis delegate to payload truthiness: dynamic
containers are truthy; mixed container conditions retain their runtime rejection.

Exact `W<T>/T`, `T/W<T>` and `W<T>/W<T>` ternary/Elvis joins preserve `W<T>`;
approved Result-left coalescing extracts `T`. No family crossing or implicit
unwrap-then-scalar conversion is granted. Mixed copies retain value behavior and
dynamic container copies share identity. `result_or_bool` remains invalid for `??`.

## Elvis rule

`$x ?: $y` lowers through a temporary plus `php::ternary_eval(...)` so `$x` is evaluated exactly once.

Current helper interpretation:
- elvis reuses the same `condition_truthy(...)` authority as ternary for the left operand
- if the left operand is truthy under that shared rule, the result is the normalized left branch value
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
- current elvis compile-time rejected wrapper rows in `specs/operator_matrix/data/semantics/operators_conditional_selection/elvis.tsv` therefore describe a matrix-slice limitation, not a claim that `php::ternary_eval(...)` lacks wrapper-aware condition behavior
- when matrix/profile docs and this runtime-helper document diverge, this document wins for helper-owned ternary/elvis semantics until the matrix slice is expanded

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

## Locally typed string-hash coalescing in the legacy generator — 2026-10-16

For a single indexed read from a locally typed, string-keyed `hash<string>`,
`$values[$key] ?? $fallback` supplies absence when the key is missing and a present
string when it exists. A present empty string or string `"0"` is usable and must
not select fallback. The existing nullable/string coalescing rules govern the
result; no new branch-join conversions are introduced.

The key is evaluated once. The fallback is evaluated only on absence, once; errors
in evaluated key/fallback expressions propagate. Lookup never inserts a key or
mutates the hash. The selected result is owned, not a reference into the container.
Ordinary indexed reads retain their checked-access behavior.

This bounded legacy-generator rule uses existing local declaration metadata. It
adds no whole-program inference or v0.2 preparation behavior. Nested paths, member
receivers, arbitrary receiver calls, other payload/key families, and `??=` are
outside this amendment. Mutation of the receiver from inside its key expression
is not established by this slice's single-evaluation guarantee.
