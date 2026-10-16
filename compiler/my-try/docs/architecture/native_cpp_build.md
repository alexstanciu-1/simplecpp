# Native C++ build consumers
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
threads and `-O0`. It emits a JSON result only after success, with executable path and
counts for compiled/reused objects, PCH creation and linking. It does not execute the
program or rebuild the compiler itself. Programmatic callers use `native_cpp_config`.


## Shared commands and optional Ninja execution

`Native_CPP_Commands` owns ordered compiler flags, runtime PCH contents/creation,
runtime-provider compilation, generated-unit compilation, linking and the controlled
child environment. Its methods return argument arrays; they do not execute tools or
make freshness decisions. Both consumers share `native_cpp_toolchain`; their cache
and directory settings stay in separate derived configuration records. The existing
`native_cpp_config` constructor and cached-builder behavior are preserved.
`Native_CPP_Publication` decodes the same validated runtime-build contract for both.

The first Ninja entrypoint consumes a complete ready publication:

```bash
php compiler/my-try/tools/build_ninja.php /absolute/published /absolute/new-build --jobs=8
php compiler/my-try/tools/build_ninja.php /absolute/published /absolute/new-build --jobs=8 --reuse
```

Without `--reuse`, use a fresh empty dedicated directory. An already owned directory
is rejected rather than deleted. `--reuse` permits Ninja to reuse that directory's
outputs, including across separate compiler processes. No content-addressed native
cache runs underneath Ninja. This is ordinary Ninja timestamp/command freshness,
not historical content-addressed reuse after a Git revert. Use the entrypoint for
every build so publication validation, tool identity observation and reservations run;
invoking the saved graph directly is a diagnostic operation, not a readiness API.

Options are `--no-pch`, `-O0`/`-O2`, `--jobs=N`, `--compiler=ABSOLUTE_PATH`,
`--runtime=ABSOLUTE_PATH` and `--ninja=ABSOLUTE_PATH`. Defaults preserve C++23,
PHP target, pthread, `-O0`, the runtime PCH and `/usr/bin/ld`. There is no Mold
selection/tuning change in this slice. `--jobs` limits Ninja commands; it does not
configure compiler-internal MT or linker-internal thread counts.

The graph contains the current runtime PCH, one object per published translation
unit, one object per selected runtime provider, and one executable. Object names
use source-path identities, not traversal positions or content digests. Removed
units disappear from the link inputs even if their old objects remain on disk.
Providers do not depend on the PCH; generated objects have a real PCH prerequisite.
No object depends on the whole publication manifest. Compilation collects full
header dependencies with `-MD -MF -MQ`; linking uses the existing GNU ld dependency
file facility. No separate `-M` discovery pass or PCH syntax-only validation command
is scheduled. Clang's normal PCH validation remains enabled.

Graph/umbrella/identity files are written only when their bytes change. Compiler
and environment-launcher content identity is separate from linker identity; replacing
the linker does not itself invalidate objects. Keep the surrounding toolchain/include
search installation fixed during retained reuse; after changing search layout or
adding a shadowing external header, choose a fresh directory. This first slice does
not implement include-search inventory or historical artifact corruption recovery.

The worker holds a shared publication reservation and exclusive Ninja-directory
reservation throughout execution. It verifies all publication digests before invoking
Ninja. Ninja owns its direct external outputs and completion log (unlike the cached
consumer's per-action staged promotion). `.scpp-ninja.status` is pending before an
admitted attempt and ready only after successful Ninja completion and executable
hashing. A stale executable after failure is not the requested build's result.
The CLI returns JSON containing the executable path and captured Ninja output.

Ninja graph escaping and POSIX shell quoting are separate. Relative object/executable
paths avoid GNU ld's unescaped absolute-output-path depfile limitation. Build and
publication directories containing spaces, dollar signs and colons are covered.
Quotes, backslashes, pipes, tabs and newlines in dependency paths are rejected in
this first integration. The standard system library search paths are assumed;
broader depfile path handling and long link response files remain
[CH15 debt](../catalog/15_blocking_debt.md#ch15-ninja-01--ninja-adapter-follow-ups).

The implementation follows the upstream [Ninja manual](https://ninja-build.org/manual.html)
for command freshness, compilation depfiles, explicit dependencies and escaping.
This integration changes neither C++ grouping/emission nor compiler incremental/MT
internals. Automatic splitting and multi-file classes remain deferred.

Focused evidence: `python3 compiler/my-try/tests/ninja_build.py --results FRESH`.
This runs the PHP-hosted builder and real Clang/Ninja-generated executables, not a
native compiler rebuild or a performance benchmark. The existing
`tests/runtime_module_build.py` separately covers the refactored cached consumer.

### Focused checkpoint — 2026-10-16

The Ninja suite passed 13 successful build/execution scenarios plus four rejection
checks (reuse admission, unsupported path, compile failure, publication tampering).
Observed action counts: cold 6; unchanged 0; body edit 2; added unit 3; removed unit 2;
provider edit 2; library-list edit 1; provider-header edit 3; no-PCH fresh 5; O2 fresh 6;
PCH-header edit 6; unchanged after PCH rebuild 0; retry after compile failure 2.
Each count includes linking where required. The existing cached-consumer suite also
passed its six scenarios. Evidence: `/tmp/scpp-ninja-final-20261016/` and
`/tmp/scpp-recipes-existing-20261016/`. These are correctness observations, not
performance claims. Test sources preserve the checks if temporary logs expire.

The subsequent [O2 native accounting checkpoint](../planning/native_ninja_accounting_20261016.md)
executes this builder natively on the 515.7 KiB fixture: three full/unchanged/body-edit
trials, exact PHP/native artifact agreement and 94 CLI cases. It also proves direct
native publication after explicit missing-digest guards replaced the legacy S2S
coalescing form. This bounded checkpoint does not replace broad native language tests.

## Cached consumer: ownership and ordering

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

The opt-in `--runtime-snapshot` option reuses Clang dependency discovery when the
publication supplies complete generated include metadata. Generated inputs retain
current content validation; external dependencies and include-search inventories
are checked. Missing or unknown include metadata retains the full-scan path.
Runtime dependency contents are pinned for the cache lifetime: after a runtime
change, use a fresh native cache directory. Reload retained compiler catalogs as
well when runtime metadata changes. Automatic runtime refresh remains
[CH15 debt](../catalog/15_blocking_debt.md#ch15-native-runtime-identity-01--automatic-runtime-change-invalidation).
The default remains fresh dependency scanning.

PCH creation explicitly uses `-fpch-instantiate-templates`, moving eligible pending
template instantiations into the reusable PCH. This creation-only flag participates
in the existing ordered action-argument key. Older PCH entries cannot match; generated
object keys also change through the selected PCH path/content, and linking follows
the changed objects. Runtime provider objects compiled without PCH retain their keys.
No global cache-version bump or cache deletion is needed. Ordinary dependency scans,
PCH validation/recovery and the no-PCH configuration retain their existing contracts.

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
