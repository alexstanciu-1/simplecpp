# Typed Storage receiver adaptation
Doc Status: planning

Applied the nested-storage-locals-01 proof to affected collection operations in
loading, tokenization, parsing, occurrence collection, name/template preparation,
struct preparation and LLVM emission. Locals alias Storage/Keyed_Storage membership;
ordinary vector/hash mutations were not rewritten through copies. Parser child-list
locals are captured once per payload/block, not reconstructed inside each iteration.
Two native type-shadowing locals were renamed (file/loaded and token/span).

Validation: PHP AST/model/tokenizer checks pass; all 19 LLVM fixtures are byte-identical
to the lifecycle checkpoint and 28 call/reference/array/struct/template sample programs
compile and execute with expected exits. The 29 production inputs plus the native
sample driver convert successfully. The normal candidate build passes STAN and
reaches C++; reported Storage/Keyed_Storage receiver errors are absent in this build.
This is not an exhaustive native success: per-file error limits and cascading errors
remain. Other native failures include nullable-interface conversions, exception type
qualification, optional lookup/coalescing, enum switches and container reset typing.

Candidate: PR #244 d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 plus toolchain fixes through
0c28b96e. Two native build attempts: initial collection changes and final loading/name
cleanup. Saved source hashes, PHP logs and final native log describe this checkpoint.
No native toolchain code was changed for the collection-local adaptation.
