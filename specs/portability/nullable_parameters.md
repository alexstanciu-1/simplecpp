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

Only null defaults are added for ordinary methods. Implicitly nullable `T $x = null`,
non-null defaults, nullable containers, mixed/object, unions, by-reference/variadic
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
