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

No bracket overloads are selected in this slice. String receivers report that their
operator contract is deferred; other prepared receivers report deferred overload
resolution, including the explicit arity. Neither form publishes a fabricated result
type, access flag or generated operation. Surrounding reads, assignments and updates
must not interpret empty brackets as append. Lowering remains deferred.

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
compaction and recovery without claiming successful bracket lowering.
