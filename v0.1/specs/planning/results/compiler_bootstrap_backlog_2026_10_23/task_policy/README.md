# TASK-01 registered joined admission
Doc Status: planning

Candidate: b92221b1d plus this commit's metadata/policy integration.

One compiler task_transfer_policy enum replaces the parameter Boolean. Reader,
concrete registration, generic patterns/substitution, exact callable contracts,
shared payload/capture preparation and emitted guards carry it. Legacy metadata
requires_transfer remains isolated; only task_run_publish_unordered opts into
joined_shared for inferred T/R payloads and both worker-executed callbacks.
Unknown/conflicting policy declarations and invalid callable parameter shapes reject.
No scheduler, resource permission or synchronization protocol was added.

Evidence:
- task_transfer.py: 23 tasks pass, both modes, including shared known/erased callback
  execution through an explicit joined-policy provider, empty callback rejection,
  nested permission intersection and preserved isolated rejection/cold-warm behavior.
- Extended metadata rejection and retained callback repair pass separately after
  adding unknown/conflicting/reference/variadic/default/scalar/nested policy cases.
- task_joined_shared.py: six tasks pass. Shared input/result identity and synchronized
  updates execute in both modes. Isolated shared payloads and joined borrowed captures
  reject. The shared object occurs twice in the input and is observed as that same
  object by publication; two updates and two publications are asserted.
- Portable extracted retained lookup: PHP and both v0.2 modes pass with 4000 operations,
  2002 entries and original identities. Previous legacy overlap/TSAN and reader-unlocked
  negative-control evidence remains in retained_lookup. This closes TASK-02's bounded
  repair, not a whole-compiler race audit. This new run did not instrument v0.2 with TSAN.
- Existing task submission: unordered, empty, publisher-context guard, erased isolated
  rejection, worker error and retained metadata/placement contracts all pass.

Initial bootstrap attempt stopped during conversion because new internal methods had
unsupported enum parameter defaults. Every call already supplied the policy; defaults
were removed. This was source authoring repair, not new converter support.

TASK-01 remains open for explicit shared-owner lifetime and worker/publisher failure
proof closure. Passing these fixtures is not a native compiler rebuild.

Bootstrap retry: 74.18 s wall / 341684 KiB RSS (333.7 MiB) / 312 MiB PHP heap.
First required-file initialization blocker and aggregate diagnostic set unchanged.
No fatal/OOM, emission or native compiler rebuild. Source hashes accompany the run.
