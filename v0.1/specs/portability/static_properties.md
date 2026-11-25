# Static class fields
Doc Status: supporting

The converter supports visibility followed by static on initialized fields, using
its existing instance-field type/default grammar. Static methods retain their
existing syntax and conversion path. No symbol/type lookup is introduced.

```php
final class Model {
    public static int $count = 0;
    public static array $rows /** vector<Row> */ = [];
    public static array $by_name /** hash<int> */ = [];
    public static ?Row $root = null;

    public static function reset(): void {
        self::$count = 0;
        $rows /** vector<Row> */ = [];
        self::$rows = $rows;
        self::$root = null;
    }
}
```

Supported initialized forms include bool/int/string literals, enum/named constants,
nullable fields with the existing allowed defaults, and explicitly annotated empty
vectors/hashes. Public/private/protected visibility is preserved; target analysis
and PHP own access validation. Static readonly fields remain rejected.

Literal local, qualified and fully qualified class names can select static fields.
Class-local self::$field and self::method() remain lexical self references in the
output; the converter does not resolve them. Reads, assignments, indexed writes,
append and foreach use the existing expression paths. Keyed isset and single
hash-slot unset also accept static field roots under their existing key rules.
Static field access is not an access to a PHP local with the same spelling.

Required fields without initializers are now covered by the
[required-field contract](required_fields.md). Arbitrary property initializer expressions, late-static
binding, parent access, dynamic class/member selection and calls through static
property values are outside this slice. Bare isset/unset of the static property
itself is not added. Static locals in functions are not class fields and remain
unsupported. Custom Storage/Keyed_Storage template binding is separate work.

## Empty static hashes

On pinned target 9b4b33f35f053b487e018c94d6a4a7888d77c64a, an explicit empty
static hash literal emits a capturing lambda at C++ namespace scope, which fails
compilation. For a nonnullable static hash with authored empty-array initializer,
the converter emits the typed PHS declaration without the initializer. Native hash
default construction supplies the same empty value. This is a local structural
lowering rule, not acceptance of uninitialized PHP input or type inference.
Nullable hashes retain their explicit null initializer. Instance hashes are unchanged.
No generated C++ or pinned target source is patched.

## Validation

`python3 tests/portability/static_properties.py --results FRESH`
checks PHP expected behavior, checking/conversion, incremental conversion and
source-attributed rejections. Add `--target-checkout TARGET` for native execution.
The validation driver includes the PHP proof and exposes `--native static`.

The proof uses two workers sharing static state, public/private/protected fields,
scalar/enum/nullable fields, vector/hash writes, iteration, keyed membership/removal,
self calls and reset preserving a previously retained record. Expected output is
specified independently. A converter pass alone is not native evidence.
See [recorded evidence](../planning/compiler_migration/results/static-properties-01/README.md).
This does not register new compiler-ready files or make the full Model convertible.
