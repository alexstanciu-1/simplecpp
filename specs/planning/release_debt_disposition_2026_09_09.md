# Release debt disposition — 2026-09-09
Doc Status: planning

This records the explicitly deferred baseline defects for the upcoming release.
It does not change language semantics, disable tests, or mark failing tests as
passing. Baseline reproductions used isolated main at
`ff2ca601dc9ab69f7f391c7d56b752a68c4af15d`; see
[release_candidate_validation_2026_09_09.md](release_candidate_validation_2026_09_09.md).
No new baseline reproduction is claimed by this disposition pass.

The release decision is to defer these four existing defects rather than expand
this stabilization pass into entrypoint lowering and runtime operator semantics.
They remain visible known failures. A release may proceed with these exceptions
recorded once the current candidate fixes and final release validation are complete.
This is not a declaration that every suite is green.

| Tracking ID | Disposition and impact | Owner / authority | Completion criterion and current alternative |
|---|---|---|---|
| REL-DEBT-01 | **Deferred known compiler defect:** top-level `return 0;` produces a wrapped integer at a native `int` entrypoint and fails compilation. | `generators/php/src/Generator/Generator.php`, entrypoint return lowering; positive legacy fixture `int_000_top_level_return_zero`. | Define the entrypoint exit-status boundary locally and prove zero/nonzero returns plus normal completion. For a successful script, omit the terminal `return 0;` and let it complete normally. Keep the positive fixture failing until repaired. |
| REL-DEBT-02 | **Deferred known runtime implementation gap:** prefix/postfix increment on `nullable<int_t<>>` is missing from the lifted operator surface. | Centralized runtime operator generation and nullable lifting. `runtime/specs/spec.md` §6.10 and `runtime/specs/config.json` group `nullable_lifted_ops` require checked delegation for supported mutation families. | Add mutation through the existing lifting owner, proving prefix reference semantics, postfix old-value semantics, and empty-value failure. Meanwhile extract a checked integer, mutate it, and assign it back. Keep `test_nullable.cpp` expectations unchanged. |
| REL-DEBT-03 | **Deferred known runtime implementation gap:** native `result<sample_box> == error` has no overload. This is not merely an obsolete test: the configured result lifting contract explicitly allows error-sentinel comparisons. | Centralized runtime wrapper equality/lifting; `runtime/specs/config.json` result `operator_lifting_rule`. | Implement branch comparison through the shared wrapper owner and prove success/error states and supported operand orders without requiring payload equality. Use `has_error()` / `has_value()` natively, or checked `take(...)` in PHS/JSS. Keep `test_or_wrappers.cpp` expectations unchanged. |
| REL-DEBT-04 | **Deferred semantic reconciliation:** the native mixed comparison test requires bool/int `==` to throw, while `support/mixed_t.cpp` explicitly performs boolean conversion and comparison. Both behavior and failure reproduce on main. | `specs/dynamic_types.md` mixed-kind delegation, native scalar comparison policy, and `runtime/include/scpp/support/mixed_t.cpp`. | Resolve the bool/numeric equality contract against the higher-level specs before changing runtime behavior or the assertion. Cover both operand orders and representative true/false/zero/nonzero values. Prefer explicit kind stabilization or strict identity (`===` at the source surface); do not rely on the disputed loose comparison. Keep `test_mixed_t_dynamic.cpp` visible as failing. |

Namespace aliases remain a separate documented unsupported feature. Their
negative compile expectation now checks the alias declaration's source location
and name across GCC and Clang; passing that test does not mean aliases work.

No native operator families or entrypoint semantics were changed in this pass.
The managed-struct slice continues to exclude mixed fields, but JSON and existing
runtime consumers still use mixed values; that exclusion does not eliminate
REL-DEBT-04.
