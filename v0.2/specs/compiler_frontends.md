# Compiler flavor and shared syntax contract

Doc Status: supporting
Date: 2026-10-29
Scope: approved rewrite direction and the intended first parser subset. The active
PHP rebuild implements loading, tokenization and the bounded declaration/assignment
parser and the bounded [integer entry slice](../../specs/compiler_entry_v0_2.md). This is not
an assertion of complete C++, PHP or JavaScript compatibility.

## Language and policy boundary

Simple C++ is one language with C++, PHP and JS syntax flavors. There is one PHP
flavor, not separate PHP/strict-PHP++/PHS modes. Historical strict-PHS donor guidance
does not establish a second mode in this rewrite. PRJ configurations will select composed profiles controlling policy and configured
defaults, enforced by common compiler analysis. These are not separate PHP language
modes. The profile composition/configuration schema remains a separate future slice. Parsers retain syntax
and intent regardless of whether a later profile permits it.

Mixed flavors in a project are allowed, though consistent project style is encouraged.
Flavor is per file, never global. Selected composed profiles are project-wide.
PRJ loading, profile composition and profile enforcement are not implemented in this
parser slice. The CLI requires explicit cpp/php/js; it does not guess from extension.
The driver accepts a flavor for each source operation, and token generations retain
that selection. Parsing dispatches from that retained flavor, not a second argument.

Integer literal 3 is intended to acquire the project-configured default integer type
through analysis; the parser neither selects int64 nor converts the spelling. Unknown
names/types, duplicate declarations, overflow, conversions and inference restrictions
remain semantic responsibilities. Successful parsing does not mean a valid program.

## First common subset

| Intent | C++ | PHP | JS |
| --- | --- | --- | --- |
| Explicit local | `int count = 3;` | `$count int = 3;` | `let count: int = 3;` |
| Inferred local / unresolved write | `auto count = 3;` | `$count = 3;` | `let count = 3;` |
| Name reference | `count` | `$count` | `count` |
| Existing-local assignment | `count = 4;` | `$count = 4;` | `count = 4;` |

The shared AST distinguishes an explicit/inferred Local from an Assignment.
C++/JS assignments carry ExistingBinding intent. PHP bare assignments carry
DeclareOrAssign intent; analysis decides introduction versus update. Parsing does not resolve names or directly mutate a global symbol index.
Local collection records occurrences; function discovery notifies the coordinator. All locals in this first subset are mutable; immutable
binding syntax is deferred rather than silently mapped to mutable let/auto.

Implemented now: initialized local declarations and simple-name assignments;
type annotations are single names; values are decimal integers or flavor-correct
name references. Explicit and inferred declarations retain syntax, and PHP bare
writes retain DeclareOrAssign intent. Empty input is valid. Statements require
semicolons; a failed parse publishes no partial file. Unknown types, unknown names,
duplicate declarations and large integer spellings remain analysis concerns.

Planned extensions: expression statements and brace blocks. Semicolons are mandatory; no JS automatic
semicolon insertion. Expressions: decimal integer tokens, names, parentheses, unary
plus/minus, multiplication/division/remainder, addition/subtraction and shifts.
Precedence descends in that order; binary operators associate left. Assignments are
statements, not expressions in this slice. The executable subset additionally admits `return` with an integer/name value.
The [void-function slice](../../specs/compiler_entry_v0_2.md#no-argument-void-function-extension)
adds no-parameter definitions (`void f() {}` in C++, `function f(): void {}` in PHP/JS),
no-argument statement calls, and bare returns inside void functions. Function bodies
have private local scopes; named signatures settle before body preparation.
The bounded [echo slice](../compiler/docs/output_slice.md) accepts an integer or quoted literal
as its single operand in all flavors. String literals are currently output-only.
There is no member access, comparisons, general string/float expressions, const declarations, PHP tags/HTML
modes, C++ preprocessing, JS prototypes or JS template literals yet.

Planned type syntax will preserve nested type arguments, including vector<vector<int>>.
This is syntax admission, not container construction, type lookup or generic lowering.
Every closing angle consumes one token. Shift expressions consume two byte-adjacent
angles and produce one AST operation; whitespace/comments between angles prevent
combination. Tokens are never rewritten or marked ignored. <<= and >>= retain their
existing lexical tokens but compound assignment parsing remains deferred.

## Lexical boundary

Source bytes, spans, token publication and diagnostics are shared.
The current scanner shares decimal/quote/trivia/punctuation machinery with explicit
flavor rules: PHP admits $-prefixed variable tokens and # comments (except #[);
JS admits $ as an identifier character; C++ rejects $ names in this bounded subset.
C++/JS reject # constructs for now. Identifier spelling is ASCII. Keyword
classification is flavor-specific; PHP-only words such as echo remain ordinary
identifiers in C++/JS. auto and let are recognized only in their respective flavors. All keywords share
one integer token kind; consumers read the exact spelling from the source span. The initial keyword
sets are intentionally bounded, not full host-language reservation tables.

Lexing whole quote/numeric spellings does not admit them in the planned expression
parser or establish escape/conversion semantics. Additional literal families require
review of their flavor rules before parser support. Strings, interpolation and
literal decoding must preserve source intent without importing host-language behavior.

## PHP rebuild ownership

The active PHP entry point is `compiler/main.php`. `compiler/compiler/flavor.php`
owns per-source flavor and keyword classification; `01_prepare_inputs/` owns
immutable source snapshots; `02_tokenize/` owns shared scanning and immutable token
lists; `compiler/frontend.php` coordinates loading, scanning and parsing. PHP token kinds are
integer-backed enum values in 1..127 with separate readable labels; keyword spelling
is retained in source spans. PHP records do not claim a packed native layout. The CLI inspects tokens or the implemented syntax subset using `tokens` or `parse`.
The arithmetic/block/generic extensions described above remain planned.
See [retained parser ideas](../compiler/docs/parser_migration_notes.md).

## Parser ownership and proof boundary

`03_parse/flavors/` owns flavor-specific declaration and assignment grammar.
Shared parsing machinery owns cursors, named type syntax, integer/name values and
complete-file publication. Each completed statement is recorded by a source-local
`Symbol_Collector` during parsing; `Parser::parse()` publishes syntax and collection
together in one `ast_file_node`. Collection preserves occurrences without resolving
names or granting forward visibility. There is no later collection traversal.
Typed AST records retain source intent without resolved
types or policy; they do not own compiler processing or host serialization.
The donor supplies typed node families and syntax/analysis separation, without
importing its incremental scopes, synchronized global publication or converter-specific storage.
The bounded entry analysis now resolves integer locals and return values in source
order and settles facts for C++ emission. Richer types, profile enforcement and
execution-order rules remain future work; see the [entry guide](../compiler/docs/entry_slice.md).

The retired parser's design and test cases are summarized in the migration notes.
The PHP tests now cover declaration equivalence, write intent, exact spans, unresolved
names/types, malformed and wrong-flavor input, EOF diagnostics, 10,000 flat statements
and CLI publication. Arithmetic, generic types and nesting proofs remain future work.

## Multifile entry slice

The `program` command accepts an explicit mixed-flavor inventory and a selected
root-relative entry. Shared program preparation rejects executable statements in
secondary sources; they admit comments/whitespace and void function definitions. Each
source produces a translation unit and one adapter supplies `main`. See the
[multifile guide](../compiler/docs/multifile_slice.md) for invocation, verification
and deferred work. The command runs sequentially; see the
[execution guide](../compiler/docs/execution.md). PRJ discovery,
incremental reuse remains pending; no-argument void calls now resolve across files.

## File AST ownership

The parsed result owns one `ast_file_node` containing a keyed declaration map.
Currently only `func:name` nodes exist. Top-level executable statements become
one implicit file-private `func:main`, placed first; declaration-only files have
no implicit entry. The original statement spans remain unchanged. Explicit `main`
is reserved and not yet supported as source syntax.

Body-local collections and prepared facts attach to the same function-body model
for entry and ordinary functions. Public symbol publication references declaration
nodes directly. Duplicate function keys diagnose before insertion; cross-file
duplicates remain a whole-program analysis check. This representation adds no
constant, class, interface or trait syntax. Existing CLI views are derived from it.

## AST token positions

All AST syntax records inherit `ast_node` with token-based `[start, end)` and a
single-assignment syntax parent. The file root retains the token generation and
source information. Tokens retain byte offsets and lengths; source text and
line/column diagnostics are recovered through that root. Nodes retain names and
other semantic input fields directly. They do not retain byte spans or source
references. Empty ranges anchor to the next token, or source EOF; synthetic entry
names/type spellings are fields without authored tokens. Function nodes acquire their
full end when the body completes; header discovery remains earlier and reads only
initialized header information. These positions do not establish incremental reuse.

## Declaration-owned names and type facts

AST classes consistently use the `ast_` prefix. Names and written type names are
fields on their owners, not child syntax nodes. Local declarations retain optional
`type_name` and resolved `type_def`; functions retain optional `return_type_name`
and resolved `return_type_def`. Resolution is checked before consumers can read
these facts. The current implementation resolves builtin int/int64 to one canonical
int64 definition and void to its own definition; it does not add inference or types.

Function calls retain direct links to settled function declarations. Local indexes
and variable bindings reference local declaration nodes directly. The declaration
phase completes before any body uses foreign signature facts. Reset/reanalysis is
sequential; old handles are not independent snapshots of declaration-owned facts.
