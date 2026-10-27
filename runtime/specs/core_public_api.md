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
middleware. Inline strings are covered below; containers, general task callbacks
and language lowering remain outside this migration. The existing `scpp/runtime.hpp` umbrella remains
available for legacy/template consumers. New focused consumers do not need it.

The new public API requires core linkage even when optional modules are disabled.
Task-owned disabled adapters call the general compiled `raise_runtime_error` primitive
from `scpp/failure.hpp`. Core constructs/throws errors but owns no task-module policy.
Both task modes have template-free public include closures. The legacy template API
retains its no-link throwing fallback during migration. Typed public exception
inspection, error values and inline strings are covered below.

## Inline string value

`scpp/string.hpp` now exposes the complete ordinary string interface approved in
[the value API plan](../../specs/planning/runtime_core_value_api.md#string-complete-proposed-ordinary-interface).
It links through `scpp_core_api`. String owns an actual `string_t` in aligned inline
storage; there is no separately allocated facade object. Short-string optimization,
independent copies and native allocation failures are preserved. Operations delegate
to existing string/UTF-8/operator authorities. `isset` is true for every string;
`empty` reports zero bytes. No source-language type identity changes.

Public lengths use uint64. Byte access is checked and returns uint32 in 0..255.
Pointer inputs are synchronous host borrows: null single-pointer construction means
empty, null with nonzero length throws, and equals/vocabulary inputs must be valid
NUL-terminated pointers. No public operation exposes a raw pointer/view to contents.

`tools/value_layout.py` probes the actual target compiler and emits
`public/scpp/detail/value_layout.hpp`. Run with the runtime compiler and ABI flags
(`--compiler`, repeatable `--flag=...`); `--check` verifies published output. The
checked-in header is the current target's generated artifact, not a portable guessed
size. A different target ABI must regenerate it before building/publishing runtime.
Implementation assertions verify size, alignment and nonthrowing lifecycle traits.
Publish these headers together with the matching runtime library. Cross-target probe
execution and a stable binary ABI are not promised. Compatibility consumers can
still include the historical root through `include/scpp/string.hpp`.

The private `detail::string_access` bridge borrows only live native objects and
constructs owned results synchronously in final storage. It never byte-copies native
strings. Self-copy/move/swap preserve the object; ordinary moves retain native valid
moved-from semantics. Clearing retains reusable capacity. `equals` and `is_one_of`
borrow text and do not construct temporary strings.

Builder, source spans and compiler facade migration remain separate slices; this implementation adds no new module dependency.

## In-band errors and retained exception inspection

`scpp/error_value.hpp` exposes an inline native `error_t`: independent copying,
nonthrowing moves, no-error construction and `no_error`, presence/bool operations,
`make(message, line, file)` and owned message/file getters. Making an error with
empty fields still creates a present error. Defaults yield empty fields and line
zero. Line uses concrete int64. No new Result/source identity or automatic
conversion to a thrown exception is introduced. Failed assignment retains native
fieldwise assignment behavior, not a new transactional guarantee.

`scpp/exception_info.hpp` retains an actual `std::exception_ptr` inline. It has no
public default constructor or destructive move; rvalues copy, keeping both handles
valid. Copy/assignment/destruction are noexcept. The only factory,
`capture_runtime_exception()`, verifies the active exception is `runtime_error`.
Foreign exceptions rethrow unchanged; outside a catch it raises a runtime error
with code `no_active_exception`, component `core`, operation
`capture_runtime_exception`. Captured exceptions remain alive after the catch.

Inspection returns owned strings for message/code/component/operation, detail
keys/values, and trace module paths/relative addresses. Detail/trace counts and
indices use int64; negative/out-of-range indices raise `index_out_of_range`, and
unrepresentable counts raise `size_out_of_range`. Bounds failures leave the retained
exception intact. `rethrow()` rethrows the retained original, regardless of the
currently active exception. Native typed and standard-base catches are preserved.
Message follows native `what()`'s NUL-terminated observation contract.

Both headers use only the restricted public include root and link core. Layouts
and lifecycle traits share the existing target probe and implementation assertions.
No STL or native exception headers escape. The bridge inspects lazily by rethrowing
the retained pointer and constructing owned results inside the catch. No reference
to the caught object escapes, including on implementations that copy on rethrow.
Exception machinery may allocate during capture, inspection and rethrow; no zero
allocation guarantee is made for those operations. Returned long strings retain
their native allocation cost. Concurrent mutation/assignment of the same facade
requires caller synchronization, as for ordinary C++ values.

Compiler facade migration, source spans and textbuilder remain separate work.
