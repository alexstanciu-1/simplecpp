# v0.2 foundation

Doc Status: supporting

This is the active destination for explicitly reviewed reuse slices. Files outside
this directory are reference material, never build inputs. Normal external compiler,
standard-library and build-tool dependencies are permitted.

Implemented slices are native JSON parsing, the generator's metadata value model,
and metadata-driven integer, bool_t/null, float64, string and concrete vector APIs
with explicit checked casts and selectively published operators/adapters. Nullable
and result-family carriers now add metadata-selected checked payload extraction.
The [ownership/value foundation](specs/ownership_slice.md) generates concrete shared, weak
and unique handles, const-pointee variants and inline values for registered records
in a separate proof publication. Value copy/move capabilities follow their payload.
Their private standard smart pointers live inline, without per-handle heap wrappers.
The [Rust compiler foundation](compiler/README.md) provides byte sources, spans,
compact tokens, immutable token-sequence publication, PHS scanning, diagnostics and
source/token inspection commands. Its replaced C++ foundation has been removed. Cargo builds independently with
eight jobs and mold, without a C++ runtime or generator-binary dependency.
It uses only the Rust standard library. Parsing and semantic compilation remain pending.
The runtime stays C++; API generation
and physical pre-lowering are migrating to a shared Rust tooling library. The
[G2 tooling](tools/api_generator/README.md) provides that library, JSON loading,
scalar/string/vector/carrier/ownership preparation and a small C++ layout probe.
Rust preparation covers the current metadata families. The C++
generator remains the production path; Rust scalar emission has artifact and consumer parity; other family emitters remain pending. Compiler and tooling migration follow the
[migration plan](specs/planning/rust_compiler_migration.md).
Standard-library types and private templates are appropriate for tool support;
generated runtime consumer headers remain template-free.

Build from this directory with the default preset: Ninja, 8 jobs, Clang,
libc++ and mold. Ninja/Clang/mold must be installed and discoverable; configuration
rejects a different generator/compiler or missing mold.

```sh
cmake --preset default
cmake --build --preset default
ctest --preset default
./build/clang/scpp_runtime_demo
```

The build directory is `build/clang/` (ignored by Git). Generated public API files
remain in `runtime/generated/`. The build preset sets eight jobs; the configure
preset also sets `CMAKE_BUILD_PARALLEL_LEVEL=8`. Project executables and the separate
generated-consumer link proofs explicitly use the discovered mold linker. The
`ubsan` configure/build/test presets retain that toolchain and eight build jobs while
enabling undefined-behavior checks with recovery disabled.

The [plain C++ example](examples/runtime_demo.cpp) combines numeric, bool/null,
string and vector APIs; it does not exercise carriers. It prints `Ada: 21; ratio=7`.
The [API summary](specs/runtime_api_summary.md) lists
supported/rejected operators and casts, ownership boundaries and deferred language
decisions. The active hash/mixed/dynamic follow-on is a separate reviewed port;
none of those implementations has been imported yet.

## Generator and artifact ownership

The [G1 generator inventory](specs/planning/api_generator_contract_inventory.md)
records the current C++ contract and reproducible parity checks for the planned
Rust port. Python 3.10+ is required when BUILD_TESTING is enabled; production
generation remains the existing C++ executable.

runtime/metadata/runtime.json owns identities, conversion policies and operation
bindings. publication.json selects concrete types, operators, native adapters and
collection specializations. tools/api_generator prepares a typed plan and emits
runtime/generated/public/scpp headers, src/runtime.cpp, prepared.json and inventory.
The generator builds independently of the runtime; consumers link scpp_runtime,
which exports only the generated public include root.

Generated code, plans, inventories and compilation outputs are local artifacts,
ignored by Git. Commit metadata, generator sources, handwritten primitives, tests
and examples. A fresh checkout recreates its API through the normal build.

From this directory, generation can also run independently:

```sh
./build/clang/scpp_api_generator \
  runtime/metadata/runtime.json runtime/metadata/publication.json \
  runtime/generated
```

Add --check for read-only freshness. Generation validates before writing and rejects
retired output paths in an existing inventory; use an empty staging output when
changing artifact names. I/O publication is not transactional. Output is specific
to the native generation target. CMake currently expects the default header groups.
Cross-compilation and sealed prebuilt ABI packages are not provided.

## JSON tooling and bounded reuse

The following describes the production C++ generator's custom JSON implementation.
The new Rust tooling uses Serde/serde_json behind a metadata adapter that preserves
raw number tokens, decoded duplicate-key rejection and the 128-container limit.
See the [tooling boundary](tools/api_generator/README.md).

The reader owns its input and is neither copyable nor movable. It validates UTF-8,
JSON grammar, decoded duplicate keys and depth (default 128, permitted 1..256).
Numbers can be read as original token text without floating conversion; signed
64-bit extraction is separately checked. Parse/protocol failures throw `read_error`
with category, byte position and path, and make the reader terminal. Allocation
failures are not translated. Consumers must finish the document before publishing
results. The reader has no Result/shared-pointer language adapter.

`tools/api_generator/value.*` owns the metadata tree and deterministic serialization.
It reuses the former generator's value model (null, text, number, boolean, array,
object), preserving number token spelling rather than converting through floating
point. Object keys serialize in map order; this is deterministic output, not a
numeric-normalizing canonical JSON format. Parsing accepts an optional diagnostic
filename and completes validation before returning a tree. Output depth is limited
to 128 containers, matching the default input limit.

The representation remains tool-private with directly accessible fields for future
generator reuse. Checked accessors reject non-object access; null can be initialized
as an object through `operator[]`. Serialization rejects inconsistent field kinds,
invalid booleans/number tokens and invalid UTF-8, and escapes strings independently
of the process locale. This is a local strengthening of the imported model, not a
new general-purpose runtime JSON API.

Reuse provenance: repository revision `a2dbdb018`,
`runtime/include/modules/json/detail/reader.hpp` and the native cursor portion of
`runtime/include/modules/json/pull_reader.hpp`. These paths are historical references.
Their lexical/cursor algorithms are adapted under `tools/support/json/` using
`std::string`, `bool`, native depth and `std::int64_t` values. The old language adapter
is excluded. Focused proof cases are adapted/extended from
`tests/runtime/native/test_json_pull_reader.cpp`; no synchronization with old copies
is required. Future imports require agreement on their bounded responsibility.

The second reuse slice adapts `runtime/tools/api_generator/value.hpp` and
`value.cpp` from the same revision. It replaces runtime-reader adaptation with the
native reader and strengthens access/output checks.

## Validation

Focused tests cover parser/model behavior, preparation rejection, deterministic
emission, generated freshness, independent public headers and valid/invalid overload
composition. Separate publications exercise every integer width, reduced string
APIs, core-only vectors, additional vector element families and explicit elementwise
casts. The runnable example covers the numeric, bool/null, string and vector families;
carriers have separate consumer and publication tests.

Fresh standalone Clang/libc++ UBSan builds pass without old-tree dependencies or
pregenerated files. String/vector ownership and partial-cast failure consumers pass
Clang/libstdc++ ASan+UBSan. Leak detection is disabled because LeakSanitizer cannot
run in the traced execution environment. libc++ ASan has an independently reproduced
allocation/deallocation mismatch in the installed exception libraries; its checks
were not suppressed. Neither limitation is counted as a successful sanitizer check.

See the family contracts in the API summary for current behavior and the
[compiler README](compiler/README.md) for compiler ownership and validation. Completed
plans and milestone evidence are retained under [specs/archive](specs/archive/README.md).

## Nullable and result carriers

```cpp
nullable_int64 maybe{int64{20}};
result_int64 computed{int64{3}};
int64 sum = maybe + computed;
```

The default publishes all four carrier families for int64, bool_t, string and
vector_string. Payload construction stays explicit; extraction is an implicit,
checked one-layer value copy. Empty/error/sentinel extraction throws. See the
[carrier contract](specs/carrier_slice.md) for states, boolean distinctions,
scpp::error_t and the optional explicit-only extraction policy. No new lifted
arithmetic overload matrix or source compiler integration is introduced.
