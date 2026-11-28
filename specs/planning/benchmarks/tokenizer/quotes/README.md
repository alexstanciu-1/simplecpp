# Quoted tokens on dev002

Doc Status: planning

The quote-free comparison uses the native scanner from `9f3a1ca3d` and the
scanner with quoted tokens. Both use the same production compiler, runtime,
driver, inputs and Clang 18.1.3 flags (`-O2 -g0`, libc++). The scanner validates
UTF-8, scans the supplied bytes, builds a vector of token spans and releases it
on every pass. Inputs are built before the timed loop. Parsing, literal decoding,
process startup and file I/O are outside the interval.

The host was dev002 (Ryzen 9 5950X), pinned to logical CPU 6. Each process
times 256 passes using the monotonic clock. The quote-free sample scans 16.5 MiB
and the string-heavy sample scans 18 MiB. The probe checks the total token count
including the end sentinel on every sample. Discard the first round and use the
median of 11 rounds. Quote-free before/after order alternates each round.

| Flavor | Quote-free before, MiB/s | Quote-free after, MiB/s | Change | String-heavy after, MiB/s |
|---|---:|---:|---:|---:|
| PHP | 70.51 | 71.43 | +1.3% | 118.42 |
| C++ | 70.82 | 71.12 | +0.4% | 118.42 |
| JS | 71.74 | 71.43 | −0.4% | 118.42 |
| Python | 73.33 | 73.33 | 0.0% | 123.29 |

String-heavy input contains UTF-8 text, escaped delimiters and comment markers
inside quotes. Each 36-byte pattern produces five tokens; the 33-byte quote-free
pattern produces nine. Byte throughput across these patterns reflects that
difference in token density. The old scanner rejected the string-heavy input.

To reproduce, build the datetime-enabled configuration on dev002, then run from
the repository root:

```sh
git show 9f3a1ca3d:compiler/examples/translated_path/frontend.phs > /tmp/baseline-frontend.scpp
XDEBUG_MODE=off taskset -c 6 python3 -B specs/planning/benchmarks/tokenizer/quotes/measure.py /tmp/baseline-frontend.scpp
```

[Probe](measure.py) · [Raw samples](dev002.csv)
