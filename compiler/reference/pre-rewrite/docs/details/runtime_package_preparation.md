# Runtime package preparation
Doc Status: supporting

Status: design decisions from the September 2026 discussion, with the first
standalone string package and version-1 metadata now implemented. Compiler-side
consumption has not started. The [foundations tracker](../planning/compiler_foundations.md#sequence-and-composition-gates)
owns sequencing and completion proofs. The [tool directory](../../src-runtime-preparation/README.md)
owns development navigation and output configuration.

## Ownership and lifetime of the tool

Develop the tool in `src-runtime-preparation/` until the compiler becomes
part of Simple C++. Prepare a selected Simple C++ version and target/toolchain
configuration once, reusing the package across applications and source files.
Application builds still compile their own code and link the prepared artifacts.

Generated files initially belong under `generated-runtime/`, configured
by `output_directory` in the tool's configuration. Relative paths resolve against
that configuration file; absolute paths are allowed. The output can move into
Simple C++ later without changing tool code. Generated output is ignored by Git.

## Local definitions are authoritative

The first version uses our own local exposure and semantic contracts. Importing
or reconciling existing Simple C++ metadata is deferred. Simple C++ supplies the
actual C++ headers and implementations; our definitions own the contract exposed
to this compiler. Later these definitions can move into Simple C++ and replace
the older compiler metadata, without requiring two competing sources of truth.

Use a `definitions/` folder under the tool for handwritten JSON. The proposed
initial grouping is `strings.json`, `console.json` and `conversions.json`;
`strings.json` and a scalar ABI demonstration in `scalars.json` are implemented. Its [version-1 contract](../../src-runtime-preparation/definitions/README.md)
was defined around the first proof. These are data groups,
not separate compiler paths or a requirement for one handler per file.

| Input | Responsibility |
|---|---|
| Local JSON definitions | Exposed identities, language signatures, provider declaration/operation selection, ownership and error policies |
| Simple C++ headers and implementations | Real types and executable behavior to consume |
| Clang with fixed build settings | Parsed declarations, target layout and compiled implementations/ABI lowering |

Do not hand-copy sizes, alignments, field offsets or lowered signatures into the
definitions. Validate semantic mappings against the selected implementation.
Missing declarations, unsupported adaptations and missing implementations must
fail preparation, not become advertised capabilities. A header declaration alone
does not establish a linkable implementation.

JSON selects supported operations and adaptation rules; it is not a language for
arbitrary wrapper code. Required executable adaptations must have real bridge
implementations. Mechanical bridges may be generated through reusable rules;
custom runtime behavior retains a named implementation owner. Adding a definition
must not require dispatch by fixture identity or concrete runtime name in consumers.

The extension rule is configuration-only for additional elements using supported
contracts. Type names, member names and exposed operation identities belong in
definitions. New calling or lifetime patterns require a reusable tool capability
once, with a matching metadata contract and proof; subsequent elements use that
capability through configuration. Do not add per-type or per-method exceptions.
See the [definition extension rule](../../src-runtime-preparation/definitions/README.md#extension-rule).

Entity identities must not rely on hash uniqueness. Exported symbols reversibly
encode their full components with `_X_` separators, doubled literal underscores
and `_xHH_` byte escapes. The [symbol spelling contract](../../src-runtime-preparation/definitions/README.md#generated-symbol-identities)
owns the exact format. Internal compiler tables may allocate compact IDs in their
own scope; output fingerprints remain separate change-detection information.

## Package outputs and consistency

The package contains:

- Type metadata: identity, representation and relevant authoritative layout facts.
- Operation/ABI metadata: language meaning and signature, lifetime obligations,
  implementation identity, and prepared argument/result passing arrangements.
- Matching LLVM implementations: readable `.ll` and compatible bitcode, identifying
  ordinary and ThinLTO-prepared variants explicitly.
- A manifest tying definitions, target, toolchain/configuration, dependencies and
  artifact identities to the same prepared package.

Generate metadata and code from consistent inputs, validate advertised bindings,
then publish them together. A failed preparation must not pair new metadata with
old code. Select work before generating it; reuse valid outputs when the relevant
definitions, implementation/header dependencies and build settings are unchanged.
Keep the current package at the stable `package/` path in each configured output
directory. Hashes identify inputs and contents in metadata, not directory names.
Build and validate replacements privately, replace the whole package directory
under the output lock, update the current pointer, then remove the temporary
backup. Publication failure restores the previous directory. Failed generation
preserves the current package. Successful reuse also clears legacy hashed package
directories. Consumers must hold the output lock in shared mode while using
package files; directory and pointer replacement are separate filesystem steps.
The tool README describes the current schema, dependency records and package
replacement. They remain scoped to this first slice rather than a general
C++ interface exporter.

## Storage is separate from ABI passing

Do not force nonnumeric types to use separately allocated pointer handles.
An inline runtime value has authoritative size/alignment; a call can receive its
address or construct into caller-provided result storage. Passing a pointer does
not imply that the object itself is heap allocated. Private runtime fields need
not become source-visible. Construction, copying and destruction remain explicit
operations; raw byte copying is not a substitute for nontrivial C++ copying.

The existing Simple C++ string bridge allocates an object and returns a handle.
That is one existing bridge design, not a universal representation rule. The
planned string consumer uses runtime-defined inline storage and lifecycle calls.
Character-buffer allocations are separate from storage for the string object.

A completed isolated probe of the actual `scpp::string_t` header used Clang 18.1.3,
C++23, x86_64 Linux and installed libstdc++ 13 headers with the new string ABI.
Clang reported one `std::string` field, size 32 and alignment 8, plus nontrivial
copy/destruction and available copy/move constructors. Native checks verified
copy isolation after mutation for short and long strings. LLVM used local record
storage and a hidden result-storage pointer for a function returning the string.
These are measured facts for that configuration, never constants for other builds.
Declaration JSON and layout were separate outputs; the filtered JSON dump contained
multiple roots. The probe establishes feasibility, not a completed provider package
or compiler integration. Clang's raw AST dump is not our published metadata schema.

## Initial surface and first proof

The agreed small surface is strings, echo, console line input, explicit integer
to string conversion, and strict string to integer conversion. String operations
start with literals, concatenation and byte length. Include required lifecycle
operations even when they are not directly exposed to source programs.

Invalid input stops with a clear runtime error. Keep recoverable errors, retries,
exception-language machinery, generic containers and broad string APIs deferred.
The implemented [console contract](runtime_console.md) removes LF/CRLF, accepts a
final unterminated line, and terminates clearly on empty EOF/read errors. Generated
bridges catch provider exceptions and terminate with the operation diagnostic.

Implemented first vertical slice: select `scpp::string_t` and byte length in local
definitions, extract declarations/layout, prepare construction, byte-length and
destruction bridges, export matching metadata/LLVM, and run an isolated consumer.
Unchanged inputs reuse the outputs. Generic copy support is now implemented;
the default string package now exposes that binding. This first slice does not integrate the package into the compiler or
implement the entire console/conversion surface. The
[standalone test](../../src-runtime-preparation/tests/run.php) also proves
a second C++ type through the same adapters, input invalidation, honest failures,
publication isolation and direct-to-linker LTO consumption. Runtime byte counts
remain an explicitly measured unsigned `size` type. The later source-visible
byte-length wrapper checks conversion to language `int`; it does not reinterpret
that measured type. The bounded [console surface](runtime_console.md) is now
implemented through provider definitions and local provider headers.

The [LTO feasibility gate](../planning/compiler_foundations.md#provider-artifacts-and-lto-feasibility)
remains separate: test ordinary linking, full LTO and ThinLTO, including real
cross-module optimization and one body change with retained unchanged inputs.
Reusing provider IR does not guarantee reuse of optimized machine code. Production
LTO integration and performance work remain deferred; normal builds stay at `-O0`.

## Compiler consumption follow-up

The [package adapter](runtime_package_consumption.md) consumes direct integer
functions and [no-cleanup inline construction/borrowing](inline_runtime_storage.md).
[Cleanup](runtime_cleanup.md) and metadata-authorized [local copy construction](runtime_copy_construction.md)
are also implemented. [Literal construction and echo](runtime_string_literals.md)
now consume the package through generic supported ABI contracts.
