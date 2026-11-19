# Owning checked byte view in the tokenizer

Doc Status: supporting

The prototype consumes the scanner’s private by-value input after the result has retained its source. It caches data and size from its own immutable string after the move. Copy and move of the view are disabled because a cached pointer can refer to inline storage in the owner. The public API exposes size and checked byte reads. No scanner-specific range proof is required.

## dev002 comparison

Clang 18.1.3 and GCC 13.3.0, C++23, -O2 without LTO. Each throughput cell shows **owning view / cached control**, each relative to the current out-of-line byte_at at 1.00. Higher is faster. The control caches a pointer and size in scanner locals. All variants use the same previous-count reserve and final vector copy. Throughput uses the median of 45 batches of 128 scans per cell, interleaved in randomized groups. Compile time is median scanner translation-unit wall time over three invocations in fixed variant order.

| Compiler | Flavor | Identifiers | Operators | Mixed | Compile ms (current / view / cached) |
|---|---|---:|---:|---:|---:|
| clang++ | cpp | 1.82 / 1.85 | 1.17 / 1.21 | 1.59 / 1.61 | 298 / 308 / 308 |
| clang++ | php | 1.68 / 1.70 | 0.98 / 1.10 | 1.43 / 1.44 | 300 / 301 / 301 |
| clang++ | js | 1.68 / 1.75 | 1.07 / 1.08 | 1.43 / 1.43 | 299 / 304 / 303 |
| g++ | cpp | 1.91 / 2.12 | 1.66 / 2.18 | 2.12 / 2.28 | 247 / 257 / 256 |
| g++ | php | 2.27 / 2.35 | 2.33 / 2.24 | 2.32 / 2.38 | 239 / 265 / 247 |
| g++ | js | 1.72 / 2.00 | 2.17 / 2.20 | 2.07 / 2.02 | 247 / 253 / 250 |

## Correctness and loop inspection

All 18 executables matched 11 PHP oracle dumps and passed the edit sequence including failure, restoration and retained sibling and previous results. Both compilers also passed the standalone lifetime cases under AddressSanitizer and UndefinedBehaviorSanitizer with the runtime instrumented. Cases cover an empty source, embedded NUL and high byte, inline capacity 22, heap size 23 and a larger heap string. The original source can be destroyed or reassigned. Indices -1 and size throw the production exception type and message.

Both compilers eliminated calls to the production byte accessor in the view scanner. Optimized Clang LLVM IR and GCC GIMPLE each retained 12 static calls to the view’s bounds-failure routine in cpp, 9 in php and 11 in js. These are distinct failure sites, not counts of checks executed per byte.

In the cpp-flavor identifier loop, Clang’s outer loop tests the index against the entry size (%15), then the accessor reloads the member (%31) and tests against it. Inside the identifier run the sign check disappears, but an upper-bound check remains (%57). GCC similarly compares against the entry size (_219) and the separately propagated view size (prephitmp_589) before the identifier load. In Clang’s keyword path the first character test reuses the already loaded byte. Lookahead sites retain their own guards. The owner and cached descriptor occupy the same object in this prototype. Separating their storage while keeping the descriptor scoped to the owner is a candidate for a further comparison.

Reproduce with `python3 -B inline_accessor.py --checked-view FIXTURE_ROOT RUNTIME WORK`. The harness retains optimized dumps in each compiler/flavor directory. [Raw timings](checked_view.dev002.json).
