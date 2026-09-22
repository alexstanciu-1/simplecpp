# Shared type model ownership
Doc Status: supporting

This is a contract and dataset owner, not a compiler phase. It does not import
metadata, interpret syntax, select work, run tools or call its producers.

```text
Language_Types; Package_Adapter                 ../../01_prepare_inputs/load_runtime/
  -> named definitions / Type_Catalog           data/definitions.php; data/catalog.php
  -> semantic call signatures                  data/semantic_calls.php; data/type_references.php
  -> paired measured ABI contracts             data/callables.php
  -> resource obligations / allocation effects  data/resources.php
  -> imported/composed lifecycle contracts      data/lifecycle.php
  -> normalized record declarations            data/records.php

Record_Preparation; resolution workers          ../resolve_types/
  -> private definition requests
Record_Join; Signature_Join; Local_Type_Join
  -> materializers -> private Type_Store         data/store.php
  -> canonical rows / flat member ranges        data/representations.php

Type_Resolution -> checking / lifetime / backend consumers
  -> [action] read accepted shared definitions and canonical storage
```

Type_Catalog owns exact named input lookup. Type_Store owns canonical IDs within
one retained lineage, representation interning and flat member ranges. Resolution
owns work selection and materialization; the model owns storage invariants.
Source and provider definitions share the same model. Preparation/JSON classes
and syntax classes are not dependencies of these contracts. `compile` contributes
only the common result/store marker interfaces.

Imported callable signatures use `named_type_reference` (exact name/namespace) for
all parameter and result types. Scalar, opaque and record references resolve in the
same fixed definition view after record acceptance; only then do callable signatures
carry canonical type IDs. References have no representation or target facts.

`named_type_definition::resource_paths` shares static owned-leaf paths composed by
record resolution. Source operation transitions remain lifetime-analysis results.
`array_type_definition` rejects resource-bearing elements until dynamic subobject
ownership is supported; producer-neutral inputs cannot silently lose obligations.

`lifecycle_operation_kind::composition()` owns source field roles and body/member
ordering as a compact `lifecycle_order`. It also exposes has_source() and
creates_destination() for consumers with distinct ABI or ownership responsibilities.
`source_lifecycle_operation` validates its members against that contract and retains
the order. `lifetime_contract::operation()` selects the implementation for a role;
it does not infer availability from whether an operation is present.

`generic_contract::copyable_value` in data/generic.php is the default source
type-slot contract. `Generic_Contracts::missing()` queries accepted copy, assignment
and cleanup facts; instantiation workers/joins own diagnostics and acceptance.
It does not infer eligibility from storage/category names or manufacture operations.

Family declarations live in `data/families.php`, independently of C++ bindings and ABI.
`Family_Contracts::validate()` checks formal owner/slot references, supported generic
requirements and distinct element-overlap/invalidation facts. Both preparation and
`load_runtime\Family_Adapter` use it. Source `check_templates` terms retain their
source provenance and constant/array state; they are not the provider protocol.
`dependent_value` is an unresolved family result; concrete callable ABI association
rejects it. Binding a formal result must establish scalar-value versus owned-object
production before ordinary compiler consumption.

`data/source_families.php` contains `family_declaration` and `family_method`: source
exposure payloads retaining the original semantic definition/operation and explicit
provider primitive mappings. `family_definition::language_type` and
`family_operation::expose_as` carry optional exposure records, independent of exact
native identity. These declarations have neither measured layouts nor prepared ABI.

`Type_Store::intern_signature()` calls `Result_Contracts::production()` to retain
concrete result ownership beside types and parameter passing. This is independent of
provider ABI. `lifetime_contract::expiring` retains value construction, legal copy
fallback, an accepted `move_construct` operation or missing support. No trait alone
authorizes an operation or promises that the source becomes empty.
