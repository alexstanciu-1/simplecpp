# Field recognition and duplicate checks in typed arrays of records

Doc Status: planning

## Results

The schema bitmask removed three allocations per four-field row. At 1,024 shuffled rows it reduced allocations from 3,086 to 14, requested bytes from 201,488 to 103,184, and median decode time from 381.31 to 308.43 µs. Returning a field ID while keeping the generic duplicate tracker measured 372.86 µs. The two-field control already fit inline and showed no allocation change.

Median µs per complete document:

| Rows and key order | Baseline | Field ID | Mask | Whole row | Ordered prefix | Allocations, baseline → mask |
|---|---:|---:|---:|---:|---:|---:|
| 0, ordered | 0.14 | 0.14 | 0.14 | 0.14 | 0.14 | 2 → 2 |
| 1, ordered | 0.51 | 0.52 | 0.45 | 0.43 | 0.40 | 9 → 6 |
| 64, two | 11.54 | 11.03 | 10.90 | 10.70 | 9.21 | 10 → 10 |
| 64, ordered | 21.55 | 21.44 | 17.16 | 16.93 | 13.88 | 202 → 10 |
| 64, shuffled | 23.14 | 22.68 | 18.94 | 18.22 | 17.94 | 202 → 10 |
| 64, escaped | 25.95 | 26.23 | 21.46 | 21.32 | 22.14 | 202 → 10 |
| 1024, shuffled | 381.31 | 372.86 | 308.43 | 301.97 | 305.74 | 3,086 → 14 |

Batching the entire row after introducing the mask reduced median time a further 2.1% on the largest shuffled case. Matching an ordered prefix helped most when the producer consistently emitted declaration order (16.93 to 13.88 µs for 64 rows). On the escaped, shuffled fixture it went from 21.32 to 22.14 µs. Prioritize the schema mask for small typed records across field orders. The ordered path is a separate optimization with a workload-dependent payoff.

[Raw timings and allocations](dev002.json)

## Experiment

The compiler generated a Packet containing a vector of Row records. Each row has a required integer id and three defaulted fields (integer x, boolean ok, integer tag). Each timing starts with an existing runtime string containing a complete object of the form `{"rows":[...]}`. It includes text ownership, UTF-8 validation, record and vector construction, checked result publication, observation of the result and destruction. The outer Packet, vector append, scalar readers, input ownership, field order semantics and diagnostic path formatting are common to all variants.

| Variant | Work performed |
|---|---|
| Baseline | The current generated decoder advances the cursor, asks for the decoded key, compares field names, and reads each scalar. The JSON reader keeps the first two distinct names inline and inserts additional names into a hash set. |
| Field ID | A single decoder call advances to the next field and recognizes its name, returning a small integer. It keeps the baseline duplicate tracker and generated presence flags. |
| Mask | The provider reuses the recognized field ID to check and set one bit per known field. An unknown field still reaches the ordinary schema error after delimiter validation. The generated presence flags remain in this control. |
| Whole row | A schema-specific decoder operation fills all four scalar slots. The provider loops on field IDs and uses the same mask for duplicates, required fields and defaults. Generated code constructs the record. |
| Ordered prefix | The whole-row operation first attempts each distinct key in declaration order. It matches the quoted spelling and colon directly, reads the value with the usual scalar reader, and sets the corresponding bit. At the first miss, it continues the ordinary masked loop at the same input byte. Escaped keys, reordered fields and duplicates after a recognized prefix take that fallback. |

The private variants model potential generated code. The provider in the last two variants knows this concrete schema. Code size across many schemas, instruction cache pressure, other Decoder providers and the design of a general interface were outside this experiment.

## Measurement

Clang 18.1.3 with libc++, C++23 and O3, PHP 8.5.12, AMD Ryzen 9 5950X, CPU 7 on dev002. Nine shuffled timing rounds per shape, with warmups. Each shuffled or escaped document uses independently shuffled field order per row, fixed across candidates and timing rounds. The escaped shape spells the first letter of id as a Unicode escape. The two-field shape supplies id and x so the baseline duplicate tracker fits inline. Allocation counts and cumulative requested bytes come from separate scalar operator-new instrumented executables.

56 valid documents matched an independent value oracle and 76 invalid documents matched the baseline's complete diagnostic text, including byte offset and path. The tests exercise required fields, defaults, permutations, integer limits, duplicate escaped names, malformed punctuation, and errors after 0, 1 or 64 completed elements. Every timed fixture also passed a full comparison of decoded rows before timing.

## Reproduction

Use a JSON-enabled publication built from b71a83afd or its study-only successor 0d74ec4f5. In an isolated checkout:

```sh
cmake --preset default -DSCPP_ENABLE_JSON=ON -DBUILD_TESTING=OFF
cmake --build --preset default --target scpp_json scpp_compiler_publication
study=specs/planning/benchmarks/json_record_arrays
work=$(mktemp -d)
XDEBUG_MODE=off python3 "$study/build.py" "$PWD" "$work"
python3 "$study/run.py" "$work" --check
# Sustained timings on dev002
taskset -c 7 python3 "$study/run.py" "$work" > "$work/results.json"
```
