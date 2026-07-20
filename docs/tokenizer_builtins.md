# Tokenizer builtins - first pass
Doc Status: supporting

PHS/JSS tokenization is runtime-owned and lives under
`namespace scpp::tokenizer`.

The compiler should consume tokenizer output as lexical facts. Parser,
resolver, overload, template, ABI, and LLVM semantics remain compiler-owned.

## Runtime files

- runtime module header: `runtime/include/modules/tokenizer/tokenizer.hpp`
- public runtime umbrella: `runtime/include/scpp/tokenizer.hpp`

## Implemented functions

Strict PHP++ and legacy PHP registry names:

- `phs_tokenize`
- `jss_tokenize`

Both functions currently return a `mixed` table with compact token columns:

```text
schema_version
language
source_length
token_count
diagnostic_count
kind_ids
start_offsets
lengths
line_numbers
columns
flags
line_start_offsets
diagnostics
```

This `mixed` table is the first exposed carrier so strict PHS code can inspect
runtime tokenizer output immediately. It is not the final required hot resident
representation. A first-class token-buffer type may replace it after compiler
parser integration proves the access pattern.

## Token Kind Ids

```text
0 eof
1 identifier
2 keyword
3 number
4 string
5 symbol
6 comment
7 error
```

## Offset Policy

All token offsets and lengths are byte offsets.

Compiler hot paths must not use codepoint `substr()` for character scanning.
Use runtime tokenizers or byte helpers.

## Semantic Boundary

The tokenizer is lexical only.

It may identify:

- keywords;
- identifiers;
- numbers;
- strings;
- symbols;
- comments;
- EOF;
- lexical diagnostics.

It must not identify:

- symbol resolution;
- method calls;
- property access;
- operator overloads;
- template/materialized types;
- ABI or LLVM lowering.
