# Output growth at the last JSON escape

Doc Status: planning

On dev002 the AVX2 bulk decoder filled a 4,095-byte output buffer with the literal prefix. Appending the final ASCII character moved the buffer and raised capacity to 8,191 bytes every time. Reserving room for one decoded byte before copying the prefix brought the median throughput of a 16-string document from 982 to 3,203 MB/s. The two neighboring lengths already had spare capacity and ran at about 3,300 MB/s with the original decoder.

## Results

Each string ends in a six-byte Unicode escape for one ASCII character. The two builds use identical wire input and produce identical bytes. The reserve prototype changes construction of the output in parse_string_tail. It reserves prefix.size() + 1 on the empty string, appends the prefix, then executes the existing escape logic. The prototype applies that policy to all string tails. The original full-document binary is the AVX2 variant of the [suffix study](../json_string_suffix/README.md).

| Decoded bytes | Original capacity before → after | Buffer moves per 100,000 appends, original / reserve | Median append ns, original / reserve | Median µs per 16-string document, original / reserve |
|---:|---:|---:|---:|---:|
| 8 | 22 → 22 | 0 / 0 | 23.1 / 22.9 | 0.969 / 1.057 |
| 23 | 22 → 47 | 100,000 / 0 | 28.2 / 23.4 | 1.371 / 1.492 |
| 129 | 135 → 135 | 0 / 0 | 23.4 / 23.5 | 2.136 / 2.366 |
| 4,095 | 4,095 → 4,095 | 0 / 0 | 23.2 / 23.3 | 19.639 / 19.873 |
| 4,096 | 4,095 → 8,191 | 100,000 / 0 | 90.6 / 23.4 | 66.851 / 20.503 |
| 4,097 | 4,103 → 4,103 | 0 / 0 | 23.4 / 23.5 | 19.924 / 20.085 |

The reserve build kept a capacity of 4,103 at the 4,096-byte append. All eleven paired document rounds favored reserve at that length. The 4,096-byte plain control took 19.551 / 20.053 µs. At 8, 23 and 129 bytes all eleven escaped-document rounds favored original construction. Avoiding a growth at the inline-storage boundary (23) still costs more overall with this prototype.

## Allocator control

The isolated append difference is about 67 ns per string. It cannot by itself explain 46 µs saved per document. A second experiment ran the same uninstrumented binaries with the default glibc allocator settings and with a process-local 16 MiB trim threshold. Each timing cell is a median over five shuffled rounds on CPU 7.

| Bytes | Decoder | Default µs/doc | Raised threshold µs/doc | Minor faults/doc, default / raised |
|---:|---|---:|---:|---:|
| 4,095 | Original | 19.72 | 19.73 | 0.071 / 0.068 |
| 4,095 | Reserve | 20.03 | 20.00 | 0.070 / 0.067 |
| 4,096 | Original | 66.83 | 21.82 | 10.073 / 0.073 |
| 4,096 | Reserve | 19.99 | 19.91 | 0.070 / 0.068 |

The 4,096-byte growth changes allocation size and copies the prefix. Increasing glibc's trim threshold recovers most of the original driver's lost time with the same decoder code. The concurrent disappearance of roughly ten minor faults per document implicates heap trimming and page reuse on this workload. The experiment counts process-wide minor faults and measures the complete allocation lifecycle. It does not attribute individual faults to a particular allocator call. The capacity boundary and timings are specific to this libc++, allocator and workload.

## Reproduction and checks

Use the preceding study's dev002 AVX2 checkout and its bin/avx2 drivers. Make a separate copy of the checkout, apply reserve.patch, rebuild its JSON-enabled Release publication, and run the shared typed_json_strings/build.py with --batched into the new study work directory's bin/reserve. Build the probes and run on dev002 with the same Clang 18.1.3 and libc++ toolchain as the parent study.

```sh
python3 build_probe.py "$original/runtime/src/json/reader.hpp" "$work/probe-base"
python3 build_probe.py "$reserve/runtime/src/json/reader.hpp" "$work/probe-reserve"
taskset -c 7 python3 run.py "$suffix_study" "$work" > dev002.json
taskset -c 7 python3 allocator.py "$suffix_study" "$work" > allocator.json
```

The lexical probe measures capacity and data address immediately around the actual Unicode append. It times only appending the already decoded byte with steady_clock. Both timings include clock overhead, and repeated single-string parsing reuses warm allocator storage. The document drivers have no probe instrumentation. They decode a typed vector of 16 strings, observe its length and destroy the result each iteration. Eleven rounds shuffle original/reserve order for each measurement. The correctness check compares both drivers on 48 valid and malformed boundary documents plus 12 timing fixtures, checking decoded bytes against Python and comparing diagnostics verbatim. Fault counts include startup, 375 warmups and 3,000 timed decodes per process, divided by 3,375. Raw results include the plain controls and every timing sample.

[Paired measurements](dev002.json) · [Allocator control](allocator.json)

## Selective reserve for long, full prefixes

On dev002, reserving one extra byte only for long prefixes that would fill their buffer recovered the 4,096-byte gain in all eleven paired rounds. At 4,095 and 4,097 bytes median decode time moved by at most 0.2%. Short-string medians ranged from 0.4% faster to 1.7% slower than the original.

| Decoded bytes per string | Original µs/doc | Always reserve µs/doc | Selective µs/doc | Selective time change vs original |
|---:|---:|---:|---:|---:|
| 8 | 0.957 | 1.051 | 0.953 | −0.4% |
| 23 | 1.369 | 1.494 | 1.373 | +0.3% |
| 129 | 2.114 | 2.357 | 2.151 | +1.7% |
| 4,095 | 19.526 | 19.762 | 19.554 | +0.1% |
| 4,096 | 65.375 | 19.824 | 19.922 | −69.5% |
| 4,097 | 19.553 | 19.769 | 19.599 | +0.2% |

Each document has 16 strings ending in a single ASCII character spelled as a Unicode escape. The three drivers ran in shuffled order on CPU 7. At 4,096 bytes, selective reserve sustained 3,297 MB/s. The probe observed zero moves in 100,000 final appends per round, with capacity staying at 4,103. At 23 bytes it retained the original inline-to-heap transition. The 4,096-byte plain control differed by 0.1% in median time.

The experiment defines a long prefix as at least 1,024 bytes. It inspects the prefix length before constructing the string. In dev002's libc++, allocation for a long string rounds the requested bytes including its terminator up to a multiple of eight. The prototype uses this to detect a full buffer without allocating it first. The capacity probe compared the prediction against actual string construction at each of 64,513 lengths from 1,024 through 65,536 inclusive. All matched, including 8,064 full buffers. The cutoff is an experimental choice and the rounding rule needs qualification on any other standard-library build.

All 424 JSON-enabled default tests passed on dev002 with the selective prototype. All three drivers agreed on 58 valid documents and on all 54 invalid-document diagnostics, including error text, byte offset and path. The corpus exercises prefixes on both sides of the cutoff, escaped NUL, supplementary characters, multiple escapes, malformed escapes, invalid UTF-8, control bytes and truncation.

To reproduce, apply selective.patch to a fresh AVX2 baseline and build the batched drivers in the third work directory under bin/selective. Run the capacity check and build the append probe with the same toolchain. Pass the third directory to the study runner to compare all three variants.

```sh
clang++ -stdlib=libc++ -std=c++23 -O3 check_capacity.cpp -o "$third/check_capacity"
"$third/check_capacity"
python3 build_probe.py "$selective/runtime/src/json/reader.hpp" "$third/probe-selective"
taskset -c 7 python3 run.py "$suffix_study" "$work" "$third" > selective.dev002.json
```

[Three-way measurements](selective.dev002.json) · [Experimental patch](selective.patch)

## Selective reserve after synchronous input borrowing

The baseline is the verified input-borrowing decoder at `c1c12d66b`. Two isolated copies received borrowed_selective.patch. One ran the capacity qualification on dev002 with the same Release toolchain used for the candidate. The other skipped qualification and compiled the ordinary construction path. All three builds retain input borrowing, the eight-byte scanner, bulk suffix appends, and owning result fields.

The experimental qualification is opt-in for a native build. CMake enables the capacity prediction only if the executable compiles, runs, and succeeds. Cross builds skip the probe. On dev002, all 64,513 tested prefix lengths from 1,024 through 65,536 agreed with the eight-byte rounding rule, including 8,064 full buffers. The candidate only predicts within that range and only when an escape follows the prefix. A focused native Clang/libstdc++ check returned a capacity mismatch, built the fallback without the optimization definition, and decoded the 4,095-byte prefix plus final escaped character correctly.

Median microseconds per document of 16 equal-length strings, each ending with one ASCII character encoded as a Unicode escape. Positive time savings favor the qualified candidate. Each paired round shuffled the builds on CPU 7. The final column counts buffer moves per 100,000 final appends in the separate instrumented lexical reader.

| Decoded bytes/string | Committed µs | Fallback µs | Qualified µs | Time saved vs committed | Moves, committed → qualified |
|---:|---:|---:|---:|---:|---:|
| 8 | 1.002 | 0.999 | 0.954 | +4.8% | 0 → 0 |
| 23 | 1.432 | 1.445 | 1.417 | +1.0% | 100,000 → 100,000 |
| 129 | 2.019 | 2.013 | 2.000 | +0.9% | 0 → 0 |
| 4,095 | 26.438 | 26.464 | 26.729 | -1.1% | 0 → 0 |
| 4,096 | 59.843 | 58.179 | 52.103 | +12.9% | 100,000 → 0 |
| 4,097 | 56.096 | 55.858 | 55.840 | +0.5% | 0 → 0 |

For each candidate the typed scalar and vector drivers matched the decoded-byte oracle on 780 valid documents and the baseline on all 844 malformed diagnostics, including offsets and paths. The ownership probe overwrote and destroyed the input before checking decoded values. The timing harness also checked lengths around the qualification cutoff, valid Unicode, malformed escapes, control bytes, invalid UTF-8 and truncation.

To reproduce, apply borrowed_selective.patch to two clean copies of the baseline. Build both with the default Release preset and JSON enabled, passing `-DSCPP_STUDY_QUALIFY_RESERVE=ON` only for the qualified copy. Use the shared typed_json_strings builder with --batched to produce bin/base, bin/fallback and bin/selective. Use build_probe.py to build probe-base, probe-fallback and probe-selective from their corresponding reader headers. Pass `-DSCPP_JSON_MEASURED_CAPACITY_8=1` to the instrumented probe only when the qualified build reports a successful match. Run `taskset -c 7 python3 run.py --borrowed "$work"` on dev002.

[Raw measurements including plain controls and per-append timings](borrowed.dev002.json) · [Qualified experiment patch](borrowed_selective.patch)

The 4,096-byte baseline samples in the first run ranged from 43.6 to 63.8 µs. A second run interleaved all three lengths for 15 rounds, with 1,500 timed documents per sample. Both runs used the same executables and CPU. The timing delta includes allocator and system effects; the capacity and buffer-move observations came from the separate probe.

| Bytes/string | Committed µs | Fallback µs | Qualified µs | Time saved vs committed | Qualified faster in paired rounds |
|---:|---:|---:|---:|---:|---:|
| 4,095 | 25.252 | 25.217 | 25.101 | +0.6% | 11/15 |
| 4,096 | 27.296 | 27.316 | 25.178 | +7.8% | 14/15 |
| 4,097 | 25.012 | 25.032 | 24.999 | +0.1% | 8/15 |

All 425 JSON-enabled default tests passed in each of the qualified and fallback builds. [Boundary repeat measurements](recheck.dev002.json)
