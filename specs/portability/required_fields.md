# Required fields without initializers
Doc Status: supporting

Ordinary class instance/static fields can omit an initializer while retaining an
explicit scalar or literal named type. Annotated vector/hash fields can also omit
an initializer. Visibility is preserved; no type resolution is added.

```php
public int $offset;
public int $compact_offset /** uint32 */;
public Row $row;
public array $rows /** vector<Row> */;
public static Record $root;
```

The optional `uint32` annotation on a required nonnullable `int` field emits an
unsigned 32-bit native field; PHP retains its `int` carrier. Authors must keep values
within 0..4294967295. This annotation currently requires an omitted initializer.

These emit native declarations with the same nonnullable types and no explicit
initializer. Native type default construction supplies the initial storage state;
there is no synthetic referenced object allocation, nullable wrapper or sentinel.
The initial state is not permission to consume an incomplete record: authors must
assign required fields before reading them or publishing a complete record.

PHP keeps its normal uninitialized-property behavior. This slice does not emulate
PHP's read-before-initialization exception in native code or promise observable
initial values for every native field type. Read-before-assignment is outside the
authored contract; target checks remain enabled.

Explicit nullable fields still require their existing explicit initializer, normally
null. Missing initializers do not infer nullable. Untyped/mixed/object fields,
unannotated arrays, union types and custom Storage annotations remain unsupported.
Readonly scalar constructor initialization retains its prior contract. Value-record
@scpp-struct declarations retain their separate, narrower grammar.

## Target analysis boundary

On pinned target 9b4b33f35f053b487e018c94d6a4a7888d77c64a, a method reading
private/protected required fields after a separate populate method assigns them
can fail STAN with a read-before-initialization diagnostic. The proof initializes
those fields in the constructor, which STAN can verify. Public data records are
assembled before publication; no new interprocedural initialization inference is
claimed. Do not disable STAN or fabricate nullable fields to hide this limitation.
Review each real worker's initialization protocol during conversion.

## Proof

`python3 tests/portability/required_fields.py --results FRESH` checks PHP outcomes,
checking/conversion, incremental conversion and source-attributed rejections. Add
`--target-checkout TARGET` for native execution. The validation driver includes the
PHP proof and exposes `--native required-fields`.

The fixture covers required scalar/enum/named/container fields, visibility,
constructor assignments, static publication/replacement, and retained old object
identity. It never consumes uninitialized fields. Evidence is recorded in
[the capability checkpoint](../planning/compiler_migration/results/required-fields-01/README.md).
No compiler-ready files are added; custom Storage bindings remain separate work.
