# Runtime public header boundary

Doc Status: supporting

Public headers expose the smallest usable contract. Keep formatting, IO, storage,
error construction and other implementation dependencies in compiled runtime
sources. Generated application units include the relevant public header and call
its API; they must not carry duplicate nontrivial runtime implementations.

- Include only what declarations or deliberately public definitions require.
- Prefer forward declarations where they honestly suffice. A public header must
  also compile by itself; callers must not depend on include order.
- Templates, constexpr and deliberately inline operations may require definitions.
  Document that choice; it does not justify unrelated transitive includes.
- Review the transitive include closure, not only the header's immediate includes.
- Test behavior and isolated header use. For build-time changes compare fresh builds
  of the same source and separate instrumentation from ordinary timing samples.
- Generated headers are authored through the API generator/templates. Do not patch
  generated publications as the source of truth.

## Current output boundary

The generator always publishes the core output declaration group, independent of
optional modules. Runtime metadata selects its provider submodule; the default is
`scpp/output.hpp`, including only `<stddef.h>` and `<stdint.h>`:

```cpp
void scpp::echo_bytes(const char* data, ::size_t length);
void scpp::echo_int(::int64_t value);
```

The C scalar headers avoid pulling libc++ template helpers into the public include
closure. The byte buffer is borrowed during the call; a zero length permits an empty buffer.
Integers use locale-independent decimal formatting. Calls add no separator or newline.
`runtime/src/output/output.cpp`, compiled into `scpp_runtime`, owns formatting,
stdout writes and exception construction. `<charconv>`, `<cstdio>`, `<system_error>`
and `<stdexcept>` remain private implementation dependencies. Native parameter types
form the backend boundary; this does not add native types to source language syntax.

This preserves the earlier bounded echo helper behavior, including buffered stdout:
a short write throws, but a failure deferred until a later flush is not diagnosed by
this API. There is no new automatic flush policy. The remaining source-language
work is tracked in [echo debt](planning/echo_debt.md); the authoritative output
semantics remain [output_v0_2](../../specs/output_v0_2.md).

## Metadata-owned declaration providers

`runtime.json` owns `submodules` (module, header, direct `requires` providers and
`requires_exports` semantic type dependencies). Declaration groups own placement:
`scalar_providers.core` and `.numeric` select the scalar groups; string, output and
checked conversions each select their provider. Compiler bindings reference type
exports or declaration groups rather than duplicating those paths.

The default scalar partitions are `scpp/core/scalars.hpp` and
`scpp/numeric/scalars.hpp`. Core membership comes from `core_integers`, value records
and the floating record. Optional integer widths remain in the existing `numeric`
module and are emitted only when selected. Both headers contain declarations only,
with no implementation or STL includes. Aliases follow their selected target's
provider. An unselected scalar group emits no header.

`scpp/conversions.hpp` remains separate. Its dependencies come from the source/result
identities of actual published helper signatures. Selected string parsers retain a
concrete string forward declaration, avoiding a scalar/string include cycle. String
formatting dependencies likewise follow selected casts, not all enabled capabilities.
Publication preparation combines these requirements with catalog edges, validates
cycles and published dependency availability, and records the effective nodes,
closures and selected providers in `prepared.json`. The assembler consumes that
resolved graph without adding dependencies. Scalar-only preparation finalizes its
own demand; runtime preparation finalizes after all families are prepared.
Compiler header queries remain generated code: application compilation does not
read runtime metadata or traverse this publication graph.

`scpp/numeric.hpp` remains a native convenience aggregate of selected scalar
providers; `short_names.hpp` retains its explicit imports. Neither is a compiler
blanket include. The old `integers.hpp` and `values.hpp` are retired without forwarding
shims. Publication `headers.types`/`headers.values` are rejected as obsolete; scalar
paths have one metadata owner. CMake obtains managed header outputs through the same
provider resolution, and tracks metadata/publication changes for reconfiguration.

String and output groups may share a provider; scalar groups remain independent.
String publication requests no longer need a header field. A supplied legacy field
must agree with its provider. The handwritten output implementation includes the
stable generated private `output_provider.hpp` bridge. The compiler retains per-file
include collection through generated header queries.

Only core and numeric provider modules are supported here. Other runtime families
retain their existing renderers, with includes adapted to the relocated scalars.
See the [partition plan](planning/runtime_submodule_partitioning.md) for verification
and deferred work.

## Entry target boundary

The fixed core header `scpp/entry.hpp` exposes `SCPP_REQUIRE_ENTRY_TARGET(digits)`
and `scpp::entry_exit_checked(int64_t)`. Generated entry code includes that public
header and invokes the macro with the prepared target's value-bit count. The macro
owns the static assertion and diagnostic; the small constexpr bit-count helper
checks the consuming compiler's `INT_MAX`, without assuming no padding bits.
Its definition is intentionally public because the check must happen at compile time.

Only `<limits.h>` and `<stdint.h>` enter this header's include closure; no STL
limits or exception headers enter generated application code. The checked conversion
is compiled into `scpp_runtime` and throws for values outside the actual native int
range. Proven conversions remain simple casts chosen during preparation.

All includes emitted by the compiler must name existing `scpp/` public headers.
The native output integration test checks that rule for every emitted source unit
across all three flavors; private runtime paths and direct standard-library includes
are rejected. Native test harnesses may still include standard headers for testing.

## Native integer arithmetic boundary

`scpp/arithmetic.hpp` is a generated core compiler-facing ABI exposing
`int64_divide(int64_t, int64_t)` and `int64_remainder(int64_t, int64_t)`. It includes
only `<stdint.h>`. Compiled `runtime/src/numeric/arithmetic.cpp` delegates to the
existing private scalar semantics for zero-divisor checks and native arithmetic.
It does not add signed-overflow checks. Compiler lowering emits ordered snapshots
before invoking these functions; plain addition/subtraction/multiplication need no
runtime include. The isolated-header proof is `tests/arithmetic_runtime.py`.

## Representation-independent type exports

`type_exports` lists existing canonical identities, public `name` spellings and an
owning `declaration_group`. The planner checks unique declaration ownership and
resolves `scpp::` names and providers once. Names must already be declared for that
identity, including existing aliases such as byte; exports do not invent declarations
or admit new source types. Scalar and movable string exports use this same model.

The include planner knows nothing about classes versus native aliases, constructors,
extraction members or conversion admission. Existing lowering strategies retain
those responsibilities. Native scalar aliases are already integrated; partitioning
changes placement and dependencies, not their implementation or source semantics.

## Publication-resolved vector exports

The vectors declaration group joins the provider assembler. `vector.provider` owns
placement; validated publication specializations supply its concrete type identities
and exports. Count, boolean and element identities supply actual public dependencies.
No second metadata specialization list is required. Type-export lookup on the
prepared runtime plan now yields each concrete vector's C++ symbol and provider.
Compiler generation consumes these exports for [vector type resolution](../compiler/docs/vector_types.md).
The first [vector value operations](../compiler/docs/vector_operations.md) use these concrete bindings. See [the vector contract](vector_slice.md).

## String storage ABI

The native string object occupies 24 bytes. Its public header exposes
`scpp::string::inline_capacity` (22 payload bytes) and `value.is_inline()`.
Construction selects storage by byte length. The inline form keeps an explicit
length and a trailing NUL, including for binary strings with embedded NULs.
Append promotes to private heap storage when the result exceeds 22 bytes.
Clear and moved-from values use the empty inline form. Both forms pass borrowed
bytes to the runtime through a private view. The layout change requires a
coordinated rebuild of native consumers and runtime archives.
