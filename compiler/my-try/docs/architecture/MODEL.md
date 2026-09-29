# Retained compiler model
Doc Status: supporting

Incremental refactor checkpoint: standalone `Compiler::parse()` now updates existing
declaration nodes and collected identities in place. Collector is invoked by the
parser and registers globals under the task batch lock. Bodies/references are
replaceable; symbol revisions and change state belong to `collected_name`, not
all AST headers. Failed files retain an incomplete mutable graph for retry.
Standalone preparation now follows the join with separate declaration/function-body/
file-body work lists and retained dependency links. Unchanged bodies retain their
syntax, occurrences and facts. Combined sync uses these same phases and removes
deleted symbols after a successful join. See [the current boundary](../planning/incremental_strategy.md#agreed-parsingcollection-slice--implemented-resolution-deferred).


Model owns the shared compiler roots. `Compiler_Lifecycle` sequences initialization,
resets, built-in installation and tree cleanup. Model does not call processors.
Workers process records; retained records
contain data, initialization and representation-level access/navigation methods. Storage<T> is the numeric shared
object-list boundary; scalar lists remain explicit typed arrays.
`Key_Storage_List<T>` owns duplicate-key membership, ordered traversal and lookup.
Storage and Keyed_Storage share Storage_Abstract. Module membership is keyed and
ordered; stage-result collections remain numeric. Keyed_Storage also provides
module-local source indexes.

| Root | Owned data |
| --- | --- |
| modules | One `Keyed_Storage<module>` owns keyed records in current input order, followed by tombstones. Each module owns `Keyed_Storage<source_record>` by module-relative path. |
| revision | General uint32 reconciliation counter; participating records retain inline last-seen revisions. |
| language_scope | Owns language/runtime type definitions; the built-in Simple C++ `int`, `bool`, `float`, fixed-width integer aliases and `void`. |
| global_scope | Shared global lexical scope, with language_scope as its parent. |
| prepared_files | Completed preparation records pointing to source files; AST specialization records own the facts. |
| cpp_program | Retained C++ fragments keyed by declaration/body preparation owners; cached text, includes and record-order dependencies. |
| cpp_files | Final C++ artifact names and bytes; no preparation backlinks. |
| llvm_files | llvm_module records; each owns output functions, blocks, operands and text. |

## Source identity and stage ownership

A `source_record` owns the current file snapshot, optional token result and optional
parsed result. The parsed result owns its collection and shares the exact token
result. A weak module backlink records stable membership. Source/file paths are relative to
that module; only IO and external notification boundaries construct full paths.
The source record retains pending change_state and a uint32 scan revision. Edits, deletion and
reappearance retain that identity. Read/tokenization failures preserve published tokens;
parse failures retain incomplete mutable syntax for retry.
Deletion marks retained syntax and declarations as tombstones. Overlapping module
roots (including duplicate/canonical aliases) are rejected at discovery.

`Model::tokens()`, `syntax_files()` and `collected_files()` produce temporary ordered
snapshots from source records. They are not retained roots or synchronized indexes.
Publication holds a direct source record and previous parse, so it needs neither
path scans nor a join/reordering pass. Scope replacement removes superseded live
entries by `collected_file` identity while retaining tombstones and built-ins.

Name-bearing AST specializations alone use `Collected_Occurrence`. Collection
attaches the canonical entry once; preparation reads it directly. Preparation reset
clears derived facts without clearing the occurrence. Empty/punctuation and unnamed
specializations hold no occurrence field. Declaration comparison creates temporary
records with cached structural keys and spellings; `Key_Storage_List` groups them.
These keys do not become persistent declaration identity.

## Semantic preparation direction

`File_Preparation` and specialization-attached facts are the active backend-neutral
semantic model, currently consumed by C++ generation. Future semantic work extends
that model toward one shared preparation path.

The separate LLVM preparation stack is parked in `05_backend/llvm/` solely for
regressions. Its `LLVM_Legacy_Name_Preparation`, template checkers and token-indexed
`llvm_legacy_prepared_names` are not shared preparation owners. No new language
semantics should be added independently there. Resuming LLVM requires a review and
adaptation to consume shared facts, keeping only LLVM-specific lowering in the
backend. The two implementations remain behaviorally separate in this isolation task.

## AST graph

parsed_file.root owns a file_node. A property-free abstract ast_node defines common
operation/inspection contracts; concrete nodes own their named syntax fields and
uint32 spans. There is no separate payload object or sibling chain. Typed worker
hooks traverse owned fields. Syntax owns no inspection-parent links. See [AST layout](ast_layout.md) for ownership and traversal rules.

In the incremental parser, parsed_file.scopes owns the file declaration scope and
replacement executable scopes. Function specializations own retained signature
scopes; struct specializations own retained member scopes. Function bodies and occurrences
observe their lexical scope. Global name pools index the same collected declarations.
Retained declaration entries preserve their storage positions and syntax identity;
obsolete body/reference entries leave holes. During parsing an early registered
symbol may have unfinished required syntax fields. Its file remains incomplete,
and consumers must wait for the successful parsing/collection join before reading
those fields.

## Lifetime and mutation

Storage owns membership and retains objects. Reads return the same object; edits
through references are shared. Removal/replacement changes membership but leaves
old retrieved handles alive. No view or collection readonly protocol remains.
See [Storage](../storage/STORAGE.md) and [ownership](ownership.md).

Required fields are nonnullable and must be assigned before read/publication.
Nullable is explicit. file.tokens is an optional backlink initialized/reset to null.
file.content is the input for the next scan; token_list.content is the retained
source snapshot for existing token spans. Successful File_Loader reload clears the
file token backlink; a failed read preserves the prior record. Direct loader calls
do not invalidate Model roots; rebuild through the coordinator before using new
stage results. See [the initialization audit](../lifecycle/initialization_audit.md).

Compiler.init reconciles module keys, paths and positions. Any configuration change
resets compilation roots without walking discarded ASTs; identical configuration
preserves them. `Compiler_Lifecycle::reset()` explicitly starts a fresh session,
including module identities. See [module synchronization](../lifecycle/incremental.md#module-reconciliation-and-full-reset). Tokenization clears syntax,
collection, global scope, LLVM and all file token backlinks before rebuilding.
Parsing clears syntax/collection/global scope/LLVM; LLVM generation clears old output.
Completed files can publish before a later file fails: this is not atomic rollback.
Static roots are shared between Compiler instances, not isolated compilation sessions.

Experimental LLVM preparation records remain transient. S2S facts are owned by
AST specialization records; Model::$prepared_files retains completed-file records.
Generated output owns copies of incoming
operands and does not depend on preparation lifetime. Existing source/AST purity,
failed-stage behavior and native sample execution remain regression requirements.

## Native direction

Start with Storage<T> holding shared_p<T>. Per-file segmentation remains useful,
but special allocation, compact payload stores, weak-reference machinery and bulk
serialization are future native work. Strong object cycles still require deliberate
cleanup/review. Ownership comments express intent, not implemented native weakrefs.
Issue #242 now requests replacement of the old native API with simple vector/hash
wrappers. Updated PR #243 delivery and bindings remain pending; no native
layout/binding is claimed by this change.

## Collection choices during LLVM preparation

- Storage: prepared files, each file's functions, ordered parameters, and the
  append-only pending-instance queue. The queue is traversed by position because
  preparing a body can append newly demanded instances; it never removes members.
- Keyed_Storage: struct types by emitted name, fields by source name, external
  targets by emitted name, and the worker's exact definition/argument registry.
  Repeated external use replaces the same handle without changing first-use order.
  Field insertion uses add() after source duplicate diagnostics.
- Typed arrays: scalar lists, sparse token/declaration
  indexes, and locals keyed by source declaration position. Those integer keys
  are not Storage append positions; do not cast them to strings to fit Keyed_Storage.
- SplObjectStorage: transient identity indexes from declarations/files to their
  prepared records. These are PHP identity adapters still awaiting conversion.

Collection type does not redefine record ownership: external targets and worker
registries reference the functions belonging to prepared files. Struct definitions
and imported uses share the same type object. Indexed fields/names must remain
stable during a preparation run; rebuilding creates fresh collections and indexes.


### Object-identity index conversion

Transient SplObjectStorage indexes in template/LLVM preparation now explicitly
bind to `hash<Value, shared<Key>>`. The PHP carrier preserves object identity;
native uses its existing shared-pointer-key hash. Workers build/publish each index
without depending on membership aliases after publication. This does not add IDs
or change retained ownership. See [binding details](../../../../specs/portability/object_hashes.md).


### Typed syntax access

The property-free abstract `ast_node` supplies common contracts. Concrete nodes own
narrow typed fields, applicable facts and exact fixed kind methods. Workers consume
those fields through typed dispatch; `children()` is an inspection
API, not a processing path. Inspection parents are deferred until needed. No payload accessors, sibling links or parallel child
membership remain. See [AST layout](ast_layout.md).

collected_name.collection names the owning occurrence collection. collected_file
keeps its token snapshot private; token_snapshot() and source_file() expose the
requested records directly. parsed_file also exposes source_file() and root_scope().
These accessors preserve identity without introducing more stored backlinks.

Direct scope links (`scope.enclosing`, `function_body_node.local_scope`,
`collected_name.scope`) now carry adjacent `weak<scope>` annotations for native
conversion. PHP keeps strong references; native consumers explicitly acquire live
handles. Keep the owning parsed file/model alive when scope access is required.
Other weak-intent tags remain documentary. See docs/architecture/ownership.md.

## Private parsing and compiler publication

The compiler now parses each file into its own root scope. It publishes completed
root declarations as references in global_scope. A file root's native weak
`publication` link directs semantic lookup to that shared index without changing
local ownership or hiding cross-file duplicate candidates. Function locals remain
private. The transient compiler parse queue is not retained in Model.
See [work queue](../lifecycle/work_queue.md) for the sequential PHP executor and
bounded native execution with locked completion-order publication. Unchanged sources retain their existing parsed results; syntax is replaced per updated file.

## Per-file frontend pipeline

Module scanning checks mtime/size and updates module-relative membership without reading source bytes. A discovered file has disk_source=true;
its initially empty content/zero metadata are pending placeholders. Tokenizer reads
those files in the worker, then scans their bytes. Explicit in-memory records keep
disk_source=false. Each Compiler.exec_llvm work order immediately parses its own token
result and publishes the completed file under the existing lock. Stable source
membership supplies input order independently of worker completion. See docs/lifecycle/work_queue.md for explicit
stage entrypoints and failure/publication boundaries.

## File synchronization

See [incremental sync](../lifecycle/incremental.md). file and collected_name carry only a
changes field; tokens and AST have no flags and are replaced completely. Existing
source records retain deleted files; successful-join cleanup removes deleted symbols
from collected storage and global indexes.
Consumers must filter tombstones before accessing their old syntax/scopes. No new
change-record store or persistent identity layer is introduced.

## v0.2 types, scopes and preparation

`scope` encapsulates its parent/publication observers, template slots, declaration
indexes and type-definition store. Callers register declarations, request local
candidate snapshots or use `Scope_Lookup::types` for nearest-live parent lookup.
Scope owns its `Storage<type_definition>`; `Scope_Publication` shares source type
objects from the file scope. Source definitions retain their collected declaration;
built-ins have no source declaration. Replacement removes superseded live references
and retains existing deletion evidence. Publication is not an extra lexical parent.

The language/runtime scope registers `int` (signed, 64 value bits) and `bool`
(one semantic value bit) in code. The width field uses the supported portable `uint32` annotation. Categories,
origins and emission strategies use enums; no native byte-size claim is made for
PHP objects or converted enum layouts. Runtime/library JSON import is not implemented
in this slice. No secondary global type-name registry or constructed-type model was added.

`File_Preparation` owns a transient source-order scope and returns a fresh
`prepared_file` completion record referencing its source. Binding, integer-literal, boolean-literal
and variable-reference structures own their typed preparation slots and local cleanup.
The common prepared_expression contains only type. prepared_integer_literal adds
required decimal text; prepared_boolean_literal adds a required bool value;
prepared_variable_reference adds its required weak declaration.
Concrete syntax categories extend the property-free ast_node base.
There are no per-file token-keyed fact maps or reverse `syntax` links. Binding
initializers remain ordinary AST children; generation reads their attached facts.
Declaration links in facts are explicitly weak observers of collected occurrences;
canonical type links retain their definitions. An inferred declaration uses its
existing binding occurrence as identity. Parsed classification, source declaration
inventory and published scopes remain unchanged.

`Preparation_Cleanup::tree` uses typed maintenance dispatch over owned syntax fields
and calls each concrete node's `clear_preparation` method. Syntax-only
records do nothing; expression and binding records clear their own slots. Compiler_Lifecycle resets clean the retained tree before dropping/replacing
roots. Incremental preparation clears only selected body facts and compares declaration
facts before notification. Preparation owners retain change/error state across increments;
success settles it, failure propagates to consumers, and independent work can complete.
Pending work retries without source edits; failed declaration facts cannot be consumed.
Recovery notifies consumers even for equivalent facts. Unexpected exceptions escaping preparation force a full rebuild on the next attempt.
Internal `RuntimeException` classification remains debt; partial facts are not rollback snapshots. C++ emission
failure clears output but preserves completed shared preparation. Old prepared-file handles reference the
same mutable source tree, not immutable snapshots of its former facts. Dependency
selection is owned by Preparation_Worker; explicit reset marks all owners pending.

`Compiler::prepare()` prepares synchronized sources and publishes completed facts
without emitting code. `cpp()` consumes that preparation, and rejects an unprepared
input. Repeated emission preserves fact identity. `exec_cpp()` and `update_cpp()`
remain end-to-end entrypoints: synchronize, prepare, then emit.
`reset_preparation()` clears facts, completion records and dependent C++ output;
`reset_cpp()` clears only C++ artifacts. Explicit `exec_llvm`,
`update_llvm` and `llvm` remain experimental regression entrypoints. The current C++
path requires one live source file with straight-line int/bool bindings/references
and optional entry returns. Synchronization resets preparation before processing;
preparation publishes only on success, and emission publishes only complete output.
Generated artifacts own only names and text. See [the slice](../s2s_integer_slice.md).

## Structure and processing boundary

Structures own representation, construction, local consistency, navigation and
small data queries. Processors own stage ordering, publication policy, resolution,
preparation and emission algorithms. Specializations may route process operations
and expose structural child order; each operation has one traversal owner.
Local index maintenance and per-node fact cleanup remain
structure operations; deciding when to invoke them belongs to a processor.

`Scope_Publication` selects declarations and types for publication/replacement,
including tombstone retention. Scope exposes declaration/type membership snapshots,
registration, replacement and parent/publication links without selecting update policy.
`Source_Publication` calls that processor and keeps model roots synchronized after
each completed publication so a later file failure preserves prior completed work.

Binding syntax uses `syntax_kind`; prepared facts use
`resolved_kind`. Their meanings remain distinct: an untyped first write is unresolved
syntax even when preparation identifies a declaration. No new validation rules,
source-language forms, storage representation, or LLVM lowering rules were added.

`exec_cpp()` / `update_cpp()` and `exec_llvm()` / `update_llvm()` identify the backend
explicitly. `prepare()` processes synchronized sources, `cpp()` emits prepared sources, and
`llvm()` retains its experimental preparation/emission path. Direct parse
publication goes through Source_Publication, without a Compiler forwarding wrapper.

### Floating scalar facts

`float_literal_structure` owns optional `prepared_float_literal` facts, cleared
locally like integer and boolean facts. Its decimal string retains the exact source
mantissa/exponent; preparation never converts it to a host number. The canonical
language-scope `float` definition is signed, 64-bit and shared by bindings and
references. C++ representation and rounding belong to the backend/toolchain.

### Specialization operation hooks

Agreed 2026-09-26: specialization records may provide dispatch/traversal hooks for
compiler operations, including backend-specific ones. Algorithms remain in their
processing owners. A phase worker starts an independent pass; interlinked operations
may be delegated when needed. Each operation defines one traversal owner to avoid
double visits or unintended execution order. The abstract base should implement
shared operation interfaces once. The base implements `ast_node_i`; concrete hooks
forward to typed preparation, C++ and maintenance worker interfaces. Algorithms use
per-invocation context records; syntax never retains those contexts. Unsupported
operations throw before walking children. Native optimization of virtual calls
requires separate evidence. See [dispatch ownership](ast_layout.md#specialization-dispatch).

### Retained C++ generation

`collected_file.preparation_changes` is an identity-keyed handoff of completed
semantic changes and deleted owners. C++ consumes it only after successful assembly.
Preparation versions remain the completion stamps; repeated preparation before
emission cannot lose pending generation work.

`Model.cpp_program` owns separate fragments for signatures, record definitions,
function bodies and file executable bodies, keyed by their existing preparation
owners. Selection builds definition/body work lists from missing, dirty or outdated
fragments. Successful rendering replaces one fragment; a failed render leaves it
dirty, preserves pending handoff and publishes no complete output. Unchanged fragments
retain their objects and text. Unexpected generation exceptions request a full rebuild.

Assembly still walks top-level declarations to preserve current order; it does not
walk unchanged bodies. Struct dependencies order cached definitions. Includes are
reassembled from current fragments, so removed fragments cannot leave stale includes.
`reset_cpp()` drops final artifacts and retired fragments; compilation/syntax reset
also drops the complete fragment store. No filesystem publication is introduced.

Generated names use role prefixes and normalized source names in the currently
supported scopes. They are deterministic across fresh/incremental runs and independent
of token positions. Function-body temporary counters are independent. Namespace,
overload and richer shadowing support must review naming when those features arrive.
Output partitioning remains deferred. Token/source storage now appends new inputs;
retained nodes keep their indexes until post-output cleanup. See the appended-token
section in the incremental strategy for ownership and failure handling.


Maintenance traversal is node-owned: specialized `maintain()` methods enumerate
named owning syntax fields. Transient workers implement node entry, child-edge
handling and additional token-index mapping. Edge handling controls recursion;
inspection-parent links and their attachment worker are absent. Preparation and generation retain
their existing specialized worker dispatch. No generic maintenance visitor class
or inspection iterator is needed for these operations.
