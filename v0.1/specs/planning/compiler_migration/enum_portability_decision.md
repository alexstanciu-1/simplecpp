# Semantic enum portability decision
Doc Status: planning

Status: staged typed-tag/codec recommendation accepted by the user on 2026-09-22.
Production enum adaptation and its behavioral proofs remain outstanding. The audit
itself changed no production declaration or consumer.

## Evidence and ownership

The token-based declaration inventory covers `compiler/src` and
`compiler/src-runtime-preparation`: 54 enums total, including 41 string-backed enums
with 167 cases in 17 files. Four enums own seven methods: generic_contract,
lifecycle_operation_kind, argument_passing and source_export_role. The companion
text searches find 19 factory/enumeration sites and 43 candidate method-call sites;
these are textual candidates, not resolved consumer counts.

Reproduce from the repository root with:

```sh
php specs/planning/compiler_migration/results/enum-shape-audit-01/audit.php
```

The saved inventory records each declaration's source hash, cases and methods.
The selected target `2f0d667f` explicitly excludes string-backed enums, enum methods,
name/value pseudo-properties and synthesized cases/from/tryFrom in
`generators/php/specs/unsupported.md`. This is target scope, not merely a restriction
in our portability converter.

These strings are not all display labels. Examples:

- Type_Store uses floating_format values and parameter passing modes in intern keys.
- Runtime package import parses ABI, ownership and operation vocabulary from strings.
- Source export roles carry versioned protocol semantics consumed by runtime preparation.
- Lifetime/body/LLVM inspection exports contain enums, sometimes via nested JSON
  serialization rather than a directly visible `->value` access.
- `flow_end::return_exit` has the external spelling `return`; case names cannot be
  substituted for string values mechanically.

## Recommended contract

Keep typed unit/integer enums as internal tags. At the owning vocabulary, author
explicit companion operations for the capabilities its callers actually need:

```php
// Proposed source shape, not implemented by this audit.
enum allocation_effect_kind { case acquire; case release; /* remaining cases */ }
final class Allocation_Effect_Kinds {
    public static function wire(allocation_effect_kind $kind): string { /* exact old spelling */ }
    public static function try_parse(string $text): ?allocation_effect_kind { /* exact match or null */ }
}
```

Move existing enum behavior into the corresponding typed companion owner, e.g.
`Argument_Passing::is_borrow($passing)` and
`Lifecycle_Operations::composition($kind, $custom_body)`. Retain algorithms and
ownership distinctions; do not distribute enum-specific logic among callers.
Provide an explicit ordered `all()` only where enumeration is used. Do not introduce
reflection, runtime dictionaries or converter-side inference of receiver types.
No general enum-conversion framework is proposed.

String values, parse rejection behavior, enumeration order, keys and externally
visible schemas must remain exact. A native integer tag is not a replacement wire
value. Do not turn original `from()` failures into silent null/default cases:
inspect the boundary and preserve its observed exception/error contract. If a
required exception kind is unavailable, record that blocker before adopting the
family. PHP readonly/memory behavior is a separate concern.

## Staged scope and validation

The shared strategy is approved. Migrate one vocabulary family and all affected
consumers together, starting with resource/allocation effects. Only claim readiness
for whole files whose dependencies and behavior are proved. Do not convert all 41
enums in an unvalidated mechanical batch.

Affected ownership areas include type-model records and operations, runtime package
loading, semantic checking and lifetime analysis, backend preparation/lowering/LLVM
exports, runtime preparation, and their contract tests. The model change is that
string encoding and enum behavior become explicit owned operations instead of PHP
synthesized enum facilities. This unlocks the next semantic-record dependencies
without making the converter a resolving compiler.

For each family, freeze the original value/case-order/behavior expectations; audit
concrete consumers including implicit JSON serialization; adapt only that family's
consumers; prove PHP/native encoding, parsing, equality and operations; run affected
retained compiler/runtime-preparation fixtures and consolidate. Costs and risks are
consumer discovery, hidden JSON encoding, exact failure behavior and protocol/key
drift. A broader inseparable redesign must still be reported before proceeding.

Non-goals: new enum semantics, altered wire formats, removal of future backend
paths, untyped string tags throughout the algorithms, complete standard-PHP enum
compatibility, or changes to the v0.1 generator/runtime in this workspace.

## Alternative

Request native string-backed enum/method/pseudo-property/factory support on the
v0.1 line and wait for an immutable tested candidate. That retains more original
source syntax, but is a materially larger target feature and would still require
bounded converter support and proofs. No such issue is filed by this audit.

The separate typed-cursor recommendation was also accepted on 2026-09-22.
The user explicitly authorized the staged cross-owner enum strategy described here;
a broader inseparable redesign still requires reporting and confirmation under
AGENTS.md. Production adaptation has not begun.
