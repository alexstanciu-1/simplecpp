# Source body checking
Doc Status: supporting

Body_Worker::prepare binds fixed accepted callable/name/type inputs and private output
and diagnostic owners. check() runs once, returning a complete Checked_Body or a
source-attributed failure. Calls read signatures and never check other bodies.
Expression and statement stacks retain iterative ordering. Pending places are resolved
by consumers; reserved argument slots survive nested calls; scope IDs match lexical
bindings. Initialization, assignment and return construction remain distinct choices.
No lowering or lifetime permission is invented during body checking.

Run:

```sh
python3 compiler/tests/body_worker/run.py --results /tmp/body-worker-NEW --target-checkout /tmp/scpp-json-240-probe
```

58 PHP/native cases cover ordinary/provider/named-conversion calls; nested argument
ranges; scalar and record expressions; fields and fixed-array indices; conversions;
record borrow and owned-return modes; declarations/assignments; scopes, branches and
loops; meaningful rejection diagnostics; canonical-store purity and one-shot ownership.
Produced plans are traversed by Expression_Order. Depth cases cover long operations,
nested calls and blocks. Explicit nested-call expectations are reused from the preserved
prototype parameter-body tests. The prototype is not run as a complete compiler here.

Remaining integration coverage: concrete method/template application bindings,
storage-element expressions, metadata-bound byte/echo success paths, and managed
lifecycle write/return paths beyond the proved plain-record cases. The corresponding
worker algorithms are present. Whole body selection/reuse/join and the complete analysis
pipeline remain unfinished. Point and Buffer fixture records use normalized materialization
rather than the unfinished preparation coordinator.

Source failures retain path/span/reason through the worker's diagnostic() reader.
Internal stale-state/incomplete-output errors do not fabricate a source diagnostic.
Snapshot membership stays independent and immutable rows retain identity.
Evidence and effort: `specs/planning/compiler_migration/results/body-worker-01`.
