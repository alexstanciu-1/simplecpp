# Fixed-length keyword matches in the bounded scanner

Doc Status: supporting

The bounded scanner gives little evidence for changing production keyword recognition. Caching the pointer removed accessor calls inside the loop. The mixed corpus ran 0.9–1.4% faster, while the operator-only control moved 2.6% in every flavor. The identifier corpus ranged from −0.5% to +0.6%. Consider a general binary match operation on its API merits and verify performance on a real consumer before treating it as a compiler optimization.

## Match using the string accessor

| Flavor | Synthetic input | Ordinary MiB/s | Match MiB/s | Paired throughput change | Middle 50% | Faster pairs |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| cpp | identifiers | 731.1 | 722.8 | -1.2% | -1.4% to -1.1% | 2/16 |
| cpp | operators | 386.7 | 397.1 | +2.4% | +1.4% to +2.8% | 12/16 |
| cpp | mixed | 447.5 | 446.3 | -0.3% | -0.8% to +0.1% | 4/16 |
| php | identifiers | 730.3 | 730.0 | +0.3% | -1.0% to +0.9% | 10/16 |
| php | operators | 343.0 | 337.4 | -1.7% | -2.3% to -1.3% | 1/16 |
| php | mixed | 441.2 | 448.2 | +0.6% | -0.2% to +1.0% | 12/16 |
| js | identifiers | 725.7 | 705.3 | -2.5% | -3.3% to -1.8% | 1/16 |
| js | operators | 383.9 | 392.4 | +2.2% | +1.5% to +2.5% | 12/16 |
| js | mixed | 436.2 | 443.2 | +0.2% | -0.5% to +3.1% | 10/16 |

## Native build cost

| Flavor | Mode | Compile ms | Link ms | Compile + link ms | Frontend trace ms | Backend trace ms |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| cpp | match | 299.4 | 55.0 | 353.4 | 261.1 | 33.6 |
| cpp | ordinary | 305.7 | 54.4 | 360.3 | 254.2 | 39.1 |
| php | match | 296.4 | 54.1 | 350.8 | 254.8 | 33.3 |
| php | ordinary | 297.5 | 54.8 | 353.4 | 253.6 | 36.1 |
| js | match | 300.4 | 54.8 | 355.1 | 258.2 | 34.0 |
| js | ordinary | 307.3 | 55.7 | 363.5 | 262.2 | 38.7 |

The first trial calls the runtime string's `data()` accessor in the length-matched keyword branch. Inspection of the measured object relocations found one accessor call site in each ordinary scanner and four, three and four in the cpp, php and js trial scanners respectively. There was no external `memcmp` or `bcmp` call. Static call-site counts describe the generated code; the token length determines which branch actually runs.

## Controls and scope

Both modes used the current generated scanner, its checked byte view, ordinary vector append, a previous-token-count reserve hint, and the existing source and token-vector transfers. The trial checks the complete identifier length, validates the offset and remaining byte count, then compares the fixed-length pattern. The equals lookahead and all other token logic are identical. Preparing the comparison asserts that the only source changes are the keyword condition, the helper include and, in the cached trial, its one scan-local pointer.

On dev002, both executables reproduced the PHP lexer oracle on all 11 inputs per flavor, including failure, and the four-input edit/recovery sequence. The local boundary fixture additionally checked embedded NUL, a high byte, insufficient remaining bytes, extreme offsets and an empty pattern. Its byte-vector case uses a native control container.

The throughput workloads are synthetic. Each repeats a 16–44-byte sequence 512 times. Identifiers exercise keywords and near misses; mixed input includes identifiers, punctuation and whitespace. The operator-only control cannot reach keyword matching. Its paired differences capture variation from other sources, including possible native code layout effects. Each executable scanned an already available input in memory, using the previous count. Each process returned nine samples of 128 whole scans including result destruction. A pair used one process median per mode, with one warm-up pair discarded and 16 retained pairs run in alternating order. MiB/s uses the median time in each mode; percentage changes use the median within-pair throughput ratio.

Clang 18.1.3 ran serially on dev002 CPU 7 with `-std=c++23 -O2 -g0`, its default standard library and linker, warm filesystem, no LTO, PCH or compiler cache. The runtime was compiled once with function and data sections; both links used `--gc-sections` and reused the runtime and driver objects. Build columns are medians of five compiler or linker commands, with compile-plus-link measured per pair. Frontend and backend totals come from a separate traced compilation. These are scanner TU and executable costs; PHP preparation and native build orchestration were outside the timer.

## Match using a scan-local pointer

This second run pairs a fresh ordinary build with a candidate acquiring the source pointer once after the checked view is created. Each candidate object had two accessor call sites at scan setup (the view and the matcher) and none inside keyword recognition. The ordinary source hashes and build flags matched the first run. The percentages compare each candidate to its own paired baseline.

| Flavor | Synthetic input | Ordinary MiB/s | Match MiB/s | Paired throughput change | Middle 50% | Faster pairs |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| cpp | identifiers | 730.2 | 731.1 | +0.3% | -0.1% to +2.4% | 12/16 |
| cpp | operators | 399.1 | 409.7 | +2.6% | +2.0% to +3.3% | 14/16 |
| cpp | mixed | 452.7 | 460.2 | +1.4% | +0.8% to +3.4% | 14/16 |
| php | identifiers | 722.6 | 734.2 | +0.6% | +0.3% to +2.9% | 14/16 |
| php | operators | 388.3 | 398.4 | +2.6% | +1.2% to +2.8% | 13/16 |
| php | mixed | 453.3 | 459.3 | +1.3% | +0.8% to +2.2% | 13/16 |
| js | identifiers | 727.1 | 724.7 | -0.5% | -0.7% to -0.2% | 2/16 |
| js | operators | 391.0 | 400.3 | +2.6% | +2.2% to +2.8% | 13/16 |
| js | mixed | 451.0 | 455.7 | +0.9% | +0.8% to +1.5% | 14/16 |

### Cached variant build cost

| Flavor | Mode | Compile ms | Link ms | Compile + link ms | Frontend trace ms | Backend trace ms |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| cpp | match | 298.8 | 53.1 | 351.9 | 256.0 | 33.8 |
| cpp | ordinary | 301.6 | 54.2 | 355.5 | 255.7 | 39.4 |
| php | match | 297.6 | 55.0 | 352.6 | 253.0 | 32.7 |
| php | ordinary | 298.4 | 54.0 | 352.4 | 251.9 | 36.0 |
| js | match | 295.0 | 54.3 | 348.0 | 256.2 | 33.4 |
| js | ordinary | 301.4 | 55.5 | 358.4 | 251.1 | 38.0 |

## Reproduce

Run `fixed_match_bench.py prepare DIR` or `fixed_match_bench.py prepare-cached DIR` with Python 3 from a checkout with the Clang runtime and compiler publication available. On the benchmark host run `taskset -c 7 python3 -B fixed_match_bench.py bench .` inside the prepared directory. [Benchmark runner](fixed_match_bench.py) · [Ordinary-accessor pairs](fixed_match.dev002.json) · [Cached-pointer pairs](fixed_match_cached.dev002.json)
