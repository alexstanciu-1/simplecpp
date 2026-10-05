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
  Compiler_Lifecycle::retire_deleted_sources
    guard successful join -> semantic notification/unlinking -> Collection_Retirement disposal
Compiler::prepare() -> same retirement protocol -> declaration list -> function-body list -> file-body list -> pending preparation changes
Compiler::cpp() -> select dirty definition/body fragments -> render -> assemble -> publish main.cpp -> acknowledge source changes
```

Standalone tokenize/parse/prepare entrypoints use these same phases. Parser calls
collector directly; new global entries register under the task batch lock. The C++
path retains unaffected prepared facts. LLVM name/template preparation remains
parked and runs on the whole live program; it does not define shared semantics.
Host_Report owns display and optional native sample execution.

Tokenizer returns `token_list`; Parser consumes it and returns `parsed_file`.
Only these data records are published in Model, never the workers.

The standalone `File_Preparation` adapter also sequences lifecycle retirement
before analysis. Parsing accumulates source changes and transfers body-work identity;
analysis owns scheduling state. No later-stage scheduling runs from the parser.
Standalone `CPP_Generator::generate` returns assembled output without Model publication
or acknowledgment. Backend reads of work/type facts use `Preparation_Status` and
`Type_Queries`; they do not invoke semantic preparation.

Parser builds a direct object graph rooted at parsed_file.root. Its private
productions return ast_node objects. Concrete nodes own typed fields and Storage
child lists; collector links point to those same objects. No registry lookup or
view membership step is involved.

LLVM preparation returns Storage<llvm_prepared_file>; template checking and LLVM
generation consume it. Internal named object collections use Keyed_Storage;
[LLVM backend](../05_backend/llvm/README.md) lists the boundaries.
