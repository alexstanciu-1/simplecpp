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

Assignment through a local/parameter-rooted path selects an independently
registered indexed-store or append operation. Empty brackets select append only
when that receiver's contract permits it; a zero-argument value read is still gated.
`prepared_index_assignment` records the operation and element conversion, not a
native slot reference or a local-variable binding. Construction and writes share
container-element admission and contextual nested-literal typing. See the
[write contract](array_semantics.md#22-write-path) for ordering and failure guarantees.

Nested targets retain ordered `prepared_index_path_step` facts under the assignment:
each references its existing bracket syntax and records the key conversion and
registered traversal result type. Traversal capability is separate from value reads
and construction opt-in. In particular, dynamic traversal retains a dynamic child
contract; ordinary dynamic value reads also return dynamic under the approved
separated-family contract (runtime/compiler migration pending). Path brackets are
not prepared or emitted as copied value reads.

String indexing, unregistered receivers, zero-argument value reads, intermediate append,
indexed updates and native reference arguments remain deferred.
Effective storage modifiers and broader key families are not admitted by this slice.

Keyed `isset`/`empty` use their existing value-probe node with an optional
`prepared_keyed_path`. A dedicated `unset_node` statement owns the same non-creating
path fact shape. These operations are independently registered, not inferred from
readability or writability. The [probe contract](count_empty_isset_contract.md#v02-keyed-evaluation-order)
defines lazy keys and safe prefix re-resolution; the [removal contract](array_semantics.md#v02-keyed-removal)
defines no-op versus error behavior. Vector-element removal, bare-variable unset,
properties, multiple operands and weak/cross-family paths remain gated.

## Optional key inputs

A typed optional key expression uses the existing checked by-value argument conversion
into the selected operation's required key type. Its payload must satisfy the existing
key-domain rules, and any object ancestry conversion must be independently admitted.
Absence fails before that indexed step or a write's value expression; it does not
become a null key. This applies to reads, writes, nested paths, probes and removal,
while preserving lazy probe-key evaluation. It does not admit nullable stored key
types or PHP bool/float/null key coercion. The focused nullable_keys.py proof covers
ordinary and prelowered execution, identity upcasts and failure ordering.

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
compaction, read/write/path facts, deferred append/reference uses and incremental recovery.
`compiler/my-try/tests/container_writes.py` owns focused generated-C++ read/write proofs.
`compiler/my-try/tests/keyed_paths.py` proves lazy probes and keyed removal.
