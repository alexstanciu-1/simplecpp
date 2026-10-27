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

`families` contains parameter names, optional trailing `defaults`, an existing native `binding` when needed, a
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

## Template defaults

Defaults are type expressions attached to the trailing parameters. For example:

```json
{
  "parameters": ["T", "U", "V"],
  "defaults": ["string", "sequence<T>", "U"]
}
```

The resolver processes arguments left to right. Explicit arguments are normalized
first; an omitted argument substitutes already-resolved earlier parameters into its
default expression, then resolves the resulting type. References are whole type
nodes, not text replacement: `T` can appear inside `vector<T>`. Chained defaults and
explicit overrides therefore work without a family-specific rule. Earlier argument
aliases are normalized before substitution. A parameter cannot be used as a template
family (`T<string>`); template-template parameters are not part of this schema.

All-default families accept both the bare family name and `family<>`. Empty argument
lists on leaves are rejected. Required parameters still require explicit arguments.
Parameter names must be unique, defaults must be a trailing list no longer than the
parameter list, and a default cannot reference itself or a later parameter, even
when all arguments are supplied explicitly. Undefined selected types are rejected;
a depth bound rejects recursive cross-family default expansion without stack overflow.

Expansion happens before canonical identity, concrete naming, import lookup and
layout dependency planning. Omitted, partially explicit and fully explicit equivalent
forms share one definition and reuse the same imported specialization. Inner concrete
representations are resolved before outer storage is emitted.

The shared naming registry retains its `parameters` kind list and optional
`parameter_names` list; API metadata supplies that name list automatically. Existing
constant defaults without names remain supported. The naming registry also supports
its existing admitted integer values in dependent defaults; this does not add
value-parameter emission to the public API recipe schema.

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
Other families, reference-valued source semantics,
full scalar operations and compiler facade migration remain separate work.

## Capability conditions and typed hashes

The optional top-level `capabilities` object defines named physical capabilities.
Each entry has nonempty `checks` (C++ target-probe expressions) and optional private
`includes`; `${cpp}` and `${native}` refer to the checked representation. Leaf and
family recipes declare an optional `capabilities` array. These are explicit exposure
claims, not source-language admission inferred from C++ methods. Family declarations
travel with their published concrete records.

A family or operation can specify `requires_capabilities`, mapping parameter names
to arrays of capability names. All listed capabilities are required. A failed family
requirement rejects the specialization; a failed operation requirement omits that
operation before resolving its dependencies or expanding its body. Unknown names,
parameters, duplicate claims and malformed lists fail generation. Required claims
are checked in the same target probe as the consumer's layout, with duplicate checks
removed. Unused claims do not pull their private headers into unrelated probes.

For example, typed hash requires `K: [typed_hash_key]`, while its `append` operations
require `K: [integer_key]`. These names and native checks belong to metadata; the
C++ generator has no hash/integer dispatch. This does not admit arbitrary native key
policies as source-language hash keys. Shared identity keys and mixed/dynamic families
remain outside this slice.

`${type:T}` expands a parameter's normalized canonical type expression, rather than
its C++ name. It lets a signature request `nullable<${type:V}>` through the same
resolver used for root demands, including nested parameters and imported types.

The typed hash recipe uses inline `hash_t<public V, public K>` storage, a default
string key, owned checked reads, non-inserting `find`, explicit writes/removal and
an insertion-order companion cursor. Its read-only `operator[]` delegates to `at`,
never the native inserting operator. Cursor keys and values are owned; the native
preservation/retirement implementation controls mutation and escaped lifetimes.
The nullable recipe supplies owned checked extraction, reset, fallback, constructors,
assignment, `take`, `was_found` and `is_nullopt`. Absence is not an error or stored null.
It does not expose interior references or the native optional.

Concrete integer append uses the private `hash_detail::integer_key_policy`, alongside
existing concrete key hashing/equality adapters. The existing next-key and overflow
algorithm remains the authority for both native and concrete integers; no global
numeric trait opt-in or metadata copy of the algorithm is introduced.

Publication imports treat a structural name ending in `_` as an assertion of the
canonical generated name, not as an authored alias. A mismatch is rejected; normal
authored-name restrictions remain unchanged. This permits application-owned nested
specializations to be reused even when they have no friendly name mapping.

## First mixed value surface

`mixed` and `float64` are zero-parameter recipes using the same class, lifecycle,
transport and companion mechanisms as parameterized families. No generator dispatch
or new signature form is needed. Runtime publication demands `mixed`, which pulls in
`float64`. The existing `null_t` declaration and `null` constant now live in the public
include root; the compatibility header forwards to that same definition.

`float64` stores native `float_t` inline and exposes construction from native double
or concrete int64 plus native-value inspection. This first scalar slice does not
claim the full floating operator surface. Double is an explicitly described native
interchange leaf, not a templated dependency. Mixed construction/assignment accepts
null, boolean, int64, float64 and string. Exact extraction returns owned public values;
it is not a permissive scalar cast. Numeric conversions/operators remain later work.

Mixed reads bind `null_index_read`, not the permissive legacy `mixed_t::get`: null or
missing entries produce mixed null, but scalar receivers reject. Writes use the
existing checked write helpers, including null bootstrap. Removal probes before
mutation, so an absent/null receiver is not turned into a table. Self-aliased writes
snapshot the input before mutating the receiver; nonaliased writes avoid that extra
copy. These adaptations are short metadata snippets, not new container algorithms.
`reset` and `clean` replace the binding with native null. `make_mixed_table` constructs
an explicitly empty owned mixed table, distinct from null.

The mixed companion cursor uses native `bound_range`, via `value_iteration`, rather
than a direct hash cursor. It therefore observes replacement/move/reset of the boxed
binding as well as table mutations. Keys and values return owned mixed values; null
and scalar traversal reject. The mixed cursor's `next()` is a no-op when finished, including when preservation
exhausts the remaining entries immediately after consumption; key/value reads still
reject a finished cursor. Internal pointers, mutable extraction, weak-table hooks
and native ownership carriers are not exposed. No dynamic/container cross-family
conversion is introduced. Public nullable/mixed recursive dependencies and implicit
C++ conversions remain outside this slice.
