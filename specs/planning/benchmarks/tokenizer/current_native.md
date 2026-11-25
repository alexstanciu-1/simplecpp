# Current compiler tokenizer throughput against handwritten C++

Doc Status: supporting

Measured revision `0886aac46` on dev002 CPU 7 (AMD Ryzen 9 5950X), Ubuntu clang version 18.1.3 (1ubuntu1).

| Flavor | Input | Handwritten MiB/s | Ordinary MiB/s | Selected MiB/s | Ordinary / handwritten | Selected / handwritten |
|---|---|---:|---:|---:|---:|---:|
| cpp | identifiers | 1135.3 | 732.7 | 1163.6 | 64.0% | 102.7% |
| cpp | operators | 1534.1 | 389.2 | 1171.5 | 25.2% | 76.0% |
| cpp | mixed | 942.5 | 453.4 | 960.6 | 47.9% | 102.0% |
| php | identifiers | 1439.1 | 733.7 | 1175.1 | 50.7% | 81.4% |
| php | operators | 1563.6 | 387.3 | 1165.9 | 24.6% | 74.6% |
| php | mixed | 1215.3 | 444.5 | 967.0 | 36.7% | 78.9% |
| js | identifiers | 1152.2 | 732.6 | 1167.5 | 63.3% | 101.4% |
| js | operators | 1542.6 | 389.4 | 1171.1 | 25.2% | 75.9% |
| js | mixed | 1009.0 | 449.2 | 963.2 | 44.3% | 95.4% |

Aggregate throughput for scanning each of the nine flavor/input combinations once: **44.4%** of handwritten for ordinary and **91.0%** for selected. This divides the sum of handwritten median times by the corresponding sum of generated median times.

## Native build cost

| Flavor | Mode | Compile ms | Link ms | Compile + link ms | Frontend trace ms | Backend trace ms |
|---|---|---:|---:|---:|---:|---:|
| cpp | handwritten | 362.4 | 52.4 | 415.3 | 310.5 | 49.5 |
| cpp | selected | 300.7 | 54.4 | 354.9 | 256.1 | 40.6 |
| cpp | ordinary | 299.7 | 54.1 | 354.1 | 255.1 | 39.4 |
| php | handwritten | 365.2 | 52.4 | 417.0 | 318.2 | 49.5 |
| php | selected | 301.3 | 54.4 | 355.3 | 257.6 | 37.6 |
| php | ordinary | 297.8 | 54.9 | 352.3 | 261.5 | 37.4 |
| js | handwritten | 365.3 | 53.3 | 418.5 | 314.2 | 49.6 |
| js | selected | 305.5 | 54.7 | 359.9 | 253.6 | 39.8 |
| js | ordinary | 300.8 | 54.9 | 357.5 | 264.2 | 38.3 |

## What was measured

The checked-in Simple C++ scanner was compiled twice per flavor with the project command, once at its default settings and once with `--optimize-function=scan`. The runner requires the direct-slot helper to appear only in the latter output. Both used the compiler-emitted translation units and headers verbatim. The handwritten baseline used std::string, std::string_view and std::vector, and the corresponding flavor's keyword set (if/while, plus auto for cpp or let for js). All modes stored three 64-bit fields per token and retained the source.

The current scanner covers identifiers, flavor fixture keywords, whitespace and equals operators. The three repeated synthetic corpora were 22,528 bytes of identifier-heavy input, 8,192 bytes of operators and 20,480 bytes of mixed input. The earlier whole-source-file experiment used additional benchmark-only lexical rules and optimized C++ rewrites; its percentages have a different scope.

All nine binaries were checked against the production PHP lexer on all 11 inputs, with zero and nonzero reserve hints, and on the four-step input edit/failure/recovery sequence. Timings used preloaded bytes and an accurate previous token count. Each timed scan includes by-value input ownership, tokenization, storage and result destruction. File I/O, preparing the hint and dumping token text are outside the timer. After a warm-up triplet, each workload used twelve triplets in all six execution orders twice. Each process returned nine batches of 128 scans; the row percentages are medians of within-triplet throughput ratios.

Developer build costs are for a serial Clang -O2 -g0 C++23 scanner object build and link with warm filesystem and reused driver/runtime objects, default standard library and linker, no PCH, compiler cache or LTO. Each mode used the same linker garbage-collection flag. The generated scanners linked the prebuilt runtime object; the handwritten implementation uses its native library. The columns are medians of six builds in balanced order, and the frontend/backend totals are from one separate traced compile. PHP preparation, driver compilation and runtime construction are excluded from build comparisons.

Run `python3 -B current_native.py prepare NEW_DIR` on a checkout with the Clang compiler publication available. Run `taskset -c 7 python3 -B current_native.py bench .` from that prepared directory on the benchmark host. The runner validates the package hashes before compiling. [Raw measurements](current_native.dev002.json) · [Runner](current_native.py) · [Handwritten baseline](baseline.cpp)
