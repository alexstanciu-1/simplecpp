# Typed string joining
Doc Status: supporting

## Contract

Use `q_implode(string $separator, array $pieces): string` with explicit
`vector<string>` intent. Exactly two arguments are required. Generated PHS calls
`implode($separator, $pieces)`, following the existing
[builtin contract](../builtins/string/implode.md).

Join elements in list order with separators only between elements. Empty lists
produce an empty string; empty elements/separators are valid. Preserve bytes,
including runtime NUL and invalid UTF-8; joining does not validate text encoding.
Binary source literals remain subject to the converter's existing restrictions.

The PHP adapter requires `array_is_list($pieces)` and string elements, throwing
`InvalidArgumentException` with `q_implode requires a list of strings` or
`q_implode requires string elements`. It never invokes PHP's element coercions.
Input membership/values are unchanged. A function-map entry selects this adapter
and the existing native builtin; no special parser rule or new native helper exists.

The converter checks arity, not remote argument types. Native typed validation
rejects incompatible element types. The existing native builtin also accepts
hashes; this portable slice supports string vectors only and does not promise a
converter rejection for every input outside that contract. No mixed/coercing,
legacy-swapped or one-argument form is exposed.

```php
$parts /** vector<string> */ = ['first', 'second'];
$text = q_implode(', ', $parts);
```

## Integration and validation

Only the two closure capture/parameter joins in
`compiler/my-try/05_backend/cpp/syntax.php` use the new helper in this slice.
Other incremental string builders retain their current implementation.

Run the focused adapter/conversion/native proof:

```sh
python3 tests/portability/string_join.py --results /tmp/FRESH_JOIN_RESULTS
```

Run the explicitly requested focused native compiler rebuild and closure parity:

```sh
python3 tests/portability/string_join_compiler.py --results /tmp/FRESH_COMPILER_RESULTS
```

Optional `--baseline DIRECTORY` compares all generated closure artifacts against
an existing pre-change export from `tests/closures.php`; `--resume` reuses that
proof workspace after a failure. The runner preserves attempt logs. Native runs
bypass legacy STAN and parked LLVM; these checks are not a cumulative native sweep.

Dated evidence and performance limitations are recorded in the
[conversion-quality plan](../../compiler/my-try/docs/planning/conversion_quality.md).

The optional Linux/Clang microbenchmark retains the old emitted loop for comparison:

```sh
clang++-18 -std=c++23 -O2 -DSCPP_LANGUAGE_TARGET_PHP=1 -I runtime/include tests/portability/fixtures/string_join_bench.cpp -o /tmp/string-join-bench
/tmp/string-join-bench
```

Its CSV columns are part count, implementation, trial, iterations, total nanoseconds
and output-byte checksum. Run without competing builds; it measures the native join,
not end-to-end compiler execution or allocations.
