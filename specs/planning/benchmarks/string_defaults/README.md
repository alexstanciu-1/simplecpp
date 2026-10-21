# String replacement and comparison benchmark

Doc Status: supporting

This harness measures native programs emitted by `compiler/my-try/`, alongside
native C++, PHP, Rust, Python, JavaScript and Java. It does **not** measure compiler
throughput. The compiler is PHP-hosted; the PHS benchmark is compiled to native
code through the production publisher and native builder.

See the [dated findings and measurements](../../results/string_defaults_2026_10_21/README.md)
and the [semantics comparison](../../utf8_string_defaults_comparison.md).
The [bulk-comparison follow-up](../../results/string_bulk_compare_2026_10_21/README.md)
retains the first runtime optimization and its full before/after rerun.
The [snapshot-elision follow-up](../../results/string_borrow_2026_10_21/README.md)
adds guarded before/after measurements and compilation cost on dev002.

## Workloads

The deterministic corpus contains ASCII and valid UTF-8 (`é中😀`) at nominal
64-byte, 4-KiB and 64-KiB sizes. Each has seven cases:

- Literal replacement with no matches, dense shrinking replacement, and dense
  growing replacement.
- Three-way case-sensitive comparison: equal, first-character mismatch, and
  last-character mismatch.
- A length-and-accumulate loop control.

The ASCII needle `ab` and UTF-8 needle `é` each occupy **two UTF-8 bytes**, but one
is two code points and the other is one. Match counts and output sizes also differ.
Consequently ASCII/UTF-8 ratios are not an isolated measurement of decoding cost.
`cases.json` records exact sizes, code-point counts, UTF-16 units and match counts.
Late-mismatch cases add one ASCII character; early-mismatch UTF-8 cases replace
the first two-byte character with one ASCII byte. Sizes are explicit in the data.

Replacement is literal, non-overlapping, replace-all, using nonempty needles and
replacement text without JS substitution markers. Comparison means the sign of
lexicographic ordering; it does not mean normalization, collation, case folding,
or grapheme comparison. Mismatches occur at ASCII characters, so UTF-8 byte order,
UTF-16 unit order and code-point order agree for this corpus.

## Implementations

| Label | Measured operation |
| --- | --- |
| `mytry_concrete` | Strict PHS `str_replace` / `substr_compare(s,t,0)`, default concrete lowering |
| `mytry_ordinary` | Same PHS with `--no-prelower-templates` |
| `runtime` | Direct C++ calls to `scpp::str::replace` / `scpp::str::substr_compare` |
| `runtime_snapshot` | Same helpers after explicit copies matching the emitted call snapshots |
| `cpp` | Native `std::string::find`/append replacement loop; `std::string::compare` |
| `cpp_reserved` | Same C++ replacement loop with initial `reserve(subject.size())`; same native comparison |
| `php` | `str_replace` / `strcmp`; CLI with `-n`, no Xdebug, OPcache or JIT |
| `rust` | `str::replace` / `str::cmp`; optimized native code |
| `python` | `str.replace`; `(s > t) - (s < t)` |
| `python_bytes` | Same Python expressions on UTF-8 `bytes` |
| `js` | `replaceAll`; `(s > t) - (s < t)` |
| `js_flat` | JS replacement followed by `Buffer.from(result, 'utf8')`; includes encoding and allocation |
| `java` | `String.replace(CharSequence,CharSequence)` / `String.compareTo` |

C++ has no direct `std::string` replace-all member. Its baseline intentionally
uses the same search/append algorithm as our runtime, separating wrapper cost
from algorithm choice. The reserved-output variant is a diagnostic, not a runtime
patch. Python and JS execute two relational comparisons to produce the sign;
their rows are not single-call `strcmp` equivalents.

The length control includes API/binding costs. Before snapshot elision, emitted
PHS copies its string argument even for `strlen`; eligible calls now borrow it.
This control is not an empty loop and is not subtracted from other results.

## Timing and correctness

Input loading/decoding, fixture construction, output correctness checks, compilation,
process startup and printing are outside timed loops. Before timing, replacement
results must exactly equal the common expected fixture; comparison signs must agree.
Every timed batch returns an accumulated length/sign checksum checked by the driver.
Lengths use each representation's native units, independently checked against the
metadata. The normal JS row permits the engine's ordinary string representation;
the materialization row shows a different, explicitly more demanding consumer.

Each case calibrates a power-of-two iteration count to at least 20 ms, then records
seven batches. Calibration warms all targets; Java also receives 500 ms of per-case
warmup with synchronous JIT compilation (`-Xbatch`). A second process round provides seven further samples. All benchmark
processes run sequentially on one CPU selected from the allowed affinity mask.
There is no cross-language concurrent load from this harness. Background host/WSL
load and allocator/GC effects remain possible; raw samples and min/max are retained.

C++ uses compiler memory barriers. Emitted PHS now calls the benchmark-only
`bench_fence()` memory barrier inside every timed loop, registered by `publish.php`.
Without it, eliminating copies allowed Clang to hoist comparisons and length
reads. The snapshot-elision comparison rebuilds both compiler revisions with
this same instrumentation; do not compare it directly to older unguarded PHS
measurements. Rust uses `black_box`; Java reads its subject
through a volatile reference and publishes replacement output. Node/V8 uses an
unoptimized `opaque` function marked with `%NeverOptimizeFunction` and launched
with `--allow-natives-syntax`. This prevents loop-invariant comparisons from being
hoisted. Its overhead is visible in the length control. This flag is benchmark
instrumentation, not an application requirement. An earlier unguarded JS run was
rejected after implausible sub-nanosecond long-string comparisons; it contributes
no samples to the reported results.

Native C++ and emitted programs use Clang 18 `-O3`, no PCH and no LTO. Rust uses
`rustc -O`. Java uses a bounded 128–512 MiB heap. Default inlining opportunities
differ between concrete middleware, ordinary emission and direct C++ calls;
that difference is part of the diagnostic comparison.

`ALLOCATION_PROBE` builds a **separate executable** that counts C++ `operator new`
requests around one operation after setup. Allocation counts and requested bytes
are not peak memory, copied bytes, or timing measurements. Timed executables do
not contain that instrumentation.

## Reproduce

Requires Linux, PHP, Clang/C++20, Rust, Python 3, Node/V8, a JDK and `taskset`.
The report renderer additionally uses matplotlib. From the repository root:

```bash
python3 specs/planning/benchmarks/string_defaults/run.py --work /tmp/string-bench
mkdir -p /tmp/string-bench/rounds/round1
cp /tmp/string-bench/run_*.stdout /tmp/string-bench/rounds/round1/
python3 specs/planning/benchmarks/string_defaults/run.py --work /tmp/string-bench --skip-build
python3 specs/planning/benchmarks/string_defaults/report.py /tmp/string-bench
```

To compare two retained result directories with identical corpus and timed source:

```bash
python3 specs/planning/benchmarks/string_defaults/compare_runs.py /path/to/before /path/to/after
```

The driver also records runtime source hashes and the runtime patch against HEAD,
so measurements of uncommitted runtime changes remain identifiable.

Optional `--rustc /path/to/rustc` and `--java-home /path/to/jdk` select toolchains.
`--only` selects backend labels for focused reruns. Do not archive and then retain
the same samples as a fresh round without rerunning them. Keep archived rounds
only when source/corpus/toolchain semantics match. Use a fresh work directory after
changing PHS source; `--skip-build` deliberately reuses generated executables.

The measured session extracted Ubuntu Rust/JDK packages under
`/tmp/scpp-string-bench-tools/root`; no system package installation was required.
The extracted Rust compiler needs its `usr/lib/x86_64-linux-gnu` directory in
`LD_LIBRARY_PATH`. Absolute `/etc` links in the extracted JDK were redirected to
its extracted `etc` tree. Version and source/executable hashes accompany results.

## Setup blockers kept separate from measured operations

The concrete path currently fails to compile an `explode` result used for corpus
parsing: it attempts to transport `vector_t<string_t>` as
`vector_t<concrete_string>`. Fixture setup instead loads individual files with
`fs_get`, outside timing. This is a transport limitation, not a string timing result.

The Ninja builder rejects concrete-layout publication and directs callers to
`Native_CPP_Build`. The harness uses the production `build_cpp.php` entrypoint for
both modes. Neither limitation was patched, and no generated output was edited.

## Paired compiler revisions on a stable host

`run.py --compiler-root /path/to/checkout` selects a compiler and its metadata
while retaining this harness. For a focused paired run, use:

```bash
python3 specs/planning/benchmarks/string_defaults/runtime_pair.py \
  --before /path/to/before --after /path/to/after \
  --results /tmp/string-pair --mbstring /path/to/php8.4/mbstring.so --rounds 3
```

This dev002 driver uses `/usr/bin/php` for publication and `/usr/bin/php8.4`
with the supplied extension for the production native builder. It builds both
lowering modes before timing, pins one CPU, alternates revision order, and
includes direct runtime/C++ controls. It retains samples, checksums, executable
hashes and command logs. Source uploads use isolated temporary checkouts.
