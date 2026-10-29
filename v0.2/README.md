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
concrete vectors, typed hashes, nullable/result carriers and concrete record ownership/value APIs.
The [API summary](specs/runtime_api_summary.md) and linked family contracts define
supported behavior. [Typed hashes](specs/hash_slice.md) are generated on request; mixed/dynamic remain future runtime work.

The optional [datetime module](specs/datetime_module.md) provides eleven clock,
sleep, UTC and host-local operations. Enable it with `-DSCPP_ENABLE_DATETIME=ON` when
configuring, then link `scpp_datetime`; the core has no datetime dependency.
Source compiler integration and PHP compatibility wrappers remain deferred.

The sole API generator is [PHP 8.5 tooling](tools/api_generator/README.md).
It resolves metadata and explicit native C++ probe facts into public headers,
C++ definitions and a prepared API description. The runtime remains C++;
generated public headers remain template-free.

The [PHP compiler](compiler/README.md) is being rebuilt incrementally from
`compiler/my-try/`. Source loading and flavor-aware tokenization work today.
It is independent of the runtime build; Rust has been retired.

Runtime builds require 64-bit PHP 8.5 or later and Python 3.10+ (including with
tests disabled). No Cargo, Rust toolchain, Composer packages or old-tree files
are needed for generation.

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
records the retired C++ contract. Frozen artifact fixtures and PHP/Python tests now own
its regression evidence; no C++ generator binary is required.

`runtime/metadata/runtime.json` owns identities, policies and operation bindings.
`publication.json` selects concrete types/operators/adapters. PHP tooling prepares
one resolved API description and emits public headers, C++ definitions, prepared.json and inventory.
The runtime links only the emitted API and handwritten private runtime algorithms.
Native layout facts come from the standalone C++ probe, never from a legacy runtime.

Generation and target facts are build-local, so Release and UBSan do not share writable
artifacts. Generated files and CMake outputs remain untracked. The configured
launcher invokes PHP with the current build's explicit facts/context:

```sh
./build/clang/scpp_api_generator runtime/metadata/runtime.json \
  runtime/metadata/publication.json build/clang/generated --check
```

Remove `--check` to publish. Preparation validates before writing; check mode is
read-only. Obsolete inventory entries reject. Publication is per-file rather than
transactional. Cross-compilation and sealed ABI packages remain outside this slice.

PHP metadata parsing preserves large/raw number tokens, rejects decoded duplicate
keys and limits nesting to 128 containers. This is a tooling boundary, not a
runtime JSON module port. See the generator README for the current implementation
and validation commands.

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
