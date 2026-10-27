# C++ compiler bootstrap
Doc Status: supporting

This is the initial startup implementation for the my-try rewrite. It initializes
the static catalog model and optionally discovers, loads and tokenizes source files.
Parsing and semantic compilation are not implemented yet. See
[compact tokenization](docs/tokenization.md) for storage, revisions and validation. See [source discovery](docs/source_discovery.md) for its
configuration, retained-state contract and measured costs. See the
[startup slice](docs/startup_slice.md) for commands and the exact validation boundary.
The [discussion plan](../../specs/planning/cpp_compiler_rewrite.md) records direction.
Measured future memory work is tracked in [memory optimization debt](docs/memory_optimization.md).
Initial scope is Linux, Clang, Ninja and Mold, with Python 3 and CMake 3.20+ as build
orchestration tools. The development profile is C++23, O2, NDEBUG, static libraries,
without LTO or PCH.

## First setup

From this directory:

```sh
python3 tools/build.py runtime
python3 tools/build.py middleware
python3 tools/build.py test
python3 tests/build_boundary.py
```

`runtime` builds existing `scpp_runtime_core`, `scpp_json`, `scpp_filesystem` and
`scpp_datetime` and `scpp_tasks` targets using
the runtime's CMake project. JSON supplies symbols required by the compiled core
bundle. Optional providers and PHP-target configuration are disabled. Runtime CMake
also configures its test targets; this command neither builds nor runs them.
The existing runtime core is still an aggregate implementation unit. This bootstrap
does not refactor it or claim that every core facility is needed by the compiler.

`middleware` compiles the concrete facade against the selected runtime package.
`test` builds the executables and runs focused CTest checks. The separate runtime
smoke program exits
zero on success: it assembles text using the runtime text builder, checks positive
and negative equality, and roundtrips through the compiled runtime string pool.
The pool operation proves compiled runtime linkage; it is not a decision to use
that pool in future concurrent compiler stages.

## Normal edits

Follow the [C++ source style](docs/code_style.md) for handwritten compiler,
middleware and C++ test files.

The build driver defaults to eight parallel Ninja jobs; override with `--jobs N`.
Mold links use eight threads independently of the compile-job limit. Direct Ninja
invocations need `-j8` to use that same limit.

```sh
python3 tools/build.py build
# Or, after configuration:
ninja -C build/compiler -j8
ctest --test-dir build/compiler --output-on-failure
```

The compiler Ninja graph contains no runtime or middleware compilation rules.
The Clang launcher rejects active `template`/`typename` tokens throughout the
preprocessed compiler include closure, including macro expansions. The runtime publishes its own template-free `public/` include root; middleware
re-exports its scalar names without copying the headers. The broad runtime include
directory remains private to middleware/support implementations. Middleware and runtime may use templates.
This token check enforces the current template boundary, not the whole future
restricted-C++ or memory-safety policy. Native `int` exits and literal/argv character pointers remain host interfaces.
The [common runtime surface](docs/common_surface.md) defines concrete scalar types
and focused imports; collection sizes/indexes use `int64`, while event counters and timings use `uint64`.

`tests/build_boundary.py` checks no-op builds, source/header dependency isolation,
a main translation-unit rebuild, successful execution and transitive/macro template
rejection. It temporarily changes source timestamps and restores them; no source
contents are edited. Run it without simultaneous editing/builds. Timings are saved
in `build/compiler/boundary-evidence.json`.

## Explicit dependency updates

```sh
# After runtime changes:
python3 tools/build.py runtime
python3 tools/build.py middleware
python3 tools/build.py build

# After middleware-only changes:
python3 tools/build.py middleware
python3 tools/build.py build
```

Runtime and middleware each have one stable publication directory:
`dependencies/runtime/` and `dependencies/middleware/`. Their persistent Ninja
build trees are `build/runtime/` and `build/middleware/`. Changed dependency
sources rebuild only affected units; unchanged inputs reuse the publication.
Content hashes remain in `manifest.json`, not directory names.
`python3 tests/dependency_publication.py` checks incremental object reuse,
unchanged publication reuse, failed-build preservation, runtime compatibility
and interrupted-publication recovery in an isolated fixture.

Publication stages complete headers and libraries before replacing the current
directory. Build failures preserve the previous publication. The driver serializes
builds and publication, rolls back failed replacement, and recovers an interrupted
replacement on its next invocation. Do not run direct Ninja concurrently with a
dependency publication; direct Ninja does not acquire the driver lock.

Middleware records the runtime identity it was built against. Publishing a changed
runtime requires an explicit middleware update before the next compiler build;
the driver rejects a mismatched pair. Normal compiler builds never compile or
inspect dependency source trees for changes. Published header/library changes are
ordinary Ninja inputs and rebuild affected compiler consumers.

Receipts and package CMake references are relative to `dependencies/`. Generated
build trees contain absolute paths: remove `build/` after moving the project and
run `build` to recreate the compiler graph using the published libraries.
`python3 tests/package_relocation.py` verifies this, including paths with spaces.

To migrate the old hashed directories without dependency recompilation, run
`python3 tools/build.py stable-paths`. It preserves the selected binaries, updates
relative references and removes obsolete hashed publication/build directories.
CMake build trees cannot be moved, so the first subsequent explicit dependency
build creates its stable tree; later edits reuse it. Migration requires the selected
runtime to match the runtime used by the selected middleware.

Build output and publications are ignored. Removing `build/compiler` resets only
the compiler build; removing all `build/` retains the published dependencies.

## Concrete facade scope

The [concrete naming contract and registry](docs/concrete_names.md) record depth
encoding, short names and registered families. `string` and `vector_string` now
have value facades. `int32`, `uint32`, `int64`, `uint64` and `boolean` have
allocation-free concrete APIs owned by the runtime and re-exported by middleware.
Private native bindings are generated from the registry only during explicit
middleware builds. Focused naming and value-lifetime checks run with `test`.

The initial handwritten, noncopyable `textbuilder` owns one private implementation
allocation and delegates text storage to `scpp::str::text_builder`. It exposes no
runtime layouts or templates. Destruction is out-of-line and automatic. Literal
inputs are borrowed for a call only; no interior buffer reference escapes.

This small facade establishes the boundary. It is not a generic specialization
generator, optimized final layout, or complete string API. Broader concrete types
should reuse existing middleware functionality after their contracts are selected.
Keep new handwritten/generated code files below 200,000 bytes; existing imported
runtime files are outside this rewrite's file-size enforcement scope.

## Optional startup profiling

The [catalog validation baseline](docs/catalog_validation_profile.md) records
allocation counts and isolated probes. Its `catalog-validation-profile` target is
opt-in and excluded from normal builds/tests.

## AddressSanitizer and UndefinedBehaviorSanitizer

```sh
python3 tools/sanitizers.py
# Optional compile parallelism override:
python3 tools/sanitizers.py --jobs 8
```

This separate configuration uses `build/sanitizers/` for its build trees and
published dependencies. It instruments runtime, middleware and compiler code with
`-O1 -g -fsanitize=address,undefined -fno-omit-frame-pointer
-fno-sanitize-recover=all`. Normal dependency receipts, libraries and compiler build
configuration are unchanged. The first run rebuilds dependencies; subsequent runs
reuse the isolated build trees and unchanged publications.

The command runs the eight focused CTest checks and startup against the five real
default catalogs. ASan leak detection and immediate ASan/UBSan failure are enabled.
Python schema/generation checks are orchestration checks; sanitizer coverage applies
to the native test binaries and compiler processes they execute. Compile-only scalar
boundary probes remain syntax/template checks, not sanitizer runtime checks.

Passing these checks is evidence for exercised paths, not proof of memory safety or
thread safety. ThreadSanitizer and concurrent lifecycle testing are separate work.

Local run on 2026-10-26: all seven focused checks and real-catalog startup passed
with no ASan, UBSan or leak reports. Warm sanitizer run: 3.413 s, including
2.90 s for CTest. Runtime/middleware/compiler compile commands were checked for
instrumentation flags, normal dependency contents were unchanged, and the normal
compiler build remained a no-op. Expected-error fixtures explicitly reject
sanitizer diagnostics rather than accepting any nonzero exit as success.

The sandboxed attempt could not run LeakSanitizer's thread inspection (its ptrace
restriction); the successful run used approved unsandboxed execution with leak
detection still enabled. In similarly restricted environments, run this command
from a regular WSL terminal rather than treating a LeakSanitizer startup failure
as a program memory error or silently disabling leak checks.

### In-memory quick checks

The built executable accepts a single source directly, without catalogs or a
project configuration:

```sh
compiler/my-try-cpp/build/compiler/my-try-cpp -e 'echo "Hello";'
compiler/my-try-cpp/build/compiler/my-try-cpp - <<'PHS'
echo "Hello";
PHS
```

These commands currently **tokenize only**. Success returns a JSON record with
`state: "tokenized"` and `execution_available: false`; it does not execute `echo`
or validate program semantics. Parsing and execution are future stages.

The command owns an immutable source buffer and the ordinary compact token
sequence in memory. It performs no catalog loading, directory discovery, source
file creation, native build, or cache writes, and does not use `/tmp`. Existing
runtime and middleware are linked into the executable. No project configuration
is needed for this lexical-only stage. Extra options/program arguments are
rejected rather than silently ignored. Stdin is read until EOF, preserving NUL
bytes; source size and token length retain the tokenizer's limits.

Errors identify `<stdin>` or `<command-line>`, with a zero-based byte offset and
one-based line and byte column. LF, CRLF and CR line endings are recognized.
`--catalogs ... --source ...` remains the separate project startup path.


Thread-safety checks use a separate instrumented runtime, middleware and compiler:

```sh
python3 compiler/my-try-cpp/tools/sanitizers.py --mode thread
```

This runs 12 compatible tests; the scalar allocation-hook test is excluded because
its replacement allocation operators conflict with ThreadSanitizer. The default
`sanitizers.py` mode remains ASan/UBSan. See the
[memory safety audit](docs/memory_safety_audit.md) for sample coverage and limits.


## Compiler model and support

See [ownership, validation and build measurements](docs/model_support.md).

Compiler-owned token definitions now live in `model/include/scpp_compiler/`.
`support/` owns their runtime-backed storage and the dependent file records,
discovery/read adapters and token job batches. `compiler_support` is a normal
incremental target: model and support edits automatically rebuild affected objects.
Ordinary stage-source edits do not rebuild support. General middleware and runtime
still require explicit publication commands.

The middleware publishes focused scalar, string, container and JSON headers in a
private `detail/` native bridge alongside its public
headers and library. Only support implementations can include this bridge and the
runtime template headers; compiler/model consumers retain the template-free include
boundary. Support never compiles against live `middleware/src` or generated build
headers. The token's native scalar access implementation is in `support/src/token.cpp`;
its declaration and 8-byte layout are compiler-owned model definitions.

This slice preserves existing namespaces and public include spellings, token
layout, exceptions and ownership. File-record and batch interfaces moved together
because they depend on token storage; redesigning their data/worker responsibilities
and introducing AST storage are separate work.
