# Portable tokenizer rewrite
Doc Status: supporting

Four files under `compiler/src/02_tokenize/` own lexical vocabulary/rows, buffers,
whole-file scanning and project composition. `File_Tokenizer::tokenize(Source_Buffer)`
returns a `Lexical_Buffer` retaining its source, token rows and explicit lexical
validity. `Tokenizer::tokenize(Source_Texts)` retains file order/entry and returns a
complete `Lexical_Project`; its valid flag is false if any file is invalid. Consumers
must check validity before parsing. Invalid files contain anchored start/length/reason
and no partial token rows. Source path remains available through the retained source.

## Reused behavior and redesigned storage

The prototype's ASCII identifier/variable rules, keyword vocabulary, decimal spelling,
punctuation/arrow handling, comments, quoted escapes and rejections are retained.
Trivia produces no rows. Successful files end in exactly one EOF row at byte length.
Type names remain identifiers. Strings are scanned without decoding; comments/strings
can contain arbitrary bytes. Unexpected bytes outside those contexts are rejected.
All spans are byte offsets, including malformed UTF-8; managed text helpers do not
belong in this scanner.

`Token_Row` is an explicit scalar record: uint32 start, length and numeric kind.
Native output is a struct in a typed vector, not one shared object per token. Token
text is derived from the source span; it is not copied into each row. Inputs larger
than uint32 span capacity are rejected before scanning. Actual >4GiB allocation is
not part of the focused test. Integer kind constants retain the prototype's numeric
vocabulary. `Token_Kinds::name` is a total debug encoder with `unknown` fallback;
it is not a semantic validation API. No exact native sizeof claim is made here.

The source buffer is retained by shared identity and must remain immutable by usage.
New rows are created once and never mutated after append; this avoids differing PHP
object versus native record-copy behavior. Explicit byte loops replace PHP span/
substring builtin combinations without adding converter inference. Small keyword/
punctuation selection helpers own classification. Future profiling may justify tables
or bulk byte operations; neither optimization is assumed to be faster without data.

The prototype's custom Source_Error exception is represented here as a structured
lexical failure with path/span/reason. Numeric file IDs, exception identity and exact
message formatting are not preserved. This is a lexical result contract, not a new
cross-stage diagnostics framework. Parser integration must consume invalid results
explicitly. No Step/Join lifecycle or incremental identity reuse is implemented yet.

## Existing tests reused

`compiler/tests/tokenizer/reuse_tests.php` executes the retained
`compiler/reference/pre-rewrite/tests/02_tokenize/tokenization.php` body with a minimal
reference-only host shim, replacing only bootstrap and the scan entry to capture cases.
Its existing assertions pass. Forty-one scans are captured from that unit, including
exact tokens/spans, variables, comments, errors, EOF, long identifiers/numbers and quotes.
The shim provides the historical buffer constructor/debug-export shape; it does not
claim that the new API implements the old debug serializer. The rewritten implementation
is checked against captured meaningful rows/error spans in both PHP and native.

Another 261 cases compare the reference tokenizer over all single bytes, vocabulary,
binary string contents and comment/escape edges. Two new project-batch cases check
entry/order/aggregate validity. Total: 304 outcomes. New checks require input purity,
no partial rows for rejected files, and correct retained error path. These differential
cases supplement the old independent assertions; agreement alone is not a language spec.

`variable_tokens.php` and `lexical_updates.php` exercise the old compiler session,
selective reuse and joins; they remain preserved for later incremental integration.
Do not count them as passing unchanged in this rewrite. No source or tests under
`src-runtime-preparation` were migrated; that area stays PHP as-is for now.

## Native corrections and proof

Checker-only source adjustments: file-scope integer constants replace unsupported
class constants; equivalent upper-exclusive comparisons replace unsupported `<=`;
explicit addition replaces unsupported `+=`. An import-sync invocation over host test
helpers was rejected and corrected by staging only the portable probe. No converter
syntax was expanded.

Native attempt 1 stopped in STAN on a runtime-name collision with Token_Buffer and
an unrecognized terminal-throw return path in the debug encoder. Distinctive
Lexical_Buffer naming plus an explicit unknown-kind return cleared attempt 2. The
batch owner/proofs were added before that second build. No target or generated code
was patched. All final stage verification uses clean exact candidate
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`.

```sh
python3 compiler/tests/tokenizer/run.py --results FRESH
python3 tools/php_portability/validate.py --results FRESH \
  --native compiler --target-checkout /tmp/scpp-json-240-probe
```

[Saved timing, test provenance and cycles](../planning/compiler_migration/results/tokenizer-rewrite-01/README.md)
separate corrective iterations from final verification. Next: parser, reusing suitable
existing parser unit cases against this token/result contract.
