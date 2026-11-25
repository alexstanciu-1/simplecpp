# Public API boundary and next dependency inventory
Doc Status: supporting

Current completion audit: [runtime-core inventory and batches](../../specs/planning/runtime_core_completion.md)
(checkpoint `6a1cb4af2`, excluding modules). The tables below retain the earlier
value-boundary checkpoint; they are not a complete inventory of today's public surface.

Checkpoint: 2026-10-27. This is a physical API inventory, not a new source-type
registry. The first slice establishes publication and include isolation; it does
not migrate compiler facades. Value slices now expose inline string/error values and retained exception inspection.

## Implemented boundary

`runtime/public/` owns the restricted include root. Consumers link `scpp_core_api`
or `scpp_tasks_api`. Both supply only this include root; the latter enables and
links tasks. The existing `scpp_runtime_core`, `scpp_tasks`, configuration and
aggregate targets remain broad compatibility targets during migration. Their
implementation includes are suppressed through link-only dependencies of the new
API targets. This works with static and shared runtime builds.

| Header | Contract and dependencies | Compiled owner |
| --- | --- | --- |
| `scpp/integers.hpp` | Existing concrete scalar definitions; no includes. All currently supported ordinary operations remain declared. | Runtime core, `core/integers.cpp` |
| `scpp/string.hpp` | Complete ordinary string API; concrete scalars and target-probed inline layout only. No public native borrow. | Runtime core, `core/string.cpp` |
| `scpp/error_value.hpp` | Inline in-band error with concrete scalar/owned string getters. | Runtime core, `core/error_value.cpp` |
| `scpp/exception_info.hpp` | Retained runtime exception; owned lazy inspection and original rethrow. | Runtime core, `core/exception_info.cpp` |
| `scpp/failure.hpp` | General error construction/throwing from synchronous borrowed NUL-terminated fields. Copies fields before throwing; null fields mean empty strings. No includes. | Runtime core, `core/failure.cpp` |
| `modules/tasks/worker_pool.hpp` | Integer configuration/query and shutdown. Disabled configuration calls the core error primitive; diagnostic policy/arguments remain in this task header. | Tasks for enabled operations; core error machinery for disabled configuration |

Disabled public configuration needs core but no task library. Query/shutdown still
require enabled task implementation when called; this slice adds no disabled success
stubs. The legacy `tasks.hpp` configuration overload preserves its header-only
throwing implementation and exact diagnostic fields without runtime-library linkage.
Compatibility forwarding headers preserve existing include paths. Definitions exist
only in the public root, including the scalar generator's output.

Error raising and retained public exception inspection are exposed.
Public callers can propagate or catch exceptions; existing host/compatibility code
can still inspect the unchanged `runtime_error` type. The private test inspector
checks exact failure fields in a separate translation unit. The physical error representation is now exposed through error_value;
source-language error semantics are unchanged.

Runtime packages publish `public/` directly. Compiler middleware re-exports scalar
names through that selected runtime root, without copying headers into its own
publication. Runtime implementation roots remain private to middleware/support.
The existing publication identity and explicit rebuild rules remain in force.

## Value group: string/errors implemented; borrowing and builder approved

| Facility / current owner | Required public operations and dependencies | Representation / contract questions to resolve before implementation |
| --- | --- | --- |
| String: `scpp/string_t.hpp` and registered string operations | Construction/copy/move/destruction, length-bearing bytes including NUL, checked access/slices, comparison and declared mutation; integer/boolean results. Current header pulls `detail.hpp`, STL string, bool and UTF-8 helpers. | Preserve independent values and byte/code-point distinctions. Native string currently stores `std::string` inline; the compiler facade adds a heap wrapper. Prefer no extra wrapper allocation. Implemented as target-probed inline `scpp::string`; compiler facade migration is pending. |
| Text builder: `modules/strings/strings.hpp` | Reserve/capacity/length, append string/bytes/integer/boolean/checked byte span, clear, copied string result and transferred string result. The existing header also exposes a string-parts builder and unrelated string helpers. | Keep builder state inline or otherwise justify allocation costs. Distinguish copy-producing `to_string` and transferring `take_string`. Full ordinary API includes `append_byte_span`: it introduces the source span contract even when most callers only append strings. Forward declarations can avoid eager header dependencies for references but cannot replace the missing concrete span definition. |
| In-band error: `scpp/error_t.hpp` | Default/no-error, actual failure construction, copy/lifetime, presence, message/file strings and line integer; Result construction/extraction follows its own state rules. | Do not confuse a valid error with an empty message or merge error with null/false. Preserve ownership and source getter semantics; the C++ implementation currently returns references internally, whereas source values have their own declared transport rules. |
| Thrown runtime error: `scpp/runtime_error.hpp` | Failure metadata inspection and host reporting; underlying exception storage also retains detail arrays and trace data. Currently includes iostream, strings, vectors, optional, trace/platform facilities. | Keep exact exception identity/diagnostics for compatibility. Implemented by retained exception_info without exposing STL return types or tracing implementation. C++ runtime exceptions and in-band `error_t` are different contracts. |
| Source byte span: `modules/source` / compiler-support contract | Checked borrowing, byte length/access, builder append; owner liveness and invalidation. | Span copies do not retain the byte owner. Do not convert a borrowed span into an owning string or unguarded pointer merely to simplify the public signature. |
| Result/nullable/ownership | Concrete variants demanded by actual returned values and runtime methods, plus declared supported operations. | Preserve value/error/sentinel distinctions, aliases and cleanup. Generate through the shared physical mechanism; reuse published specializations and do not invent a second semantic registry. |

The compiler string/textbuilder facades are references for usability and measured
behavior, not new semantic authorities. Its filesystem adapters intentionally throw;
those must not replace the runtime filesystem Result contracts when public modules
are migrated.

The approved [value API plan](../../specs/planning/runtime_core_value_api.md) now
settles signatures, storage, capture and borrowing policy. Runtime string is implemented;
error values and retained inspection are implemented; builder and source spans remain follow-up slices. Compiler facades
continue using their existing implementation until an explicit migration.

## Validation scope

All public headers are checked independently with only the public include root in
both task modes: no active template tokens, no implementation-header dependencies,
and deliberate inclusion of implementation headers fails. Enabled and disabled
caller translation units compile through the actual narrow CMake targets. Separate
compatibility TUs inspect diagnostics and execute the existing four-worker concurrency,
reuse and recovery workload. General failure tests check copied input lifetime and
null fields. Static/shared linking and focused sanitizer results are recorded with
the completed slice measurements.

See [first-slice test and measurement results](public_api_boundary_results.md).
The native text builder currently has ordinary value copy/move support through its
string storage; the compiler facade deletes copying. The next API design must check
the source contract and must not accidentally inherit that compiler-only restriction.
