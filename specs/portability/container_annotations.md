# Explicit nested container annotations
Doc Status: supporting

Executable PHP can now declare native container intent recursively:

```php
private array $by_top_folder /** vector<vector<int>> */ = [];
private array $row_by_file_id /** hash<int, int> */ = [];
private array $id_by_path /** hash<int> */ = [];
```

These are member declarations inside a class. Locals use the same adjacent comment,
for example `$groups /** hash<vector<int>, string> */ = [];`.
No backticks belong inside the actual type comment.

## Parsing and conversion

`tools/php_portability/src/container_type.php` owns recursive lexical parsing:

- `vector<T>` takes one argument; `hash<T>` or `hash<T, K>` takes one or two.
- Elements/values can be scalar or literal named types, or recursively nested
  vectors/hashes. Named types are not resolved by the converter.
- Explicit hash keys currently admit `int` and `string`; omitted keys use the
  target's string-key default. Other key families need their own proof.
- Whitespace around type punctuation is accepted and normalized. Generic arity,
  complete consumption, unsupported element spellings and nesting over 32 levels
  produce source diagnostics rather than a guessed conversion.
- Public/private/protected annotated array properties preserve their visibility.
  This is syntax preservation, not a claim that every target visibility check is
  enforced. Ordinary scalar/private-field coverage is unchanged.
- Existing nullable field and nullable promoted-array forms use this same parser.
  The PHP `?` supplies nullability. This does not add generic method signatures or
  nonnullable array promotion.

PHP input annotations now emit [native PHS type syntax](map_iteration.md), e.g.
`private $folders vector<vector<int>> = [];`. Type doc comments are not needed in
the generated PHS.
Converter fingerprints include the parser source so changes invalidate generated
output. Missing type annotations still fail; documentary `@var` is not inferred.

## Meaning and authoring limits

`hash<int, int>` means integer values indexed by integer keys; `hash<int>` means
integer values indexed by string keys. This is existing Simple C++ map support,
not a new map implementation. PHP arrays are an execution carrier for that declared
intent. Use dense zero-based lists for vectors and the declared key family for maps.
Do not assume PHP numeric-string key coercion or arbitrary mixed keys match native
hash semantics. This slice proves existing-key access, assignment and copy behavior;
the subsequent [map/iteration slice](map_iteration.md) adds keyed `isset` and
by-value `foreach`. Removal and a complete map API remain outside these proofs.

Nested annotations are ordinary local conversion work, not a design blocker.
Source_Set still needs its actual operations and snapshot behavior adapted/proved,
but explicit types do not require symbol resolution or a replacement container.

## Native evidence and target limitation

`tests/portability/container_annotations.py` exercises private/protected fields,
vector-of-vector membership, sparse integer keys, path string keys, a map of integer
vectors, and independent copies. Both PHP and strict native v0.1.76 must produce:

```text
1:12:3:12
2:2:8:5
3:9
```

On this target, direct `$this->folders[0][0]` generates an invalid `.get()` call on
the inner vector. Use an explicit local for read access:

```php
$ids /** vector<int> */ = $this->folders[0];
return $ids[0];
```

That local has value-copy behavior; it is not a mutable alias to the field. An edit
to a copied inner vector requires an explicit write-back when field mutation is
intended. The proof separately checks nested edits through copied local containers.
No generated C++ is patched. The direct-field lowering limitation remains target
debt and is not concealed by the annotation parser.

Tracked as [Simple C++ issue #232](https://github.com/alexstanciu-1/simplecpp/issues/232).
The report records the v0.1.76 reproducer and verified workaround; newer target
revisions have not been tested for this failure. It is separate from #194's nested
literal construction failure and #231's runtime API requirements.

The [successful proof](../planning/compiler_migration/results/container-annotations-01/summary.json)
retains PHP/native inputs, command logs and the runner. The failed direct-field
probe is retained alongside it. `tests/portability/nullable_fields.py` additionally
checks malformed nesting, arity, unsupported key/element types, and no publication
on rejection. No compiler production files are added to the ready set by this slice.

Later candidate proof: [collection integration](collection_helpers.md) verifies
that commit `08c8206a` fixes the chained nested-vector field read (#232). The
v0.1.76 workaround above remains historical evidence, not a requirement on that
proved candidate.
