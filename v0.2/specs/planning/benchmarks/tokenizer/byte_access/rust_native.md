# Rust tokenizer on the nine whole source files

Doc Status: supporting

Safe Rust 1.75.0 (Ubuntu package, LLVM 17.0.6) implements the benchmark vocabulary with standard byte predicates, short-circuit equals lookahead, the same flavor keyword and punctuation inventories, and a 24-byte three-i64 token. Each scan takes a fresh owned copy of the preloaded bytes, moves that source into the result and builds the token vector with Vec::with_capacity using the prior count. The scanner and driver are separate crates, opt-level=2, LTO off, generic host target. These are results for this Rust implementation and toolchain. The C++ binaries are unchanged Clang 18.1.3 and GCC 13.3.0 -O2 builds, with the same input ownership, record widths and reserve hints.

dev002, CPU 7. After warmup each file had 30 matched rounds of all five executables. Orders rotated so each executable occupied each position six times. Per run, nine batches of 128 scans include input copy, scan and destruction. Parsing files, oracle comparison and computing the reserve hint occur outside the timed loop. Each executable passed its 11 lexical fixtures and edit/failure/recovery checks and matched every token kind, byte offset, byte length and spelling on the complete files. This bounded lexical benchmark does not measure the full language tokenizer.

| Flavor | File | Rust MiB/s | Simple C++ Clang | Handwritten Clang | Simple C++ GCC | Handwritten GCC |
|---|---|---:|---:|---:|---:|---:|
| cpp | billing | 765.0 | 850.0 | 795.8 | 772.9 | 698.6 |
| cpp | scanner | 759.7 | 807.9 | 800.8 | 622.3 | 595.5 |
| cpp | support | 710.3 | 722.4 | 704.0 | 563.3 | 573.2 |
| php | billing | 823.4 | 810.0 | 872.3 | 719.5 | 690.2 |
| php | scanner | 775.6 | 814.3 | 881.0 | 705.8 | 663.3 |
| php | support | 718.8 | 703.0 | 711.1 | 412.6 | 467.0 |
| js | billing | 755.3 | 828.4 | 836.1 | 618.3 | 642.7 |
| js | scanner | 718.6 | 763.2 | 812.2 | 540.9 | 566.6 |
| js | support | 749.1 | 667.9 | 783.1 | 475.6 | 546.1 |
| All | One pass per file | 756.6 | 787.9 | 799.7 | 606.4 | 611.1 |

The aggregate divides total source bytes by the sum of median per-file scan times. [Source files and workload descriptions](source_classification.md) · [Raw samples and executable hashes](rust_native.dev002.json).

Run `python3 -B rust_native.py FIXTURES CORPUS NATIVE_WORK RUST_WORK RUSTC --cpu 7` after preparing the existing C++ whole-file baselines. The Rust build uses the standard library and no downloaded crate dependencies.
