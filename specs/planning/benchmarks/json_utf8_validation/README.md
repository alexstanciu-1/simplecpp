# Portable ASCII blocks in UTF-8 prevalidation

Doc Status: planning

The baseline is the committed closing-quote decoder at `56800977e`. The [eight-byte candidate](ascii8.patch) copies a full word with memcpy and checks the high bit of every byte. A zero mask advances eight bytes. For a nonzero mask or a partial tail it decodes one code point with the existing validator. This works with unaligned input in either byte order. The [leading-byte candidate](ascii8_leading.patch) tests the first byte before loading the word, so a multibyte lead goes directly to the existing decoder. Apply either patch independently to the baseline.

The experiment changes only whole-document UTF-8 prevalidation. Both owning and borrowing readers still validate before parsing. The committed JSON string scanner, allocation policy and generated typed decoder operations are common to all builds.

## Method

Each fixture supplies the same complete JSON wire bytes to two measurements. Validation alone runs the inner loop extracted from the corresponding reader header in a separate translation unit, one call per document, with the returned size consumed. Full decode calls the generated scalar string, string-vector or integer-vector record's from_text operation on an existing runtime string, observes the field size and destroys the result. Full decode includes prevalidation. The difference between the timings is not an exact component breakdown because the compiler can optimize the standalone and integrated loops differently.

Every rate is measured on dev002 CPU 7 (Ryzen 9 5950X, Clang 18, libc++, C++23, -O3). The runner warms each binary before timing and shuffles binary order within eleven rounds for each measurement. The eight-byte candidate was measured twice with different seeds. The leading-byte follow-up compares all three binaries in a fresh run. Inputs and expected values are generated outside the timed loop. These are warm single-thread throughput measurements.

## Verification

In each build, 576 valid scalar and string-vector documents matched the independent JSON value oracle. On 1,088 malformed documents the complete diagnostic matched the committed decoder. The corpus varies the start offset across 32 consecutive byte positions and checks the reported offset for stray continuation bytes, overlong encodings, invalid continuations, surrogate values, values above U+10FFFF and truncated sequences. A leading JSON syntax error followed by an invalid UTF-8 byte still reports the later UTF-8 error. ASCII control characters and invalid escapes continue to reach the JSON parser. Every timed input, including integer vectors, also has its decoded contents checked outside timing.

The eight-byte build passed all 425 default tests on dev002. A local guarded-page check put inputs of lengths 0–96 immediately before an inaccessible page and tested an invalid byte at every offset, checking the error category and byte position.

## Results

Throughput change against the committed decoder in the three-way run, calculated as baseline median ns / candidate median ns − 1. Positive values are faster. Byte counts include JSON syntax. Sparse text repeats 62 ASCII bytes and one two-byte character. The dense strings repeat a literal two-, three- or four-byte character. The escaped-Unicode fixture uses ASCII surrogate-pair spellings on the wire. The long string vector contains sixteen 4,096-byte strings.

| Shape | Wire bytes | Eight-byte validation | Eight-byte full decode | Leading-byte validation | Leading-byte full decode |
|---|---:|---:|---:|---:|---:|
| empty | 12 | +5.1% | +14.2% | +10.7% | +18.0% |
| short-ascii | 19 | +48.1% | +7.9% | +64.5% | +9.7% |
| short-unicode | 21 | -2.2% | -2.4% | -11.1% | +0.8% |
| ascii-4096 | 4,108 | +169.1% | +133.2% | +301.9% | +135.1% |
| sparse-4096 | 4,108 | +78.5% | +68.7% | +47.2% | +56.3% |
| dense-2byte | 4,108 | -4.6% | -3.6% | -7.5% | -5.8% |
| dense-3byte | 4,107 | -16.1% | -4.1% | -16.3% | -11.0% |
| dense-4byte | 4,108 | -14.0% | -6.4% | -14.0% | -8.6% |
| escaped-unicode | 12,300 | +171.5% | +6.0% | +289.2% | +6.7% |
| words-short | 171 | +152.9% | +7.0% | +239.0% | +7.7% |
| words-long | 65,595 | +167.3% | +149.6% | +299.2% | +151.0% |
| numbers-3 | 17 | +73.8% | -0.0% | +96.6% | -1.1% |
| numbers-4096 | 19,381 | +166.7% | +7.4% | +291.2% | +6.5% |

The two initial runs gave a 2.32–2.35× end-to-end speedup for the 4,096-byte ASCII scalar, roughly 2.49× for the sixteen long ASCII strings and 7.0–7.4% higher throughput on the 4,096-integer vector. Short records varied more: full decode for the seven-byte ASCII value gained 2.8–3.1% in those runs, while the three-integer record ranged from −1.3% to +0.9%. Every run found lower full-decode throughput on dense literal Unicode for the unconditional check. Testing the leading byte first also lost throughput on all three dense fixtures. Neither candidate meets a requirement to preserve dense-Unicode performance on this host. The leading-byte build also passed all 425 default tests.

## Reproduction

Use three JSON-enabled Release builds from the same baseline with the same compiler and libc++ configuration. Leave the first build unchanged. Apply ascii8.patch to the second and ascii8_leading.patch to the third. Configure and build each checkout with `cmake --preset default -DSCPP_ENABLE_JSON=ON` and `cmake --build --preset default`. Build the measurement drivers with the same toolchain on PATH:

```sh
python3 build.py "$baseline_root" bin/base
python3 build.py "$ascii8_root" bin/ascii8
python3 build.py "$leading_root" bin/leading
taskset -c 7 python3 run.py bin > ascii8.dev002.json
taskset -c 7 python3 run.py bin --seed 17143 > ascii8-recheck.dev002.json
taskset -c 7 python3 run.py bin --leading --seed 49007 > leading.dev002.json
```

[Eight-byte run](ascii8.dev002.json) · [Independent recheck](ascii8-recheck.dev002.json) · [Leading-byte comparison](leading.dev002.json)
