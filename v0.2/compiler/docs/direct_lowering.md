# Direct scalar lowering without a preparation pass

Doc Status: supporting

Step 1 removes unnecessary sequencing wrappers inside the C++ backend. Source
semantics, preparation algorithms, AST fields and dependency watches are unchanged.
The owning evaluation contract remains [ordered evaluation](../../../specs/expression_evaluation.md).

## Bounded safety proof

The emitter computes a temporary `reorderable` result alongside each expression's
C++ text in its existing recursive traversal. It is an output parameter, not an
AST fact or retained cache, and requires no second walk or callee-body inspection.
Reordering is permitted only when it cannot be observed within the currently
supported language and defined native arithmetic domain.

- Generated `cpp_trivial_value()` classifies native scalar copies as nonthrowing,
  nonallocating and free of user callbacks. Strings and unknown types default false.
- Literal and ordinary scalar-name reads inherit this property. The current source
  language has no volatile/atomic reads or concurrent unsynchronized global writes.
- A binary subtree is safe only if both children are safe and the generated
  `cpp_binary_reorderable()` admits its lowering strategy. Current primitive native
  comparisons and int64 `+ - *` qualify. Checked division/remainder do not.
- Source calls always return false, even if their arguments permit direct spelling.
  No assumption is made about the callee's effects or exceptions.
- Function invocation omits its argument wrapper only when all arguments are safe,
  trivial by-value values with identity conversions. References, string copies and
  every nonidentity conversion retain ordered temporaries. Default arguments obey
  the same rule. Trivial scalar temporaries need no rvalue casts; strings keep them.

Generated properties derive from already-validated storage and lowering strategies;
there is no runtime metadata interpretation or handwritten source-type admission
matrix in the backend. Inventory version is 11. No algebraic reassociation, constant
folding, unsigned wrapping or new signed-overflow behavior is introduced. Native
floating comparisons retain their contract; this does not introduce floating
exception-environment controls.

Conservative warning handling retains snapshots for identical operand spellings,
so valid self-comparisons do not trigger Clang's `-Wtautological-compare`. This also
leaves some identical-spelling arithmetic wrapped. Direct boolean binary conditions
omit their expression's outer grouping pair because the condition statement already
supplies it; generated boolean identity truthiness adds no redundant pair. Warning
flags remain unchanged, including `-Werror`.

## Examples and boundary

```cpp
// Ordinary scalar reads have no intervening observable effects.
subtotal = subtotal + index;
// The original expression tree remains grouped; no reassociation.
subtotal = (price * 162LL) + quantity;
```

`value + change(value)` keeps its ordered snapshots. Likewise `(1 / 0) + later()`
keeps sequencing so the left failure suppresses the later call. A string argument
is copied before a subsequent reference argument can mutate its source.

This is not statement-based lowering. Necessary lambdas stay in their original
expression positions, so while/do-while tests still execute on each test and
else-if tests remain conditional. Flattening those sequences into prerequisite
statements is deferred. Further evaluation-order enforcement is
[paused as debt](../../specs/planning/evaluation_order_debt.md); it is not the
next scheduled lowering step. The language guarantee itself remains open for
future review, with no specification change made yet.

## Proofs

`tests/direct_lowering.php` checks direct scalar operations and identity/default
arguments, conservative throwing operations, AST/preparation purity and retained
string move handling. Its native runner covers all three flavors, value and string
snapshot semantics, calls, loops, conversion failure ordering and competing failure
ordering. Existing arithmetic/comparison proofs retain all-flavor incremental
signature-change/recovery tests. The capability benchmark repeats retained-session
no-op/body-edit/signature-error/recovery checks for all three workload sizes.

No compiler preparation structures were added or changed. Existing type definitions
have two backend-only generated queries; `Cpp_Emitter::value()` has one transient
boolean output. Timing/size comparisons belong to the capability sample report.
