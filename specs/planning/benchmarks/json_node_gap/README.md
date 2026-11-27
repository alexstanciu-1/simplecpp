# Integer and literal UTF-8 decode against Node

Doc Status: planning

The baseline is Simple C++ production revision `b3103543a`. The driver uses generated typed records. Numbers has a vector<int> field with signed 64-bit elements, and Words has a vector<string> field with owned UTF-8 strings. Neither timing constructs an intermediate dynamic or mixed tree. Node.js 22.23.4 produces a JavaScript object containing an Array of Numbers or strings.

The Node text measurement starts with a native string as in the earlier standings. The Node bytes measurement starts with the same UTF-8 bytes as the typed reader, calls a reused strict TextDecoder on every iteration, then calls JSON.parse. The timed operation in each case observes the array length. Each binary/process warms up and checks the complete field against an independent value oracle before timing. The input already exists in memory. These are warm decode measurements, with each runtime's normal object lifetime behavior. The numeric fixtures fit JavaScript's exact integer range. Simple C++ retains signed-64-bit parsing and schema checks. Varying-width and homogeneous strings all have 16 elements, with 4,096 payload bytes per element (4,095 for the three-byte and mixed fixtures).

Two experimental patches apply independently to the baseline:

- [Short decimals and same-width runs](candidate.patch) checks up to four positive decimal digits inline and requires a legal compact delimiter. Other encodings use the committed integer path. On x86-64 the prevalidator uses SSE2 to validate complete 16-byte runs of two-byte or four-byte sequences after a valid scalar, including the restricted second byte for F0 and F4. A miss or tail goes through the committed validator.
- [Four-digit decimals and general UTF-8 blocks](general.patch) tests the delimiter at byte four before converting a positive four-digit token. The SSE2 path checks expected continuation positions across a block containing multiple widths, excludes invalid leads and checks the restricted second bytes for E0, ED, F0 and F4. It resumes at a code-point boundary. Inputs shorter than a full block fall back to the committed width check.

The integer and Unicode comparisons in this study include controls beyond consecutive integers and repeated characters: alternating signs, eleven-digit integers, three-byte text, mixed widths and varying character values within the same byte width. See run.py for exact fixtures. The UTF-8 block checks run on x86-64 when the portable-only build switch is disabled. All quoted rates are complete wire MB/s on dev002 CPU 7. Each run takes the median of seven shuffled samples.

## Results on dev002

Median wire MB/s, rounded. The baseline and Node columns come from the first general-block run. Each prototype column comes from its own first run. Both independent repeats reproduced the tradeoffs.

| Input | Production | Short decimals / same-width | Four digits / general blocks | Node text | Node bytes |
| --- | ---: | ---: | ---: | ---: | ---: |
| 128 consecutive integers | 270 | 376 | 236 | 295 | 266 |
| 4,096 consecutive integers | 411 | 703 | 606 | 524 | 473 |
| 4,096 alternating signs | 452 | 541 | 501 | 548 | 513 |
| 4,096 eleven-digit integers | 617 | 562 | 583 | 323 | 312 |
| Repeated two-byte character | 1,594 | 11,166 | 2,423 | 5,435 | 274 |
| Repeated three-byte character | 1,713 | 1,244 | 2,313 | 6,076 | 287 |
| Repeated four-byte character | 1,958 | 8,595 | 2,453 | 4,846 | 270 |
| Mixed two-, three-, four-byte characters | 1,787 | 1,255 | 2,332 | 6,267 | 304 |

Keep both patches experimental. The short-decimal path has the higher integer upside but loses about 9% on eleven-digit values. The general UTF-8 validator improves every dense Unicode shape in the study. The homogeneous path provides greater upside only for supported widths and slows down mixed text. The committed decoder already leads Node on these Unicode fixtures when both receive UTF-8 bytes.

Raw measurements and checks are in [short-decimal run 1](gap-39157.dev002.json), [run 2](gap-82361.dev002.json), [general-block run 1](general-33041.dev002.json) and [run 2](general-78919.dev002.json). To repeat the timings, build the typed driver with the adjacent json_utf8_validation/build.py at baseline and at each independently patched revision. From a scratch directory containing bench.js, run.py and the drivers at bin/{base,candidate,general}/driver, run `taskset -c 7 python3 run.py 39157` and `taskset -c 7 python3 run.py 33041 general`.

## Matched UTF-8 byte input

All six implementations start with the same in-memory bytes and finish with a native record or object containing the decoded vector or array. The two strict Node paths use a reused fatal TextDecoder, or buffer.isUtf8 followed by Buffer.toString and JSON.parse. A third Node control uses Buffer.toString without validation. Each fixture is checked in full before timing, and each timed iteration consumes the length. Disk I/O, fixture construction, process startup, warm-up and the oracle are outside timing. Each of seven shuffled rounds processes about 24 MB per implementation per fixture on dev002 core 7. The integers are exactly representable by both runtimes. Node returns native JavaScript numbers and strings and follows its ordinary GC behavior. The C++ reader also enforces the record schema, owns its output and destroys each result within the loop.

Run `taskset -c 7 python3 strict_bytes.py` with strict_bytes.js and the three built drivers in the scratch directory. [Raw samples and throughput](strict-bytes.dev002.json). On the two previously disputed shapes the committed decoder measured 1,767 MB/s for three-byte text and 1,805 MB/s for mixed-width text. Both strict Node paths were within 304–306 MB/s. Ordinary Buffer decoding on valid input reached 312 and 308 MB/s respectively. For consecutive and alternating-sign integers, the committed decoder trailed the faster strict Node path by 16% and 12% respectively. It led on eleven-digit integers by 94%.
