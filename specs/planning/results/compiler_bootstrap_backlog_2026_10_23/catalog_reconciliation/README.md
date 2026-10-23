# Catalog regression reconciliation
Doc Status: supporting

Parent b539f50b9; test-only reconciliation, no production semantics change.

Core metadata has 26 cpp_types entries: 25 definition recipes and one by-value
modifier. CPP_Type_Binding_Registration adds void, null, false and true sentinel
bindings, giving 29 definitions. The stale test expected 26. Explicit boolean
sentinel spelling checks accompany the corrected count. Full type_catalog.php
passes (0.35 seconds), including contract and demand-only interning assertions.

The model fixture loads default runtime modules and resolves their signatures:
27 intrinsic definitions, 10 selected runtime types, four callable definitions and
two internal initializer definitions give 43. There are 32 concrete identities and
14 demanded applications, for 46 types. See startup_inventory.stdout and probe.
Core-only catalog registration still has zero applications; these are different
fixture scopes, not a relaxation of demand-only template registration.

With corrected counts, model.php fails its legacy filename allowlist on
function_node, now defined in 03_parse/structures/structures_declarations.php.
The class extends callable_declaration_node and is an AST record, not a worker.
Graph validation reconciliation remains open; no full model pass claimed. Its
LLVM-backed fixture has not prompted any LLVM implementation changes.

No bootstrap rerun: only tests/docs changed; the latest production checkpoint is
nullable_static (74.47 seconds, 342944 KiB RSS, 312 MiB PHP heap).
