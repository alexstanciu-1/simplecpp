# Runtime-core public C++ API
Doc Status: normative

This contract governs new focused native C++ runtime interfaces. It is subordinate
to the language semantics, runtime layering rules and shared operator authorities.
It does not change source-language numeric semantics or retire existing runtime headers.

## Boundary rules

- Expose each facility through a small, self-contained header. Core is an ownership
  layer, not a requirement to include one global header. A consumer includes only
  the facilities it uses; integers must not pull in strings, containers or modules.
- Public signatures use concrete runtime types. Do not expose implementation
  templates, STL containers, generic callback machinery or private state through
  this boundary. An exception needs a documented reason and a bounded scope.
- Audit the transitive include closure, not just the first header. An alias to an
  instantiated template and `extern template` do not hide the class definition.
- Value types own representation, initialization, copying, access and invariants.
  Native scalar storage can be visible as a private field: opaque allocation is
  not required. Keep simple representation operations inline where useful.
- Compile semantic operations behind the boundary. Delegate to existing shared
  operator/cast authorities; do not reproduce their arithmetic or error rules.
- Implementation dependencies flow from modules to core, never the reverse.
  Link through named CMake targets. Core implementation must also remain available
  through the historical single-source runtime bundle.
- Preserve old entrypoints through explicit compatibility adapters as consumers
  migrate. Do not silently change overload resolution or return types. Document
  any source/ABI break; no general stable binary ABI is promised by this slice.
- Prove independent inclusion/linking, dependency purity, behavioral parity,
  size/alignment and relevant failure behavior. Measure caller compilation
  separately from implementation compilation and runtime execution.
- Keep files below 200 KB. Generation is allowed, with checked-in output and a
  deterministic freshness check; template instantiation belongs in implementation.

## First slice: concrete integers

`scpp/integers.hpp` exposes `scpp::int32`, `int64`, `uint32`, `uint64` and the small
`boolean` comparison result. These are classes, not aliases to `int_t<Rep>`.
They have native-sized storage, zero initialization and trivial copying. Construction
from the corresponding native representation and explicit `native_value()` access
are inline and allocation-free. There is no implicit conversion back to native.
Cross-facade and boolean-to-integer construction are explicit. Native constructor
arguments retain ordinary C++ conversion behavior, as in the existing compiler
facade; this is not a new checked-narrowing API.

Supported operations are same-type arithmetic (+ - * / %), comparisons, prefix
increment/decrement, compound arithmetic, and `isset`; boolean supports explicit
condition conversion, logical negation and `isset`. Cross-type arithmetic requires
an explicit conversion. This is deliberately a bounded facade, not the complete
conversion/operator surface of `int_t` or the language.

`include/core/integers.cpp` adapts to existing `int_t<Rep>`/`bool_t` shared operators.
Signed arithmetic retains the existing runtime's domain and limitations; this
work introduces no new overflow policy. Unsigned wrap, division/remainder errors,
and explicit representation conversions are inherited. No heap storage is added.
Out-of-line arithmetic may cost calls without LTO; representation operations do not.

The machine-owned type list is `runtime.concrete_integer_api` in `config.json`.
Run `python3 runtime/tools/concrete_integers.py` to generate and `--check` to verify.
The compiled owner is `scpp_runtime_core`, also included by `core/runtime.cpp`.
The current representation macros target the repository's Clang/GCC toolchains.

The definitions live in `runtime/public/scpp/integers.hpp`. New consumers use the
restricted `scpp_core_api` CMake target (or `scpp_tasks_api` for enabled tasks).
Compatibility include paths forward to those definitions. The compiler middleware
re-exports the exact classes and consumes the runtime's published `public/` root;
it does not copy the integer header or expose the broad implementation include root.
Its private bridge still adapts concrete values to existing template APIs as needed.
See [the boundary inventory](public_api_inventory.md) for ownership and migration scope.

## Scope and next steps

Concrete integers are used by the focused task worker-pool interface and compiler
middleware. Strings, containers, general task callbacks and language lowering
remain outside this migration. The existing `scpp/runtime.hpp` umbrella remains
available for legacy/template consumers. New focused consumers do not need it.

The new public API requires core linkage even when optional modules are disabled.
Task-owned disabled adapters call the general compiled `raise_runtime_error` primitive
from `scpp/failure.hpp`. Core constructs/throws errors but owns no task-module policy.
Both task modes have template-free public include closures. The legacy template API
retains its no-link throwing fallback during migration. Typed public exception
inspection and string/error representations remain a subsequent slice.
