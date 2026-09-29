# Compiler coordinator
Doc Status: supporting

```text
Compiler::init(paths) -> module reconciliation -> discovery when configuration changes
Compiler::exec_cpp() -> sync -> prepare -> cpp
Compiler::exec_llvm() -> sync -> parked llvm preparation/emission
Compiler::update_cpp(paths) -> sync(paths) -> prepare -> cpp
Compiler::update_llvm(paths) -> sync(paths) -> llvm
Compiler::sync(paths)
  module scan -> mark explicit notifications
  tokenize -> join -> retained parse/collect -> join/revision sweep
  notify dependents and remove deleted collected/index memberships
Compiler::prepare() -> declaration list -> function-body list -> file-body list -> pending preparation changes
Compiler::cpp() -> select dirty definition/body fragments -> render selected fragments -> assemble main.cpp
```

Standalone tokenize/parse/prepare entrypoints use these same phases. Parser calls
collector directly; new global entries register under the task batch lock. The C++
path retains unaffected prepared facts. LLVM name/template preparation remains
parked and runs on the whole live program; it does not define shared semantics.
Host_Report owns display and optional native sample execution.

Tokenizer returns `token_list`; Parser consumes it and returns `parsed_file`.
Only these data records are published in Model, never the workers.

Parser builds a direct object graph rooted at parsed_file.root. Its private
productions return ast_node objects. Concrete nodes own typed fields and Storage
child lists; collector links point to those same objects. No registry lookup or
view membership step is involved.

LLVM preparation returns Storage<llvm_prepared_file>; template checking and LLVM
generation consume it. Internal named object collections use Keyed_Storage;
[LLVM backend](../05_backend/llvm/README.md) lists the boundaries.
