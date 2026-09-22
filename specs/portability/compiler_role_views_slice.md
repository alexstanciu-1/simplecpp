# Parser role-view portability slice
Doc Status: supporting

Eleven existing structural role records now live in
`compiler/src/03_parse/data/role_views.php`. Their declarations, including readonly
fields, final modifiers, constructor defaults and the optional reference kind,
are unchanged from adoption. The remaining `structures.php` owns the expression
context/cursor. Bootstrap loads nodes, role views and cursor declarations; callers
retain the same APIs. Six production files are now in the ready source set.

## Promotion has a local conversion owner

The selected target does not discover promoted properties: the direct promotion
probe fails STAN when reading the promoted field. The converter therefore expands
an empty constructor with explicit public readonly promoted parameters into
explicit properties, a normal constructor signature, and member assignments.
All facts are present in that declaration. No caller or field lookup is performed.

The bounded form supports scalar/named types, optional nullable types, scalar/null
defaults and trailing commas. Mutable/private promotions, arrays, references,
variadics, computed defaults and constructor bodies remain unsupported. Literal
class construction now accepts argument expressions. Final class spelling is
preserved; inheritance remains rejected by this converter.

Nullable promoted fields/parameters use the target's explicit `nullable<T>` doc
annotation form. Passing through PHP `?Enum` silently loses nullability in this
release, and generic types cannot simply be written in PHP type position. This
mapping is derived from the local `?T`, independent of resolving T.

## Readonly approximation is explicit

[The target preflight](../planning/compiler_migration/results/readonly-preflight-01/summary.json)
proves that v0.1.76 accepts an explicit readonly property but permits reassignment:
the probe prints 7 then 9. Native readonly enforcement is therefore **not** claimed.
The maintained PHP declarations remain readonly, and the PHP authoring/test loop
enforces them. Native consumers must follow the same initialization-only usage
contract. This follows the agreed intent-based portability model, not exact PHP
misuse/error compatibility. Native access-control redesign is not hidden inside
this slice. Generated nullable fields carry a comment documenting the contract
because their explicit target type annotation uses an untyped PHP-shaped field.

## Evidence

[roles-04](../planning/compiler_migration/results/roles-04/summary.json) records
strict v0.1.76 native execution and the identical PHP witness. All eleven records
are constructed and their scalar payloads read; defaults and explicit overrides
are checked. The optional enum is tested absent/present and extracted with
`take_nullable`, verifying its exact case. PHP readonly rejection is checked
separately; the target preflight records the deliberate enforcement difference.

Fifteen existing compiler fixtures and converter/prologue regressions pass.
Provenance checks preserve the extracted declaration bytes. Failed `roles-01`
and `roles-02` runs retain evidence for the target nullable-spelling limitations;
`roles-03` was the intermediate passing payload/presence proof before adding enum
extraction. Generated PHP++ is evidence, never another maintained implementation.

General constructors, mutable expression cursor containers, parser algorithms,
native readonly enforcement and arbitrary binary literals remain future work.
