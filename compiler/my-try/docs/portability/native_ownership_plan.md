# Agreed native ownership direction
Doc Status: historical

Superseded by the [current model](../architecture/MODEL.md) and [collection API](../storage/STORAGE.md).
Views, parallel AST registries and inline-row lifetime machinery are not current
requirements. The text below records earlier exploration only; references to removed
helpers/tests and “current” behavior describe that historical checkpoint.

## Latest decision: shared records for the initial port

Default native Storage record representation is now `shared_p<T>`, with T still
the public record type in Storage/Storage_View annotations. Reads return shared
handles; field edits preserve identity; retrieved handles survive replacement or
removal. Views continue selecting current owner positions. This supersedes the
inline-row/bounded-reference direction below for the first integration; per-file
segmentation and the owner/view split remain. Inline storage/layout optimization
is future work. Shared ownership does not solve cycles; deliberate cleanup remains
necessary. The native worker was asked to update its contract and PR, not the PHP
helper. [Published request](https://github.com/alexstanciu-1/simplecpp/issues/242#issuecomment-5812738509).

Accepted as an incremental direction, not a completed native implementation or a
requirement to perfect every layout before continuing conversion. PHP remains the
behavioral development surface. No source record has been automatically promoted
to a native value struct by this agreement.

## Owners and allocation boundaries

| Owner | Owned data / native direction |
| --- | --- |
| Model.modules | Module records; each module owns its files. |
| module.files | File records and their loaded input text/metadata. |
| Model.tokens | Per-source-file tokenization result owners. |
| token_list | Captured source bytes and value-stored token spans; native token spelling borrows that buffer. |
| Model.syntax_files | Per-source-file parsed results. |
| parsed_file | Separate value-record stores listed below, including local scopes. |
| Model.global_scope | Direct global scope ownership. |
| Model.collected_files | Per-source-file occurrence results. |
| collected_file.entries | Value-stored occurrence records; work lists contain local positions. |
| Model.llvm_files | Independent generated-output owners. |
| llvm_module.functions | Function records. |
| llvm_function.parameters / blocks | Operand values and block records. |
| llvm_block.instructions and LLVM output sections | Owned output strings. |

parsed_file stores: nodes, scopes, blocks, functions, parameters, calls, binaries,
expression_statements, returns, bindings, array_types, array_literals, indexes,
structs, fields, field_accesses. Each concrete payload stays in its own store.
Node kind selects the payload store; a local position selects a payload record.
Exact encoding and integer widths remain deferred. Records containing strings or
containers are not automatically bulk-serializable byte blocks.

Few module/file/result-owner records prioritize stable access and clear ownership;
high-volume token, node, payload and occurrence stores prioritize avoiding separate
allocations per row. No particular arena or growth algorithm is selected.

## Views, references and indexes

AST views keep ordered membership into the same parsed_file.nodes store:
block.children, function.parameters, call.arguments, call.template_arguments,
array_literal.elements and struct.fields. View membership does not own nodes.
Read-only membership does not prevent editing record fields. Position-list versus
linked traversal stays in native_layout_debt.md.

Single-node AST links and root selection use local node positions where the owner
is already known. Cross-owner references identify the owner and position. Records
do not need an extra stored ID merely to repeat their position.

Required source dependencies: token_list.file, parsed_file.tokens,
collected_file.source, collected_name.node and AST syntax operands/type links.
Convenience/backward/context links remain non-retaining: file.tokens,
parsed_file.collection, collected_file.root, collected_name.file/scope,
block.scope and scope.parent. Weak does not prescribe std::weak_ptr and does not
imply nullable. Explicit ? alone permits a valid absent state.

Scope variables/functions/types maps index collected entries without owning them;
cross-file entries retain owner identity in their reference. Template-name maps,
token spans and collected-file work lists remain scalar indexes owned by the
relevant record. Mutation owners maintain/invalidate indexes before targets vanish.

## Preparation and execution data

Preparation results own prepared files; each owns its prepared_names and function
records. A preparation-wide type owner is the proposed clear owner of prepared
struct types. Per-file struct_types maps borrow from it; current PHP maps share
objects and have not yet been migrated to this owner.

Prepared functions own locals and parameters. Calls/external_functions refer to
existing prepared-function records; field-use maps refer to prepared type fields.
A prepared type owns its fields. Local array metadata and incoming parameter
operands are directly owned values; struct metadata and parameter-local links
reference the appropriate existing owners. llvm_place is a temporary value with
borrowed metadata. Preparation borrows source/AST/collection data. Worker registries
and pending lists index these owners rather than duplicate ownership.

Policy is operation-owned configuration. Emitted operands are independent copies;
generated output does not depend on preparation/source lifetimes. native_result
owns its build result and optional execution result directly.

## Lifetime direction and remaining delivery work

Stores own records. Views and ordinary row links do not independently retain each
row. Growth preserves logical references. Removing a row/discarding its owner
requires dependents to be cleared or invalidated before further use. Retaining an
old result requires retaining all necessary owners, not just one node store (for
example, syntax depends on tokenization and lexical context too).

This is a deliberate change from the PHP helper's currently tested survivor-handle
behavior after row removal/replacement. Do not silently claim parity between those
contracts. Before native compiler integration, reconcile the Storage delivery brief
and GitHub issue #242, define checked invalidation/borrow behavior, and adapt the
relevant tests. That work is tracked debt; this plan does not implement runtime
weak references, owner pinning, incremental snapshots or reference validation.

Proceed in small convertible components. Keep linked AST encoding, widths,
serialization format and allocation tuning deferred until native measurement.
