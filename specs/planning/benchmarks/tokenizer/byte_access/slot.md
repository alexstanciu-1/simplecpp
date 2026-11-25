# Constructing a token in its reserved vector slot

Doc Status: supporting

All generated scanners retain the split checked byte view, last-use vector move, source ownership and previous-count reserve. Stock uses the generated append. The by-value and in-slot fast paths both check for storage and spare capacity, then use the same fallback on a miss. In-slot receives offset, length and kind separately and begins the token lifetime in the destination with aggregate placement construction. Handwritten C++ uses its own scanner and std::vector.

## Operator throughput on dev002

MiB/s, measured over 27 warm batches of 128 scans with destruction, shuffled in groups of nine. C++23, -O2, separate translation units, no LTO. The corpus contains 2,560 tokens in 8,192 bytes. Compare in-slot with by-value within a row to isolate the construction change.

| Compiler | Flavor | Stock | By-value fast | In-slot | Handwritten | In-slot / by-value | In-slot / handwritten |
|---|---|---:|---:|---:|---:|---:|---:|
| clang++ | cpp | 358 | 387 | 734 | 1555 | 1.89× | 47% |
| clang++ | php | 356 | 479 | 905 | 1582 | 1.89× | 57% |
| clang++ | js | 348 | 388 | 866 | 1251 | 2.23× | 69% |
| g++ | cpp | 751 | 729 | 731 | 857 | 1.00× | 85% |
| g++ | php | 693 | 747 | 741 | 1075 | 0.99× | 69% |
| g++ | js | 736 | 748 | 746 | 1311 | 1.00× | 57% |

Median paired in-slot / by-value throughput across the three flavors. Scanner compile wall times are medians across flavors of three invocations per variant in fixed order.

| Compiler | Identifiers | Operators | Mixed | Compile ms (by-value / in-slot) |
|---|---:|---:|---:|---:|
| clang++ | 1.16× | 1.89× | 1.27× | 300 / 304 |
| g++ | 1.01× | 1.00× | 1.01× | 250 / 252 |

## Native work and verification

On the reserved path Clang writes offset, length and kind from registers to adjacent 64-bit fields at offsets 0, 8 and 16 of the selected slot, then increments count. The prior by-value fast path staged the token in stack memory. In-slot still materializes a by-value token when storage is absent or full. The optimized cpp-flavor scanner’s reserved branch contains the following stores (AT&T syntax).

```asm
mov %rax,(%r8,%r9,8)
mov %rdx,0x8(%r8,%r9,8)
mov %rcx,0x10(%r8,%r9,8)
```

All 24 executables matched the PHP oracle on 11 fixtures and passed late lexical failure, recovery and retained-result checks. Each also produced all 2,560 expected operator tokens starting with zero and one-token hints, exercising fallback allocation and growth. Allocation traces matched among the three generated variants on all three timed corpora.

Reproduce with `python3 -B measure.py --slot FIXTURE_ROOT RUNTIME WORK`. [Raw samples, compile timings and allocation traces](slot.dev002.json).
