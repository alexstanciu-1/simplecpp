# Native-only weak object fields
Doc Status: supporting

PHP retains ordinary references. A named object field can opt into the runtime's
existing weak handle representation using an adjacent annotation:

```php
public scope $scope /** weak<scope> */;
public ?scope $parent /** weak<scope> */ = null;
```

Both emit `weak<scope>` (native `weak_p<scope>`). A weak handle already has an empty
state; it is not wrapped in `nullable`. A required link must be assigned before
publication; its native target can subsequently expire. Optional PHP fields require
an explicit null initializer. Scalars, mismatching element types, nested type
arguments and other initializers are rejected in this slice.

`weakref_get($field)` maps to the existing native locking primitive. PHP's facade
returns its argument unchanged. Native expiration yields an empty shared handle;
required consumers use `object_cast(..., scope::class)` to reject that state.
Strong source owners must remain alive for as long as the algorithm needs them.
No automatic member-chain rewriting, weak collections or general lifetime analysis
is added. `@reference.weak` is descriptive; only the adjacent type annotation
selects this representation.

Runtime authority: `runtime/specs/catalog.md` weakref_get contract and
`runtime/include/scpp/weak_p.hpp`. STAN recognizes the builtin name; this does not
add dependent generic return-type inference to STAN.

`php tests/portability/weak_fields.php` checks PHP identity, rejected declarations,
the three compiler scope annotations, STAN registration and generated C++ spelling.
The compiler's native build now passes all 142 PHP/native comparisons, including
48 valid emitted programs compiled and executed. See
[weak-scope native evidence](../planning/compiler_migration/results/weak-scope-native-01/README.md).
This exercises live-owner access, not standalone expiration behavior.
