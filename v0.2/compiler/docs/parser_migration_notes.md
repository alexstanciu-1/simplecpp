# Parser ideas retained for the PHP rebuild

Doc Status: planning
Date: 2026-10-29

The Rust compiler has been retired. These are design and proof notes, not claims
that the PHP compiler already parses programs. The active PHP pipeline stops at
complete tokenization. The language direction is in
[the flavor contract](../../specs/compiler_frontends.md); mine the working PHP
pipeline in `compiler/my-try/` as each stage is introduced.

## Small shared syntax model

Flavor-specific declaration/assignment grammar should feed a common model:
local declaration (name, optional type syntax, initializer), assignment (name,
value, binding intent), expression statement, and block. Keep nested type arguments
as syntax. Do not resolve types, names or project policy during parsing.

| Intent | cpp | php | js |
| --- | --- | --- | --- |
| Explicit local | `int count = 3;` | `$count int = 3;` | `let count: int = 3;` |
| Inferred local | `auto count = 3;` | analysis decides | `let count = 3;` |
| Assignment | `count = 4;` | `$count = 4;` | `count = 4;` |

C++/JS assignment requires an existing binding. PHP bare assignment retains
DeclareOrAssign intent until analysis can decide introduction versus update.
Normalize the leading PHP `$` out of the logical name, while retaining its full
source span. Integer expressions retain spelling; default integer type and overflow
belong to analysis. Unknown names/types and duplicate bindings are not parse errors.

## Expressions and resource limits

The prior small grammar handled integers, names, groups, unary +/-, multiplicative
operators, additive operators and shifts, with precedence descending in that order.
Binary operations associate left. Example: `10 - 3 - 2 * -(4 + 1) << 2` means
`((10 - 3) - (2 * (-(4 + 1)))) << 2`.

Keep generic closing angles separate. `vector<vector<int>>` consumes individual
angles; `input >> 2 + 1` combines byte-adjacent angles into a shift. Spaces or
comments between angles prevent combination. Do not rewrite the token stream.

A file-local expression arena avoided recursive destruction of long binary chains.
Choose the PHP representation on its merits; retain a long flat-expression proof
(10,000 operators) and a bounded nesting proof for groups, types, unary chains and
blocks. The prior nesting cap was 128, a resource guard rather than language policy.

## Proof checklist for the parser slice

- Equivalent explicit declarations across all three flavors produce equivalent intent.
- One frontend handles mixed-flavor files without leaking lexical or grammar choices.
- Source snapshots survive later scans; names and expressions retain exact spans.
- Inferred declarations differ from PHP unresolved writes; no premature symbol table.
- Nested generic types and shifts coexist without token mutation.
- EOF errors can use an empty span; diagnostics retain the source and byte location.
- Failed parsing publishes no partial AST; empty input yields an empty file.
- Reject wrong-flavor declarations, missing semicolons, malformed groups and blocks.

The old subset did not support functions, calls, member access, assignments as
expressions, const bindings, string/float expressions, PHP tags, preprocessing or JS
ASI/templates. Extending these is new work, not implied by successful tokenization.
Packed tokens, numeric tags, Rust ownership wrappers, allocation hints and build
benchmarks are not requirements for the PHP implementation. The API generator's
behavior is already covered by retained PHP tests and frozen parity fixtures.
