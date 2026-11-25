# Parser foundation: syntax storage and angle matching
Doc Status: supporting

This is the first parser slice, not a complete grammar parser. Three production files
under `compiler/src/03_parse/` own syntax vocabulary/rows, a private mutable arena,
and token-level binary precedence/template-angle matching. Statement, declaration,
expression grammar, semantic resolution and incremental parser coordination remain
unimplemented in the active rewrite. Next: the expression grammar on this storage.

`Syntax_Row` is a compact scalar record with uint32 kind, start, length, first child,
last child and next sibling. Numeric kind values retain the prototype vocabulary.
`Syntax_Arena` owns its typed row vector, one-based IDs and zero sentinels. `row()`
returns an explicit independent copy; `finish()` and `child()` replace copied rows
inside the owner, so PHP identity cannot accidentally supply native record mutation.
`add()` checks numeric capacity; syntax spans are bytes. Input-source bounds and kind
membership remain grammar-owner responsibilities. Capacity guards are tested by
inspection, not by allocating billions of rows.

The extra last-child field provides constant-time child append without the prototype's
reference parameter or exposing storage mutation. Builder precondition: append only
an unattached subtree containing no ancestor of the parent. This is an internal
construction API, not a validator for arbitrary graphs. Basic ID/self-link/duplicate
last-child misuse is rejected; general cycle or multiple-parent detection is not
claimed. A future memory pass can measure the extra field versus separate builder
scratch. No exact sizeof benchmark is claimed.

`Binary_Syntax` preserves the prototype's scoped angle stack, reusable capacity,
precedence and source-only disambiguation algorithm. It does not resolve whether an
identifier denotes a template. Explicit missing states replace nullable enum/string
returns: zero means no supported binary operator, empty string means no operation
name. Classification converts stored uint32 token tags to int before comparing with
int vocabulary constants. Native strict comparisons otherwise failed despite PHP
success. Use explicit numeric-domain normalization at this boundary; do not weaken
strict comparison globally or patch generated code.

## Reused tests and native evidence

The preserved 5,000-stream `binary_syntax_oracle.php` unit runs against the new helper
through a host-only vocabulary/loading adaptation. Its control flow and expected
comparison remain unchanged. The frozen original algorithm supplies the oracle.
`reuse_unit.php` records this bridge; it does not convert host test infrastructure.

Another 408 scoped token streams (eight named/deep cases plus 400 deterministic
random streams) compare the retained implementation with the rewrite in PHP/native.
Named scope cases have independent expectations as well. Six arena/precedence
outcomes check ordered links, byte spans, independent row reads, retained earlier
copies and rejected operations. Total native outcomes: 414. Full parser units such
as parsing.php, parameter_parsing.php and struct_parsing.php are not passing or
claimed yet; their grammar components have not been rewritten.

Native build attempt 1 compiled but failed behavioral comparison: every angle map
was empty due to uint32/int strict comparisons. An explicit int cast corrected it;
attempt 2 compiled and all outcomes passed. Thus build success took one attempt,
behavioral success two attempts and one corrective cycle. A separate host oracle
script typo was fixed before native testing. No converter or target code changed.

```sh
python3 compiler/tests/parser_foundation/run.py --results FRESH \
  --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results FRESH
```

Exact clean native target: `9b4b33f35f053b487e018c94d6a4a7888d77c64a`.
Final cumulative validation runs PHP/tools; this slice's native proof is recorded
separately. Unchanged stages retain prior native evidence, avoiding redundant builds.
See [timings, cycles and provenance](../planning/compiler_migration/results/parser-foundation-01/README.md).
`src-runtime-preparation` stays unchanged PHP and outside conversion scope.
