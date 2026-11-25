# Concrete stored source handles
Doc Status: supporting

The existing `cpp_types` recipe now supports an explicit `pointee` parameter and
`concrete_boundary` marker. Generic stored representation selection includes implicit
class storage. Pointer declarations expose typed `operator->` and `operator*` while
compiled implementations delegate checks/lifetime operations to the original runtime.
Native layout probes use forward payload declarations; implementation includes require
complete payloads. Recursive Left/Right source fields pass without changing the layout
scheduler. Source construction uses existing prepared runtime-operation requests;
constructor operands retain their application representations. Prepared shared/unique
upcasts use the existing conversion adapter. No family code was added to the engine.

Validation: accounting compiles at O0 without PCH and passes all 998 checks.
`prelower_header_boundaries.py` passes output, indexing and compound groups, comparing
ordinary and composed behavior/counters and unchanged build reuse. The output fixture
now calls a source-class copy function and checks pointee identity, reference count,
checked null dereference and recursive declaration compilation under an alternate
runtime handle binding. Both source-owner runs report `0 0 0 0 0 0 0` counters.
Source-demand retirement/replay/configuration switching pass with six demanded types
(the previous four plus Left and Right). Metadata rejects 17 invalid configurations.

Raw evidence: `/tmp/accounting-concrete-handles`, `/tmp/prelower-handles-proofs`,
`/tmp/prelower-pointer-contract`. No O2 or native compiler rebuild was run.
Strings/scalar includes, shared runtime payload interfaces and other template exposure
remain separate work; this checkpoint does not claim a template-free application.
