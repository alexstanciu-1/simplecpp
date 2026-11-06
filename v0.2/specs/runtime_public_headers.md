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

## Metadata-owned string/output providers

`runtime.json` owns `submodules` (module, header and direct `requires` dependencies).
The runtime string and output records select their provider. Compiler bindings
reference the runtime `declaration_group`, so runtime publication does not depend
on compiler lowering strategies or maintain a second header spelling.

The API generator validates this graph and each declaration group's intrinsic
provider requirements before publication. Requirements may be satisfied through
transitive dependencies. Group export contracts also reject incompatible compiler
bindings and literal-header fallbacks for migrated bindings. The prepared plan
separates provider `nodes` from declaration `groups`. The assembler places string
and output declaration groups into their selected headers. Both may share a
provider. Existing scalar headers are registered dependency providers whose
contents and publication-selected paths remain owned by their existing renderers.
Only core submodules and complete-header dependencies are supported in this slice.
Other families, scalar representations and general forward-declaration synthesis
are unchanged.

String publication requests no longer need a header field. A supplied legacy field
must agree with the provider; it cannot override metadata. The compiler's generated
header queries resolve providers during generation and retain per-file collection
at application compilation. The handwritten output implementation includes a
stable generated private `output_provider.hpp`, which includes the selected public
header. This private bridge follows relocation without exposing implementation
headers to application code.

See the [partition plan](planning/runtime_submodule_partitioning.md) for subsequent
work and the first-slice verification record.

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
