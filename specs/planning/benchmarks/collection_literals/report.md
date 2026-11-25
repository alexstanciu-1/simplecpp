# Collection-literal construction comparison — dev002

Doc Status: planning

2026-11-19, revision 2 (fairness/ownership review fixes). Research only: no production compiler/runtime changes. Handwritten C++ construction candidates, not a claim that populated/nested source literals are implemented.

## Conclusion

Presize destination element storage whenever literal count is known, at every nested level. Presizing and bulk construction are separate improvements: reserve-plus-append removes growth but retains one emitted call per element. Bulk construction reduces that frontend/backend workload. The count comes directly from the syntax entry inventory; it needs no expensive preparation analysis. Metadata selects construction bindings and the runtime owns capacity/allocation policy.

Brace-list static factories and constructors have similar compile costs. Their const input copies heap strings and nested containers. Consuming compiler-owned mutable arrays avoids those copies and allocations, at additional compile cost for scoped array materialization. Neither static methods nor constructors universally win; the meaningful choice is the input/ownership path.

The current data-initialization contract admits read-only, call-scoped input. Consuming private temporaries would require a reviewed builtin transport rule; it must not consume source locals or silently broaden user-defined factory semantics. Do not select that optimization for arbitrary application types from these scalar/string results.

## Method

- Host: `tf-dev002`; Clang 18.1.3, C++23, `-O2 -g0 -Wall -Wextra -Werror`, libstdc++, default GNU linker. Full machine/compiler details are in the JSON.
- Eight forms × six workloads × five randomized clean compile/link/execution rounds = 240 untraced native builds and 240 clean timing trials. Three separate profile rounds add 144 traced compilations and 144 untimed allocation/copy proofs. Each fixture TU has 12 distinct construction sites; all sites are correctness-checked, while execution timing measures site zero.
- One build at a time, no LTO/PCH/ccache, no ThinLTO cache. Runtime archive and workload driver objects are compiled once and reused. Each fixture object/executable is rebuilt every round. Filesystem caches are not flushed. Host is shared, not exclusively reserved or CPU-pinned.
- Every variant includes the same lightweight declaration header. Container implementation is concrete at the public boundary, with private shared implementation templates in one runtime TU. Constructor/static adapters delegate to the same storage operations. Production scpp::string is used unchanged.
- The isolated vector representation has a separately allocated storage record and element block. Presizing therefore means one element-block allocation, not one total allocation. It is a controlled prototype, not the current production vector implementation.
- Compile and link wall samples use clean binaries and no tracing. Frontend/LLVM totals come from separate -ftime-trace compilations and cannot be subtracted from the untraced wall samples as if they were the same run. Optimizer/codegen submetrics are nested within backend. Native wall is untraced fixture compile + link wall. No PHP prepare/emit stage is measured.
- Runtime is build + bounded observation + destruction, after 200 warmup iterations; each trial adapts to roughly 30 ms. Every site is checked by a full-input digest before/after timing. Clean model code has neither allocator overrides nor per-element counter branches; a symbol check enforces absence of the tracking allocator/globals. Counting executables use a separately compiled BENCH_INSTRUMENT model and driver.
- Allocation counts/bytes/peak requested live heap bytes and element copy/move constructions come from a separate untimed construction. They exclude allocator overhead, RSS and stack/static backing arrays. Counters describe model element operations, not every internal string/ABI operation.
- Reported wall/execution values are medians of five samples; profiled phase values are medians of three independent samples. All samples and min/max, object/executable sizes and source hashes are in [results.dev002.json](results.dev002.json). Small runtime differences are not a stable winner claim. The old instrumented timings are retained as [superseded revision 1](results.dev002.v1.json), not mixed with the corrected data.

## Verification

Clang 18 and GCC 13 on dev002 passed the revised ASan/UBSan proof (including stack-use-after-return and leak detection) for all eight forms: distinct entry identities, exact successful result positions and failure prefixes, nested source failures, assignment atomicity through every allocation-failure position, and balanced tracked allocations. Repeated existing inner-vector and string sources remain independent of both result entries; source/result mutation and source destruction preserve the other copies, including full NUL-containing string bytes. Returned results are checked after initializer backing has already been destroyed.
Presizing is asserted rather than inferred: exact final capacity at outer/inner levels, no growth allocations, and the expected buffer-allocation count. Reserve/append boundaries 0, 1, 3, 4, 5, 8, 9, 64 and 512 prove no further buffer allocation through the reserved count. Each of the 144 counting runs also verifies its expected presizing profile; profiles must agree across all three rounds.

## Construction shapes

```cpp
// Plain nested constructors: const initializer-list input.
Matrix{IVec{1, 2}, IVec{3, 4}};

// Nested static factories: the same const-input ownership.
Matrix::from({IVec::from({1, 2}), IVec::from({3, 4})});

// Concrete consuming factory: private temporary input only.
([] {
    I values[] = {1, 2};
    return IVec::from_owned(values, 2);
}());

// Equivalent concrete constructor.
([] {
    I values[] = {1, 2};
    return IVec(owned_input{}, values, 2);
}());
```

The nested array variants apply the corresponding expression at both inner and outer levels. Every factory/constructor knows its input count and presizes the final storage. Named input arrays preserve order, native partial-construction cleanup and call-scoped lifetimes. Very large literals need a separately reviewed bounded-stack strategy.

## Measurements

All compile/link times below are **milliseconds for the 12-site translation unit**; execution is **nanoseconds per site-zero construction/observation/destruction**.

### 3 integers

| Form | Frontend profile ms | LLVM/backend profile ms | Clean compile wall ms | Clean link wall ms | Clean native wall ms | Clean execute ns | Allocations | Peak heap B |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Append, geometric growth | 10.73 | 12.73 | 41.58 | 55.52 | 97.00 | 38.95 | 2 | 56 |
| Presized append | 9.63 | 13.30 | 43.22 | 55.06 | 97.80 | 43.75 | 2 | 48 |
| Brace-list constructor | 9.34 | 8.43 | 36.00 | 54.88 | 91.79 | 33.43 | 2 | 48 |
| Static from(brace-list) | 9.11 | 8.31 | 37.51 | 54.96 | 92.47 | 33.21 | 2 | 48 |
| Constructor, const array | 10.33 | 10.69 | 39.55 | 56.92 | 95.73 | 33.64 | 2 | 48 |
| Static from(const array) | 10.13 | 10.68 | 41.32 | 53.85 | 96.25 | 34.23 | 2 | 48 |
| Constructor, owned array | 9.21 | 10.70 | 40.22 | 54.24 | 94.32 | 34.09 | 2 | 48 |
| Static from_owned(array) | 10.37 | 10.83 | 38.63 | 55.59 | 93.35 | 33.62 | 2 | 48 |

### 64 integers

| Form | Frontend profile ms | LLVM/backend profile ms | Clean compile wall ms | Clean link wall ms | Clean native wall ms | Clean execute ns | Allocations | Peak heap B |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Append, geometric growth | 18.19 | 49.96 | 84.93 | 58.20 | 142.78 | 316.71 | 6 | 792 |
| Presized append | 15.97 | 50.73 | 86.17 | 58.06 | 140.22 | 263.68 | 2 | 536 |
| Brace-list constructor | 9.86 | 8.39 | 37.56 | 53.62 | 92.06 | 40.92 | 2 | 536 |
| Static from(brace-list) | 10.14 | 8.43 | 37.37 | 53.59 | 90.73 | 43.30 | 2 | 536 |
| Constructor, const array | 11.30 | 10.65 | 40.90 | 54.20 | 94.29 | 43.41 | 2 | 536 |
| Static from(const array) | 10.24 | 10.71 | 42.06 | 57.02 | 100.77 | 35.21 | 2 | 536 |
| Constructor, owned array | 11.27 | 10.76 | 41.21 | 56.57 | 97.77 | 43.60 | 2 | 536 |
| Static from_owned(array) | 11.52 | 10.70 | 41.83 | 58.24 | 99.50 | 44.18 | 2 | 536 |

### 512 integers

| Form | Frontend profile ms | LLVM/backend profile ms | Clean compile wall ms | Clean link wall ms | Clean native wall ms | Clean execute ns | Allocations | Peak heap B |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Append, geometric growth | 56.05 | 332.19 | 402.87 | 57.77 | 460.52 | 2528.53 | 9 | 6168 |
| Presized append | 54.69 | 333.18 | 401.32 | 58.44 | 459.42 | 2256.55 | 2 | 4120 |
| Brace-list constructor | 15.63 | 8.79 | 42.80 | 56.64 | 98.05 | 160.54 | 2 | 4120 |
| Static from(brace-list) | 15.46 | 8.69 | 39.80 | 56.19 | 95.99 | 158.89 | 2 | 4120 |
| Constructor, const array | 19.45 | 11.01 | 46.73 | 54.69 | 101.18 | 161.99 | 2 | 4120 |
| Static from(const array) | 19.45 | 10.98 | 45.48 | 56.70 | 103.01 | 156.06 | 2 | 4120 |
| Constructor, owned array | 19.96 | 11.12 | 46.61 | 57.21 | 103.44 | 131.11 | 2 | 4120 |
| Static from_owned(array) | 19.70 | 10.98 | 45.75 | 57.20 | 102.94 | 160.22 | 2 | 4120 |

### 16 inline strings

| Form | Frontend profile ms | LLVM/backend profile ms | Clean compile wall ms | Clean link wall ms | Clean native wall ms | Clean execute ns | Allocations | Peak heap B |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Append, geometric growth | 16.61 | 61.75 | 97.33 | 57.03 | 154.36 | 241.41 | 4 | 600 |
| Presized append | 16.64 | 62.38 | 98.80 | 56.95 | 154.76 | 216.40 | 2 | 408 |
| Brace-list constructor | 13.90 | 50.15 | 81.89 | 57.36 | 138.25 | 218.95 | 2 | 408 |
| Static from(brace-list) | 12.32 | 50.59 | 82.14 | 54.24 | 136.39 | 259.46 | 2 | 408 |
| Constructor, const array | 13.40 | 61.57 | 93.68 | 57.60 | 150.46 | 240.90 | 2 | 408 |
| Static from(const array) | 13.67 | 61.62 | 92.62 | 56.66 | 149.20 | 246.65 | 2 | 408 |
| Constructor, owned array | 12.45 | 61.82 | 94.93 | 57.93 | 151.57 | 188.31 | 2 | 408 |
| Static from_owned(array) | 13.82 | 61.66 | 94.25 | 56.56 | 150.25 | 187.94 | 2 | 408 |

### 16 heap strings + NUL

| Form | Frontend profile ms | LLVM/backend profile ms | Clean compile wall ms | Clean link wall ms | Clean native wall ms | Clean execute ns | Allocations | Peak heap B |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Append, geometric growth | 17.02 | 62.00 | 98.08 | 55.01 | 153.50 | 686.30 | 36 | 2446 |
| Presized append | 18.79 | 62.37 | 97.78 | 54.76 | 154.33 | 622.06 | 34 | 2446 |
| Brace-list constructor | 11.30 | 50.21 | 81.94 | 57.01 | 138.95 | 908.33 | 66 | 4484 |
| Static from(brace-list) | 12.53 | 50.37 | 82.16 | 57.88 | 140.37 | 1115.87 | 66 | 4484 |
| Constructor, const array | 13.76 | 61.65 | 94.77 | 56.30 | 148.11 | 908.07 | 66 | 4484 |
| Static from(const array) | 14.79 | 61.81 | 94.36 | 56.02 | 150.57 | 912.94 | 66 | 4484 |
| Constructor, owned array | 13.70 | 61.69 | 93.58 | 58.04 | 149.69 | 506.57 | 34 | 2446 |
| Static from_owned(array) | 12.49 | 61.84 | 93.92 | 57.20 | 150.77 | 633.59 | 34 | 2446 |

### 8 vectors × 16 integers

| Form | Frontend profile ms | LLVM/backend profile ms | Clean compile wall ms | Clean link wall ms | Clean native wall ms | Clean execute ns | Allocations | Peak heap B |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Append, geometric growth | 34.78 | 156.53 | 205.54 | 55.50 | 262.10 | 820.86 | 35 | 1368 |
| Presized append | 35.51 | 165.58 | 216.38 | 58.41 | 274.09 | 791.36 | 18 | 1304 |
| Brace-list constructor | 12.33 | 73.23 | 104.90 | 53.40 | 157.26 | 637.34 | 34 | 2520 |
| Static from(brace-list) | 12.29 | 73.73 | 107.22 | 54.62 | 162.13 | 645.23 | 34 | 2520 |
| Constructor, const array | 25.56 | 93.71 | 131.00 | 53.64 | 184.50 | 640.74 | 34 | 2520 |
| Static from(const array) | 23.97 | 94.56 | 132.61 | 54.88 | 187.49 | 613.48 | 34 | 2520 |
| Constructor, owned array | 23.39 | 93.63 | 132.70 | 55.32 | 189.15 | 333.27 | 18 | 1304 |
| Static from_owned(array) | 23.64 | 94.57 | 132.04 | 58.01 | 189.40 | 331.67 | 18 | 1304 |

## One-time setup

| Component | Compile wall ms |
|---|---:|
| production | 1871.55 |
| model | 389.19 |
| model-counted | 394.75 |
| runtime archive | 17.02 |
| counted archive | 14.67 |
| int3-driver driver | 924.36 |
| int3-counted driver | 895.79 |
| int64-driver driver | 927.05 |
| int64-counted driver | 905.16 |
| int512-driver driver | 925.78 |
| int512-counted driver | 909.21 |
| short16-driver driver | 924.83 |
| short16-counted driver | 920.18 |
| heap16-driver driver | 935.52 |
| heap16-counted driver | 905.39 |
| nested8x16-driver driver | 913.44 |
| nested8x16-counted driver | 884.72 |

For a clean application build, add production + model + runtime-archive setup and its workload driver. Counted-model/archive/driver setup belongs only to this experiment’s verification work. Setup is shared across literal sites; it is not charged repeatedly to each construction.

## Scope and next decision

Keep the production implementation unchanged. First choose whether a bounded std::initializer_list dependency is acceptable for trivial scalar construction, or whether a concrete no-template array/view boundary is preferred consistently. Measure LTO, larger input bounds and actual compiler-produced units before declaring a production optimum. GCC was used for correctness here, not timing; no Rust or source-flavor performance comparison is claimed.

Reproduce with the commands in [README.md](README.md). The runtime snapshot and source hashes freeze the production string used in this experiment.
