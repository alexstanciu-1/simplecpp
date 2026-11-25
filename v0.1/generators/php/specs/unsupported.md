# Explicitly Not Supported / Reduced Semantics
Doc Status: normative
This section defines behaviors that are intentionally unsupported or differ from PHP.

## Rejected by Design
- Untyped null assignment
- PHP reference semantics
- unset() on non-nullable values
- full PHP array semantics (the current supported subset is documented in the array catalog/spec rows)
- foreach outside vector_t
- Dynamic properties
- Dynamic property names
- Traits
- include / require
- and/or/xor
- Untyped parameters in named functions/methods
- Untyped/mixed variadics
- Function/method overloading
- Nested wrappers

## Variadics
- Supported: typed trailing variadics, lowered as `const vector_t<T>&`
- Supported: calls to typed trailing variadics, lowered by packing trailing arguments into `vector_t<T>{...}`
- Not supported: untyped variadics such as `function f(...$values)`
- Not supported: mixed/dynamic variadics that would require a `mixed_t`-style payload
- Constraint: the variadic parameter must be the trailing parameter

## Reduced Semantics
- References are reduced (C++-like)
- Object nullability not strictly enforced
- Loose comparisons differ from PHP
- Division semantics may differ
- switch behavior may differ
- `finally` now supports delayed `return` from the protected `try` / `catch` region, but it still rejects `break` / `continue` leaving the protected region and also rejects `return` / `break` / `continue` inside the `finally` block itself

See incompatibilities.md for more.

## Closures
- Closures with `use ($x)` are supported for capture-by-value.
- Direct invocation of closure-valued variables is supported, including trailing default arguments when the lowered native closure parameter type can be emitted concretely.
- Closure bodies now reuse the supported statement lowering path for `if`, `return`, `echo`, and expression statements; unsupported inner control-flow shapes remain rejected case by case.
- Safe v1 uses block-local variable visibility: a variable is visible only in the block where it is first introduced and in nested child blocks. The first write in a block becomes a new declaration only when no visible outer variable with the same name exists.
- Safe v1 also allows explicit typed local predeclarations without initialization, for example `$f /** function<int(int)> */;`. Function-typed locals must use an explicit `function<return_type(arg_types)>` annotation rather than bare `callable`.
- Variables declared inside `if` / loop / nested statement blocks do not escape those blocks. Later use outside the declaring block is a generator error.
- Not supported yet: `use (&$x)` and broader PHP callable normalization. Arrow functions are supported in Safe v1 with implicit by-value capture, native PHP return types, and the existing callable-signature rules; variadics/default params remain unsupported.

Enums
- Late static binding remains limited to the current file-local lowering context. Cross-source-file late-static rebinding and enum late-static binding are unsupported in the current pass.

## Enum scope in the current stage

Only the initial simple-enum path is supported:
- unit enums with plain cases
- int-backed enums with literal integer case values

Not supported in the current stage:
- string-backed enums
- enum methods
- enum interfaces / traits / properties / constants beyond cases
- enum pseudo-properties such as `->name` / `->value`
- synthesized helpers such as `cases()`, `from()`, `tryFrom()`


- Safe v1 rejects conflicting type declarations when both native PHP syntax and a doc annotation try to type the same parameter/property/closure return. Use exactly one typing mechanism.
