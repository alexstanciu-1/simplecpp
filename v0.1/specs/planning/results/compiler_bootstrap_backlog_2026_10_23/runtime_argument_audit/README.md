# Runtime argument boundary audit — 2026-10-25
Doc Status: planning

No production fixes in this audit. Latest completed bootstrap is nullable_enum_ownership:
first diagnostic runtime-call signature matching, 16 categories total.

Existing Runtime_Call_Preparation selects exact generic bindings. Its checked-payload
fallback only handles registered_function, not generic recipes. An owner already
binds Storage<T>'s T; a Derived argument cannot rebind that identity to Base. A null
guard also does not refine a nullable argument's static type. Ordinary typed-local
initialization already owns safe upcasts and checked nullable extraction.

Source candidates within the five owners identified by the preceding partial audit:
- CPP_Generator::generate_program appends a type_contract_work view to Storage<preparation_owner>.
- Runtime_Callables::register_function/register_generic insert concrete registered
  function types into Keyed_Storage<runtime_function_definition>.
- CPP_Initializer_Buffer::reference appends guarded ?canonical_type_use into
  Storage<canonical_type_use>.
- Callable_Instances::obtain appends callable_declaration_node into Storage<declaration_node>.
These are source-inspected candidate expressions, not fresh expression-located
whole-bootstrap diagnostics. A later error in a method may still be hidden.

Six minimal probes, each in ordinary and concrete v0.2 output mode, establish:
derived append, derived add, and guarded nullable append reject; corresponding
explicit typed local versions prepare and emit successfully. results.json records
all 12 outcomes. This audit does not claim native execution or full-site repair.
The first harness attempt configured runtime metadata repeatedly and failed; configuring
once before per-case reset corrected the audit harness. No compiler change was made.

Recommendation: source-only typed locals at these actual boundaries, preserving
object identity, insertion order and existing null guards. Do not copy containers,
change runtime metadata, add implicit generic conversion ranking, or narrow the
container's truthful common-base element type. Extend behavioral native proofs in
v0.1 and both v0.2 modes, then retry measured bootstrap after approval.
