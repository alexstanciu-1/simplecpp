# Incremental native C++ build
Doc Status: supporting

`06_native/Native_CPP_Build` consumes a ready disk publication, not syntax or semantic
work. It never calls preparation/emission, changes Model roots or acknowledges source
changes. The legacy LLVM `Native_Runner` remains separate. Configuration and results
are named invocation-local records; a worker owns one build attempt.

Host entrypoint:

```bash
php compiler/my-try/tools/build_cpp.php /absolute/published /absolute/cache
php compiler/my-try/tools/build_cpp.php /absolute/published /absolute/cache --no-pch -O2
```

The CLI defaults to `/usr/bin/clang++`, the checkout runtime headers, C++23, PHP target,
threads and `-O0`, without native debug information. Pass `--native-debug` (or set
`$config->native_debug = true`) to request full Clang `-g` information for
PCH, generated units and runtime provider objects. Debug selection is independent
of optimization and participates in the existing action cache keys; returning to
the default configuration reuses its prior artifacts. This does not disable
source-language diagnostics or runtime checks.

The builder emits a JSON result only after success, with executable path and
counts for compiled/reused objects, PCH creation and linking. It does not execute the
program or rebuild the compiler itself. Programmatic callers use `native_cpp_config`.

## Ownership and ordering

Hold the source publication's shared reservation for the whole build, then a stable
exclusive cache reservation. Contention is nonblocking and leaves readiness alone.
An admitted attempt marks native status pending before checking source readiness.
Verify every listed artifact against its content digest and use exact manifest unit
membership; never discover link inputs by scanning the cache directory.

The dedicated cache owner marker reserves its control files and artifact namespaces.
Unknown owners and initial collisions reject. Per-action metadata is pending before
launch, ready only after successful staged promotion. Completed actions can survive
a later failed action. Missing or corrupt *cache-owned* binary outputs can be rebuilt;
source-output edited-file protection is a different policy and remains unchanged.
Old/unreferenced cache artifacts are retained but never automatically linked.

`Output_Stage` owns each external output path. The managed process helper supplies
literal argv, timeout/kill/reap and captured diagnostics. Every stage closes in
`finally` after its writer has stopped. Promotion uses rename without copying the
PCH/object/executable into PHP/native strings. Failed attempts cannot publish native
readiness; a prior executable is not evidence of success for the attempted revision.

Successful native status records the exact source manifest digest and executable
digest/path. A future run/current-build reader must check both current source
readiness and this association while holding the relevant reservations. A raw
historical executable path remains a historical artifact, not a freshness API.

## Dependencies and PCH

The publication's required `runtime_build` snapshot supplies runtime provider sources,
link libraries and global definitions. The builder never reopens catalogs. Relative
provider paths resolve below the configured runtime root (including a resolved-path
containment check). Each provider gets an ordinary cached object without PCH; generated
units retain the runtime PCH. Definitions participate in all compile/PCH identities;
ordered libraries participate in link identity. Publications made before this field
was introduced must be republished. There is no hardcoded core-source fallback.

Reuse is keyed by the compiler/linker/environment-launcher identities, compiler
version, exact ordered arguments and content digests of inputs. The child environment
is explicit (`env -i`, fixed PATH/locale/timezone/source-date epoch); inherited
CPATH/CXXFLAGS and other ambient settings cannot silently influence reuse.

For compilation, ask Clang for a fresh full dependency rule, including system headers.
`Native_Depfiles` owns bounded Make-rule parsing: continuations, escaped characters
and doubled dollar signs; it is not a general Make evaluator. Fresh scanning catches
newly shadowing headers rather than only rechecking a previously selected header set.
Hashing streams file contents; it does not rely on coarse mtime/size equivalence.

PCH content is a fixed runtime-only header. It contains no generated project
declarations. Its transitive input set and compilation configuration determine its
cache identity. Clang's normal PCH validation remains enabled; an invalid retained
PCH is rebuilt, never silently replaced with an uncached/no-PCH fallback. Object
keys include the PCH artifact digest. `--no-pch` is an explicit separate configuration.

The exact ordered object set determines link identity. GNU ld emits its dependency
file; cached linking rechecks those library/startup/script inputs as well. First-link
metadata associates the completed artifact with the discovered dependency key,
without copying the executable. Permissions are checked before binary cache reuse.

## Runtime layout actions

Published `layout_probe` artifacts are compiled and linked through the existing
native action cache before application units. These small host executables print
layout/trait definitions; their output is a cached `.hpp` action, identified by
protocol version, toolchain, configuration and probe executable digest. The regular
fresh dependency scan covers their runtime and published headers. There is no second
layout cache or mutation of the source publication.

Generated units receive the resolved headers using `-include`, so dependency hashing
also covers layout contents. Native status remains pending if layout compilation or
execution fails. Missing/corrupt cache-owned layout output is regenerated using the
same action ownership/readiness protocol. Layout headers are reused on body-only
edits. `layouts_built`/`layouts_reused` report resolved headers; ordinary object/link
counts exclude probe actions. Probe compilation/linking still performs its normal
cache validation on warm builds.

This first protocol is host-executed, Linux/Clang only, and uses one grouped probe.
It does not support cross-compilation or a probe that depends on generated opaque
source-record layouts. One grouped header conservatively couples consumers when its
contents change; finer dependency partitioning remains follow-up work. Runtime
provider objects do not receive application layout macros.

## Bounded first implementation and next work

This is Linux, Clang plus GNU ld with dependency-file support, ordinary core runtime
and metadata-declared provider linkage, and `-O0`/`-O2`. It is not a replacement for the legacy `scpp`/Ninja project
system or a claim of every optional runtime module's linkage. No arbitrary user
flags, cross-compilation, alternative linkers, runtime package-manager integration,
distributed cache or native build of this compiler is claimed.

Toolchain/runtime inputs must remain quiescent during one build. Reuse assumes an
unchanged toolchain installation/search configuration between builds except
for explicitly fingerprinted files. Replacing toolchain shared libraries or changing
default library search selection requires a fresh cache; automatic detection of those
changes is not yet proved. Native cache metadata
is trusted build-owned state, not a hostile-filesystem/security boundary. Power-loss
durability is not promised. SIGKILL may leave unreferenced adapter/tool temporaries;
automatic garbage collection is deferred. Cache maintenance must honor reservations.

First-pass costs are deliberate: dependency scans and content validation run even on
a warm build; native actions are sequential within one builder. Follow-up work owns
bounded parallel actions and extended toolchain acceptance. The core edit/signature/
layout matrix and phase timings are now covered by `tests/native_invalidation.py`;
see the [dated roadmap evidence](../planning/incremental_cpp_artifacts.md#broader-native-invalidation-and-phase-timing--2026-10-06).
Reducing validation overhead without weakening dependency correctness is
deferred by user request to [CH15-NATIVE-CACHE-PERF-01](../catalog/15_blocking_debt.md#ch15-native-cache-perf-01--native-cache-validation-overhead).
Toolchain shared-library/search-environment completeness and optional runtime-module
linkage require further acceptance work before claiming a hermetic/general cache.

Focused module evidence: `tests/runtime_module_build.py` proves cold/warm reuse,
one-unit body/provider edits, definition/PCH invalidation and library-only relinking.
This validates generated executables, not a native rebuild of the compiler.

The native-debug cache proof in `tests/native_cache.py` checks actual ELF debug
sections, execution, cold/warm debug builds and reuse when returning to the default.
This option does not implement source-language stepping, variable inspection or
source-mapped runtime stacks; those require a compiler-owned debugging design.
