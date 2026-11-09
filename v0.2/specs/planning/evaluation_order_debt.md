# Expression evaluation order debt

Doc Status: planning

Decision: 2026-11-04. Defer further evaluation-order enforcement work in the PHP
bootstrap compiler. Its implementation complexity and development cost are
displacing more useful compiler work.

Keep the existing lowering behavior for now. Do not proceed with the proposed
statement-based expression splitting, lambda elimination for sequencing, or
additional effect/reference analysis solely to enforce evaluation order. New
feature slices should record ordering gaps here rather than expand sequencing
machinery as an implicit prerequisite.

Decision on 2026-11-08: eager binary operand order follows the native C++ implementation.
The [expression evaluation contract](../../../specs/expression_evaluation.md) records
the decision. Existing sequencing tests can verify a particular lowering. Checked
integer division and remainder now pass safe, identity-converted operands directly
to the runtime helper. The enclosing emitter still treats the call as potentially throwing.

Current implementation: [direct scalar lowering](../../compiler/docs/direct_lowering.md).
No compiler code, structures or existing tests change with this deferral.

Vector replacement now uses a direct native member call. The index and RHS can
execute in either order, including when one throws. Bounds checking and mutation
remain inside the member, after both arguments succeed. See the
[native lowering inventory](native_cpp_lowering.md).

## Alternative to evaluate later: GNU statement expressions

GCC/Clang statement expressions could replace sequencing lambdas without moving
prerequisite statements out of their expression positions:

```cpp
({ auto left_value = a(); auto right_value = b(); left_value + right_value; })
```

This sequences the statements inside the block. Separate statement expressions
used as sibling operands or call arguments do not establish their relative order;
an ordered call would need argument evaluation and invocation inside one block.

This is a compiler extension, not portable standard C++. GCC documents by-value
C++ results and temporary-lifetime differences, so strings, reference-valued
results, copies/moves and destruction require explicit verification. Keep required
conversions, runtime checks, reference binding and lazy branch behavior intact.
See [GCC statement expressions](https://gcc.gnu.org/onlinedocs/gcc/Statement-Exprs.html)
and [Clang extensions](https://clang.llvm.org/docs/LanguageExtensions.html).

The potential benefit is less frontend/optimization work from eliminating lambda
closure types and call operators. Existing optimized lambdas may already have no
runtime abstraction cost; neither runtime nor compile-time improvement is proven.
Explicit sequencing still permits optimizations that preserve observable behavior.

If resumed, start with an isolated scalar-result experiment and compare generated
size, Clang frontend, LLVM, linker and native wall times, plus ordering/correctness
checks. Do not blindly replace all lambdas or extend this experiment to object and
reference results. This is an option for later, not an approved implementation or
benchmark task; current lowering and the specification remain unchanged.
