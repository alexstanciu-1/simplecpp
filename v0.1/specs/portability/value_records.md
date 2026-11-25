# Scalar value records and explicit local aliases
Doc Status: supporting

Executable PHP is the authoring surface. The converter recognizes explicit storage
intent; it does not resolve types, prove lifetimes or optimize arbitrary PHP objects.
This slice follows the [native-aware work list](../planning/compiler_migration/native_aware_portable_php_plan.md).

## Record declaration

```php
/** @scpp-struct */
final class Source_Span {
    public int $offset /** uint32 */ = 0;
    public int $length /** uint32 */ = 0;
}
```

This emits a native `struct Source_Span` with two inline uint32 fields. Ordinary
unmarked classes retain shared identity. The standalone marker immediately precedes
a top-level final class. The initial record grammar admits only public initialized
fields: `int` with an adjacent `uint32` comment, or `bool`. Integer defaults must
be decimal literals in 0..4294967295. Methods, constructors, inheritance, interfaces,
nonpublic/static/readonly fields and other field types are outside this grammar.
Target structs support more types, but those types need PHP copying proofs first.

Use no-argument construction and explicit field initialization. PHP does not enforce
the annotated uint32 range during later assignments or arithmetic. Authors must
establish bounds; native numeric boundary tests remain necessary. For the selected
target, returning a uint32 field from an `int` method requires an explicit `(int)`
conversion. Do not infer full fixed-width normalization from PHP success.

## Three distinct operations

Fresh construction creates a new PHP object/native value. For an independent copy,
use an explicit operation on the record's owner:

```php
class Source_Spans {
    public static function copy(Source_Span $source): Source_Span {
        $copy = new Source_Span();
        $copy->offset = $source->offset;
        $copy->length = $source->length;
        return $copy;
    }
}
```

This is ordinary supported code, not a generic converter copy special case. Keep
its fields synchronized with the record. No new PHP runtime helper is needed.

Mutable local aliases have two accepted first-declaration spellings:

```php
$second = /** &ref */ $first;
$third /** &ref Source_Span */ = $first;
```

They lower respectively to `$second = &$first;` and
`$third ref Source_Span = &$first;` in PHS. PHP already shares the record object;
native uses a reference to the existing local value. Field mutations through either
name are visible through the original. No type lookup is needed for either spelling.

The initializer must be one distinct simple local variable, not `$this`, a call,
temporary, property or indexed element. The binding is a standalone declaration,
not an assignment inside another expression. Directly seen repeated destination
names and direct assignment/redeclaration of either borrowed variable are rejected
within the callable/file lexical scope. That conservative bookkeeping spans nested
blocks; it is not control-flow or lifetime analysis.

Writer obligations beyond those checks:

- Use these aliases only for known value records; the converter does not establish
  the type of an untyped source or resolve a named type to its declaration.
- Keep aliases within the source local's lifetime. Do not rebind, escape, capture,
  return or store them as long-lived aliases, including indirect mutation of the
  binding. Avoid aliasing parameters in this initial authoring contract.
- Do not use object identity as record equality. Compare relevant fields.
- Treat ordinary record reads/arguments as observational: PHP may share the object
  while native copies a value. Mutating a by-value parameter is not caller mutation.
- Return a fresh/copied record when the caller may mutate it. Use an explicit
  `&ref` only for the supported local alias case; bare object assignment does not
  promise a cross-target independent copy or mutable alias.

These are PHP/native agreement rules, not complete PHP reference semantics. The
converter does not certify indirect aliasing or arbitrary lifetime/rebinding safety.

## Records in vectors

PHP array insertion retains an object handle; native vector insertion stores a value.
Copy explicitly at boundaries where later mutation must be independent:

```php
$rows /** vector<Source_Span> */ = [];
$rows[] = Source_Spans::copy($first);
$old = Source_Spans::copy($rows[0]);
$replacement = Source_Spans::copy($rows[0]);
$replacement->length = 20;
$rows[0] = Source_Spans::copy($replacement);
```

Ordinary reads are suitable while neither reader nor another owner mutates the
shared PHP record. Take an explicit copy for retained snapshots. Native references
into vector/hash storage remain forbidden by the current target safety contract.
No `&ref` container-element workaround or general deep-copy operation is supplied.

## Native-aware storage choices

- Use compact records for numerous small values; classes for identity-bearing stores,
  workers and shared owners. A class with named fields is not automatically inline.
- Use dense zero-based vectors for indexed storage and hashes for sparse/arbitrary-key
  lookup. `hash<V,K>` spells value then key; omitted keys default to string. Current
  converter key families are int and string. Check presence before consuming missing
  entries; do not assume PHP numeric-string key coercion matches a typed map.
- A vector owning records plus a name-to-ID hash can avoid duplicate payloads. Define
  stable ID versus storage position, missing sentinel and deletion/compaction policy.
- Prefer source offsets/lengths over repeated token text where useful. Choose narrow
  widths from real bounds. Interning and more elaborate layouts need measured need.
- PHP tests establish behavior; native layout/allocation measurements establish
  storage costs. Do not estimate native memory savings from PHP memory consumption.

## Proof and boundaries

`python3 tests/portability/value_records.py --results FRESH --target-checkout TARGET`
checks explicit copy/alias effects, vector replacement, retained values, uint32 max,
method-local aliasing, incremental reuse/record changes and source-attributed
rejections without output publication. A separate target-only layout project
observes struct size/alignment/field width without modifying converted output.

The fast driver includes PHP checks; select `--native records` for native proof.
The fixture is not an ABI guarantee or allocation benchmark. Nested records, string/
container fields, general clone, alias parameters/returns and in-place vector-element
borrowing are not added. Existing compiler records are not automatically changed
or promoted to compact storage by this slice.
