# Required fields without initializers
Doc Status: supporting

The omitted-initializer checkpoint below describes structural conversion and the
legacy route. v0.2 now has a temporary field-only empty-storage exception under
[type-use modifiers](../type_use_modifiers.md#temporary-required-field-storage-exception--2026-10-25).
Executable PHP should omit the initializer on required typed fields: PHP itself
does not permit a literal null default on a nonnullable object property. The PHS
exception does not change PHP validation. Assign before any use on both routes.
Existing promoted inputs and explicit checked nullable storage remain valid and
provide stronger local guarantees.

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

## Explicit staged class fields

The converter supports `public Payload $item /** _uninitialized_ */;` for a
class payload declared in the same file and a class owner without inheritance.
The field may remain absent after construction. Direct same-class `$this->item`
value reads and standalone whole-field assignments become private nullable storage
and checked accessors. A read before assignment fails; a null write fails without
resetting the previous value. Class reads preserve shared identity. The private
setter accepts nullable input internally so its explicit guard runs before any
required-value extraction; this does not make the original field nullable.

Adapt outside/unresolved receivers to explicit authored methods. Generated storage
is private and the original field is removed, so unreplaced external access cannot
bypass checking. Projection/mutation, assignment expressions, probes/removal and
nested callable receiver uses require source adaptation. Trait-owned/inherited
fields and unresolved/cross-file payloads are not admitted by this bounded slice.
Struct payload copying and struct owners remain separate unfinished work. No new
Simple C++ uninitialized value or annotation is introduced.

`python3 tests/portability/staged_marker.py --results FRESH` checks PHP, v0.1 with
`--no-stan`, and ordinary/concrete v0.2 execution. It covers early read, class
identity, self replacement, failed null writes, throwing RHS evaluated once with
previous state preserved, successful replacement, incremental conversion and
structural rejection controls. Exception class/message and cleanup timing parity
are not promised. This is not a whole-compiler native bootstrap or a STAN proof.

## Authored static startup storage

A required static object populated by a startup method is not a constructor input.
Use private static ?T backing initialized to null, a required checked getter, and
a setter accepting T. This uses existing private/static/nullable language contracts;
it needs no converter-generated staged-field protocol or startup-order inference.

Model runtime metadata uses this pattern. Compiler_Lifecycle validates privately
before its existing final publication point calls set_runtime_metadata; consumers
call require_runtime_metadata. Semantic resets retain the same input. The storage
setter is not a second configuration API; startup validation and live-replacement
policy remain lifecycle-owned. An early getter throws instead of returning a dummy
empty catalog. No catalog IO or lazy loading occurs in the accessor.

`tests/portability/runtime_metadata.py` extracts the actual storage/accessors and
proves early failure, identity, mutation visibility, throwing replacement preserving
old state, and replacement across PHP, legacy and ordinary/concrete native modes.
The payload is a dependency double; actual runtime_catalog, runtime_type_bindings
and startup_metadata host tests prove validation and reset/publication policy.
