# Token Buffer Runtime Contract

Status: implementation contract for the runtime tokenizer fast path.

The runtime tokenizer returns `token_buffer` for both `phs_tokenize_buffer()` and
`jss_tokenize_buffer()`. `phs_tokenize()` and `jss_tokenize()` also return this
typed buffer. Readable/mixed output is an adapter and is not the compiler fast
path.

## Source-Level Signatures

Current strict-profile source signatures are:

```text
phs_tokenize(string): token_buffer
phs_tokenize_buffer(string): token_buffer
jss_tokenize(string): token_buffer
jss_tokenize_buffer(string): token_buffer
token_buffer_count(token_buffer): int
token_buffer_kind_id(token_buffer, int): int
token_buffer_start_offset(token_buffer, int): int
token_buffer_length(token_buffer, int): int
token_buffer_line(token_buffer, int): int
token_buffer_column(token_buffer, int): int
token_buffer_flags(token_buffer, int): int
token_buffer_to_mixed(token_buffer): mixed
```

The `*_tokenize_buffer` names currently consume source text as `string`; a
future `source_buffer` overload should enter metadata as a distinct authority
row rather than changing these signatures in place.

## Hot Tokens And Extras

`token_buffer` stores one canonical hot token stream plus side data:

```text
tokens
tokens_extra
```

The current native implementation uses compact parallel storage for the hot
token fields. Line/column are compatibility accessors derived from
`line_start_offsets`; they are not stored per token in the hot stream.

| Column | Runtime storage | Public accessor | Meaning |
| --- | --- | --- | --- |
| kind | `uint16` | `token_buffer_kind_id()` | Token kind id. |
| offset | `uint32` | `token_buffer_start_offset()` | Source byte offset. |
| length | `uint16` plus extended-length sidecar | `token_buffer_length()` | Token byte length. |
| flags | `uint16` | `token_buffer_flags()` | Token trivia/diagnostic flags. |

`length == UINT16_MAX` means the real length is stored in
`tokens_extra.extended_lengths`, keyed by token index.

Token kind ids are currently numeric runtime constants:

| Id | Meaning |
| --- | --- |
| 0 | EOF |
| 1 | identifier |
| 2 | keyword |
| 3 | number |
| 4 | string |
| 5 | symbol |
| 6 | comment |
| 7 | error |

These ids can later be exposed as an enum-backed `uint16` contract without
changing the column width.

Token flags currently include:

| Flag | Meaning |
| --- | --- |
| `1` | whitespace before this token |
| `2` | newline before this token |
| `4` | extended length sidecar is required |

## Location Rules

- Offsets and lengths are byte based.
- `token_buffer_line()` and `token_buffer_column()` are derived from
  line-start side data.
- Columns are byte columns, not UTF-8 codepoint or grapheme columns.
- Derived lines and columns are 1-based.
- EOF is emitted at the current scanner position after consuming trailing
  whitespace/newlines.
- Values outside the fixed-width column range raise a runtime error.

## Adapter

`token_buffer_to_mixed()` is provided for diagnostics, debugging, and legacy
readable output. Parser and compiler stages should prefer typed accessors or
direct runtime integration over `mixed`.
