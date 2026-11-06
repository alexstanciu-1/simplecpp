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
DeclareOrAssign spelling intent; first writes become inferred local declarations
using prior body-local collection, while subsequent writes remain assignments. Parsing does not resolve names or directly mutate a global symbol index.
Local collection records occurrences; function discovery notifies the coordinator. All locals in this first subset are mutable; immutable
binding syntax is deferred rather than silently mapped to mutable let/auto.

Implemented now: initialized local declarations and simple-name assignments;
type annotations are single names; values are decimal integers/floats, `true`/`false`/`null`, quoted strings, or flavor-correct
name references, calls and the scalar comparisons documented below. A sign immediately before a floating token is part of the bounded
literal grammar; this does not introduce general unary expressions. Generated metadata registers the supported core scalar literals/types.
The [scalar contract](../../specs/compiler_scalars_v0_2.md) defines checked conversion
at written type destinations and `(Type) operand` explicit scalar casts in all three flavors. Explicit and inferred declarations retain syntax, and PHP subsequent bare
writes retain DeclareOrAssign intent. Empty input is valid. Statements require
semicolons; a failed parse publishes no partial file. Unknown types, unknown names,
duplicate declarations and large integer spellings remain analysis concerns.

Planned extensions: expression statements and brace blocks. Semicolons are mandatory; no JS automatic
semicolon insertion. Expressions: decimal integer tokens, names, parentheses, unary
plus/minus, multiplication/division/remainder, addition/subtraction and shifts.
Precedence descends in that order; binary operators associate left. Assignments are
statements, not expressions in this slice. The executable subset additionally admits `return` with an integer/name value.
The [typed-function slice](../../specs/compiler_entry_v0_2.md#typed-value-parameters-and-returns)
adds explicit value parameters/returns, positional and nested calls, and calls in value
positions. PHP uses `function f(int $value): int`, C++ `int f(int value)`, and JS
`function f(value: int): int`. Signature types settle before body preparation; each
body indexes its signature-owned parameters alongside its local declarations.
The bounded [echo slice](../compiler/docs/output_slice.md) accepts one or more comma-separated int64 or string operands
in all flavors. Operands are evaluated and printed once in source order; empty lists and trailing commas reject. Typed string locals admit literals and same-type copies; globals and text conversions remain deferred.
Same-type [scalar comparisons](../compiler/docs/comparisons.md) and parentheses are implemented in all flavors.
Int/int64 binary `+ - * / %` are implemented through the same metadata-driven path.
Multiplicative operators bind above additive operators, then relational operators, then equality;
all levels associate left. Integer/float unary signs, same-type float arithmetic and
mixed int64/float64 `+ - * /` use generated type handlers. Mixed compounds remain deferred.
Logical `!`, `&&` and `||` reuse generated condition truthiness in all flavors.
Prefix `!` binds above arithmetic; `&&` binds below equality and above `||`.
Each operand independently admits bool, int64, float or string; the result is bool.
See [logical expressions](../compiler/docs/logical.md) for short-circuit and incremental boundaries.
There is no member access, general string expressions, PHP tags/HTML
modes, C++ preprocessing, JS prototypes or JS template literals yet.

Planned type syntax will preserve nested type arguments, including vector<vector<int>>.
This is syntax admission, not container construction, type lookup or generic lowering.
Every closing angle consumes one token. Shift expressions consume two byte-adjacent
angles and produce one AST operation; whitespace/comments between angles prevent
combination. Tokens are never rewritten or marked ignored. <<= and >>= retain their
existing lexical tokens but compound assignment parsing remains deferred.

## Lexical boundary

Source bytes, spans, token publication and diagnostics are shared. Source must be
valid UTF-8 in all three flavors, including comments and quoted literals. The PHP
bootstrap uses `preg_match_all` with UTF-8 validation; offsets, lengths and diagnostic
columns remain byte-based. Invalid encoding diagnoses the start of its first invalid
sequence before lexical classification. No BOM stripping, Unicode identifier rules,
Unicode whitespace expansion or normalization is introduced. ASCII whitespace remains
space, tab, CR and LF. Valid NUL bytes inside quoted strings remain supported.
The [native tokenizer debt](planning/native_tokenizer_debt.md) records the intended
native implementation review.
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
complete-file publication. Each completed statement is recorded by `Body_Collection` for source-order indexes;
its declarations use the same `Scope_Collection` publisher as file and compilation scopes. `Parser::parse()` publishes syntax and collection
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
secondary sources; they admit comments/whitespace and typed function definitions. Each
source produces a translation unit and one adapter supplies `main`. See the
[multifile guide](../compiler/docs/multifile_slice.md) for invocation, verification
and deferred work. The command runs sequentially; see the
[execution guide](../compiler/docs/execution.md). PRJ discovery,
incremental reuse remains pending; typed calls now resolve across files.

## File AST ownership

The parsed result owns one `ast_file_node` containing an ordered syntax inventory and a keyed first-reference map.
Currently only `func:name` nodes exist. Top-level executable statements become
one implicit file-private `func:main`, placed first; declaration-only files have
no implicit entry. The original statement spans remain unchanged. Explicit `main`
is reserved and not yet supported as source syntax.

Body-local collections and prepared facts attach to the same function-body model
for entry and ordinary functions. Public symbol publication references declaration
nodes directly. Every body is parsed. Public function duplicate chains belong to the compilation
scope; local variable chains belong to their body scope. File indexes only reference
nodes, and file traversal uses the syntax inventory independently of live chains. Complete file roots expose a
simple duplicate diagnostic list. Whole-program preparation reconciles cross-file
candidates and reports every additional declaration against the original location
before settling facts or lowering. `parse` and `collect` CLI commands retain full
inspection JSON with a diagnostic list, print errors to stderr and exit 1. Syntax
errors still stop parsing; general syntax recovery is not implemented. This representation adds no
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
these facts. Builtins resolve to canonical generated definitions; int/int64 share one identity.
Inferred locals retain no written type and take their prepared type from the
initializer, rejecting null without an explicit type. Void remains its own definition.

Function calls retain stable symbol entries resolving to current function declarations.
Function existence/signature dependencies invalidate observing body caches; see the
[stable-symbol implementation guide](../compiler/docs/stable_symbols.md). Local indexes
and variable bindings reference local declaration nodes directly. The declaration
phase completes before any body uses foreign signature facts. Reset/reanalysis is
sequential; old handles are not independent snapshots of declaration-owned facts.

## First namespace slice

Implemented namespace/path syntax and exact function lookup for all flavors follow
[the normative namespace contract](../../specs/namespaces_v0_2.md). See
[the compiler slice](../compiler/docs/namespaces.md) for structure, limitations,
incremental dependency handling and proofs. AST namespace separators are always
backslashes. JS uses TypeScript-style namespace braces/dotted paths and a Simple
C++ leading-backslash root qualifier. Export/API visibility remains deferred;
namespace declarations are currently visible without an export keyword.

Body-local `use`/`use function` imports now resolve existing functions, including
symbol renaming and homogeneous groups. Imports have absolute targets, apply
throughout only their source namespace body and do not publish new identities.
Preparation validates unused imports on full and incremental passes. All flavors
use `use` with their own path separators; constant imports now participate; type imports remain deferred.
Ordinary top-level variables stay local to implicit main; configurable same-file
visibility and variable shadowing are [debt](planning/variable_visibility_debt.md).

## Explicit global variables

The [integer-global slice](../compiler/docs/globals.md) adds file/namespace-level
`global` declarations to the same AST inventory and symbol publication path as functions.
They admit inferred or explicit int64 storage with signed decimal literal initialization
before entry execution. Namespace-qualified reads work across files and functions;
visible locals take precedence over unqualified globals. Ordinary top-level locals
remain private to implicit main. Writes, other types and variable imports are deferred.
Reads retain stable symbol entries with unknown constant values; initializer-only
changes need not invalidate reader bodies, while removal/type changes do.

Mutable reference parameters use `T &$name` (PHP), `T &name` (C++), and `&name: T`
(JS Simple C++ extension). Calls use ordinary variable expressions. Exact canonical
storage identity, mutation, forwarding and argument ordering follow the shared
[function contract](../../specs/compiler_entry_v0_2.md#explicit-mutable-reference-parameters).
Read-only references and reference defaults are deferred.

Braced `if`/`elseif`/`else if`/`else` now share one AST and preparation path across
flavors. `elseif` is reserved in all three. Each branch body has a lexical scope;
standalone blocks and unbraced arms remain deferred. Generated type handlers admit
bool/int64/float64/string conditions and reject null/byte/void. See the
[conditional contract](../../specs/conditional_statements_v0_2.md).

All flavors now admit braced `while (condition) { ... }` and
`do { ... } while (condition);`, including nesting. Both reuse generated condition
admission and lexical body scopes; the trailing do-while semicolon is mandatory.
Break/continue and unbraced loops remain deferred. See the
[loop contract](../../specs/loop_statements_v0_2.md).

## Bounded for header

All flavors support [one initializer, condition and assignment update](../compiler/docs/for.md)
with a braced body. Header-local declarations do not escape the loop; continue
runs the update before the next test. Omitted/comma clauses and increments remain
deferred. Header parsing reuses existing declaration/write and expression grammar.

## Typed literal constants

The [constant slice](../compiler/docs/constants.md) adds typed file/namespace constants,
forward/cross-file reads and constant imports. cpp/php use `const int LIMIT = 8;`;
js uses `const LIMIT: int = 8;`. Only literal initializers are supported. Type behavior
and conversions reuse generated definitions; initializer expressions and class/local
constants remain deferred.


## Compound assignment statements

All flavors accept existing-local int64 `+=`, `-=`, `*=`, `/=`, `%=` through one
assignment parser and shared underlying-operator map. PHP compound writes never
declare locals. Types and write-back admission belong to generated operator rules
and preparation. The [scalar contract](../../specs/compiler_scalars_v0_2.md#int64-compound-assignment-statements--2026-11-05)
defines ordering and limits. The same assignments are supported in `for` initializer
and update clauses; compound expression values remain deferred.


## Discarded-value increment/decrement

Prefix/postfix `++`/`--` on simple local names is supported at standalone statement
and for initializer/update boundaries, with generated int64-only admission. PHP
names retain `$`. These tokens do not enter value-expression parsing; consuming an
update result, chained updates and complex targets remain deferred. One common
parser handles every flavor and both header positions.

### Int64 unary signs

All flavors parse prefix `+`/`-` on expressions into `ast_unary_expression` with
one operand and `unary_operator`. Signs nest right-to-left above binary operators;
longest-match `++`/`--` retain update syntax. Existing signed floating literals
and cast lookahead are preserved. Generated type handlers admit only int/int64
for general unary signs. See [the scalar contract](../../specs/compiler_scalars_v0_2.md)
and [implementation/proofs](../compiler/docs/unary.md).
