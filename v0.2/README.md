# v0.2 foundation

Doc Status: supporting

This is the active destination for explicitly reviewed reuse slices. Files outside
this directory are reference material, never build inputs. Normal external compiler,
standard-library and build-tool dependencies are permitted.

The [runtime rewrite objectives](specs/planning/runtime_rewrite_objectives.md)
record the agreed architectural purpose: remove runtime complexity that compensated
for the type-blind compiler, preserve Simple C++ contracts, and reuse proven v0.1
implementation and knowledge, including modules. The note also records the intended
compiler/runtime boundary, core milestone and open design decisions.

The runtime implements metadata-selected integers, bool/null, float64, strings,
concrete vectors, nullable/result carriers and concrete record ownership/value APIs.
The [API summary](specs/runtime_api_summary.md) and linked family contracts define
supported behavior. Hash/mixed/dynamic remain future runtime work.

The sole API preparation/generation implementation is now the shared Rust
[tooling library](tools/api_generator/README.md). It emits the C++ runtime API from
resolved plans and explicit native C++ probe facts. The C++ generator and its private
JSON implementation have been retired after exact artifact parity proofs. The
runtime remains C++; generated public headers remain template-free.

The [Rust compiler foundation](compiler/README.md) provides source bytes, spans,
tokens, immutable token sequences, flavor-aware scanning, C++/PHP/JS parsing into
a shared AST, and diagnostics. Semantic compilation remains a future slice. The compiler uses only the Rust standard library. Rust tooling builds independently; CMake
invokes Cargo for runtime generation, with no reverse dependency.

Install Rust 1.99.1 and put Cargo on PATH, then run `cargo fetch --locked` once to
populate the dependency cache. Normal CMake/Cargo builds use locked offline packages.
Python 3.10+ is required for the native layout probe even without tests.

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
live in `build/clang/generated/` (or the selected build directory). The build preset sets eight jobs; the configure
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

The [historical G1 inventory](specs/planning/api_generator_contract_inventory.md)
records the retired C++ contract. Frozen artifact fixtures and Rust tests now own
its regression evidence; no C++ generator binary is required.

`runtime/metadata/runtime.json` owns identities, policies and operation bindings.
`publication.json` selects concrete types/operators/adapters. Rust tooling prepares
one typed model and emits public headers, C++ definitions, prepared.json and inventory.
The runtime links only the emitted API and handwritten private runtime algorithms.
Native layout facts come from the standalone C++ probe, never from a legacy runtime.

Generation and target facts are build-local, so Release and UBSan do not share writable
artifacts. Generated files and Cargo/CMake outputs remain untracked. The configured
launcher invokes Rust with the current build's explicit facts/context:

```sh
./build/clang/scpp_api_generator runtime/metadata/runtime.json \
  runtime/metadata/publication.json build/clang/generated --check
```

Remove `--check` to publish. Preparation validates before writing; check mode is
read-only. Obsolete inventory entries reject. Publication is per-file rather than
transactional. Cross-compilation and sealed ABI packages remain outside this slice.

Rust JSON metadata parsing uses Serde behind a bounded adapter preserving raw number
tokens, decoded duplicate-key rejection and a 128-container depth limit. This is a
tooling boundary, not a runtime JSON module port. Historical JSON reuse and its
retirement are documented in the migration record.

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
