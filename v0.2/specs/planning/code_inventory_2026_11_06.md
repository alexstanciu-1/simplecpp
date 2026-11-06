# v0.2 code inventory — 2026-11-06

Doc Status: planning

Snapshot after commit `88e3fe490`, with the runtime ABI debt documented locally.
Counts are physical lines containing code after removing comments and blank lines.
Preprocessor directives and strings remain code; Python docstrings and shebangs are
excluded as documentation/comments. No comment-line counts are added to the totals.
Pygments 2.17.2 lexers were used, with assertions for comment/string/directive handling.
All complete PHP source files were cross-checked with PHP token_get_all: counts agree
except the launcher's single shebang, which this inventory excludes.

Scope: active v0.2 runtime/compiler, shared tools/build files/tests, and source samples.
Excluded: build trees, caches, IDE files, docs/Markdown, specs/planning evidence, logs,
locks and binaries. Published runtime/generated and compiler/generated are included;
per-project generated build products are excluded. JSON metadata/config/manifests and
fixture data are reported separately from executable source. Templates are handwritten
source, not generated output. Shared generator code is counted once. Samples include
copied donor code and synthesized benchmark inputs, not active compiler implementation.
Counts describe current published output/configuration, not every possible generator output.
One compact generated line counts as one physical line, regardless of statement count.

## Runtime and compiler

| Area | Runtime | Compiler |
| --- | ---: | ---: |
| Handwritten implementation (including compiler host/CLI) | 2,070 | 7,342 |
| Generated implementation | 1,583 | 902 |
| Tests and verification harnesses | 3,771 | 10,409 |
| Internal tools | 0 | 239 |
| Examples | 52 | 2,563 |
| Total | 7,476 | 21,455 |

Implementation only: handwritten 9,412 + generated 2,485 = **11,897** lines.

## Compiler handwritten implementation breakdown

| Area | Lines |
| --- | ---: |
| Input/module loading | 759 |
| Tokenizer | 414 |
| Parser, AST structures and collection | 2,611 |
| Analysis/preparation | 1,033 |
| C++ backend | 813 |
| Native target support | 29 |
| Compiler orchestration/incremental | 684 |
| Type interfaces | 180 |
| Host/CLI and boot | 819 |
| Total | 7,342 |

## Shared tools/build support

| Area | Lines |
| --- | ---: |
| API generator implementation | 3,014 |
| API generator handwritten templates | 456 |
| API generator tests | 867 |
| Generator parity tests | 947 |
| Web compiler panel | 862 |
| Layout probe | 244 |
| CMake/build scripts | 504 |
| Total | 6,894 |

Runtime + compiler + shared support = **35,825** code lines, including tests/examples.

## Separate source samples

| Sample | Lines |
| --- | ---: |
| accounting-large | 9,835 |
| accounting-small | 2,548 |
| capability-benchmarks | 18,935 |
| my-try donor sample | 37,029 |
| my-try-simple | 2,154 |
| Total | 70,501 |

Including source samples: **106,326** code lines. These samples are not included in the
11,897-line implementation subtotal or the 35,825-line active/support total.

## Metadata and configuration (not code totals)

| Area | Nonblank data/config lines |
| --- | ---: |
| Runtime metadata and source module manifests | 2,429 |
| Runtime generated JSON | 2 |
| Compiler generated inventory | 1 |
| Runtime test configurations | 372 |
| Generator parity fixture JSON | 3,930 |
| Build presets | 63 |
| Total excluding samples | 6,797 |

These are formatting-dependent JSON physical lines; minified inventories count as one
line. Sample JSON/provenance/configuration is listed separately in the raw inventory.

## Reproduction and per-file inventory

Run `python3 v0.2/build/code-inventory-2026-11-06/count.py` from the repository.
`v0.2/build/code-inventory-2026-11-06/inventory.json` records every counted path, category, kind and line count, as well as
file counts per category. No compiler or runtime implementation files were modified.

## Sizes in KB

1 KB = 1,000 bytes. **Code KB** excludes comment/docstring tokens and blank lines,
removes trailing whitespace, retains indentation and uses one LF per retained line.
UTF-8 byte counts are used. **File KB** is the full original file length for those
same selected code files, including comments and whitespace; it is not filesystem
allocated space. JSON remains separate from code totals.

| Area | Code lines | Code KB | File KB |
| --- | ---: | ---: | ---: |
| compiler/examples | 2,563 | 51.40 | 51.53 |
| compiler/generated | 902 | 30.20 | 39.97 |
| compiler/host+CLI | 819 | 34.02 | 38.75 |
| compiler/source | 6,523 | 224.78 | 304.35 |
| compiler/tests | 10,409 | 531.53 | 556.47 |
| compiler/tools | 239 | 11.69 | 12.53 |
| runtime/examples | 52 | 2.12 | 2.32 |
| runtime/generated | 1,583 | 91.10 | 92.48 |
| runtime/source | 2,070 | 83.79 | 90.96 |
| runtime/tests | 3,771 | 215.32 | 220.39 |
| samples/accounting-large | 9,835 | 533.90 | 541.58 |
| samples/accounting-small | 2,548 | 122.28 | 123.34 |
| samples/capability-benchmarks | 18,935 | 583.58 | 584.40 |
| samples/my-try | 37,029 | 1,720.73 | 1,943.50 |
| samples/my-try-simple | 2,154 | 57.11 | 76.80 |
| shared/build | 504 | 36.43 | 37.86 |
| shared/generator/source | 3,014 | 171.10 | 175.65 |
| shared/generator/templates | 456 | 20.43 | 23.37 |
| shared/generator/tests | 867 | 53.80 | 56.34 |
| shared/tests/generator_parity | 947 | 58.88 | 61.24 |
| shared/tools/compiler_panel | 862 | 57.70 | 59.98 |
| shared/tools/layout_probe | 244 | 13.52 | 14.52 |

| Combined area | Code lines | Code KB | File KB |
| --- | ---: | ---: | ---: |
| Implementation only | 11,897 | 463.89 | 566.51 |
| Runtime including tests/examples | 7,476 | 392.33 | 406.15 |
| Compiler including tests/tools/examples | 21,455 | 883.62 | 1,003.60 |
| Shared support | 6,894 | 411.87 | 428.95 |
| Active + support, excluding samples | 35,825 | 1,687.82 | 1,838.70 |
| Samples | 70,501 | 3,017.60 | 3,269.63 |
| All code including samples | 106,326 | 4,705.42 | 5,108.33 |

Metadata/configuration sizes are available per file and category in `v0.2/build/code-inventory-2026-11-06/inventory.json`.
