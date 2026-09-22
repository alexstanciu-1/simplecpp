# Parsing
Doc Status: supporting

Status: implemented in the PHP prototype. The original call/return subset
continues through semantic analysis, lowering and LLVM emission. Local-variable
syntax and nested blocks pass name/type/lifetime analysis, storage lowering
and native execution for initialized no-cleanup scalars.
Inspection mode stops before native
building; `--output` also builds the executable.
Typed function parameters and ordered call arguments (including nested calls)
are parsed and structurally compared. Parameter names bind in root scope and
body checking and scalar parameter/argument lifetimes are implemented. Parsing
itself does not resolve names/types or produce executable output.
[Declared return-type resolution](return_type_resolution.md) now follows the frontend stages.

The compiler enters parsing through `Parser` in
[main_parse.php](../../src/03_parse/main_parse.php): assignment-only construction,
`init()` for fixed task selection, `run()` for file workers, `finalize()` for
joining, then `result()`/`store()` for the same completed `Frontend_Set`.
This is one execution per update, with terminal failure and no reset. Empty
selections still finalize to handle removals and retained frontends. The
[call map](../../src/03_parse/calls.md) and
[lifecycle rules](../code_formatting.md#step-lifecycle) show the public boundary;
individual parser workers and the segmented join retain their existing APIs.

## Grammar

```text
file       = (definition | statement)* EOF
definition = "function" identifier "(" parameters? ")" ":" identifier block
parameters = parameter ("," parameter)*
parameter  = variable_name identifier
block      = "{" statement* "}"
statement  = block | "return" expression? ";" | local_decl ";"
           | assignment ";" | expression ";" | if_stmt | while_stmt
if_stmt    = "if" "(" expression ")" block ("else" block)?
while_stmt = "while" "(" expression ")" block
local_decl = variable_name identifier "=" expression
assignment = variable_name "=" expression
expression = primary ("+" primary)*
primary    = integer_literal | variable_name | call | "(" expression ")"
call       = identifier "(" arguments? ")"
arguments  = expression ("," expression)*
```

The tokenizer already removes whitespace/comments. Names and literal spelling
remain byte spans into source; type names are not hard-coded to `int`.
No integer conversion or type/range validation happens here. Duplicate/unknown
names, return compatibility and unreachable statements belong to later stages.

Typed locals follow the strict
[language syntax](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/simple_cpp_php_strict_quick_learn.md#typed-shorthand-note):
`$count int = 42;`. This slice requires an initializer and a simple named type;
declarations without initializers and compound type syntax remain deferred.
Assignments are statements with a plain variable target. Variable leaves retain
the `$` in their source span. Visibility, shadowing and declaration order belong
to [name resolution](symbol_resolution.md#locals-and-block-scopes), not parser
checks. [Local annotations](local_type_resolution.md) now resolve to shared type
IDs; [body checking](body_checking.md) checks their expressions. Valid local/block
code reaches lifetime analysis and [local storage lowering](lowering.md) before
LLVM emission and native building.

Parameters use the same strict variable-before-type surface as typed locals,
e.g. `function choose($first int, $second int): int`. This initial syntax slice
requires a simple named type and positional arguments. Defaults, variadics,
reference parameters, named arguments and trailing commas remain unsupported.
Source order is preserved; evaluation and passing rules belong to later stages.
Duplicate parameter names and unknown names/types are semantic decisions.

Addition associates to the left; grouping and call arguments use the same
expression parser. Conditional and loop bodies require braces. See
[operations and control flow](operations_and_control_flow.md) for their semantics.
Operators other than addition, bare-name values, nested function definitions
and other statement/declaration kinds remain unsupported. Encountering them or
malformed syntax raises the first `Source_Error`, carrying file identity/path
and a zero-based byte span. At EOF
the error span has zero length. Multi-error recovery is deferred.

## Representation and work

[Parser](../../src/03_parse/main_parse.php) creates one
[File_Parser](../../src/03_parse/parse_file.php) per task. Its cursor and
flat `Syntax_Tree` are private work state; it never mutates tokens or project
symbols. Only integer node IDs survive appends. There is no recursive object
tree or per-node child container.

Every file returns a `File_Frontend` with one `file_root` node identified by
`Syntax_Tree.root_node_id`. Its children are the implicit entry block followed
by top-level definitions in source order. `defined_entities` and `entry_body_id`
index those same nodes without copying them. This root order describes
containment, not execution order. Root/index consistency is checked at joins.
Even empty or declaration-only files have a root and entry block. The entry block spans the
whole source but contains only executable statements. Definitions interleaved
with those statements stay in the definition list. A shared statement-list loop
builds entry and function bodies. A call used as a statement has an
`expression_statement` parent; a returned call is the child of `return_statement`.
Bare return has no child. Function children are name, parameter list,
return-type name and body. The parameter-list span includes its parentheses;
function and nested blocks include braces. Simple-statement spans include
semicolons, while `if`/`while` spans end at their final body's closing brace.
Each `parameter_declaration` in the parameter list has variable-name and
type-name children. Call children are the target name followed by argument
expressions in source order, using sibling links without an extra list node.
An explicit stack of open-call cursors parses nested arguments without recursive
PHP calls; completed nodes attach to their enclosing call once.
A local declaration has variable-name, type-name and initializer
children. An assignment has target-variable and value-expression children.
Local declarations remain in their containing body, outside `defined_entities`.

## Statement handler contract

Following the [extension intent](../code_organization.md#controlled-feature-extension-in-the-php-prototype),
`File_Parser` explicitly composes four private method groups under `handlers/`:

- [Statement_Parsing](../../src/03_parse/handlers/statements.php) owns
  statement lists and dispatch, blocks, returns, variable declarations/assignments and
  expression statements. Variable lookahead distinguishes those related forms;
  expression statements share one implementation regardless of their first token.
- [Control_Statement_Parsing](../../src/03_parse/handlers/control_statements.php)
  owns the existing `if`/`while` grammar and optional `else` body.
- [Declaration_Parsing](../../src/03_parse/handlers/declarations.php)
  owns declaration dispatch, context restrictions, functions and parameter lists.
- [Expression_Parsing](../../src/03_parse/handlers/expressions.php)
  owns operand dispatch, addition construction and iterative call/group traversal.

`statement()` selects a handler with the cursor at the first token. Each handler
consumes one complete statement, returns its finished node ID and leaves the
cursor at the following token. Simple handlers call `finish_simple_statement()`
to consume the required semicolon and finish the span. Blocks and control
statements consume their own delimiters. The statement-list loop attaches the
node returned by `statement()` to its containing block. Handlers attach their
own children, including nested blocks, through the owner's node/link helpers
and report syntax errors through `fail()`.

The owner retains file assembly, token/tree helpers and all worker state.
Traits declare no properties and are loaded explicitly by the prototype bootstrap
before the owner class. They are internal parts of this worker, not cross-stage
APIs. `Parser::run()` calls `File_Parser::parse()` over each fixed token snapshot;
private selection in `init()`, independent file outputs, joins and invalidation
retain the same rules.

Add future statement forms at `statement()` and implement them in the appropriate
handler group. Keep common token consumption, node creation and child ordering
on the same path. This organization does not add grammar or require matching
feature traits in later stages.

## Declaration handler contract

`statements()` asks `declaration($file_scope)` to recognize the current token.
Null means no supported declaration starts here: no tokens are consumed and no
nodes allocated, so the list proceeds through `statement()`. A recognized
declaration validates its context before consuming input; nested functions report
the existing error at the function keyword. Successful handlers consume a whole
declaration and return a finished node ID. Only the statement-list loop registers
that ID in `definitions`, preserving the order of declarations interleaved with
entry statements.

`function_definition()` assembles the name, parameter list, return-type name and
body in their existing allocation/child order. `parameter_list()` owns both
parentheses, comma handling and ordered parameter children. `parameter()` stops
before its list separator. Simple type annotations still use the shared name
parser; binding and type meaning remain later-stage decisions.

## Expression handler contract

`expression()` owns a local stack of `expression_cursor` records. It returns a
completed expression ID while leaving the caller's delimiter unconsumed.
`expression_operand()` dispatches at the first operand token. A positive ID
means a completed operand; null means an opening delimiter was consumed and a
new call/group frame was pushed. `call_operand()` builds the callee child and
either completes an empty call or opens a frame for its arguments.

The driver combines operands through `addition_expression()`, recognizes `+`
continuation and resumes enclosing expressions. `resume_expression()` attaches
an argument and either consumes a comma, resets the current call frame and
returns null, or consumes the closing parenthesis and returns the enclosing
operand. The driver pops only completed frames. Grouping creates no extra AST
node; named calls retain their exact source spans and ordered children.

This is the existing left-associative addition algorithm with named method
boundaries. It adds no expression recursion, operator precedence rules, retained
metadata or alternate evaluation path. Declaration/statement handlers return
complete constructs; operand handlers explicitly distinguish completion from
suspension. They share token/tree helpers, not an artificial common handler API.

## Feature extension map

| Change | Owned extension point |
|---|---|
| Statement grammar | `Statement_Parsing::statement()` and the selected handler |
| Declaration grammar/context | `Declaration_Parsing::declaration()` and the selected handler |
| Operand grammar | `Expression_Parsing::expression_operand()` and the selected handler |
| Infix grammar | `Expression_Parsing::expression()` continuation and named node construction |
| Call/group continuation | `Expression_Parsing::resume_expression()` and operand frame setup |
| Syntax vocabulary/payload | `syntax_kind` and related records in [structures.php](../../src/03_parse/data/structures.php) |
| Child roles and expression membership | Named accessors and shape checks in [Syntax_Access](../../src/03_parse/utilities/syntax_access.php) |
| Logical equality | Explicit kind/spelling rules in [Syntax_Comparer](../../src/03_parse/utilities/syntax_comparer.php) |
| Debug output | [File_Frontend::to_json()](../../src/03_parse/data/result.php) when new payload needs export |

A feature changes only the contracts it needs. Generic child traversal and flat
export already handle many shapes. New operator precedence requires deliberate
expression-algorithm work, not merely another node-construction branch.
Comparison validity, expression membership and exported spelling answer
different questions; their current checks remain in their respective owners.
Stores, joins and structural queries remain separate classes. New token kinds
belong to tokenization, and semantic behavior belongs to later stages; neither
is introduced by adding a parser handler.

## Consumers and retained results

The process-local `data/` folder groups `structures.php`, `store.php` and
`result.php`: syntax records, dataset owners and completed outputs. Their class
names, `parse` namespace and ownership contracts remain unchanged.

The process-local `utilities/` folder groups stateless supporting classes.
`Syntax_Access` and `Syntax_Comparer` keep their `parse` namespace and public
contracts for consumers; their implementation is separate from worker traits.

[Syntax_Access](../../src/03_parse/utilities/syntax_access.php) owns structural access to
function/parameter parts, the first parameter, call targets, the first argument,
statement expressions, local declaration parts and assignment parts. Temporary part records name existing node IDs; they are not
another syntax dataset. Symbol collection owns which parts contribute to a definition, and
resolvers/checkers own their meaning. Consumers can traverse ordinary child and
sibling links without duplicating these role layouts.

`Frontend_Set` owns lookup by stable source-file ID. Selection is full rebuild
or absent/stale frontend, where freshness means the exact same token object.
Changed files reparse fully; unchanged files share their complete prior result.
This proves frontend reuse, not semantic or backend incremental eligibility.

[Frontend_Join](../../src/03_parse/join.php) is private coordinator
preparation over fixed sources, tokens, previous frontends and the exact selected
task batch. `merge` accepts an index/count segment in any result order. It validates
task/source/token identity and file-root/index consistency for the whole segment
before adopting its references. Unselected results and duplicates within or across
segments fail without changing earlier preparation. Segment work covers only
the supplied results and their definition indexes.

`finish` assembles results in current source-file order, excludes removed files,
and requires exactly one result per selected task, including forced reparses of
unchanged tokens. It rejects missing/stale frontends before returning a `Frontend_Set`. Whole
project membership and completeness are handled at this boundary, rather than
on each segment. Unchanged frontends remain shared. The serial runner uses the
same candidate with one batch. Failure preserves previous bytes, tokens and ASTs.

Current semantic reuse is conservative within an edited file: replacing its AST
refreshes symbols, checked bodies and lowering for unchanged functions in that
file too. Logical comparison still catalogs only actual changes. Finer reuse
across AST replacement is deferred; it does not require per-node change flags.

`--debug=json` adds `inputs.frontends`: `root_node_id`, definition/body indexes and flat nodes containing
IDs, kinds, spans, child/sibling links and derived text for name, variable and
literal leaves. Logical comparison uses their spelling and ordered children,
including block nesting; positions and comments do not establish a change.
No exporter runs without the debug flag.

The [parser tests](../../tests/03_parse/parsing.php) check source-derived trees,
spans, nested blocks, role accessors, large statement lists and syntax errors. The
[variable parsing tests](../../tests/03_parse/variable_parsing.php) cover local
syntax through file reads, workers, comparison, reuse and failure/repair, and
verify the current semantic rejection boundary. The
[update tests](../../tests/03_parse/parse_updates.php) check independent workers,
segmented/out-of-order joins, completeness, reuse, failure/repair, fresh-build
equivalence, removals and release of obsolete ASTs.

[Parameter/argument proofs](../../tests/03_parse/parameter_parsing.php) check
ordered roles and exact spans, nested/wide lists, unique node containment,
exports, definition versus body comparison, fixed workers and segmented joins,
warm/full frontend reuse, malformed input, semantic rejection and repair.
Name resolution binds names within argument expressions, and the type stage
resolves declared parameters. Body checking models incoming bindings and
checks argument count/types and left-to-right evaluation. Scalar lifetime
analysis handles incoming parameters and call consumption; backend preparation,
lowering and LLVM emission complete the scalar native path. See the completed
[parameter foundation](../planning/compiler_foundations.md) and its remaining restrictions.
