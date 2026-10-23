# Registered default construction and inventory refresh
Doc Status: planning

Candidate: 17bca67b1 plus core metadata/default-construction repair.
Reuse existing runtime_default_constructible registry fact. Core vector/hash now
explicitly register their existing guarantee; supports_default no longer names them.
No cleanup, transfer, literal-construction or new factory permission is inferred.

/tmp/registry-default-construction-01: fourteen checks pass, including implicit fields
of an unfamiliar vessel provider and vector/hash in ordinary/concrete O0/O2 output.
Factory counts stay unchanged during implicit construction. Removing the provider's
default_constructible opt-in rejects an uninitialized field in its constructor.
Existing empty-only factory/default/admission cases remain covered.

registry_audit.py refresh: 255 files, 181 current candidates, 32 retired/changed.
Immediate repeat is byte-identical. Prior dispositions survive exact excerpt identity;
missing matches are historical candidates, not evidence of semantic closure.

Bootstrap 1:14.80 GNU wall, 342620 KiB RSS, 312 MiB PHP heap. Same first syntax_node
blocker and diagnostic set. No native compiler rebuild; CH15 remains blocking.
