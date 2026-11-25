# First-match byte search

The fixture compiles PHP, JS, and C++ source functions through the project compiler.
Each flavor has a two-argument and a three-argument caller. The strings are passed
by reference. PHP returns the full falseable integer across the application
function boundary. The driver checks the result and consumes its payload.

## LLVM and include boundaries

| Source | Two arguments | Third argument | Result |
|---|---|---|---|
| PHP `strpos` | Narrow PHP adapter calls `str::find` | Adapter supplies `signed_offset` | `result_or_false_int64` |
| JS `indexOf` | Direct `str::find` | Generated call supplies `clamp` | Signed byte index, −1 on miss |
| C++ `find` | Direct `str::find` | Generated call supplies `nonnegative` | Signed byte index, −1 on miss |

Only PHP pulls in the falseable integer declaration. The shared native header
forward-declares the string and exposes the offset policy. In the Clang 18.1.3
ordinary optimized IR, both integer-returning flavors tail-call the native
search. PHP also calls a carrier constructor on success or the false factory on
a miss. With ThinLTO, the PHP wrapper, factory and constructor are inlined. An
escaping successful PHP result calls `operator new(24)`; a miss stores a null
storage pointer. All six tested callers retain exactly one native search call.
These observations concern calls inside the application functions. The search
algorithm, driver-side extraction, and destruction have their own costs.

[PHP LLVM IR](results/php.ll) · [JS LLVM IR](results/js.ll) · [C++ LLVM IR](results/cpp.ll)

## dev002 measurements

Locally built x86-64 Linux executables ran on dev002, an AMD Ryzen 9 5950X,
pinned to logical CPU 8. Both sides used Clang 18.1.3, C++23, libc++, LLD,
`-O2 -g0`, and static linkage of the C++ standard library. Each binary had one
warm-up run. Twelve paired rounds alternated baseline/candidate order and
randomized the order of the ordinary and ThinLTO pairs. The driver timed
512,000 searches per case per run. Each case used the same shuffled 1,024
inputs, half 16-byte and half 128-byte subjects, a three-byte embedded-NUL
needle, and equal shares of early hits, middle hits, end hits and misses.
The third argument was one. The timed loops include consumption of the PHP
carrier and verify their checksums. Input construction is outside the timer.

The baseline used the previous compiler's emitted flavor adapters and matching
runtime. Candidate and baseline executable `.text` sections were byte-identical
within each build mode. The ordinary SHA-256 was
`72bfd4c23c5f0870ff256292d26358dc7db0093580c0b24f87847251bef445a5`.
The ThinLTO SHA-256 was
`e6bbee19e8f91121b47c787bfc0c889c9de756e94647e73ebc9ba9546053042f`.

| Caller | Ordinary baseline → candidate (ns/search) | ThinLTO baseline → candidate (ns/search) |
|---|---:|---:|
| PHP, 2 | 251.96 → 252.42 | 243.38 → 242.13 |
| PHP, 3 | 247.38 → 245.56 | 247.83 → 243.65 |
| JS, 2 | 240.94 → 238.43 | 232.84 → 227.69 |
| JS, 3 | 234.66 → 235.11 | 237.32 → 233.20 |
| C++, 2 | 239.79 → 239.79 | 233.14 → 228.27 |
| C++, 3 | 235.23 → 235.29 | 237.02 → 232.53 |

Each cell reports the two sample medians. The raw report also gives the median
and interquartile range of the 12 **paired** percentage differences. The
largest absolute paired median change was 1.18%. Identical executable code
supports treating those small differences as timing variation for this workload.
[Raw samples and paired summaries](results/dev002.json)

## Local compilation cost

Each source unit contains both arities. Measurements used one compile at a time,
warm filesystem caches, no PCH, ordinary `-O2 -g0`, and 12 interleaved,
uninstrumented compiles per cell. A separate Clang time trace sampled frontend
and backend work. These are per-unit measurements; linking and runtime compilation
are outside this table.

| Unit | Preprocessed bytes before → after | Median compile wall time before → after |
|---|---:|---:|
| PHP | 56,909 → 6,643 | 66.93 → 50.80 ms |
| JS | 12,440 → 4,042 | 50.80 → 46.89 ms |
| C++ | 12,456 → 4,048 | 51.12 → 48.33 ms |

[Compile timings and trace components](results/local-compile.json)

## Reproducing the checks

With the v0.2 default runtime and compiler publications built, run from this
directory:

```sh
python3 build.py /tmp/strpos-candidate
```

Each build mode produces an executable named `bench`, readable LLVM modules,
and a demangled `calls.json`. The ordinary modules come from Clang's
`-S -emit-llvm`. The ThinLTO modules come from LLD's saved post-optimization
bitcode via the matching `llvm-dis`. The native driver object keeps the timed
application boundary observable.

To compare revisions, build the same fixture against each revision. The builder
also accepts `--emitted` and `--runtime` for a saved application bundle and its
matching generated runtime. Put the four executables in a directory as
`old-ordinary`, `new-ordinary`, `old-thin`, and `new-thin`, then run
`python3 compare.py DIRECTORY --cpu N` with an available logical CPU.
