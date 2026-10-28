# Nullable/result migration slice

Doc Status: planning
Date: 2026-10-28

Requested follow-on: implement nullable and result_* after the completed scalar,
string and vector goal. The prior implicit one-layer extraction agreement remains
in force. This takes precedence over continuing the separately recorded hash review.

Owners: runtime metadata defines families/defaults/extraction/error contracts;
preparation resolves published payloads and rejects unsupported dependencies;
the existing emitter generates concrete public classes and lifecycle wiring;
private carrier_semantics owns state access/checks. Compiler source admission,
sequencing and take binding expressions remain outside this runtime slice.

Implementation includes nullable, result, result_or_false and result_or_bool with
explicit boxing, copied extraction, structured error payloads and no lifted operator
matrix. Boolean payloads and sentinel states use distinct constructors/factories.
Keep result default success; moved-from states return to family defaults. Keep
scpp::error_t namespaced because of the platform typedef collision.

Non-goals: null/nested/move-only payloads, shared-object lifting, carrier arithmetic
with carrier-valued results, interior references, generic delegated members, source
compiler integration and a new runtime-wide error ABI. The exact implemented
contract and reviewed reuse are in ../carrier_slice.md.

Validation gates: default composition and failed access, value ownership, explicit
metadata policy in a reduced publication, template-free public closure, full existing
suite, fresh standalone UBSan and ASan ownership checks with known environment
limits recorded. Commit only validated handwritten sources/metadata/tests/docs;
generated artifacts stay local and ignored.

Implemented checkpoint: all 24 Release and standalone UBSan tests pass. The carrier
ownership/error consumer also passes ASan+UBSan using libstdc++, with the known
LSan environment limitation. Arithmetic signature count remains 151; carrier
extraction reuses existing operators instead of generating lifted signatures.
