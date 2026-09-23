# Construction lookup and association debug evidence
Doc Status: planning

12 PHP/native scenarios and 57 host invariants pass. Native attempt one stopped
at the known STAN final-throw return-flow limitation in the fixture finder; attempt
two passed. One C++ build, no production correction. Native dependency bytes match
the workspace. Timing distinguishes construction-only, association, and full-scope
PHP checkpoints; debug coverage was added before native stabilization.

Construction tests use real construction-name bindings, source/provider definitions,
missing preparation, exact-identity mismatch and stale owner/instance rejection.
Generic annotation lookup proves substitution only, not permission to construct a
formal type. A deliberately rebound fixture tests inconsistent canonical identity;
normal consumers must never mutate a published snapshot.

Debug tests cover source/provider signatures, nullable receivers, local pairs,
family operations, stable repeated output and escaped source paths. This is an
association projection, not the complete type dump. Remaining owner serializers
are tracked in ../../type_debug_projection_inventory.md.
