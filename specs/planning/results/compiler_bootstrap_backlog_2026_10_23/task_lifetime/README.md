# TASK-01 bounded lifetime and failure closure
Doc Status: planning

Production revision c53a66687, with this commit's test-only additions. No new
scheduler, lifetime semantics or task permissions were added in this checkpoint.

`task_joined_runtime.py --results /tmp/task-joined-lifetime-01` passes with
AddressSanitizer/UndefinedBehaviorSanitizer and leak detection, outside the ptrace
sandbox. Two workers must cross a barrier before either can complete. Exercise
success, worker failure and publisher failure with per-call threads and a two-worker
shared pool. One worker delays after the barrier so the error path must join live
work. Assertions require two entries/completions before return/rethrow, original
error propagation, callback-owned shared handles alive during work/publication,
release of both owners after joining, and a successful subsequent batch. Observer
references are runtime-test instrumentation, not source borrowed-capture admission.
The fixture and tasks.cpp are instrumented; the shared fixture-runtime object is
built by the existing runner without sanitizer flags. No whole-runtime sanitizer
coverage claim is made.

`task_joined_shared.py --results /tmp/task-joined-shared-02` passes 12 tasks across
ordinary and concrete modes. New source proofs cover temporary callbacks returned
from factories with the only shared owners, native destructor observation after the
complete joined call, original source exceptions from work/publish, preserved partial
publication and successful reuse. Existing shared input/result identity and isolated/
borrow rejection controls pass. The deterministic source failure tests use one worker;
the runtime proof above separately forces overlap and verifies join-before-rethrow.
Native destructor observation is not a PHP exact-cleanup-timing requirement.

Together with task_policy and task_permissions evidence, this closes TASK-01's
accepted bounded scope and TASK-02's dependent v0.2 execution requirement. PHP/legacy
portable lookup parity and the legacy overlap/TSAN negative control are recorded in
retained_lookup; these are not a claim that the compiler itself runs concurrently.

No new bootstrap measurement: production sources/runtime/metadata are unchanged
since c53a66687. The latest checkpoint remains 74.18 s / 333.7 MiB RSS / 312 MiB
PHP heap, stopping on required file-field initialization. SELF-01, source/converter
initialization work, other backlog items and blocking CH15 work remain open.
