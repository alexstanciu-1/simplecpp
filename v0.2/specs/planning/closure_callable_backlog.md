# Closure and callable backlog

Doc Status: planning

The [closure specification](../../../specs/language/closures.md) owns semantics. This backlog tracks the active v0.2 compiler. Fixed arrays and runtime helper publication have separate owners.

| Work | Preflight and acceptance |
| --- | --- |
| Implemented · Contextual signatures at typed callback arguments | The selected argument slot already supplies a canonical callback type. Represent an omitted annotation by absence in the existing parameter or result field. Read the corresponding component once during lambda preparation. Reuse the ordinary exact-signature check, capture rules and native function-pointer ABI. Exercise partial annotations, named slots, void results, cross-file signature edits and repair. |
| Verified · Captured populated-vector result | The current compiler runs the donor case. Retain a native regression for the creation-time snapshot and ordered elements. No compiler change required. |
| Implemented · PHP array-callable diagnostic | Refine the error for a collection literal supplied to a typed callback. Use the existing destination and collection syntax. Retain ordinary collection behavior and the native repair test. |
| Implemented · Lexical reference captures | Inferred PHP block closures borrow stable named storage, including aliases and enclosing captures. The native proof covers mutation, caller value facts, nested readonly propagation, move-only storage, attempted escape and incremental repair. Closure copies and inline-field aliases depend on their own storage boundaries. |
| Implemented · Borrowed receiver | Explicit PHP blocks borrow `$this` and arrows infer receiver use from the selected expression or instance method. Class and struct receivers follow the same rule. Nested blocks, static lexical access, constructor initialization, erased callback rejection and incremental static/instance edits have native proofs. |
| Implemented · Owned callable locals | Fixed by-value PHP `function<R(Args...)>` locals use `std::function`. Contextual initialization, copy, replacement, absence, reset and positional invocation use the existing preparation pass. Reference environments, borrowed receivers and defaults reject at erasure. |
| Implemented · Free-function callable factories | PHP free functions return an owned callable by value with the existing local conversion. The native proof covers snapshot lifetime, borrow rejection, absent returns, nominal inputs, cross-file arity edits and recovery. |
| Implemented · Free-function owned callable parameters | Required by-value parameters supply the signature for inline closures and named callable references. The native proof covers independent reset, named argument evaluation order, forwarding absence, and signature edits and repair. |
| Implemented · Static method owned callable parameters and returns | Static methods reuse the free-function ownership checks. Native revisions exercise late static dispatch, exact redeclarations, selected parameter names and cross-file signature repair. |
| Implemented · Instance method owned callable parameters and returns | Interface and base-class calls use exact callable contracts. Native tests cover dynamic dispatch, cross-file override edits and recovery, escaping field snapshots and rejected receiver borrows. |
| Implemented · Owned callable instance fields | Class and struct fields default to absent, use contextual assignment and support invocation through a parenthesized field read. Native revisions cover replacement during argument evaluation, absent calls, rejected borrows and cross-file signature repair. |
| Implemented · Owned callable static properties | Each declaring class or struct owns one initially absent slot. Native revisions cover cross-file replacement during a call, borrowed captures, absent invocation and signature repair. |
| Implemented · Typed callable containers | Vectors and hashes store fixed-signature owned callables. Native tests cover indexed selection during replacement, copies, lazy keyed presence, borrowed-capture rejection and cross-file signature repair. |
| Richer signatures | Reference inputs and returns require per-parameter modes, return origins and alias checks through indirect calls. Variadics additionally require pack binding and ordered conversion. Preserve positional full-arity invocation of today's fixed typed callbacks. |

Contextual completion uses the existing preparation pass and callback ABI. The vector regression and the diagnostic add no work to successful compilation. The remaining items need lifetime, representation or calling-convention work. Reassess those designs and measure representative callers before enabling them.

## Next design checkpoint

The capture node uses one mode flag and its existing resolved initializer link. The emitter uses a native reference init-capture per borrowed name. Typical Clang storage cost is one pointer per reference capture, subject to ABI layout. Preparation resolves access once per distinct borrowed slot and records its ID on the lambda when writable. A direct call visits only those IDs. Inferred closure locals stay in their declaring scope. A factory return uses the local erasure check to reject borrowing environments. An owned callable may itself be captured by value. Its target has already passed the erasure check. The local boundary keeps the existing restrictions on capturing a concrete callable local.

## Preparation measurement

The [preparation probe](benchmarks/callables/prepare.php) builds 400 ordinary locals and 128 inline callback arguments. It parses a fresh AST outside each timed interval, then times only declaration and body preparation. PHP 8.5.7 CLI, Xdebug disabled. Six rounds used the same CPU and alternated the order of the three variants. Each process took eight warm-ups and 101 measurements. The table gives the median of the six process medians and their range. Run with `XDEBUG_MODE=off php v0.2/specs/planning/benchmarks/callables/prepare.php explicit` or use `context` as the final argument.

| Source | Bytes | Median ms | Process medians, min–max ms |
| --- | ---: | ---: | ---: |
| Explicit, baseline `4f250671f` | 12,981 | 7.740 | 7.310–8.144 |
| Same explicit source, updated compiler | 12,981 | 7.606 | 7.327–8.118 |
| Omitted annotations, updated compiler | 11,957 | 6.967 | 6.857–7.873 |

The explicit-source ranges overlap. The updated median was 1.7% lower on this fixture. The contextual case uses the selected type directly and avoids resolving written annotations. These timings cover this fixture's preparation stage only. [Native contextual, vector and incremental proofs](../../compiler/tests/capture_free_lambdas_native.py) · [Array-callable repair proof](../../compiler/tests/named_callbacks_native.py)

For lexical reference captures, the same explicit fixture took 7.739 ms at `c6354f0b8` and 7.705 ms with the change (median of four pinned process medians per version, alternating pair order, after native tests finished). Ranges were 7.651–7.931 and 7.585–7.859 ms respectively. This measures preparation for unchanged callers.

For borrowed receivers, the same unchanged fixture took 7.662 ms at `ed824667c` and 7.575 ms with the change (median of four pinned process medians per version, alternating pair order). Ranges were 7.637–7.815 and 7.521–7.623 ms respectively. The fixture measures preparation of capture-free callers. Receiver detection runs during existing expression and method resolution.

For owned callable locals, the unchanged 12,981-byte callback fixture took 7.973 ms at `065e0f992` and 8.017 ms with the change (+0.6%, median of four pinned process medians per version, alternating pair order). Ranges were 7.676–8.256 and 7.744–8.832 ms. This measures common preparation overhead. Native tests cover target copying, replacement during argument evaluation, destruction of the creating scope, and the absent-call exception.

For free-function factory returns, the unchanged 12,981-byte fixture took 8.285 ms at `094bae64c` and 8.194 ms with the change (median of four pinned process medians per version, alternating pair order). The ranges overlapped at 7.850–8.888 and 8.060–8.515 ms respectively. The native closure suite ran concurrently with part of the benchmark.

For by-value owned callable parameters, the unchanged 12,981-byte fixture took 7.963 ms at `fb587a806` and 7.627 ms with the change. These are medians of four pinned process medians per version, alternating pair order after native tests completed. The ranges overlapped (7.619–8.151 and 7.600–7.950 ms). This measures preparation for existing callback arguments.

For static method callable signatures, the same unchanged fixture took 9.318 ms at `4f5019d31` and 11.101 ms with the change after the native tests completed. Four pinned process medians ranged from 8.983–12.272 and 10.115–12.027 ms respectively. An earlier run overlapping native tests gave 9.508 and 8.450 ms. The direction changed between runs, so these measurements do not establish a reliable preparation cost.
