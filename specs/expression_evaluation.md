# Expression evaluation — v0.2
Doc Status: normative

Agreed 2026-10-10. This amendment defines eager binary operand sequencing for
Simple C++ v0.2. It supersedes the previous order-independent operand restriction
and unspecified ordering of operand failures, including integer exponentiation.
It is a stronger guarantee than the general expression-order rules of PHP or C++;
it does not promise that the legacy generator implements this amendment.

## Eager binary operations

For a supported eager binary operation:

1. Evaluate the left operand once and complete its selected operand conversion.
2. Preserve that converted value, then evaluate the right operand once and complete
   its selected operand conversion.
3. Perform the selected operation on those two values.

A failure stops subsequent evaluation, conversion and computation. Earlier effects
are not rolled back. If the operation itself fails, both operands have already
completed. A right-side write cannot retroactively change the left scalar value.
Snapshots obey ordinary type semantics, not recursive deep-copy semantics: copying
a shared container value preserves its shared identity.

The rule applies uniformly to admitted arithmetic, power, bitwise/shift, comparison,
strict identity, concatenation and eager logical xor. It does not add operand types,
numeric promotions, overloaded operators or new assignment targets. Ordinary
expression admission still applies; operands cannot introduce implicit locals.
Presence comparisons against literal null observe the other operand once without
copying or consuming its payload or ownership handle.

Grouping and associativity still determine the expression tree. For example,
`a() + b() * c()` evaluates a, b, c in that order, computes the product, then adds.
Power remains right-associative, without reversing operand evaluation order.

## Lazy expressions and other constructs

Logical and/or evaluate the left condition first and evaluate the right only if
needed. Ternary, Elvis and coalescing retain their existing branch-selection rules.
Placing them inside an eager operand does not evaluate skipped branches. Both
operands/branches remain subject to compile-time validation even when unreachable
under a constant condition.

Assignment, calls, construction, indexing and removal retain
their separately documented receiver/argument/key/store contracts. This amendment
does not change their admission rules.

## Compound updates — 2026-10-10

For an admitted compound assignment, resolve the writable destination once and
retain any required object owners. Read and preserve its old value, including
the selected left operand conversion, before evaluating the RHS once. Compute
with those values, complete the write-back conversion/check, then store into the
original destination. The expression returns a value snapshot of the updated
destination, not a reference. A destination/null-owner failure precedes RHS effects.

For example, if `$x` is 10 and `change($x)` sets it to 100 and returns 3,
`$x += change($x)` stores 13. On RHS/computation/conversion failure, no compound
write-back occurs; earlier RHS effects remain. Numeric mixed/dynamic stored-kind
validation uses the preserved left value's kind, not a kind substituted by the
RHS. Existing kind-change errors remain; text concatenation keeps its text contract.

Ordinary admitted value expressions, including fields, calls, nested updates and
lazy expressions, may be RHS operands. They may not introduce implicit locals.
Indexed destinations follow the existing-element path contract in
[array semantics](array_semantics.md#v02-indexed-compound-updates).
Fixed-width compound updates remain deferred.

The AST and prepared facts retain compound-assignment intent and its selected
operator/conversions. Direct C++ `+=` is permitted only when equivalent ordering,
conversion and lifetime are established; it is not the general lowering contract.

## Lowering

The backend must enforce the source order rather than inherit native C++ operator
or function-argument ordering. Ordered converted value temporaries are sufficient.
Temporary storage and helper code may be eliminated only when observable behavior,
including conversion failures, effects and lifetime, is preserved. The requirement
does not itself mandate heap allocation or a runtime-dispatched operator path.
