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

Derive links explicitly: `$observer->target = weak($owner);`. The portable
`weak` facade preserves PHP object identity; conversion emits `weak(...)` for
both compiler routes. Its operand must have shared object type in PHP++; do not
pass a weak handle or an untyped null literal. Copy weak fields directly, and
clear them with direct null assignment.

`weakref_get($field)` converts to `lock($field)` for both routes. v0.1 uses the
runtime `scpp::lock` helper; v0.2 uses its existing typed ownership operation,
including template prelowering. This does not enable implicit shared-to-weak
conversion in v0.2.

`weakref_get($field)` acquires a shared handle from the native weak link. PHP's facade
returns its argument unchanged. Native expiration yields an empty shared handle;
required consumers use `object_cast(..., scope::class)` to reject that state.
Strong source owners must remain alive for as long as the algorithm needs them.
No automatic member-chain rewriting, weak collections or general lifetime analysis
is added. `@reference.weak` is descriptive; only the adjacent type annotation
selects this representation.

Runtime authority: `runtime/specs/catalog.md`, `runtime/include/scpp/memory.hpp`
and `runtime/include/scpp/weak_p.hpp`. The legacy STAN registration for
`weakref_get` remains; this slice does not extend STAN inference for `weak`/`lock`.
Native compiler-work proofs use the agreed `--no-stan` route.

`php tests/portability/weak_fields.php` checks PHP identity, rejected declarations,
the compiler scope annotations, STAN registration and generated C++ spelling.
The historical compiler native checkpoint passed 142 PHP/native comparisons, including
48 valid emitted programs compiled and executed. See
[weak-scope native evidence](../planning/compiler_migration/results/weak-scope-native-01/README.md).
This exercises live-owner access, not standalone expiration behavior.

File-root scope.publication is also native weak: compiler publication owns the
global index through Model, while file scopes retain their local declaration maps.

`python3 tests/portability/weak_operation.py --results FRESH` proves PHP/live-owner
parity, single evaluation, identity, weak copies and null clearing through v0.1
and ordinary v0.2 C++. Native-only checks additionally prove expiration after
all strong owners are released. Prelowered derivation, acquisition, identity and
clearing also pass a separate live-owner proof. The presence check is an expected
failure: strict-null comparison of a locked class emits `.has_value()` on a
concrete handle lacking that member. This backend defect remains unfixed.
PHP expiration parity is deliberately not claimed.
