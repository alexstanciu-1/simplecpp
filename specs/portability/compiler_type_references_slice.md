# Semantic type-reference records
Doc Status: supporting

`type_model/data/type_references.php` preserves the existing marker interface and
four concrete declaration-reference forms: language name/namespace, provider/id,
generic owner/slot, and family/ordered arguments. No reference is resolved and no
new validation rule is introduced. These are unresolved semantic contracts, not
layout records or concrete type-store IDs.

The family constructor now explicitly annotates its existing PHP array as
`vector<type_reference>`. The collection can contain the different implementing
classes and nested family applications. Membership is copied; referenced records
keep shared identity. Callers must supply dense lists and keep the readonly
contracts unchanged. PHP readonly does not imply a native immutable object graph.

## Explicit named locals

Local declarations now accept a literal unqualified, qualified or fully qualified
named type, for example:

```php
$anchor /** \type_model\type_reference */ = $named_reference;
$arguments /** vector<\type_model\type_reference> */ = [$anchor];
```

`Converter::localAnnotation` owns this lexical parsing alongside the existing
scalar/wrapper/container spellings. The converter neither finds the declaration
nor checks assignment compatibility. An unknown literal name is passed through;
the native target must resolve and validate it. Dynamic/mixed/array pseudo-types,
self/parent/static, unions, arbitrary generics and named wrapper payloads remain
rejected. Existing scalar wrapper restrictions remain intact.

This does not implement downcasting, variant payload access or type-reference
resolution. Those consumers still need their own adaptation and proof.

## Validation

The cumulative fixture independently checks exact scalar fields, heterogeneous
argument order, repeated-reference identity, nested family identity and empty
arguments. Replacing entries in the original list or a copied field list must not
change the family's membership. The retained semantic-call contract fixture checks
that these records still feed the adopted semantic/ABI model.

The foundation harness checks literal named annotation pass-through without remote
resolution, alongside rejection of unsupported local forms. Evidence and the
selected immutable target are recorded with the cumulative migration results.

Evidence: `specs/planning/compiler_migration/results/type-references-01/summary.json`.
PHP/native expectations, strict build/STAN, converter regressions and eighteen
retained compiler fixtures pass on `2f0d667f38a35ff02ef77e813f409189cba2d032`.
Thirty-two production files are ready; the full semantic model is not yet migrated.
