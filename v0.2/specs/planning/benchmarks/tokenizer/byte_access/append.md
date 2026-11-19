# Token construction and append on operator-heavy input

Doc Status: supporting

The operator corpus is 8,192 bytes and emits 2,560 tokens per scan. Each generated variant keeps the split checked byte view, 24-byte token, previous-count reserve, retained source and last-use move. The four variants cross two choices: assigning fields to a named token versus passing an aggregate, and the stock by-value append versus a small reserved-capacity path with the original append algorithm in an out-of-line fallback. Both append paths handle empty storage and growth. Handwritten C++ uses its regular std::vector::push_back, its own recognition loop and source ownership. Only comparisons between generated variants isolate the two changes.

dev002, Clang 18.1.3 and GCC 13.3.0, -O2, separate translation units, no LTO. Each rate is the median of 27 batches of 128 complete warm scans including destruction. Variant order is shuffled between groups of nine batches. Allocation tracing uses separate executables.

## Operator throughput in MiB/s

| Compiler | Flavor | Named, stock | Aggregate, stock | Named, fast | Aggregate, fast | Handwritten | Named fast / stock | Named fast / handwritten |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| clang++ | cpp | 347 | 344 | 376 | 378 | 1224 | 1.08× | 31% |
| clang++ | php | 285 | 352 | 381 | 390 | 1267 | 1.34× | 30% |
| clang++ | js | 353 | 344 | 382 | 475 | 1235 | 1.08× | 31% |
| g++ | cpp | 746 | 747 | 722 | 718 | 1014 | 0.97× | 71% |
| g++ | php | 694 | 663 | 741 | 739 | 1054 | 1.07× | 70% |
| g++ | js | 733 | 742 | 740 | 734 | 1059 | 1.01× | 70% |

## Other measured costs

Median of the three paired flavor ratios per compiler. Throughput ratios compare the named fast path with named stock. Compile wall times are scanner-only medians across flavors, each flavor measured three times in fixed build order.

| Compiler | Identifiers | Operators | Mixed | Compile ms (stock / fast / handwritten) |
|---|---:|---:|---:|---:|
| clang++ | 1.04× | 1.08× | 1.30× | 305 / 305 / 372 |
| g++ | 0.99× | 1.01× | 1.00× | 251 / 253 / 289 |

## Native work and verification

With the stock append, Clang's optimized cpp scanner stores offset, length and kind into a stack record, copies the by-value argument onto the outgoing stack and calls append. The reserved-capacity branch in the callee transfers the 24-byte record to the vector. Aggregate construction retains that by-value boundary. There is no separate token zeroing at this site in the inspected machine code. The small fast path exposes the destination to the scanner and branches to append_slow only for absent or full storage. Clang IR still contains a 24-byte transfer on the fast path. Handwritten Clang IR writes the three fields to the vector destination on the reserved path. The inspected cpp-flavor fast-path machine code still stores the fields to stack, then loads them back for the vector stores. GCC stock scanner objects contain no relocation to the public append in any flavor. GCC reported an attribute warning for the experimental noinline fallback declaration followed by its inline header definition, so small compile-time differences should be treated cautiously. None of these variants changes the operator-corpus allocation trace among the generated scanners.

All 30 binaries compare full token slices and diagnostics against the 11 PHP oracle fixtures. Recovery after an unsupported byte behind a valid prefix publishes zero partial tokens and preserves retained results. Timing inputs have capacity for the whole scan. The dump and edit checks also exercise empty inputs and changes in the previous count.

Reproduce with `python3 -B measure.py --append FIXTURE_ROOT RUNTIME WORK`. The harness retains the compiler dumps and scanner objects alongside each executable. [Raw timings, compiler versions and allocation events](append.dev002.json).

The named fast path reached 30–31% of handwritten operator throughput with Clang and 70–71% with GCC. Aggregate spelling alone gave variable results. A follow-up could test in-place construction from three scalar fields with the same capacity and growth rules, then inspect whether the stack round trip disappears. The residual gap has not been attributed to that round trip.
