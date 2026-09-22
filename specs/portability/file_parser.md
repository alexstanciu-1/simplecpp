# File statement and declaration parsing
Doc Status: supporting

`parse\File_Parser::parse(tokenize\Lexical_Buffer $tokens): Parse_Result` parses one
complete tokenizer snapshot. The same owner exposes `parse_expression` for isolated
expression/type syntax. Parse_Result now lives in `03_parse/data/result.php`, separate
from expression continuation state; it replaces the expression-only result name.

A successful file result retains the exact input tokens/source, a compact Syntax_Arena,
one file root, an entry block and an ordered vector of top-level definition IDs. The
root owns the entry first, then the definitions. Interleaved declarations do not
interrupt executable statement order. Definitions index existing nodes, without
copies. Empty files still have a root and empty entry covering the whole source.

Lexical or grammar failure returns valid=false with a byte span and reason; root,
entry, definitions and the published arena are empty. A fresh invocation owns a fresh
arena; failure cannot replace or mutate a prior result. This boundary expects complete
tokenizer output, not arbitrary fabricated token vectors. Source identity and path
remain available through the retained token buffer. Diagnostics wording is not frozen.

## Grammar and ownership

Direct same-namespace traits retain the prototype layout:

- `handlers/statements.php`: ordered blocks, echo/return, typed locals, assignments
  and discarded expressions.
- `handlers/control_statements.php`: braced if/else-if/else, while, constexpr if
  and consteval context selection.
- `handlers/declarations.php`: file functions, ordered typed/reference parameters,
  structs, fields, array extents and public/const method wrappers.
- `handlers/metaprogramming.php`: template type/value parameters, constexpr/consteval
  function wrappers and global/local constant declarations.

These preserve the existing prototype grammar. Name/type resolution, writability,
constant evaluation and semantic validity belong to later stages. Nested declarations,
unsupported parameter defaults/packs, field initializers and unsupported syntax still
fail. No new language features were added.

The arena owns sibling tails. Handlers attach child IDs directly rather than passing
PHP scalar references. Zero denotes no declaration/reference annotation. Fixed integer
tag comparisons replace enum membership/match dispatch. Portable locals crossing
branches have explicit initialization. Error reset uses a typed empty vector before
assigning the result's definitions field, as required by the current S2S metadata limit.

Expression parsing stays iterative and now releases its root frame on every successful
return. Sequential expressions reuse bounded frame slots; storage remains proportional
to maximum expression nesting. Frames are currently newly allocated when a slot is
reused, an optimization opportunity rather than a semantic requirement. Statement/block
and else-if parsing retain the prototype's ordinary recursion; 128-level cases are
proved, not unlimited nesting or a new stack-resource guarantee.

## Evidence and boundaries

The focused proof scans five retained parser units and harvests 35 literal/nowdoc
inputs from four of them, plus explicit boundary/stress cases. The preserved parser runs independently as an
oracle; canonical ordered node kinds/byte spans/child counts and definition counts are
compared, not allocation IDs. Independent empty-file/return expectations and tree
reachability/span/index assertions supplement that oracle. A host lifecycle proof
checks unchanged token identity, independent arenas, failed-publication cleanup,
determinism and bounded expression continuation slots.

Whole original unit bodies also exercise syntax views, semantic stages and project
joins; this proof does not claim those full unit bodies pass.
[Syntax access/comparison](syntax_access.md) now has its own focused proof. Project-wide parser
selection/join/reuse and the compiler CLI are still pending. src-runtime-preparation
remains unchanged PHP.

```sh
python3 compiler/tests/statements/run.py --results FRESH
python3 compiler/tests/statements/run.py --results FRESH_NATIVE --target-checkout TARGET
```

See [timing, attempts and proof evidence](../planning/compiler_migration/results/statements-declarations-01/README.md).
