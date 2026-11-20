# Bounded match lowering

Doc Status: supporting

See the [language contract](../../../specs/match_v0_2.md).

The parser owns one subject and an ordered syntax inventory of arms. An arm has
one optional label and one result; null label means default. There is no retained
label index, second prepared tree or runtime dispatch table. Signed labels reuse
the declaration-literal parser. Collection and preparation reset traverse all
children through the existing source-local paths.

Preparation resolves subject, labels and results once. A temporary label set and
one temporary default-arm reference diagnose duplicates. Named constants and enum cases record the
ordinary symbol dependencies. The result retains only the canonical value type;
it does not claim a constant result or cheap/reorderable evaluation.

Integer/enum C++ lowering emits a native switch. An identity-converted function return emits
that switch directly. Other expression contexts use an immediately invoked lambda
with an explicit value return type. Each case returns, so there is no fallthrough.
The switch performs single subject evaluation and preserves lazy arm selection.
No additional runtime API, metadata entry, ownership control block or AST cache
is introduced.

```cpp
// Source: return match (x) { 1 => 8, default => 9 };
switch (var_x_0) { case 1LL: return 8LL; default: return 9LL; }
```

`tests/match_native.py` exercises all three flavors with Clang/GCC, warnings as
errors, signed boundaries, integer and string-backed enum cases, selected checks,
string copies/embedded NUL, nesting, invalid programs and retained-versus-fresh
recovery. Its persistent Ninja builds check unchanged caller/native object reuse.
This does not establish a compile-time speedup or native parallel safety.

## Constant-label dispatch

Each published type can opt in using `compiler.value_types.*.constant_dispatch`.
The generator validates the strategy against the type representation and emits the
canonical type query. A nominal enum derives its case strategy from its settled
integer storage. Preparation checks exact type identity and uses the strategy to
obtain a temporary duplicate key. No occurrence stores a dispatch plan.

The byte-equality strategy compares the subject's length and literal bytes. Empty
labels need only the length test. A simple local is read directly; a computed subject
is evaluated once and its temporary stays alive during comparison. Named string
labels use the decoded bytes of their watched constant declaration. A default-only
match still evaluates its subject. The ordered condition form below is unaffected.

Source `switch` remains a separate statement slice. Its parser, break targets and
fallthrough flow analysis can consume the same constant-label capability.

## Ordered condition slice

Literal `true` selects conditions through `ast_match_expression::is_condition_subject`.
This derives the mode from immutable syntax, not a new retained field or a propagated
value. The parser admits ordinary condition expressions. Preparation requires exact
bool labels, with no duplicate-condition set entries; it still checks all results.

The fallback is prepared last even if written first. Calls in conditions can invalidate
local constant facts through reference parameters, and those invalidations must reach
the fallback's conversions. Earlier results are prepared immediately after their
condition. This reuses conservative invalidation without copying branch maps or adding
another preparation pass. As with logical expressions, revisit this model if conditional
expressions acquire fact-producing assignment effects.

`match_selection` emits successive `if (condition) { return result; }` statements and
a final fallback return. An identity-return context needs no match wrapper; value
contexts retain the existing explicit-result lambda. Conditions reuse generated bool
condition lowering. Existing wrappers inside a condition or result remain governed by
those expressions' own lowering contracts.

`tests/match_conditions_native.py` covers ordered effects, repeated conditions, default
placement, skipped failures, checked fallback conversion after reference mutation,
string copies/NUL, nesting and surrounding short-circuiting. It checks predicate
signature edits, deletion/restoration, result/constant edits, repeated failed preparation,
fresh equivalence, caller/native reuse and no-op behavior in cpp/php/js on Clang/GCC.
`tests/match_conditions.php` checks AST ownership, single-visit preparation, inspection,
emission purity and reset/reprepare equivalence.
