# CH15 registry/type-use discovery
Doc Status: planning

The final-catalog blocking audit is in progress, not closed. The
[lexical candidate inventory](compiler_registry_candidates_2026_10_23.json) scans all
255 stage/core PHP files, including parked LLVM. Refreshed after the default-construction
repair, it records 181 current locations: 38 named registry lookups, 101 semantic
definition/protocol tests and 42 spelling comparisons. Thirty-two previous locations
are retained separately as retired/changed candidates, not automatically classified
as repaired. These patterns find review candidates, not all possible type consumers.
Comments, annotations, test fixtures and generated text are not proof of a defect.
The scan does not prove absence of dynamic/computed name gates or retained-key errors.

## Named lookup review

The compiler already uses registered identities for intrinsic shared/nullable forms,
scalar literals/constants, enum backing, boxed protocols, void emission and variadic
vector ABI. Retain those language roles; a registry lookup alone is not a defect.
Named lookup locations retain initial classification/owner notes when their exact
path/kind/subject/excerpt still matches; those notes are not a fresh semantic review.

Additional policy/transport groups beyond the old four-gate inventory:

| Owner | Finding | Disposition |
| --- | --- | --- |
| Transfer_Preparation::classify | vector/hash recursive transfer plus a mixed-key exception | TASK-01 / CH15: retain isolation semantics, replace family gates with truthful registered transfer facts; copyability alone is insufficient. |
| CPP_Default_Constructors | vector/hash restriction on outlining empty default fields | Repaired using settled prepared_sequence_construction / prepared_empty_construction plans. Existing emitter preserves expressions and field order. Unfamiliar-provider execution proof covers ordinary/prelowered O0/O2; broader audit remains open. |
| CPP_Provider_Properties | int/string, read-only bool/mixed and vector<string> transport | Existing bounded provider ABI; generalization must preserve read/write and representation restrictions. No blanket permission from storage capability. |
| CPP_Wrapper_Transport | explicit vector/hash recursion for provider/native payload conversion | Reuse NULL-01 consumer audit; distinguish actual provider reconstruction from identity transport. No arbitrary wrapper erasure or duplicate semantic model. |
| Typed Iterator consumers | exact registered Iterator identity in relationships/admission | Review intrinsic protocol publication separately from CURSOR-01 safe constructor relation. |

Previously known construction, cleanup, emptiness and callable-payload gates remain
open and assigned in the execution ledger. Source-derived capabilities remain
completed preparation facts with observed dependencies, not registry caches.

## Remaining audit

Review each candidate in context; discard unrelated CLI/metadata-schema spelling
comparisons with reasons. Inventory additional operation/capability gates and retained
identity keys beyond these lexical patterns. Validate both ordinary and prelowered
consumers. Parked LLVM findings are inventoried without implementing LLVM semantics.
Repairs must use their owning model and accepted language contracts; new semantic or
wide cross-owner protocols require discussion while independent slices continue.
Discovery counts do not close CH15, and unfamiliar-provider proofs are still required
for every generalized operation. Existing generic initializer proofs cover only their
construction/default paths, not these other operation families.

## Reproducible refresh and default-construction remediation

Run `python3 compiler/my-try/tools/registry_audit.py --inventory INVENTORY --output OUTPUT`.
The scanner preserves exact candidate dispositions, updates line numbers, adds new
pending candidates and retains missing/changed excerpts separately. Repeated refresh
is byte-stable. It uses the inventory's existing patterns; dynamic/computed names,
metadata and helpers outside stage/core PHP remain separate audit scope.

Object_Construction_Preparation::supports_default now relies on existing registered
`default_constructible` for vector/hash as it already did for unfamiliar providers.
Core metadata declares their existing native empty-state guarantee. Both modes/O0/O2
execute implicit vessel/vector/hash fields; absent provider opt-in rejects. Factory
call counts prove implicit native construction is distinct from explicit [] factories.
Cleanup and transfer gates remain open: neither copying, indexing nor default
construction is evidence of reset or task-transfer permission.
