# Old type/capability model: bounded assessment
Doc Status: supporting

Date: 2026-09-07. Read-only inspection of the old compiler's current working
tree, HEAD `9776900f6c305729ae6ef8474052363508913315`. No execution tests run.
This is evidence for the new mental model, not a full type-support audit.
The [current type model](../type_model.md) owns the adopted rules; this historical
assessment does not define additional implementation requirements.

The [provider/consumer protocol][protocol] is a useful plan: providers publish
contracts; features consume capabilities; adapters realize authorized behavior.
It explicitly avoids hidden feature-by-type branches and mandatory OOP dispatch.

There is real implementation behind that plan:

- [Type traits][traits], `row_from_type_ref_id()`, obtains traits from provider
  descriptors, including storage, cleanup, ABI, and numeric properties.
- [Type references][refs], `add_family_instance_with_type_args()`, looks up
  structurally matching instances, handles ID collisions, and stores argument
  rows. This is a reusable family mechanism.
- [Capability readiness][readiness],
  `coverage_from_type_refs_and_consumer_plan()`, consumes common provider and
  consumer records to assemble readiness coverage.

The implementation also preserves concrete `result<int>` and `nullable<int>`
identity branches and spelling rows in `type_refs.phs`. The [family boundary
plan][boundary] explicitly calls these stable-ID transition exceptions. It also
states that value-argument materialization and source container-family
acceptance remain blocked/planned. Having argument storage is not complete
family support.

Conclusion: retain canonical identities, derived family contracts, and generic
consumers. Do not inherit the transition exceptions or treat readiness coverage
as proof of executable support. As the [lowering investigation][lowering] shows,
resolved behavior must reach the backend through a complete execution contract.

[protocol]: ../../../simple_cpp_compiler/compiler/docs/core/provider_consumer_interface_protocol.md
[traits]: ../../../simple_cpp_compiler/compiler/src/compile/capabilities/type_traits.phs
[refs]: ../../../simple_cpp_compiler/compiler/src/compile/model/type_refs.phs
[readiness]: ../../../simple_cpp_compiler/compiler/src/compile/capabilities/type_capability_readiness.phs
[boundary]: ../../../simple_cpp_compiler/compiler/docs/future/generic_families_boundary_plan_2026_08_03.md
[lowering]: resolved_to_llvm_development_slowdown.md
