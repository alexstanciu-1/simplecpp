# Analysis
Doc Status: supporting

[collect/](collect/) is called directly by the parser, not run as a separate pass.
It reuses existing declaration identities and registers new declarations as they
are recognized. File/member/signature scopes are private to their worker; global
function/type registration goes through `task_synchronize` and `Scope_Publication`.
File executable variables remain local. Unresolved occurrences retain syntax and
scope context for resolution after all parsing/collection workers have joined.

Parsing ends before resolution. After its successful join, standalone `prepare()`
selects changed declarations and affected bodies, retains unaffected facts, and removes
deleted collected entries. Combined `sync` now uses the same incremental frontend phases. See the [incremental strategy](../docs/planning/incremental_strategy.md).

[prepare/](prepare/) owns the active backend-neutral semantic direction:
`Preparation_Worker` schedules the shared `File_Preparation` algorithms, which prepare supported syntax and attaches facts to AST specializations.
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
