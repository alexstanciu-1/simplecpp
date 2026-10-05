# Bracket operator model
Doc Status: normative

Agreed 2026-10-02 for the my-try v0.2 frontend. Brackets are one overloadable
operation with a receiver and zero or one explicit argument:

- `base[expression]` invokes the one-argument form of `operator[]`.
- `base[]` invokes the zero-argument form of the same operator.

The optional argument is an ordinary expression. Empty brackets supply no argument;
they do not supply a null value, sentinel, implicit index or append instruction.
Multiple comma-separated bracket arguments are outside this slice.

The selected type/operator contract must determine the operation's meaning, result
type, effects and result access properties. The compiler must not infer append,
write-only use, container mutation, integer indexing or writability solely from
bracket syntax. A future overload may give the zero-argument form any supported
meaning. General user-defined operator declarations and resolution are separate work.

## Current frontend boundary

Both forms use the existing `index_node`: `base` is required and `index` is an
optional expression. The syntactic assignable base class permits parsing assignment
targets; it is not evidence that a selected operation returns writable storage.
Postfix suffix parsing is shared by variables, literals, grouped expressions and
call results. Inspection and maintenance visit the receiver followed by the argument
when present.

`Operator_Preparation::prepare_index` prepares the receiver and optional argument,
then sends `operator_kind::index` to the shared operator-decision boundary. Its
operand-type vector places the receiver first, followed by zero or one argument.
Nested expressions retain their own semantic and effect restrictions. Bracket
preparation does not introduce local declarations or bypass enclosing arithmetic's
existing restrictions on effects.

The v0.2 one-argument read slice selects registered vector/hash/mixed/dynamic
contracts. Type-definition identity supplies key/result substitutions and either
checked-access or null-on-miss policy; initialization opt-in does not grant indexing.
The agreed [read contract](array_semantics.md#21-value-read-path) defines exact types,
key admission and runtime failure behavior. Selected reads retain an operator decision
on `prepared_index_expression`; they are non-addressable values regardless of the
syntax node's assignable base class. Ordinary typed consumers convert that value
after lookup, not by changing lookup policy.

String indexing, unregistered receivers, zero-argument brackets, indexed writes,
updates, native reference arguments and keyed probes remain deferred. Effective
storage modifiers and broader key families are not admitted by this bounded slice.
Surrounding assignments/updates must not infer append from empty brackets.

## Runtime and legacy boundary

The existing [runtime string-index reservation](../runtime/specs/spec.md) remains
unchanged. Byte versus code-point indexing, result types, bounds behavior and writes
must be settled by the future string operator contract. Existing legacy generator
container/append behavior does not define this canonical bracket model.

LLVM remains parked. Its existing fixed-array one-argument path is unchanged; an
absent argument now receives an explicit unsupported-lowering diagnostic rather than
a nullable dereference. This does not implement the canonical overload model there.

[Focused tests](../compiler/my-try/tests/indexing.php) prove both arities, expression
arguments, postfix bases, traversal, shared diagnostics, retained syntax/collection,
compaction, read facts, deferred write/reference uses and incremental recovery.
`compiler/my-try/tests/container_reads.py` owns focused generated-C++ read proofs.
