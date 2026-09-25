# Compiler coordinator
Doc Status: supporting

```text
Compiler::init(paths) -> Model::reset + Module_Loader discovery (no reads)
Compiler::exec() -> sync(all live paths) -> llvm()
Compiler::update(paths) -> sync(notified paths) -> llvm()
Compiler::sync(paths)
  private file -> worker read/tokenize -> parse/collect
  locked publish_update -> compare declarations -> replace file + update globals
  join -> restore model root order
Compiler::llvm() -> full live-program preparation -> generation

Standalone inspection: tokenize() / parse() retain explicit stage/reset behavior.
Module changes: init(complete module list), then exec().
Host_Report owns display and optional native sample execution.
```

Collection occurs during parsing. Preparation resolves names, checks symbolic
template contracts and prepares demanded instances before generation. Native
execution is enabled by the host debug constant; non-debug tests explicitly call
the same Native_Runner when they need execution.

Tokenizer returns `token_list`; Parser consumes it and returns `parsed_file`.
Only these data records are published in Model, never the workers.

Parser builds a direct object graph rooted at parsed_file.root. Its private
productions return ast_node objects. Payloads own child references and Storage
child lists; collector links point to those same objects. No registry lookup or
view membership step is involved.

LLVM preparation returns Storage<llvm_prepared_file>; template checking and LLVM
generation consume it. Internal named object collections use Keyed_Storage;
[MODEL.md](../MODEL.md#collection-choices-during-llvm-preparation) lists the boundaries.
