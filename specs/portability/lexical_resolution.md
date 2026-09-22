# Lexical and body name resolution
Doc Status: supporting

`resolve_symbols\Resolution_Worker` binds one exact source symbol against a fixed
`Symbol_Store` and scalar `Type_Catalog`. It is one-shot. Removed/stale tasks are
contract errors; source failures return a `Resolution_Attempt` with the path, byte
start/length and reason. A failed attempt cannot expose a partial result.

The worker preserves the prototype's algorithm:

- Iterative statement/expression traversal; only lexical-block cursors retire maps.
  Both control-flow branches are bound, without evaluating their conditions.
- Root parameters (including an implicit method receiver) precede body locals.
  Nested locals shadow outer locals; sibling scopes do not leak. A declaration
  hides an outer local even in its own initializer, where self-reads are rejected.
- Template parameters precede scoped constants, which precede global lookup.
  Constants are visible only after declaration and reject self-initialization.
  Earlier template parameters are visible in later value-parameter annotations.
- Calls retain project symbol IDs; member calls retain receiver occurrences for
  concrete type checking. Index operands are visited in root-to-leaf order.
- Explicit template arguments are checked for count and type/value role. Applications
  retain the exact definition snapshot. No template deduction or instantiation occurs.

`Symbol_Resolution` privately owns typed fact vectors and lookup indexes. Numeric
scope/local/call/member/template-parameter/constant rows are inline native value
records, copied at publication and read boundaries. PHP identity is not an API for
these rows. Named catalog targets and template definitions retain shared identity.
Syntax and symbol inputs remain shared read-only. Scratch scope maps are typed
`Scope_Names` objects and disappear with the worker; no ad-hoc semantic payload arrays.

`type_model\Generic_Contracts` owns the default copyable-value template contract.
It permits copy construction, assignment and destruction, independently of later
specialization. Value parameters carry no generic contract. Declaration binding
records this policy; it does not prove a concrete type meets it.

Source namespace/provider-record support follows its actual input owners. Current
limitations retain the prototype's non-template else-if rejection, local-only method
receivers, no calls through constants/template parameters, and explicit template
arguments. These are not new converter restrictions.

The worker alone does not establish project-level selection, completeness acceptance,
or retained-result invalidation. Those are proved in the [project coordinator](resolution_project.md); local
result validation alone does not establish that a whole phase is complete.

Validation uses independent expected bindings and byte diagnostics, adapted retained
local/template cases, a deep traversal case, host mutation/invalid-result checks,
and the retained generic-permission oracle. See [measured proof](../planning/compiler_migration/results/lexical-resolution-01/README.md).
