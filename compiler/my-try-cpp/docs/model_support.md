# Compiler model and automatically built support
Doc Status: supporting

Implemented 2026-10-27. This slice starts with token representation and storage;
AST storage and parser implementation remain future work.

## Ownership and dependencies

- `model/include/scpp_compiler/token.hpp` owns the existing token class and its
  private packed fields. `token_kind.hpp` owns the existing tag vocabulary.
- `support/` owns token buffers, token sequences/builders and their native runtime
  adaptation. The token constructor/accessor definitions live in `support/src/token.cpp`
  because native scalar extraction is intentionally private to the runtime bridge.
  Their constant-time implementation, exception behavior and 8-byte token layout
  are unchanged. No companion accessor class or new allocations were introduced.
- File-record storage, discovery, source-read batches and token jobs depend on the
  token interfaces, so they moved from middleware into support in the same slice.
  Their APIs, namespaces, algorithms and ownership contracts are unchanged. A later
  slice may separate their model records and stage processing further.
- General middleware retains scalar/string/container/JSON/textbuilder facades and
  host adaptation. It no longer includes compiler token or file-record definitions.
- Compiler stages consume template-free model/support interfaces. Only support
  implementation units include runtime template definitions. The existing compiler
  include-closure check remains enabled for stage and test compilation.

Support uses the published middleware's private `detail/` bridge, copied together
with its matching library and headers. It does not include mutable middleware
source or build-directory generated headers. The bridge contains scalar access,
string state/access and generated native bindings; it is not a compiler-facing API.

`compiler_support` is a normal CMake/Ninja target linked with the compiler. Compiler
builds automatically rebuild affected support objects. Runtime and middleware
publications still require explicit `tools/build.py runtime` / `middleware` commands.
An older middleware package without the bridge is rejected during configuration.

## Validation

- WSL normal suite: 13/13 passed; dev002 normal suite: 13/13 passed.
- Isolated ASan/UBSan with leak detection: 13/13 passed, 8.64 seconds.
- Isolated ThreadSanitizer: 12/12 compatible tests passed, 10.28 seconds; the existing
  scalar allocation-hook exclusion remains unchanged. No sanitizer findings.
- Expanded `tests/build_boundary.py` proves that touching a support source rebuilds
  support, touching the token model header rebuilds dependent support and compiler
  units, and touching a compiler source does not rebuild support. Live runtime and
  middleware source/header edits still cause no automatic dependency rebuild.
- Dependency archives remain unchanged during compiler rebuilds. Active templates
  in compiler include closures remain rejected.

## Build measurements

Release Clang, Ninja, Mold; warm filesystem caches; single measurements, not medians.
Full rebuilds remove all compiler and support objects and both local static archives,
then build only `my-try-cpp`. They exclude tests, configuration and runtime/middleware
publication. There are now 17 compilation units: 10 stage/entry units plus 7 support
units. The former compiler-only measurement excluded these support implementations.

| Machine | Jobs | Full compiler + support rebuild |
| --- | ---: | ---: |
| WSL | 8 | 10.771 s |
| dev002 | 8 | 2.616 s |
| dev002 | 32 | 2.656 s |

WSL boundary-check measurements (the default target also relinks affected test
executables): no-op 0.010 s; main.cpp touch 0.364 s; token_sequence.cpp touch 2.093 s;
token model header touch 7.346 s. Keep 8 jobs as the default. Layout changes honestly
recompile dependent template storage; ordinary stage edits retain fast iteration.

Local evidence is under `build/compiler/` (`boundary-evidence.json`,
`support-build-times.json`, `support-final-tests.log`) and each sanitizer build's
`support-refactor.log`. These are build artifacts, not tracked sources.

## Focused native bridge — 2026-10-27

The aggregate native bindings header has been replaced with four generated private
headers. Registry mappings declare their bridge family and any additional runtime
header dependencies:

| Header | Responsibility |
| --- | --- |
| `runtime_scalars.hpp` | Boolean and fixed-width integer aliases |
| `runtime_string.hpp` | Runtime string alias |
| `runtime_containers.hpp` | Concrete string-vector alias and required element header |
| `runtime_json.hpp` | JSON node and JSON-vector aliases |

Scalar access and string state include only their own families. Vector-string and
JSON implementations explicitly include their bindings. Support files that own
runtime vectors now include `scpp/vector_t.hpp` directly. The publisher copies all
four headers into the private bridge with the matching middleware library; public
compiler interfaces remain unchanged. Regeneration is via `type_names.py
--bindings-dir DIRECTORY`; no aggregate bridge is generated or published.

All four generated headers were compiled independently. Registry regression tests
check family separation and element dependencies. Normal suites passed 13/13 on
WSL and dev002, and the build-boundary checks passed. ASan/UBSan passed 13/13
tests (8.64 s); TSan passed 12/12 compatible tests (9.60 s), with its existing
scalar allocation-hook exclusion. No sanitizer findings. Sanitizer evidence is retained
in `build/sanitizers/focused-bridge.log` and
`build/thread-sanitizer/focused-bridge.log`.

Measured preprocessed input (bytes, line markers removed with `-E -P`):

| Support unit | Before | After |
| --- | ---: | ---: |
| token.cpp | 1,812,051 | 1,440,689 |
| token_sequence.cpp | 1,825,097 | 1,688,556 |
| token_jobs.cpp | 2,995,767 | 2,993,685 |

The job unit still includes the broad task/runtime dependency graph. This split
reduces unnecessary direct dependencies but does not eliminate runtime transitive
header costs or repeated task-template instantiation.

Three clean full `src/` + `support/` builds per configuration, including linking,
excluding tests and dependency publication, with warm filesystem caches:

| Host | Ninja jobs | Runs (seconds) | Median |
| --- | ---: | --- | ---: |
| WSL | 8 | 8.520, 11.968, 9.817 | 9.817 s |
| dev002 | 8 | 2.617, 2.626, 2.642 | 2.626 s |
| dev002 | 32 | 2.617, 2.679, 2.615 | 2.617 s |

All 17 implementation units were rebuilt each run. Runtime/middleware archive
hashes and modification times remained unchanged. Compared with the prior single
measurements (WSL 8.879 s, dev002 2.628/2.660 s), this does **not** establish a full
build speedup. WSL timing is variable; dev002 is essentially unchanged. The existing
heavy support units still determine overall build latency. Build evidence is in
`build/compiler/focused-bridge-build-times.json` on each host. Keep 8 jobs as default.
