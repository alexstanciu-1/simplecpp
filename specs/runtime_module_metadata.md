# Runtime module metadata — version 1
Doc Status: normative
Status: bounded v0.2 catalog format

Runtime-owned catalogs live in `runtime/metadata/modules/<module>.json`, outside any
compiler implementation. They describe callable semantics independently of C++
bindings. This first slice describes concrete free-function contracts; it is not
a declaration of complete runtime coverage or a replacement of v0.1 metadata yet.

## Shape

Every module object requires exactly these fields:

- `version`: integer `1`.
- `module`: nonempty lowercase ASCII letters/digits/underscore name, matching its filename.
- `dependencies`: ordered array of module names, without duplicates or cycles.
- `functions`: ordered array of function contracts.
- `cpp`: array of separate C++ bindings, joined to functions by `id`.

A function requires `id`, `name`, `parameters`, and `returns`. Identities are globally
unique across selected modules. Source names may repeat for overloads with different
parameter types; return type or passing mode alone does not distinguish overloads.
Each parameter requires a unique `name`, a `type`, and `passing` (`value`, `reference`,
or `readonly_reference`). These names are metadata, not named-argument support.
Reference-bearing signatures must have an unoverloaded source name in this slice;
ordinary call admission still checks reference places and access.

A type is either a source-exposed registered name such as `"string"`, or an application:

```json
{"name": "result", "arguments": ["dynamic"]}
```

Arguments recursively use this same representation. Resolution delegates identity,
arity and formation checks to the type registry; JSON contains no canonical type IDs,
source ASTs, C++ type spellings or compiler callbacks. Void is not a parameter type.

A C++ binding requires `id`, `symbol`, and `header`. Each function has exactly one
binding in its declaring module, and no orphan bindings are allowed. Symbols are
qualified names, not C++ expressions; headers are include-path spellings, not source
snippets. These are trusted toolchain catalogs, not executable user extensions.
The backend validates and consumes bindings; semantic call preparation does not.

Unknown fields, wrong JSON kinds, duplicate decoded keys, unsupported versions,
unresolved types, invalid template applications and duplicate contracts are errors.
There is no silently ignored future-schema field.

## Loading, ownership and publication

The composition root supplies a catalog directory and selected modules. Load each
selected/dependent module once, dependency-first, directly through the shared JSON
pull reader into typed metadata. No mixed/dynamic or generic document tree is built.
Complete document/schema validation precedes publication; failed loading/configuration
does not replace the previous configuration.

Metadata is retained in memory as immutable-by-contract startup input. Resolve
signatures into a private callable inventory against the current canonical type
registry; only validated identities enter language scope. C++ bindings are installed
separately by the backend under coordinator sequencing. Ordinary compilation and
incremental updates perform no metadata IO or JSON parsing.

Semantic registry resets reuse retained typed metadata but recreate canonical type,
callable and backend-binding identities. Old targets cannot reuse new bindings merely
because their string IDs match. Catalog edits take effect in a new compiler session;
live catalog replacement/hot reload is outside this slice.

The current my-try host selects `core` and `json`, preserving the previously enabled
JSON API. Native validation snapshots these same files and supplies their location
explicitly; installed/distributed compiler packaging must likewise supply resources.
Runtime-link/build-module policy remains with existing build owners. Catalog dependencies
order metadata loading; they do not yet configure linker inputs automatically.

## Initial contents and next boundary

- `core`: `strlen(string): int`, counting bytes through `scpp::str::byte_length`.
- `json`: existing dynamic/mixed decode contracts and both encode overloads, preserving
  Result wrapping and family separation.

Parameterized functions (`count<T>`), new runtime type declarations, optional/default
and variadic arguments, reference-return lifetime contracts, static runtime factories,
profile availability and the rest of the runtime inventory are not encoded by this
version's bounded consumer yet. Extend the shared models before enabling these rows;
do not generate fake concrete overloads or function-name-specific preparation paths.
