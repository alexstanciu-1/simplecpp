# Token Buffer Runtime Contract

Status: implementation contract for the runtime tokenizer fast path.

The runtime tokenizer returns `token_buffer` for both `phs_tokenize_buffer()` and
`jss_tokenize_buffer()`. `phs_tokenize()` and `jss_tokenize()` also return this
typed buffer. Readable/mixed output is an adapter and is not the compiler fast
path.

## Columns

`token_buffer` stores one row per token in parallel fixed-width columns:

| Column | Runtime storage | Public accessor | Meaning |
| --- | --- | --- | --- |
| kind | `uint8` | `token_buffer_kind_id()` | Token kind id. |
| offset | `uint32` | `token_buffer_start_offset()` | Source byte offset. |
| length | `uint32` | `token_buffer_length()` | Token byte length. |
| line | `uint32` | `token_buffer_line()` | 1-based source line. |
| column | `uint32` | `token_buffer_column()` | 1-based byte column. |
| flags | `uint16` | `token_buffer_flags()` | Token flags; currently `0`. |

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

These ids can later be exposed as an enum-backed byte contract without changing
the column width.

## Location Rules

- Offsets and lengths are byte based.
- Columns are byte columns, not UTF-8 codepoint or grapheme columns.
- Lines and columns are 1-based.
- EOF is emitted at the current scanner position after consuming trailing
  whitespace/newlines.
- Values outside the fixed-width column range raise a runtime error.

## Adapter

`token_buffer_to_mixed()` is provided for diagnostics, debugging, and legacy
readable output. Parser and compiler stages should prefer typed accessors or
direct runtime integration over `mixed`.
