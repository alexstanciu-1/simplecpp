# Retained compiler model
Doc Status: supporting

Model owns the shared compiler roots. `Compiler_Lifecycle` sequences initialization,
resets, built-in installation and tree cleanup. Model does not call processors.
Workers process records; retained records
contain data, initialization and representation-level access/navigation methods. Storage<T> is the numeric shared
object-list boundary; scalar lists and name indexes remain explicit typed arrays.
Storage and Keyed_Storage share Storage_Abstract. Root collections remain numeric;
Keyed_Storage is used for named object collections during LLVM preparation.

| Root | Owned data |
| --- | --- |
| modules | module records; each owns a Storage<file>. |
| tokens | token_list records; each owns source snapshot text and Storage<token>. |
| syntax_files | parsed_file records; each owns a root AST and Storage<scope>. |
| language_scope | Owns language/runtime type definitions; currently the built-in Simple C++ `int` and `bool`. |
| global_scope | Shared global lexical scope, with language_scope as its parent. |
| collected_files | collected_file records; each owns Storage<collected_name> and local position work lists. |
| prepared_files | Completed preparation records pointing to source files; AST specialization records own the facts. |
| cpp_files | Final C++ artifact names and bytes; no preparation backlinks. |
| llvm_files | llvm_module records; each owns output functions, blocks, operands and text. |

## AST graph

parsed_file.root owns a final ast_node. The common node owns an
optional node_structure and private first-child/next-sibling links, with native
weak parent/previous links and a uint32 child ordinal. Node token spans are uint32.
Named structure child fields/lists remain retaining aliases for existing workers.
The parser validates and links completed children before publishing each node.
See [AST layout](ast_layout.md) for child order, traversal and mutation rules.

parsed_file.scopes remains the uniform owner of local scopes and a standalone
parser's root scope. Blocks reference the appropriate local/global scope. Global
name pools reference collected entries; these are indexes, not additional declarations.
Collection entries keep their existing local positions and syntax object identity.

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

Compiler.init resets all roots before loading. Tokenization clears syntax,
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
- Typed arrays: scalar lists, scope overload pools, sparse token/declaration
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


### Concrete payload access

The optional node_structure payload remains privately node-owned. `kind()` exposes
the fixed tag; `Syntax_Nodes::*_data` provides typed access to the specialization
for all consumers. Specializations retain named syntax fields and child lists.
These accessors return the same objects, not copies. Preparation and C++ emission
walk first_child()/next() and read the corresponding specialized record.
Payload/list mutation after publication is unsupported.

collected_name.collection names the owning occurrence collection. collected_file
keeps its token snapshot private; token_snapshot() and source_file() expose the
requested records directly. parsed_file also exposes source_file() and root_scope().
These accessors preserve identity without introducing more stored backlinks.

Direct scope links (`scope.enclosing`, `block_structure.scope_reference`,
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
bounded native execution with locked completion-order publication. No revision tracking or reuse exists.

## Per-file frontend pipeline

Module discovery now publishes paths only. A discovered file has disk_source=true;
its initially empty content/zero metadata are pending placeholders. Tokenizer reads
those files in the worker, then scans their bytes. Explicit in-memory records keep
disk_source=false. Each Compiler.exec_llvm work order immediately parses its own token
result and publishes the completed file under the existing lock. Model token/syntax
roots return to input order after all jobs join. See docs/lifecycle/work_queue.md for explicit
stage entrypoints and failure/publication boundaries.

## File synchronization

See [incremental sync](../lifecycle/incremental.md). file and collected_name carry only a
changes field; tokens and AST have no flags and are replaced completely. Existing
root stores retain deleted files; global candidate vectors retain deleted symbols.
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
No class extends ast_node.
There are no per-file token-keyed fact maps or reverse `syntax` links. Binding
initializers remain ordinary AST children; generation reads their attached facts.
Declaration links in facts are explicitly weak observers of collected occurrences;
canonical type links retain their definitions. An inferred declaration uses its
existing binding occurrence as identity. Parsed classification, source declaration
inventory and published scopes remain unchanged.

`Preparation_Cleanup::tree` walks owned child/sibling links and calls each node's
`clear_preparation` method, which delegates to the specialization. Syntax-only
records do nothing; expression and binding records clear their own slots. Compiler_Lifecycle resets clean the retained tree before dropping/replacing
roots. Preparation starts clean and clears partial facts on failure. C++ emission
failure clears output but preserves completed shared preparation. Old prepared-file handles reference the
same mutable source tree, not immutable snapshots of its former facts. No selective
invalidation machinery is introduced by this lifecycle.

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
preparation and emission. Local index maintenance and per-node fact cleanup remain
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
