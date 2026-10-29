# PHP compiler

Doc Status: supporting

The active rebuild is authored in PHP, using the stage layout and reusable compiler
concepts from `compiler/my-try/`. It requires 64-bit PHP 8.5 or newer; this slice
needs no PHP 8.6 features or packages. A future Simple C++ implementation will be
ported manually or with assistance, without imposing converter constraints here.

## Current working slice

```sh
php v0.2/compiler/main.php tokens php path/to/source
php v0.2/compiler/main.php tokens cpp path/to/source
php v0.2/compiler/main.php tokens js path/to/source
php v0.2/compiler/main.php parse php path/to/source
php v0.2/compiler/tests/tokenizer.php
php v0.2/compiler/tests/parser.php
python3 -B v0.2/compiler/tests/cli.py
```

The CLI requires an explicit flavor and emits JSON only after a complete scan or parse.
Token rows contain kind, byte offset, length and lossless hexadecimal spelling.
Errors go to stderr with a nonzero exit status. Input uses Simple C++ flavor syntax;
PHP opening tags, C++ preprocessing and JS template literals are not supported.

## Ownership and layout

- `boot.php`: explicit dependency loading, with no scanning or CLI side effects.
- `main.php`, `host/cli.php`: executable entry and presentation.
- `compiler/flavor.php`: per-source syntax identity and bounded keyword vocabulary.
- `compiler/frontend.php`: stage coordination, reusable across mixed-flavor sources.
- `compiler/diagnostics.php`: errors tied to the retained source and byte position.
- `01_prepare_inputs/`: file loading and immutable source snapshots, LF line indexing.
- `02_tokenize/`: scanner workers and immutable token publications. Trivia stays in
  source bytes; token spelling is sliced on demand. Adjacent angle queries preserve
  generic delimiters and recognize shifts without mutating tokens.
- `03_parse/structures/`: shared syntax nodes and checked source spans.
- `03_parse/flavors/`: small declaration/assignment grammars for cpp/php/js.
- `03_parse/cursor.php`, `parser.php`: shared token access, values and syntax publication.
- `tests/`: scanner, parser, source/publication and subprocess CLI proofs.

Future slices will populate the donor's remaining `03_parse/{collect,scopes,types}`,
`04_analyze/{conversions,operators,prepare}`, `05_backend/cpp`, `06_native`, helpers
and tools as working functionality arrives. Flavor-specific grammar lives under
`03_parse/flavors/`, feeding one common syntax model. No empty stage scaffolding is
needed yet.

The parser currently supports initialized declarations and simple-name assignments,
named type annotations, and integer/name values. `parse` outputs inspectable JSON
with normalized names, original spans, declaration annotations and assignment intent.
Integer spellings remain strings; parsing does not convert them to PHP integers.
Arithmetic, grouping, blocks, generic types and other expressions are later slices.

There is one PHP syntax flavor. Composed profiles selected in PRJ configurations
will control compiler policy; common analysis will enforce them across all flavors.
This slice introduces no profile configuration schema, profile enforcement, semantic
analysis, incremental reuse, backend or native execution. Successful parsing is not
semantic validity. See [the flavor contract](../specs/compiler_frontends.md).

## Migration boundary

The donor supplies stage ownership, source retention, trivia/span handling and
adjacent-angle queries. The retired Rust implementation supplied the bounded flavor
behavior: identifiers, keywords, comments, numbers and punctuation. The PHP code uses
ordinary arrays, readonly records and an integer-backed token-kind enum with explicit IDs and separate readable CLI labels; numeric Rust tags,
packed storage and allocation machinery are not PHP implementation requirements.
The byte source/token size bounds remain checked without claiming a packed layout.

Rust sources, tests and Cargo configuration have been removed.
The remaining parser ideas and proof cases are in
[parser migration notes](docs/parser_migration_notes.md). The donor remains unchanged.

Token kinds use explicit integer IDs in 1..127 (zero reserved). All keywords share
`token_kind::keyword`; consumers read their spelling through `token_list::text_at()`.
Recognition remains flavor-specific. CLI keyword rows use kind `keyword`, with the
exact spelling in `text_hex`. Adding a keyword does not consume another kind ID.

## PHP authoring style

Declare properties explicitly in the class body so the complete data layout is
visible independently of its constructor. Constructors take ordinary parameters
and assign fields explicitly; do not use constructor property promotion. Preserve
explicit types, visibility and readonly declarations where applicable.
