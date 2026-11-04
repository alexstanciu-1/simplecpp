# Simplified compiler-layout sample
Doc Status: supporting

Mirrors all 257 `.phs` paths under `../my-try/phpp/`, replacing the compiler
implementation with supported PHP-flavor syntax. No donor implementation or build
products are copied. `module.json` explicitly maps the retained `.phs` filenames
to the PHP flavor; the compiler does not automatically discover this extension.

Each of the 256 non-entry files defines one uniquely named no-argument void
function, an integer local, a call to the next function (except the last), and a
void return. `main.phs` calls the first function, echoes `my-try-simple: OK`, and
returns exit status 8. This is a synthetic file-layout workload, not a compiler.
The source is 42,195 bytes versus 1,943,499 bytes in the donor compiler corpus.

## Reproduce

From the repository root:

```sh
XDEBUG_MODE=off php v0.2/samples/my-try-simple/benchmark.php
```

The standalone harness copies source into a temporary directory, checks full
semantic preparation once, warms filesystem caches, and measures seven runs in
one PHP process. It removes the temporary directory afterward. The repository
sample is never modified by benchmark scenarios. Compiler implementation code is
unchanged; a benchmark-local read wrapper counts actual `.phs` reads.

Stage measurements cover every file: reading includes path validation and source
snapshot/line-index construction; parsing includes local symbol collection. Global
symbol indexing is measured separately. Module loading is outside these individual
stage timers. Full/incremental frontend timers include module loading, mtime/size
checks and symbol publication, and inventory writes when membership changes.
Manifest edits, test-file edits and verification assertions are outside timers.
The explicit source map means these are not recursive directory-discovery timings.

Checks cover exact AST/token identity on reuse, actual source-read counts, source
status counts, redirected function keys after body changes/moves, stable symbol-entry retention, symbol addition/removal, and final key-set equivalence with
a fresh frontend run. The edit changes an integer literal and its byte length,
avoiding the known same-size/same-second metadata-check limitation.

By default, this harness measures only the frontend, not the separate incremental body-cache API or lowering. The timed passes stop after symbol
publication. No native executable was built by this benchmark. PHP GC is collected
between sample sequences; this is neither cold-disk latency nor a long-session
memory-retirement test.

## Local WSL measurement (2026-11-02)

PHP 8.5.7, Xdebug disabled; seven warm-cache samples. Raw times and proof counts
are retained in [benchmark-results.json](benchmark-results.json). 4,870 tokens;
peak PHP allocator usage across the whole harness: 8 MiB.

| Stage / scenario | Median ms | Min–max ms |
| --- | ---: | ---: |
| read_snapshot | 5.375 | 5.138–5.913 |
| tokenize | 22.868 | 22.615–23.381 |
| parse_collect | 4.819 | 4.616–5.148 |
| symbol_index | 0.299 | 0.276–0.333 |
| full | 36.696 | 35.213–37.796 |
| unchanged | 3.922 | 3.480–4.413 |
| changed | 4.898 | 3.882–5.626 |
| added | 4.861 | 4.227–5.747 |
| removed | 5.143 | 5.040–5.490 |
| moved | 5.447 | 4.990–7.263 |

| Incremental scenario | Reused roots | Source reads | Removed records |
| --- | ---: | ---: | ---: |
| full | 0 | 257 | 0 |
| unchanged | 257 | 0 | 0 |
| changed | 256 | 1 | 0 |
| added | 257 | 1 | 0 |
| removed | 257 | 0 | 1 |
| moved | 256 | 1 | 1 |

## Integrated UTF-8 tokenizer results

The production tokenizer now uses `preg_match_all` with UTF-8 validation for all
three flavors. The original results above are the previous byte-scanner baseline.
The harness and sample are unchanged; this rerun uses the integrated compiler.
Both runs used Xdebug-off PHP 8.5.7, seven warm-cache samples. Comparisons are
separate runs, not interleaved A/B timings. Raw rerun samples and all reuse proofs
are retained in [benchmark-utf8-results.json](benchmark-utf8-results.json).

| Stage/scenario | Previous median ms | Integrated median ms | Integrated min–max ms |
| --- | ---: | ---: | ---: |
| read_snapshot | 5.375 | 5.240 | 4.654–5.389 |
| tokenize | 22.868 | 6.515 | 6.348–6.728 |
| parse_collect | 4.819 | 4.635 | 4.447–4.772 |
| symbol_index | 0.299 | 0.292 | 0.256–0.420 |
| full | 36.696 | 19.362 | 18.630–19.856 |
| unchanged | 3.922 | 3.778 | 3.383–4.167 |
| changed | 4.898 | 4.360 | 3.635–5.021 |
| added | 4.861 | 4.210 | 4.008–4.958 |
| removed | 5.143 | 4.919 | 4.613–5.311 |
| moved | 5.447 | 5.165 | 5.017–5.283 |

## Current preparation and watch-coordinator baseline (2026-11-04)

See [the current report](benchmark-2026-11-04.md) for stage timings, incremental
preparation reuse, raw samples and measurement boundaries. Add `--prepare` to the
existing command to time full preparation/C++ text emission and run incremental
preparation after each frontend scenario. The default frontend-only mode is retained.
Neither mode invokes Clang; original sample sources and previous results are preserved.

## Complete build including Clang

The [native benchmark report](benchmark-2026-11-04-native.md) extends the compiler
stage measurements through Clang and mold. It compares O0/O1/O2 with no runtime
PCH, freshly built PCH and reused PCH, using Ninja -j4 and no debug symbols.
It retains Clang frontend/LLVM phase traces, linker timings, total build wall time,
and executable correctness checks. Use `native-benchmark.py --output <new-directory>`
to reproduce; the existing runtime archive is reused. Compiler-only timings above
are not complete executable build times.

## Grouping experiment (benchmark only)

[1 / 4 / 8 / 258 translation units with Ninja -j8](benchmark-2026-11-04-grouping.md)
compares full O2 builds, including frontend/LLVM/linker profiles and a same-run
258-unit -j4 control. `grouping-benchmark.py` concatenates emitted C++ only in its
own artifact directory; it does not change compiler emission or shared build defaults.
