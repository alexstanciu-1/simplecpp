# Tokenizer vocabulary portability slice
Doc Status: supporting

The maintained `compiler/src/02_tokenize/structures.php` is now included in
`compiler/portability.json`. It supplies the 36 integer-backed `token_kind` cases
and the tokenizer's `token` row class. Only managed function imports changed in
the adopted PHP source; case values, fields, defaults and behavior remain intact.

## Representation

Rows retain their existing ordinary class semantics: assignment shares the row;
construction creates a separate row with zero offsets/length and an invalid kind.
This is a behavioral migration, not completion of the packed token-storage design.
The existing source comment about eventual packed native layout remains a future
requirement. Struct/value copying, fixed-width backing metadata, token buffers,
containers and the tokenizer algorithm are outside this slice.

## Local conversion contract

The independent converter now accepts file-scope `enum Name: int` declarations
containing nonnegative literal integer cases and documentary comments. It also
accepts literal `Type::member` expressions and public named-type properties whose
initializer has that same structural constant-access form. Qualified type names
are accepted. These are syntax rules: the converter does not resolve whether a
name denotes an enum, whether a member exists, or whether it matches a property's
type. PHP and native checking own those questions.

Enums with string backing, implicit cases, computed/negative case values, methods,
reflection (`cases`, `from`, `name`, `value`), static calls and dynamic constant
access have no portability contract in this slice. No PHP runtime adapter was
needed. Reference classes and enum cases remain separate AST concepts.

## Proof

The cumulative [compiler component runner](../../tests/portability/compiler_context/run.py)
stages both production files with their original paths and one shared entrypoint.
It checks three-file conversion/no-op reuse, rejection without manifest mutation,
PHP/native default values and enum comparisons, shared mutation and fresh-row
independence. Existing compiler tokenization, variable-token and lexical-update
fixtures run alongside the two update-context fixtures.

```bash
python3 tests/portability/run.py
python3 tests/portability/compiler_context/prologues.py
python3 tests/portability/compiler_context/run.py \
  --target-checkout /path/to/clean-v0.1.76-checkout \
  --results /path/to/fresh-evidence-directory
```

[Recorded evidence](../planning/compiler_migration/results/token-02/summary.json)
passed against the selected v0.1.76 target with strict STAN enabled and Clang 18.
The provenance record verifies that removing the managed import block recovers
the original source bytes. Native output is generated evidence, never a second
maintained compiler implementation. LLVM provider configuration is unchanged.
