# Portable compiler string-byte boundary
Doc Status: supporting

This consolidation closes two concrete gaps found while preparing subsequent
compiler slices. It does not add a production file to the ready set.

## PHP byte lengths

Checkpoint update: the [UTF-8 text contract](utf8_text_contract.md) supersedes the
original default binding described below. Managed `strlen` now counts code points;
the portable compiler's byte-sensitive calls were changed to `string_byte_len`.
The original evidence and literal restrictions remain applicable.

PHP `strlen` counts bytes. The selected v0.1.76 target maps its `strlen` to
`str::length`, which calls `string_t::length_cp()`; its separate `string_byte_len`
uses `byte_size()`. The portable function map therefore keeps normal PHP `strlen`
in authored code but emits `string_byte_len` in PHP++. No PHP runtime override or
per-file import choice is needed. This is a mapping decision owned by the
portability tool, not a change to target language semantics. Older target builtin
prose describing `strlen` as byte-oriented does not match this selected release's
implementation; the migration proof uses executable evidence.

## Literal admission

The converter now gives quoted string tokens an explicit AST node and locally
checks their decoded bytes without evaluating PHP code or resolving names. The
same check applies to scalar string property defaults. Single-quoted escapes,
double-quoted simple escapes, hexadecimal escapes and octal escapes are accounted
for when validating bytes; the original spelling is retained in generated output.

Literal NUL bytes and invalid UTF-8 are rejected with a source-path/line diagnostic.
Unicode escape syntax is also rejected for now; UTF-8 source text can express
those characters. This prevents known silent binary-literal corruption by the
selected target. It does not promise arbitrary PHP string syntax compatibility.
Binary data obtained from runtime byte operations remains supported; this
restriction is on literal construction, not the string value's possible contents.
A native-safe construction mechanism for arbitrary byte literals remains work
before components requiring such constants can be marked ready.

## Proof

[string-bytes-01](../planning/compiler_migration/results/string-bytes-01/summary.json)
records the cumulative strict v0.1.76 native/PHP proof, including lengths of literal
UTF-8 and hex-escaped valid UTF-8. The local converter suite rejects hexadecimal,
octal and NUL binary literals in expressions and fields, and accepts valid UTF-8
escapes and single-quoted textual backslashes. The cumulative runner checks that
rejections leave the published conversion manifest intact. All nine compiler
fixtures and prior component witnesses continue to pass.

The compiler source algorithms, ready-file count, selected target and LLVM
provider pin are unchanged by this consolidation.
