# Quoted-byte literal decoding
Doc Status: supporting

`check_bodies\Byte_Literals` now uses explicit byte access and typed scalar state.
Single/double quote behavior, recognized control escapes, one-to-three-digit octal
escapes (modulo 256), one-to-two-digit hexadecimal escapes, unknown escapes and
existing malformed/interpolation/Unicode-escape errors are preserved. The converter
still performs structural translation without symbol or string-type inference.

The framework owns `string_byte_from_int(int): string`: exactly one byte for 0..255,
with an InvalidArgumentException outside that range. The PHP implementation uses
checked chr; the native implementation constructs two hex digits and extracts the
existing hex2bin result. This bounded adapter needs no v0.1 compiler/runtime change.
It makes no performance claim and does not relax binary source-literal restrictions.
It is not a Unicode scalar encoder. Uniform imports include the new operation.

The decoder continues the prototype's append-based algorithm. No new interpolation,
Unicode escape or compiler language feature is introduced. Other body-checking
components remain unmigrated.

Evidence: `specs/planning/compiler_migration/results/byte-literals-01/summary.json`.
The frozen original matches on 8,593 inputs: every raw/escaped/dollar-prefixed byte,
numeric escape boundaries, invalid forms and deterministic randomized combinations.
The PHP/native witness independently expects all 256 hex byte values, 512 octal
values (including truncation), control/unknown escapes, multibyte literals, partial
numeric escapes and exact error messages. Byte-adapter range errors are also checked.

Strict build, STAN, native execution and seventeen retained compiler fixtures pass
on `2f0d667f38a35ff02ef77e813f409189cba2d032`. Thirty-one production files are ready;
that count is not an estimate of whole-compiler completion.

The retained `integration/runtime_strings.php` fixture also passes: real runtime
strings, literal construction, copying/cleanup, workers and incremental literal
replacement. Its separate logs are retained beside the cumulative evidence.
