# Bundled language definitions
Doc Status: supporting

`named_types.json` supplies the initial named return types and their language
value representations. Consumers resolve these definitions through `load_runtime`
and `resolve_types`; type names must not select behavior elsewhere in the compiler.
`literal_types.integer` selects the shared default for decimal integer literals.
The agreed fractional-literal default is `float`; fractional syntax remains a
later slice. [Literal and conversion rules](../docs/details/body_checking.md)
describe the implemented boundary and deferred conversion metadata.
`entry_return_type` independently supplies the manifest entry's language return
definition. It is not a native process-exit type or an implicit cast rule.

Every type explicitly supplies `lifetime`: scalars declare
`{"copy": "value", "cleanup": "none"}`, while void supplies `null` because it has
no value. These shared rules describe copying the scalar value and requiring no
cleanup action. Missing or unsupported rules are rejected, never inferred from
names or representations. The [lifetime stage](../docs/details/lifetime_analysis.md) consumes these facts
and records reachable scalar temporaries separately.

[Provenance, scope and verification](../docs/details/return_type_resolution.md)
explain the current subset. Layout, ABI and executable actions are not defined here.
