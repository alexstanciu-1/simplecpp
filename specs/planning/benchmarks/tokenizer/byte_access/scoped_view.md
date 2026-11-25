# Scoped checked byte reads in the compiled tokenizer fixture

Both variants copy the by-value source string into the result, reserve using the previous count and move the finished vector. The ordinary control spells the length initializer as `strlen(owner.source) + 0` to bypass view selection. Both read the same field through source indexing. The optimized variant acquires data and size once. Every read retains its signed bounds checks.

dev002, CPU 7, Clang 18.1.3 and GCC 13.3.0, C++23, -O2, no LTO. Each workload has eight adjacent pairs with alternating order. Each measurement is the median of nine batches of 128 scans including result destruction. The previous input appends one identifier. Ratios are medians of within-pair throughput ratios. Compile times are medians of three scanner-unit compilations per variant, taken in variant order. They exclude linking and are descriptive.

| Compiler | Flavor | Identifiers × | Operators × | Mixed × | Mixed ordinary → view (MiB/s) | Compile ordinary → view (ms) |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| clang++ | cpp | 2.38 | 1.25 | 1.71 | 232 → 397 | 295 → 304 |
| clang++ | php | 2.09 | 1.23 | 1.51 | 266 → 404 | 294 → 298 |
| clang++ | js | 2.15 | 1.24 | 1.55 | 258 → 403 | 297 → 299 |
| g++ | cpp | 3.43 | 2.57 | 2.79 | 257 → 716 | 240 → 248 |
| g++ | php | 3.49 | 2.83 | 2.80 | 275 → 769 | 237 → 241 |
| g++ | js | 3.12 | 2.34 | 2.54 | 291 → 737 | 242 → 247 |

All six view builds passed PHP tokenizer parity for eleven inputs, error/recovery edit parity, inline and heap owner retention after caller replacement, and checks for embedded NUL, high bytes, negative index and end index. The checked descriptor is non-copyable and rejects acquisition on an rvalue. The ordinary variants passed the same tokenizer oracle and edits.

On the prepared scanner tree, the view proof alone took 9.90 µs (cpp), 9.32 µs (php) and 9.87 µs (js). The entry body without a candidate cost 0.09 µs in each flavor. Preparation used PHP 8.5 with Xdebug off on CPU 7. These isolated measurements use the existing warm-batch harness.

To reproduce, run `byte_access/scoped_view.py FIXTURES` with generated compiler bindings available. On the native host run `taskset -c 7 python3 byte_access/scoped_view.py FIXTURES --runtime RUNTIME` from the tokenizer benchmark directory, preserving the repository layout for the driver and lifetime fixture.

[Raw samples](scoped_view.dev002.json) · [Runner](scoped_view.py) · [Preparation harness](../transfer_preparation.php)
