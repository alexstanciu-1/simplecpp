# Qualifying a UTF-8 prevalidator for typed JSON decode

Doc Status: planning

The [goal](../../json_decode_optimization.md) uses the production decoder at `56800977e`, the eight-byte ASCII study and the width-only study as controls. The original SSE2 string scanner and earlier UTF-8 alternatives remain study patches. The runtime source of the production build matches the goal's starting revision in the JSON reader and UTF-8 helper. All comparisons use JSON-enabled Release builds from the root preset with Clang 18, libc++ and qualified selective reserve. Full typed timings observe the result of generated from_text on CPU 7 of dev002.

## Where the cost appears

The constructor probe calls the linked generated read_leaf_record with a callback that only increments a counter. That includes construction, prevalidation, callback dispatch and destruction of the same borrowed cursor as full decode. The isolated measure runs validation on the same wire document in its own translation unit. Timings below are median serialized TSC ticks per document over eleven shuffled rounds. Hardware performance counters were restricted on dev002. TSC measures reference ticks independently of changing core frequency; these are not retired core cycles.

| Wire fixture and decoder | Isolated validation | Reader constructor path | Full typed decode |
|---|---:|---:|---:|
| 4,096-byte ASCII scalar, committed | 3,015 | 3,054 | 4,044 |
| Same, original eight-byte check | 1,145 | 808 | 1,768 |
| Same, width-only | 770 | 1,192 | 2,135 |
| Sixteen long ASCII strings, original eight-byte check | 18,337 | 12,313 | 24,264 |
| Same, width-only | 12,257 | 18,454 | 30,674 |
| Dense two-byte Unicode, committed | 14,361 | 14,988 | 15,862 |
| Same, width-only | 6,035 | 7,666 | 8,576 |

The reversal between isolated and integrated ASCII loop performance is already present at construction. The constructor difference accounts for nearly all the full-decode difference on both long ASCII fixtures. Disassembly shows that Clang emits the validators inside the checked reader path with different loop layouts from the isolated translation unit. These timings do not pin the cost to an individual instruction.

## Candidates

1. [Long ASCII runs](ascii_run.patch), applied on top of [width.patch](width.patch). Once an eight-byte block is known to be ASCII, load four more portable eight-byte words at a time and check their combined high bits. At a mixed block resume the ordinary eight-byte/width path without skipping it. Prediction: fewer loop iterations in long ASCII documents, with the existing width checks on dense Unicode.

[Raw constructor measurements](constructor.dev002.json)

The in-loop candidate improved full typed decode on the long ASCII scalar by 239–241% over production in two runs and exceeded the original ASCII control by 47–51%. On dense four-byte Unicode it gained 36–37% over production, but lost 4.8–4.9% against the protected width-only control in both runs. That repeatable loss kept it experimental.

2. [Document-entry scan](ascii_head.patch), also applied to width.patch alone. Scan complete 32-byte blocks before entering the width validator once per document. A block with a high bit remains for the ordinary validator. Prediction: recover long-ASCII speed without mixing a new long-run loop into every Unicode step. The Unicode-then-ASCII fixture measures the cost of limiting this scan to the leading run.

On the entry-scan candidate the twice-shuffled long ASCII scalar ran 224–226% faster than production. The initial dense two-byte and short Unicode cases were noisy, including more than a twofold spread within one binary. A fresh run with four times as many iterations per round gave +0.6% on the short ASCII scalar, +1.7% on short Unicode and +3.8% on three integers against production. Dense two-, three-, four- and mixed-width gains were 83%, 44%, 42% and 52% respectively; their medians trailed the width control by 0.5–0.8%, with overlapping sample ranges on every fixture in the longer run. The candidate passed all 425 default tests, owned-result lifetime checks, guarded-page checks with and without AVX2 dispatch, and 11,796,480 width comparisons.

3. [One length check](one_bound.patch), applied on top of width.patch alone. Retain the document-entry scan but derive the required multibyte width from the lead byte before reading any continuation. Prediction: reducing the number of length checks on three- and four-byte paths may recover the remaining dense throughput. Two-byte data is a control for the extra width calculation.

[In-loop run](ascii_run-35121.dev002.json) · [In-loop recheck](ascii_run-69743.dev002.json) · [Entry run](ascii_head-35121.dev002.json) · [Entry recheck](ascii_head-69743.dev002.json) · [Longer qualification](ascii_head-focused.dev002.json)

The one-check candidate matched the width control on four-byte Unicode, but was 15% slower on two-byte text and 10.5% slower on three-byte text in both runs. Keep it experimental.

## Decision

Select the document-entry candidate for integration. It exceeded both long all-ASCII controls and retained the dense gains in full decode (42–83% above production in the longer qualification). The remaining 0.5–0.8% difference from the width control is reported above; the sample ranges overlap. It does not recover every gain of the ASCII-only experiment on mixed text: on an early Unicode character followed by ASCII, it was about 85–88% above production and about 19% below the original ASCII candidate. Short and numeric cases held up against the committed decoder in the longer recheck. All three candidates matched the oracle on 1,008 valid inputs and the original complete diagnostics on 1,952 malformed inputs.

Reproduce with build.py and run.py from this directory. Build the production, original ascii8.patch and width.patch binaries in bin/base, bin/ascii8 and bin/width. For each new candidate, apply its patch on top of width.patch, use the patch stem as its binary directory name, and keep compiler flags identical. Run on dev002 CPU 7:

```sh
taskset -c 7 python3 run.py bin --diagnose --dense width
taskset -c 7 python3 run.py bin --dense width "$candidate" --seed 35121
taskset -c 7 python3 run.py bin --dense width "$candidate" --seed 69743
taskset -c 7 python3 run.py bin --dense width ascii_head --seed 91703 --work-factor 4 --fixtures empty short-ascii short-unicode numbers-3 dense-2byte dense-3byte dense-4byte mixed-width-dense ascii-4096 words-long
```

[One-check run](one_bound-35121.dev002.json) · [One-check recheck](one_bound-69743.dev002.json)

## Production verification

The two runtime headers built from the integration diff matched the qualified candidate byte for byte. A new default test compares validated widths with the scalar decoder and verifies invalid-sequence offsets across leading ASCII blocks. The forced-portable build disables the AVX2 JSON string scanner; the UTF-8 block checks themselves use portable loads. Both builds passed the scalar/vector oracle and diagnostic corpus. An independent ownership probe overwrote and destroyed the input before observing the decoded fields.

Median full typed throughput change, two fresh shuffled runs on dev002 CPU 7. The columns use the committed, original ASCII and width-only controls respectively.

| Fixture | Against production at 56800977e | Against original eight-byte check | Against width-only |
|---|---:|---:|---:|
| alternating | +16.9% / +17.2% | +26.2% / +27.3% | -0.7% / -0.8% |
| ascii-4096 | +223.5% / +225.5% | +41.4% / +41.8% | +71.9% / +72.2% |
| ascii-then-unicode | +69.8% / +69.8% | +51.3% / +52.1% | +10.3% / +10.4% |
| dense-2byte | +83.6% / +83.4% | +86.9% / +87.3% | -0.1% / -0.5% |
| dense-3byte | +43.8% / +43.8% | +49.6% / +50.5% | -0.3% / -0.9% |
| dense-4byte | +43.2% / +42.6% | +53.1% / +53.3% | -0.9% / -0.6% |
| empty | +2.2% / +2.8% | +0.4% / +1.7% | +1.1% / +3.1% |
| escaped-unicode | +14.3% / +13.6% | +6.2% / +6.2% | +3.5% / +3.0% |
| mixed-width-dense | +51.9% / +51.5% | +60.8% / +60.6% | -0.3% / -0.4% |
| numbers-3 | +6.4% / +5.1% | +5.3% / +1.8% | +3.3% / +3.1% |
| numbers-4096 | +6.6% / +6.5% | +0.1% / +0.1% | +3.1% / +2.1% |
| short-ascii | +4.1% / +3.1% | +0.4% / +2.2% | +1.4% / +1.8% |
| short-unicode | +5.8% / +4.7% | +2.2% / -0.6% | +1.2% / +0.9% |
| sparse-4096 | +48.2% / +47.4% | -11.7% / -11.8% | -0.5% / -0.2% |
| unicode-then-ascii | +86.4% / +86.1% | -19.5% / -19.3% | -1.3% / -1.9% |
| words-long | +289.5% / +295.0% | +55.2% / +56.6% | +95.1% / +97.6% |
| words-short | +10.1% / +11.9% | +4.4% / +6.3% | +4.0% / +4.9% |

[Integrated run](integrated-73139.dev002.json) · [Integrated recheck](integrated-37237.dev002.json) · [Forced-portable verification](portable-verification.json)

All 426 default tests passed on the final dev002 run. On the first parallel run the compiler frontend service idle test failed its timing-sensitive status check, then passed immediately in isolation. Both complete integration measurements exceeded production throughput on all 17 fixtures; short-input medians vary with shared-host load. The final result retains the dense gains while materially exceeding the original ASCII control on long all-ASCII input. The other new candidates remain experimental.
