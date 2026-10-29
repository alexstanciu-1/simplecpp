# Compiler flavor and shared syntax contract

Doc Status: supporting
Date: 2026-10-29
Scope: approved rewrite direction and the intended first parser subset. The active
PHP rebuild currently implements source loading and tokenization only. This is not
an assertion of complete C++, PHP or JavaScript compatibility.

## Language and policy boundary

Simple C++ is one language with C++, PHP and JS syntax flavors. There is one PHP
flavor, not separate PHP/strict-PHP++/PHS modes. Historical strict-PHS donor guidance
does not establish a second mode in this rewrite. Project settings will own strictness
and configured defaults, enforced by common compiler analysis. Parsers retain syntax
and intent regardless of whether a later profile permits it.

Mixed flavors in a project are allowed, though consistent project style is encouraged.
Flavor is per file, never global. Initially the semantic profile is project-wide.
Project-manifest loading and strictness enforcement are not implemented in this
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
DeclareOrAssign intent; analysis decides introduction versus update. No parser builds
or consults a symbol table. All locals in this first subset are mutable; immutable
binding syntax is deferred rather than silently mapped to mutable let/auto.

Planned first statements: initialized local declarations, simple-name assignments,
expression statements and brace blocks. Semicolons are mandatory; no JS automatic
semicolon insertion. Expressions: decimal integer tokens, names, parentheses, unary
plus/minus, multiplication/division/remainder, addition/subtraction and shifts.
Precedence descends in that order; binary operators associate left. Assignments are
statements, not expressions in this slice. There are no functions, calls, member
access, comparisons, strings/floats in expressions, const declarations, PHP tags/HTML
modes, C++ preprocessing, JS prototypes or JS template literals yet.

Type syntax preserves a name and nested type arguments, including vector<vector<int>>.
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
lists; `compiler/frontend.php` coordinates loading and scanning. PHP token kinds are
integer-backed enum values in 1..127 with separate readable labels; keyword spelling
is retained in source spans. PHP records do not claim a packed native layout. The CLI currently
inspects tokens only. The parser behavior above remains planned until its PHP migration slice.
See [retained parser ideas](../compiler/docs/parser_migration_notes.md).

## Future parser ownership and proof boundary

`03_parse/flavors/` will own flavor-specific declaration and assignment grammar.
Shared parsing machinery will own cursors, types, blocks and common expressions;
one source-level AST will retain source intent without resolved types or policy.
Common analysis will resolve names/types, apply the project profile, establish
execution order and settle facts for the common backend.

The retired parser's design and test cases are summarized in the migration notes.
They are future validation requirements, not executable PHP parser coverage today.
