# Safe string snapshot elision — 2026-10-21

Doc Status: supporting

my-try now avoids redundant string argument snapshots for reviewed runtime
providers when shared preparation proves the whole argument list harmless.
Uncertain calls retain the existing ordered snapshots. No source-language
borrowing restrictions or UTF-8 defaults changed.

## Implementation and proof

The provider metadata contract `snapshot_borrow_safe` is enabled only for
`strlen`, `str_replace` and the three `substr_compare` arities. It promises
synchronous readonly use, no retained input references, no mutation through
aliases or callbacks, and no observable input-storage identity dependency.
Preparation checks exact conversions and stable variable/literal arguments in
two linear scans. It records an optional borrowing hint while preserving the
semantic value boundary. Both C++ lowering modes consume that hint.

The focused suite passed all nine tasks: prepared-fact checks, behavioral output
in both modes, four exception/evaluation-order cases, and two allocation checks.
It covers 18 eligibility/fallback cases per mode, seven invalid metadata shapes,
and retained edits from safe to unsafe calls and back. Alias mutation, replacement
argument order, nullable extraction, temporary owners, and existing `const&`
behavior are included. At `-O0`, eligible comparison plus length calls on a 64-KiB
string allocate **zero** times; an uncertain nested-call case still allocates.
See [proof summary](proof/summary.json) and [command logs](proof/logs/commands.json).

The broader string regression also passed four PHP tasks and 33 native cases.
An old assertion that core contains no constants failed on the unchanged baseline
as well; it now checks exactly the selected modules' declared constants.
See [regression commands](proof/regression-02/logs/commands.json).
These runs compile emitted application C++; the compiler itself was not rebuilt
natively and this is not a native-portability claim.

## Compilation overhead on dev002

Isolated before/after source copies, Ryzen 9 5950X, CPU 0, PHP 8.2.34 CLI,
OPcache CLI disabled, one excluded warmup and 15 alternating measured pairs per
workload/mode. Timings below include compiler boot, frontend, preparation and
C++ emission; they exclude C++ compilation/linking and artifact publication.

| Workload | Lowering | Before ms | After ms | Change |
| --- | --- | ---: | ---: | ---: |
| simple_1000 | ordinary | 295.60 | 301.06 | +1.85% |
| simple_1000 | concrete | 483.88 | 490.26 | +1.32% |
| fallback_1000 | ordinary | 333.00 | 338.92 | +1.78% |
| fallback_1000 | concrete | 534.68 | 538.27 | +0.67% |
| string_benchmark | ordinary | 61.55 | 61.68 | +0.21% |
| string_benchmark | concrete | 71.13 | 71.79 | +0.94% |

The deliberately call-heavy eligible workload adds about 6–7 ms in preparation
(+5.3–5.9% for that stage), but only 1.3–1.9% to total compiler time. The real
string benchmark adds 0.2–0.9% overall. These are measured workload results,
not a guarantee for every program. Peak memory allocation buckets were unchanged.
[Raw samples](compile_cost/samples.json), [stage medians](compile_cost/summary.json),
[machine](compile_cost/machine.json), [source manifest](compile_cost/source_manifest.json)
and exact replayable workloads are retained.

## Cross-language rerun and measurement controls

The local full rerun includes native C++, PHP, Rust, Python, JavaScript and Java,
plus both my-try lowering modes and diagnostic variants: 42 ASCII/UTF-8 cases,
14 batches each, **7,644 validated batches**. The my-try before run adds 1,176.
[Tables](after/tables.md), [samples](after/summary.json),
[validation](after/validation.json) and [per-round medians](after/round_medians.json)
are retained. Local host variation was substantial, including unchanged controls:
these tables are descriptive, not evidence for small performance differences.
The dev002 paired measurements below are the basis for the optimization's impact.

After removing copies, Clang could hoist invariant comparisons/length calls.
The initial unguarded attempt was rejected. The retained before **and** after
programs use the same benchmark-only compiler memory fence in each timed loop.
Historical unguarded PHS timings in the earlier reports are not directly comparable.
The fence has no hardware synchronization cost but constrains compiler optimization.

Both revisions use the preceding bulk-byte-comparison runtime improvement;
this report isolates argument snapshot elision. The base compiler is repository
HEAD `04a23a22fb5ea7dca06243db6651abc65f950ef1`; source manifests and runtime/compiler
patches identify the measured overlays. The temporary dev002 source upload was
explicitly approved and did not alter an existing checkout.

Reproduction: [benchmark harness](../../benchmarks/string_defaults/README.md),
[compiler test and overhead commands](../../../../compiler/my-try/docs/testing.md).

## Paired runtime results on dev002

Both revisions and lowering modes were rebuilt through the production publisher
and native builder at `-O3`, without PCH or LTO. Three sequential rounds alternate
before/after order on CPU 0. Each case contributes 21 measured batches; all
**5,292 batch checksums passed** across my-try and direct runtime/C++ controls.

Median microseconds per operation, nominal 64-KiB inputs:

| Lowering | Input | Operation | Before µs | After µs | Speedup |
| --- | --- | --- | ---: | ---: | ---: |
| concrete | ascii | compare_early | 16.1105 | 0.0323 | 498.16× |
| concrete | ascii | compare_late | 16.8563 | 0.9086 | 18.55× |
| concrete | ascii | replace_absent | 2.3965 | 1.4528 | 1.65× |
| concrete | ascii | replace_dense | 78.5692 | 78.8148 | 1.00× |
| concrete | ascii | replace_grow | 231.1859 | 214.4607 | 1.08× |
| concrete | utf8 | compare_early | 16.0134 | 0.0324 | 494.95× |
| concrete | utf8 | compare_late | 16.8651 | 0.9077 | 18.58× |
| concrete | utf8 | replace_absent | 2.4000 | 1.4595 | 1.64× |
| concrete | utf8 | replace_dense | 70.7074 | 69.8302 | 1.01× |
| concrete | utf8 | replace_grow | 166.9495 | 166.6272 | 1.00× |
| ordinary | ascii | compare_early | 1.8633 | 0.0026 | 729.58× |
| ordinary | ascii | compare_late | 2.7551 | 0.8856 | 3.11× |
| ordinary | ascii | replace_absent | 16.7102 | 1.4343 | 11.65× |
| ordinary | ascii | replace_dense | 77.5855 | 75.6190 | 1.03× |
| ordinary | ascii | replace_grow | 257.7204 | 229.8532 | 1.12× |
| ordinary | utf8 | compare_early | 1.8789 | 0.0026 | 735.81× |
| ordinary | utf8 | compare_late | 2.7537 | 0.9061 | 3.04× |
| ordinary | utf8 | replace_absent | 16.8853 | 1.4390 | 11.73× |
| ordinary | utf8 | replace_dense | 68.8311 | 67.4581 | 1.02× |
| ordinary | utf8 | replace_grow | 241.6047 | 217.6148 | 1.11× |

Comparison now approaches direct runtime/native C++ long-prefix cost (about
0.9 µs here). Default concrete lowering retains more overhead on short-prefix
comparisons than ordinary lowering. Dense replacement changes little: its search
and output work dominate after input-copy removal. Growing replacement shows
smaller, workload-dependent gains. No-match replacement benefits clearly.

The before timings differ substantially by host and lowering, so speedup ratios
are not universal constants. The default late-mismatch comparison's three round
medians were 16.99/16.53/16.86 µs before and 0.909/0.916/0.901 µs after. Default
dense ASCII replacement was 78.57/78.28/79.73 µs before and
78.81/79.66/77.64 µs after: the apparent local regression did not reproduce.
Some variation remains (including ordinary no-match replacement in round 3);
raw ranges and per-round medians are retained.

The ordinary length-control loop reaches about 0.2 ns per iteration because
optimized length access is trivial; do not interpret it as a general standalone
call latency or subtract it from the other measurements. Allocation tests at
`-O0` independently prove snapshot removal, without relying on timing.

[All medians and samples](runtime_pair/summary.json),
[per-round medians](runtime_pair/round_medians.json),
[environment and hashes](runtime_pair/environment.json),
[commands](runtime_pair/logs/commands.json) and
[Clang version](runtime_pair/clang-version.txt) are retained.
The native builder used PHP 8.4 with a matching-ABI mbstring package extracted
under the isolated temporary directory; no system package was installed.
