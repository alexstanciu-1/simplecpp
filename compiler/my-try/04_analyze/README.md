# Analysis
Doc Status: supporting

[collect/](collect/) records canonical names supplied by the frontend during parsing
and registers declarations in file-local scopes after a successful parse. Serialized
global publication belongs to `compiler/publication.php`.

[prepare/](prepare/) owns the active backend-neutral semantic direction:
`File_Preparation` prepares supported syntax and attaches facts to AST specializations.
Completed-file records reference that syntax; they do not own token-indexed fact maps.
[The C++ backend](../05_backend/cpp/) currently consumes those shared facts.

Shared [type definitions](../compiler/types/) live at the application level and language
definitions are installed when Model initializes its language scope. Shared
[scope representation and lookup](../03_parse/scopes/) live with parsing.

The old name-resolution and template-checking stack is parked under
[the LLVM backend](../05_backend/llvm/README.md). Its `LLVM_Legacy_*` workers and
`llvm_legacy_prepared_names` are retained only for existing LLVM regressions; they
are not shared semantic owners.

The intended destination is one backend-neutral semantic preparation path. New
semantic work must extend `File_Preparation` and specialization-attached facts,
not independently add semantics to the parked LLVM stack. Before LLVM development
resumes, review and adapt it to consume shared facts, retaining only LLVM-specific
lowering in that backend. This isolation does not merge the implementations or
change language support, fact layout, or retained storage.
