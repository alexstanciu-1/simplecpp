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
clang++ -O3 -std=c++23 -I "$candidate/runtime/src/json" ../../../../tests/json/string_scan.cpp -o check_scan
./check_scan
```

[Raw measurements](dev002.json) · [Intel instruction reference for extracting per-byte masks](https://cdrdv2-public.intel.com/671110/325383-sdm-vol-2abcd.pdf)

## Production bulk suffix verification

The baseline is the committed batched string-vector decoder at `5eb9b4945`. The production candidate appends each nonempty ordinary run after an escape in one operation. A scanner-only executable applies word8.patch directly to the baseline. A fourth executable applies the scanner to the production candidate. All four use the same string storage policy, scalar escape grammar, UTF-8 validator and typed consumer.

Decimal MB of complete wire JSON per second on dev002, measured in eleven shuffled rounds on CPU 7. Each document holds 16 strings with the same decoded contents for all four escape placements.

| Decoded bytes/string | Escape | Batched baseline | Bulk append | 8-byte scanner only | Bulk + 8-byte scanner |
|---:|---|---:|---:|---:|---:|
| 8 | plain | 281.4 | 285.8 | 320.6 | 330.7 |
| 8 | first | 218.8 | 255.6 | 211.9 | 245.7 |
| 8 | middle | 237.6 | 246.1 | 231.6 | 236.6 |
| 8 | last | 270.6 | 269.9 | 260.9 | 269.7 |
| 129 | plain | 633.1 | 630.6 | 1,037.1 | 1,043.2 |
| 129 | first | 212.5 | 572.9 | 211.1 | 878.9 |
| 129 | middle | 307.7 | 533.9 | 335.0 | 804.2 |
| 129 | last | 577.0 | 590.2 | 902.6 | 935.0 |
| 4,096 | plain | 1,039.0 | 1,012.0 | 2,523.8 | 2,526.8 |
| 4,096 | first | 217.2 | 1,008.3 | 216.0 | 2,397.4 |
| 4,096 | middle | 410.6 | 1,020.4 | 457.2 | 2,390.9 |
| 4,096 | last | 590.2 | 582.6 | 883.2 | 895.2 |

Both typed scalar and vector drivers matched the independent value oracle on 390 valid documents per shape. All three candidates matched the batched baseline's full error text, byte offset and path on 422 malformed documents per shape. The guarded-page scanner probe passed. The JSON-enabled default suite passed all 424 tests on the production bulk build and again on the bulk-plus-scanner build.

Build each driver's time and counts executables using the typed_json_strings builder with --batched. Name the directories batched, bulk, scan_only and word8, then run `taskset -c 7 python3 run.py "$bin" --production`. Use the committed baseline for the batched and scanner-only builds, and the production bulk implementation for bulk and word8.

[Raw four-way measurements](production.dev002.json)

## Production eight-byte scanner

The reference is the bulk suffix decoder at `b080b9fe4`. The candidate changes only the ordinary-byte prefix scan shared by plain and escaped strings. Each complete word is loaded with memcpy. The mask identifies a word containing a quote, backslash or control byte. The scalar loop finds the exact stopping byte in input order and handles spans shorter than eight bytes, including the final partial word.

Throughput is decimal MB of complete wire JSON per second, shown as **bulk baseline → integrated scanner**. Eleven shuffled paired rounds ran on dev002 CPU 7. Each document contains 16 strings. For each decoded length, all escape placements produce identical contents.

| Decoded bytes/string | Plain | Escape first | Escape middle | Escape last |
|---:|---:|---:|---:|---:|
| 3 | 201 → 203 | 196 → 197 | 189 → 187 | 210 → 208 |
| 7 | 284 → 281 | 254 → 254 | 247 → 245 | 269 → 264 |
| 8 | 296 → 337 | 264 → 262 | 255 → 253 | 280 → 276 |
| 9 | 316 → 360 | 280 → 285 | 269 → 265 | 291 → 305 |
| 129 | 646 → 1,068 | 586 → 900 | 548 → 830 | 600 → 951 |
| 4,096 | 1,050 → 2,581 | 1,063 → 2,478 | 1,057 → 2,465 | 609 → 919 |

The guarded-page scanner test covers every byte value at every position for lengths 0–96, including unaligned starts, partial tails and empty input. It runs as json_string_scan in the default Linux suite. The differential driver checked 390 valid and 422 malformed documents for each of the typed scalar and vector entry points. Both drivers agreed with the valid-byte oracle and produced byte-identical diagnostics on every malformed document.

Build the shared typed_json_strings drivers with --batched for both checkouts, put the executables in bin/bulk and bin/word8, then run `taskset -c 7 python3 run.py bin --scanner` on dev002.

[Raw paired measurements](scanner.dev002.json)

## Runtime-dispatched AVX2 against the committed eight-byte scanner

The reference is the qualified selective-reserve decoder at `ae9eff851`. Apply [the candidate patch](dispatch.patch) to that commit for the dispatch build. On x86-64 with GCC or Clang it checks AVX2 availability and scans complete 32-byte blocks for a quote, backslash or control byte. Only the scanner function targets AVX2. Other builds, CPUs without AVX2, spans below 32 bytes and final partial blocks use the committed eight-byte scanner with its scalar tail. The length check sees the remaining JSON input, so an early short string in a vector can take the AVX2 path.

Median throughput in decimal MB of wire JSON per second, **eight-byte baseline → AVX2 dispatch**. Each document has 16 strings. A single Unicode escape at the indicated position preserves the decoded contents. Eleven shuffled rounds ran on CPU 7 of dev002 (Ryzen 9 5950X). Allocation counts and allocated bytes matched in every pair.

| Decoded bytes/string | Plain | Escape first | Escape middle | Escape last |
|---:|---:|---:|---:|---:|
| 3 | 203 → 202 | 198 → 190 | 192 → 182 | 211 → 201 |
| 7 | 292 → 322 | 251 → 248 | 247 → 239 | 272 → 264 |
| 8 | 356 → 356 | 262 → 260 | 257 → 251 | 283 → 278 |
| 9 | 385 → 369 | 292 → 280 | 263 → 262 | 312 → 293 |
| 31 | 533 → 583 | 424 → 441 | 403 → 427 | 453 → 476 |
| 32 | 568 → 585 | 433 → 450 | 428 → 436 | 407 → 423 |
| 33 | 568 → 593 | 463 → 460 | 445 → 440 | 499 → 494 |
| 129 | 1,246 → 1,416 | 1,080 → 1,152 | 989 → 1,056 | 1,112 → 1,203 |
| 4096 | 2,759 → 3,656 | 2,535 → 3,479 | 2,586 → 3,462 | 2,663 → 3,529 |

The 4,096-byte cases gained 32–37% across escape placements. At 129 bytes the gain was 7–14%. At 3–9 bytes, each escaped case was slower, with a largest median loss of 6.1%. The 31–33-byte cases ranged from a 1.2% loss to a 9.6% gain.

The AVX2 build and a separate build with `-DSCPP_JSON_PORTABLE_SCAN_ONLY=1` each matched the committed decoder on 390 valid and 422 malformed scalar documents and the same counts for vectors. Valid decoded bytes also matched the independent oracle. Malformed documents produced identical diagnostic messages including byte offsets and paths. Both builds passed the guarded-page scanner test for lengths 0–96. The fallback check ran on the same AVX2-capable host. The AVX2 candidate passed all 425 tests in the default suite on dev002.

Build the typed_json_strings drivers with `--batched` for all three builds with JSON enabled and selective reserve qualified. Put their binaries in `bin/word8`, `bin/dispatch` and `bin/portable`. Run `python3 run.py bin --check-pair word8 portable` and `taskset -c 7 python3 run.py bin --dispatch`.

[Raw paired measurements](dispatch.dev002.json)

## Scanning eight bytes before AVX2 dispatch

This dev002 run remeasures the committed portable decoder (`ae9eff851`), the direct AVX2 study (`5444519be`), and a candidate that checks eight bytes portably first. With at least 40 bytes of JSON input remaining, the candidate looks for a marker in the first eight. If all eight are ordinary and the CPU supports AVX2, it starts the 32-byte scanner at offset eight. Shorter spans, unsupported targets and tails use the portable implementation. Remaining input can extend beyond the current string.

Median decimal MB/s of complete JSON input, **portable / direct AVX2 / eight-byte-first AVX2**. Each vector contains 16 strings with identical decoded contents across escape positions. All three binaries ran in shuffled order over eleven rounds on CPU 7 of dev002 (Ryzen 9 5950X), using the same qualified selective reserve.

| Decoded bytes/string | Plain | Escape first | Escape middle | Escape last |
|---:|---:|---:|---:|---:|
| 3 | 209 / 209 / 209 | 199 / 191 / 193 | 192 / 181 / 182 | 212 / 199 / 203 |
| 7 | 294 / 327 / 308 | 255 / 252 / 249 | 252 / 245 / 242 | 271 / 264 / 265 |
| 8 | 371 / 357 / 346 | 267 / 263 / 264 | 259 / 254 / 251 | 284 / 279 / 279 |
| 9 | 384 / 388 / 370 | 294 / 281 / 277 | 271 / 267 / 264 | 314 / 296 / 294 |
| 31 | 531 / 578 / 558 | 423 / 439 / 432 | 403 / 424 / 417 | 452 / 478 / 466 |
| 32 | 576 / 590 / 585 | 438 / 457 / 445 | 426 / 435 / 427 | 412 / 430 / 422 |
| 33 | 580 / 605 / 593 | 464 / 459 / 455 | 455 / 447 / 440 | 491 / 491 / 489 |
| 129 | 1,248 / 1,439 / 1,404 | 1,063 / 1,159 / 1,132 | 996 / 1,065 / 1,037 | 1,141 / 1,239 / 1,237 |
| 4096 | 2,750 / 3,673 / 3,681 | 2,588 / 3,574 / 3,551 | 2,549 / 3,407 / 3,398 | 2,692 / 3,581 / 3,585 |

Across the three builds, 780 valid scalar and vector documents matched the independent value oracle, and 844 malformed documents produced byte-identical diagnostics including offsets and paths. Allocation counts and bytes matched across all 36 timed fixtures. The candidate passed the default suite on dev002, including the protected-page test for every byte at every offset in spans of length 0–96. A local build forcing the portable fallback also passed the protected-page test.

For the candidate apply [dispatch.patch](dispatch.patch), then [first8.patch](first8.patch). Build the batched typed_json_strings drivers for all three configurations. With executables under `bin/word8`, `bin/dispatch` and `bin/first8`, run `taskset -c 7 python3 run.py bin --first8`.

[Raw three-way measurements](first8.dev002.json)

## Integrated direct AVX2 scanner

The integrated reader checks the x86-64 runtime CPU feature before calling the AVX2 function on at least 32 remaining input bytes. Short strings within a vector can still enter that function because the span extends through the document. The portable implementation handles unsupported CPUs and tails. The guarded-page test exercises both the selected scanner and the portable implementation against expected marker positions.

Median throughput in decimal MB/s of wire JSON, **committed eight-byte decoder → integrated decoder**. The comparison used the qualified selective-reserve baseline from `ae9eff851` and the integrated source on top of `435013fd1`. Each document contains 16 strings. Eleven shuffled rounds ran on dev002 CPU 7. Allocation counts and bytes matched for all 36 fixtures.

| Decoded bytes/string | Plain | Escape first | Escape middle | Escape last |
|---:|---:|---:|---:|---:|
| 3 | 206 → 205 | 193 → 185 | 188 → 178 | 208 → 196 |
| 7 | 285 → 318 | 245 → 242 | 248 → 240 | 263 → 256 |
| 8 | 361 → 342 | 260 → 257 | 252 → 246 | 278 → 273 |
| 9 | 373 → 359 | 282 → 270 | 258 → 260 | 310 → 291 |
| 31 | 518 → 566 | 413 → 429 | 394 → 416 | 438 → 465 |
| 32 | 557 → 571 | 409 → 423 | 416 → 430 | 402 → 421 |
| 33 | 574 → 591 | 435 → 433 | 448 → 444 | 480 → 477 |
| 129 | 1,223 → 1,396 | 1,037 → 1,133 | 958 → 1,017 | 1,112 → 1,206 |
| 4,096 | 2,734 → 3,660 | 2,560 → 3,532 | 2,539 → 3,392 | 2,650 → 3,524 |

The default suite passed on the integrated dev002 build. Both the integrated build and a separate build compiled with `SCPP_JSON_PORTABLE_SCAN_ONLY=1` passed the guarded-page scanner test and matched the portable baseline on 780 valid scalar/vector documents and 844 malformed documents, including diagnostic messages, byte offsets and paths. Both builds ran on the same AVX2-capable Ryzen 9 5950X.

[Raw paired measurements](integrated-avx2.dev002.json)

## Short escaped strings after direct AVX2 integration

The committed decoder at `457f9790b` is the baseline. One escape splits a string into two ordinary-byte scans. The scanner sees the remaining document, so it can enter AVX2 even when its first byte is the closing quote. For example, in the 16-string, three-byte, escape-last Unicode fixture, 13 of the 16 suffix scans have at least 32 input bytes available. All 16 have zero ordinary bytes to copy.

Three independent patches change only the scan following a successfully decoded escape:

- [Portable suffix](portable_tail.patch) always uses the eight-byte scanner after an escape. The initial prefix still uses the committed dispatch.
- [Closing quote](close_tail.patch) consumes an immediately following closing quote and returns the decoded string. Other suffixes continue through the committed scanner.
- [SSE2 suffix](sse_tail.patch) checks the first 16 available suffix bytes inline on the existing x86-64 SIMD build path. If the block is ordinary it continues through committed dispatch. Shorter spans use the portable scanner.

Each build decoded vectors of 16 strings on dev002 CPU 7, in 11 shuffled rounds. The portable eight-byte decoder ran alongside the committed AVX2 decoder and all three candidates. The original Unicode fixtures encode one letter as `\uXXXX`. Additional fixtures replace one letter with an escaped slash (`\/`). Compare builds within a fixture since wire lengths differ between escape spellings. The plain-string controls use the same initial scanner for all three candidates.

The table shows throughput changes against the committed decoder. Each short entry is the median of the 12 per-case changes for lengths 3, 7, 8 and 9 and three escape positions. The long entries use 4,096-byte strings with a Unicode escape.

| Change after an escape | Short Unicode | Short slash | Long escape first | Long escape middle | Long escape last |
|---|---:|---:|---:|---:|---:|
| Portable suffix | +1.2% | +1.2% | -27.2% | -14.7% | +0.7% |
| Closing quote | +2.0% | +1.2% | +0.2% | +1.0% | +0.7% |
| SSE2 suffix | +0.6% | +0.9% | +0.2% | +0.2% | -0.3% |

The closing-quote shortcut improved all eight short escape-last fixtures in both runs. The initial gains were 2.7–6.0% over the committed decoder. A three-way recheck with a different shuffle seed gave 2.0–6.2%. Short escape-first and escape-middle fixtures ranged from −0.9% to +1.7% in the recheck. An empty suffix therefore accounts for a recoverable part of the regression. These experiments do not isolate the cause for nonempty short suffixes.

Across the seven 4,096-byte fixtures, the rechecked candidate stayed within −0.3% to +0.6% of the committed decoder and retained a 32.3–40.2% throughput gain over the eight-byte decoder. Keeping AVX2 available after an escape matters most when much of the string remains.

All builds matched the committed decoder's diagnostics byte for byte on 844 malformed scalar/vector documents and matched the independent oracle on 780 valid documents. An additional check covered 42 valid and 56 malformed scalar/vector documents around adjacent escapes, quotes, missing terminators and invalid delimiters. Allocation counts and bytes matched in all 42 timed fixtures. The closing-quote candidate passed all 425 default tests on dev002. A local guarded-page probe also checked the new SSE2 suffix scanner for lengths 0–96.

To reproduce, apply each candidate patch separately to the committed decoder. Build the batched typed_json_strings drivers under bin/dispatch (committed), bin/word8 (eight-byte decoder), bin/portable_tail, bin/close_tail and bin/sse_tail. Run `taskset -c 7 python3 run.py bin --short-escapes` and then `taskset -c 7 python3 run.py bin --close-recheck`.

[Five-way measurements](short-escapes.dev002.json) · [Closing-quote recheck](close-recheck.dev002.json)

## Integrated closing-quote shortcut

The shared reader now consumes a closing quote immediately after it finishes decoding an escape. Empty suffixes skip the second scanner call. Nonempty suffixes use the existing scanner. The integrated reader and the committed AVX2 reference from `457f9790b` were compared on dev002 CPU 7 with the eight-byte decoder as a third control. Each fixture held 16 strings. The table shows median wire throughput in decimal MB/s across eleven shuffled rounds, **committed AVX2 → integrated**, for an escape at the end of each string.

| Decoded bytes/string | Unicode escape last | Escaped slash last |
|---:|---:|---:|
| 3 | 197 → 207 (+4.8%) | 163 → 172 (+5.8%) |
| 7 | 262 → 276 (+5.3%) | 244 → 253 (+3.7%) |
| 8 | 272 → 284 (+4.5%) | 258 → 264 (+2.4%) |
| 9 | 289 → 302 (+4.5%) | 282 → 289 (+2.5%) |

Across the seven 4,096-byte fixtures (plain and both escape spellings at three positions), throughput changed by -0.1% to +0.7% against committed AVX2. The integrated decoder retained a +32.6% to +38.9% gain over the portable scanner. Allocation counts and bytes matched across all 42 fixtures.

The integrated build passed all 425 default tests on dev002. Scalar and vector differential checks matched the baseline diagnostics byte for byte on 844 malformed documents and matched the value oracle on 780 valid documents. An additional check around adjacent escapes, closing quotes, missing terminators and malformed delimiters matched on 56 malformed and 42 valid documents. Reproduce the timings with the typed_json_strings batched drivers in bin/dispatch, bin/word8 and bin/close_tail and the benchmark runner's `--close-recheck` mode.

[Raw integration measurements](integrated-close.dev002.json)

## Short strings with an escape first or in the middle

The reference is the committed closing-quote decoder at `56800977e`. An escape in the first position has an empty initial prefix. An escape in the middle has ordinary bytes on both sides. With a single escape in either position, the suffix scanner locates the closing quote, then the parser copies the ordinary bytes and makes another trip through its escape loop to consume that quote.

The dev002 comparison applies each patch independently to the reference:

- [Initial backslash check](escape_prefix.patch) returns an empty prefix when the first input byte after the opening quote is a backslash. It leaves escape validation to the existing parser.
- [Quote after a nonempty suffix](suffix_quote.patch) consumes the quote when the scanner has found a nonempty ordinary suffix and the next byte is a closing quote. Subsequent escapes and malformed endings still use the existing loop.
- [Combined checks](both_quotes.patch) applies both changes. The portable eight-byte decoder provides a separate throughput reference for the original regression and the long-string gains.

Each fixture is a typed vector of 16 strings. Each run takes the median of eleven shuffled rounds on dev002 CPU 7. The two escape spellings are a six-byte Unicode escape and an escaped slash. Compare throughput within a fixture because their wire lengths differ.

Median percentage change in throughput against the committed decoder, taken across the per-fixture changes. The first, middle and last columns each summarize eight fixtures (lengths 3, 7, 8 and 9 for both escape spellings). The plain column summarizes four fixtures.

| Build | Escape first | Escape middle | Escape last | Plain |
|---|---:|---:|---:|---:|
| Portable eight-byte control | +3.1% | +2.2% | -1.7% | +2.4% |
| Initial backslash check | +1.6% | -2.4% | -1.6% | -0.3% |
| Quote after nonempty suffix | +0.6% | +0.9% | +0.6% | -1.3% |
| Combined checks | +4.6% | +1.4% | +1.1% | +1.6% |

The initial backslash check alone improved escape-first throughput unevenly and slowed all eight escape-middle fixtures. The suffix quote check alone gave smaller gains. Together they improved every escape-first fixture in both runs. A three-way recheck with a different shuffle seed measured +2.6% to +6.1% (median +5.1%) against the committed decoder for escape-first and −0.4% to +3.1% (median +1.9%) for escape-middle. The patches interact in the compiled decoder. End-to-end timings do not establish the exact instruction cost behind that interaction.

In the recheck, the seven 129-byte fixtures changed by −0.8% to +0.6%. All seven 4,096-byte fixtures retained a substantial gain over portable (+31.6% to +37.9%), and all seven were slower than the committed decoder (−2.0% to −0.2%, median −0.8%). The five-way run showed the same tradeoff. The experiment supports the combined changes for workloads dominated by short escape-first strings. It leaves a measurable long-string cost and a residual gap on some short escape-middle fixtures.

All three candidates matched the committed decoder on the 780 valid and 844 malformed scalar/vector documents in the differential harness. An additional 42 valid and 56 malformed documents covered adjacent escapes, closing quotes, truncation and invalid delimiters. Valid values matched the independent oracle and diagnostics matched byte for byte, including offsets and paths. Allocation counts and bytes matched for all 42 timed fixtures. The combined build passed all 425 default tests on dev002.

Apply each candidate patch to the reference separately and build the typed_json_strings drivers with `--batched`. Place the binaries in bin/close_tail (committed), bin/word8 (portable), bin/escape_prefix, bin/suffix_quote and bin/both_quotes. On dev002 run `taskset -c 7 python3 run.py bin --nonempty` and `taskset -c 7 python3 run.py bin --nonempty-recheck both_quotes`.

[Five-way timings](nonempty.dev002.json) · [Independent recheck](nonempty-recheck.dev002.json)

## First-block scanning around a middle escape

The production reference is the closing-quote decoder at `56800977e`. The combined patch from the preceding study stays separate. In a 3–9-byte string with a single middle escape, each ordinary run is at most four bytes, but either scan can enter the out-of-line AVX2 function because the available input extends into subsequent array elements.

The [scanner placement patch](middle_scan.patch) tests one 16-byte SSE2 comparison inline at the initial prefix, the suffix following a decoded escape, or both. It checks for quotes, backslashes and control bytes. If the block has no marker, scanning resumes at byte 16 with the committed dispatcher. The helper uses the portable scanner when a full block is unavailable or the x86 SIMD path is disabled. Escape decoding and decoded-byte ownership follow the production path in all builds.

The comparison uses the committed decoder and the earlier eight-byte portable decoder as controls. Each fixture contains 16 strings. The short middle group includes lengths 3, 7, 8 and 9, with one Unicode escape or one escaped slash. The seven 4,096-byte fixtures cover plain strings and both spellings at the beginning, middle and end. All rates use the complete wire size. Each median comes from eleven shuffled rounds on dev002 CPU 7.

| First-block check placement | Median change vs. committed on short escape-middle | Fixtures faster than committed |
|---|---:|---:|
| Initial scan only | +1.7% | 7/8 |
| Post-escape scan only | +1.9% | 7/8 |
| Both scans | +4.9% | 8/8 |

Long-string samples in the first run were variable on the shared host. In the 4,096-byte Unicode escape-first fixture the slowest committed-decoder sample took 2.3 times as long as the fastest. The independent recheck used a different shuffle seed and only the two controls and the both-scans build. It doubled each timed loop count for the shorter fixtures and quadrupled it for the 4,096-byte fixtures.

In the longer recheck, using both checks gained 3.8–6.3% on all eight short escape-middle cases (median +4.3%). It beat the portable control in seven of eight. However, every 4,096-byte fixture lost 0.8–1.7% against committed AVX2, although each remained 32.0–37.5% faster than portable.

A [bounded-span follow-up](middle_window.patch) applies on top of the placement patch. With both placements enabled, it sends spans over 512 bytes directly to the existing dispatcher. The cutoff is an experimental choice that separates the short 16-string documents from the long documents in this matrix. It uses remaining JSON bytes, so the follow-up also measures a 256-element vector of seven-byte strings with a middle escape. Most elements in that control have more than 512 bytes left in the document.

Median percentage change (minimum to maximum) for the bounded candidate in a separate longer-duration three-way run:

| Fixture group | Against committed decoder | Against portable |
|---|---:|---:|
| Short, escape middle | +4.1% (+2.8% to +8.3%) | +2.4% (-1.1% to +14.1%) |
| 129 bytes | +0.6% (+0.0% to +1.8%) | +9.0% (+6.6% to +14.7%) |
| 4,096 bytes | -1.4% (-3.9% to -0.7%) | +33.2% (+29.4% to +36.9%) |

In the 256-element control the bounded candidate gained 3.1% over the committed decoder for the middle Unicode escape and 6.3% for the escaped slash. The 512-byte rule still trailed the committed decoder in every 4,096-byte fixture. Keep the production dispatcher while exact long-string performance is a requirement. The unbounded two-scan result narrows the investigation to scans on both sides of the escape, but the end-to-end measurements cannot attribute the entire gap to dispatch overhead.

Across the four candidates, 822 valid scalar/vector documents matched the independent value oracle and 900 malformed documents produced byte-identical diagnostics to the committed decoder, including byte offsets and paths. Allocation counts and allocated bytes matched the controls on every timed fixture. The unbounded and bounded both-scans builds each passed all 425 default tests on dev002. A guarded-page probe directly exercised the helper locally for every byte value at every offset in spans of 0–96 bytes, also with SIMD disabled. The bounded version additionally passed at lengths 510–515.

To reproduce, apply middle_scan.patch to the committed reader. Build the batched typed_json_strings drivers with SCPP_STUDY_PREFIX16, SCPP_STUDY_SUFFIX16, or both definitions enabled in the runtime build. For window16, apply middle_window.patch as well and enable both definitions. Place the drivers under bin/prefix16, bin/suffix16, bin/both16 and bin/window16. Use bin/close_tail for the committed baseline and bin/word8 for the portable reference. Pin run.py to CPU 7 and pass `bin --middle-scan prefix16 suffix16 both16`, then `bin --middle-scan both16` and `bin --middle-scan window16`.

[Placement comparison](middle-scan.dev002.json) · [Both-scans recheck](middle-scan-recheck.dev002.json) · [Bounded-span follow-up](middle-window.dev002.json)
