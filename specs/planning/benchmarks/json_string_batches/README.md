# Batching typed JSON string vectors

Doc Status: planning

## Results

For 128 eight-byte strings, the fused loop reduced median decode time by 64.6% for plain strings and 44.9% for escaped strings. Each nonempty shape had a lower fused time than baseline in every paired round. Comparing grouped and fused isolates the added benefit of consuming strings and delimiters together while deferring successful element paths. The experiment bundles these changes; it does not attribute the savings to path formatting alone. For the longest strings the two provider loops were close. On that shape most of the measured improvement was already present with the grouped loop.

| Vector shape | Spelling | Baseline µs | Grouped µs | Fused µs | Fused time reduction vs baseline | Allocations per document, all variants |
|---|---|---:|---:|---:|---:|---:|
| 0 × 8 | plain | 0.155 | 0.153 | 0.153 | 1.1% | 2 |
| 1 × 8 | plain | 0.247 | 0.252 | 0.194 | 21.5% | 4 |
| 1 × 8 | escaped | 0.298 | 0.299 | 0.241 | 19.4% | 5 |
| 3 × 8 | plain | 0.389 | 0.396 | 0.246 | 36.6% | 5 |
| 3 × 8 | escaped | 0.485 | 0.506 | 0.341 | 29.7% | 5 |
| 16 × 8 | plain | 1.302 | 1.261 | 0.586 | 55.0% | 7 |
| 16 × 8 | escaped | 1.751 | 1.747 | 1.045 | 40.3% | 7 |
| 16 × 129 | plain | 4.453 | 4.023 | 3.266 | 26.7% | 39 |
| 16 × 129 | escaped | 8.131 | 7.693 | 6.929 | 14.8% | 39 |
| 16 × 4096 | plain | 77.411 | 63.706 | 62.769 | 18.9% | 39 |
| 16 × 4096 | escaped | 172.873 | 158.683 | 157.518 | 8.9% | 39 |
| 128 × 8 | plain | 8.843 | 8.432 | 3.129 | 64.6% | 10 |
| 128 × 8 | escaped | 12.440 | 12.236 | 6.854 | 44.9% | 10 |
| 128 × 129 | plain | 34.988 | 31.525 | 25.287 | 27.7% | 266 |
| 128 × 129 | escaped | 63.409 | 59.532 | 53.747 | 15.2% | 266 |

Counts and total requested bytes were identical across all three binaries on every measured shape. Batching reduced processing time after the previous string ownership optimization. The dev002 measurements compare binaries from this run; absolute timings from the preceding study were taken in a different run.

## Experiment

Baseline commit f66b7b943 constructs plain strings from the input span and transfers long decoded escape buffers. The benchmark compiles three isolated JSON-enabled publications. Each consumes the same typed record with a vector of strings. Input ownership, UTF-8 validation, runtime string ownership, vector growth, and result construction are common to all three.

| Variant | Per-array and per-element work |
|---|---|
| Baseline | Generated record code begins the array and calls the decoder to advance and read each string. |
| Grouped | Generated record code makes one call for the vector. The JSON provider loops over the existing cursor advance and typed read operations, including their per-element checks and diagnostic path construction. |
| Fused | The provider also consumes delimiters and quoted elements in one loop. It uses the baseline plain-prefix scanner, escape parser, string construction and append. It counts elements on success and constructs the element path on a lexical failure. Unexpected delimiters or types resume scalar traversal from the delimiter checkpoint. |

The variants are experimental patches. They apply to temporary checkouts, and the builder checks that each generated consumer calls the intended decoder operation. There is no presizing, preliminary element count or change to string escape handling.

## Method

PHP 8.5.12 generated native consumers; Clang 18.1.3 compiled Release C++23 with libc++ on dev002 (Ryzen 9 5950X). Timing processes were pinned to CPU 7. For each shape, eleven rounds randomized the order of the three binaries. Each process warmed up, then measured complete record decode from an existing string, observation of the vector count and destruction. Counts of scalar operator-new calls and requested bytes came from separate binaries. All candidates were compared with a byte-level value oracle before timing. Nano- and microsecond values are elapsed time per document. Positive time reductions are faster.

The plain and escaped fixtures have identical decoded bytes. Escaped input spells one character at the midpoint of each value as a Unicode escape. Vector lengths are 0, 1, 3, 16 and 128. Decoded strings are 8, 129 or 4,096 bytes. The study covers these combinations and escape density.

## Correctness

The prior typed-string corpus compared 28 valid and 28 invalid documents for each of the scalar and vector shapes. An additional vector corpus compared 10 valid and 134 invalid documents, including empty arrays, whitespace, mixed Unicode content, and failures after 0, 1, 9, 10, 99, 100 and 128 valid elements. Negative cases cover malformed delimiters, trailing commas, wrong element types, bad escapes, literal control bytes, UTF-8 errors, truncated strings, duplicate fields and trailing data. Both candidates matched all baseline diagnostics byte for byte. All 424 tests in the JSON-enabled default suite passed on dev002 with the fused prototype. The oracle checks each element in order after the source runtime string and reader have been destroyed.

## Reproduction

Start three isolated copies of baseline commit f66b7b943. Apply grouped.patch to the grouped and fused copies, then apply fused.patch to the fused copy. Use the study's version of the shared typed_json_strings/build.py driver, which accepts the --batched check. Configure and build each publication:

```sh
cmake --preset default -DSCPP_ENABLE_JSON=ON
cmake --build --preset default --target scpp_json scpp_compiler_publication
```

Build and run the consumers from the directory containing this README:

```sh
python3 ../typed_json_strings/build.py "$baseline_checkout" "$bin/baseline"
python3 ../typed_json_strings/build.py "$grouped_checkout" "$bin/grouped" --batched
python3 ../typed_json_strings/build.py "$fused_checkout" "$bin/fused" --batched
python3 ../typed_json_strings/run.py "$bin/baseline" "$bin/fused" --transfer "$bin/grouped" --check
python3 run.py "$bin" --check
taskset -c 7 python3 run.py "$bin" > "$bin/dev002.json"
```

[Raw measurements](dev002.json) · [Grouped-loop patch](grouped.patch) · [Fused-loop patch](fused.patch)

## Production verification

The production string-vector binding uses a shared array traversal with the existing integer reader. On a failed string scan the traversal restores the delimiter checkpoint and lets scalar traversal report the error. The native Decoder default implementation also uses scalar traversal. The four-flavor integration fixture verifies the generated batch call and the returned strings for each frontend.

On dev002 CPU 7, the preserved scalar driver and the production driver decoded the same documents in eleven shuffled paired rounds. Each number below is median microseconds per complete document. The empty array took approximately four additional nanoseconds. All fourteen nonempty fixtures had lower production medians. Allocation counts were identical for every measured pair. The implementation uses the ordinary string scanner and existing output allocation policy.

| Vector shape | Plain, scalar → batch | Escaped, scalar → batch |
|---|---:|---:|
| Empty | 0.153 → 0.157 | — |
| 1 × 8 bytes | 0.250 → 0.211 | 0.296 → 0.248 |
| 3 × 8 bytes | 0.392 → 0.268 | 0.487 → 0.357 |
| 16 × 8 bytes | 1.310 → 0.636 | 1.861 → 1.145 |
| 16 × 129 bytes | 4.496 → 3.380 | 8.147 → 7.002 |
| 16 × 4,096 bytes | 77.061 → 64.246 | 174.319 → 159.237 |
| 128 × 8 bytes | 9.001 → 3.434 | 12.519 → 7.053 |
| 128 × 129 bytes | 34.666 → 25.477 | 63.729 → 54.382 |

The ten valid differential cases and all timed fixtures matched the byte oracle. All 134 invalid documents produced the same full diagnostic as the scalar driver. Reproduce with the original scalar driver in bin/baseline, the production driver built with --batched in bin/production, and `taskset -c 7 python3 run.py bin --production`.

[Raw production measurements](production.dev002.json)
