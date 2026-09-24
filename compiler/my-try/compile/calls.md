# Compiler coordinator
Doc Status: supporting

```text
main.php
  Compiler::init(paths)                         compile.php
    [each path] Module_Loader::init(module, path) ../01_prepare_inputs/module.php
      [each PHS file] File_Loader::init(file, path) ../01_prepare_inputs/file.php
  [action] display retained modules and source content
  Compiler::exec()                              compile.php
    Compiler::tokenize() -> Tokenizer           ../02_tokenize/tokens.php
    Compiler::parse() -> Parser                 ../03_parse/parser.php
      Symbol_Collector::record / finish         ../04_analyze/collect/collect.php
    Compiler::llvm()
      LLVM_Preparation::prepare_program        ../05_llvm/prepare.php
      LLVM_Generator::generate                  ../05_llvm/generate.php
    [if dbg] Compiler::run_native()
      Native_Runner::run -> dump                ../06_native/run.php
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
