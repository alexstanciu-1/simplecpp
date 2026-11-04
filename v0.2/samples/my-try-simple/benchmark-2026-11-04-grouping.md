# Generated C++ grouping experiment — 2026-11-04

Doc Status: supporting

Local WSL, Ryzen 5 PRO 4650G (6 cores/12 logical CPUs), PHP 8.5.7 without Xdebug, Clang 18.1.3, mold 2.40.4. Compiler revision `40a046e72`; compiler implementation and shared build defaults unchanged.

257 source files / 42,195 bytes. Grouping is performed solely in the benchmark harness, by concatenating the 258 emitted files into 1, 4 or 8 files. Counts include the entry adapter. No functions are manually rewritten, removed or renamed.

O2, g0, libc++, mold, no PCH/LTO/ccache; prebuilt runtime archive. Warm caches, fresh app objects. Round-robin concatenation of sorted generated paths, including entry adapter in the requested total. All cases j8 except explicit j4 control. Separate instrumented phase traces; cumulative elapsed, not CPU or wall time. Total includes PHP startup/loading/preparation/emission/writes plus grouping and native build. Grouping preserves emitted function definitions but allows ordinary intra-unit LLVM optimization across functions.

Seven rotated samples/configuration. Each compiles all application sources from fresh objects; module loading and generated-file writes are included in the PHP process time. Runtime rebuilding, setup, cleanup and correctness assertions are outside timers. No incremental object-reuse result is claimed.

## Native and complete build timing

All times in milliseconds; ranges are min–max. Speedup compares native build medians against the newly measured 258-unit j8 baseline.

| Units | Ninja jobs | Native median [range] | Total median [range] | Native speedup |
| ---: | ---: | ---: | ---: | ---: |
| 1 | 8 | 214.14 [209.04–322.63] | 311.90 [310.35–463.12] | 10.12× |
| 4 | 8 | 147.78 [131.79–176.27] | 244.84 [227.23–288.84] | 14.67× |
| 8 | 8 | 152.69 [139.69–185.19] | 249.38 [236.62–287.67] | 14.20× |
| 258 | 8 | 2167.65 [1967.47–2513.49] | 2271.40 [2063.44–2611.61] | 1.00× |
| 258 | 4 | 3189.66 [2910.28–4115.40] | 3280.70 [2998.25–4227.87] | 0.68× |

## Components

Medians below are calculated independently. Native compile window is parallel elapsed time; compile sum adds durations of overlapping Clang processes. Link-command timing includes driver startup. Grouping time includes reading existing generated C++ and writing concatenated files; no grouping is needed for 258 units.

| Units/jobs | PHP process | Group/write | Compile window | Compile sum | Link command | Executable bytes |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| 1/8 | 92.72 | 6.19 | 157.00 | 157.00 | 54.00 | 43216 |
| 4/8 | 92.35 | 6.09 | 92.00 | 316.00 | 53.00 | 43312 |
| 8/8 | 92.08 | 7.49 | 91.00 | 628.00 | 60.00 | 43456 |
| 258/8 | 98.81 | 0.00 | 2089.00 | 16456.00 | 74.00 | 68120 |
| 258/4 | 93.63 | 0.00 | 3111.00 | 12372.00 | 74.00 | 68120 |

## Separate instrumented frontend / LLVM / linker profile

One independent instrumented build per configuration. Frontend and backend are cumulative elapsed phase durations across units, not parallel wall time or CPU measurements. Optimizer and CodeGenPasses are nested within Backend and must not be added to it. mold wall is its internal `--perf` report, excluding the Clang driver.

| Units/jobs | Frontend | LLVM backend | Optimizer (nested) | Codegen passes (nested) | mold wall |
| --- | ---: | ---: | ---: | ---: | ---: |
| 1/8 | 40.73 | 128.94 | 61.88 | 65.17 | 14.00 |
| 4/8 | 44.15 | 194.84 | 58.44 | 128.96 | 17.00 |
| 8/8 | 67.69 | 218.17 | 83.41 | 118.68 | 14.00 |
| 258/8 | 660.37 | 1933.95 | 654.26 | 633.04 | 23.00 |
| 258/4 | 483.96 | 1341.98 | 466.28 | 425.82 | 29.00 |

## Interpretation and scope

The j4 row is a contemporaneous control, so the thread-count comparison does not depend on yesterday’s or a previous run’s host load. With 1/4 grouped units, Ninja can run at most 1/4 application compile jobs even with `-j8`.

This sample consists of tiny functions containing dead integer locals and a chain of void calls. Grouping also lets LLVM see definitions across original file boundaries: this is a real unity-build effect, not a pure compiler-process-count experiment. There is no LTO. Results do not establish an optimal grouping for larger bodies or incremental edits. A changed group would require recompiling the whole group; that tradeoff was not measured here.

All 35 measured builds printed exactly `my-try-simple: OK\n`, returned 8 and had no debug sections. The final executable of every configuration still defines all 256 sample functions (`nm` checked).

No permanent compiler grouping or Ninja-default changes were made.

## Reproduce and artifacts

```sh
python3 -B v0.2/samples/my-try-simple/grouping-benchmark.py \
  --output v0.2/build/my-try-simple-grouping-new-run --samples 7
```

Output must be a new directory. Generated/combined C++, executables, Ninja graphs/logs and Clang/mold traces are retained at:

/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/build/my-try-simple-grouping-2026-11-04

[Raw samples and phase totals](benchmark-2026-11-04-grouping.json).
