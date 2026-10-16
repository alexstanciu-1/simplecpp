# Integer decrement in convertible PHP
Doc Status: supporting

The converter preserves PHP's `T_DEC` token through the ordinary expression-token
path, just as it preserves `T_INC`. Both `--$value` and `$value--` are admitted;
the converter does not rewrite them to subtraction or duplicate the target.

The proved authoring domain is ordinary integer locals and integer fields,
including discarded statements and integer `for` updates. Prefix returns the
updated value; postfix returns the previous value. A receiver-producing call is
evaluated once. Negative literals and subtraction of a negative literal remain
separate syntax.

Admission is structural, not a promise that every PHP decrement operand is valid
on the target. PHP parsing owns malformed mutation syntax; target operator/type
checking owns invalid typed operands. In particular, the proof demonstrates that
a string decrement can pass conversion and fail native compilation. Strings,
mixed/nullable operands, unsigned-width boundaries, keyed-element mutation and
expressions with competing side effects are outside this demonstrated contract.
Integer overflow equivalence with PHP is not claimed.

The downstream generator already emits native prefix/postfix decrement through
its assignment-target path. Integer prefix decrement updates the value directly;
postfix also takes a scalar snapshot for its result. Neither introduces a heap
allocation. Prefer prefix when the old value is not needed. No measured speedup,
layout change or memory reduction is claimed over `-= 1`.

## Focused proofs

From the repository root, with fresh results directories:

```sh
python3 tests/portability/decrement.py --results /tmp/decrement-proof
python3 tests/portability/decrement_compiler.py --results /tmp/decrement-compiler-proof --baseline /path/to/pre-migration-baseline
```

`decrement.py` checks independent PHP/native expected values for positive, zero
and negative integers, prefix/postfix results and mutation, fields, fresh objects,
single receiver evaluation, discarded statements and loop updates. It also checks
checker/converter agreement, reuse, failure publication, invalid native operand
rejection, generated C++ shape and the retained signed-field regression proof.
The latter keeps `--1` invalid as a field default.

The compiler proof's baseline directory must contain a `control_flow` subdirectory
populated by the pre-migration `compiler/my-try/tests/control_flow.php` suite. It
compares those artifacts, rebuilds my-try, and tests empty, single and multiple
`for` conditions, including a false final condition and observable earlier effects.
It compares PHP/native compiler output and executes emitted programs against
independent expected results. Both migrated source files and their generated
prefix operations are checked explicitly.

These proofs use the shared runner and the my-try `--no-stan` native workflow;
LLVM is excluded. See the dated [compiler review](../../compiler/my-try/docs/planning/conversion_quality.md)
for actual execution evidence and limits.
