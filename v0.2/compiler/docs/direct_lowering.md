# Direct scalar lowering without a preparation pass

Doc Status: supporting

Step 1 removes unnecessary sequencing wrappers inside the C++ backend. Source
semantics, preparation algorithms, AST fields and dependency watches are unchanged.
The owning evaluation contract remains [expression evaluation](../../../specs/expression_evaluation.md).

## Bounded safety proof

The emitter computes a temporary `reorderable` result alongside each expression's
C++ text in its existing recursive traversal. It is an output parameter, not an
AST fact or retained cache, and requires no second walk or callee-body inspection.
The emitter uses this conservative fact to select compact spellings. The generated
`type_dynamic` also admits a dynamic local with an integer literal for addition
and less-than. Its runtime helpers still check the boxed payload.

- Generated `cpp_trivial_value()` classifies native scalar copies as nonthrowing,
  nonallocating and free of user callbacks. Strings and unknown types default false.
- Literal and ordinary scalar-name reads inherit this property. The current source
  language has no volatile/atomic reads or concurrent unsynchronized global writes.
- A binary subtree is safe only if both children are safe and the generated
  `cpp_binary_reorderable()` admits its lowering strategy. Current primitive native
  comparisons and int64 `+ - *` qualify. Checked division/remainder may throw. Integer helpers take safe operands with identity conversions directly and still report the result as potentially throwing to the enclosing expression.
- Source calls always return false, even if their arguments permit direct spelling.
  No assumption is made about the callee's effects or exceptions.
- A single reference parameter binds directly to a local declaration of the exact
  type. Preparation checks writable storage. For a sole by-value parameter, the
  emitter applies the prepared conversion to the supplied argument or declaration
  default directly in the call. C++ initializes the parameter, including copying
  an lvalue string. Calls with multiple arguments can omit temporaries when all
  arguments are safe, trivial by-value values with identity conversions.

The generator derives the type-specific direct-spelling rules from validated
operation plans. Its [compiler renderer](../../tools/api_generator/php/render_compiler.php)
owns the generated inventory version. A checked operation can use direct operands
and still report a potentially throwing result to its parent.

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

Sequencing wrappers stay in expression positions where the current lowering
uses them, including loop tests and conditional arms. The
[eager operand order contract](../../../specs/expression_evaluation.md)
leaves eager binary operand order to the native C++ implementation. Lazy
operators keep their short-circuit behavior.

## Proofs

After building `scpp_compiler_publication` with the default LP64 publication, run
`ctest --test-dir v0.2/build/clang -R '^compiler_lowering_catalog$' --output-on-failure`
from the repository root. It compiles each PHP example in the
[lowering catalog](php_lowering_catalog.md) through C++ emission and compares both
its implementation excerpt and its include and header declarations.

`tests/direct_lowering.php` checks direct scalar operations and identity/default
arguments, conservative throwing operations, AST/preparation purity and retained
string move handling. Its native runner covers all three flavors, value and string
snapshot semantics, calls, loops, conversion failure ordering and competing failure
ordering. Existing arithmetic/comparison proofs retain all-flavor incremental
signature-change/recovery tests. The capability benchmark repeats retained-session
no-op/body-edit/signature-error/recovery checks for all three workload sizes.

No compiler preparation structures were added or changed. The emitter consumes backend queries during the existing expression walk. Timing/size comparisons belong to the capability sample report.
