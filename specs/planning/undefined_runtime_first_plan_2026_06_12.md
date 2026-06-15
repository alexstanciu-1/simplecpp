# Undefined Runtime-First Plan
Doc Status: planning

Date: 2026-06-12

Purpose: define `undefined` as a distinct runtime and type-system concept before adding JSS source support, so absence semantics do not get approximated with `null`, ad hoc hash checks, or frontend-only lowering tricks.

This is a planning artifact, not semantic authority.

## Direction

`undefined` should be treated as a separate concept from `null`, but the first implementation should be narrower than a full storable language value.

Working intent:

- `null` means present empty value
- `undefined` means absent / not provided / missing slot
- PHP++ / PHS should own the first source-level `undefined` support
- PHS may accept `undefined` as a reserved keyword only in explicit comparison forms at first
- S2S should lower `expr == undefined`, `expr != undefined`, `expr === undefined`, and `expr !== undefined` to a compiler/runtime intrinsic rather than treating `undefined` as an ordinary value
- JSS should not implement `undefined` source behavior until the runtime/PHS/S2S contract exists
- optional chaining should not wait on or return `undefined` in the current direction; JSS optional chaining should return `null` on failed access

## Why This Must Start In The Runtime

If JSS implements `undefined` first, we would likely create one of these bad paths:

1. lower `undefined` to `null`
2. lower `undefined` to helper calls with no runtime type
3. fake absence through frontend-only structural checks

All three would create semantic drift between:

- JSS source behavior
- PHS/PHP++ behavior
- STAN type/narrowing behavior
- runtime/container behavior

That is exactly the kind of duplicate path we have been trying to avoid.

## Required Runtime / Type-System Work

Before JSS source support, we likely need:

1. A comparison intrinsic
   - S2S-visible helper or compiler intrinsic for “is undefined”
   - should work safely for statically known non-undefined-capable values
   - should preserve `===` / `!==` style intent without importing JS loose absence behavior

2. Reserved keyword handling in PHS
   - accept `undefined` only in the comparison forms for the first slice
   - reject or defer general value use such as assignment, passing as an argument, or storing in containers until a real runtime value model exists

3. Later runtime representation, if needed
   - analogous in status to `null_t`, but distinct
   - may eventually survive container storage, comparison, and transport through `mixed` / dynamic boundaries

4. Later type-system spelling and unions
   - `undefined`
   - `T | undefined`
   - interaction with `?T` / `T | null`
   - clear distinction between nullable and absent-capable values

5. Container lookup semantics
   - missing hash key
   - missing object-like field on dynamic carriers
   - present key with `null`
   - present key with `undefined`

6. Comparison and narrowing rules
   - `value === undefined`
   - `value !== undefined`
   - STAN narrowing after explicit checks
   - no loose equality shortcuts

7. Runtime/helper surface decisions
   - whether presence checks are separate helpers
   - whether some APIs return `result<T>` versus `T | undefined`
   - how `undefined` behaves at JSON/dynamic boundaries

## JSS Scope For Later

Once the runtime/type work exists, JSS can add:

- explicit `=== undefined` / `!== undefined` checks
- maybe explicit `== undefined` / `!= undefined` checks if they remain accepted by the PHS comparison contract
- lookup/narrowing rules for `row["k"]`, only after the runtime/PHS contract defines what can be undefined
- maybe later general `undefined` literal/value support once null-vs-undefined semantics are fully documented

But that JSS work should happen on top of the runtime contract, not before it.

## Recommended Branching / Delivery Strategy

1. Separate runtime/type/STAN design branch for `undefined`
2. Runtime representation and core typing contract
3. STAN narrowing and lookup semantics
4. PHS/PHP++ surface decision
5. JSS source support only after the above is stable

## Explicit Non-Goals For The Current JSS Branch

- do not lower `undefined` to `null`
- do not add frontend-only fake absence semantics
- do not broaden `??` based on guessed future `undefined` behavior
- do not make missing-key behavior language-specific between JSS and PHS
- do not make optional chaining return `undefined`; the current JSS optional-chain direction is failed chain -> `null`

## Follow-Up Questions

1. Should `undefined` be storable everywhere `mixed` is allowed, or only through selected container/dynamic paths?
2. Should hash lookup return `T | undefined`, a wrapper/result shape, or remain operation-specific?
3. Do we want a dedicated runtime `undefined_t` name, or another canonical spelling?
4. What should JSON decode do with absent versus explicit `null` fields at the dynamic boundary?
5. Should `== undefined` and `!= undefined` remain accepted as comparison-intrinsic forms, or should only `===` / `!==` be accepted after the proof of concept?
