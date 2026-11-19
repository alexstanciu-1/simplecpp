# Splitting the tokenizer byte descriptor from its owner

Doc Status: supporting

The scanner retains its result source, then moves its private by-value input into a const local string. A second local contains only the byte pointer and signed length obtained from that owner. The descriptor performs the same signed bounds checks as the combined owning view. The immutable owner stays at a fixed address until after the descriptor is destroyed, including on exception paths. Inline bytes therefore stay valid as well as heap bytes. The descriptor rejects temporary owners and cannot be copied or moved. This is a guarantee of the generated scan scope. A general C++ borrower could still misuse an lvalue owner by destroying or mutating it too soon.

## Verification and method

All variants use the same emitted scanner, previous-count reserve, retained result source and final token-vector copy. Run on dev002 with C++23, -O2, separate translation units and no LTO. Each throughput figure uses the median of 45 batches of 128 scans, interleaving variants in shuffled groups of nine batches. Compilation measures the scanner translation unit only, using three builds per variant in fixed order. Use the raw samples when interpreting small differences.

Each of 18 binaries is checked against the 11 PHP oracle fixtures and the edit sequence with error, recovery and retained results. The lifetime harness reads every byte after reassigning the moved-from input, including embedded NUL and 0xff, empty input, lengths 22 and 23 at the inline/heap transition, and a larger heap string. Both invalid endpoints (-1 and size) must throw the expected exception and message.

Run the isolated benchmark with `python3 -B inline_accessor.py --split-view FIXTURE_ROOT RUNTIME WORK`. Clang emits LLVM IR and GCC emits optimized GIMPLE for both descriptor layouts in each compiler/flavor directory. [Raw dev002 measurements](split_view.dev002.json).

## Optimized loops and native results

All parity, recovery and lifetime checks passed. Clang and GCC also passed the lifetime suite under ASan and UBSan with the runtime instrumented. Each flavor’s identifier continuation loop now loads the byte directly after the loop guard. In the representative cpp scanner, Clang LLVM IR block 49 forms the address and loads the byte, and block 63 tests the increment against the captured length. GCC’s optimized block with induction variable var_i_5.5_156 does the same. The combined layout had a second upper-bound test leading to the exception path inside this loop on both compilers. Clang still checks the sign at the outer token entry, and both compilers retain failure paths for keyword reads.

Each throughput cell gives **split relative to current / split relative to combined**. Values above 1 indicate higher throughput.

| Compiler | Flavor | Identifiers | Operators | Mixed | Compile ms (current / combined / split) |
|---|---|---:|---:|---:|---:|
| clang++ | cpp | 1.80 / 0.99 | 1.14 / 0.90 | 1.61 / 1.02 | 307 / 310 / 302 |
| clang++ | php | 1.73 / 1.05 | 1.21 / 0.96 | 1.45 / 1.03 | 299 / 303 / 302 |
| clang++ | js | 1.63 / 0.97 | 1.18 / 1.03 | 1.47 / 1.02 | 302 / 313 / 310 |
| g++ | cpp | 1.98 / 1.04 | 2.21 / 1.04 | 2.20 / 1.05 | 244 / 257 / 249 |
| g++ | php | 2.16 / 0.97 | 1.99 / 1.04 | 2.28 / 1.08 | 243 / 249 / 250 |
| g++ | js | 2.01 / 0.98 | 1.79 / 0.98 | 2.23 / 1.05 | 246 / 253 / 249 |
