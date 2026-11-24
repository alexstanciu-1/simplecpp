# Closure and callable backlog

Doc Status: planning

The [closure specification](../../../specs/language/closures.md) owns semantics. This backlog tracks the active v0.2 compiler. Fixed arrays and runtime helper publication have separate owners.

| Work | Preflight and acceptance |
| --- | --- |
| Implemented · Contextual signatures at typed callback arguments | The selected argument slot already supplies a canonical callback type. Represent an omitted annotation by absence in the existing parameter or result field. Read the corresponding component once during lambda preparation. Reuse the ordinary exact-signature check, capture rules and native function-pointer ABI. Exercise partial annotations, named slots, void results, cross-file signature edits and repair. |
| Verified · Captured populated-vector result | The current compiler runs the donor case. Retain a native regression for the creation-time snapshot and ordered elements. No compiler change required. |
| Implemented · PHP array-callable diagnostic | Refine the error for a collection literal supplied to a typed callback. Use the existing destination and collection syntax. Retain ordinary collection behavior and the native repair test. |
| Bounded reference captures | Requires the original storage owner, access mode and lifetime for each borrow, including aliases and nested captures. Preparation must invalidate outer value facts across calls that can write borrowed storage. First prove mutation, readonly propagation, escape rejection and incremental recovery with a common borrow representation. |
| Borrowed receiver | Depends on the borrow boundary above. Preserve lexical declaring-class access separately from receiver availability. Test nested closures, static contexts and the original instance lifetime. |
| Declared callable storage, factories and escapes | A native function pointer holds no captured environment. Select ownership, copy, invocation and destruction operations for a typed callable value before enabling typed locals, returns, fields, containers, clean or unset. Require transitive borrow rejection. Extend contextual completion to each storage boundary as it becomes available. |
| Richer signatures | Reference inputs and returns require per-parameter modes, return origins and alias checks through indirect calls. Variadics additionally require pack binding and ordered conversion. Preserve positional full-arity invocation of today's fixed typed callbacks. |

Contextual completion uses the existing preparation pass and callback ABI. The vector regression and the diagnostic add no work to successful compilation. The remaining items need lifetime, representation or calling-convention work. Reassess those designs and measure representative callers before enabling them.

## Next design checkpoint

Start with the provenance of one lexical borrow in an inferred local closure. Reuse the existing [writable and read-only binding checks](../../compiler/04_analyze/prepare/readonly.php). Record which caller storage can change when the closure runs, so a call can invalidate only affected value facts. Exercise a nested alias, a second closure borrowing the same binding, and a copy that would outlive its origin. Estimate storage per borrowing closure and measure unchanged callers before extending the proof to receiver capture. The [current callable type](../../compiler/types/callback.php) records a fixed signature and capture presence; capture presence alone cannot answer these lifetime questions.

## Preparation measurement

The [preparation probe](benchmarks/callables/prepare.php) builds 400 ordinary locals and 128 inline callback arguments. It parses a fresh AST outside each timed interval, then times only declaration and body preparation. PHP 8.5.7 CLI, Xdebug disabled. Six rounds used the same CPU and alternated the order of the three variants. Each process took eight warm-ups and 101 measurements. The table gives the median of the six process medians and their range. Run with `XDEBUG_MODE=off php v0.2/specs/planning/benchmarks/callables/prepare.php explicit` or use `context` as the final argument.

| Source | Bytes | Median ms | Process medians, min–max ms |
| --- | ---: | ---: | ---: |
| Explicit, baseline `4f250671f` | 12,981 | 7.740 | 7.310–8.144 |
| Same explicit source, updated compiler | 12,981 | 7.606 | 7.327–8.118 |
| Omitted annotations, updated compiler | 11,957 | 6.967 | 6.857–7.873 |

The explicit-source ranges overlap. The updated median was 1.7% lower on this fixture. The contextual case uses the selected type directly and avoids resolving written annotations. These timings cover this fixture's preparation stage only. [Native contextual, vector and incremental proofs](../../compiler/tests/capture_free_lambdas_native.py) · [Array-callable repair proof](../../compiler/tests/named_callbacks_native.py)
