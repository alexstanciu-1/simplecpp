# Compiler-prepared literal construction
Doc Status: planning

Recorded 2026-10-05 during the chapter 06 container discussion. Proposed for v0.3;
not a current language/runtime contract.

## Direction

The agreed [v0.2 standard](../../specs/data_initialization.md) uses recognized
initialization interfaces for positional lists and keyed entries, with known target
types and runtime factory validation. It covers vector, hash, mixed and dynamic;
user-defined types explicitly opt in when their compiler support is available.
Notation includes quoted/bare property names and trailing commas. Nested mixed and
dynamic constructions inherit their enclosing family; stored entry values are mixed
only. Concrete interface/input API declarations remain to be settled before coding.

In v0.3, investigate compiler support that pre-prepares eligible literal data for
types participating in that protocol. Possibilities include validated constant
entries, precomputed key metadata and more efficient construction of known data.
Do not promise a specific representation or performance benefit before measurement.

## Constraints and open questions

- Optimization must preserve the agreed initialization protocol's observable
  behavior: conversions, entry order, duplicate-key policy, evaluation order,
  errors and user-defined construction effects.
- Mutable values and shared dynamic handles must preserve the agreed fresh-instance
  and copy/identity semantics; static prepared data must not accidentally share
  mutable instances between separate evaluations.
- Distinguish fully constant input from literals containing runtime expressions.
- Decide how a type explicitly opts into compiler preparation, what metadata it
  supplies, and when the ordinary runtime construction path remains necessary.
- Keep notation and initialization independent of the optimized representation;
  v0.2 must not require this future compiler support to work correctly.

This is separate from the [Result auxiliary-data proposal](result_auxiliary_data.md).
