# Shared semantic preparation
Doc Status: supporting

- `file.php`: `File_Preparation`, the active backend-neutral preparation owner;
  prepare the supported file body and attach facts to specialization records.
- `literals.php`: exact integer literal preparation under the Simple C++ contract.
- `cleanup.php`: tree traversal invoking each specialization's cleanup.
- `structures.php`: backend-neutral facts, completed-file records and invocation data.

Shared types live in `../../compiler/types/`; scope representation and lookup live in
`../../03_parse/scopes/`. This grouping changes locations, not processing behavior.

Prepared facts are accessed through the specialization records: `preparation()`,
`require_preparation()` and `set_preparation()`. The `Preparation_Facts` trait shares
nullable access, assignment and cleanup; concrete fields and required casts stay in
the final specialization. Literal and reference facts share only
the prepared_expression type field; integer decimal text, floating decimal spelling,
boolean value and reference declaration live in separate typed fact records. Floating
spellings are retained verbatim from validated numeric tokens, without host rounding.

Compiler::prepare() publishes shared preparation without backend emission.
Compiler::cpp() consumes it; exec_cpp()/update_cpp() still run the complete pipeline.
Compiler_Lifecycle::reset_preparation() clears facts and dependent C++ output;
reset_cpp() clears output alone.

File_Preparation owns root iteration and failed-phase cleanup. Each node delegates
through its specialization's statement/expression preparation hook; typed static
routines own inference and resolution using an invocation-local preparation_context.
Bindings and returns select their expression children explicitly. Base hooks reject
unsupported nodes without walking them; no kind-selection chain or generic second
walk is involved. Specializations attach returned expression facts locally.

The destination is one shared semantic preparation path. Extend this model for new
language semantics. The separate token-indexed name maps and template checks are
[parked LLVM regression code](../../05_backend/llvm/README.md), not a second active
semantic model. LLVM must be reviewed and adapted to consume shared facts before
its development resumes, leaving backend-specific lowering there. Consolidating
those implementations is deferred; this isolation does not change either behavior.
