# Canonical identity and filesystem failure boundaries
Doc Status: planning

2026-10-25; baseline 063dc6caf plus this slice. User approved both PHP adaptations
and then approved repair of the failure-path blocker exposed by native proof.

canonical_type_use::matches uses existing type_id() accessors, preserving uint32
storage and canonical-int strict comparison. Native_Ninja_Build::admit uses typed
empty entries and take_false instead of comparing a container Result with false.

Failure-path audit showed framework binding mismatch, not missing compiler Result
semantics: filesystem scan/size/mtime PHS wrappers returned plain false where the
existing explicit false_sentinel is required. All three now use false_sentinel,
accepted on both routes. No type-system, converter inference or operator admission
changes; ordinary false bool payloads remain distinct from failure.

Proof extracts the production Type_Identity/canonical_type_use classes and Ninja
scan extraction block. Tests equal/different/max uint32 IDs and by-value distinction.
Real filesystem paths prove successful empty scan, populated scan, and missing-path
failure with the exact expected exception message. Size and mtime prove success and
failure with previous outputs preserved on failed take. PHP, legacy native, ordinary
v0.2 native and concrete v0.2 native all pass. Installed production scan/size/mtime
bindings are selected unchanged for the focused v0.2 fixture. Native proof does not
claim the entire Ninja build orchestration was executed.

Host type_catalog, type_capabilities and incremental_preparation pass. Initial proof
attempt had an invalid PHP tag in a PHS fixture; corrected. Next attempt reproduced
the explicit-sentinel binding failure; fixed only after user approval. The first
bootstrap was interrupted and its process handle was no longer available on resume;
no completed measurement is claimed for it.

Final bootstrap: 136.98 seconds, 612960 KiB peak RSS (598.59 MiB), 580.5 MiB PHP heap.
Preparation categories decrease 19 -> 17: canonical-int and Result-construction
messages disappear, no new messages. First remaining failure: S2S value probes
require a scalar, nullable scalar, Result scalar, mixed, registered container or null.
Stopped before fixing that blocker, as requested. Native compiler generation/run
and second-generation acceptance remain incomplete.
