# Scanning and appending ordinary spans after JSON escapes

Doc Status: planning

The fused decoder is the baseline from the [string-vector study](../json_string_batches/README.md). This experiment compares three changes in isolated checkouts on dev002. **Bulk** retains the baseline scalar scanner and appends the entire ordinary span following each decoded escape. **Word8** adds portable eight-byte detection to the prefix scanner used on both sides of an escape. **AVX2** instead uses a 32-byte comparison and bit mask. The input copy, full UTF-8 prevalidation, escape parser, fused array loop, result type and string ownership are the same across candidates.

## Throughput

Decimal MB of wire JSON per second. Each vector has 16 equal strings. For a given length, every fixture decodes to the same repeating lowercase alphabet; exactly one literal character is replaced by its six-byte Unicode escape in the first, middle or last position. The three escaped documents have identical wire lengths. The plain control contains no escapes.

| Decoded bytes per string | Escape | Fused baseline | Scalar bulk | 8-byte bulk | AVX2 bulk | Node.js 22.23.4 |
|---:|---|---:|---:|---:|---:|---:|
| 8 | plain | 320.5 | 322.0 | 363.5 | 355.8 | 233.6 |
| 8 | first | 228.6 | 277.6 | 265.9 | 275.0 | 252.4 |
| 8 | middle | 251.4 | 267.6 | 251.1 | 265.4 | 253.7 |
| 8 | last | 286.3 | 282.7 | 263.4 | 277.9 | 252.8 |
| 129 | plain | 655.9 | 657.8 | 1,106.4 | 1,261.0 | 1,720.4 |
| 129 | first | 223.4 | 601.4 | 916.7 | 1,015.5 | 1,383.6 |
| 129 | middle | 322.1 | 563.7 | 861.1 | 953.7 | 1,371.9 |
| 129 | last | 614.7 | 613.2 | 968.2 | 1,053.3 | 1,375.6 |
| 4,096 | plain | 1,079.1 | 1,057.2 | 2,621.8 | 3,444.7 | 3,291.8 |
| 4,096 | first | 222.4 | 1,039.8 | 2,491.8 | 3,354.5 | 2,094.3 |
| 4,096 | middle | 426.6 | 1,060.2 | 2,480.7 | 3,269.6 | 2,166.5 |
| 4,096 | last | 602.5 | 611.4 | 922.4 | 1,021.7 | 2,169.5 |

The scalar bulk vs fused columns isolate the effect of appending the suffix as one span. The wider variants also accelerate the initial prefix scan. At 4,096 bytes the bulk improvement follows suffix length. The AVX2 prototype exceeds Node for the first and middle escape but trails it for the last escape. The timing does not isolate the remaining cost of constructing and possibly growing the native output string. Small strings do not consistently benefit from the wider scans.

## Scanner and validation

The eight-byte loop loads only complete words with memcpy. XOR with a repeated quote or backslash converts an equality into a zero byte. Masking with repeated 0xe0 yields zero for controls below 0x20. A standard word-level zero-byte test detects a candidate block; the scalar scanner locates the first marker there and handles the tail. The AVX2 loop compares 32 lanes, combines the quote, backslash and unsigned control masks, then extracts a bit mask. The first set bit gives the byte offset. Disassembly of the dev002 binary confirmed vpcmpeqb, vpminub and vpmovmskb. Neither experiment changes the UTF-8 validator. The AVX2 patch targets a known-capable benchmark machine; deployment would need an appropriate CPU target or runtime selection.

The correctness corpus covered 390 valid and 422 invalid documents per candidate. All decoded bytes matched an independent oracle; all error messages, byte offsets and paths matched the fused baseline. Escape sequences, control characters, invalid UTF-8 and truncation occur at varying offsets. A separate protected-page probe tested every possible byte at every offset for input lengths 0–96, with no mismatched marker or read past the end. The AVX2 candidate also passed all 424 JSON-enabled default tests on dev002.

## Measurement and reproduction

Dev002 used an AMD Ryzen 9 5950X with AVX2, Clang 18.1.3, libc++, and eleven shuffled timing rounds on CPU 7. The existing C++ and Node drivers warm up and then decode whole documents and observe array length. Node receives a JS string prepared before timing; Simple C++ includes its input copy, UTF-8 validation and typed schema checks. Correctness checks and process startup are outside timing. Raw samples also include C++ counts of calls and bytes visible to the scalar operator-new linker wrapper; those counters are not a complete trace of library-internal allocations.

Start from f66b7b943 plus grouped.patch and fused.patch from the parent batching study. Keep the unmodified fused binary. Apply bulk.patch to all three candidate source trees, then apply word8.patch or avx2.patch to the respective copies. Build each JSON-enabled publication and the shared typed-string timing/count drivers as in the parent study, using --batched. Name the driver directories fused, bulk, word8 and avx2.

```sh
taskset -c 7 python3 run.py "$bin" > dev002.json
clang++ -O3 -std=c++23 -I "$candidate/runtime/src/json" check_scan.cpp -o check_scan
./check_scan
```

[Raw measurements](dev002.json) · [Intel instruction reference for extracting per-byte masks](https://cdrdv2-public.intel.com/671110/325383-sdm-vol-2abcd.pdf)
