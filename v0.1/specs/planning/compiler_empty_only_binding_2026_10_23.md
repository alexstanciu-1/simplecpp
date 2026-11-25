# Explicit empty-only provider initialization
Doc Status: planning

## Scope selected by the user

Use an explicit empty-only binding backed by existing default-construction
machinery for CONVERT-01. Nonempty provider literals remain outside this slice.
The bounded implementation now follows this design; validation evidence is recorded
in the [execution ledger](compiler_portability_execution_2026_10_23.md).

Runtime type metadata:

```json
{
  "construction": "make",
  "default_constructible": true,
  "empty_initialization": "default"
}
```

`empty_initialization` is an explicit permission for typed `[]`, including a
contextual parameter default. Neither default constructibility, indexing nor
iteration alone grants it. The initial provider contract requires an existing
registered construction factory callable with zero supplied arguments; its normal
optional-argument defaults are allowed. The resolved result must be the exact
target type. Do not infer permission from the Storage name or template arguments.

An empty-only registration and sequence initialization recipes on the same type
are rejected as conflicting registrations in this bounded design. Existing
sequence providers retain their established empty-input factory behavior. A later
need for explicit precedence must be discussed rather than silently choosing one.

## Reuse and ownership

- Runtime catalog reader/recipe and type registry own the registration and its
  validation. Keep it separate from `initialization_declaration`, whose required
  value/key/nested types truthfully describe a sequence input, not an empty value.
- Shared `Data_Construction_Preparation` selects the registered empty route only
  for zero entries with bracket notation. Nonempty positional/keyed inputs and
  empty brace notation do not acquire permission from this registration.
- Extract the existing runtime construction-factory resolution from
  `Object_Construction_Preparation` into a shared local helper. Reuse
  `Runtime_Call_Preparation`, exact result checking and `prepared_object_construction`;
  do not synthesize source AST or duplicate callable admission/default processing.
- Add one `prepared_data_construction` variant holding the settled
  `prepared_object_construction`. The variant is the small new retained fact;
  existing sequence facts keep their required fields and meaning.
- Parameter-default admission uses the same registered initialization selection,
  then existing property-default facts and call-local materialization. Copy
  capabilities remain checked. Each omission constructs a fresh value; explicit
  arguments do not evaluate the default.
- C++ data construction delegates this variant to existing object construction
  emission with no supplied arguments. The runtime factory already uses
  `CPP_Declarations::selected_call`; ordinary and template-prelowered calls must
  follow that same path. Do not misuse the sequence `initialize_empty` operation,
  which means a factory call with an empty initializer input.

This is a bounded extension of registered initialization, not a new container
element protocol or a general default-constructible-type literal rule. Source
record initialization and nullable wrapping retain their separate contracts.

## Required proof before closing this slice

1. An unfamiliar registered provider and the Storage family accept contextual
   and explicitly typed empty brackets in ordinary and prelowered output.
2. Parameter omission invokes construction once per call; an explicit argument
   does not invoke the default. Preserve factory/default effects and fresh state.
3. Missing registration, conflicting recipes, unusable zero-argument construction,
   and nonempty inputs reject during catalog loading or shared preparation as
   appropriate, with a useful diagnostic. No Storage-name exception.
4. Existing sequence-factory defaults keep their behavior. Retained invalidation
   and recovery use the existing configuration-reset/provider dependency protocol.
5. Retry the measured PHP-hosted bootstrap after implementation; record wall time,
   peak RSS/heap and the next blocker. Do not claim legacy-route Storage literal
   support or native compiler rebuilding from a v0.2 fixture alone.

Converter nullable-annotation work (CONVERT-02) is independent of this binding.
