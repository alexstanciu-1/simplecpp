# Shared declaration model
Doc Status: planning

Agreement: 2026-10-07. The user approved the following model and a bounded migration
of existing behavior, not implementation of every class/interface/trait feature.

## Agreed shape

- One final `type_declaration_node`, distinguished by `type_declaration_kind`:
  class, struct, interface or trait. Abstract/final/etc. are authored modifiers,
  not additional declaration kinds. Preparation owns legality and implicit defaults.
- Four typed owning collections: `properties`, `methods`, `constants`, `use_traits`.
  They preserve order within each category; there is no persistent combined member
  collection. Source spans preserve cross-category positions for future inspection.
- Separate property, method and constant naming categories. Identical spelling is
  permitted across categories; duplicate admission is category-specific. Static is
  a modifier, not an additional naming namespace. Semantic names exclude `$`.
  Collected indexes reference declarations, never duplicate syntax. Future inherited
  and trait-composed member lookup belongs to preparation, not AST storage.
- `callable_declaration_node` is the shared abstract syntax owner for final
  `function_node` and `method_node`. Parameters, return syntax, reference-return
  marker, signature scope and optional `function_body_node` are shared. Methods
  add authored modifiers. No separate method-body implementation or synthetic
  authored `$this` parameter is introduced.
- A completed bodyless method differs from an incomplete parse. Return-type absence
  can represent constructor syntax; it does not relax ordinary-function admission.
- `constant_declaration_node` is the shared abstract for final
  `namespace_constant_node` and `member_constant_node`. Both own name, initializer
  and settled constant value; member constants add their modifiers. Expression
  evaluation stays in analysis and is reusable independently of declaration policy.
- `property_node` owns its name, modifiers, optional type syntax and optional
  initializer. Allowed combinations are a preparation policy, not AST inference.
- `trait_use_node` owns ordered named trait references. Each `use Trait_A, Trait_B;`
  is one entry in `use_traits`. Aliases, precedence and adaptation blocks are deferred
  to future versions. Unresolved composition conflicts must not silently pick a winner.
- Relationships remain explicit: optional class base, extended interface references,
  implemented interface references and trait-use declarations are not one parents list.
- Backend-owned member naming will separate categories when execution is added;
  source spelling and diagnostics remain unchanged. No C++ naming belongs in AST
  or collected semantic identities.

## Bounded migration implemented

The shared syntax lives in `03_parse/structures/structures_declarations.php`.
Existing struct parsing now creates `type_declaration_node` and `property_node`;
existing functions and namespace constants use their shared bases. Explicit `public`
on existing struct properties is retained rather than discarded. Lazy inspection
and neutral maintenance cover every new owning edge without flattening inventories.
Required-body/type queries preserve the old consumers' nonnullable boundaries.

Existing `collected_struct`, `collected_field` and `prepared_record` remain the
supported struct semantic path. Only that declaration kind may register through it;
preparation rejects newly modeled members/relationships/modifiers rather than ignoring
them. The common declaration's record fact slot is populated only on that bounded path;
future semantic families must not masquerade as records merely to reuse syntax.
Namespace constants retain `collected_constant` and their existing value protocol.
`Constant_Expression_Preparation` now owns the shared bounded evaluator; no binary
arithmetic or runtime calls were added to constant expressions.

Property/member-call/constant collection and effective lookup will be added with
their respective semantic slices. The existing scope's variable/function/constant
pools remain unchanged; no dormant second member-index implementation was created.
Cross-category collision admission and C++ name separation are agreed behavior,
not an execution claim from the syntax-only proof. Existing deferred duplicate-field
diagnostics are not silently marked complete by this migration.

## Non-goals and next boundary

This slice does not admit authored classes/interfaces/traits, methods, member
constants, constructors, destructors, trait composition, inheritance, interface
dispatch or new property-initializer forms. It does not change struct value storage,
copy rules or function behavior. The parked LLVM files receive mechanical syntax
name/accessor updates only; no LLVM functionality or native portability is claimed.

Before extending struct members/constructors, amend the first-slice restrictions in
`specs/compact_layout_types.md` explicitly. The agreed parity direction is not a
silent override of that normative contract. Generalize callable algorithms when
member preparation supplies a real receiver context; do not duplicate function logic.

Focused proof: `tests/declaration_model.py`, using the shared 12-slot runner. It
checks model ownership, independent cursors, bodyless completion, shared constant
facts, retained identities, unchanged emitted artifacts on source movement, admission
gates and existing PHP-host regressions. It does not compile generated C++ or rebuild
the compiler natively. CH10-PERF-01 remains required before the chapter's native build.
