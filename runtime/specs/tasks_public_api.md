# Tasks public C++ API boundary
Doc Status: supporting

## Worker-pool slice

`runtime/public/modules/tasks/worker_pool.hpp` exposes `scpp::tasks::worker_pool::configure(const
int64&)`, `size() -> int64` and `shutdown()`. The header uses the runtime-owned
[concrete integer API](core_public_api.md). Enabled callers need no templates,
scheduler, callback, batch-state or result-container definitions. The implementation
remains compiled in `tasks.cpp`; both interfaces share the same reusable pool.
The CMake `scpp_tasks` target publicly declares its core linkage so callers can
use the concrete integer operators with static or shared composition.

`tasks.hpp` includes this focused header and retains the existing
`configure_default_worker_pool(int_t<>)`, `default_worker_pool_size() -> int_t<>`
and `shutdown_default_worker_pool()` API. The focused API uses a nested namespace
because C++ cannot overload the size query by return type alone. The old focused
header's template signatures now require `tasks.hpp`; the legacy umbrella API
retains its signatures. This is a deliberate source migration for direct users of
the recent focused header, not a change to generated-language or generic task APIs.

### Disabled builds

An absent `SCPP_HAS_TASKS` macro defaults to zero. New public callers link runtime
core through `scpp_core_api`; they need no task library. The task-owned inline
configuration adapter calls the general compiled error primitive. It preserves
message, code, component and operation. Error storage/tracing stays private to core;
task-disabled policy stays in tasks. Both enabled and disabled public headers are
template-free. Size/shutdown still require enabled compiled definitions when called.

Legacy `tasks.hpp` callers retain the no-link disabled fallback. Compatibility
forwarding headers keep existing include paths; the concrete API definitions now
live only under `runtime/public/`. See [the inventory](public_api_inventory.md).

### Tests and measurement

Four focused CTest cases cover the enabled API and disabled default/explicit/umbrella
inclusion. The enabled caller TU includes only the public header. A separate full
API workload proves four-worker concurrency using a barrier, ordered results,
reuse without additional worker creation, failure propagation and subsequent reuse.
Configuration checks cover negative, zero and positive counts and repeated shutdown.
The public disabled cases link core only and verify exact diagnostic fields through
a separate compatibility inspector TU. The legacy case links no runtime library.

Run the named targets `test_worker_pool_api`, `test_worker_pool_disabled_default`,
`test_worker_pool_disabled_explicit` and `test_worker_pool_disabled_umbrella` in a
configured runtime build, then `ctest --test-dir BUILD -R scpp_test_worker_pool`.

`tests/runtime/native/measure_worker_pool_headers.py --output BUILD/header-cost`
compares equivalent four-worker configuration calls through focused and generic headers in
both enabled and disabled modes. It alternates three compile trials per case,
records preprocessed bytes and asserts that focused dependencies exclude the
full task header and runtime mixed/dynamic/hash/vector headers. It uses Clang,
C++23 and O2, with no linking or PCH. Timing is caller compilation, not runtime
execution speed or a full compiler rebuild.

## Previous int_t checkpoint — 2026-10-27

WSL, Ubuntu Clang 18.1.3, C++23/O2, three alternating compile-only trials per case,
warm filesystem caches. Each caller performs the same configuration call.

| Mode | Header | Preprocessed bytes | Compile runs (s) | Median (s) |
| --- | --- | ---: | --- | ---: |
| Enabled | worker_pool.hpp | 1,428,700 | 1.0453, 0.8769, 1.0332 | 1.0332 |
| Enabled | tasks.hpp | 2,960,152 | 4.2997, 3.1433, 3.5767 | 3.5767 |
| Disabled | worker_pool.hpp | 1,490,126 | 1.1964, 1.1918, 1.0942 | 1.1918 |
| Disabled | tasks.hpp | 2,929,138 | 3.5881, 3.6619, 3.5046 | 3.5881 |

The enabled focused caller compiled about 71% faster in this checkpoint; disabled
about 67% faster. This is not a generic task-adapter or whole-compiler speedup.
`int_t.hpp` alone expands to 1,423,918 bytes, explaining almost all of the remaining
enabled include footprint.

All four focused tests passed in normal, ASan/UBSan (with leak detection), and
ThreadSanitizer builds. The worker-pool implementation and generic execution
algorithms were not changed. Existing published compiler dependencies were not
refreshed: this checkpoint tests the runtime sources directly.

Local evidence is under `compiler/my-try-cpp/build/worker-pool-header-cost/` and
`worker-pool-final.log` in its runtime, sanitizers and thread-sanitizer build areas.

## Concrete integer checkpoint — 2026-10-27

WSL, Ubuntu Clang 18.1.3, C++23/O2, three alternating compile-only trials per
case after the builds completed. No runtime build ran alongside this measurement.

| Mode | Header | Preprocessed bytes | Compile runs (s) | Median (s) |
| --- | --- | ---: | --- | ---: |
| Enabled | worker_pool.hpp | 5,431 | 0.0538, 0.0669, 0.0543 | 0.0543 |
| Enabled | tasks.hpp | 2,965,480 | 3.5482, 3.0971, 2.8466 | 3.0971 |
| Disabled | worker_pool.hpp | 1,258,737 | 1.1338, 1.3232, 0.9219 | 1.1338 |
| Disabled | tasks.hpp | 2,934,583 | 3.3247, 3.2794, 2.9518 | 3.2794 |

The enabled focused header shrank from 1,428,700 to 5,431 preprocessed bytes.
Its median caller compile fell from the previous checkpoint's 1.0332 s to 0.0543 s
(about 95% lower). Historical wall-time comparisons remain sensitive to WSL load;
the current generic caller measured 3.0971 s. These are caller-only costs, not full
compiler builds. Disabled compilation remains about 1.13 s because preserving the
header-only exception fallback still exposes the existing exception implementation.
The integer header alone preprocesses to 5,272 bytes and contains no templates.

All six focused checks passed in normal, ASan/UBSan with leak detection, and
ThreadSanitizer configurations. They include scalar layout/copy/conversion checks,
arithmetic/comparison parity with the existing template operators, unsigned wrap,
negative signed division/remainder, exact zero-divisor diagnostics, and preservation
of the destination on failed compound operations. Signed overflow behavior is not
redefined or claimed to be checked. The four-worker barrier and legacy/new API
interoperability checks exercise the unchanged worker pool.

Reproduction: build the four worker-pool targets plus `test_concrete_integers`, then
run `ctest --test-dir BUILD -R 'scpp_test_(concrete_integers|worker_pool|integer_api_boundary)'`.
The boundary test also verifies generated-file freshness. Local timing evidence is
`compiler/my-try-cpp/build/concrete-integer-header-cost/results.json`.

For the allocation/call-cost smoke measurement, compile
`tests/runtime/native/measure_integer_arithmetic.cpp` with Clang C++23/O2,
`-Iruntime/include`, the built `libscpp_runtime_core.a`, and `-pthread`.
This standalone measurement intercepts `new/new[]`; do not combine those hooks
with ThreadSanitizer. It validates a 100,000-iteration public integer sum across
the compiled boundary, without LTO. It does not benchmark error-path allocations.
The measured WSL run took 325 microseconds, made zero `new/new[]` calls and produced
4,999,950,000. This is a smoke measurement of the out-of-line facade, not a claim
that it matches fully inlined template arithmetic performance.
