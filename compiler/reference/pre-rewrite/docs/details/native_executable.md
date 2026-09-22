# First native executable
Doc Status: supporting

Future optimization direction: [LLVM LTO readiness](llvm_lto_readiness.md)
records parallel backend work, incremental cache ownership and the separation
from current `-O0` behavior.

Status: the PHP prototype compiles the three-file call/return sample to a real
executable. It also rebuilds and runs a function-body edit in the same session.
The executable subset includes integer literals, positional scalar calls and
parameters, returns, initialized scalar locals and nested scopes, including void
functions. Arguments use identity or lossless integer widening and value-copy/no-cleanup contracts. No language optimization pass
or PHP semantic worker threads are added; native compilation uses external processes.

## Using it

```sh
php src/main.php examples/three_files/project.json --output /tmp/scpp-sample
/tmp/scpp-sample
# The sample returns process status 42.
```

The [function-argument sample](../../examples/function_arguments/README.md)
adds mutable parameters, nested calls and scalar copies across three files.

`--output` is relative to the invocation directory; its parent must exist. Keep
outputs outside recursively participating source folders. Explicitly selected
files permit output beside them, while the files themselves remain protected.
The manifest/project input, project lock,
active type catalog, backend configuration and resolved Clang/linker executables are
protected from output replacement. Input owners supply their paths; checks
resolve relative paths and symlink aliases to the same canonical targets. The
session supplies `Project_Lock::path()`; native building does not reconstruct
the lock filename. For manifest input without this option, the same
pipeline stops after LLVM text emission at `build_native`, with
`completed: false`. With it, `completed: true` and `stopped_before: null` mean
object generation, linking and publication succeeded. Compilation never runs the
program. `--debug=json` includes `llvm` (the module text and entry plan) and
`native` (published path and SHA-256), alongside the earlier stage results.
The API is `Compiler_Session::compile(manifest_path, output_path = null, default_output = false)`.
Its first argument also accepts a `.phs` source as a virtual one-file project.
The CLI requests default output for direct `.phs` input: the source stem beside
the source, plus `.exe` when Clang's prepared canonical triple identifies Windows.
`--output` uses its exact filename; `--check` selects inspection only.
See [virtual projects](project_manifest.md#virtual-one-file-projects).

Simulation accepts `--output` too. Its destination must be outside both swapped
project trees and the recovery journal. The final executable reflects the edited
project even after the original sources are restored. Both results describe the
same output path at different points in time; their hashes distinguish contents.

## Native entry contract

[`Native_Entry`](../../src/05_generate_code/lower/main_native_entry.php) owns the hosted-C
startup policy. The configured Clang emits a probe for `int main(void)` on its
selected target. Preparation accepts only the implemented ABI form: external
`main`, parameterless default C calling convention, direct integer result and
no special return attributes. Other forms fail precisely. The native integer
width comes from that probe, not the host PHP integer or a target-name table.

An immutable `native_entry_plan` references the prepared language-entry binding
and records the native width, symbol/convention and conversion. The status policy
is explicit: equal widths preserve bits; narrowing retains low bits; widening
uses sign extension for a signed source and zero extension for an unsigned one.
This is process-boundary behavior, not permission for an implicit source cast.
The operating system determines how much of the native status a parent observes;
the Linux proof observes 257 as status 1.

LLVM emission consumes the chosen operation. LLVM defines
[`trunc`, `zext` and `sext`](https://llvm.org/docs/LangRef.html#conversion-operations)
for these integer-width changes. Named language functions have prepared external linkage so calls can cross
object boundaries; source visibility and a public library ABI are separate concerns.

## Emission and native work

[`LLVM_Emitter`](../../src/05_generate_code/emit_llvm/main_emit_llvm.php) selects changed lowered
bodies before emitting one function per independent worker. Constants become
immediate operands, call results become SSA names, and each block ends in its
prepared jump, branch or return. A private `Emission_Worker` owns the text and
operand state; [instruction, call and terminator handlers](code_generation_handlers.md#llvm-emission)
implement the dispatch. Workers never consult source ASTs or decide language conversions.
The first coordinator join validates complete current results, excludes removed
functions and groups shared definitions by source-file ID in a temporary
`Emitted_Function_Set`. `Emitted_Function` retains distinct called contracts
collected during its existing instruction traversal.
[`Module_Assembler`](../../src/05_generate_code/emit_llvm/main_assemble_modules.php) then selects
independent `module_task` inputs for changed files (all current files on full).
Each worker reads fixed function rows, backend context and an optional entry
plan; it builds private file IR without tools or writes to shared inputs.
The second join validates task/result membership and contracts, restores file
order and retains unchanged modules. Neither join builds LLVM text. Entry-only
changes select the entry file even when function IR is unchanged. Changed
file modules include declarations only for callees outside that file, matching
the prepared return/convention facts. Unchanged functions and file modules are
shared without rescanning old ASTs. The manifest entry file also contains the
native entry adapter. `Emitted_Program` owns module/function lookups; each
`Emitted_Module` is a separate, complete LLVM input. No combined project IR is
retained. Debug JSON exposes `llvm.modules[]` with file IDs and individual IR.
[`module_assembly.php`](../../tests/05_generate_code/emit_llvm/module_assembly.php) checks reversed
completion, full/selected reuse, stale/incomplete results, fixed-input purity,
entry changes and real source-to-executable behavior. Execution remains serial;
the two selected batches are the future multi-CPU execution boundaries.

[`Native_Builder`](../../src/06_build_output/build_native/main_build_native.php) selects missing or
stale objects, executes the selected file-module jobs through a bounded external
process queue, then joins the results and retained objects. The configured Clang parses/code-generates each
selected IR input at `-O0`, then links the complete current object set.
[Clang's driver](https://clang.llvm.org/docs/CommandGuide/clang.html) owns object
production and runtime/linker selection. Missing target runtime or linker support
fails the build. Current proof is on Linux x86-64 with Clang 18; other targets
are not claimed tested merely because their descriptors can be prepared.

A unique private directory beside the destination holds the staged executable.
Each new `Native_Object` owns a separately reserved temporary object path and its
content fingerprint. Accepted snapshots share these owners; the last reader
releases the object file. Candidate failure explicitly discards only new objects,
never retained ones. This is a resident cache, not cross-session persistence.
Only a successful link permits the final executable rename. Failure cleans this
attempt's known files and preserves the old output. Failed cleanup is
reported with the remaining paths and stays retryable; unknown workspace contents
are never recursively removed. If publication fails too, its primary error and
cleanup details are reported together. Before the rename, the session requests
`Source_Set::acknowledged()` to prepare completed file work/removals without
editing retained rows. Successful publication adopts that snapshot and all
candidate stage results, then advances the session generation.
A cleanup failure after the rename is nonfatal: the result
remains completed and exports `warnings`, also printed to CLI stderr with or
without debug mode, including simulation runs. Abandoned-workspace cleanup
failures use the PHP error log. Previous snapshots are not mutated. Hard crashes can leave
unreferenced `.scpp-native-*` directories or temporary `scpp-object-*.o` files; persistent generation recovery and
physical tombstone reclamation remain deferred. Output paths must be reserved
for this project; sharing an output across independent projects is unsupported.

## Incremental boundary and proof

Inspection and executable requests admit unchanged callable definitions with
changed bodies through the same eligibility gate.
Added/removed/changed definitions or unknown catalog changes set `full_rebuild`
at the post-resolution boundary and repeat the same frontend/symbol stages with
all work selected. The output option determines whether native building and
publication follow LLVM emission; it does not change incremental eligibility.
Manifest/catalog changes already select full work.

[Incremental policy checks](../../tests/compile/incremental_policy.php) compare
both request modes across body edits, additions, signature changes, renames,
removals and failure/repair.

A body-only edit replaces its analysis, lowered body and emitted function.
Unchanged callers share results. Only the affected file's module/object is rebuilt, then all current objects are
linked. Missing/tampered objects also select work; an intact executable does not
hide a broken object cache. Full rebuild selects all modules through the same
workers. With unchanged modules, intact objects and an unchanged executable,
no tools run. Missing or modified executables relink using reusable objects.
Linker selection and executable stat changes invalidate linking only; runtime
library and transitive tool upgrades still require a session restart.

After publication, complete discovery with no pending files/removals, unchanged
manifest/catalog and the same verified backend configuration lets the session
share entire accepted semantic/LLVM results. It skips their selection/join
passes and goes directly to the common native validation/repair/publication
path. Fresh scan facts and an empty symbol-change view describe this request;
previous results remain intact. Inspection-only sessions do not acknowledge
native work and therefore do not use this published-generation shortcut.
[`native_reuse.php`](../../tests/integration/native_reuse.php) proves these
boundaries, linker-only changes, artifact repair and failure followed by repair.

[`native_executable.php`](../../tests/integration/native_executable.php) proves
actual execution, body edits, full fallback, void/unreachable behavior,
independent emission workers, invalid joins, failure/repair, native status
adaptations, output reuse and debug exports. Existing frontend/lifetime/lowering,
locking and folder-swap recovery proofs remain in the full prototype suite.

[`native_publication.php`](../../tests/integration/native_publication.php) additionally
proves protected-input collisions/aliases, final-rename rollback, simultaneous
publication/cleanup failures, coherent successful publication with warnings,
cleanup retry and abandoned-owner diagnostics. CLI tests exercise warning delivery
through real native builds and folder-swap simulation.

[`native_modules.php`](../../tests/06_build_output/build_native/native_modules.php) verifies file
partitioning, distinct cross-file declarations, exact object reuse/tool counts,
missing/tampered object repair, full selection, out-of-order object joins,
file removal, retained-reader lifetime and per-module debug export. Large-project
measurements must include the extra Clang startup cost of serial file-based builds.


## Concurrent native compilation

`tools/backend.json` now sets `compile_jobs` to **20**, as requested for testing.
Custom configurations may supply any positive integer; omission keeps a limit
of one. This scheduling setting does not invalidate IR, ABI contracts or cached
objects. Full builds and multi-file increments use the same selected-job queue;
a one-file edit starts one compiler, and unchanged output starts none.

PHP compiler stages remain serial. `LLVM_Toolchain::compile_objects()` starts
up to the configured limit, polls active jobs, refills completed slots and waits
for all selected objects before linking once. Probe, compile and link processes
share the `Tool_Process` lifecycle. The existing ten-second per-invocation timeout
starts on launch, not while a task is queued. The coordinator uses private file
streams rather than pipe buffering or PHP worker serialization.

Here multi-CPU execution includes separate processes as well as threads. The
object queue uses multiple Clang processes for both full builds and increments.
The selected linker may use its own threads after the object join. The
`compile_jobs` limit controls concurrent Clang processes, not linker threads.
`tools/backend.json` currently selects `linker: "mold"` following the
[measured comparison](native_dispatch_investigation.md#linker-selection-and-unchanged-reuse).
Mold must be available on PATH; select another executable or null for Clang's
default when using a different installation. Linking still consumes every
current object; native object reuse is incremental, final linking is not.

The external process executor currently requires **Linux, PHP POSIX support and
`setsid` on PATH**. Each invocation has its own process group; failure/timeout
cancels active groups (including driver descendants) and reaps direct children
before native output rollback. No further jobs are launched after an observed
failure. Successfully cached objects and the published executable remain intact.

[`parallel_native.py`](../../tests/integration/parallel_native.py) proves the
20-job bound, a three-file parallel increment, zero-tool unchanged reuse,
scheduling-only configuration reuse, partial-failure cancellation, descendant
cleanup, timeout, repair and invalid-limit rejection. The PHP semantic pipeline
and GC policy are unchanged by this execution setting.
