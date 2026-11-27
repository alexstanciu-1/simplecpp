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

## Dense Unicode follow-up

The reference remains the committed decoder at `56800977e`. Both phases use the same measurement and timing drivers as the original ASCII study. The new cases alternate one ASCII byte with a three-byte character, mix two-, three- and four-byte characters in one dense string, or put ASCII and Unicode in separate runs. An ASCII run following one non-ASCII character distinguishes the cost of delaying the next probe from the cost of abandoning ASCII batching.

Two experiments retain the baseline scalar decoder. After the first non-ASCII block, [retry64](retry64.patch) validates at least 64 bytes scalarly, ending on a code-point boundary or at end of input. The [remainder fallback](remainder.patch) validates the rest of the document scalarly. The ASCII control retries its word check after every scalar. On dev002, backing off did not eliminate the dense regression: full typed decode for the 2-, 3- and 4-byte fixtures was 7.3–21.3% slower than committed for retry64 and 4.1–15.1% slower for the remainder policy. Both still gained about 2.3× on an all-ASCII 4,096-byte string. Both lost about 46% on sparse Unicode (one two-byte character every 64 bytes). After one non-ASCII character followed by ASCII, retry64 regained fast scanning (+119% over committed) while the remainder fallback lost 42%.

The committed validation loop calls the shared code-point decoder and uses its width. The isolated baseline binary contained the scalar-assembly shift/OR loop and range checks. A third experiment retains the original eight-byte ASCII check and changes only its scalar fallback. The [width-only candidate](width.patch) checks the lead byte, expected continuation bytes, and the restricted second-byte ranges for E0, ED, F0 and F4. It returns the width without assembling a scalar. Only the JSON prevalidator calls it. All tests compare error offsets at the original lead byte.

The [local differential check](width_check.cpp) compares the helper against the existing decoder for every lead/second-byte pair, six boundary values for each subsequent byte and input lengths 0–4 (11,796,480 comparisons). Guarded-page tests for all three candidates put inputs of lengths 0–96 immediately before an inaccessible page and verified every possible invalid-byte position. The dev002 corpus adds 72 placements around fallback transitions, checking valid multibyte sequences, malformed continuations, truncation and JSON syntax errors preceding invalid UTF-8.

Width-only fallback with the eight-byte ASCII check, throughput change against committed (run 1 / independent recheck). Each phase used eleven shuffled paired-comparison rounds per run on dev002 CPU 7, with the original eight-byte binary as a third control.

| Fixture | Validation alone | Full typed decode |
|---|---:|---:|
| empty | -0.3% / +16.4% | +7.4% / +6.7% |
| short-ascii | +64.0% / +71.7% | +12.3% / +9.3% |
| short-unicode | +11.3% / +9.2% | +11.2% / +7.4% |
| ascii-4096 | +292.4% / +293.8% | +90.7% / +92.9% |
| sparse-4096 | +144.5% / +146.8% | +50.3% / +50.4% |
| dense-2byte | +139.8% / +138.7% | +84.4% / +84.5% |
| dense-3byte | +42.9% / +42.9% | +44.3% / +44.4% |
| dense-4byte | +50.1% / +50.3% | +42.4% / +43.5% |
| mixed-width-dense | +57.3% / +58.0% | +57.6% / +57.6% |
| alternating | +34.2% / +34.5% | +18.2% / +18.0% |
| unicode-then-ascii | +292.1% / +291.3% | +90.0% / +91.4% |
| ascii-then-unicode | +75.5% / +76.4% | +53.9% / +54.8% |
| escaped-unicode | +298.5% / +298.3% | +10.2% / +9.8% |
| words-short | +237.9% / +236.7% | +9.2% / +10.3% |
| words-long | +300.4% / +299.8% | +98.0% / +98.7% |
| numbers-3 | +106.3% / +105.6% | +0.9% / +5.8% |
| numbers-4096 | +300.2% / +298.5% | +4.1% / +3.7% |

The inline width candidate consistently sped up dense literal text, but full decode on the 4,096-byte ASCII scalar gained about 91–93% versus roughly 135% for the original eight-byte candidate. The [separate-loop follow-up](separate.patch) applies on top of width.patch. It moves the same byte loop into a non-inlined function, leaving depth-limit checks and exception conversion in the caller. All four binaries (committed, original ASCII, inline width and separate width) ran in two new shuffled comparisons. Validation alone still compiles the loop separately for every variant. The end-to-end comparison tests placement of that loop in the reader binary.

| Separate width validator | Validation vs. committed | Full decode vs. committed | Full decode vs. original eight-byte candidate |
|---|---:|---:|---:|
| numbers-3 | +107.9% / +106.4% | +1.0% / -2.9% | +4.9% / -4.4% |
| mixed-width-dense | +57.0% / +57.9% | +49.4% / +37.7% | +52.8% / +40.6% |
| ascii-4096 | +292.5% / +292.4% | +92.7% / +92.0% | -17.2% / -17.0% |
| sparse-4096 | +145.3% / +146.6% | +64.6% / +65.9% | -2.3% / -2.3% |
| short-ascii | +71.8% / +71.6% | +4.0% / +16.1% | +0.8% / +11.9% |
| alternating | +34.1% / +34.2% | +29.5% / +29.2% | +39.8% / +39.4% |
| short-unicode | +10.1% / +9.5% | -5.4% / +3.0% | -3.8% / +3.1% |
| words-long | +301.2% / +300.7% | +103.4% / +100.0% | -19.7% / -19.7% |
| ascii-then-unicode | +75.4% / +76.1% | +45.9% / +42.0% | +27.6% / +27.1% |
| unicode-then-ascii | +295.3% / +293.3% | +92.6% / +92.1% | -17.0% / -16.6% |
| empty | +11.0% / +17.0% | +8.7% / +4.4% | +1.4% / +2.0% |
| dense-4byte | +49.9% / +49.0% | +30.8% / +29.9% | +39.1% / +39.5% |
| words-short | +246.6% / +238.8% | +6.4% / +4.8% | -0.6% / -2.0% |
| escaped-unicode | +297.5% / +297.8% | +5.6% / +5.4% | -0.4% / -1.1% |
| numbers-4096 | +298.6% / +299.9% | +5.8% / +5.6% | -1.0% / -1.8% |
| dense-2byte | +139.1% / +138.9% | +84.7% / +84.4% | +89.3% / +88.7% |
| dense-3byte | +42.7% / +42.7% | +45.5% / +44.1% | +51.3% / +50.0% |

Separating the loop did not recover full long-ASCII throughput. The separate-loop build stayed 17% behind the original ASCII candidate for a 4,096-byte ASCII scalar in both paired runs, despite being about 92% faster than the committed decoder. On the same host, the new width checks consistently improved both dense-Unicode validation and complete dense-Unicode decode. This supports testing the width-only fallback further; the measurements do not identify the exact cause of the remaining end-to-end ASCII difference.

All four new candidates matched the committed decoder's complete diagnostic on each of 1,952 malformed documents and the value oracle on 1,008 valid documents. The inline width build passed all 425 default tests on dev002. On the separate-loop build, 424 passed in the parallel suite and the compiler frontend service idle test passed when rerun alone after its running-status assertion failed in parallel. The helper has a second expression of UTF-8 validity rules, so retaining the differential width check matters if those rules change.

To reproduce, build the drivers as in the original study and use subdirectories named base, ascii8, retry64, remainder, width and separate. Apply retry64.patch, remainder.patch or width.patch separately to the committed reader. For separate, apply width.patch and then separate.patch. Pin run.py to CPU 7 and use these arguments:

```sh
taskset -c 7 python3 run.py bin --dense retry64 remainder --seed 82031
taskset -c 7 python3 run.py bin --dense width --seed 29083
taskset -c 7 python3 run.py bin --dense width --seed 64591
taskset -c 7 python3 run.py bin --dense width separate --seed 37391
taskset -c 7 python3 run.py bin --dense width separate --seed 57239
```

[Retry policies](dense.dev002.json) · [Width-only run](width-29083.dev002.json) · [Width-only recheck](width-64591.dev002.json) · [Separate-loop run](separate-37391.dev002.json) · [Separate-loop recheck](separate-57239.dev002.json)
