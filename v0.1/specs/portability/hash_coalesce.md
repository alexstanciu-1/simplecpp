# Local string-hash coalescing
Doc Status: supporting

Use an explicitly typed local string hash for a single missing-key fallback:

```php
$digests /** hash<string> */ = [];
$before = $digests[$path] ?? '';
```

PHP-to-PHS conversion keeps `??`. The legacy PHS-to-C++ generator recognizes the
simple local receiver and exact string hash type, uses the existing non-mutating
`find()` lookup, and passes its nullable string to `coalesce_eval`. Missing keys
select fallback; present empty strings and `'0'` do not. Keys run once, fallback
runs only when needed, and errors from either evaluated expression propagate.
The selected string is owned and survives later hash mutation. Ordinary indexed
reads remain checked and still throw on absence.

This is a bounded local metadata check. Nested/member/temporary receivers,
non-string hash payloads or keys, mixed/dynamic containers, and `??=` are not
covered. No remote type inference, general PHP array behavior or receiver mutation
from inside key evaluation is promised. Existing nullable and wrapper coalescing
rules remain unchanged; incompatible fallback types still fail at the target.

Both authored publication expressions stay unchanged. The generator repair belongs
to the access context, not the PHP-to-PHS converter, runtime, or publication worker.
The [conditional matrix](../conditional_expression_matrix.md) owns semantics.

The lookup does not copy the hash or insert missing keys. A present string is copied
into an owned nullable and may be copied again by coalescer normalization. No speed
or allocation reduction is claimed; changing generic coalescer ownership would be
a separate task.

Focused proofs (fresh result directories):

```sh
python3 tests/portability/hash_coalesce.py --results /tmp/FRESH_COALESCE
python3 tests/portability/hash_coalesce_compiler.py --results /tmp/FRESH_PUBLICATION
```

The second accepts `--resume`, rebuilds the unchanged compiler, compares PHP/native
publication bytes across lifecycle/recovery scenarios, checks ownership protections,
and builds/runs real provider consumers from both publications. Both use the shared
runner; native runs bypass legacy STAN and leave LLVM parked. This is not a
cumulative native sweep. Dated results and baseline failures are saved in the
[conversion-quality plan](../../compiler/my-try/docs/planning/conversion_quality.md).
