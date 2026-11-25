# Current source skeleton
Doc Status: supporting

This walkthrough describes the retained PHP++ reference. The active
[PHP prototype](../../README.md) now reads source snapshots, tokenizes,
parses, collects symbols and resolves calls and declared return annotations,
retaining valid contributions; it stops before native building. See
[declared return-type resolution](return_type_resolution.md).

Status: valid-JSON manifest reading, its initial rebuild decision, and recursive
source discovery are implemented;
malformed JSON is recoverable through checked decoding. Complete
[manifest type validation remains unfinished](project_manifest.md#current-toolchain-blocker).
No resident loop, source content reader, tokenizer, parser, resolver,
publication, or backend is enabled. Calling an
unfinished operation throws an explicit `Not implemented` exception. These
are developer placeholders, not the eventual source diagnostic mechanism.
The skeleton lays out the pipeline's [six-item refresh recap](../compiler_pipeline.md#six-item-refresh-recap),
not responsibilities 1 through 6 of its 22-item map. The
[first working slice](first_slice.md) remains a future behavior target.

## Where to read

The [command entry](../../reference/original-phpp/src/main.phs) accepts a manifest path, reads it, and
discovers source files. With `--debug=json`, it prints `manifest` and `sources`
JSON snapshots; otherwise no exporter
runs. [Build/run instructions](../../reference/original-phpp/src/README.md)
use the custom toolchain. This command inspects the implemented input stages;
resident execution and source-to-native compilation remain future work.
`--simulate-increment` performs two input refreshes with retained snapshots;
its [folder swap and recovery journal](increment_simulation.md) belong to the
driver. `compile/inputs.phs` supplies the same prefix to this command and the
preparation coordinator. `Input_Snapshot` is observed input state, not a published
compiler generation.

Start at [prepare_compiler_update](../../reference/original-phpp/src/compile/update.phs). It lays out
manifest reading, initial/manifest rebuild detection, participating-file
discovery, and one preparation loop. With a valid [manifest](project_manifest.md),
execution currently stops at source content reading. `begin_compiler_update` creates
the per-update decision without modifying session state. The [phase runners](../../reference/original-phpp/src/compile/phases.phs)
show the actual file and symbol loops with
`update.full_rebuild || element.needs_recompile` selection.

```text
retained session + manifest path
  -> read/validate manifest -> decide initial full_rebuild
  -> reconcile current files and removals
  -> repeat preparation if selective work escalates to full:
       selected files: read snapshot -> tokenize -> parse
       join source snapshots and file ASTs
       reconcile project declarations/indexes; compare change support
       selected symbols: resolve uses against current project declarations
       check incremental support; join retained and replaced bindings
  -> prepared candidate (no publication yet)
```

There is one possible escalation from selective to full within this loop.
It revisits the same stages, so files/symbols skipped earlier are included.
The flag never changes while a phase runs. Initial/full updates start with it
enabled. A missing incremental rule requires recomputation; a language error
must still produce diagnostics rather than being accepted in full mode.

## Handoffs and ownership

- [Compiler_Session](../../reference/original-phpp/src/compile/state.phs) retains the published manifest,
  file snapshots, frontend tables, project symbols, and resolved bindings.
- [Update_Context](../../reference/original-phpp/src/compile/state.phs) holds one update's selection
  decision. [Prepared_Update and Frontend_Phase_Result](../../reference/original-phpp/src/compile/result.phs)
  carry separate candidate/phase outputs. Publication remains an explicit
  function in [update.phs](../../reference/original-phpp/src/compile/update.phs), awaiting the remaining
  semantic/backend stages.
  The same file declares `cleanup_compiler_update` as a deferred, uncalled
  placeholder after successful publication and completion of old-state readers.
  It reserves the boundary for store-owned removal cleanup; it is not required
  for the first working increment. See the [cleanup TODO](incremental_refresh_rules.md#todo-cleanup-after-an-update).
- [Source_Set](../../reference/original-phpp/src/read_sources/store.phs) owns the module-folder vector,
  one shared file dataset, path/buffer payload tables, and removed IDs.
  [Inline records](../../reference/original-phpp/src/read_sources/structures.phs) carry folder ownership
  and change state. Folder indexes reference file IDs; file lookup resolves IDs
  to rows through owner methods. Canonical path lookup identifies live files;
  ID lookup also retains deleted rows. Discovery compares mtime and size and
  constructs a separate candidate with refreshed indexes.
  [Manifest roots](../../reference/original-phpp/src/read_manifest/result.phs) resolve relative to the
  manifest directory and are scanned recursively, with one owning module per
  file. A selected file produces a separate `Source_Buffer`; the join adopts
  it into a replacement source set without mutating published phase inputs.
  Deleted files skip frontend work even in full mode; their contributions still
  require removal. Path resolution and recursive scanning work; source reading
  and snapshot joins remain stubs. See the [timing limitation](incremental_refresh_rules.md#inputs-and-comparison-baseline).
- [File_Frontend](../../reference/original-phpp/src/parse/result.phs) is the parsed-file result: tokens,
  flat syntax storage, top-level declaration IDs in `defined_entities`, and
  `entry_body_id` for the implicit file callable. A completed result references
  a block even when the entry has no statements; zero means unfinished.
  [parse_source](../../reference/original-phpp/src/parse/parse.phs) returns this complete result contract.
  [Syntax_Tree](../../reference/original-phpp/src/parse/store.phs) stores the nodes without a file-root row.
  [join_file_frontends](../../reference/original-phpp/src/parse/parse.phs) declares a segment merge:
  `index` is the first result entry and `count` is the number of entries, covering
  `[index, index + count)`. Each call takes the current candidate and returns
  the candidate for the next merge, retaining other current files unless they
  were removed. The serial coordinator supplies the whole
  available range. Progress polling and safe publication of worker data belong
  to later caller code; the merge itself remains an explicit placeholder.
- [Symbol_Store](../../reference/original-phpp/src/collect_symbols/store.phs) owns inline declaration records
  and private indexes. Its lookup methods hide physical storage. The separate
  [declaration collection process](../../reference/original-phpp/src/collect_symbols/collect.phs) is the
  placeholder for producing a replacement store and work list. Its contract
  consumes each file's entity list, assigns an implicit owner to its entry body,
  and removes deleted contributions from candidate indexes before resolution.
- [Symbol resolution](../../reference/original-phpp/src/resolve_symbols/resolve.phs) declares a result
  containing use-to-symbol binding rows. Replaced ASTs
  invalidate their old node IDs: select re-resolution where needed, even for an
  otherwise unchanged declaration in that file. Later body/backend reuse has
  its own selection; resolution work does not automatically mean codegen work.
  Eligibility is specified in the [incremental rules](incremental_refresh_rules.md).

File and symbol work units read unchanged phase inputs and produce separate
outputs, collected in deterministic order. Execution is serial now. When
resolution gains dependencies between units, order those groups with joins;
do not introduce worker writes to shared indexes or a second compiler path.
The entity list provides explicit declaration roots for future progressive
indexing. Worker progress and safe partial publication are not implemented;
the current frontend join's index/count still selects file-result entries.

## Scope and validation

The three-file executable sample remains the first behavior goal. This skeleton
establishes signatures, storage boundaries, the common loops, and implemented
manifest/discovery stages. Further manifest settings, source diagnostics,
semantic change classification, dependency updates, and downstream store
operations are still TODOs.

The [storage check](../../reference/original-phpp/tests/README.md) compiles all source declarations in a
temporary project, exercises the existing flat storage, and verifies that an
attempted update stops without publishing. It does not prove incremental or
multithreaded execution. The [test notes](../../reference/original-phpp/tests/README.md) own the command,
observed storage sizes, and current STAN return-analysis limitation. Native
validation uses explicit `--no-stan`; dummy returns are not substituted.

Cross-process types currently use fully qualified names. In the vendored
toolchain, short type aliases imported with `use` generated unqualified field
types and conflicting forward declarations in C++ headers. Fully qualified
references compile correctly; process-function imports use `use function`.
This keeps namespace ownership intact without modifying the toolchain.

Keep vector-returning function signatures on one line with the current
toolchain: multiline signatures emitted an `auto` return declaration in C++
headers, preventing calls from other source files.
