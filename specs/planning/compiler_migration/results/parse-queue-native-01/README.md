# Private parse queue and unordered native publication
Doc Status: planning

Normal STAN-enabled native build and incremental rebuild passed with the tasks
module enabled and Compiler.jobs = 3. All 142 exact PHP/native comparisons passed:
48 valid programs compiled/executed with expected results and 94 rejections.
Repeated compilation and post-error recovery remain checked by the driver.
STAN reports zero blockers, 217 advisory errors and 85 warnings.

Reproduce using a fresh results path:

```sh
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /tmp/scpp-native-244 \
  --candidate-revision d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 \
  --results compiler/my-try/build/native-unordered-queue-02
```

The PR244 candidate includes prior portability fixes plus this change's unordered
task helper, strict registry and shallow signature. Existing candidate changes were
preserved when applying the patch. Target file hashes and full build logs are in
the local results directory; the verified release pin remains unchanged. Saved
source hashes identify the compiled snapshot; subsequent purpose comments have no
behavioral effect.

The native runtime probe tests completion-order publication by making item zero
wait for item one's publication. It also tests mutual exclusion, bounded work,
empty/invalid inputs, worker and publisher failures joining before rethrow, and the
existing ordered helper's unchanged order. Build/run against the built tasks runtime:

```sh
clang++-18 -std=c++23 -O0 -g -pthread -DSCPP_HAS_TASKS=1 -Iruntime/include \
  tests/runtime/native/test_unordered_publication.cpp \
  /absolute/path/to/libruntime.so -Wl,-rpath,/absolute/path/to/runtime-directory \
  -o /tmp/test-unordered-publication
/tmp/test-unordered-publication
```

For this run the library was under build/native-unordered-queue-01/phpp/.prism/
runtime/project/php-strict/fc846b42b2c27d36. Probe compilation/execution succeeded.
PHP publication.php checks isolated parsing, reversed publication, declaration
identity, private locals, global duplicates, membership sealing and work-state
validation. tests/portability/tasks.php proves the sequential facade's callbacks,
error identity, worker validation and conversion. Both passed, along with the
portability regression runner, weak-field tests and 49-source style check.

Earlier independent private-parse and sequential-queue checkpoints also passed
142 comparisons. One queue conversion attempt rejected postfix decrement; source
uses explicit subtraction. The native probe's initial range-for used an unsupported
vector_t iterator surface and was changed to indexed access. Both unordered compiler
runs passed; the final one includes sealed queue membership and compiler job-limit
validation. No incremental behavior, dynamic submission, discovery parallelism,
performance improvement or complete thread-sanitizer coverage is claimed.
