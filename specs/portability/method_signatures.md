# Explicit method signatures
Doc Status: supporting

The portability converter now uses one signature parser for ordinary class
methods, expanded trait methods and declaration-only interface methods.

Supported signatures have explicitly typed positional parameters and an explicit
return type. Scalar types `bool`, `int`, `string`, `float` and literal named types
(local, qualified or fully qualified) are preserved. `void` is allowed for returns,
not parameters. Existing framework exception-name mapping applies as elsewhere.
Public/private/protected and static class methods use the same path.

This replaces a scalar-only converter restriction, not a Simple C++ restriction.
There is no declaration lookup to validate a type or infer a signature. The PHP
runtime and native analysis/compiler own type existence and compatibility.
Later update: literal class `implements` lists now pass through in the
[Source_Set slice](compiler_source_set_slice.md); indexing still does not perform
interface resolution or prove general polymorphic behavior.

Named object parameters and returns retain the existing shared class identity.
Mutating an object through a passed or returned reference affects that same object;
the converter does not copy it or turn it into a native value struct. `return;`
works in a void method through the existing statement path.

Later slices add annotated concrete container parameters/returns and
[nullable concrete returns and ordinary constructors](compiler_token_store_slice.md).
The remaining list below describes this original slice only.

Originally outside this slice: untyped parameters, nullable/union/intersection types,
array/callable/mixed/object/iterable boundaries, self/parent/static type spellings,
by-reference/variadic parameters, parameter defaults, generic signature annotations,
ordinary constructor bodies and standalone function declarations. These are
converter coverage/contract boundaries, not blanket target restrictions. Float
signature support does not add floating-point literals or general numeric semantics
to the expression parser. Existing promoted-constructor support is unchanged.

## Proof

```bash
python3 tests/portability/method_signatures.py \
  --target-checkout /tmp/scpp-v0.1.76-probe \
  --results FRESH_RESULTS_DIRECTORY
```

The fixture passes a cross-namespace record to a trait method, returns that same
record, mutates it through a void method, and checks that a separate record stays
unchanged. It also exercises static returns, local named types through a private
method, float parameter/return spelling, and interface parameter/void declarations.
PHP and native execution must match independently specified output:
`5:1:same:same:number`. Negative cases exercise both checking and conversion.

[Recorded evidence](../planning/compiler_migration/results/method-signatures-01/summary.json)
retains command logs, PHP/PHP++ sources and the runner.

The runner records commands, generated source and results against the pinned
v0.1.76 revision. This is a tool capability slice; it adds no production compiler
files to the ready set and no compiler functionality.
