# Borrowing the input of a synchronous typed JSON decode

Doc Status: planning

The baseline is the eight-byte scanner and bulk decoder at `29a000dcd`. Both drivers receive the same existing Simple C++ string. The candidate borrows its bytes until the synchronous reader finishes. Whole-input UTF-8 validation, escape scanning, schema checks and construction of owning result fields use the baseline code.

## Results on dev002

Median microseconds per complete record on CPU 7, nine paired rounds in randomized binary order. The vector contains sixteen elements. An escaped fixture spells one ASCII character at the midpoint as a Unicode escape. Each timed decode observes the result length and destroys the result.

| Field | Bytes per string | Plain, baseline → borrowed µs | Escaped, baseline → borrowed µs |
|---|---:|---:|---:|
| Scalar | 8 | 0.146 → 0.135 | 0.195 → 0.170 |
| Scalar | 129 | 0.215 → 0.202 | 0.266 → 0.246 |
| Scalar | 4,096 | 2.827 → 1.701 | 1.813 → 1.779 |
| Vector | 8 | 0.542 → 0.511 | 1.157 → 1.077 |
| Vector | 129 | 1.982 → 1.720 | 2.709 → 2.249 |
| Vector | 4,096 | 25.470 → 24.337 | 27.040 → 26.084 |

One allocation per document disappeared in 11 of 12 cases. The 20-byte wire document containing a plain eight-byte scalar already fit inline. For the plain vector of 4,096-byte strings, borrowing removed 65,600 requested bytes for a 65,595-byte wire document. The 39.8% reduction for the plain 4,096-byte scalar is specific to this shape and build. The escaped scalar at that size improved by 1.9%, winning six of nine rounds. The timing measures complete decode and destruction; it does not isolate memcpy or allocator latency.

## Lifetime boundary

The experimental compiler selects the borrowed entry point for a generated root record containing supported scalar fields or vectors of scalars. The generated traversal reads all fields and finishes the root document before evaluating defaults and constructing the result. Plain string values copy directly from the source span into result storage; escaped values own their decoded buffer. A nested record can finish construction while the outer reader still has bytes to read, so nested schemas retain the owning entry point. An arbitrary native consumer can mutate the input through another reference during its callback. The existing callback entry point consequently retains its input snapshot. A persistent pull reader also retains its input. The experimental hook has a stable-input precondition throughout traversal and is intended for generated leaf consumers.

The typed-string harness compares values and complete diagnostic messages for invalid UTF-8, escapes, surrogates, truncation, wrong types, and trailing input. Its verification path destroys the input before inspecting a result or error. The additional ownership probe overwrites each input byte, destroys the input, then checks short and heap-backed scalars, escaped NUL, and mixed plain/escaped vector elements. It also clears the original input in an ordinary native callback before reading the first field and verifies that this reader still decodes its owned snapshot.

## Reproduction

Use isolated baseline and candidate copies of `29a000dcd`. Apply borrow.patch only to the candidate. Configure and build both JSON-enabled Release publications with the root CMake presets. Use the shared typed_json_strings/build.py driver with --batched for each checkout, producing bin/baseline and bin/borrow. Run the paired measurements and parity checks on dev002.

```sh
taskset -c 7 python3 ../typed_json_strings/run.py bin/baseline bin/borrow > dev002.json
python3 check_ownership.py "$candidate" bin/borrow
python3 ../json_string_suffix/run.py bin --check-pair baseline borrow
```

On dev002 both scalar and vector outputs matched the independent byte oracle on 390 valid suffix-corpus documents per shape. All 844 malformed scalar/vector diagnostics matched the baseline byte for byte, including paths and offsets. The explicit overwrite and callback-alias probe passed. All 425 JSON-enabled default tests passed in the experimental build. [Raw measurements and allocation counts](dev002.json)
