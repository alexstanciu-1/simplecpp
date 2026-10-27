# Concrete middleware names
Doc Status: supporting

Decisions recorded 2026-10-26. This contract names middleware specializations; it
does not introduce source-language types or change the compiler's semantic rules.
The build-time owner is `middleware/types.json` with `middleware/tools/type_names.py`.
The latter is tooling, not an implementation of the future C++ compiler type system.

## Names and identity

- Authored type names and explicit concrete names must not end in `_`.
- Automatically generated specialization names always end in `_`.
- Generic roots belong to the explicitly registered template-family space. A
  prefix such as `vector_` does not establish membership.
- Resolve registered short names first. Otherwise encode the normalized type tree
  in preorder, with each argument's depth immediately before its name.
- The root has no depth prefix; its direct arguments have depth zero.
- Same depth means a sibling; one deeper means a child; a shallower depth returns
  to that ancestor's child list. Depth is a decimal integer, not a single digit.
- Keep the normalized canonical type separately and reject conflicting name claims.
  No digest suffix or child-count encoding is used for these concrete type names.

Examples (T1/T2 are theoretical registered types/families, not production entries):

```text
hash<T1<T2>,T1>     -> hash_0T1_1T2_0T1_
hash<T1,T2<T1>>     -> hash_0T1_0T2_1T1_
hash<A<B<C>>,D<E>>  -> hash_0A_1B_2C_0D_1E_
```

There is no special single-argument encoding: an unmapped `vector<T>` is
`vector_0T_`. Names grow with the complete tree; this slice does not silently
truncate them or add a hash fallback.

## Escaping

Trailing-underscore exclusion alone does not disambiguate internal underscores.
Within structural node names, escape `z` as `zZ`, `_` as `zU`, and `:` as `zC`.
For example `some_0Type` becomes `somezU0Type`, and `scope::Type` becomes
`scopezCzCType`. Escaping z makes this reversible even for literal `zU` in a name.
The encoded node contains only letters/digits and starts with a letter, so the
leading decimal depth and underscore separators remain unambiguous. Root names
use the same escaping. Registered short names retain their ordinary underscores.

The initial tool accepts ASCII names with letter-leading namespace components;
leading underscores, trailing underscores and double underscores are rejected.
Concrete short names must be unqualified and cannot be C++ keywords. Unicode name
encoding is outside this bounded tooling implementation.

## Initial registry

| Family | Arguments | Notes |
| --- | --- | --- |
| vector | Value type | One argument |
| hash | Value type, key type | Missing key normalizes to string, matching runtime metadata |
| shared | Target type | One argument |
| unique | Target type | One argument |
| weak | Target type | One argument |
| int | Integer width | Recognized widths 8/16/32/64; non-type arguments need an explicit mapping |
| uint | Integer width | Recognized widths 32/64; explicit mappings |

| Canonical type | Concrete name | Runtime representation | Concrete facade |
| --- | --- | --- | --- |
| string | string | scpp::string_t | Implemented |
| vector<string> | vector_string | scpp::vector_t<scpp::string_t> | Implemented |
| int<32> | int32 | scpp::int_t<std::int32_t> | Implemented |
| int<64> | int64 | scpp::int_t<std::int64_t> | Implemented |
| uint<32> | uint32 | scpp::int_t<std::uint32_t> | Implemented |
| uint<64> | uint64 | scpp::int_t<std::uint64_t> | Implemented |
| bool | boolean | scpp::bool_t | Implemented |

Bare string/bool are registered type identities. This registry validates argument
counts/kinds and explicit integer widths; it does not decide hashability, pointee
admission or other language capabilities. Registering a family or resolving its
name is not proof that its middleware specialization exists.

Short names are accepted as aliases in tooling requests. Naming first expands
them to canonical types: `vector<vector_string>` and `vector<vector<string>>`
both become `vector_0vector_1string_`. Adding a short name for a nested type does
not change existing outer names. Numeric parameterizations are the bounded
exception: `int<32>` requires the explicit atom `int32`, so `vector<int<32>>` and
`vector<int32>` both become `vector_0int32_`. Unmapped numeric parameterizations
fail rather than inventing a numeric type-node spelling.

## Implementation and proof

```sh
python3 -B middleware/tools/type_names.py 'vector<string>' 'int<32>' 'hash<vector<string>>'
python3 tools/build.py middleware
python3 tools/build.py test
```

Only the explicit middleware build generates private native aliases from registry
entries with implemented public facades. Aliases remain in the middleware build
directory and never enter the published public headers. Handwritten method bodies
use these aliases, so the registry owns their native representation spelling.

The new string/vector_string facades expose copyable values, checked vector reads,
capacity reservation and value append. Reads return independent owned strings;
no container interior reference escapes. Strings support length-bearing input,
including embedded NULs; input ranges must be valid for the duration of the call.
Runtime storage, copy and bounds behavior remain behind compiled implementations.
Copy assignment follows the underlying runtime value contract. Move-specific APIs,
iteration and mutable indexed references are not implemented. Concrete scalar
facades and their export policy are documented in [common_surface.md](common_surface.md).

Both facades currently use one private implementation allocation per instance,
in addition to allocations required by their runtime payloads. This is an explicit
initial layout cost, not an optimized representation selected for high-volume
compiler records. Reusing layout-probed inline concrete storage is a later design
choice; avoid spreading this provisional allocation pattern through compiler data
before evaluating that choice. No handwritten allocator or parallel STL container
implementation is introduced.

Focused tests cover the two nesting examples, aliases/default normalization,
escaping, registry conflicts and rejected argument forms. Native tests cover
embedded NULs, copy independence, growth, ownership after container destruction,
and negative/end indexes. All consumer C++ still passes the template-token guard.

Canonical naming is owned by `runtime/tools/api_generator/names.cpp`. The Python
Registry and compiler entrypoints delegate to the C++ tool; relocated publications include the same tool in their
private generation SDK. Runtime-selected exports and application-generated demands
therefore share canonical identities and names.
