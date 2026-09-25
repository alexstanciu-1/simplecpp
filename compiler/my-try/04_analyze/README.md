# Analysis
Doc Status: supporting

`collect/` records canonical names supplied by the frontend during parsing. It
registers declarations in file-local scopes after a successful parse; serialized
global publication belongs to `Source_Publication` in `compile/publication.php`.

`types/` owns canonical type definitions, hardcoded language types and construction
of source type definitions. Shared scopes and parent lookup live in `scopes/`.
`file.php` prepares the currently supported straight-line S2S file, including
binding classification and expression facts. `literals.php` owns exact integer
literal preparation. Model retains completed prepared files; `05_cpp` maps their
canonical types and emits C++.

`prepare.php` contains the earlier `Name_Preparation` experiment, consumed by LLVM
preparation and regression tests. `templates.php` and `template_check_context`
remain backend-coupled checking debt. They are not the canonical S2S resolver or a
completed validation pass; their isolation/development remains deferred.
