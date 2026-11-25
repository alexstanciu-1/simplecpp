# Project resolution acceptance and reuse
Doc Status: supporting

`Resolution_Plan` selects current source owners whose accepted result is absent or
whose source/declaration dependencies changed. Full mode selects every owner.
`Resolution_Worker` remains the same independent worker. `Resolution_Join` accepts
one result per selected owner in any arrival order, rejects incomplete/duplicate/
unselected batches, and emits current source membership in store order. Removed
owners disappear. No failed attempt publishes a partial candidate.

`Resolution_Set::publish` is the only operation that populates the set's private
rows. It validates complete membership and current dependencies, then structurally
checks fresh results. Results shared from a previously accepted set skip that AST
walk. Empty construction creates a baseline, never a way to inject unchecked rows.
`Symbol_Resolver::resolve` composes these owners synchronously. Generic Step/session
integration and production debug serialization remain later integration work.

Dependency checks preserve exact source ownership, exact catalog definitions,
current global lookup targets and exact template definition snapshots. A plain
function-body edit preserves callers' name bindings while a template definition
edit invalidates applications. Type/ABI/body eligibility is not established here.
Metadata-only frontend rebinding currently re-resolves that file's owners to retain
current diagnostics; sharing facts under a new owner is a possible later optimization.

Two negative lookup dependencies are explicit: a newly added constant can make an
existing function call invalid, and a newly provided type can conflict with a source
struct. Retaining a surviving function ID or unchanged struct body is insufficient.

`Binding_Coverage` walks fresh syntax iteratively. Separate typed claim indexes and
consumption counts replace heterogeneous maps/unset. It requires all free names,
applications, members, declarations, scopes and variable uses exactly once. It also
checks nearest lexical locals, read/write access, self-initialization guards, scoped
constants and ordered template-parameter visibility. Value-parameter annotations
precede publication of that parameter name. Field/member names remain type-owned.
This is structural acceptance, not concrete type checking or template instantiation.

Proofs include clean/warm/full agreement, arbitrary worker order, deletion, repair,
ordinary versus template edits, catalog replacement, shadowing dependencies, rejected
malformed worker outputs, and the lexical component's source failure/deep/template
fixtures. See [evidence and timings](../planning/compiler_migration/results/resolution-project-01/README.md).
