# Tasks public C++ API boundary
Doc Status: supporting

## Worker-pool slice

`modules/tasks/worker_pool.hpp` exposes the existing configuration, configured-size
query and shutdown operations. Callers need neither `tasks.hpp` nor scheduler,
callback, batch-state or result-container definitions. The enabled implementation
remains compiled in `tasks.cpp`; the private reusable pool and its scheduling
behavior are unchanged. The existing `tasks.hpp` includes this focused header to
preserve existing callers. The CMake `scpp_tasks` target declares its core linkage
because the compiled task implementation uses core mixed/dynamic symbols.

This slice deliberately retains the existing `int_t<>` signatures. It does not
claim a template-free public boundary yet, or change generic `run`/`start` adapters.

### Disabled builds

An absent `SCPP_HAS_TASKS` macro still defaults to zero. Configuration preserves
its header-only throwing fallback, now in `detail/worker_pool_disabled.hpp`, so a
disabled caller does not need to link the tasks library. It preserves the message,
`tasks_module_disabled` code, `scpp::tasks` component and
`task_set_worker_pool_size` operation. As before, the size query and shutdown have
compiled definitions only; this change does not introduce disabled-mode success
stubs for those operations.

The disabled implementation header is textually included: the exception dependency
is not hidden from disabled callers. Moving it out of line would change their
linkage requirements and is outside this slice.

### Tests and measurement

Four focused CTest cases cover the enabled API and disabled default/explicit/umbrella
inclusion. The enabled caller TU includes only the public header. A separate full
API workload proves four-worker concurrency using a barrier, ordered results,
reuse without additional worker creation, failure propagation and subsequent reuse.
Configuration checks cover negative, zero and positive counts and repeated shutdown.
The disabled cases link without the tasks library and verify exact diagnostic fields.

Run the named targets `test_worker_pool_api`, `test_worker_pool_disabled_default`,
`test_worker_pool_disabled_explicit` and `test_worker_pool_disabled_umbrella` in a
configured runtime build, then `ctest --test-dir BUILD -R scpp_test_worker_pool`.

`tests/runtime/native/measure_worker_pool_headers.py --output BUILD/header-cost`
compares an identical configuration call through focused and generic headers in
both enabled and disabled modes. It alternates three compile trials per case,
records preprocessed bytes and asserts that focused dependencies exclude the
full task header and runtime mixed/dynamic/hash/vector headers. It uses Clang,
C++23 and O2, with no linking or PCH. Timing is caller compilation, not runtime
execution speed or a full compiler rebuild.

## Next experiment: concrete scalar API

User direction: prefer resolved/prelowered runtime types in public APIs unless a
template is necessary. The immediate obstacle is `int_t<>`, whose header alone
preprocesses to about 1.42 MB in the current Linux/Clang environment. The focused
worker-pool header is approximately 1.43 MB in enabled mode: scalar dependencies
now dominate this interface.

Evaluate a runtime-owned concrete integer surface with stable layout and checked
operations, exposing only needed declarations and compiling template adaptation
inside the runtime. Preserve scalar semantics, overload behavior and supported
conversions, and assess ABI/source compatibility before adopting it. A type alias
to `int_t<>` or `extern template` alone does not hide the required class definition.
Do not add another task callback abstraction or redesign scheduling for this work.

## Measured checkpoint — 2026-10-27

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
