# Native performance evidence
Doc Status: planning

See the [performance report](../../compiler_native_performance_2026_10_25.md) and
[preparation concurrency proposal](../../compiler_preparation_concurrency_audit_2026_10_25.md).
No production compiler/runtime changes. Source revision fd3dd1816; implementation
matches b3ef5d2f0. Scripts retain exact historical paths and are recipes, not portable
CLI interfaces. Local and dev002 results are distinct hardware measurements.

- Local O2 build/self time, exact output comparison, GDB stage events and raw heap
  allocation-volume summary are at this directory's top level.
- `dev002/results-03/`: uncontended-with-own-build 1/32-worker comparison, thread
  samples, normalization check, host publication/native build time and machine facts.
- `dev002/clang-profile/summary.json`: every timed action and Clang time-trace totals.
- `dev002/clang-parallel-02/`: replay summary, every action's resource measurements,
  commands and corrected link proof. Objects and executable match the serial build.
- Raw Clang traces/per-action logs remain under
  `/home/alex-ai/compiler-self-o2-20261025/` on dev002. The retrieved full timing archive
  is `/tmp/compiler-o2-dev002-results.tar.gz` locally.
- Complete raw heap recording remains at
  `/tmp/compiler-native-o2-heap-raw-01/heap.raw.zst` (about 1.5 GiB compressed).
  `raw_profile_volume.cpp` parses raw v3 records and sums requested bytes; symbol
  resolution uses the measured executable's symbol table. The summary filters the
  top 30 stacks by requests >=64KiB; overall bytes/events count all allocation records.

Setup/diagnostic failures are not compiler correctness failures: direct GDB initially
needed ptrace permission; interpreted heaptrack failed with bad_alloc and was replaced
by a completed raw recording; dev002's PHP8.4 lacked mbstring, so installed PHP8.5 was
used; direct-driver host publication avoided absent project-adapter files in the
isolated snapshot; parallel replay initially included layout probes and its first
link mishandled -Xlinker forwarding. Those harness issues were corrected and outcomes
are explicitly separated in the report. No template checks or runtime contracts were
removed to obtain successful results.
