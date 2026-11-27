# Compiler flavor and shared syntax contract

Doc Status: supporting
Date: 2026-10-29
Scope: approved rewrite direction and the intended first parser subset. The active
PHP rebuild implements loading, tokenization and the bounded declaration/assignment
parser and the bounded [integer entry slice](compiler_entry_v0_2.md).
The Python subsection records the v0.2 typed surface.

## Starting a PHP-flavor source

A PHP-flavor file starts directly with source code. For migration, the compiler accepts `<?php` at byte zero followed by whitespace or end of file. It warns on stderr once per compiler execution across all source files. A selected PHP entry that reaches the end exits with status zero. Explicit integer returns still select the exit status.

Simple C++ type checking is always enabled. The compiler diagnoses `declare(strict_types=1);` and directs authors to remove it. The `require`, `require_once`, `include` and `include_once` forms direct authors to the source inventory in `module.json`. In typed declarations the assignment can immediately follow the closing generic delimiter, as in `$v vector<int>=[];`.

## Language and policy boundary

Simple C++ is one language with C++, PHP, JS and Python syntax flavors. There is one PHP
flavor, not separate PHP/strict-PHP++/PHS modes. Historical strict-PHS donor guidance
does not establish a second mode in this rewrite. PRJ configurations will select composed profiles controlling policy and configured
defaults, enforced by common compiler analysis. These are not separate PHP language
modes. The profile composition/configuration schema remains a separate future slice. Parsers retain syntax
and intent regardless of whether a later profile permits it.

Mixed flavors in a project are allowed, though consistent project style is encouraged.
Flavor is per file, never global. Selected composed profiles are project-wide.
PRJ loading, profile composition and profile enforcement are not implemented in this
parser slice. File commands and explicit program inventories accept cpp, php, js
and py. Module discovery recognizes .py files alongside the other flavors. Token
generations retain the selected flavor, which also drives parsing.

Integer literal 3 is intended to acquire the project-configured default integer type
through analysis; the parser neither selects int64 nor converts the spelling. Unknown
names/types, duplicate declarations, overflow, conversions and inference restrictions
remain semantic responsibilities. Successful parsing does not mean a valid program.


## Contextual keyword blocks

The shared brace-expression parser recognizes registered introducers by the
identifier followed by an opening brace, optionally preceded by parenthesized group settings. Each construct parses its own body,
retains source positions, and produces its own expression. The first such
construct, `parallel`, owns an ordered sequence of named or inline worker launches. Inline launches own
their ordered read/write grants and scalar value expressions, plus a capture-free block with context-typed parameters. An initial `each:` clause supplies a caller-side iteration index and a read-only worker parameter. Group `workers:` limits participation across all expanded entries. A `workers:` clause immediately following `each:` limits the concurrent chunks of that entry.
Preparation sends it directly to the existing joined-batch provisioning path.
A future construct can use a different body grammar and preparation rule.
[Parallel block contract](compiler_entry_v0_2.md#parallel-block-expressions)

## Python syntax flavor in v0.2

The Python surface is a typed Simple C++ frontend selected per file with py.
One tokenizer adds Python layout tokens with original byte positions, and the
shared parser builds the same AST used by cpp, php and js. Preparation uses
the common type, ownership and evaluation rules. The shared backend emits C++.

| Area | Supported surface |
| --- | --- |
| Layout and functions | Indented multiline suites, comments, parenthesized continuations, typed def parameters and returns, typed initialized locals and subsequent assignment. |
| Expressions and control | Core scalar literals and arithmetic, comparisons, bool-only not/and/or, conditional expressions, if/elif/else, while with optional else, pass, unlabelled break and continue, and return. |
| Collections | Typed vector and hash local literals, checked indexed reads and single indexed writes. A for name in source loop binds vector elements or hash keys. A new loop name stays in the loop and an existing local receives the selected entries. Typed hashes also accept for key, value in mapping.items(). The key is loop-local and the value uses the shared foreach destination rule. Typed hash traversal follows key insertion order. |
| Records and ownership | Typed class fields, self, dot access, construction with __init__, and one base class with an initial super().__init__ call. Methods can call super().method(...) as a statement or expression. Compile-time @virtual and @override select the shared method contract, with an explicit override marker required. |
| Async free functions | Typed `async def`, direct `await` calls and `await async_sleep_ms(...)` use the [shared async contract](async_await.md). |
| Typed failures | Raise, bare rethrow in a handler, typed except Type as name clauses and finally under the shared cleanup rules. |

The shared collection contract rejects negative vector indexes and duplicate
literal keys. Typed hash writes insert or replace. Boolean operators require
bool operands. Local scopes follow Simple C++ blocks, and every new ordinary
local needs a type and initializer.

Python `key in mapping` and `key not in mapping` test key presence at comparison precedence. The mapping must publish a typed key-presence binding, and the key must match its declared key type. Both operands evaluate once, key first. A failed key expression skips the mapping expression. The result is independent of the stored value and traversal order.

Python `in` on a typed vector scans elements in order and stops at the first match. It compares supported scalars by value and shared handles by allocation identity. Integer/float membership compares the exact numeric values, including integers above 2⁵³. A boolean needle compares numerically to integer elements. The needle evaluates once before the vector expression, including for an empty vector.

For `mixed` and `dynamic` receivers, Python list literals retain their sequence identity through copies. Dictionary literals and existing containers received from cpp, php, or js test keys, even when all keys are consecutive integers starting at zero. Python dictionary literals preserve integer and string keys separately within the boxed storage's supported key range. A boxed string supports substring membership. In each case `not in` negates the result.

In a known `mixed` or `dynamic` destination, `()`, `(value,)` and `(first, second)` construct boxed tuples. Parentheses around one expression preserve grouping. Construction evaluates slots once in source order. Both families support `count(t)`, positive and negative integer indexing, ordered value iteration, element membership and same-family structural equality. Tuples and lists compare unequal. Subscript assignment to a tuple fails at runtime. An explicit shared child retains its family semantics. Python boxed iteration selects sequence values or mapping keys from the receiver kind. Tuple keys, unpacking and slicing are deferred.

Python `is None` and `is not None` evaluate the left operand once and return bool. The operand can be a nullable carrier that admits absence, a shared record handle, or a null value. These tests preserve the declared type in both branches. Result errors do not represent absence.

Python `a is b` and `a is not b` also accept two shared record handles, including optional handles and interface views. The operands evaluate once in source order. Identity compares the allocation retained by each operand. Two empty handles compare identical, including when their declared payload types differ. Scalar and collection values do not support this two-handle form.

Python integer scalars support unary `~`, binary `&`, `^` and `|`, and corresponding compound assignments on existing locals and parameters. Bitwise operations bind more tightly than comparisons. Narrow destinations use the shared checked write-back rules.

Python binary / yields float64 for supported int64 and float64 operands.
Integer // rounds down and integer % uses the divisor's sign. These spellings
select separate operations from the cpp/php/js integer division and remainder.
See the [arithmetic contract](planning/python_flavor.md#python-division-floor-division-and-modulo).

Python comparison chains support `== != < <= > >=` for the operand pairs admitted by the shared comparison rules, `in` and `not in` for supported collection receivers, and `is` and `is not` for the handle and literal-None tests above. The unbound `undefined` missing-key marker is available only in a standalone indexed test. Every adjacent pair is type-checked. Operands execute once, from left to right. A false comparison skips the remaining operands at runtime. Parentheses can make a comparison result an operand of a separate comparison.

Deferred Python spellings and semantics include floating floor division and
modulo, operand-valued boolean operators, type inference
on first assignment, general iteration and range, standalone items views and general destructuring,
comprehensions, generator syntax, import and print, general decorators, dynamic
attributes, Python-authored interfaces, and general super proxies. Package interoperability and CPython execution are outside the
flavor scope. Native tokenizer layout parity is tracked as separate cutover work.
See the [runnable example](../compiler/examples/python/main.py) and the
[acceptance record](planning/python_flavor.md#v02-closeout).

## Named callback syntax

The cpp, php and js flavors use `callback<Result, Input1, Input2>` on a parameter and
`name(...)` to refer to a source function. Static references use each flavor's
type-qualified syntax. See the [named callback contract](compiler_entry_v0_2.md#named-callbacks).
These flavors also admit [capture-free `fn` expressions](compiler_entry_v0_2.md#lambdas)
with their ordinary parameter syntax.

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
The [scalar contract](compiler_scalars_v0_2.md) defines checked conversion
at written type destinations and `(Type) operand` explicit scalar casts in all three flavors. Explicit and inferred declarations retain syntax, and PHP subsequent bare
writes retain DeclareOrAssign intent. Empty input is valid. Statements require
semicolons; a failed parse publishes no partial file. Unknown types, unknown names,
duplicate declarations and large integer spellings remain analysis concerns.

Planned extensions: expression statements and brace blocks. Semicolons are mandatory; no JS automatic
semicolon insertion. Expressions: decimal integer tokens, names, parentheses, unary
plus/minus, multiplication/division/remainder, addition/subtraction and shifts.
Precedence descends in that order; binary operators associate left. Assignments are
statements, not expressions in this slice. The executable subset additionally admits `return` with an integer/name value.
The [typed-function slice](compiler_entry_v0_2.md#typed-value-parameters-and-returns)
adds explicit value parameters/returns, positional and nested calls, and calls in value
positions. PHP uses `function f(int $value): int`, C++ `int f(int value)`, and JS
`function f(value: int): int`. Signature types settle before body preparation; each
body indexes its signature-owned parameters alongside its local declarations.
The bounded [echo slice](../compiler/docs/output_slice.md) accepts one or more comma-separated int64 or string operands
in all flavors. Operands are evaluated and printed once in source order; empty lists and trailing commas reject. Typed string locals admit literals and same-type copies; globals and text conversions remain deferred.
Same-type [scalar comparisons](../compiler/docs/comparisons.md), mixed int64/float64
ordering and [equality](../compiler/docs/mixed_equality.md), exact [string equality](../compiler/docs/string_equality.md), and parentheses are implemented in all flavors.
Int/int64 binary `+ - * / %` are implemented through the same metadata-driven path.
Multiplicative operators bind above additive operators, then relational operators, then equality;
all levels associate left. Integer/float unary signs, same-type float arithmetic and
mixed int64/float64 `+ - * /` use generated type handlers. Float destinations also
admit int64 RHS for `+= -= *= /=`; the reverse direction remains rejected.
Logical `!`, `&&` and `||` reuse generated condition truthiness in all flavors.
Prefix `!` binds above arithmetic; `&&` binds below equality and above `||`.
Each operand independently admits bool, int64, float or string; the result is bool.
See [logical expressions](../compiler/docs/logical.md) for short-circuit and incremental boundaries.

The current compiler supports `nullable<T> ?? T` for published runtime nullable
carriers, with an exact canonical payload fallback. It evaluates the left once
and evaluates the fallback only when absent. Both branches are prepared; zero,
false and empty string are present values. `??` associates right and binds below
`||` but above `xor`. Broader wrapper pairs in the conditional matrix and application
carriers remain deferred here. Statement-form `??=` supports declared nullable
locals using ordinary assignment conversion and lazy fallback. Parameters, fields,
indexed targets and for headers remain deferred. See [coalescing](../compiler/docs/coalesce.md).

There is no member access, general string expressions, PHP inline HTML modes, C++ preprocessing, or JS prototypes. Named interpolation uses the bounded [three-flavor grammar](string_interpolation.md#active-v02-flavors).

Type-reference syntax preserves nested arguments, including vector<vector<int>>;
that syntax alone does not make nested container specializations available.
The bounded application-vector slice now resolves `vector<Record>` for local storage and by-value function/method signatures:
struct elements use value copies and class elements use their ordinary shared ownership.
Empty construction, append, checked indexing/replacement and copying are implemented;
application-vector writable references and traversal remain deferred. See
[application vectors](../compiler/docs/application_vectors.md). Application-defined hash values now follow the same local/by-value path with strict published int/string keys; see [application hashes](../compiler/docs/application_hashes.md). Both application-defined collection families support value/key foreach, including inside yield producers, without default-constructing their elements.
Application vector/hash instance fields initialize empty and reuse their copy and mutation paths; recursive concrete field dependencies remain deferred.
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

## Typed exception statements

Core metadata publishes `Exception::make(string)` and `get_message()`. Source
classes can derive from the canonical exception root. Shared preparation checks
the resolved type of each throw operand and handler binding. A typed catch
binds one local in a sibling lexical scope. PHP uses
`catch (Type $value)`, C++ uses `catch (Type value)`, and JS uses
`catch (value: Type)`. `throw value;` propagates the same shared allocation.
Arms are tried in source order. A previous arm covering the entire type of a later
arm is a preparation error. Bare `throw;` within a handler propagates its original
object, including after the binding changes. Union catch types remain deferred.
Runtime guard failures unwind
through source frames and are reported by the generated entry boundary.

A throwing `??` fallback and a throwing `match` default use the same transport.
Only the selected fallback evaluates its operand. A coalescing expression retains
the carrier payload type, while a match gets its type from the value arms.
Both forms check the exception operand during preparation.

A braced `finally` may follow the try body directly or follow its last catch.
It runs once after the protected operation has unwound or finished, including
after runtime guards and exceptions escaping a handler. A saved value or void
return resumes after cleanup. Nested finalizers finish from inner to outer.
An escaping finalizer exception replaces the pending exit. Returns from finally
are rejected. Bare and numbered exits can cross protected regions and
resume their resolved destination after intervening finalizers. Break counts loops
and switches. Continue counts loops. Continue proceeds to
the loop update or next test. Break skips both. Transfers within a finalizer
can target its own loops. A transfer out of a finalizer is rejected. Exception
statements in yield producers and exception regions during readonly initialization
remain deferred.

See the [exception contract](exceptions_v0_2.md) for the full language
semantics and [the native proof](../compiler/tests/exceptions_native.py) for
diagnostic identity, byte location, and incremental recovery. The [finally proof](../compiler/tests/finally_native.py)
checks cleanup order, exit precedence, and recovery after edits to finalizer dependencies.

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
[the normative namespace contract](namespaces_v0_2.md). See
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
[function contract](compiler_entry_v0_2.md#explicit-mutable-reference-parameters).
Read-only references and reference defaults are deferred.

Braced `if`/`elseif`/`else if`/`else` now share one AST and preparation path across
flavors. `elseif` is reserved in all three. Each branch body has a lexical scope;
standalone blocks and unbraced arms remain deferred. Generated type handlers admit
bool/int64/float64/string conditions and reject null/byte/void. See the
[conditional contract](conditional_statements_v0_2.md).

All flavors now admit braced `while (condition) { ... }` and
`do { ... } while (condition);`, including nesting. Both reuse generated condition
admission and lexical body scopes; the trailing do-while semicolon is mandatory.
Break/continue and unbraced loops remain deferred. See the
[loop contract](loop_statements_v0_2.md).

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
and preparation. The [scalar contract](compiler_scalars_v0_2.md#int64-compound-assignment-statements--2026-11-05)
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
for general unary signs. See [the scalar contract](compiler_scalars_v0_2.md)
and [implementation/proofs](../compiler/docs/unary.md).

## Prefixed integer literal slice

All flavors accept hexadecimal `0x`/`0X` and octal `0o`/`0O`, with at least one valid digit.
Unprefixed leading-zero literals remain decimal. These spellings produce int64 values, not inferred
unsigned or narrow representations. Direct signed prefixed literals admit the full signed int64 range,
including its minimum, while exponentiation retains its existing precedence over unary signs.
Malformed digits and out-of-range values produce source diagnostics. Written destinations reuse generated
checked conversions. Binary prefixes, separators and suffixes remain deferred.

## Checked shared-class access

The bounded implementation of
[checked object access](inheritance_v0_2.md#checked-shared-object-access-and-type-tests)
admits class and interface operands/targets. Explicitly shared struct operands admit same-type and ancestor targets, and descendant targets when the declared source is polymorphic.

| Intent | C++ flavor | PHP flavor | JS flavor |
| --- | --- | --- | --- |
| Required checked view | `object_cast(node, Leaf::class)` | `object_cast($node, Leaf::class)` | `object_cast(node, Leaf.class)` |
| Boolean type test | `node instanceof Leaf` | `$node instanceof Leaf` | `node instanceof Leaf` |

`object_cast` and `instanceof` are reserved operator spellings in all three flavors.
The cast's second operand is a static type marker, not a value argument. Both operations
retain a single evaluated operand and a written target type reference; ordinary
namespace/import resolution applies. A target cannot be a string, variable or ownership
wrapper. `instanceof` binds more tightly than logical negation and comparison:
`!node instanceof Leaf` means `!(node instanceof Leaf)`. Parentheses can make that
intent explicit. Tests do not narrow the variable's static type.

Common preparation admits same-type/upward relationships and polymorphic downward
relationships, including class/interface and interface/interface cross-casts. Two
interfaces need no known common implementer; a class can gain an interface through
a descendant. Final classes cannot acquire additional interfaces through descendants;
impossible final-class/interface casts and tests reject during preparation. Required and optional shared operands are accepted; bare null, inline
values, unrelated records and weak/unique handles are rejected. A successful cast
returns the required target class/interface view or a present `shared<TargetStruct>`. Absence/mismatch throws `invalid_object_cast`;
`instanceof` returns false. Native RTTI must be enabled for downward operations and cross-casts.
No virtual member is introduced solely for casting. Same-type/upward operations use
presence checks and pointer adjustment and do not require RTTI.

## Record and trait destructors

PHP spells the zero-argument method `function __destruct() {}`, C++ spells it
`~Name() {}`, and JS spells it `destructor() {}`. In a C++ trait, `Name` is the
authored trait name. Each consumer emits its selected destructor on its own native
record. Ordinary member visibility, access and nonthrowing cleanup apply.
[Destructor contract](destructors_v0_2.md)

## Abstract trait hooks

A PHP trait can declare `abstract protected function step(): int;`. JS uses
`protected abstract step(): int;` and C++ uses `protected: virtual int step() = 0;`.
After inheritance and composition settle, a concrete consumer must supply a concrete
instance method with compatible parameters, return and visibility. The implementation
can be local, inherited, or contributed by another trait. Each obligation is checked
even when no body calls it. Multiple traits may require the same method.
An ordinary implementation keeps its existing dispatch. An abstract consumer reuses a compatible existing method or carries an unresolved hook
as one pure virtual slot. Same-name obligations share the slot, with public visibility
taking precedence. Concrete descendants implement it through ordinary override rules.

## Explicit ancestor member access

PHP uses `parent::evaluate(...)`, JS uses `super.evaluate(...)` and Python uses `super().evaluate(...)`. Lookup starts at
the immediate base of the lexical declaring record and follows inherited members.
The C++ flavor accepts `Parent::evaluate(...)` for a lexical parent method call.
It also accepts an actual ancestor type name, such as `Node::evaluate(...)`, including
ordinary namespace/import qualification, to select a particular strict ancestor.
Inside a contributed trait method the lexical declaring record is the consuming class or struct.

For instance methods these forms select the resolved ancestor implementation on the current object and
suppress virtual redispatch for that call. Virtual calls made inside that body still
use ordinary dispatch. Defaults and conversions belong to the selected method.
Written parent qualification and prepared fixed dispatch are retained on the existing
call node; argument syntax and semantic admission use the ordinary call machinery.

For static methods, preparation binds the selected declaration directly, including
its visibility and defaults. PHP/JS parent qualification requires a lexical base.
C++ static qualification names a type explicitly. A static caller can call a static
target. An instance target requires an initialized current instance. PHP `parent::$count` and `parent::STEP`, and JS `super.count` and `super.STEP`,
use the same lexical base selection for data members. C++ uses the written type,
such as `Middle::count` and `Middle::STEP`. Reads and writes select the declaring
property’s single storage location. Constants are read-only. Both forms enforce
the selected declaration’s visibility.
A parent method call may be the first authored constructor-body statement, after
implicit base construction. It is not mistaken for `parent::__construct(...)` or
`super(...)`.

## Abstract classes and methods

All three flavors admit explicit `abstract class Node`. In the C++ flavor,
`abstract class` is a Simple C++ source marker, not a native C++ keyword.
Bodyless public instance methods use these spellings:

| Flavor | Declaration inside an abstract class |
| --- | --- |
| PHP | `abstract public function evaluate(): int;` |
| JS | `abstract evaluate(): int;` |
| C++ | `virtual int evaluate() = 0;` |

Abstract methods participate in virtual dispatch. Every replacement requires the
existing override marker (including PHP `#[\Override]`). A bodyless replacement
uses `abstract override` in PHP/JS or trailing `override = 0` in C++.
Signatures follow the shared-value override rules. Abstract methods cannot have
bodies or be final, static, private, constructors or destructors. PHP/JS abstract
methods do not also take an explicit virtual marker.

An explicitly abstract class cannot be constructed, even when all its methods
have implementations. A concrete descendant must implement all inherited abstract
obligations; an abstract intermediate may retain or reintroduce them. Inherited
concrete implementations satisfy obligations without redeclaration. Fixed ancestor
calls cannot select a bodyless abstract method. Constructors and fields in an
abstract base remain usable through concrete descendants.

Lowering uses native pure virtual declarations and existing shared ownership.
Abstract structs use the same method markers and obligations. A concrete inline descendant remains exactly copyable when its fields allow it. Abstract structs cannot be complete inline values, including in fields, callable signatures and collection payloads. Explicit shared handles may name them. Shared-value callable variance applies to explicitly shared structs. Unique ownership and borrows remain deferred. Class interfaces are covered below. See the
[implementation and verification](../compiler/docs/abstract_methods.md).

Static method lookup starts at the qualified type and follows its base chain. Lexical
`self::` (PHP), `Self::` (C++) and `class.` (JS) spellings start at the enclosing record. Protected access requires the
declaring record or a descendant context; no instance receiver is involved.
Descendants can redeclare a static method with an exact signature and equal or wider visibility.
A final static method seals that declaration. Protected static properties and constants accept declaring-type and descendant names, and lexical self.
Lookup starts at the qualified record and binds the declaring record’s property storage or constant. See
[protected static methods](../compiler/docs/protected_static_methods.md).

## Class interfaces

The first interface slice admits public bodyless instance contracts and class
implementers in all flavors. Bare interface types use shared storage. Class instance
fields and functions preserve written nullability.
Interfaces cannot be constructed.

| Flavor | Interface declaration | Implementing class |
| --- | --- | --- |
| PHP | `interface Readable { public function read(): int; }` | `class Leaf extends Node implements Readable { public override function read(): int { return 8; } }` |
| JS | `interface Readable { read(): int; }` | `class Leaf extends Node implements Readable { override read(): int { return 8; } }` |
| C++ | `interface Readable { int read(); };` | `class Leaf : public Node, public Readable { int read() override { return 8; } };` |

The concrete base is optional. C++ places it first when present; the remaining colon
edges are interfaces. PHP/JS use a comma-separated implements list. Multiple independent
interfaces may have compatible same-name requirements. A fixed shared-value parameter may widen and a shared result may narrow along settled ancestry. Modes, presence and optional positions remain exact. Default values and parameter names may differ. The static callable determines defaults.

Every locally authored implementation needs the existing Override marker, including
PHP `#[\Override]`. An unchanged compatible inherited public method fulfills an
interface without redeclaration. The compiler may emit a native forwarding member;
it does not change the original base method's dispatch policy or source identity.
Abstract implementers may retain outstanding obligations; concrete types must fulfill
them. Nonvirtual source hiding restrictions still apply.

An inherited public final class method can fulfill a newly introduced interface.
An interface can extend multiple interfaces. PHP and JS use `interface Both extends Left, Right`.
C++ uses `interface Both : public Left, public Right`. The static interface selects the
original method and its defaults. A diamond shares its original declaration. If separate branches
declare the same method, an explicit compatible redeclaration can select the call contract.
Struct implementers and broader reference returns remain deferred.
Interface fields, constants, static methods, constructors, destructors and method
bodies are not admitted. Unique, borrows and threading are outside this slice.
See [implementation and verification](../compiler/docs/interfaces.md).

## Bounded match expressions

All three flavors admit `match (subject) { label => result, default => result }`.
See the [language contract](match_v0_2.md) and
[compiler ownership/lowering](../compiler/docs/match.md). Integer/enum subjects,
constant labels, one required default and exact result types form the first slice.
Literal `match(true)` additionally admits ordered boolean conditions and lazy results.
General boolean subjects and broader selection forms remain deferred.

### Protected instance methods

PHP/JS use the protected member modifier; C++ uses protected member sections.
Ordinary class/struct instance methods and class virtual/abstract methods share the
existing lexical and declared-receiver access contract. Overrides may widen
protected to public, never narrow visibility. Protected static methods use lexical access as described above.

### Final classes

PHP/JS use `final class Leaf`; C++ accepts `class Leaf final`. Final classes may
inherit and implement interfaces but cannot be extended. Abstract/final combinations,
duplicate final modifiers and final structs/interfaces reject in this class-only slice.
The native payload is final; ownership and required-field policies are unchanged.

## Static calls qualified by a value

All three flavors accept `produce()::method(args)`. A local qualifier is
`$p::method()` in PHP, `p::method()` in JS and `(p)::method()` in C++.
C++ keeps `p::method()` as a named path. Existing JS dotted calls retain
their ordinary value/namespace lookup.

Preparation uses the qualifier's declared class or struct and the selected
method's ordinary access and argument contracts. At execution the qualifier
finishes, including temporary destruction, before the first argument. An empty
typed handle can qualify the call. See the [static call contract](static_members_v0_2.md#expression-qualified-static-calls--2026-10-07).

The same postfix qualifier admits static property reads, plain statement assignments
and constant reads. PHP distinguishes `produce()::$count` from `produce()::LIMIT`.
C++ and JS use `produce()::count` and `produce()::LIMIT` with their existing static
data lookup. Inherited storage belongs to the selected declaration. On a write,
the qualifier finishes before RHS evaluation and conversion. On a constant read,
the qualifier still runs before the constant value is used. Such reads are runtime
expressions and cannot supply constant-only defaults or value-match labels.
