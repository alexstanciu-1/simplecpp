# Portable sequence and keyed collection helpers
Doc Status: supporting

The PHP framework exposes `sequence_map`, `sequence_filter`, `keyed_map` and
`keyed_filter` through the uniform imports. The converter maps them to the same
names on the native target, which owns carrier/type validation. This requires
candidate `08c8206aae914240d8b25fd2d1cd49c7149dd417` (or a subsequently proved target),
not the old v0.1.76 release. The candidate includes #231 adapters and the #232
nested-vector field-read fix; it is not a released or merged version.

| Operation family | Portable input | Result |
| --- | --- | --- |
| sequence | Annotated vector, dense PHP list | Fresh dense vector/list |
| keyed | Annotated typed hash, PHP array | Fresh table preserving keys/order |

The policy is explicit, never inferred from PHP array shape. Filtering `[10,20,30]`
for values above ten produces `[20,30]` for sequences and `[1=>20,2=>30]` for keyed
operations. PHP checks list shape only to validate sequence input. Native carrier
constraints reject hash-to-sequence and vector-to-keyed calls, even for empty or
dense-integer-key hashes. The adapters reuse the generic target algorithms.

## Callback authoring

```php
$values /** vector<int> */ = [10, 20, 30];
$limit = 10;
$selected /** vector<int> */ = sequence_filter(
    $values,
    static function (int $value) use ($limit): bool { return $value > $limit; }
);
```

This slice accepts `function` and `static function` expressions with exactly one
explicit scalar/named by-value parameter, an explicit non-void return type and
optional explicit by-value captures. Callback bodies use the existing structural
statement grammar. References, defaults, variadics, untyped parameters/returns,
arrow functions and general callable-local invocation remain outside this slice.
The import policy distinguishes closure capture lists from namespace imports;
manual imports remain prohibited. The converter does not resolve captured values,
receiver types or callback signatures against collection element types.

A map callback returns the new element; a filter predicate returns bool and retains
the original element. Empty input invokes no callback. Exceptions propagate and
partial results are not published. The host framework rejects a non-bool predicate
result when invoked; it does not prove exact native type compatibility, including
on empty input. Native validation owns that obligation.

Callbacks must not mutate input, aliases, shared elements or reachable captured
state. Purity is authoring discipline, not whole-program analysis. Fresh outer
membership does not deep-copy objects. PHP numeric-string key coercion remains a
representation restriction: canonical integer-looking string keys cannot be used
to prove native string-key distinctions. Dynamic/mixed carriers and fixed-array
source annotations are not added to the portable profile here.

## Evidence and target boundary

`tests/portability/collections.py` converts executable PHP, compares PHP/native
output to independent expected results and checks rejected carrier/signature and
callback syntax cases. It explicitly pins the combined candidate and checks its
checkout stays clean. Coverage includes sequences, dense integer hashes, string
keys/order, string-value callbacks, type-changing maps, captures, nested calls,
empty/all/none results, shared object identity, independent outer membership and
exception propagation. It also proves the chained nested-vector field read from
#232 through converted source, without generated-C++ edits.

The host-only `collections_php.php` additionally tests sparse/negative integer
keys, callback invocation order, exception identity and no partial publication.
Its instrumentation callbacks are test observations, not authoring examples.

The prior [PHP-only checkpoint](../planning/compiler_migration/results/collections-php-01/summary.json)
identified missing constrained native adapters. v0.1 commit `541413ac` supplies
them; that earlier integration blocker is resolved in the combined candidate.
New collection and cumulative compiler evidence is stored under
`specs/planning/compiler_migration/results/collections-native-01/`.
Process and lock framework parity remain separate unfinished work under #231.

The [runtime-preparation symbol slice](compiler_preparation_symbols_slice.md) adds
`sequence_require_strings`: an explicit PHP-carrier assertion whose native signature
is `vector<string>`. It retains caller-owned shape/element diagnostics without
adding converter type inference or generic native mixed-value validation.
