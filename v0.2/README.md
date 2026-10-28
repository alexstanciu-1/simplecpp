# v0.2 foundation

Doc Status: supporting

This is the active destination for explicitly reviewed reuse slices. Files outside
this directory are reference material, never build inputs. Normal external compiler,
standard-library and build-tool dependencies are permitted.

Implemented slices are native JSON parsing, the generator's metadata value model,
and metadata-driven integer, boolean/null and float64 APIs with explicit scalar casts.
Compiler integration and broader runtime imports are not implemented here yet.
Standard-library types and private templates are appropriate for tool support;
generated runtime consumer headers remain template-free.

Build from this directory with the default preset: Ninja, 8 jobs, Clang,
libc++ and mold. Ninja/Clang/mold must be installed and discoverable; configuration
rejects a different generator/compiler or missing mold.

```sh
cmake --preset default
cmake --build --preset default
ctest --preset default
```

The build directory is `build/clang/` (ignored by Git). Generated public API files
remain in `runtime/generated/`. The build preset sets eight jobs; the configure
preset also sets `CMAKE_BUILD_PARALLEL_LEVEL=8`. Project executables and the separate
generated-consumer link proofs explicitly use the discovered mold linker. The
`ubsan` configure/build/test presets retain that toolchain and eight build jobs while
enabling undefined-behavior checks with recovery disabled.

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

## Integer generation slice

`runtime/metadata/runtime.json` owns integer facts and policies;
`publication.json` selects int32/int64, boolean/null and the requested integer
operators, including optional native-int adapters. The native
generator validates metadata, creates a typed plan and emits concrete constructors,
operators, short names, implementations and inventories under `v0.2/runtime/generated/`.
`prepared.json` exposes resolved conversion and execution facts.
The generator builds without linking the generated runtime.

```cpp
#include <scpp/short_names.hpp>
#include <scpp/native_bridge.hpp> // Optional native int arithmetic.

int32 a{20};
int64 b{3};
int64 sum = a + b;
auto incremented = a + 1; // int32 on a target with 32-bit int.
auto reversed = 1 + b;    // int64.
int32 narrow = static_cast<int32>(b);
```

Consumers link the CMake target `scpp_runtime`; only the generated public
include root is exported. Constructors are explicit and checked, including native
inputs. Mixed addition uses int64. This slice preserves native signed-addition
overflow limits; it does not introduce checked arithmetic. Conversion failure uses
standard `std::out_of_range` until a runtime error surface is adopted.

Native operands are mapped by their complete type range into the existing domain
promotion policy. The default bridge exposes only int in
both directions; it does not inspect literal values. Unrequested fundamental
inputs have exact deleted overloads, preventing accidental conversion to an
admitted operand type. These declarations add no runtime implementations. Bridge functions explicitly cast
to runtime types and call the core same-type operator. Constructors stay explicit:
write `int64 x{12}`, not `int64 x = 12`. Restricting native int values to literals
is a coding convention; C++ overload resolution cannot enforce that distinction.

Run generation independently from this directory:

```sh
./build/clang/scpp_api_generator \
  runtime/metadata/runtime.json runtime/metadata/publication.json \
  runtime/generated
```

Add `--check` to verify without writing. Output is native-target-specific; no
cross-compilation or sealed ABI publication is provided yet. The initial CMake
integration expects the default header grouping. See the
[integer slice contract](specs/integer_slice.md) for exact scope, provenance and
unsupported inputs. This is an adapted extraction of existing scalar preparation,
class emission and behavior owners, not a parallel engine using the old tree.

## Validation checkpoint — 2026-10-28

- Clang 18/libc++ release build and focused tests passed.
- A standalone copy of this directory under `/tmp` built and passed libc++ UBSan
  tests with recovery disabled. Its compiler dependency inventory contains no
  original repository paths.
- Clang/libstdc++ ASan+UBSan tests passed with leak detection disabled: LeakSanitizer
  cannot run under the current traced execution environment. No leak-proof claim.
- libc++ ASan validation remains blocked by an allocation/deallocation mismatch in
  the installed exception libraries. A standalone program containing only a
  `std::runtime_error` throw/catch reproduces the same failure without this reader.
  The reader was not changed to hide it, and the mismatch check was not disabled.

The metadata value slice passed the same release and isolated libc++ UBSan checks,
plus Clang/libstdc++ ASan+UBSan with leak detection disabled. Both focused tests
pass together. The libc++ ASan and leak-check limitations above remain unresolved;
they are not counted as successful checks. Isolated compilation inputs contain no
old-tree dependencies.

The integer/native-operand slice passes all eight focused tests in Clang/libc++ release and in
isolated libc++ UBSan and Clang/libstdc++ ASan+UBSan builds (same leak-check limitation).
Proofs cover metadata negatives, the 56-pair integer planning domain, native range
adaptation, the then-published twenty compiled addition overloads, checked narrowing,
combined-header overload resolution, restricted-publication compile negatives,
header independence, fresh staging determinism and read-only freshness. This does not establish compiled
coverage of every integer specialization or integration with the PHS compiler.

The earlier optional-bridge refinement passed all eight release tests. That default
publishes eight supported overloads: four core and four native-int adapters. Proofs
cover core-only exclusion, bridge composition, explicit construction, checked casts,
unsupported native inputs and the required same-type forwarding dependency.
Earlier sanitizer checkpoints above predate this refinement.

The next binary-operation slice adds metadata-bound `- * / % & | ^`, preserving
explicit construction and the separate native-int bridge. All nine release tests
pass, including an independent publication compiling every integer width and the
eight binary operators. The default requests same-type operations plus the prior
mixed addition pair; it does not expand every mixed operator combination. See the
[active family plan](specs/planning/runtime_type_operations.md) for the full goal,
remaining groups, compiler boundary and completion requirements.

This binary-operation checkpoint also passes all nine tests in a fresh standalone
Clang/libc++ UBSan build with recovery disabled. The build starts without generated
artifacts; compiler dependency records contain no original-repository paths.

## Current value and operation-form checkpoint

The metadata root is now `runtime/metadata/runtime.json`; the integer-only name and
driver have been retired. One runtime plan prepares types, source mappings and
operations. Consumers link `scpp_runtime`. Generated `scpp/values.hpp` provides
`bool_t`/null; integer comparisons return `bool_t`. Integer unary, shifts, compound
assignment and prefix/postfix operations are now generated from their own forms.
The default has 69 core and 48 optional native-int integer operator signatures.
The full-publication proof compiles all eight integer types, all 64 comparison pairs,
shifts, unary and mutation behavior, including checked write-back preservation.
See [the value contract](specs/values_slice.md) for precise behavior and remaining
scalar-cast work at that checkpoint. Floating support is recorded below; strings
and vectors remain later slices in the active goal.

The value/form checkpoint passes all eleven tests in a fresh standalone Clang/libc++
UBSan build with recovery disabled. Generation starts from no derived files, and
compiler dependency records contain no original-repository paths.

The public boolean name is `bool_t`. `int_t` aliases the configured signed integer
default (currently int64), using the same metadata fact as source int. These aliases
introduce no wrappers or duplicate overloads. `scpp::float_t` aliases the floating default (float64). It is not globally imported
by short_names.hpp because C math headers have their own float_t typedef.

## Floating checkpoint

Float64 now supports arithmetic, comparison, unary and mutation operations through
the shared prepared model. Explicit integer/float/bool_t casts are generated from
metadata. Wide integer/float expressions require an explicit float cast; narrow
integer types may participate in requested mixed operations. See the
[floating contract](specs/floating_slice.md) for conversion bounds, NaN/zero behavior,
alias rules and the exact supported/rejected surface. String/vector slices and
compiler integration are still outstanding.

Validation for the floating/build checkpoint: all thirteen tests pass in the default
release preset and a fresh standalone UBSan copy, both using Ninja, eight build jobs,
Clang/libc++ and mold. ELF .comment records confirm mold linked both the generator
and the independent consumer proof. The isolated compiler dependency inventory has
no original-repository includes. Generated freshness and alias composition pass.
