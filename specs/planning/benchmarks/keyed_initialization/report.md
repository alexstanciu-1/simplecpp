# Typed keyed initialization — dev002

Doc Status: supporting

Measured 2026-11-19 on tf-dev002. Ubuntu clang version 18.1.3 (1ubuntu1); g++ (Ubuntu 13.3.0-6ubuntu2~24.04.1) 13.3.0.

These are acceptance-cost observations, not a before/after optimization claim. One generated table is constructed per executable run. Execution wall includes process startup and output; it is **not** a per-operation construction-throughput benchmark.

## Method

- Four inputs × three source flavors × two native compilers × three rounds = 72 clean compile/link/executable samples. Twelve separate Clang compilations supply phase profiles.
- C++23, O2, g0, warnings as errors, libstdc++, default linker, sequential jobs; no LTO, PCH or ccache. Runtime/hash/output/arithmetic objects from the native proof are reused; their build cost is excluded.
- PHP 8.5 fresh processes. Prepare includes parsing/collection/analysis; emit is measured separately. PHP wall also includes process startup and loading. Filesystem caches are not flushed; dev002 is a shared host.
- Each sample checks the program exit and count output. Separate source/native and allocation-failure proofs verify contents, ordering, duplicates, copies and cleanup. No sanitizer, allocation override or compiler tracing is present in clean timing samples.
- Integer inputs exercise the 64-entry inline boundary. Strings use 16 distinct heap-backed values. Reported native wall is compile + link for that sample. The staged sum includes PHP process wall + native wall; it excludes harness orchestration and executable verification.

## Clean medians

Times are milliseconds; bytes are generated C++ for the full test unit.

| Compiler | Flavor | Input | PHP prepare | PHP emit | PHP wall | Compile | Link | Native wall | Staged sum | Execute process | C++ bytes |
| --- | --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| clang++ | cpp | int3 | 0.51 | 0.07 | 53.14 | 42.00 | 55.84 | 98.04 | 151.18 | 1.49 | 543 |
| clang++ | cpp | int64 | 1.34 | 0.18 | 56.88 | 41.98 | 54.27 | 96.83 | 152.18 | 1.43 | 1262 |
| clang++ | cpp | int65 | 1.39 | 0.19 | 57.38 | 66.27 | 61.24 | 127.51 | 186.66 | 1.63 | 1324 |
| clang++ | cpp | string16 | 0.66 | 0.15 | 55.93 | 53.94 | 58.81 | 112.75 | 168.47 | 1.49 | 1807 |
| clang++ | php | int3 | 0.52 | 0.08 | 56.96 | 42.22 | 53.97 | 95.76 | 152.16 | 1.37 | 543 |
| clang++ | php | int64 | 1.34 | 0.19 | 57.18 | 38.33 | 59.42 | 100.88 | 155.68 | 1.37 | 1262 |
| clang++ | php | int65 | 1.34 | 0.18 | 54.97 | 63.07 | 58.42 | 121.50 | 177.54 | 1.48 | 1324 |
| clang++ | php | string16 | 0.68 | 0.14 | 53.02 | 53.64 | 55.91 | 108.21 | 161.22 | 1.46 | 1807 |
| clang++ | js | int3 | 0.53 | 0.07 | 53.20 | 43.83 | 57.40 | 101.23 | 154.66 | 1.39 | 543 |
| clang++ | js | int64 | 1.36 | 0.19 | 57.51 | 41.04 | 55.40 | 95.04 | 152.84 | 1.47 | 1262 |
| clang++ | js | int65 | 1.40 | 0.19 | 55.78 | 65.27 | 57.84 | 121.33 | 178.41 | 1.38 | 1324 |
| clang++ | js | string16 | 0.70 | 0.15 | 56.43 | 50.86 | 57.72 | 109.16 | 162.86 | 1.41 | 1807 |
| g++ | cpp | int3 | 0.55 | 0.08 | 58.40 | 25.87 | 46.99 | 72.86 | 130.07 | 1.49 | 543 |
| g++ | cpp | int64 | 1.35 | 0.19 | 55.88 | 30.10 | 47.29 | 77.38 | 131.78 | 1.45 | 1262 |
| g++ | cpp | int65 | 1.37 | 0.19 | 54.03 | 44.51 | 46.83 | 91.26 | 145.53 | 1.47 | 1324 |
| g++ | cpp | string16 | 0.70 | 0.15 | 53.90 | 47.55 | 46.46 | 93.44 | 146.45 | 1.42 | 1807 |
| g++ | php | int3 | 0.57 | 0.08 | 57.71 | 26.90 | 47.02 | 74.89 | 131.07 | 1.54 | 543 |
| g++ | php | int64 | 1.39 | 0.19 | 58.26 | 30.90 | 45.65 | 76.21 | 134.47 | 1.58 | 1262 |
| g++ | php | int65 | 1.36 | 0.19 | 55.86 | 45.02 | 44.58 | 89.60 | 144.89 | 1.59 | 1324 |
| g++ | php | string16 | 0.79 | 0.15 | 57.75 | 48.42 | 48.16 | 95.88 | 153.63 | 1.58 | 1807 |
| g++ | js | int3 | 0.53 | 0.07 | 56.85 | 29.04 | 46.16 | 75.19 | 128.05 | 1.48 | 543 |
| g++ | js | int64 | 1.38 | 0.18 | 56.03 | 30.30 | 44.59 | 75.54 | 132.55 | 1.47 | 1262 |
| g++ | js | int65 | 1.44 | 0.19 | 55.35 | 44.09 | 46.11 | 87.85 | 145.55 | 1.51 | 1324 |
| g++ | js | string16 | 0.69 | 0.15 | 57.04 | 47.28 | 46.59 | 92.66 | 148.61 | 1.50 | 1807 |

## Separate Clang phase profiles

Single instrumented runs, milliseconds. Optimizer/codegen subtotals are nested inside backend time; do not add them again or subtract them from clean median wall times.

| Flavor | Input | Frontend | LLVM/backend | Optimizer | Codegen |
| --- | --- | ---: | ---: | ---: | ---: |
| cpp | int3 | 16.84 | 4.93 | 2.14 | 1.98 |
| cpp | int64 | 16.84 | 4.38 | 1.80 | 1.84 |
| cpp | int65 | 17.69 | 40.02 | 31.43 | 7.72 |
| cpp | string16 | 16.34 | 13.87 | 5.94 | 7.20 |
| php | int3 | 15.73 | 4.76 | 2.09 | 1.94 |
| php | int64 | 15.78 | 4.37 | 1.81 | 1.82 |
| php | int65 | 16.01 | 26.22 | 20.81 | 4.67 |
| php | string16 | 16.17 | 13.80 | 5.92 | 7.15 |
| js | int3 | 16.87 | 4.65 | 2.05 | 1.89 |
| js | int64 | 17.06 | 4.51 | 1.84 | 1.88 |
| js | int65 | 16.16 | 26.36 | 20.77 | 4.79 |
| js | string16 | 16.49 | 14.10 | 6.00 | 7.34 |

## Verification and reproduction

138 source/native fixtures passed in cpp/php/js on both Clang and GCC. Both ownership proofs passed with ASan/UBSan, leak detection and stack-use-after-return checking. Real retained-session Ninja tests additionally proved native object reuse and fresh-output equivalence in all flavors.

Run `keyed_initialization_native.py --out native-clang` and the same command with `--cxx g++ --out native-gcc`. Then run this directory’s `run.py --out timing --objects <parent-of-native-clang-and-native-gcc> --php php8.5`. Source/runtime paths resolve from the checkout. `report.py` renders the checked-in JSON.

[Raw measurements](results.dev002.json) · [Verification record](verification.dev002.txt)
