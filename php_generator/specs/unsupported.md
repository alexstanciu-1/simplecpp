# Explicitly Not Supported / Reduced Semantics

This section defines behaviors that are intentionally unsupported or differ from PHP.

## Rejected by Design
- Untyped null assignment
- PHP reference semantics
- unset() on non-nullable values
- PHP arrays (hashtable semantics)
- foreach outside vector_t
- Dynamic properties
- Dynamic property names
- Traits
- include / require
- and/or/xor
- Untyped parameters
- Untyped/mixed variadics
- Function/method overloading
- static::$prop
- Nested wrappers

## Variadics
- Supported: typed trailing variadics, lowered as `const vector_t<T>&`
- Supported: calls to typed trailing variadics, lowered by packing trailing arguments into `vector_t<T>{...}`
- Not supported: untyped variadics such as `function f(...$values)`
- Not supported: mixed/dynamic variadics that would require a `value_t`-style payload
- Constraint: the variadic parameter must be the trailing parameter

## Reduced Semantics
- References are reduced (C++-like)
- Object nullability not strictly enforced
- Loose comparisons differ from PHP
- Division semantics may differ
- switch behavior may differ

See incompatibilities.md for more.
