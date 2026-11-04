# Generated int/int64 benchmark — 2026-11-04

Current snapshot: `2528796f5` plus the uncommitted metadata-generated int/int64 implementation. This measures the current compiler, not an isolated before/after comparison of code generation. No compiler implementation changes were made for this benchmark.

WSL2, PHP 8.5.7 NTS, Xdebug absent, CLI OPcache disabled; Clang 18.1.3, Ninja 1.11.1, mold 2.40.4. Sample: 257 files, 42,195 source bytes, 4,870 tokens. Warm filesystem caches; no CPU isolation.

## PHP stages

Seven samples. Times in milliseconds; individual stage medians need not sum to the total median. C++ text emission is in memory; native compilation is measured separately.

| Stage | Median | Min–max |
| --- | ---: | ---: |
| read_snapshot | 5.409 | 4.634–5.489 |
| tokenize | 6.683 | 6.522–6.913 |
| parse_collect | 9.250 | 7.299–10.209 |
| symbol_index | 0.973 | 0.814–1.066 |
| prepare | 2.254 | 2.003–2.810 |
| cpp_text | 1.102 | 0.889–1.556 |
| staged_total | 25.817 | 22.836–27.214 |

Peak PHP allocator usage: 8 MiB; emitted C++: 81,968 bytes. Median per-sample preparation share: 8.85%. This simple function-only sample does not predict preparation costs for future classes or overload resolution.

## Incremental frontend and preparation

These times exclude native compilation. Unchanged tokens and AST identities, selective reads, symbol indexing and prepared-body reuse are checked by the harness.

| Scenario | Combined median | Min–max |
| --- | ---: | ---: |
| full | 27.878 | 25.904–29.336 |
| unchanged | 5.474 | 5.121–5.940 |
| changed | 5.503 | 5.227–6.943 |
| added | 5.988 | 5.752–6.443 |
| removed | 6.297 | 6.119–6.634 |
| moved | 6.779 | 6.387–7.537 |

Unchanged: 0 bodies prepared, 257 reused. One-file edit: 1 prepared, 256 reused. All harness assertions passed.

## Native build

Five fresh-object builds per configuration, warm caches; `-O2 -g0`, libc++, mold, prebuilt runtime archive, no PCH/LTO/ccache. Grouping uses the existing benchmark-only round-robin concatenation, including the entry adapter. It is not a permanent compiler change. Total includes PHP startup, loading, compilation stages, emission/writes, grouping and native build.

| C++ units | Ninja jobs | Native median | Native min–max | Total median | Link command median |
| ---: | ---: | ---: | ---: | ---: | ---: |
| 1 | 8 | 220.92 | 215.94–302.10 | 326.87 | 56.00 |
| 4 | 8 | 170.55 | 135.04–228.58 | 273.76 | 62.00 |
| 8 | 8 | 163.56 | 154.59–228.21 | 276.96 | 60.00 |
| 258 | 8 | 2206.12 | 2051.85–2563.26 | 2310.06 | 78.00 |
| 258 | 4 | 3302.55 | 3142.50–3565.89 | 3402.49 | 79.00 |

All executables produced `my-try-simple: OK\n` and exit status 8; all 256 sample function definitions were retained. Executables were checked for absent debug sections.

## Clang phase detail

Separate instrumented builds: cumulative elapsed time across translation units, not parallel build wall time or CPU time. LLVM Backend includes optimization/code generation; its subcategories overlap with the total and must not be added to it. Mold internal wall time excludes compiler-driver/startup overhead included in the link-command timing above.

| Units/jobs | Frontend | LLVM Backend | Optimizer (within backend) | CodeGenPasses (within backend) | Mold internal wall |
| --- | ---: | ---: | ---: | ---: | ---: |
| 1/8 | 36.37 | 150.12 | 54.98 | 92.85 | 21.00 |
| 4/8 | 52.03 | 248.89 | 105.43 | 132.83 | 18.00 |
| 8/8 | 82.98 | 384.83 | 126.62 | 228.65 | 21.00 |
| 258/8 | 696.13 | 2053.41 | 700.93 | 673.99 | 29.00 |
| 258/4 | 712.21 | 1955.25 | 646.06 | 653.31 | 71.00 |

## Interpretation

The previous PHP staged median was 23.960 ms; this run is 25.817 ms (+7.8%). Preparation was 2.066 ms and is now 2.254 ms (+0.188 ms). Unchanged incremental was 4.982 ms and is now 5.474 ms; a one-file edit was 5.417 ms and is now 5.503 ms. These are separate runs across several compiler changes, with overlapping ranges: they do not establish a performance regression caused by generated type definitions.

The current 258-unit native build at eight jobs was previously 2,167.65 ms and is now 2,206.12 ms. Grouping remains the much larger lever on this synthetic sample: 4–8 units take approximately 164–171 ms. The ranges overlap; neither grouping size is a demonstrated winner. Grouping also changes LLVM optimization visibility. Incremental native recompilation tradeoffs were not measured.

## Reproduction and artifacts

```bash
XDEBUG_MODE=off php v0.2/samples/my-try-simple/benchmark.php --prepare
XDEBUG_MODE=off python3 -B v0.2/samples/my-try-simple/grouping-benchmark.py \
  --samples 5 --output v0.2/build/my-try-simple-generated-types-2026-11-04
```

- [PHP raw results](benchmark-2026-11-04-generated-types-preparation.json)
- [Native raw results](benchmark-2026-11-04-generated-types-native.json)
- Build logs, generated files, executable artifacts and Clang traces: `v0.2/build/my-try-simple-generated-types-2026-11-04/`.
