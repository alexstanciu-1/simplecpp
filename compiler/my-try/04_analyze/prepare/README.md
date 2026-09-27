# Shared semantic preparation
Doc Status: supporting

- `file.php`: `File_Preparation`, the active backend-neutral preparation owner;
  prepare declaration signatures first, then the entry body and function bodies.
- `declarations.php`: signature/type lookup, function-local contexts, struct fields,
  named calls and reference/value boundaries.
- `literals.php`: exact integer literal preparation under the Simple C++ contract.
- `cleanup.php`: tree traversal invoking each specialization's cleanup.
- `structures.php`: backend-neutral facts, completed-file records and invocation data.

Shared types live in `../../compiler/types/`; scope representation and lookup live in
`../../03_parse/scopes/`. This grouping changes locations, not processing behavior.

Prepared facts are accessed through the specialization records: `preparation()`,
`require_preparation()` and `set_preparation()`. The `Preparation_Facts` trait shares
nullable access, assignment and cleanup; concrete fields and required casts stay in
the final specialization. Literal and reference facts share only
the prepared_expression type and stable-storage flag; integer decimal text, floating decimal spelling,
boolean value and reference declaration live in separate typed fact records. Floating
spellings are retained verbatim from validated numeric tokens, without host rounding.

Compiler::prepare() publishes shared preparation without backend emission.
Compiler::cpp() consumes it; exec_cpp()/update_cpp() still run the complete pipeline.
Compiler_Lifecycle::reset_preparation() clears facts and dependent C++ output;
reset_cpp() clears output alone.

File_Preparation owns root iteration and failed-phase cleanup. Each node delegates
through its specialization's statement/expression preparation hook; typed static
routines own inference and resolution using an invocation-local preparation_context.
Bindings, calls, member accesses and returns select their expression children explicitly. Base hooks reject
unsupported nodes without walking them; the declaration prepass dispatches only top-level signatures; body traversal does
not repeat expression preparation. Specializations attach returned expression facts locally.

The destination is one shared semantic preparation path. Extend this model for new
language semantics. The separate token-indexed name maps and template checks are
[parked LLVM regression code](../../05_backend/llvm/README.md), not a second active
semantic model. LLVM must be reviewed and adapted to consume shared facts before
its development resumes, leaving backend-specific lowering there. Consolidating
those implementations is deferred; this isolation does not change either behavior.

## Ordinary functions and value structs

The current S2S boundary remains one source file. Function/struct declarations may
appear after their users. Signatures are attached before any body is prepared;
each function gets a fresh local `Key_Storage_List<prepared_storage>` seeded with
parameters. Entry locals are not implicit function captures. Calls retain their
resolved declaration and signature; members retain their exact prepared field.
No backend performs symbol lookup. Struct fields use an ordered duplicate-key
collection owned by their prepared record, not the surrounding variable scope.

Supported function signatures use named scalar/record types, explicit returns
(including `void`), positional value parameters and explicit `&` parameters.
Only a uniquely resolved function is supported; overload selection and templates
remain deferred. Integer value boundaries use the runtime integer conversion;
reference boundaries require stable storage with a compatible representation.
Integer aliases with equal width/sign have compatible reference storage.

Structs use inline value storage. This frontend slice supports `bool`, fixed-width
integer aliases and nested structs as fields. Ordinary `int` and `float` fields
remain excluded by the existing compact-layout contract. Typed locals without an
initializer use their normal generated default; copies, field reads/writes and
record function boundaries are supported. Positional array literals are not struct
constructors. Keyed initialization, `new`, field initializers and other syntax not
accepted by this frontend are not added here. General validation/STAN is deferred;
preparation rejects only boundaries it cannot serve correctly to generation.
