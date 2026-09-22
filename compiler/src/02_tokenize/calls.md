# 02 tokenize call map
Doc Status: supporting

Caller: Phases::run_tokenization(), after source snapshots finish. tokenize.php owns
File_Tokenizer and its lexical dispatch. join.php accepts buffers; store.php and structures.php
own data. Full and incremental work use the same selected tasks.

The following are ordered lifecycle calls, not calls between siblings.

```text
Tokenizer                                  main_tokenize.php
  init() -> Token_Selection::select()
  run() -> File_Tokenizer::tokenize() [each task]
  finalize() -> Token_Join::join()
  result() => Token_Set
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

File_Tokenizer::quoted_end() scans quoted spans without decoding contents or
choosing runtime types. Byte_Literals in body checking owns escape interpretation.

Metaprogramming vocabulary includes template/typename/constexpr/consteval/const
and boolean literals. Separate left_angle/right_angle tokens preserve adjacent
closing brackets in nested applications. Lexing does not distinguish type names
from constant names or perform template lookup.
