# O0/O1 shared-allocation outlining
Doc Status: planning

Date: 2026-10-16. Implemented in the isolated accounting checkout based on
`25405ed8`; the original compiler working tree is untouched. The experiment compares
against that constructor-outlining checkpoint, not against the earlier inline-default
baseline. Exact candidate source and executable identities accompany the evidence.

## Boundary and ownership

At O0/O1, the same 25 eligible accounting classes now have a stable, backend-named
no-argument shared-allocation function. Its declaration follows the type, and its
definition belongs to the type's existing source `.cpp`. It calls the original
`scpp::shared<T>()` allocator. Shared construction calls the helper; unique and value
construction retain their existing path. Eligibility excludes authored constructors,
bases, data-initialization contracts, abstract/polymorphic lifecycle and authored
destructors, and requires explicit constant/empty-container defaults with at least
one container. There is no argument forwarding or source-visible factory feature.

`CPP_Shared_Allocations` owns selection and spelling. The backend program retains
selected qualified names; rendering contexts receive that selection. If eligibility
changes, backend fragments are regenerated so untouched callers cannot retain stale
helper references. Stable selections preserve existing fragment reuse. Publication
still compares artifact content. This bounded backend invalidation does not add
semantic dependencies or modify compiler MT internals.

The latency profile advances to `cpp-lowering-v2:latency`; native consumers still
accept previous v1 latency publications at O0/O1. Execution identity and O2/O3
lowering remain unchanged. All 292 O2 accounting artifacts match the prior
checkpoint byte-for-byte. The sample remains 93 source-based C++ units, with no
unit splitting, new PCH composition or link-layout change.

## Measurement protocol

Separate dev002 workspace: `/home/alex-ai/accounting-opt-policy-20261016/allocation`.
Native Linux/Ryzen 9 5950X, Clang 18.1.3, Ninja 1.11.1, Mold 3.0.0 with 8 threads,
Ninja 8 jobs, compiler 12 jobs, runtime-only PCH, profiling disabled for the build
matrix. The separate runtime copy is unchanged. OS file caching is allowed.
Each invocation starts a fresh native compiler process; Ninja owns cross-run object
reuse. These are not retained semantic-incremental measurements.

Three paired trials at each of O0/O1 alternate old/new configuration ordering across
rounds. Each configuration runs full, unchanged, workflow body edit, revert and
revert-unchanged. O2 adds one preservation trial. The body edit is the same neutral
`20000` → `20000 + 0` change in `workflow_checks`. Source edits are restored with
Git. Full runs use dedicated empty build directories. PHP references are independently
emitted locally, then compared with native publication artifact hashes remotely.

Times below select each case's median-total trial. Memory is the maximum sampled
process-tree RSS across its trials (10 ms sampling), not PSS; shared pages may be
counted in multiple processes. Compile wall time is the union of overlapping Clang
intervals, not their sum. Exe readiness is successful link-driver exit. Residual
work is distributed across the invocation, not solely after linking.

## Results

All **65 accounting builds** pass with identical application output and **1,855
checks per build**. All 13 full configurations match their independent PHP artifact
references. Every full build compiles 93 generated objects, three runtime objects
and one PCH, then links. Every body edit/revert rebuilds one generated object and
reuses 95 objects plus the PCH. All 26 unchanged runs launch zero native actions.
The source fixture remains 528,088 bytes across 93 authored PHS files.

| Mode | Lowering | Full seconds | Full peak MiB | Body-edit seconds | Body peak MiB | Unchanged seconds |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| O0 | Constructor outlining only | 9.223 | 1539.5 | 2.131 | 287.1 | 1.074 |
| O0 | Plus allocation outlining | **9.006** | 1500.8 | **1.954** | 292.7 | 1.052 |
| O1 | Constructor outlining only | 17.710 | 1614.2 | 4.742 | 283.9 | 1.035 |
| O1 | Plus allocation outlining | **17.374** | 1616.3 | **4.414** | 276.0 | 1.021 |
| O2 | Unchanged execution spelling, one trial | 23.741 | 1672.7 | 8.699 | 390.6 | 1.054 |

The paired medians improve about 2.4%/1.9% for full O0/O1 builds, and 8.3%/6.9%
for the expensive body edit. Memory has no consistent improvement: small changes
in either direction are visible. This is a modest additional compile-time gain,
not another constructor-outlining-sized reduction.

| Outlined case | C++ ready | Active compile/PCH | Link | Other distributed work | Exe ready | Total |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| O0 full | 0.745 | 7.839 | 0.049 | 0.373 | 8.932 | 9.006 |
| O0 body edit | 0.777 | 0.782 | 0.048 | 0.347 | 1.896 | 1.954 |
| O1 full | 0.771 | 16.210 | 0.040 | 0.353 | 17.315 | 17.374 |
| O1 body edit | 0.756 | 3.275 | 0.045 | 0.337 | 4.380 | 4.414 |

The nearest-rank p80 across 279 generated-object full-build compilations per
configuration moves 0.676 → 0.669 s at O0 and 1.190 → 1.185 s at O1. Maxima move
1.057 → 1.030 s and 3.711 → 3.397 s respectively. The sample already met the
p80 ≤3 s target; the slowest O1 unit remains above three seconds.

## Isolated Clang traces

Twelve serial diagnostic compilations run after the timed matrix, with matching
existing PCHs and `-ftime-trace`. These are single observations, not trial medians.
The allocation owner is `warehouse_desk`.

| Mode/unit | Frontend seconds, previous → outlined | LLVM backend seconds, previous → outlined | Driver wall seconds, previous → outlined |
| --- | ---: | ---: | ---: |
| O0 workflow_checks | 0.669 → 0.561 | 0.299 → 0.233 | 0.99 → 0.82 |
| O0 closing_checks | 0.532 → 0.459 | 0.191 → 0.148 | 0.75 → 0.63 |
| O0 warehouse_desk | 0.776 → 0.784 | 0.207 → 0.212 | 1.01 → 1.02 |
| O1 workflow_checks | 0.679 → 0.577 | 2.976 → 2.761 | 3.68 → 3.37 |
| O1 closing_checks | 0.544 → 0.469 | 1.647 → 1.504 | 2.22 → 2.01 |
| O1 warehouse_desk | 0.783 → 0.792 | 1.135 → 1.110 | 1.95 → 1.93 |

Caller frontend and backend work both decrease. The observed owner cost is nearly
unchanged; do not interpret its small O1 decrease as a guaranteed improvement.
Nested trace events overlap and must not be added as independent costs. Per-process
RSS and raw traces are included in the evidence; these differ from the matrix's
sampled aggregate process-tree memory.

## Runtime and cache witnesses

The allocation/use/destruction probe performs 200,000 iterations with two Bag
objects (four hash fields each), an Item and a shared alias. It verifies independent
containers, alias mutation, a sum of 1,400,000 and exactly 200,000 Item destructions.
Six configurations each pass one warm-up and seven timed runs; ordering alternates
between rounds. All **42 timed runs** pass.

| Mode | Previous median | Outlined median | Peak RSS, previous → outlined |
| --- | ---: | ---: | ---: |
| O0 | 0.69818 s | 0.70031 s | 4.00 → 4.00 MiB |
| O1 | 0.05607 s | 0.05563 s | 3.75 → 3.75 MiB |

O0's median is about 0.3% higher, with overlapping ranges (0.692–0.702 versus
0.695–0.714 s). O1's short runs also overlap. This witness shows no clear runtime
regression, but does not establish a general execution-performance bound. O2/O3
witnesses also pass using the unchanged execution lowering.

A same-directory upgrade from the previous compiler's v1 latency output to the new
v2 output changes 51 artifacts and rebuilds 58 generated objects, reusing 38 objects
and the PCH, then links once. The next identical invocation launches zero native
actions. Both upgraded applications pass the accounting checks. A separate native
check rejects v2 latency publication at O2 after emission and before any Clang/link
action. This is a one-time
lowering-version transition; normal body edits still rebuild only one object.

## Proof boundary and corrections

The focused PHP-hosted proof compiles and executes O0/O1/O2/O3 applications, checks
six retained policy switches, independent containers/aliases, original unique
allocation, excluded classes, and a default edit confined to the owning `.cpp`.
It also adds/removes an authored constructor without editing the caller, checking
helper removal/restoration and byte equality against a fresh backend emission.

Native conversion initially rejected a computed map key inside `isset`; binding the
qualified name to a local fixes the authored portability spelling. The first native
compile then found a record-specific call through the general declaration type;
an explicit checked record cast fixes it. The corrected compiler was rebuilt at
O2 using the existing legacy S2S converter/framework with STAN bypassed, following
the compiler's local policy. Generated C++ was regenerated from PHP, not patched.
There were two native build attempts, the second successful. PHP behavior passed
before native stabilization; final native/PHP parity checks cover the corrected source.

Two benchmark setup attempts are excluded: a transferred wrapper lost its executable
bit, then a full-build retry correctly rejected that now-nonempty build directory.
The accepted matrix uses an executable wrapper and fresh dedicated directories.

These are bounded construction, output/cache and runtime witnesses, not a full
language regression or certification of incremental/MT internals. Broader allocation
eligibility, constructor arguments, runtime-header reorganization and automatic unit
splitting remain separate work.

## Evidence

[Evidence directory](allocation_outlining_20261016/) contains comparison tables,
source/executable identities, focused test logs, reproducible harness scripts and
`evidence.tar.gz` with raw remote results, memory samples, traces and source snapshot.
All 214 authored core PHP files match the native snapshot; the additional benchmark
driver has its own hash. All 93 accounting source hashes match the preserved baseline,
and the accounting Git tree is clean after the matrix. Compiler bootstrap timings
and diagnostics, including the failed first build, are retained separately from
application performance results.

To reproduce, preserve the previous checkpoint under the sibling `bench` directory
and use the candidate under `allocation`, sharing only the fixed sibling `repo`
runtime/tooling copy. The preparation script uses the prior local
`optimization-policy-20261016` bootstrap as its input. Run matrix, runtime witness,
cache upgrade and profiles sequentially, then collect. No system toolchain install,
runtime modification or benchmark overlap is required.
