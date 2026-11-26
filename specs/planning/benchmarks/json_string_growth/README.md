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
