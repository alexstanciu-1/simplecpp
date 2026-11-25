# Portable source control flow — 2026-10-25
Doc Status: planning

PHP-only adaptations under broad user authorization: explicit conditional assignment
for container/nullable/struct results; distinct checked initializer local; outer
string local for branch-produced native spelling; exhaustive enum preparation branches
preserving type-before-value preparation; for (;;) for unconditional ancestry/retry
loops. Existing semantics, no compiler algorithm or retained-protocol extension.

Cross-route source_control_flow.py extracts the actual enum preparation branch and
proves its call order; focused companion shapes test list/nullable selection,
retry and branch-local strings. PHP/v0.1/both v0.2 native modes pass. The initial
fixture used unsupported ?Class local comments; corrected to existing nullable<Class>
spelling in both source and fixture. No converter changes.

Host callable_instances, incremental_preparation, cpp_artifacts, references,
enum_helper_retained and local_assignment pass. specialization_dispatch fails at
its synthetic unknown expression's require_preparation; the same failure reproduces
with every touched PHP file loaded from the unchanged parent revision. Both logs
are retained. Host commands include fixture setup mkdir warnings where the tests
also create their already-provided directories; all six listed suites exited zero.

Bootstrap 1:32.91 wall / 627072 KiB RSS / 592.5 MiB PHP heap; categories 15 -> 11.
Still fails preparation. Later checks expose outlives fallthrough and another
construction-target occurrence. Native compiler/second generation not yet built.

Independent JSON binding audit: required null initialization fails; omitted initializer
fails definite assignment; nullable diagnostic fails invariant reference matching.
No JSON binding fix made; this needs a truthful initialization/API solution beyond
changing the PHP expression. Independent source work continues.
