# Explicit nullable method parameters
Doc Status: supporting

Ordinary instance/static methods, direct expanded trait methods and declaration-only
interface methods accept `?T $value`, optionally followed by `= null`. T uses the
existing scalar or literal named type grammar, including interface names. The
converter emits `$value nullable<T>` and preserves the optional null default.
It does not resolve types or infer nullability from defaults.

```php
public function attach(?Row $row = null): void {
    $this->token = $row;
}
```

`?T` permits an absent value; `= null` permits omission of the argument. Without
the default, the caller must supply an argument, which may explicitly be null.
Optional parameters must follow required parameters. Present zero, false and empty
string remain distinct from absence. Use the established take_nullable helper when
a concrete scalar payload is needed; do not assume PHP automatic nullable narrowing.

Nullable parameters permit only null defaults. Ordinary nonnullable `bool` parameters
also accept matching `true`/`false` literal defaults. Implicitly nullable `T $x = null`,
other non-null defaults, nullable containers, mixed/object, unions, by-reference/variadic
parameters and arbitrary default expressions remain rejected. Constructor handling
keeps its existing contract; nullable callback parameters and nullable interface
returns are not added by this slice. General object-property unset is not added.

## Typed payload boundary

The pinned target does not combine payload conversion and nullable wrapping in
one implicit argument conversion. Passing a concrete implementing class directly
to `?Interface`, or an int directly to `?float`, fails native compilation. Establish
the payload type first: use an explicitly interface-typed local for the object, or
an existing float-returning operation for the numeric value. The proof exercises
both typed paths. The converter neither resolves the callee nor inserts casts.
This is a target boundary, not a reason to make the parameter nonnullable.

## Optional reference reset

A field whose absence is meaningful should declare that explicitly:

```php
public ?Row $token = null;
```

Reset it with `$owner->token = null`. Required fields do not become nullable merely
because they are assembled in stages. The small compiler's file.tokens convenience
backlink follows this optional contract; Model.reset_tokens clears every backlink
before beginning a scan, including files not reached after an earlier file fails.
Readers of an optional link must handle absence before dereferencing it.

## Proof

`python3 tests/portability/nullable_parameters.py --results FRESH` checks PHP behavior,
checking/conversion, incremental conversion, and source-attributed rejection without
output publication. Add `--target-checkout TARGET` for strict native execution.
The validation driver exposes `--native nullable-parameters` and runs the PHP proof
in its fast loop. The fixture covers interface/class/trait signatures, explicit
null, omitted arguments, present scalar values, object identity and null reset
without destroying a separately retained object.

Native evidence is recorded in
[the capability checkpoint](../planning/compiler_migration/results/nullable-parameters-01/README.md).
Compiler regression tests cover initial/null state, successful token publication,
failed scans and stage restarts. Helper proof does not mark the full compiler
convertible; required-field declarations have a separate proof; Storage bindings and per-worker
initialization review remain pending.

## Nullable extraction versus class narrowing

User decision, 2026-09-29 (compiler structure review): retain nullable prepared facts
when absence represents the period before preparation or after cleanup. Do not
allocate an empty facts record solely to avoid nullable extraction. This decision
is separate from the staged construction of required syntax fields.

When the payload already has the declared return type, the agreed accessor is:

```php
private ?prepared_integer_literal $prepared_facts = null;

public function require_preparation(): prepared_integer_literal
{
    return $this->prepared_facts;
}
```

The required return boundary expresses “return the present value or fail.” There
is no class narrowing here. Host PHP rejects null with a TypeError. The current
native `nullable<T>::operator T()` calls checked `require_value()` and throws on
an empty wrapper for copy-constructible T (including shared record handles).
These preserve present object identity but do not promise identical exception
classes/messages across PHP and native execution. This is evidence of the current
runtime bridge, not a blanket promise of nullable flow narrowing or arbitrary
payload conversion.

Keep the three operations distinct:

- `object_cast($value, Target::class)` checks/narrows the object's class. Keep it
  where the static payload is a base class/interface/object and a concrete target
  is required. Do not use it merely to spell a same-type null check.
- `take_nullable($out, $value)` is the existing portable-PHP branching helper;
  it lowers to `take(...)`, returns false on null, and preserves false/zero/empty
  payloads as present. Its established portability proofs have a bounded scope.
- `require_non_null($value)` is the agreed name for an explicit value-or-failure
  helper when a suitable required typed boundary is absent. It is not registered
  or implemented by this documentation change. Reuse checked extraction internally
  if useful; do not expose a class-target argument for a null-only operation or
  advertise a generic typed PHP helper without converter/type support.

### Tooling status and follow-through

The direct accessor above is the chosen source shape for the AST proposal, not a
completed native proof. Current STAN can diagnose an unchecked nullable return;
reconcile that analysis with the checked typed-boundary behavior before claiming
end-to-end support. Do not silently reintroduce object_cast, dummy initialization,
nullable weakening or a STAN bypass to make this example pass. Report a bounded
converter/STAN limitation separately from the source/lifecycle decision.

Before applying this throughout production code, prove present identity, absent
failure and the normal STAN-enabled converted build. Do not remove genuine class
narrowing or alter callers that require a specific failure category. Native compiler
validation remains opt-in under the project's operating rules.

Implementation references: `runtime/include/scpp/nullable.hpp` (`operator T`,
`require_value`), `tools/php_portability/function_map.php` and
`tools/php_portability/runtime/bootstrap.php` (`take_nullable`).
