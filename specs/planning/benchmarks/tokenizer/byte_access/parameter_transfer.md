# Last-use source parameter transfer in the compiled scanner

dev002 CPU 7, Clang 18.1.3 and GCC 13.3.0, C++23, -O2, no LTO. The copy control was emitted at `ce5cbcc2a`. The runner verifies that the sole scanner-unit edit wraps the source parameter in `std::move` when assigning the result field. Both builds use previous-count reserve, the scoped checked view and the final token-vector move. Each corpus has eight alternating pairs. Within each run the statistic is the median of nine batches of 128 scans including destruction. Ratios are median paired transfer/copy throughput. Compilation numbers are three-run medians per scanner unit, collected in variant order.

| Compiler | Flavor | Identifier ratio | Operator ratio | Mixed ratio | Mixed MiB/s, copy → transfer | Compile ms, copy → transfer |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| clang++ | cpp | 1.013 | 1.099 | 1.113 | 409 → 455 | 295 → 306 |
| clang++ | php | 1.012 | 1.100 | 1.110 | 410 → 458 | 294 → 292 |
| clang++ | js | 1.008 | 1.094 | 1.115 | 404 → 454 | 295 → 295 |
| g++ | cpp | 0.913 | 1.373 | 1.205 | 726 → 874 | 243 → 244 |
| g++ | php | 0.890 | 1.292 | 1.167 | 774 → 903 | 239 → 237 |
| g++ | js | 0.930 | 1.395 | 1.205 | 746 → 897 | 241 → 240 |

The mixed corpus requested 163,954 bytes in six allocations for copy and 143,441 bytes in four allocations for transfer in all six builds. This saves 20,513 bytes per scan. GCC identifier throughput fell in all three flavors despite the allocation saving. This experiment does not isolate the cause.

Each variant passed PHP token parity and input-edit recovery. Both paths ran the lifetime fixture with inline and heap strings, embedded NUL and early errors. It checks the caller bytes immediately after the call and the result bytes after replacing the caller input.

To reproduce, emit the same scanner fixtures using the control and candidate compilers via `compiler/tests/tokenizer_scanner_native.py`. Run `taskset -c 7 python3 scoped_view.py CANDIDATE --runtime RUNTIME --copy-fixtures CONTROL` on dev002 with the benchmark driver, allocation counter and lifetime fixture present.

[Raw measurements](parameter_transfer.dev002.json)
