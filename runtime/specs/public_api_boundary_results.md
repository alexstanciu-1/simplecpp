# Restricted runtime public API: first-slice results
Doc Status: supporting

Date: 2026-10-27. Implementation: `2891b2a3e`; approved decisions: `280bdd611`.
See [the boundary and next-dependency inventory](public_api_inventory.md).

## Completed scope

The runtime owns `public/` and exposes narrow `scpp_core_api` / `scpp_tasks_api`
CMake targets. Integers and worker-pool declarations live there; compatibility
headers forward to their definitions. Private include roots are link-only behind
the new targets. Compiler publications consume the selected runtime's public root
directly, rather than copying its integer header into middleware.

Disabled public configuration links core only. The task-owned adapter supplies its
existing diagnostic arguments to general compiled error machinery. Core contains
no task-disabled policy. The legacy template API retains its header-only fallback.
Typed public exception inspection, string/error/textbuilder representation changes
and general specialization-inventory generation are subsequent slices.

## Tests

- Seven focused runtime checks passed in static, shared, ASan/UBSan with leak
  detection, and ThreadSanitizer builds.
- Actual CMake public consumer TUs reject implementation headers with `__has_include`
  guards. Every public header independently preprocesses without templates or
  private runtime headers in both task modes; explicit private-header probes fail.
- Enabled task workload proves four-worker concurrency, worker reuse, ordered
  results, exception propagation/recovery and old/new API interoperability.
- Disabled tests verify exact message/code/component/operation. The new path links
  only core; the compatibility path links no runtime library. A separate inspector
  TU keeps the unchanged private exception type out of the public caller.
- General failure tests verify that thrown diagnostics own copies of borrowed input
  and handle null fields as documented. Integer layout/behavior tests still pass.
- Compiler normal suites: 13/13 on WSL and dev002. WSL ASan/UBSan: 13/13; TSan:
  12/12, with the existing allocation-hook test excluded. Sanitizer publications
  leave normal dependency packages unchanged.
- Publication fixture verifies reuse, failure preservation, recovery and runtime
  mismatch rejection. Middleware no longer copies runtime headers.
- Dependency isolation passed, including changes to the live public integer header.
  Normal compiler builds leave pinned runtime/middleware archives unchanged.
- Relocation passed 13/13 compiler tests plus independent enabled and disabled clients
  using the published runtime API targets. Neither dependency was rebuilt.

## Compiler rebuild costs

Clang 18.1.3, C++23/O2/NDEBUG, Ninja 8 jobs, Mold, no PCH or LTO. Three trials per
case. Only compiler objects and the selected executable are rebuilt; tests are
excluded and dependency archives are fixed and fingerprinted. src/ has ten units;
src/ + support/ has seventeen. No competing build was started by this task during
measurement on the same host.

| Host/checkpoint | src/ runs (s) | Median | src/ + support/ runs (s) | Median | No-op |
| --- | --- | ---: | --- | ---: | ---: |
| WSL before | 0.8077, 0.7366, 0.777 | 0.777 s | 6.208, 6.3073, 6.3938 | 6.3073 s | 0.0099 s |
| WSL after | 0.8026, 0.9987, 0.9012 | 0.9012 s | 8.9045, 7.6578, 7.5453 | 7.6578 s | 0.0105 s |
| dev002 after | 0.3046, 0.3194, 0.2998 | 0.3046 s | 2.6054, 2.612, 2.6148 | 2.612 s | 0.0055 s |

The WSL after runs were slower than this slice's before runs; these measurements do
not establish a whole-compiler build speedup or isolate the cause of that difference.
There is no matched dev002 before measurement for this slice. Heavy support units
still consume legacy APIs; exposing a narrow root does not remove those paths.

Reproduce with `compiler/my-try-cpp/tools/measure_build.py --jobs 8 --trials 3
--output OUTPUT.json`. Evidence: `public-api-before.json`, `public-api-after.json`
and `public-api-dev002.json` under the local compiler build directory.

## Caller-only compile costs

WSL, Clang 18.1.3, C++23/O2, three alternating compile-only trials, after builds
completed. Focused callers receive only `runtime/public`; generic compatibility
callers receive `runtime/include`. These are equivalent configuration calls, not
whole compiler or runtime-implementation build timings.

| Mode | Header | Preprocessed bytes | Runs (s) | Median |
| --- | --- | ---: | --- | ---: |
| Disabled | Public worker pool | 5,848 | 0.0568, 0.0501, 0.0582 | 0.0568 s |
| Enabled | Public worker pool | 5,431 | 0.0518, 0.059, 0.0521 | 0.0521 s |
| Disabled | Generic tasks | 2,934,852 | 3.197, 2.9146, 2.9698 | 2.9698 s |
| Enabled | Generic tasks | 2,965,480 | 2.8706, 2.7444, 2.8272 | 2.8272 s |

The disabled public caller is now template-free: 5,848 preprocessed bytes instead
of the previous checkpoint's 1,258,737, with compile median 0.0568 s versus 1.1338 s.
The enabled caller remains small (0.0521 s versus the previous 0.0543 s). Historical
wall-time comparisons remain sensitive to host load. The structural reduction is
removal of exception implementation headers from disabled public callers.

Reproduce with `tests/runtime/native/measure_worker_pool_headers.py --output OUTPUT`.
Local evidence: `compiler/my-try-cpp/build/public-api-header-cost/results.json`.

## Inline string slice — 2026-10-27

The complete ordinary `scpp::string` API is implemented in runtime core. Other value
APIs remain approved follow-ups; compiler string facades have not migrated. No
compiler-wide speedup or source/support build improvement is claimed here.

The generated target probe reports 32 bytes/alignment 8 on WSL and dev002, with
nonthrowing native move construction, move assignment and destruction. The facade
has exactly the native footprint, containing a live native object rather than a
heap wrapper. These are target measurements, not cross-platform ABI constants.

Allocation-hook results at both O0 and O2 on WSL (O2 also verified on dev002):

| Operation | Allocations |
| --- | ---: |
| Empty/short construction, short copy, equality/vocabulary lookup | 0 |
| Long construction | 1 |
| Long copy | 1 |
| Long move | 0 |
| Long slice into the returned value | 1 |

Long fixtures are 88 bytes. The retained native character allocation remains;
there is no facade allocation. Inline object footprint grows from the compiler's
8-byte pointer facade to 32 bytes, eliminating that facade's separate native object
and allocator metadata once consumers migrate. This is not an RSS measurement.

Validation: 9 focused Release tests; 5 ASan/UBSan tests with leak detection;
3 shared-runtime tests; 3 dev002 tests. All passed. The isolated O0 build compiles
the string, integer and failure implementation owners plus a public-only caller.
Coverage includes independent copies/results, short/long strings, embedded NULs,
UTF-8 and invalid-UTF-8 fallback, clamped slices, bounds errors, self-copy/move/append,
capacity reuse, repeated lifecycle stress and failed construction/assignment/result
allocation cleanup. Public headers remain standalone and template-free in both task
modes. No task scheduling or shared mutable string behavior was introduced.

Compile timings (Clang 18, O2, no PCH/LTO; alternating caller trials, median of 3):

| Measurement | WSL | dev002 |
| --- | ---: | ---: |
| Public string caller | 0.0513 s | 0.0316 s |
| Equivalent native-header caller | 0.8161 s | 0.4577 s |
| New string adapter alone, one compilation | 3.3818 s | 1.8222 s |
| Full runtime-core recompile + archive, one run, Ninja 8 jobs | 15.2012 s | 8.0902 s |
| Runtime-core no-op build | 0.0262 s | 0.0170 s |

The public caller preprocesses to 7,364 bytes versus 1,491,642 for the native header.
The runtime still compiles core through its existing aggregate translation unit;
8 jobs cannot parallelize that single unit. Full-core times are current costs, not
matched before/after regressions. Tests are excluded from timed builds.

Reproduce caller/adapter measurements with
`python3 tests/runtime/native/measure_string_api.py --output <results.json>`.
Target-local artifacts are `compiler/my-try-cpp/build/string-api-cost.json` and
`string-core-build.json`; the latter measures removal/rebuild of only core's object,
followed by a no-op. Existing build warning about redundant hash specialization
instantiation is unchanged.
