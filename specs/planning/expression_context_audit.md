# Expression composition and context audit

Doc Status: planning

## Plan and revision

Review committed GAPS revision `9b9626589` from the sibling checkout. Use a clean archive for execution so neither checkout's local edits affect observations. The initiating checkout was at `af6808a4d`. The later field-append experiment in GAPS was uncommitted at selection time.

1. Trace how value and statement entry points parse bracket, member and call suffixes. Inspect the tree for the user's PHP example.
2. Compare the same subexpression in read, argument, receiver, callee and write positions. Vary one role or destination type at a time.
3. Check preparation's requirements and the facts lowering consumes. Examine single evaluation, callee capture, write-back and owner retention.
4. Execute representative programs, inspect generated code, run relevant flavor and incremental regressions, and record the first failing stage. Distinguish native behavior, diagnostics, deliberate restrictions and static inferences.

## Verdict

The exact PHP expression parses with the intended grouping and executes when supplied with appropriate types and populated containers. Postfix syntax is compositional for several operations, including member calls and nested indices. Its composition depends on flavor, the kind of the expression on the left, and whether parsing started in statement or value position. Write preparation has stronger storage requirements than value preparation. The compiler does not consistently settle the write-back strategy once for lowering to consume.

The earlier silent lost write on a typed vector of vectors is fixed on this revision. The audit identifies a reproducible difference in where equivalent writes are rejected and a concrete risk of future drift between preparation and lowering. The new native probes did not reproduce another silent lost write.

## Anchor traced end to end

The parser independently produced this tree from the original undeclared PHP statement. Name resolution was deliberately unnecessary for this check.

```text
indexed callable(
    index(
        index($something, index(func(index($an_expression_may_go_here, 12)), 10)),
        a_constant),
    $arg_1)
```

Here `func` has a receiver and no arguments. The indexed-call node has the entire second outer index as its receiver and one argument. The native fixture provides thirteen selector objects, an eleven-element integer vector returned by the method, a declared constant and a nested vector containing an owned callable. It executes the literal expression and observes the method and callee. A second fixture replaces the indices and argument with traced variants. Both the statement and value forms evaluated the inner index, method, argument and callable once in that order. The value form returned 8.

Preparation checks receiver and key types at each index. It resolves the method on the selector and binds its result as the next receiver. At the outer call it checks that the selected type is an owned callable and binds arguments against its signature. In the emitted C++, the inner indexed object is held in `receiver_owner` during the method call, and the outer selection initializes a `const auto selected_callable_…` before the argument executes. A separate probe replaces the callable from inside the argument. The pending invocation returned 8 from the old target; the next invocation returned 26 from the replacement. Changing only the provider method's result from a vector of integers to a vector of strings invalidated the outer index. Repair restored the original native trace. The resident and fresh compiler produced identical diagnostics or artifacts at each revision.

## Evidence by context

| Position | Observation | First deciding stage and interpretation |
| --- | --- | --- |
| Computed index and method receiver | An indexed selector supplies a method receiver. The method result supplies another receiver or an index. | PHP literal AST and native behavior agree. C++, JS and Python controls also executed an indexed object’s method as the key for another vector. A vector with a string key is rejected during preparation. |
| Callable selection | PHP indexed call as a statement and as a value succeeds. Indexed integer and wrong argument count both parse. | Preparation rejects the former for its callee type and the latter for its signature. Lowering preserves selection across argument effects. |
| Parentheses and call-result callee | PHP `$f(6)` and `($ops[0])(6)` run. `($f)(6)` and `makeOp()(6)` stop at the second opening parenthesis with “Expected ;”. Assigning the returned callable to a local and calling it runs. | Parser limitation. Local and returned callable values are representable. The available contract documents particular invocation forms; treating every callable-valued expression as invocable would require a language decision. |
| Value and reference arguments | Passing `$v[0]` to a by-value integer parameter runs. The same expression for an ordinary writable integer reference parses and is rejected for unstable storage. | Deliberate preparation distinction. The reference-parameter contract restricts eligible storage. |
| Lazy operand | An effectful key in the RHS of `false && $v[noisy()]` never runs. | Native trace checks short-circuiting. Eager binary operands have a different source ordering contract. |
| Indexed class owner, field, index | Reading `$owners[0]->values[0]` returns 3. Direct assignment to that chain stops in parsing with “Expected call statement”. Grouping the indexed owner, `($owners[0])->values[0]=7`, gets through parsing and is rejected in preparation for lack of writable local storage. Assigning the selected class handle to a local and writing through it updates the original holder to 7. | Confirmed statement-parser inconsistency and misleading diagnostic. The grouped variant exposes a separate semantic storage restriction. Relaxing the parser alone would not settle whether the direct write is admitted. The control uses a shared class handle; copying a by-value struct would change its meaning. |
| Historical compound and postfix update | The PHP, C++ and JS typed vector fixtures each read back 8 after starting at 3, adding 4 and incrementing. | Generated PHP-flavor C++ reselects a checked row slot and calls its setter. This verifies the stored destination as well as the arithmetic. |
| Python typed versus shared boxed paths | A nested typed-vector read succeeds. Nested plain assignment on that typed vector reaches preparation and is rejected. A Python dynamic list-of-lists accepts the write and observes the new value. | Matches the narrower documented Python typed-write surface. The diagnostic names a typed local path among its alternatives without identifying the flavor restriction. |
| Indexed callee in other flavors | A return statement containing `ops[0](6)` in C++, JS and Python stops at the opening parenthesis after the index. | Parse-only comparison; names intentionally unresolved. The PHP-only postfix branch explains the result. No inference about those flavors' entire callable type systems. |

## Stage ownership and priorities

1. **First priority for correctness: settle the destination once.** On the pinned revision `ast_index_expression` represents a value-copy read. An assignment or update wraps its receiver and keys in a mutation context. Preparation calls `nested_collection_root` or `boxed_path_root`, checks readonly access and eligible storage, and settles conversions. The C++ emitter consults those same helpers again to choose between ordinary access, a checked slot path, and boxed child read/write-back. Sharing a helper reduces duplication, but the caller still chooses e.g. whether a field is allowed in that operation. The historical failure shows the consequence when an update uses value-read lowering. Consider attaching a small destination-kind fact to an admitted mutation statement; use the existing AST for keys and receivers. Before changing representation, verify preparation, invalidation and re-preparation for each affected statement. A separate descriptor on every read would need independent justification.
2. **First priority for diagnostics: finish recognizing the target before judging its storage.** The statement dispatcher sends certain bracket-led continuations through a different path from the ordinary value parser. The field/index pair above reaches different stages solely because of grouping. Add the paired forms as a regression oracle when addressing that dispatch. Decide separately which retained-owner paths the language admits, including how a shared handle differs from an inline struct copy. The user should see a target-specific diagnostic for an intentionally excluded shape.
3. **Separate language decision: invocation of callable-valued expressions.** PHP already supports several callee spellings, and the backend has an owned-callable snapshot operation. Decide whether grouping a local and immediately invoking a returned callable should inherit that contract. If supported, cover receiver selection before arguments, absence, and reference-return behavior. Each other flavor needs its own admission decision.
4. **Protect the behavior already established.** Keep tests that replace the root, remove the child, replace the callable from an argument, drop the last owner, or change the provider signature between incremental builds. Mere acceptance or a single successful load cannot tell whether the eventual write or call uses the correct object.

## Source evidence at the pinned revision

- [Postfix loop and indexed statement dispatch](https://github.com/alexstanciu-1/simplecpp/blob/9b9626589/compiler/03_parse/parser.php#L1496), [grouped PHP callee recognition](https://github.com/alexstanciu-1/simplecpp/blob/9b9626589/compiler/03_parse/parser.php#L2120)
- [Indexed read and storage-root helpers](https://github.com/alexstanciu-1/simplecpp/blob/9b9626589/compiler/03_parse/structures/collections.php#L111), [write preparation](https://github.com/alexstanciu-1/simplecpp/blob/9b9626589/compiler/04_analyze/prepare/prepare.php#L318), [stored-callee preparation](https://github.com/alexstanciu-1/simplecpp/blob/9b9626589/compiler/04_analyze/prepare/prepare.php#L2707)
- [Indexed mutation lowering](https://github.com/alexstanciu-1/simplecpp/blob/9b9626589/compiler/05_backend/cpp/emit.php#L2204), [stored-callee capture](https://github.com/alexstanciu-1/simplecpp/blob/9b9626589/compiler/05_backend/cpp/emit.php#L3574)
- [Indexed callable contract](https://github.com/alexstanciu-1/simplecpp/blob/9b9626589/specs/compiler_entry_v0_2.md#L438), [nested vector writes](https://github.com/alexstanciu-1/simplecpp/blob/9b9626589/specs/vector_slice.md#L10), [evaluation rules](https://github.com/alexstanciu-1/simplecpp/blob/9b9626589/specs/expression_evaluation.md)

## Verification and limits

The [audit transcript](probes/expression_context/dev002.txt) records the individual observations, including the cross-file recovery. The clean dev002 default CMake build passed all 497 default tests. These include nested vector and boxed writes, replacement and removal, retained field ownership, readonly rejection, callable collections, and incremental edit/recovery fixtures. The audit-specific [driver](probes/expression_context/probe.py) and [syntax probe](probes/expression_context/parse.php) use the actual frontend, incremental service, production C++ emitter and native build helper. From the configured checkout root run `XDEBUG_MODE=off python3 /path/to/probe.py /new/scratch/directory`, keeping parse.php beside it. The native helper checks exit code 8, exact stdout and empty stderr. Negative probes assert a stage and diagnostic fragment. The first standalone parse separates grammar failures from later failures; the source checks above identify the relevant preparation decisions.

Scope is expression composition in the cited contexts. The default suite is supporting evidence, not an exhaustive combinatorial audit. Dynamic property names, every overload/operator, coroutine suspension in newly composed chains, exceptional evaluation at every operand, and every lifetime or incremental interleaving have not been independently certified. The results apply to the pinned commit; later GAPS edits require a separate delta check.
