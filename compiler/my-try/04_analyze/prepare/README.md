# Preparation
Doc Status: supporting

- `file.php`: prepare the supported file body and attach facts to specialization records.
- `literals.php`: exact integer literal preparation under the Simple C++ contract.
- `cleanup.php`: tree traversal invoking each specialization's cleanup.
- `structures.php`: preparation records, including experimental name/check records.
- `names.php`: the existing Name_Preparation used by the LLVM experiment.
- `templates.php`: existing experimental template checking; development remains deferred.

Shared types live in `../../compiler/types/`; scope representation and lookup live in
`../../03_parse/scopes/`. This grouping changes locations, not processing behavior.

Prepared facts are accessed through the specialization records: `preparation()`,
`require_preparation()` and `set_preparation()`. Each specialization clears its own slot. Literal and reference facts share only
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
