# Native field places across the prelower boundary
Doc Status: planning

## Finding

The existing backend already owns field selection and receiver retention in
`CPP_Declarations::generate_field_access` / `bind_storage_receiver`. Prepared
field facts supply the selected property, receiver storage and result type.
`CPP_Index_Write` already re-resolves container paths after key/RHS effects.
None of these decisions belongs in the specialization engine.

The missing physical distinction is an owned application facade versus a borrowed
address of an original native object. `cpp_assignment_sequence::value` currently
means a C++ value/place expression, and `CPP_Runtime_Operations::address` obtains
an address from that expression's representation. A native field cannot be exposed
as `Facade&`: the facade object does not exist there. Copying it into a facade
would break writes/identity and add work at the boundary.

## Proposed bounded contract

Extend the existing transient operand/receiver binding to explicitly distinguish
an ordinary represented expression from a borrowed native address. Keep its
canonical type and access mode. No new registry, AST, semantic lifetime model or
layout cache is needed. Do not change the meaning of an untagged string in place.

Field projection requests retain the existing settled field facts, as method and
construction requests retain their respective facts. Generated native bridges
select the actual native field and return its address with the admitted access.
The calling scope owns any required shared-handle retention; the address does not
own or extend a lifetime independently. Inline receivers are borrowed. Reads into
owned results use original native copying only where source semantics demand it.

First integrate plain field reads/writes and fields used as indexed-container
roots. Reuse existing receiver sequencing, conversions and indexed path handling.
The engine only deduplicates ordinary requests. A field's family never selects
engine behavior. Whole native field addresses remain valid only under the existing
prepared lifetime rules; do not keep interior container-element addresses across
key or RHS evaluation.

## Scope and validation

This touches the C++ operand/sequence contract, field emission, assignment emission
and indexed consumers. It does not change shared preparation or runtime layout.
Prove shared retention across receiver replacement, inline mutation, nested field
and container writes, readonly access, exception cleanup, demand/replay, exact
direct/ABI allocations and payload-copy counts, and sanitizer execution.

Reference arguments and reference results involving an application function need
an explicit follow-up: its current `Facade&` signature cannot accept a native field
address. Do not fabricate a reference or silently copy. Static/provider properties,
compound updates and escaping borrows are not implicitly covered by this slice.

The smallest alternative is copied property reads only, explicitly rejecting
mutation and native references. That is narrower but would not establish the
general place mechanism needed for nested mutations. Recommend the bounded
borrowed-address contract above, then extend its consumers deliberately.

The [earlier representation audit](prelower_representation_audit_2026_10_19.md)
requires agreement on a general borrowed representation before coding changes
across owners. This proposal records the concrete next decision; it is not an
implemented protocol.
