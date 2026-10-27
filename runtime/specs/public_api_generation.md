# Metadata-driven public API generation
Doc Status: normative

This physical generation contract is subordinate to language semantics, runtime
module metadata and [the public boundary rules](core_public_api.md). It does not
admit source-language programs or infer semantic types. This first slice replaces
the scan/vector/Result-specific emitter, not every runtime API generator.

## Ownership

- `runtime/metadata/modules/*.json` owns native representation bindings.
- `runtime/metadata/public_api.json` selects public exposure, type transport,
  lifecycle, signatures, explicit dependencies and adaptation snippets.
- `runtime/specs/public_specializations.json` selects runtime publication demands.
- `runtime/tools/api_generator/` owns reusable C++ parsing, naming, dependency
  planning, storage/lifecycle emission and operation emission. No named-family
  switches belong here. The library entrypoint is `scpp::api_generation::generate`;
  it accepts/returns owned runtime strings containing prepared JSON documents.
- The native runtime retains behavior, including iteration preservation and Result
  extraction semantics. Metadata snippets bind that behavior; they are trusted
  build inputs, not untrusted source-language programs.
- `runtime/tools/concrete_types.py` is a process/filesystem driver: compiler ABI
  checks, target probes, generation staging and existing package validation. It
  does not emit C++ declarations or operations or resolve template families.

The focused tooling header is `scpp/api_generation.hpp`, in its own public include
root; private metadata/naming headers are not exposed to library consumers.
The static C++ library is `scpp_api_generation`; `scpp_api_generator` is a thin
stdin/stdout CLI. The compiler can call the library without launching the CLI or
invoking Python. The API currently transports JSON rather than exposing its private
model containers; no STL or template types enter that library's public header.
The library is reentrant and has no mutable generation-global state.

## Metadata vocabulary (api-v1)

`types` maps prepared leaf identities to `cpp`, `header`, `owner`, and optional
`native`, `borrow`, `construct`, `includes` fields. `borrow` describes adaptation of
an argument; `construct` describes constructing an owned public result from a native
binding result. The existing concrete string/error bridges implement this transport.
Application leaf definitions may add types but may not redefine runtime identities.

`families` contains parameter names, an optional existing native `binding`, a
parameterized `native` representation, explicit private `includes`, target-probed
`checks`, a list of `classes`, and optional free `functions`. A companion class is an
ordinary class entry, not a generator-recognized cursor. All classes are declared
before their signatures; concrete parameter dependencies are materialized first.
A family's first slice requires copyable, assignable, nothrow-destructible payloads
through explicit metadata checks. Unsupported payloads fail target probing.

Each class has `id`, `name`, `native`, `lifecycle`, `methods` and optional `includes`.
Lifecycle entries `default`, `copy`, `move`, `destroy` select `native` or `disabled`;
inline storage requires native nothrow destruction. Disabled copy/move operations
are explicitly deleted. Enabled operations are target-probed, and destruction and
layout are also asserted in the emitted implementation. Actual native objects live
in their probed inline storage; representations are never byte-copied as unrelated
classes. Native-result factory construction supports nonmovable objects directly
in final storage.

An operation describes `name`, `kind` (method/constructor/function), `returns`,
`parameters`, optional `const`, `noexcept`, `explicit`, `transport`, `uses`,
`includes`, and exactly one `expression` or `body`. Constructor expressions supply
native constructor arguments. Parameters specify `name`, `type`, and `passing`
(value/const_ref/ref/move_ref). Selected named signatures and dependencies are
resolved structurally; the tool does not parse C++ to discover snippet dependencies.
Unknown fields, bindings, placeholders and selected types are errors.

Substitutions include:

- `${name}`, `${namebase}` for the selected concrete name.
- `${public:T}` / `${native:T}` for parameter or companion representations.
- `${self}` for the native object, preserving method constness.
- `${public:argument}` / `${native:argument}` for arguments.
- `uses` explicitly resolves additional type dependencies and makes their public/
  native representations available by identity, e.g. `${public:vector<string>}`.
- Leaf transport snippets use `${value}`, `${factory}`, `${cpp}`, `${native}`.

A body uses ordinary C++ statements and `return`. For a value-returning operation,
the generator places it in an immediately invoked `decltype(auto)` lambda, retaining
the binding result's references and value category. `transport: public` returns that
result through the declared public signature (an owned return copies a borrowed
value). `transport: native` applies the result type's `construct` binding to the
factory. Void bodies run directly. Snippets must not return dangling references;
compilation alone cannot prove arbitrary C++ safe. Native helpers and focused
lifetime/failure tests remain essential. No new snippet control-flow language exists.

## Cursor contract

The selected vector cursor is native `vector_range<public T>` in inline storage.
It is neither default-constructible, copyable nor movable. `iterate()` constructs it
directly. `valid()` and `key()` are non-const because the native range's accessors
are non-const; no generated const_cast is needed. Consumption follows
`valid -> value once -> next`. Repeated reads after consumption/mutation are not an
additional public guarantee. Native traversal preservation, owned values, mutation
failure handling and survival beyond owner destruction remain unchanged.

## Naming, probing and publication

Canonical resolution and concrete naming are owned by C++ `names.cpp`. The existing
Python Registry interface delegates to it; its parsing/structural helper functions
remain compatibility utilities, not another canonical resolution path. Registered
aliases, family arity/defaults, escaped depth names and mapped integer arguments
retain the previous naming protocol. This physical API recipe schema currently
materializes type-parameter families; new value-parameter generation is future work.

The pure library consumes prepared descriptors; imported publication identities
must already have been verified by the calling build/publication layer. It does
not read dependency files or bypass the driver's ABI/integrity checks.
The pure library returns a deterministic plan: generated units, signature identities,
layout requests and metadata capability checks. The driver compiles a probe for each
dependent unit, combining companion layouts in that invocation, then supplies results
to the same library. It stages output and installs only after all probes and stale
inventory checks pass. This is not cross-compilation support: target probe execution
must be available. Public headers must remain independently template-free.

Published packages carry the generator executable and library/header, API metadata,
existing SDK and concrete export inventory. `compiler_api_generation` exposes only
the tooling header and public runtime headers. The existing publisher seals metadata,
SDK headers, archives and generator executable; import also checks API metadata identity
and compiler/target ABI. Import reuses compatible definitions before generating
application-owned demands. Python bytecode caches are excluded from source identities.
No separate runtime-public-middleware product or semantic registry is introduced.

## Reproduction and scope

Build the tool with `cmake --build <runtime-build> --target scpp_api_generator -j8`.
For a nonstandard build directory set `SCPP_API_GENERATOR` to that executable.
Then use `python3 runtime/tools/concrete_types.py --request
runtime/specs/public_specializations.json --output runtime`. Published copies use
their shipped executable. The source-tree driver can incrementally build the tooling
target in the compiler's configured runtime build; ordinary compiler builds retain
explicit runtime/middleware publication boundaries.

The first closure includes scan, vector<string>, its cursor, Result<vector<string>>,
existing concrete leaf transports, typed-hash composition and an app-defined stored
record. Leaf integer/string/error implementations are not regenerated by this slice.
Other families, conditional operation selection, reference-valued source semantics,
full scalar operations and compiler facade migration remain separate work.
