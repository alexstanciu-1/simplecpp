# Static-member prepared-facts portability audit
Doc Status: planning

Status: proposal only; user requested audit and proposal before implementation.
Baseline: 7bccf89df. No source-language contract changes proposed.

## Finding

The measured bootstrap first reports `Object narrowing requires a polymorphic
source`. The audited `static_member_reference_node::enum_case_preparation()` casts
its `?prepared_expression` field to `prepared_enum_case_reference`.
`prepared_expression` has data and nonvirtual queries, no polymorphic contract.
Its abstract PHP spelling does not supply one. The derived
`prepared_constant_expression::value_key()` is abstract, but that does not make
its own base polymorphic. v0.2 correctly rejects the cast; the native v0.1 checked
cast has the same underlying C++ prerequisite. PHP execution alone hides it.

Authority: specs/inheritance_v0_2.md checked shared-object access;
specs/portability/object_casts.md explicitly recommends preserving concrete facts
instead of adding dummy virtual behavior to prepared_expression.

## Actual flow and reuse

- `03_parse/structures/structures_enum.php`: static-member syntax owns one optional
  generic fact through Preparation_Facts; its enum accessor downcasts.
- `04_analyze/prepare/semantics/members.php::reference`: one caller, in expression
  preparation. It constructs exactly three fact families: prepared_enum_case_reference,
  prepared_constant_reference, prepared_static_property. Runtime constants and source
  constants already share the constant family; runtime/source properties share the
  property family. No new language member categories are needed.
- `semantics/expressions.php`: publishes the generic return through set_preparation;
  afterward its common epilogue obtains the attached fact and sets suspends. Retain
  that epilogue and the exact object identity.
- `semantics/defaults.php`, `selection.php`, `constants.php`: consumers recover enum
  or constant facts by narrowing the generic expression result.
- `semantics/expressions.php::prepare_field_write`: mixes field-access and static
  property facts, testing/downcasting the generic base. Existing field_access_node
  already has require_field_access_preparation(); reuse that typed syntax access.
- `05_backend/cpp/declarations.php::member_reference` and
  `generate.php` property assignment recover the same alternatives again.
- `prepare/cleanup.php`: neutral traversal calls node->clear_preparation(). Keep
  this protocol; it must clear every alternative, including retired syntax.
- Ordinary literal nodes already retain typed facts and offer required accessors.
  Four scalar literal branches in Constant_Expression_Preparation can reuse those
  after the same Expression_Preparation call, without any retained-model redesign.
- Casts whose static source is prepared_constant_expression/prepared_literal remain
  legitimate: those narrower families already have meaningful abstract operations.
  For example CPP_Declarations' literal-default emission does not need wholesale
  rewriting merely because it contains similar instanceof syntax.

## Recommended bounded representation

Keep ownership on static_member_reference_node. Replace its generic fact slot and
Preparation_Facts use with three private nullable typed slots:

```php
private ?prepared_enum_case_reference $enum_case_facts = null;
private ?prepared_constant_reference $constant_facts = null;
private ?prepared_static_property $property_facts = null;
```

This is an internal closed alternative, expressed with existing PHP/Simple C++
classes and nullable fields. Do not add a language union, runtime registry, or
universal fact visitor. Do not retain a duplicate generic fact slot or discriminator:
slot presence is sufficient for these three alternatives.

Invariant: unprepared means all null; prepared means exactly one present.
Typed setters accept a fully built required fact, clear previous slots and attach
that same object. No callbacks or worker operations inside the setters. Explicit
required accessors throw on missing/wrong alternative; optional queries expose only
the corresponding type. Generic preparation()/require_preparation() return an
upcast of the active object. They never recreate it. clear_preparation() empties
all slots through the existing cleanup entrypoint.

Change Member_Preparation's single-caller entry to prepare/publish the selected
alternative while it still has its concrete type. Keep lookup, access checking,
qualification evaluation and construction order unchanged. It must complete all
checks/construction before publication. Expression_Preparation then uses its usual
common epilogue; it does not publish a second copy.

Consumers use syntax's typed queries after ordinary preparation. Enum selection
still validates selector compatibility and rejects non-enum constant labels.
Constant evaluation still rejects properties. Property writes still reject constants
and preserve writability, indexed-receiver, union-initialization and conversion
checks. Dispatch field writes by the already known syntax family, then use existing
field-access facts or new static-property facts. Backend reads settled alternatives
only; it must not rediscover member roles from tokens or invoke preparation.

## Alternatives and cost

- A separate three-way fact holder provides the same invariant but adds another
  allocation/identity and forwarding layer per static reference. No current need.
- A meaningful polymorphic query contract on prepared_expression could support
  broader generic consumers, but would change every expression family's model and
  native layout. Do not mark arbitrary queries virtual solely to obtain RTTI.
- Adding a kind tag while retaining only the generic pointer does not solve safe
  downcasting under existing rules. Unchecked casts are not an option.
- Narrowing the sole slot to enum facts is incorrect: constants and properties are
  real existing alternatives.

Estimated workload: medium coordinated source adaptation, roughly 3–5 times a
single typed-local repair including proofs. About eight existing source files,
not a compiler-wide type-system redesign. Main risk is stale fact alternatives or
changed qualifier/preparation order. Nullable handles increase node storage; the
proposal avoids an additional heap allocation and duplicate generic handle.

This is a new retained representation spanning syntax, analysis publication and
backend consumers, so compiler/my-try/AGENTS.md's retained-protocol/cross-owner rule
requires agreement before implementation. It changes compiler implementation data,
not Simple C++ cast, nullability, inheritance or ownership semantics.

## Required proof before acceptance

1. Direct publication of each alternative returns the exact same object through
   generic and concrete views. Generic epilogue changes remain visible concretely.
2. Replace enum -> property -> constant; wrong-alternative reads reject, old slots
   disappear, clear and reprepare leave no stale selection. Retained external handles
   remain valid; no assertions about PHP/native cleanup timing.
3. Execute portable fixtures on PHP, v0.1 native and both v0.2 output modes.
4. Host enum/default/switch/match/constant/static-property and incremental tests;
   preserve expression-qualified static-member side effects and access errors.
5. Measured full bootstrap after implementation. Other generic prepared_expression
   narrowing sites may remain; do not claim universal resolution from this slice.

No implementation or new bootstrap was performed for this audit. Latest measured
run remains projected_locals: 175.38 s, 610504 KiB RSS, 578.5 MiB PHP heap,
19 diagnostic categories; native compiler and second-generation proof incomplete.
