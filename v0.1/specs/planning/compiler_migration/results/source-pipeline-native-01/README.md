# Per-file read/tokenize/parse pipeline
Doc Status: planning

The final normal STAN-enabled native build and incremental rebuild passed.
142 exact PHP/native outcomes passed: 48 valid emitted programs compiled/executed
with expected results, and 94 rejections. Repeated compilation and recovery remain
checked. Native Compiler.jobs is 3; the PHP adapter is sequential.

The driver additionally proves the missing tokenization barrier with a one-job
fixture: a.phs parses successfully, then b.phs fails scanning. The first file's
syntax and global declaration must already be published. This runs in both PHP
and native and fails if all files are tokenized before any parsing starts. It also
checks standalone tokenize() and parse() entrypoints against a disk-backed fixture.

Reproduce with a fresh results directory:

```sh
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /tmp/scpp-native-244 \
  --candidate-revision d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 \
  --results compiler/my-try/build/native-source-pipeline-03
```

The candidate is PR244 plus the preceding portability/task fixes; this slice adds
no new candidate toolchain patch. Full logs, commands and candidate file hashes
remain in the local results directory. Saved input hashes identify the compiled
snapshot; final role-comment edits are behavior-neutral.

STAN: zero blockers, 223 advisory errors, 87 warnings. Earlier attempts exposed
an untyped static Storage receiver in the new driver and an empty object-hash
property initializer. Both were fixed using explicitly typed locals; no generated
C++ was patched. The final native run passed.

PHP pipeline.php additionally verifies no content read at discovery, changed disk
bytes being read by Tokenizer, file removal between discovery and execution, and
failure publication. publication.php verifies queue identity/provenance guards.
Both passed, alongside the 50-source style check and existing model suite. The host
main report still prints source text; its sample compiled and returned 9.

Work is a fixed batch of per-file chains. Discovery still completes first. Reads,
scanning and parsing can overlap across files under one job limit, without separate
stage pools or a tokenization barrier. No incremental, filesystem snapshot-coherence,
throughput or memory-efficiency claim is made.
