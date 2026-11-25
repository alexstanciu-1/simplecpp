# Native tokenizer implementation debt

Doc Status: planning

The PHP bootstrap uses UTF-8 `preg_match_all` to move bulk lexical scanning out of
interpreted PHP loops. This is a PHP implementation choice, not a language or native
architecture requirement.

When porting to Simple C++, evaluate a proper native tokenizer first. A direct
scanner will probably be a better fit: it can validate UTF-8, classify tokens and
write the token buffer without PHP-style match arrays or temporary lexeme strings.
Measure it on representative workloads before choosing the final implementation;
PHP regex speedups do not predict native performance.

Keep the settled contract: all three flavors, unchanged ASCII identifier rules,
byte offsets and lengths, lossless literal spelling, longest punctuation matches,
24-bit token-length limits, UTF-8 rejection, and precise lexical diagnostics.
Keep per-source state privately owned; process-wide regex caches are bootstrap
mechanics, not a requirement to introduce mutable shared native tables.

Before a native switch, retain token/diagnostic equivalence tests and compare
reading/tokenizing/parsing separately, including long valid and malformed tokens.
No native tokenizer implementation or additional identifier features belong to
this PHP integration slice.
