# Minimal file sync: PHP and native proof
Doc Status: planning

The existing Compiler now synchronizes notified files into its retained Model.
Full compilation uses the same sync path. Only a changes property was added to
file and collected_name; no change records, extra model properties, histories or
selective dependency engine were introduced. See compiler/my-try/docs/incremental.md.

Final command:

```sh
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /tmp/scpp-native-244 \
  --candidate-revision d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 \
  --results compiler/my-try/build/native-sync-06
```

Target is the same PR244 candidate plus previously applied portability/task fixes;
no target/runtime/converter patch was added here. Verified release pin unchanged.
Source hashes describe the converted source and native driver. Build output under
build/ is disposable; these summaries and the regression source are durable.

- Normal STAN-enabled native build and incremental rebuild passed.
- 142 PHP/native comparisons: 48 valid programs, 94 rejections.
- All 48 valid LLVM outputs compiled/executed with expected results.
- Native preflight uses the default 12 workers and exercises repeated updates,
  body-only flags, new AST replacement, unchanged caller reuse, duplicate retention,
  exact-match removal and deletion filtering. The single-worker pipeline-order
  preflight remains separate.
- STAN: zero blocking compile errors; 260 advisory errors and 104 warnings remain.
- Full PHP runner passed: 50 PHP files linted, 19 LLVM executions, 28 call executions,
  sample exit 9. Its registered incremental suite additionally checks signatures,
  struct-field addition/removal, failed candidate isolation, global tombstones,
  fresh additions, flag reset and module reset. Follow-up focused run added
  cross-file duplicate deletion and combined declaration/body flags and passed.
- Style: 51 PHP/template sources checked; git diff --check passed.

First PHP behavior checkpoint was tests/incremental.php before native stabilization.
Native attempts 1/2 stopped at converter constraints (bitwise expressions, optional
local annotation). Attempt 3 stopped at STAN's constructor-constant initialization
recognition. Attempt 4 compiled compiler units but failed the test driver's nested
vector access. Source-only adaptations addressed all four, without bypassing STAN.
Attempt 5 passed; attempt 6 repeated final validation after making deleted-entry
short-circuiting explicit. Per-command durations are retained in timings.json.

Deferred: tombstone reclamation, old-reference release, automatic watching/process
loop, bidirectional use dependencies, selective semantic rechecking and multi-error
STAN. Parser language coverage is unchanged except collected inventory now includes
already-supported struct fields. Module changes use init + exec, a full rebuild.
