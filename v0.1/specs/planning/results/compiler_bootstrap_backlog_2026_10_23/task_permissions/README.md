# TASK-01 permission transport checkpoint
Doc Status: planning

Candidate: 8804eb4d8 plus this commit's compiler/permission-header changes.
Reuse: existing capture facts, recursive container classification, closure erasure,
provider adaptation and concrete callable facade. Source class/interface shared
handles gain joined-only environment permission; isolated consumers still reject.
Runtime-owned resource handles and weak/unique/borrowed capture admission remain
unchanged. This slice does not yet select joined policy from task API metadata.

`task_transfer.py --results /tmp/task-permissions-transport-02` passes 19 tasks:
18 ordinary/concrete cold/warm programs and metadata/retained rejection-recovery.
New controls preserve joined-only permission through nested erased callbacks,
parameter/return copies and provider adaptation, while isolated permission remains
false. An empty nested callable denies both permissions without preventing ordinary
invocation of a body that does not call it. These are permission proofs, not a
joined-worker concurrency proof or native compiler rebuild.

Initial run -01 passed ordinary programs but failed concrete compilation because
the permission header included runtime_error and its template-bearing dependencies.
The corrected dependency-free task_permissions header exposes only enums and simple
permission operations; runtime guard throwing stays with callable_t. Initial metadata
fixture ordering also selected the wrong probe for rejection tests; corrected order
restores the existing execute probe contract. No semantic weakening for either fix.

Bootstrap: 75.94 s wall / 342272 KiB RSS (334.3 MiB) / 312 MiB PHP heap.
First failure remains required file-field constructor initialization. No diagnostic
texts added or removed versus retained_lookup. No fatal/OOM, emission or native
compiler rebuild; these early-stop measurements are not full-bootstrap cost.

Next: register required API policy, apply it to payload and callback checks, then
prove joined work/publish identity, lifetime, failure and synchronization, and rerun
the pending TASK-02 v0.2 cases. TASK-01 remains incomplete.
