# Direct token construction on nine representative files

Doc Status: supporting

Direct construction increased aggregate scanner throughput by **37.2% on Clang 18.1.3**
and reduced it by **8.0% on GCC 13.3.0**. The direction held on every measured file
for each compiler. The aggregate represents one scan of each file and divides the
sum of ordinary median times by the sum of selected median times.

| Compiler | Flavor | File | Ordinary MiB/s | Direct MiB/s | Paired throughput change | Middle 50% of paired changes |
|---|---|---|---:|---:|---:|---:|
| clang++ | cpp | batch_001.scpp | 424.7 | 528.9 | +23.7% | +23.1% to +26.2% |
| clang++ | cpp | scanner.cpp | 410.0 | 612.3 | +49.0% | +48.7% to +50.5% |
| clang++ | cpp | core.scpp | 386.6 | 509.8 | +32.2% | +31.3% to +33.9% |
| clang++ | php | batch_000.phs | 458.0 | 661.4 | +45.2% | +44.5% to +47.1% |
| clang++ | php | scanner.php | 450.3 | 700.2 | +55.7% | +54.4% to +57.4% |
| clang++ | php | main.phs | 368.9 | 553.8 | +49.5% | +48.9% to +50.8% |
| clang++ | js | batch_002.jss | 420.4 | 510.6 | +21.3% | +20.3% to +22.7% |
| clang++ | js | scanner.js | 394.3 | 569.7 | +45.0% | +42.6% to +47.9% |
| clang++ | js | labels.jss | 351.1 | 515.8 | +47.2% | +46.2% to +48.3% |
| g++ | cpp | batch_001.scpp | 597.2 | 521.6 | -12.6% | -12.9% to -12.0% |
| g++ | cpp | scanner.cpp | 549.6 | 492.1 | -10.1% | -10.7% to -10.0% |
| g++ | cpp | core.scpp | 487.8 | 436.0 | -10.8% | -11.2% to -10.4% |
| g++ | php | batch_000.phs | 630.2 | 583.7 | -7.5% | -8.0% to -6.6% |
| g++ | php | scanner.php | 614.1 | 567.3 | -7.6% | -7.9% to -7.4% |
| g++ | php | main.phs | 406.1 | 393.0 | -3.4% | -4.2% to -3.1% |
| g++ | js | batch_002.jss | 555.9 | 516.8 | -7.2% | -7.5% to -6.6% |
| g++ | js | scanner.js | 510.0 | 480.5 | -5.5% | -6.1% to -4.8% |
| g++ | js | labels.jss | 453.5 | 426.4 | -5.9% | -6.3% to -5.4% |

## Controls and verification

Both builds used the same stable checked view, capped previous-count reserve, moved
source string and final vector transfer. The trial’s selected form directly constructs
a token when storage has spare capacity and uses the ordinary append on growth.
The measured difference includes that explicit capacity fast path. Identical
benchmark-only keyword, literal and punctuation handlers extend the bounded scanner
to cover the full files. An assertion checks that removing the token construction
and append statements leaves byte-identical scanner units in each pair.
The six billing and support files match the earlier whole-file corpus hashes. The
three scanner files use the current scanner source. Paths and hashes are recorded in the data.

All 18 file/compiler combinations matched the PHP tokenizer’s full token stream
in both variants, with both zero and previous-count hints. The timed scans use the
previous count from the same file, representing repeat scans with a good estimate.
This does not measure the cold growth-heavy case.

dev002 CPU 7, C++23, -O2, no LTO, separate scanner and driver units. Each file and
compiler ran one discarded warmup pair and 16 adjacent measured pairs with
alternating execution order. Each recorded time is the median of nine batches of
128 scans, including result destruction. Absolute throughput divides file size in MiB by seconds per scan. The per-file throughput change uses paired
ratios. The middle half describes observed dispersion.

## Decision

Clang is the v0.2 optimization target. Its gain on all nine files supports taking
direct construction into a bounded integration slice. Preserve the ordinary append
fallback and verify failure behavior and compile cost on Clang. The GCC rows
document the original experiment. Current runners exercise Clang across cpp/php/js.

## Reproduce

First generate the destination trial bundles and native objects with the commands
in the [destination trial report](report.md). With `d` set to that benchmark directory,
run the following from the checkout root. Preparation needs the PHP compiler and its
bindings. Transfer the prepared corpus alongside the trial bundles for timing on dev002.

```sh
python3 -B "$d/source_files.py" prepare v0.2 /tmp/token-destination /tmp/token-source-corpus
taskset -c 7 python3 -B "$d/source_files.py" bench /tmp/token-destination /tmp/token-source-corpus runtime "$d/../driver.cpp"
```

[Paired timings, file hashes and toolchains](source_files.dev002.json) · [Benchmark driver](source_files.py)
