# Tasks builtin contracts
Doc Status: normative

This folder defines the first-pass Prism++ / Simple C++ tasks runtime module surface.

The `tasks` family provides a small, structured multithreading API for strict PHP++ projects.
It is intended for bounded worker batches and background batch handles, not raw thread management.

Release status: v1-alpha / experimental.

The module is opt-in and suitable for independent batch work over typed `vector`, typed `hash`, and table-shaped `mixed` / `dynamic` inputs.
Do not treat the alpha surface as stable for shared mutable object graphs, runtime resource handles, worker communication, or advanced ownership transfer.

Current first-pass scope:

- strict/runtime surface under `scpp::tasks`
- flat strict source names with the `task_*` prefix
- runtime-owned batch/progress/error objects
- optional runtime-owned reusable worker-pool backing for repeated batches
- typed vector/hash input plus conservative table-shaped mixed/dynamic input
- cooperative timeout handling through diagnostics or `task_error`
- no legacy PHP compatibility wrapper surface

First-pass builtins:

- `task_run`
- `task_start`
- `task_join`
- `task_cancel`
- `task_done`
- `task_status`
- `task_progress`
- `task_set_status`

Deferred dynamic-batch builtins:

- `task_submit`
- `task_close`

See also:

- [specs/builtins/tasks/first_pass.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/builtins/tasks/first_pass.md)
- [specs/builtins/tasks/examples.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/builtins/tasks/examples.md)
- [specs/planning/threading_batch_api_plan_2026_06_08.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/threading_batch_api_plan_2026_06_08.md)
- [specs/planning/threading_preimplementation_audit_2026_06_12.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/threading_preimplementation_audit_2026_06_12.md)
