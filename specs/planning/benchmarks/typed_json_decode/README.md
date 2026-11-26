# Batching typed JSON decode

Doc Status: planning

On dev002, a single-pass integer-array reader cut median decode time for two 2,048-element fields from 228.56 µs to 49.12 µs. Moving the ordinary element loop into the JSON provider measured 231.03 µs. The gain came when the provider could consume a run of known-type elements directly. The experiment bundles integer conversion, delimiter handling and deferred diagnostic path construction. It does not isolate how much time each of those three saves.

## Inputs and results

Each operation calls the real compiler-generated Packet result's from_text entry point, observes the decoded record and destroys the result. Packet has two integer-vector fields, one string-vector field and a defaulted integer. The full input already exists as a runtime string when timing starts. Integer arrays include whitespace and signed values. Each string case contains 16 escaped strings, with decoded lengths of 5 or 129 bytes.

Median microseconds per complete document from nine randomized rounds. The columns are independent binaries built from the same generated record and runtime publication.

| Shape | Current | Borrow | Group | Fused | Stage 16 | Exact reserve | Transfer |
|---|---:|---:|---:|---:|---:|---:|---:|
| Scalar field only | 0.157 | 0.156 | 0.155 | 0.151 | 0.165 | 0.155 | 0.158 |
| Two empty arrays | 0.349 | 0.337 | 0.348 | 0.331 | 0.354 | 0.382 | 0.333 |
| 3 + 0 integers | 0.808 | 1.146 | 1.197 | 0.695 | 0.880 | 0.999 | 0.780 |
| 16 + 12 integers | 2.252 | 2.246 | 2.176 | 0.835 | 0.787 | 1.129 | 0.833 |
| 256 + 0 integers | 14.813 | 15.892 | 14.578 | 3.499 | 3.533 | 5.602 | 3.557 |
| 2,048 + 2,048 integers | 228.560 | 246.013 | 231.034 | 49.121 | 49.997 | 82.859 | 50.159 |
| 16 short strings | 1.508 | 1.530 | 1.563 | 1.526 | 1.562 | 1.529 | 1.534 |
| 16 long strings | 4.515 | 4.771 | 4.793 | 4.742 | 4.819 | 4.798 | 4.426 |

- **Borrow** gives the synchronous cursor a view of the caller's text for the duration of from_text. The persistent pull reader retains ownership. This eliminates one input-sized copy and, above inline string capacity, one allocation. Throughput alone gave mixed results.
- **Group** adds a typed decoder operation per vector field and moves the existing advance, read and append loop into the provider. It includes borrowing.
- **Fused** additionally consumes an integer run in the provider, converts each accepted token once, and advances the frame index without formatting a path for each success. A malformed token re-enters the existing reader at that element's checkpoint. UTF-8 validation, schema dispatch, depth checks and root completion still run.
- **Stage 16** adds a bounded stack buffer to fused. The first 16 values, or the complete shorter array, populate the vector using its existing from_list operation. Later values use ordinary append. This spends an extra copy on the staged integers to reduce growth allocations.
- **Exact reserve** validates and counts the complete integer array with the fused reader, rewinds to its start, reserves once and decodes. This reduces vector growth at the cost of a second parse.
- **Transfer** adds a string change to fused. The string-vector loop moves each completed decoded native buffer through the runtime's existing own_string operation. Both this variant and fused use grouped string reads. Short strings fit inline.

## Allocation and copy evidence

Counts per document from a separate link-instrumented executable. Bytes are total requested through scalar operator new during the operation, excluding allocator overhead. They measure allocation traffic, not peak resident memory. The probes also wrapped the runtime integer-vector copy constructor and copy assignment. Both counters stayed zero for every shape and variant. Vector growth and string payload copying are distinct from those two operations.

| Shape and variant | Allocations | Allocated bytes |
|---|---:|---:|
| 16 + 12 integers, current | 13 | 1,112 |
| 16 + 12 integers, fused | 12 | 936 |
| 16 + 12 integers, stage 16 | 8 | 712 |
| 2,048 + 2,048 integers, current | 27 | 83,816 |
| 2,048 + 2,048 integers, fused | 26 | 65,960 |
| 2,048 + 2,048 integers, stage 16 | 22 | 65,768 |
| 2,048 + 2,048 integers, exact reserve | 8 | 33,256 |
| 16 long strings, current | 55 | 8,024 |
| 16 long strings, grouped and borrowed (fused) | 54 | 5,816 |
| 16 long strings, transfer | 38 | 3,640 |

On the long-string fixture, input borrowing removes the 2,203-byte input copy. Buffer transfer also removes the second construction from character data for each 129-byte decoded string. These payload sizes come from the fixture and the actual constructor paths. The allocation differences are measured. The probe does not count all bytes moved internally by the parser, vector allocator or C++ library.

## Verification and scope

74 valid documents were compared against an independent value oracle, including field reordering, defaults, signed integer limits, Unicode escapes and embedded NUL. 58 invalid documents were compared against the current generated binary's entire checked error message, including byte offset and path. Cases include a duplicate escaped key, unknown field, wrong types, trailing data, overflow and malformed integers after different numbers of valid elements. The same checks ran on dev002 before timing.

The scalar, empty and three-element timings varied widely across rounds. For 256 and 4,096 integers, every fused sample was faster than every baseline sample. This study covers warm, single-thread typed record decode. It leaves boxed decode, nested record arrays, persistent cursor throughput, and application-level latency for separate experiments.

## Reproduction

Base revision b71a83afd. Build in an isolated checkout with PHP 8.5, Clang and libc++. The dev002 run used PHP 8.5.12, Clang 18.1.3, C++23, O3, an AMD Ryzen 9 5950X and CPU 7. The runner uses separate timing and allocation binaries, warms each one used for timing and retains all nine samples. It checks result checksums across candidates. CPU affinity does not provide exclusive core reservation.

```sh
cmake --preset default -DSCPP_ENABLE_JSON=ON -DBUILD_TESTING=OFF
cmake --build --preset default --target scpp_json scpp_compiler_publication
study=specs/planning/benchmarks/typed_json_decode
work=$(mktemp -d)
XDEBUG_MODE=off python3 "$study/build.py" "$PWD" "$work"
python3 "$study/run.py" "$work" --check
# Full run on dev002
taskset -c 7 python3 "$study/run.py" "$work" > "$work/results.json"
```

[Raw samples, checksum-validated runs and allocation counts](dev002.json) · [Variant builder](build.py) · [Fused reader experiment](batch.inc) · [Measurement driver](run.py)

## Production integer-vector decoder on dev002

The generated typed record now calls one integer-array operation for a published int64 vector. The JSON reader consumes valid integer tokens in a single pass, appends into the existing vector, and replays the scalar path from the current delimiter if the fast path cannot accept the input. The production change retains input ownership and ordinary vector growth. Alternate decoder providers inherit scalar traversal.

The following rates are decimal MB of JSON input per second. The preserved binary with the typed field mask and the new production binary participated in the same seven shuffled rounds on dev002 CPU 7. The wire format is a compact object containing data (consecutive integers starting at zero) and version (7). The 128- and 4,096-element production samples all exceeded every baseline sample. Empty and three-element results had substantial timing variation.

| Decoder | Empty | 3 integers | 128 integers | 4,096 integers |
|---|---:|---:|---:|---:|
| Previous Simple C++ | 53.1 | 67.1 | 57.6 | 79.8 |
| Current Simple C++ | 72.9 | 97.6 | 259.9 | 389.4 |
| PHP 8.5.12 | 76.4 | 74.3 | 87.0 | 129.9 |
| Node.js 22.23.4 | 106.4 | 95.9 | 283.5 | 473.7 |
| nlohmann/json 3.11.3 | 55.6 | 50.5 | 66.3 | 96.4 |
| Python 3.12.3 | 21.1 | 21.7 | 74.9 | 132.6 |

[Raw measurements, controls and methodology](../json_record_arrays/integer-vector-languages.dev002.json)
