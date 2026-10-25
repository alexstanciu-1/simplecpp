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
threads, `-Wshadow` and `-O0`, without PCH or native debug information. API configuration
also defaults to no PCH. Ordinary builds may explicitly select `--pch` or
`$config->pch = true`; `--no-pch` remains accepted. Pass `--native-debug` (or set
`$config->native_debug = true`) to request full Clang `-g` information for
PCH, generated units and runtime provider objects. Debug selection is independent
of optimization and participates in the existing action cache keys; returning to
the default configuration reuses its prior artifacts. This does not disable
source-language diagnostics or runtime checks.

The builder emits a JSON result only after success, with executable path and
counts for compiled/reused objects, PCH creation and linking. It does not execute the
program or rebuild the compiler itself. Programmatic callers use `native_cpp_config`.

Successful compile/link diagnostics are returned in `native_cpp_result::diagnostics`;
the CLI writes them to stderr while preserving JSON stdout. Only actions executed in
this invocation contribute diagnostics; cached warnings are not replayed. Legal source
shadowing is accepted, and `-Wshadow` is not promoted to an error. The flag participates
in the ordinary action/PCH keys, so caches from before its introduction rebuild once.


## Application optimization and C++ lowering

Application levels 0, 1, 2 and 3 are admitted explicitly by both native consumers.
`cpp_lowering_policy` is the backend-owned lowering input; coordinators pass it to
`Compiler::exec_cpp`, `update_cpp` or `cpp` alongside the publication. The host S2S
CLI accepts `--optimization 0|1|2|3`. Standalone emission defaults to execution
spelling (level 2) to preserve existing output. This is independent of the
optimization used to build the compiler executable.

For a latency-oriented two-step build, select the level at both boundaries:

```bash
php compiler/my-try/main.php --s2s /absolute/source --entry main.phs --output /absolute/published --optimization 1 --no-prelower-templates
php compiler/my-try/tools/build_ninja.php /absolute/published /absolute/build --reuse -O1 --jobs=8
```

A combined coordinator constructs `new cpp_lowering_policy($config->optimization)`
and passes it to emission before invoking its native consumer. Optional directory
reuse still belongs to Ninja.

Levels 0/1 share `cpp-lowering-v2:latency`; levels 2/3 share
`cpp-lowering-v1:execution`. A profile change clears retained backend fragments,
without changing prepared semantic facts or source-unit membership. The publication
records `cpp_policy` under the ready digest. Native consumers reject latency output
at O2/O3 and unknown policy identities; regenerate first. Earlier
`cpp-lowering-v1:latency` publications remain valid at O0/O1. Execution spelling,
including pre-policy publications, may deliberately be compiled at O0/O1 as a
baseline. Native consumers never invoke lowering themselves. Optimization flags
remain in compile/PCH command identity, so O0 to O1 rebuilds native inputs even
though the emitted C++ profile is identical.

The first latency pattern outlines default constructors for ordinary non-inherited
classes without authored constructors, data initialization, abstract/virtual lifecycle
or authored destructors. Every instance field must have an explicit constant default
or an empty construction with a prepared sequence or empty-only initialization
contract, with at least one such construction default. Eligibility uses the settled
initialization plan, independently of the registered type name.
Keep the original expressions and order in a member-initializer list; emit the
constructor in the declaring source's existing implementation group. Uninitialized
fields are excluded to preserve C++ value-initialization behavior. Authored methods,
copy/move/destruction and source contracts are unchanged.
O2/O3 keep the previous generated form. `record_definition_text` holds type-owned
static storage, outlined constructors and allocation definitions. Oversized function
groups may split; ordered type-owned storage stays in the source unit.

The second latency pattern emits a no-argument shared-allocation function for the
same eligible classes, declared after the type and defined in its existing `.cpp`.
Shared construction calls it; its body calls the unchanged `scpp::shared<T>()`
runtime allocator. Unique/value construction and classes outside that eligibility
retain their previous path. No argument forwarding or new source-visible factory
is introduced. O2/O3 emit neither the helper nor calls to it.

`CPP_Shared_Allocations` selects helpers from settled declarations. The selected
qualified names belong to `cpp_program`, and rendering contexts receive the current
selection. A changed selection clears backend fragments before rendering, including
unchanged callers; this prevents stale helper references when a field or constructor
edit changes eligibility. Stable selection preserves ordinary fragment reuse.
Content-aware publication still avoids rewriting unchanged artifacts. This bounded
invalidation is backend-owned and does not add semantic dependencies or alter MT.

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

Options are `--no-pch`, `-O0`/`-O1`/`-O2`/`-O3`, `--jobs=N`, `--compiler=ABSOLUTE_PATH`,
`--runtime=ABSOLUTE_PATH`, `--ninja=ABSOLUTE_PATH`, `--linker=ABSOLUTE_PATH` and
`--linker-threads=N`. Defaults preserve C++23, PHP target, pthread, `-O0`, the runtime
PCH and `/usr/bin/ld`. `--jobs` limits Ninja commands; `--linker-threads` independently
configures linker-internal parallelism. Neither changes compiler-internal MT.

The graph contains the current runtime PCH, one object per published translation
unit, one object per selected runtime provider, and one executable. Object names
use source-path identities, not traversal positions or content digests. Removed
units disappear from the link inputs even if their old objects remain on disk.
Providers do not depend on the PCH; generated objects have a real PCH prerequisite.
No object depends on the whole publication manifest. Compilation collects full
header dependencies with `-MD -MF -MQ`; linking uses the existing GNU ld dependency
file facility (also supported by Mold). No separate `-M` discovery pass or PCH syntax-only validation command
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
internals. Later backend-owned implementation splitting is independent of this
consumer; multi-file source classes remain deferred.

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

## Linker selection and identity

Both entrypoints accept `--linker=/absolute/path/to/mold --linker-threads=8`.
Programmatic callers set `native_cpp_toolchain::linker` and `linker_threads`.
The default is `/usr/bin/ld` with zero threads, meaning no thread option is sent;
the selected linker retains its default. A positive count emits `-Xlinker --threads=N`
and requires a linker supporting that option (tested with Mold 3). GNU BFD should
use zero. Relative linker paths and negative thread counts are rejected. Unsupported
linker options fail through normal tool diagnostics; there is no silent fallback.

```bash
php compiler/my-try/tools/build_ninja.php /absolute/published /absolute/build --reuse --jobs=12 --linker=/absolute/path/to/mold --linker-threads=8
php compiler/my-try/tools/build_cpp.php /absolute/published /absolute/cache --linker=/absolute/path/to/mold --linker-threads=8
```

Only the link recipe receives these settings. Each build observes the selected
executable's resolved path and SHA-256, including same-path content replacement and
symlink retargeting. Ninja records that in its link-only identity input and observes
thread/path changes through the command hash. The content-addressed consumer keeps
linker identity in the link key, separate from compilation/dependency-discovery keys.
Objects/PCH survive linker-only changes. The latter consumer uses compilation key
namespace v2, causing a one-time miss for older v1 entries; Ninja directories need no reset.
A revert may reuse a historical executable in the content-addressed consumer, whereas
Ninja relinks when its latest recorded command/identity changes.

Existing link depfiles, pending/ready publication, error propagation and recovery
remain in force. Configure the actual linker binary: scripts or wrappers that invoke
other mutable tools do not establish transitive tool identity. Toolchain files must
remain quiescent during a build. Selected linker shared-library dependencies retain
the documented toolchain-installation limitation below.

Focused proof: `tests/linker_configuration.py --mold /absolute/path/to/mold --results FRESH`
checks both consumers, configuration changes, binary replacement with preserved mtime,
same-byte symlink retargeting, no-op/revert behavior and failed-link recovery.

## Dependencies and PCH

Publications carry `application_templates: "allowed" | "forbidden"`. The compiler
coordinator publishes `forbidden` for the general concrete-substitution mode.
Missing/unknown policies reject with a republish diagnostic; the native builder
does not infer lowering mode from C++ text. Standalone artifact publishers must pass
their intended policy to `Output_Publication::publish` (the third argument).

For `forbidden`, the broad runtime PCH is incompatible; the default is already no PCH.
The builder rejects rather than silently disabling PCH or reverting lowering mode.
`entry.cpp` and every `units/` translation unit are checked; `specializations/`
implementation units and layout probes retain native templates. Unknown translation
unit locations and a missing entry reject instead of being silently excluded.

Before compiling a new strict application object, Clang dumps its active preprocessed
tokens with the same flags, definitions, includes and resolved layouts. A `template`
keyword rejects; comments, string literals and inactive preprocessor branches do not
constitute template declarations. Missing token-stream completion or compiler failure
also rejects. This is deliberately a conservative no-template-keyword input contract,
not a second C++ parser or a provider-name allowlist. The existing independent
complete-closure regression still checks template declarations and instantiation traces.

Validated objects use the existing native action cache with a versioned validation
identity. Ordinary objects cannot satisfy these keys. Strict app objects always use
fresh Clang dependency discovery, even with `--runtime-snapshot`; thus a changed or
newly selected transitive provider header invalidates their validation. Unchanged
validated objects reuse without another token dump. Failures leave native readiness
pending and cannot publish a successful executable for the attempted inputs. Source
publication readiness means generated inputs are complete, not that native validation
has succeeded. Toolchain/runtime quiescence during a build remains required.

Focused proof: `tests/prelower_native_boundary.py` covers provider mutation/recovery,
macro-expanded templates, comment/string/inactive text, strict PCH rejection,
ordinary PCH support and cache separation. `tests/prelower_null_output.py` exercises
the policy through real concrete generation and the independent closure audit.

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

Compilation reuse is keyed by compiler/environment-launcher identities, compiler
version, exact ordered arguments and content digests of inputs; link reuse additionally
includes the selected linker identity. The child environment
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

The exact ordered object set determines link identity. GNU ld or Mold emits its dependency
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

This protocol is host-executed and Linux/Clang only. Probes run in manifest order;
each receives all previously resolved layout headers. The specialization engine groups
concrete layout requests by dependency depth and bounded parts within a depth,
allowing outer native templates to contain concrete inner owners. Prior header contents participate in the existing
dependency scan/cache; source publication remains immutable. Grouped headers and
all-prior-batch inclusion conservatively couple consumers; finer partitioning remains
follow-up work. Acyclic source-record headers participate through the same generated-header dependency
facts. Cross-compilation and recursive header/layout graphs remain unsupported. Runtime provider objects do not receive application layout macros.

## Bounded first implementation and next work

This is Linux, Clang plus GNU ld or Mold with dependency-file support, ordinary core runtime
and metadata-declared provider linkage, and `-O0`/`-O2`. It is not a replacement for the legacy `scpp`/Ninja project
system or a claim of every optional runtime module's linkage. No arbitrary user
flags, cross-compilation, other linkers, runtime package-manager integration,
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

Concrete prelowered publications currently use `Native_CPP_Build`. The optional
Ninja consumer rejects them explicitly until it supports layout probes and strict
application closure validation. Shared-allocation outlining remains ordinary-only;
concrete construction continues through the existing prepared transport.
