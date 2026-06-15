# Threading Pre-Implementation Audit
Doc Status: planning

Date: 2026-06-12

## Purpose

Capture the focused pre-implementation audit for the `tasks` runtime module and `task_*` strict PHP++ surface.

This note is planning only.
It does not change current language, runtime, generator, or build semantics by itself.

Related planning:

- [specs/planning/threading_batch_api_plan_2026_06_08.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/threading_batch_api_plan_2026_06_08.md)

## Summary

The first implementation should not start from raw runtime code alone.
It needs three supporting slices to line up first:

1. callable lowering and callable-boundary behavior
2. runtime module/build plumbing
3. builtin contract stubs for the `tasks` family

The recommended first implementation target remains a narrow fixed-batch path:

```text
task_run(collection, workers, exec) -> result collection
```

Then add background handles, status/progress, error callback, timeout, and later dynamic submission.

## Callable Lowering Audit

Relevant authorities and precedents:

- [specs/language/closures.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/language/closures.md)
- [specs/builtins/regex/first_pass.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/builtins/regex/first_pass.md)
- [specs/builtins/regex/preg_replace_callback.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/builtins/regex/preg_replace_callback.md)
- [runtime/include/modules/regex/regex.hpp](/home/alexv/__AI/simple_cpp/simple_cpp_02/runtime/include/modules/regex/regex.hpp)

Current closure model:

- closures are concrete compiler-generated values
- no implicit generic callable conversion occurs unless required by a callable boundary
- captures lower to native C++ lambda captures
- closures are not dynamic values and cannot be stored in `mixed_t` or untyped containers

Current callable builtin precedent:

- regex callback builtins use concrete callable signatures such as `function<string_t(vector_t<string_t>)>`
- runtime implementation uses `std::function<...>` for those callback parameters
- callback support already exists conceptually, but the `tasks` family needs broader generic callback behavior than regex

Risks for `tasks`:

- `task_run` wants generic `T -> R` behavior, not one fixed callback type
- optional context arity means `function (T $item): R` and `function (T $item, task_context $ctx): R` need either overloads, generated adaptation, or STAN/generator arity validation
- callback captures may be unsafe if they reference mutable state shared with the main thread
- worker-thread invocation needs exception/error capture across thread boundaries

Recommended first implementation constraint:

- support one callback shape first: `function (T $item): R`
- keep `task_context` callback arity as the next step if that meaning is not already easy to lower
- prefer typed `vector<T>` first, then typed `hash<T>`, then `mixed`
- defer capture-safety enforcement to STAN advisory work after the runtime slice exists

Open implementation question:

- whether the generator can emit a templated runtime call preserving closure type, or whether it must coerce to `std::function<R(T)>` at the call boundary

## Runtime Module Plumbing Audit

Relevant implementation anchors:

- [bin/project_services.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/bin/project_services.php)
- [generators/php/specs/php_runtime_symbols_strict.json](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/specs/php_runtime_symbols_strict.json)
- [specs/project_build_v1.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/project_build_v1.md)

Current module model:

- default runtime modules: `json`, `filesystem`, `datetime`
- optional shared modules: `mysqli`, `regex`, `curl`
- allowed modules are explicitly checked in `resolve_runtime_build_config(...)`
- runtime composition includes module `.cpp` files explicitly
- shared optional module composition is handled separately for `mysqli`, `regex`, and `curl`
- strict flat source names map through `generators/php/specs/php_runtime_symbols_strict.json`

Implementation touchpoints for `tasks`:

1. add `tasks` to the allowed runtime module list
2. decide whether `tasks` is default or opt-in
3. if opt-in shared module, add `tasks` to `shared_optional_runtime_modules()`
4. include `modules/tasks/tasks.cpp` in runtime composition when enabled
5. include `modules/tasks/tasks.cpp` in shared optional module composition if shared
6. add strict runtime-symbol mappings for `task_*`
7. add `runtime/include/scpp/tasks.hpp`
8. add `runtime/include/modules/tasks/tasks.hpp`
9. add `runtime/include/modules/tasks/tasks.cpp`
10. add build/module tests for unsupported/missing module behavior

Recommended first implementation policy:

- make `tasks` opt-in at first
- no external dependency probe is needed because the backend is C++ standard library threading
- `tasks` can be a shared optional module like `regex`/`curl`, but without package detection
- fail clearly when source calls `task_*` without the `tasks` module enabled

## Runtime Layout Audit

Existing modules use:

```text
runtime/include/modules/<module>/<module>.hpp
runtime/include/modules/<module>/<module>.cpp
runtime/include/scpp/<module>.hpp
```

The `tasks` module should follow:

```text
runtime/include/modules/tasks/tasks.hpp
runtime/include/modules/tasks/tasks.cpp
runtime/include/scpp/tasks.hpp
```

The `scpp/tasks.hpp` umbrella should remain tiny:

```cpp
#pragma once

#include "modules/tasks/tasks.hpp"
```

Preferred namespace:

```cpp
namespace scpp::tasks {
}
```

Preferred source-visible aliases:

```cpp
namespace scpp {
using task_batch = tasks::batch;
using task_context = tasks::context;
using task_progress_info = tasks::progress_info;
using task_error = tasks::error;
}
```

Header hygiene:

- hide `<thread>`, `<mutex>`, `<condition_variable>`, and queues in `.cpp` or internal detail state when possible
- keep public signatures expressed in Simple C++ runtime types
- do not expose raw `std::thread` or `std::jthread`

## First Test Matrix

Minimum prep matrix before or during implementation:

- `tasks` module accepted in `prism.json`
- unsupported module name still fails clearly
- strict `task_*` names map to `scpp::tasks::*`
- `task_run(vector<int>, workers, callback)` preserves vector order
- worker count is bounded and does not create one thread per item
- unhandled worker error is reported on the main/coordinator thread
- handled item error continues by default
- duplicate custom keys overwrite silently
- `task_start` + `task_join` returns the same result shape as `task_run`
- live background batch is joined by generated main cleanup
- timeout creates a worker error/throw-like event
- `task_status` and `task_progress` read runtime-owned state

## Suggested Order

1. Implement `tasks` module build plumbing with empty/stub runtime functions.
2. Add strict symbol mappings and module-enabled compile coverage.
3. Implement `task_run` for typed vector input and a single callback shape.
4. Add error capture and main-thread reporting.
5. Add `task_start` / `task_join`.
6. Add progress/status.
7. Add timeout.
8. Revisit mixed input, thread-boundary transfer semantics, and dynamic submission.

## Post-Implementation Audit Notes

Status after the first implementation pass:

- `tasks` is opt-in and accepted by project/module plumbing.
- `task_*` strict names map through the strict runtime symbol registry.
- `runtime/include/scpp/tasks.hpp` remains an umbrella include.
- `runtime/include/modules/tasks/tasks.hpp` owns the runtime surface and implementation templates.
- Disabled-module stubs exist for the public helper family and the supported `task_run` / `task_start` vector/hash overload shapes.
- The runtime uses a bounded worker count per batch and does not create one native thread per input item.
- Worker callbacks and optional error handlers run outside result insertion locks.
- Fixed vector non-void results use indexed slots instead of a shared result mutex or post-join sort.
- Fixed vector `void` results use an atomic success count and materialize null placeholders after join.
- Hash workers use prepared input slots and avoid an extra local key/item copy before callback dispatch.
- Background result boxing moves freshly owned vector/hash result values where possible.

Focused validation now covers:

- module enablement and disabled-module diagnostics
- vector and hash `task_run`
- vector and hash `task_start` / `task_join`
- custom-index callbacks
- result targets
- nullable custom-index append behavior
- duplicate custom-key overwrite behavior
- handled and unhandled worker errors
- background repeated join errors
- cancellation/progress/status
- timeout diagnostics
- progress after source-catchable failed background joins
- vector result-target key contract errors
- a monotonic-time probe for nonblocking `task_start` and parallel join shape

Remaining follow-up belongs outside the first-pass implementation:

- precise input/output transfer semantics across worker boundaries
- mutable/exclusive item ownership
- worker communication beyond short status snapshots
- dynamic task submission
- custom merge callbacks
- reusable worker pools
- STAN enforcement for callback/container/thread-boundary contracts

Transfer-semantics follow-up:

- [specs/planning/threading_transfer_semantics_followup_2026_06_15.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/threading_transfer_semantics_followup_2026_06_15.md)
