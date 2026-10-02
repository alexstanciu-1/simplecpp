# Bounded scalar string interpolation
Doc Status: normative

Agreed 2026-10-02 for the my-try v0.2 frontend. This contract does not expand legacy
frontend support or change ordinary concatenation conversion rules.

Double-quoted strings admit `$name` and `{$name}` insertions. Names use the ordinary
ASCII identifier grammar and resolve to existing locals or parameters. Unbraced
names consume the longest identifier; braces disambiguate a literal suffix.
Single quotes never interpolate. Existing double-quote escapes remain unchanged:
`\$` inserts a literal dollar sign, and decoded escape bytes are never reparsed as
interpolation. Literal and inserted strings preserve arbitrary bytes, including NUL
and UTF-8 sequences.

Each insertion accepts a scalar supported by the existing explicit string cast:
string identity, integer widths, float and bool. Its representation is exactly that
of `(string)$name`; for example true becomes `"1"` and false becomes `""`. No
independent formatting policy, mixed-value coercion or object stringification is added.

Parts are evaluated and converted once each, left-to-right, then appended to a new
string value. The completed result is a non-addressable snapshot, independent of
later changes to inserted variables. In `$a = "[$a]"`, `$a` must already exist and
its previous value is read before the new string is stored. All names and types are
checked even when an enclosing runtime operation could skip evaluation.

## Bounded grammar

Fields, indexes, calls, assignments, arbitrary expressions, variable variables and
`${name}` are unsupported insertion forms. Braced syntax must be exactly `{$name}`.
Unbraced names followed immediately by `[`, `->` or `(` are rejected rather than
partially accepting a larger insertion. Use `"{$name}[0]"` or `"{$name}()"` when those
suffixes are literal text. Unknown names and nonscalar insertions fail preparation.
This grammar does not add declarations inside strings.

## Model and lowering

The lexer retains a complete quoted token. The parser creates an
`interpolated_string_node` with ordered literal and value parts only when an insertion
exists. Parts retain token-relative byte ranges; value parts own ordinary collected
variable references. No concatenation tokens or source-written cast nodes are invented.

Preparation attaches decoded literal bytes and string-conversion decisions to their
owning parts. The C++ backend consumes those facts and emits a string builder with
sequential append statements. The existing string runtime owns conversion and storage.

[Compiler tests](../compiler/my-try/tests/interpolation.php) cover structure,
collection, source ranges, diagnostics and incremental behavior.
[Generated-program fixtures](../compiler/my-try/tests/s2s.php) compare exact output
bytes across both spellings, scalar conversions, escapes and snapshots.
