# Specialized AST nodes
Doc Status: supporting

The active PHP model uses a property-free `ast_node` abstract base and specialized
syntax nodes. Named syntax fields, source spans and applicable facts belong to
their semantic node family; `statement_body_node` shares ordered body storage and
behavior between functions and blocks. There is no outer node/payload pair, mutable kind/payload agreement, generic sibling
chain or parallel child list. The completed migration retired its frozen review
proposal; see the [migration audit](../archive/specialized_ast_migration_audit.md)
for implemented differences and the bounded native iterator checkpoint. Git history
retains the former `03_parse/proposal/` snapshots when historical comparison is needed.

## Typed ownership

`statement_body_node` is an abstract statement owning `Storage<statement_node>`
and its source span. It shares construction, inspection and maintenance.
`block_node` is an empty final subclass; `function_body_node` adds executable-unit
scope/work state. Analysis and C++ workers select root versus block processing themselves.
Shared workers accept the abstract body type.
Algorithms remain worker-owned. This body abstraction is a named language concept,
not a generic AST child/payload container.

`if_node` owns a block body, an explicit arm kind, an optional condition and an
optional `next_arm`. Conditions are required on if/elseif and absent on else;
else has no successor. Only the initial arm enters the enclosing statements.
`next_arm` is a conditional-specific owning syntax edge. Inspection and maintenance
visit condition, body and next arm in order; preparation and emission each own their
typed traversal. Branches and standalone nested braces share the same block node.
No nested block introduces a separate preparation work item or parser scope owner.

`conditional_expression_node` owns a condition, optional true branch and required
fallback. An absent true branch means shorthand; no kind flag or duplicated condition
edge is retained. Inspection and maintenance visit the present fields in that order.
Preparation attaches truthiness, result type and branch conversions; emission owns
the shorthand snapshot temporary. Both spellings produce nonaddressable values.

`match_expression_node` owns its selector and `Storage<match_arm_node>` in source
order. Each arm owns `Storage<expression_node>` labels and one result expression;
empty labels mean default. Lazy inspection and maintenance visit selector, arms,
labels and results without copying membership. Preparation attaches exact label
decimals and arm conversions. There is no shared statement-selection superclass:
match has neither bodies nor control-transfer targets. Emission postpones default
without reordering retained syntax.

`loop_node extends breakable_node` owns one block body and common prepared facts.
While/do-while own a condition and inspect it before/after the body respectively.
For owns three ordered typed collections (initialization statements, conditions,
updates), inspected in header order followed by the body. Maintenance visits those
same named edges; execution order is not inferred from inspection order. The reserved
abstract `foreach_node` shares the loop model but has no executable/parser support;
its eventual processing extends the existing stage-owned loop workers.
Break/continue nodes retain a weak prepared target, never an owning parent link.

`switch_node extends breakable_node` owns its selector and `Storage<switch_case_group>`.
Each group owns `Storage<switch_label>` and one `block_node`; adjacent labels share
that body and its lexical scope. Labels have an explicit value/default kind, with an
expression only for value labels. Lazy inspection and maintenance traverse selector,
groups, labels and bodies in source order. Analysis-owned `Integer_Constants` selects
supported literal/reference/unary-sign forms over their prepared facts; syntax has
no constant-evaluation processing hook.

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

## Data construction syntax

`data_construction_node` replaces the positional-only `array_literal_node`. One
parser entry loop handles PHP-like brackets and JS-like braces. The node owns an
optional `target_type` and `Storage<data_entry_node>`; each entry owns an optional
key and required value. Absent keys are positional, never null sentinels. Independent
inspection cursors and maintenance traverse target, entries, key then value.

`source_representation(token_list)` derives PHP-like/JS-like from the opening token;
no representation flag or extra opening-token index is retained. With an explicit
target the opening follows its type span and `::`. Span maintenance therefore
preserves this query without another index-remapping rule. Tokens remain immutable
provenance; the parsed-file/caller supplies their lifetime.

Bare brace property names are ordinary `string_literal_node` leaves without collected
name occurrences. `literal_spelling(token_list)` normalizes bare identifiers to
quoted spelling for the ordinary string decoder; no data-notation branch is added
to preparation. Quoted and bare keys retain their real source spans.

The construction attaches `prepared_data_construction` (binding and result type);
each entry attaches `prepared_data_entry` (optional key conversion and required value
conversion). Existing maintenance clears these facts. Syntax remains the sole ordered
entry/expression graph. `Data_Construction_Preparation` owns contextual typing and
factory-binding selection; neither node has a processing hook. Source lifetime
enforcement and C++ construction remain deferred.
The parked LLVM reader only adapts to the renamed positional entry structure;
it rejects keyed/brace/explicit-target constructions and gains no new lowering.

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

Analysis owns expression/type/statement selection and auxiliary syntax processing.
Typed handlers consume named fields and attach the same specialized facts; each
operation owns its traversal and invocation-local context. AST nodes expose no
`prepare()`, completion, loop-processing or work-selection hooks. Executable roots
use their seeded environment; nested blocks enter and restore a child environment.
C++ dispatch belongs entirely to `CPP_Syntax`: `expression()`, `statement()` and
`interpolation_part()` select typed backend handlers. Known concrete children are
passed directly to their handlers. No AST `generate_cpp()`, C++ worker interface or
universal `accept()` protocol remains. Declaration fragments continue through the
backend's existing typed declaration path; no extra generic declaration walker is
needed. No shared compilation visitor replaces the removed hooks.
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
