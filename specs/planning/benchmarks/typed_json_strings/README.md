# Typed JSON string decoding

Doc Status: planning

The baseline is the generated record decoder at a24f3dd24. It parses a string into a native string, then constructs an owned runtime string from its bytes. The production candidate constructs a plain runtime string directly from a synchronous view of the input. For escaped values it uses the existing escape parser. Vectors keep their normal generated element traversal. The builder checks the emitted vector loop. All builds retain the same whole-input copy and UTF-8 validation.

## Results

Median microseconds per complete record. Positive percentages mean less elapsed time. The allocation column counts the whole document.

| Shape | Spelling | Decoded bytes per string | Before µs | After µs | Time reduction | Allocations before → after |
|---|---|---:|---:|---:|---:|---:|
| scalar | plain | 8 | 0.297 | 0.311 | -4.6% | 1 → 1 |
| scalar | plain | 129 | 0.679 | 0.654 | +3.7% | 5 → 4 |
| scalar | plain | 4,096 | 13.762 | 11.653 | +15.3% | 5 → 4 |
| scalar | escaped | 8 | 0.387 | 0.389 | -0.7% | 2 → 2 |
| scalar | escaped | 129 | 1.148 | 1.149 | -0.1% | 5 → 4 |
| scalar | escaped | 4,096 | 22.115 | 21.699 | +1.9% | 5 → 4 |
| vector | plain | 8 | 2.761 | 2.724 | +1.3% | 7 → 7 |
| vector | plain | 129 | 9.626 | 9.046 | +6.0% | 55 → 39 |
| vector | plain | 4,096 | 162.299 | 160.096 | +1.4% | 55 → 39 |
| vector | escaped | 8 | 3.633 | 3.718 | -2.3% | 7 → 7 |
| vector | escaped | 129 | 16.569 | 16.071 | +3.0% | 55 → 39 |
| vector | escaped | 4,096 | 354.463 | 347.686 | +1.9% | 55 → 39 |

At 129 and 4,096 bytes the new path saved one allocation per decoded string. For 16 values of 4,096 bytes each, the requested allocation traffic fell by 65,664 bytes for both spellings. Eight-byte strings used the same number of allocations throughout. The plain 4,096-byte scalar and plain 129-byte vector were faster in all nine paired rounds. The escaped 129-byte scalar was effectively tied. The eight-byte escaped vector was slower in seven of nine pairs.

The initial two-way run transferred all completed escaped strings via own_string. Its eight-byte escaped vector took 4.5% longer than baseline. In the final three-way run, the unconditional transfer candidate took 3.765 µs for that case, explicit inline construction took 3.718 µs, and baseline took 3.633 µs. The inline branch is retained for short escaped values. Timing for long escaped vectors improved modestly despite the large allocation reduction. This experiment does not isolate escape scanning or measure the benefit of batching.

The JSON in dev002.json labels the original decoder as before, the implementation with inline construction as after, and the unconditional transfer candidate as transfer. Recreate the latter by making take_string always return detail::own_string(std::move(bytes)), then pass its driver directory with --transfer. [Initial two-way measurements](transfer.dev002.json)

## Method

On dev002 (Ryzen 9 5950X, CPU 7), PHP 8.5 generated two one-field record types. One field is a string and the other is a vector of strings. Clang 18.1.3 compiled the production libraries and consumers with libc++, Release optimization, and separate timing and allocation binaries. Each operation decodes a complete record from an existing runtime string, checks the result, observes the scalar length or vector count, and destroys the result. Setup, process startup and correctness output are outside the timer.

Each vector contains 16 values. The scalar and vector tests use decoded lengths of 8, 129 and 4,096 ASCII bytes per value. The plain and escaped cases produce the same bytes. The escaped input spells one character in the middle as a six-byte Unicode escape. Escape density and placement can change throughput. Each binary warms up before measurement; the nine rounds shuffle candidate order. Allocation counts and requested bytes cover a whole decode and come from the separate scalar operator-new instrumentation. The measurements do not count all internal copies or peak live memory.

There are 28 valid and 28 invalid documents per record shape. Valid cases compare all decoded bytes with an independent oracle after both the input string and the reader have gone out of scope. They cover empty strings, inline-capacity boundaries, raw UTF-8, control escapes, embedded NUL and surrogate pairs. Invalid cases compare entire diagnostics byte for byte with the baseline, including byte offset, field or element path, malformed escapes, invalid UTF-8, truncation and trailing input.

## Reproduction

Build the baseline and candidate in separate JSON-enabled checkouts:

```sh
cmake --preset default -DSCPP_ENABLE_JSON=ON
cmake --build --preset default --target scpp_json scpp_compiler_publication
```

Use the scripts from this study for both builds. Each work directory receives a fresh compiler-generated consumer linked against its corresponding production runtime.

```sh
python3 build.py "$baseline_checkout" "$work/before"
python3 build.py "$candidate_checkout" "$work/after"
python3 run.py "$work/before" "$work/after" --check
taskset -c 7 python3 run.py "$work/before" "$work/after" > "$work/results.json"
```

[Raw timing samples and allocation counts](dev002.json)
