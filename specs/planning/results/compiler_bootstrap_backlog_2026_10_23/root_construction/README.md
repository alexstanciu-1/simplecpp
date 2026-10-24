# Collection root constructor promotion — 2026-10-24
Doc Status: planning

Base 2d8ffabb1 plus candidate hashes. User approved promoting collected_file root.
Reuse analysis: existing public promotion, set initialization and shared-handle
transport suffice. Parser already creates root and sets its span before allocating
collection. Pass that same root at the original allocation point; remove immediate
body assignment. Keep revival root updates and collector finalization unchanged.
Ownership/provenance documentation follows the promoted field. Adapt actual manual
fixture allocations. No converter/runtime/language or source_scope changes.

## Proofs

Extended tokens_construction.py uses production collected_file and parsed_file
constructors. PHP, legacy and ordinary/concrete native modes pass; native task times
28.99/32.08/31.76 s. Root dependency is a small fixture record; verifies exact shared
root, visible mutation, replacement mirrors and independent old-root retention.
Existing token proofs and bounded Parser_Run prefix remain covered. Native fixture
is not a full native compiler build or full parser proof.

Actual ast, ast_invariants, member_owners, token_cleanup and indexing tests pass.
Incremental preparation passes; added assertions prove deletion/reappearance keeps
the same collection, replaces retired root and preserves exact mirror identity.
model fails in parked LLVM preparation with Object not found; enum_model fails on
a retained-type task_synchronize call outside an unordered work callback. Isolated
HEAD production/test overlays reproduce both failures. Neither fixed or counted
passing. Shared runner uses CLI fallback because FPM sockets are unavailable.

## Bootstrap

73.81 s wall / 355260 KiB peak RSS (346.93 MiB) / 324 MiB PHP heap.
Task 78.71 s includes conversion/setup. First diagnostic advances from root to
runtime_metadata requiring direct initialization. Aggregate unique diagnostic set
is unchanged: other owners still have root initialization requirements. This proves
progress past the audited collection constructor, not removal of all root errors.
Stopped at runtime_metadata, with source_scope also still outstanding. No complete
bootstrap claim.
