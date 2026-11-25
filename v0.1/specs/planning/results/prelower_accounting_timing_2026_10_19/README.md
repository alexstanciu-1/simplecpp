# Accounting: prepared versus ordinary native compilation
Doc Status: planning

Date: 2026-10-19. Revision `4c603a92`. Same accounting sources in both modes;
[source hashes](source_hashes.json). [Numeric results](summary.json).

## Application compilation with reusable artifacts available

Seconds, median of three compilation passes: the application portion of the initial
build plus two forced application recompilations. Each pass compiles all 16 app units
serially and links against the matching runtime and, where applicable, middle-library
objects. The two recompilations retain PCH/layout/support artifacts. They link the
newly produced application objects, not previously cached application objects.

| Native work | Ordinary O0 | Prepared O0 | Ordinary O2 | Prepared O2 |
|---|---:|---:|---:|---:|
| Application compilation | 22.20 | 10.03 | 51.05 | 26.62 |
| Application compilation + linking | 22.73 | 10.51 | 51.24 | 26.82 |
| Observed compile + link range | 22.60–23.01 | 10.29–11.19 | 51.14–51.91 | 26.79–27.02 |

Application compilation plus linking is **2.16× faster at O0** (54% less time)
and **1.91× faster at O2** (48% less time). This measures full application recompilation
with reusable artifacts available, not a one-file edit or an unchanged-cache build.

## Preparation and whole native build costs

Single measurements, seconds. Fresh native caches do not imply cold OS file caches.

| Native work | Ordinary O0 | Prepared O0 | Ordinary O2 | Prepared O2 |
|---|---:|---:|---:|---:|
| First complete native build, wall time | 48.42 | 49.51 | 88.21 | 82.10 |
| Runtime PCH creation | 3.16 | 3.21 | 3.23 | 3.68 |
| Runtime provider object compilation | 13.71 | 13.64 | 24.51 | 22.80 |
| Concrete layout probe compilation + linking | 0 | 5.99 | 0 | 6.19 |
| Middle-library object compilation | 0 | 5.25 | 0 | 11.61 |
| Unchanged-cache build, wall time | 6.71 | 8.31 | 6.68 | 8.28 |

First native build includes dependency discovery, input validation, runtime/PCH,
layout probes, application and middle-library objects, and final linking. Its total
also includes orchestration, file hashing and compiler-proxy startup. Probe execution
is included in wall time but not in the probe compile/link row. Listed phase rows
are selected components, not a sum intended to equal total wall time.

Unchanged-cache builds compile and link nothing; their time is validation/dependency
work. Prepared output currently has more artifacts to validate. First-build performance
is approximately equal at O0 and modestly better in this O2 run; single measurements
do not establish the significance of those smaller differences.

## Method

- Ubuntu Clang 18.1.3, AMD Ryzen 5 PRO 4650G, WSL2 x86_64 Linux.
- C++23, `-O0` or `-O2`, no native debug information, no LTO, serial compilation.
- **Runtime PCH enabled in both modes**, including existing `-fpch-instantiate-templates`.
- Ordinary mode disables `prelower_templates` and `concrete_type_substitution`;
  prepared mode enables both. No source workarounds or runtime changes.
- Existing `Native_CPP_Build` performs initial/warm builds. The existing test compiler
  proxy records Clang invocation wall times. Application replay uses the same flags,
  PCH, layout includes and runtime/middle objects with new application object outputs.
  The replay uses the native builder's clean environment. Runtime/support work is
  excluded from application-only rows and included in first native build totals.
- Cold ordering: ordinary/prepared O0, prepared/ordinary O2. Recompilation ordering
  reverses between rounds. No overlapping benchmark tasks; the shared 12-slot runner
  runs one sequential benchmark task. Other system load is not controlled.
- Every reported executable passes `CHECKS passed=998 failed=0` and
  `ACCOUNTING_WORKLOAD_PASS`. No native compiler rebuild is involved.
- PHP frontend/preparation/emission time is outside these native measurements.
  This does not measure incremental compiler lowering overhead.

Local reproducibility inputs and raw logs are under `/tmp/accounting-timing/`:
`publish.php`, `build.php`, `timed_clang.py`, `run.py`, `replay.py`, `results.json`,
`timed_clang.jsonl`, `logs/`, and `replay-logs/`. Publication uses the existing timed
publication fixture with the two lowering options; build uses the existing native
cache driver with an optimization argument. The initial replay harness counted
layout-probe objects as final executable inputs and stopped at its assertion. This
was a harness issue, not a Clang failure. The corrected replay excludes temporary
probe objects, preserves all successful cold measurements, and performs the two
reported recompilation rounds. The earlier extra ordinary replay sample is excluded.

## Interpretation and limits

The current implementation moves substantial native compilation work into reusable
artifacts. The full application shows roughly a 2× improvement with PCH already
enabled, not the earlier small-experiment 10× ratio. This is not yet full template
elimination: native source-class shared wrappers and template helper/header exposure
remain, as documented in the [coverage checkpoint](../prelower_source_storage_2026_10_19/README.md).
These results do not identify individual Clang frontend/backend costs or predict
parallel Ninja builds, ThinLTO builds, runtime speed, or other applications.
