# Analysis
Doc Status: supporting

[collect/](collect/) is called directly by the parser, not run as a separate pass.
It reuses existing declaration identities and registers new declarations as they
are recognized. File/member/signature scopes are private to their worker; global
function/type registration goes through `task_synchronize` and `Scope_Publication`.
File executable variables remain local. Unresolved occurrences retain syntax and
scope context for resolution after all parsing/collection workers have joined.

Collected identities are specialized records in `collect/structures.php`:

- `collected_name` holds shared occurrence provenance, revision/change state and
  accessors. Each concrete record owns one typed syntax link exposed by `syntax()`.
- `collected_declaration` adds reconciliation/publication state. Functions and
  structs extend `collected_definition`, which alone owns a preparation-work slot.
  Fields, parameters and explicit local variables have distinct concrete records;
  members are prepared with their enclosing definition and locals with their body.
- `collected_reference` groups variable, function, field and type lookups without
  declaration state. `collected_variable_write` is a separate unresolved-write
  role: first-write declaration versus assignment is decided during preparation.

Preparation never replaces a write occurrence with a declaration record. Its existing
identity is used for inferred storage and retained by later references. Scope indexes,
syntax backlinks and dependency facts continue to reference these same objects;
there is no second symbol graph. The collector owns allocation/registration and
workers own resolution, preparation and lifecycle decisions.

`kind()` computes the existing enum classification from the concrete class. It is
retained for parser reconciliation, occurrence lists and parked LLVM consumers, not
stored as a mutable tag. Parameters and explicit variables intentionally share its
legacy `variable_declaration` classification while exposing different typed syntax.
Generic syntax access and optional preparation-owner access support mixed inventories;
consumers with a known role narrow the collected record and use its typed syntax.

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
