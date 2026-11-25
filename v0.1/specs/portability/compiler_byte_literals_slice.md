# Quoted-byte literal decoding
Doc Status: supporting

## Active migration checkpoint

The decoder is now registered in the active stage-by-stage migration. All **8,593**
frozen inputs pass in both PHP and native against pinned target
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`.
See `compiler/tests/byte_literals/run.py` and
`specs/planning/compiler_migration/results/byte-literals-active-01`. The source
uses global framework helpers; no generated function-import prologue is needed.
This proves decoding, not the unfinished body worker or runtime-provider pipeline.

## Preserved algorithm and historical checkpoint

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
It is not a Unicode scalar encoder. The shared bootstrap supplies the operation.

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
on `2f0d667f38a35ff02ef77e813f409189cba2d032`. At that historical checkpoint, thirty-one production files were ready;
that count is not an estimate of whole-compiler completion.

The retained `integration/runtime_strings.php` fixture also passes: real runtime
strings, literal construction, copying/cleanup, workers and incremental literal
replacement. Its separate logs are retained beside the cumulative evidence.
