# Long-lived incremental versus fresh compilation (slice 5)

Doc Status: supporting

The test-only `tests/retention/equivalence.py` driver keeps one PHP compiler process
alive for exactly 100 invocations per flavor. It compares selected checkpoints with
new compiler instances in fresh PHP processes using the same source paths and separate
output directories. No compiler models, cleanup policy or production behavior changed.

```sh
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/retention/equivalence.py --output /tmp/scpp-equivalence-proof
```

The output directory must not exist. Omit `--output` for a temporary proof directory
removed after completion. Requires the existing prebuilt runtime, PHP, Clang, Ninja and
mold. CTest registers `compiler_incremental_equivalence` with a 120-second limit.
`PHP_EXECUTABLE` selects PHP; the driver disables Xdebug. Native settings are O2/g0,
Ninja8, mold, no PCH/LTO and Clang time tracing.

## Fixed sequence and checkpoints

Five identical 20-invocation cycles exercise baseline/no-op, function implementation,
constant value, global type/initializer, function return type, provider deletion and
missing retry, re-addition, duplicate creation/replacement/removal, parse failure/retry,
body preparation failure/retry, recovery, consumer replacement and watch restoration.
Each cycle ends with the same baseline and a no-op. Written source changes receive
advancing mtimes; unchanged source files are not rewritten.

Fresh compilation is checked at every step of cycle one and at the end of each later
cycle: 24 checkpoints per flavor. Native compilation/execution is checked in cycle one
at baseline, changed contracts, semantic recovery and final no-op, then each later cycle
end: eight checkpoint pairs per flavor. Ninja retains incremental objects; fresh native
checkpoints use empty object directories. Builds happen only at those checkpoints,
so a native job may include changes since an earlier checkpoint, not just the last edit.

## Compared results

- Success/failure and exact ordered diagnostic paths, byte offsets and messages.
- Live symbol keys and duplicate-chain declarations, including kind, name, source
  location and authored syntax. Tombstones and object identities are deliberately excluded.
- Every generated C++/header path and byte, plus source/translation-unit/header inventories.
- Native stdout and exit status for both builds, also checked against independent
  expectations (`888` or `901`, exit 8).

Rejected frontend input has no published state in a fresh compiler. The incremental
compiler correctly retains its prior published state, so that boundary compares only
failure/diagnostics; it must not require those different retained states to match.
Failed compilations must leave published C++ unchanged. Successful no-ops prepare/emit
nothing. Prepared/reused counters are reported, not compared with fresh counters.

## Retention and isolation

The PHP adapter uses the existing read-only `Retention_Inspection`. Every resident run
checks one current session, zero retired roots, no stale watches or pending notifications,
consistent chains and watch indexes, and only current weak-observed lifetime families.
Successful runs additionally require empty preparation/emission queues. Whole-project
release must leave zero observed survivors. Exceptions are scalarized and discarded
before inspection; the adapter does not retain old sessions or emission handles.

PHP used-memory samples are captured after test-only cycle collection and before graph
inspection. They include PHP/harness overhead, not just compiler data, and are not RSS.
Object reachability is the boundedness proof. Production GC behavior is unchanged.
Fresh checkpoints run in separate processes so their objects/caches cannot contaminate
the resident process's retention measurements.

## Evidence and issues (2026-11-05)

Final proof: `v0.2/build/retention-slice5-final/report.md` and `results.json`, with per-flavor
raw results, generated artifacts, native logs and Clang traces retained beside them.

- 300 incremental invocations: 180 successes and 120 expected diagnostic failures.
- 72 fresh checkpoints: 48 successes and 24 expected failures; every comparison passed.
- 48 native build/execution checks passed.
- Final state per flavor: one session, four current AST/token roots, zero retired roots.
  Duplicate phases temporarily contain five current files/roots. No unbounded object growth.
- Used PHP memory at all five cycle ends was identical within each flavor: 2,378,960 bytes
  (cpp), 2,380,272 (php), 2,385,760 (js). These are observations, not fixed assertions.
- Total measured PHP compilation time for 100 invocations: 141.99 ms cpp, 142.41 ms php,
  137.60 ms js. Successful-run body totals per flavor: 69 prepared, 171 reused.

No compiler mismatch was found. The initial fixture attempted to echo bool directly,
which is outside the existing echo slice. The test was corrected to use typed int locals
and the existing conversions, matching the earlier native fixture. Compiler semantics
were not widened. This is a bounded deterministic workload, not exhaustive equivalence
for every supported feature or a concurrency proof.

The generated report includes native average/min/max and cumulative frontend/LLVM/link
details. It does not present a speedup claim: the program is small, linking and host noise
matter, fresh compiler caches start cold, and native builds occur only at checkpoints.
No cleanup or compiler implementation changes were necessary in this slice.
