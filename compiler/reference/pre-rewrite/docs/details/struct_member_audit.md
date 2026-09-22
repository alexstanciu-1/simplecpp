# Struct member and indirection audit
Doc Status: supporting

Audit baseline: 2026-09-09. The recommendations below describe the code before
cleanup. Following approval, the PHP prototype now implements:

- Direct folder/file path strings and source-buffer references; the two payload
  tables and their lookup/remapping methods are removed.
- Required source references on token buffers, with file IDs derived from source.
- Direct symbol name/namespace strings; the unused spelling table is removed.
- Flat AST `start`/`length` fields; the extra span object/deep-clone method is gone.
- One owner symbol and exact AST on each resolution result; binding rows contain
  only use-node and target-symbol IDs. The resolver itself remains a stub.

Stable IDs, dataset indexes, flat AST links, snapshot objects and phase boundaries
remain. The PHP++ reference is unchanged. Per-folder index co-location and
class-to-struct conversions remain optional future work. Prototype tests cover
reuse, failure isolation, buffer release, exports and the binding storage contract.

Scope: the active [PHP prototype](../../README.md), all retained PHP++
record/store/result definitions in `src/`, their consumers, and the shared design.
Parser and symbol findings concern skeletons; discovery and lexical findings
concern running code. The older compiler outside this repository was not audited.

The premise is the announced support for string, vector, hash and class-declared
properties in structs. The [local strict skill](../../../../../simple_cpp_compiler/.codex/skills/simple-cpp-php-strict/SKILL.md)
still describes older field restrictions. The local quick-learn was also checked
at `../../../simple_cpp_compiler/vendor/simple_cpp/specs/simple_cpp_php_strict_quick_learn.md`.
The coming release's copy/layout behavior has not been verified. Historical
motivation is inferred where the code does not explicitly record it.

## Main finding

The strongest simplification opportunities are **path IDs and buffer IDs in
source records**. They are snapshot-local storage locations, not stable compiler
identities. Their removal can simplify discovery and joins while preserving the
single file dataset and its indexes.

There is little evidence of excessive record types caused solely by the old
restriction. Most separate types represent an owner, immutable snapshot, worker
input or phase output. Removing their boundaries would lose useful meaning.

## Relations to simplify or reconsider

| Current relation | Evidence and purpose | Recommendation |
| --- | --- | --- |
| `source_folder.path_id`, `resolved_path_id` → `Source_Set.paths` | Each configured/resolved string is appended separately. A folder owns both spellings; neither is a project identity. | Store `path` and `resolved_path` strings directly. Strongest candidate: low folder count, simpler access. |
| `source_file.full_path_id`, `relative_path_id` → the same pool | `add_path()` only appends; it does not intern/deduplicate. Discovery rebuilds and remaps these IDs, including tombstones. The pool does keep native file rows narrower. | Prefer direct `full_path` and `relative_path` strings for simplicity. Measure native row size and scan/copy costs before claiming a performance win. |
| `source_file.buffer_id` → `Source_Set.buffers` | This indexes a reference to a per-file immutable `Source_Buffer`. Every source join builds a new pool and remaps file buffer IDs. Tokens already hold the buffer directly. | Replace the ID with an optional `Source_Buffer` reference; retain the buffer object itself. This removes a pool and remapping, not another copy of source bytes. |
| `symbol_record.name_id`, `namespace_id` → `Symbol_Store.names` | Planned spelling table, with no implemented insertion, lookup or interning. It is compatible with both the old restriction and a useful future shared-name pool. | Decide when implementing symbols. Prefer direct strings if the table merely relocates text. Retain IDs if actual interning is justified by repeated names/namespaces and compact bindings. |
| `Source_Set.by_top_folder[folder_index]` | Separate private index, containing IDs into the one file dataset. A vector member in `source_folder` could hold it. | Optional co-location, not a priority. Keeping derived indexes private under `Source_Set` is useful. Moving it must preserve coordinator-only rebuilding and avoid mutating retained folder records. |

Evidence: [source records](../../src/01_prepare_inputs/read_sources/data/structures.php),
[source store and append-only path pool](../../src/01_prepare_inputs/read_sources/data/store.php),
[discovery](../../src/01_prepare_inputs/read_sources/main_read_sources.php) and
[snapshot joins](../../src/01_prepare_inputs/read_sources/snapshot_join.php),
[symbol records](../../src/04_analyze/collect_symbols/data/structures.php),
[unfinished symbol store](../../src/04_analyze/collect_symbols/data/store.php).

Keep the two path meanings distinct: configured spelling versus resolved root,
and root-relative spelling versus canonical full file path. Deleted files have
`top_folder_index = -1`; their origin must survive removal of their folder.
Replacing path IDs does not justify discarding the full path.

A direct buffer reference should mean the same thing as the current buffer ID:
absence means source reading is needed; unchanged files share their existing
immutable object; replacement creates a new object. Keep
`full_rebuild || missing_or_invalid_result` selection and the separate flag for
unfinished downstream compilation. Worker inputs still carry the expected
file identity, path and metadata; workers do not mutate source rows.

## IDs and indexes that should remain

| Relation | Reason to retain it |
| --- | --- |
| `source_file.id`, `source_file_id`, pending removed IDs, `row_by_file_id`, `id_by_path` | Stable file identity survives row movement and root reorder. Joins match worker outputs by identity; deletion/recreation receives a new identity. |
| `top_folder_index` and per-folder lists of file IDs | Describe membership without duplicating file records. The folder index is snapshot-local, deliberately not a stable project ID. |
| `symbol_id`, target symbol IDs, symbol lookup indexes and removal lists | Connect uses/dependencies to one declaration dataset while allowing records and their derived results to be replaced. |
| `syntax_node.first_child_id`, `next_sibling_id`, declaration/body node IDs, `defined_entities`, `entry_body_id`, `use_node_id` | Preserve flat syntax storage and references across vector growth. Node IDs are local to an AST snapshot; replacing that AST invalidates its old bindings. |
| Token/AST byte spans | Refer to existing source spelling without allocating strings per token/node. Richer struct fields do not make duplicated text useful. |
| Directory task indexes and keyed join results | Match out-of-order worker completion to the scheduled work; not a workaround for record fields. These indexes are local to their batch. |

Do not replace flat AST links with node objects or a vector/hash per node merely
because those fields become legal. Most nodes have few children; leaf nodes
need none. The current compact links remain a reasonable starting point.

## Extra types and repeated fields

**Keep `Source_Buffer`, `Token_Buffer`, `Syntax_Tree`, `File_Frontend` and dataset
owners.** They distinguish immutable bytes, token storage, syntax storage,
complete parse results and indexes. `Token_Set` stores references, not another
copy of every token. In particular, lexical selection currently compares the
exact `Source_Buffer` object; converting it into a copied value would require
another snapshot-identity contract. That would add work rather than remove it.

**Keep task and result distinctions.** `source_scan_task`,
`scanned_source_file` and `source_read_task` carry different facts: scheduled
work, observed metadata and an expected read version. Reusing mutable
`source_file` records as worker output would mix observation with identity,
change classification and shared index ownership.

Some passive result bundles could become inline records on the eventual port:
`Source_Scan_Result`, `Tokenization_Phase_Result`, `Symbol_Resolution`,
`Symbol_Refresh` and possibly `File_Frontend`. This can change allocation without
merging their concepts. It is lower priority than removing unnecessary lookup
chains. Constructor/method support, nested-container copying and sharing must be
checked separately; allowing member types alone does not settle those choices.
`Compiler_Session`, `Project_Lock`, `Folder_Swap` and indexed stores remain owners.

Three independent cleanup opportunities:

1. **`Token_Buffer.source_file_id` duplicates `source.source_file_id`.** For a
   completed token buffer, require its source at construction and derive the ID.
   This eliminates one inconsistent state and some defensive comparisons. Do not
   remove file identity from source snapshots or joins.
2. **`syntax_node.span` allocates a separate `source_span` object per node in PHP.**
   It is the only current consumer of that record. Putting `start` and `length`
   directly on the node would remove that allocation and the special deep-clone
   method. The native nested struct was already inline, so this is a prototype
   simplification, not a benefit caused by the new release.
3. **`symbol_binding` repeats owner context.** `Symbol_Resolution` already owns
   `symbol_id`. If all bindings in a result belong to one symbol and one source
   AST, put owner/source context on the result and leave `use_node_id` and
   `target_symbol_id` in each row. For four native uint32 fields, reducing to two
   removes eight bytes of fields per binding, before other layout effects.
   Confirm the single-origin contract when resolution is implemented; mixed-file
   results still need per-use origin. Keep AST snapshot validity explicit.

Sources: [tokens](../../src/02_tokenize/store.php),
[token validity and joins](../../src/02_tokenize/join.php),
[syntax records](../../src/03_parse/data/structures.php),
[binding rows](../../src/04_analyze/resolve_symbols/data/structures.php),
[resolution results](../../src/04_analyze/resolve_symbols/data/result.php).
Other repeated file IDs on syntax/frontend containers may be harmless standalone
provenance. They are not a reason to combine storage owners prematurely.
`Source_Buffer.path` and `mtime` describe its immutable version, so they must not
be replaced by a lookup of the latest mutable file state.

## Suggested order and validation

1. Simplify source path fields and remove the unused indirection policy. Keep
   the source lookup API and stable file IDs.
2. Replace source buffer IDs with references, preserving immutable snapshot
   identity and failure-safe joins. This touches source ownership plus the
   lexical consumers; keep it a separate reviewed slice.
3. Require a source on completed token buffers and remove its redundant ID.
4. Consider flattening AST spans before implementing the parser. Decide name
   interning and binding context when symbols/resolution become real.

No blanket conversion of classes to structs, or of IDs to references, is proposed.
The original audit was read-only; the approved implementation is summarized above.

Future PHP changes should preserve stable IDs under root reorder and recreation,
old-snapshot purity, reversed joins, zero-work lexical reuse, failure recovery,
removal, debug exports and simulation restoration. Existing tests cover these.
Measure unchanged/edit refresh time and retained memory on representative trees.

For the eventual native port, inspect actual field layouts and check string and
container copy/move behavior, class-reference lifetime/absence semantics, and
vector growth. A vector/hash member does not establish that its elements or
buckets live inline in the containing struct; a class-declared member does not
establish that the class object itself is inline. Do not assume managed fields
remain trivially copyable, have free destruction, or become legal union payloads.
Keep large collections shared or independently owned where copying is expensive.
The announced capability expands representation choices; it does not itself
prove lower memory use, faster execution or multithreaded safety.
