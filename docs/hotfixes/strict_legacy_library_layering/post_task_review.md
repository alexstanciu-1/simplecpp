## Strict vs PHP-Legacy Layering

Doc Status: planning

Purpose: record follow-up review items that should be revisited after the current hotfix task is complete.

### Architecture Compliance Review

- The shared string helper extraction into `runtime/include/core/string_support.hpp` is now complete.
- `runtime/include/modules/strings/strings.hpp` no longer depends on `lang/php/*`.
- Follow-up:
  - keep new shared helper ownership in `core/` or another non-language runtime-owned location
  - avoid drifting string helper ownership back into `lang/php/*`

### Shared Runtime Surface Review

- Review whether all new Stage 1 shared runtime names are the final intended strict-facing names.
- Confirm especially:
  - `scpp::fs::*`
  - `scpp::str::*`
  - `scpp::io::*`
  - `scpp::json::encode/decode`

### PHP Adapter Thinness Review

- Re-check that PHP-facing wrappers are adapters only where the capability is reusable.
- Confirm that PHP-owned helpers remain only where the behavior is genuinely PHP-specific.

### Stage 2 Readiness Review

- Before introducing the strict PHP surface, verify that the shared runtime contracts are stable enough to become the authority beneath both surfaces.
- Confirm that Stage 2 can proceed without further runtime semantic churn.

### Generator Shape-Check Review

- Reduce generator-side ad hoc runtime-shape branching where a common runtime interface already exists.
- In particular, review any expression-lowering paths that branch on wrapper/runtime shapes such as:
  - `result<vector_t<...>>`
  - `result_or_false<vector_t<...>>`
  - `result_or_bool<vector_t<...>>`
  - `mixed_t`
  - `maybe_value_t`
- `foreach` already lowers through the common `::scpp::foreach_range(...)` interface.
- Follow-up:
  - prefer common runtime access helpers/interfaces for post-foreach reads too
  - avoid expanding generator knowledge of wrapper families unless a narrow documented rule truly requires it

### Operator Matrix Synchronization Review

- The wrapper equality fix in `runtime/include/scpp/generated/operators.hpp` matches the current runtime spec.
- After the task, review whether any operator-matrix derived docs or data need synchronization for explicit wrapper-vs-sentinel comparison coverage.
