# Analysis
Doc Status: supporting

`collect/` records canonical names supplied by the frontend during parsing. It
registers declarations in file-local scopes after a successful parse; serialized
global publication belongs to `Source_Publication` in `compile/publication.php`.

`types/` owns canonical type definitions, hardcoded language types and construction
of source type definitions. Shared scopes and parent lookup live in `scopes/`.
`file.php` prepares the currently supported straight-line S2S file, including
binding classification and expression facts. `literals.php` owns exact integer
literal preparation. Specialized AST nodes own prepared facts; `nodes.php` exposes
typed access and `cleanup.php` walks the tree to invoke each node’s cleanup. Model
retains completed-file records, with no token-keyed expression/binding maps.
`05_cpp` traverses syntax and reads those facts to emit C++.

`prepare.php` contains the earlier `Name_Preparation` experiment, consumed by LLVM
preparation and regression tests. `templates.php` and `template_check_context`
remain backend-coupled checking debt. They are not the canonical S2S resolver or a
completed validation pass; their isolation/development remains deferred.
