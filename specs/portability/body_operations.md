# Exact checked operation selection
Doc Status: supporting

`type_model/Operation_Contract` retains an operation name, ordered canonical
operand IDs, a result ID and an exact `Implementation_Binding`. Binding kind,
provider and entry describe identity only; they do not advertise an implemented
capability. The constructor copies typed-vector membership. Read operands through
`size()`/`operand_at()`; no implicit conversion or representation-based equivalence
is introduced. Canonical IDs require the owning type lineage.

`check_bodies/Operation_Resolver::binary` reads the left type's authoritative
named definition and requires identical left/right IDs. It selects wrapping
addition only from `wrapping_addition`, and signed/unsigned less-than only from
`ordered_comparison` with an available boolean type ID. Selection does not mutate
the store or infer capabilities from integer storage width. Unknown operations,
different operand IDs and absent capabilities produce no contract.

The original descriptors lived inside type-model definitions. They now have a
focused `data/operations.php` owner; checked integer binding tags replace the
original enum. Public operand arrays become fixed copied membership with bounded
accessors. PHP callers must supply the annotated `vector<int>` shape; arbitrary
associative/mixed arrays are outside the portable authoring contract.

The prototype selection algorithm is retained. Input type-readiness and boolean
identity are supplied by the type-resolution owner; constructing a descriptor
alone is not semantic validation. Native instruction generation, overflow behavior,
conversions and expression evaluation belong to their later consumers.

The focused proof covers all 294 combinations of seven catalog types, three
operation requests and boolean availability. Results agree with independent
expectations and the preserved prototype. Additional checks cover operand-copy
isolation, binding identity, bounds rejection and unchanged store sizes.
