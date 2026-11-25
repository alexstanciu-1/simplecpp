# Concrete preparation queue
Doc Status: supporting

`resolve_types/Preparation_Queue` owns transient requests and the fact-to-consumer
index. Concrete workers and their joins remain responsible for accepting semantic
results before publishing facts. This component does not implement the complete
concrete-preparation coordinator.

A `Preparation_Request` holds exactly one typed application, record or member
task. Integer tags replace the prototype's string enum; checked named getters
replace the union payload. `Preparation_Entry`, `Preparation_Dependents` and
`Preparation_Batch` name the mutable indexing records formerly represented by
nested ad-hoc arrays. Keys and facts retain the coordinator's spelling.

- Add rejects an already active key. Wait and complete require exact request identity.
- Missing prerequisites are deduplicated and indexed by fact; published facts are
  retained, so future requests can immediately reuse them.
- Publishing a fact visits only its indexed consumers. Satisfied edges are removed;
  the last edge schedules the request in its kind's ready batch.
- Taking a batch copies fixed membership and drains that kind. Later readiness
  does not change a previously returned batch.
- Completion rejects blocked requests and removes the active request and any ready
  membership. A key may be registered again after completion.
- A blocked cycle remains pending for the coordinator to diagnose; it is not made
  ready or silently dropped.

Two local invariant repairs make previously incidental call ordering safe:
waiting on a new prerequisite removes existing ready membership, and completing
an untaken request removes ready membership. Normal take/process/complete and
take/process/wait flows preserve the prototype's behavior. No global graph scan
or permanent completed-payload cache is introduced.

Validation lives in `compiler/tests/preparation_queue`: a scheduling trace with
independent assertions across all three task kinds, rejection checks and a
host-only weak-reference release proof. Task payloads are queue fixtures; their
semantic worker validation remains covered by the existing application/member/
record suites. PHP weak references prove host retention only, not native destructor
or allocator behavior. Full coordinator execution remains separate.
