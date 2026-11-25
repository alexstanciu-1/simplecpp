# Retained checked-body queries
Doc Status: supporting

`check_bodies/Checked_Body` owns fixed membership for typed values, calls,
statements, scopes, arguments, flow blocks and retained type/signature dependencies.
Construction copies containers and shares immutable rows. Exact callable input,
name bindings and optional local types establish provenance. Construction is not
body checking: the worker and acceptance phase must establish semantic validity.

Value, call and scope queries use one-based IDs; statement, argument and block
access use zero-based positions. Dedicated count/access methods replace public
arrays. Consumers obtain semantic type meaning only through retained dependencies.
Missing/unresolved dependencies fail explicitly. Conversion and operation queries
require earlier operand IDs and exact operation result/operand type IDs. Call
argument queries preserve the original one-based position within a contiguous
zero-based range. Local passing comes from the captured signature; ordinary locals
fall back to value passing after validating their name-table membership.

`Signature_Dependency` is the checked-body owner for one canonical signature
representation and its parameter type IDs. The migrated type model stores signature
members in a canonical store rather than inline; capture copies their IDs before
handoff. Queries therefore retain no mutable Type_Store. Provider/storage payloads
remain exact shared objects for allocation-effect queries. `signature_for` returns
this dependency view; consumers read its representation and parameter accessors.

`place_type` preserves the prototype's contextual field bounds, array element
identity, dynamic-storage element identity and integer-index checks. Field target
type selection is a producer responsibility; this query checks the field ordinal
and existence of the projected type, without claiming a second semantic field
resolution pass. Explicit guards keep invalid shapes away from kind-specific
accessors on the native target.

The focused cases use parsed source ownership with synthetic canonical type/body
associations to exercise query contracts. They do not prove an end-to-end checked
source program. They cover scalar/borrowed parameters, signature capture, immutable
membership, operation/conversion dependencies, argument ranges, record/array/dynamic
storage projections and missing/unresolved rows. Full worker, join, lifetime and
evaluation-order execution remain separate.

The prototype on-demand `to_array` projection is not discarded: its remaining
explicit serializer is tracked in the debug projection inventory. There is no
reflection-based portable debug dump or persisted-cache contract in this slice.
