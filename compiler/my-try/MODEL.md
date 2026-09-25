# Retained compiler model
Doc Status: supporting

Model owns the shared compiler roots. Workers process records; retained records
contain data, initialization and representation-level access/navigation methods. Storage<T> is the numeric shared
object-list boundary; scalar lists and name indexes remain explicit typed arrays.
Storage and Keyed_Storage share Storage_Abstract. Root collections remain numeric;
Keyed_Storage is used for named object collections during LLVM preparation.

| Root | Owned data |
| --- | --- |
| modules | module records; each owns a Storage<file>. |
| tokens | token_list records; each owns source snapshot text and Storage<token>. |
| syntax_files | parsed_file records; each owns a root AST and Storage<scope>. |
| language_scope | Owns language/runtime type definitions; currently the built-in Simple C++ `int`. |
| global_scope | Shared global lexical scope, with language_scope as its parent. |
| collected_files | collected_file records; each owns Storage<collected_name> and local position work lists. |
| prepared_files | Complete per-file expression and binding facts for the C++ path. |
| cpp_files | Final C++ artifact names and bytes; no preparation backlinks. |
| llvm_files | llvm_module records; each owns output functions, blocks, operands and text. |

## AST graph

parsed_file.root owns its concrete ast_node subclass. The abstract base owns an
optional node_structure and private first-child/next-sibling links, with native
weak parent/previous links and a uint32 child ordinal. Node token spans are uint32.
Named structure child fields/lists remain retaining aliases for existing workers.
The parser validates and links completed children before publishing each node.
See [AST layout](docs/ast_layout.md) for child order, traversal and mutation rules.

parsed_file.scopes remains the uniform owner of local scopes and a standalone
parser's root scope. Blocks reference the appropriate local/global scope. Global
name pools reference collected entries; these are indexes, not additional declarations.
Collection entries keep their existing local positions and syntax object identity.

## Lifetime and mutation

Storage owns membership and retains objects. Reads return the same object; edits
through references are shared. Removal/replacement changes membership but leaves
old retrieved handles alive. No view or collection readonly protocol remains.
See [Storage](helpers/STORAGE.md) and [ownership](docs/ownership.md).

Required fields are nonnullable and must be assigned before read/publication.
Nullable is explicit. file.tokens is an optional backlink initialized/reset to null.
file.content is the input for the next scan; token_list.content is the retained
source snapshot for existing token spans. Successful File_Loader reload clears the
file token backlink; a failed read preserves the prior record. Direct loader calls
do not invalidate Model roots; rebuild through the coordinator before using new
stage results. See [the initialization audit](docs/initialization_audit.md).

Compiler.init resets all roots before loading. Tokenization clears syntax,
collection, global scope, LLVM and all file token backlinks before rebuilding.
Parsing clears syntax/collection/global scope/LLVM; LLVM generation clears old output.
Completed files can publish before a later file fails: this is not atomic rollback.
Static roots are shared between Compiler instances, not isolated compilation sessions.

Experimental LLVM preparation records remain transient; S2S preparation records are retained in Model::$prepared_files. Generated output owns copies of incoming
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
or change retained ownership. See [binding details](../../specs/portability/object_hashes.md).


### Concrete payload access

The optional node_structure payload remains directly node-owned. Syntax_Nodes now
exposes typed *_data accessors using checked, identity-preserving object_cast.
Compiler consumers use these accessors instead of implicitly reading concrete fields
through an interface handle. Null or wrong payload types fail. PHP graph identity is
unchanged; native interfaces are polymorphic for checked narrowing.

Direct scope links (`scope.enclosing`, `block_structure.scope`,
`collected_name.scope`) now carry adjacent `weak<scope>` annotations for native
conversion. PHP keeps strong references; native consumers explicitly acquire live
handles. Keep the owning parsed file/model alive when scope access is required.
Other weak-intent tags remain documentary. See docs/ownership.md.

## Private parsing and compiler publication

The compiler now parses each file into its own root scope. It publishes completed
root declarations as references in global_scope. A file root's native weak
`publication` link directs semantic lookup to that shared index without changing
local ownership or hiding cross-file duplicate candidates. Function locals remain
private. The transient compiler parse queue is not retained in Model.
See [work queue](docs/work_queue.md) for the sequential PHP executor and
bounded native execution with locked completion-order publication. No revision tracking or reuse exists.

## Per-file frontend pipeline

Module discovery now publishes paths only. A discovered file has disk_source=true;
its initially empty content/zero metadata are pending placeholders. Tokenizer reads
those files in the worker, then scans their bytes. Explicit in-memory records keep
disk_source=false. Each Compiler.exec work order immediately parses its own token
result and publishes the completed file under the existing lock. Model token/syntax
roots return to input order after all jobs join. See docs/work_queue.md for explicit
stage entrypoints and failure/publication boundaries.

## File synchronization

See [incremental sync](docs/incremental.md). file and collected_name carry only a
changes field; tokens and AST have no flags and are replaced completely. Existing
root stores retain deleted files; global candidate vectors retain deleted symbols.
Consumers must filter tombstones before accessing their old syntax/scopes. No new
change-record store or persistent identity layer is introduced.

## v0.2 types, scopes and preparation

`scope` encapsulates its parent/publication observers, template slots, declaration
indexes and type-definition store. Callers register declarations, request local
candidate snapshots or use `Scope_Lookup::types` for nearest-live parent lookup.
Scope owns its `Storage<type_definition>`; global publication shares source type
objects from the file scope. Source definitions retain their collected declaration;
built-ins have no source declaration. Replacement removes superseded live references
and retains existing deletion evidence. Publication is not an extra lexical parent.

The language/runtime scope currently registers `int` in code: signed, 64 value
bits. The width field uses the supported portable `uint32` annotation. Categories,
origins and emission strategies use enums; no native byte-size claim is made for
PHP objects or converted enum layouts. Runtime/library JSON import is not implemented
in this slice. No secondary global type-name registry or constructed-type model was added.

`File_Preparation` owns a transient source-order scope and returns a fresh
`prepared_file`. Its token-keyed maps own prepared expression/binding records;
those records reference source syntax, collected occurrences and canonical type
definitions. An inferred declaration uses its existing binding occurrence as its
identity. It does not mutate the AST classification, source declaration inventory,
or published scope. Later reads/writes reuse that prepared declaration. Incremental
execution rebuilds these facts; inferred declarations do not acquire separate sync
flags or dependency records in this slice.

`Compiler::exec_cpp`, `update_cpp` and `cpp` drive the C++ path. Existing `exec`,
`update` and `llvm` remain experimental regression entrypoints. The current C++
path requires one live source file with straight-line integer bindings/references
and optional entry returns. A failed sync, preparation or emission clears C++ facts
and output; complete results are published together after successful generation.
Generated artifacts own only names and text. See [the slice](docs/s2s_integer_slice.md).
