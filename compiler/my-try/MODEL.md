# Retained compiler model
Doc Status: supporting

Model owns the shared compiler roots. Workers process records; retained records
contain data and collection initialization only. Storage<T> is the numeric shared
object-list boundary; scalar lists and name indexes remain explicit typed arrays.
Storage and Keyed_Storage share Storage_Abstract. Root collections remain numeric;
Keyed_Storage is used for named object collections during LLVM preparation.

| Root | Owned data |
| --- | --- |
| modules | module records; each owns a Storage<file>. |
| tokens | token_list records; each owns source snapshot text and Storage<token>. |
| syntax_files | parsed_file records; each owns a root AST and Storage<scope>. |
| global_scope | Direct shared global lexical scope. |
| collected_files | collected_file records; each owns Storage<collected_name> and local position work lists. |
| llvm_files | llvm_module records; each owns output functions, blocks, operands and text. |

## AST graph

parsed_file.root owns its ast_node. Each node owns its optional concrete payload.
Payload child collections use Storage<ast_node>: block children, function parameters,
call arguments/template arguments, array elements and struct fields. Single children
(body, operands, type syntax, etc.) are direct object links. No node positions or
per-file node/payload registries are needed. Parser productions return ast_node.
Node-kind/payload compatibility is checked at construction by Syntax_Nodes.

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

Preparation records remain transient. Generated output owns copies of incoming
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

The optional node_interface payload remains directly node-owned. Syntax_Nodes now
exposes typed *_data accessors using checked, identity-preserving object_cast.
Compiler consumers use these accessors instead of implicitly reading concrete fields
through an interface handle. Null or wrong payload types fail. PHP graph identity is
unchanged; native interfaces are polymorphic for checked narrowing.
