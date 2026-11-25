# Foreground cost of source-parameter transfer

Doc Status: supporting

The successful parameter-use proof cost 6.34–6.52 µs on the tokenizer body across cpp/php/js. The no-assignment case cost 0.86–0.90 µs, and an immediately visible later read cost 1.72–1.79 µs. Keep this bounded check in ordinary preparation for now. These measurements cover the scanner and two edits. A late read nested deep in a larger body could take longer to disqualify.

| Flavor | Case | Isolated proof (µs) | Whole body control (µs) | Whole body enabled (µs) | Paired delta (µs) | Middle 50% of deltas (µs) |
|---|---|---:|---:|---:|---:|---:|
| cpp | move | 6.52 | 560.57 | 570.18 | +9.21 | +8.08 to +10.11 |
| cpp | miss | 0.86 | 556.13 | 556.37 | +0.00 | -3.83 to +1.31 |
| cpp | live | 1.76 | 567.53 | 570.11 | +3.31 | +1.79 to +4.31 |
| php | move | 6.34 | 483.78 | 495.24 | +11.19 | +8.81 to +11.88 |
| php | miss | 0.90 | 485.09 | 488.11 | +4.37 | +1.95 to +5.94 |
| php | live | 1.79 | 481.40 | 486.21 | +5.19 | +2.37 to +7.42 |
| js | move | 6.42 | 535.66 | 546.71 | +10.37 | +9.32 to +11.45 |
| js | miss | 0.87 | 542.44 | 547.74 | +5.51 | +3.80 to +6.39 |
| js | live | 1.72 | 541.89 | 549.18 | +8.64 | +7.34 to +11.09 |

**Fixtures.** The move case is the scanner as written. The miss case keeps the by-value string parameter and record local but assigns a string literal to the field. The live case assigns the parameter and immediately reads it again in an echo. Each worker checks actual emitted C++ for the expected assignment and for both the checked view and the final vector move. Scanner units in each pair must hash identically after normalizing the one permitted string move. Emission and parsing are outside all timers.

**Method.** dev002, CPU 7, PHP 8.5.12, Xdebug off and CLI OPcache disabled. Each flavor/case uses two warm PHP processes. The control skips the parameter phase of transfer preparation and retains the ordinary local-vector proof. Each of 12 paired rounds prepares the entire scanner body 200 times per worker, alternating which worker runs first. The isolated column times the parameter phase directly on the settled tree, with 2,000 calls per round. It excludes the outer parameter eligibility screen and includes invocation overhead. Values are medians of per-round means. The paired delta is computed within each round before taking the median.

**Interpretation.** The successful move added 9–11 µs to whole-body preparation, roughly 1.6–2.3% of the control median. Its higher proof cost is consistent with checking the body for another use. A miss still scans top-level statements for eligible assignments. The immediately visible read disqualifies the candidate before scanning nested blocks. Whole-body deltas include measurement variation and process differences, particularly visible on misses. Use the isolated column to estimate proof work and the paired column to judge foreground latency. These are preparation measurements for a changed body. Incremental reuse skips preparation for an unchanged body.

**Reproduce** from the checkout root with current generated compiler bindings, the generator templates and runtime support headers available:

```sh
taskset -c 7 python3 specs/planning/benchmarks/tokenizer/parameter_preparation.py compiler
```

[Raw paired samples](parameter_preparation.dev002.json) · [Paired driver](parameter_preparation.py) · [PHP timing worker](parameter_preparation.php) · [Separate native execution and allocation results](byte_access/parameter_transfer.md)
