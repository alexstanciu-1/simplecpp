# Specialized AST nodes
Doc Status: supporting

The active PHP model uses a property-free `ast_node` abstract base and concrete
syntax nodes. Each node owns its named syntax fields, source span and applicable
facts. There is no outer node/payload pair, mutable kind/payload agreement, sibling
chain or parallel child list. The completed migration retired its frozen review
proposal; see the [migration audit](../archive/specialized_ast_migration_audit.md)
for implemented differences and the bounded native iterator checkpoint. Git history
retains the former `03_parse/proposal/` snapshots when historical comparison is needed.

## Typed ownership

`function_node` owns parameters, a `type_node` return type and a
`function_body_node`. `struct_node` owns fields. `file_node` owns file declarations
and a separate executable body. Statements cannot contain file declarations:
functions/structs extend `declaration_node`, whereas explicit local variables,
expression statements, returns and blocks extend `statement_node`.

Untyped writes use `assignment_expression_node` inside an expression statement.
Explicit typed declarations use `variable_declaration_node`. Both preparation paths
share storage/binding algorithms; assignment facts own the resolved binding and
result type. Plain-variable chains retain nested right-associative assignment nodes;
preparation classifies each write from inner to outer and C++ statement generation
lifts declarations into the surrounding body without duplicating the RHS. Assignment
expressions also produce stored-value snapshots in admitted sequenced contexts;
compound updates have their own target-owning node. New locals remain confined to
standalone assignment chains. See the [operator contracts](../s2s_scalar_operators.md).

Name-bearing nodes save their spelling. Half-open uint32 source spans
`[first_token_index, end_token_index)` are provenance, not symbol keys. Common
span methods preserve typed access through a property-free base. Exact name token
positions remain on collected occurrences for diagnostics and parked LLVM indexes.
Grouping parentheses normalize to the inner expression node. The inner span remains
its own; enclosing expressions capture consumed parentheses in their source extent.
There is no retained grouping identity. The parser uses read-only type-form lookahead
to distinguish casts; see [the grouping decision](../catalog/02_expressions.md#expr-paren-001)
for its grammar boundary and proofs.
`constant_reference_node` is a direct expression specialization, distinct from the
assignable `variable_reference_node`; preparation resolves its occurrence to a
scope-owned immutable definition without changing that syntax distinction.

Only name-bearing nodes use `Collected_Occurrence`. Its nullable weak observer
rejects replacing a live occurrence; there is no second attachment boolean. Retired
nodes must not re-enter the active graph. Declaration reconciliation keeps the
existing node and occurrence. Renames follow existing remove/add reconciliation.

## Interpolated strings

`interpolated_string_node` owns `Storage<interpolation_part_node>` in source order.
The property-free part base has two concrete forms: `interpolation_text_node` owns
literal provenance and decoded-string facts; `interpolation_value_node` owns an
ordinary variable reference and a string-conversion decision. There is no synthetic
binary operator, cast syntax or duplicate semantic child list.

Each part owns a token span plus byte offset/length within the whole quoted token.
Token cleanup remaps the token span; relative ranges stay valid. A variable reference
inside a value part uses the containing token for collection provenance; the part
provides the precise insertion range. Repeated references have distinct collection
identities. Typed maintenance traverses parts and their expressions, so preparation
cleanup and retained-body compaction use the existing lifecycle.

## Bracket expressions

`index_node` owns a required receiver (`base`) and optional argument (`index`). Both
arities dispatch to the same operator-preparation owner. A missing argument is arity
zero, not an append tag or synthetic expression. Its existing syntactic assignable
base admits target syntax without deciding whether an eventual overload result is
writable. Typed maintenance and inspection omit the absent child. Overload selection
and result/access facts remain deferred; see the [bracket contract](../../../../specs/index_operator.md).

## Scopes and lifecycle

File/body scopes are required weak observers of scopes retained by the parsed file
or external owner. Functions own signature scopes; structs own member scopes.
Accessors retain meaningful names: `file_scope()`, `local_scope()`,
`signature_scope()` and `member_scope()`. The occurrence already identifies its
enclosing lexical scope. Ordinary nodes do not duplicate that link.

The file scope and file executable-body scope are distinct. The latter's variables
are file-local. Function-body scopes parent to signature scopes, which parent to
the file scope. AST nodes have no inspection-parent links; lexical lookup follows scopes.

A function's required body may not yet exist during an incomplete first parse.
`has_parsed_body()` queries explicit construction state without reading the field;
`set_parsed_body()` attaches a successful body and marks it established. A failed
later parse preserves the old body and this state. File completion remains separate.

Each executable body owns one optional canonical preparation work record.
Unchanged bodies keep syntax, scope, occurrences and facts. Replaced bodies receive
the same work identity; dependency cleanup and preparation replace its derived
state. Declaration/signature work remains on the collected declaration. Successful
preparation settles body `syntax_changed`; failed/pending work persists separately.

Preparation and generation check active owners at their entry boundaries. Deleted
owners/declarations and incomplete parses reject execution. Generation additionally
requires ready, successful preparation. Recursive operations do not repeat deletion
checks for every child. Cleanup deliberately accepts deleted syntax. A retained
node or iterator does not by itself authorize semantic work after retirement.

## Specialization dispatch

Nodes receive `preparation_context` directly in `prepare()` and forward themselves
to the appropriate typed preparation algorithm. Facts remain attached to the node;
algorithms and statement traversal remain in preparation classes. No separate
preparation adapter is allocated, and nested work retains its own explicit context.
`generate_cpp` continues to dispatch through `CPP_Syntax`.
For maintenance, each node enumerates its own named owning fields in grammar order;
workers implement `enter`, `edge` and `token_index`. The edge callback chooses
whether to recurse. No abstract maintenance visitor or per-kind visitor methods
remain. Token-index remapping is supplied by the worker, including extra sites
such as a binary operator. Unsupported operations fail explicitly. There is
no fallback that silently walks unsupported syntax.

The `Preparation_Facts` trait groups access, replacement and local clearing. Each
concrete node retains its exact nullable field and required typed accessor. Required
same-type nullable/weak returns use the checked return boundary; actual narrowing
still uses `object_cast`. `prepared_assignment` belongs to preparation structures.

Reused syntax retains indexes
into appended token/source storage during compilation; collection stamps retained
occurrences by reused intervals. `Token_Cleanup` normalizes indexes after output
or before the next scan. `Preparation_Cleanup` visits typed owned edges and clears
local facts. None uses the debug iterator to run compilation.

## Inspection

Inspection parents are deferred until a concrete consumer needs them. There is no
parent field, accessor or attachment pass. `children()` returns an independent lazy
`child_iterator_i`. Composite cursors retain their source, and single-list cursors
retain the original collection. No child lists are copied. Order is grammar order;
file inspection groups declarations before its executable body. Mutation during
iteration is unsupported. Cursors are forward-only; restarting requires a fresh
cursor. Iterator keys are traversal positions, not AST IDs.

PHP inspection and the production common/function cursors have bounded native
proofs. `Storage_Cursor<ast_node>` widens yielded handles from typed child
collections without widening mutable collection membership. Do not infer full
compiler portability from those proofs.

## Deferred work

- Punctuation/token storage and inspection allocation costs require later profiling.
- Native devirtualization and memory/layout gains are not claimed.
- Appended-token cleanup scheduling/performance, C++ partitioning and parked LLVM
  index-map convergence remain in the incremental/v0.2 planning documents.

### Deferred narrowing after a type or kind check

Keep `object_cast` for actual class narrowing, including after a matching
`instanceof`/kind guard, until the new S2S path can lower the established type without
repeating the check. This is toolchain debt, not a reason to weaken typed boundaries.
Same-type nullable extraction is a separate operation; see the portability guide.
