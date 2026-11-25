# Conversion-only fake Storage checkpoint
Doc Status: derived

Run: `php compiler/my-try/tools/conversion_probe.php NEW_OUTPUT_DIRECTORY`.
The diagnostic tool copies 26 compiler PHP files, replaces the exact Storage and
Keyed_Storage type tokens with Conversion_Only_Storage / Conversion_Only_Keyed_Storage,
and removes their adjacent generic declaration annotations. Local annotations use
the corresponding placeholder name. Source line counts remain unchanged; original
and projected source hashes are recorded. Strings and other PHP comments are not
rewritten. Host bootstrap, tests and PHP collection implementations are excluded.

These placeholders intentionally have NO runtime implementation or generic element
checking. This is syntax exploration, not a mock that emulates collection behavior.
No generated output may be compiled, registered as ready, or used as a production
binding. Every partial PHS file and the output directory carry a DO NOT BUILD marker.
Actual source annotations, Storage implementation and production converter remain
unchanged by projection. Trait expansion uses the normal project declaration index.

At this checkpoint 13/26 files pass projected syntax conversion; 13 report their
first rejection. This count is not comparable to real-binding acceptance (previously
5/26). Results do not prove generic construction, element types, aliasing, nullable
access, collection operations, native module registration or resolved dependencies.

Independent real-source changes:
- Tokenizer now scans explicit integer bytes with supported byte helpers and
  ASCII predicates, preserving spelling and offsets; private class constants,
  strspn/str_contains and interpolated errors are no longer needed.
- llvm_policy initializes its two typed map entries in its constructor.
- Struct preparation uses byte slicing, q_count and explicit membership checks
  instead of raw substr/count and null-coalescing expressions.

PHP validation: exhaustive tokenizer cases for all 256 bytes, EOF/malformed forms,
exact spans/error offsets; Storage, AST, model, LLVM text and generation/rejection
checks passed. The 19 LLVM output fixtures remain byte-identical to the earlier
formatting checkpoint. No native build/execution performed. Probe outputs live in
/tmp/scpp-fake-storage-04; rerun the tool to regenerate against current source.
