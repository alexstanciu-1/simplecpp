# Variable visibility and shadowing policy

Doc Status: planning

Agreed 2026-11-04. `use` and explicit integer `global` declarations are implemented.
Ordinary top-level variables currently remain local to the implicit
entry function and are not accessible from other functions or other source units.
Keep that simpler implementation until the configurable policy is introduced.

Deferred work:

- Project/profile configuration for allowing same-source functions to access ordinary
  top-level variables. This must not implicitly publish them across source units.
- Variable-shadowing configuration: define which outer bindings can be shadowed and
  whether the selected policy permits or diagnoses it. Do not change current duplicate
  local diagnostics or introduce configuration keys before implementing enforcement.
- Globals currently support int64 literal initialization and reads, with visible
  locals taking precedence over unqualified globals. Defer writes, additional types,
  expression initialization and variable imports to separately reviewed slices.

Policy belongs in common preparation, shared by all flavors. When configuration is
implemented, include it in preparation cache validity: reused AST does not mean facts
prepared under a previous visibility policy remain valid. Imports and scope parent
links must not silently introduce variable captures or global publication.

See [the namespace contract](../../../specs/namespaces_v0_2.md) and
[current namespace/import ownership](../../compiler/docs/namespaces.md).
