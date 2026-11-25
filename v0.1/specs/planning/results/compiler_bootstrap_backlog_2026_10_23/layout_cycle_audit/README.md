# Bootstrap layout cycle audit — 2026-10-25
Doc Status: planning

Revision 77f853ce3; no production changes. Host-only instrumentation reruns the saved
converted compiler input and dumps dependencies only at the existing cycle rejection.
The saved graph omits generated text/layout statements; the original full capture is
/tmp/bootstrap-layout-cycle-01/graph.json. Seven strongly connected components exist;
226 headers remain unresolved, including downstream dependents. Seven components do
not imply seven independent compiler defects.

The shortest demonstrated cycle is runtime_type_spec -> Storage<runtime_type_spec>
-> runtime_type_spec. Additional cycles involve weak source links and object-keyed
hashes. CPP_Types::implementation_argument requests complete source payloads; opaque
layout dependencies retain those headers. Storage native layout can be instantiated
with an incomplete record, but the current mapping demands its completed source
header. Weak currently lacks the pointee metadata that shared/unique have; pointee
also controls other transport behavior, so adding it blindly is not a safe fix.

Small proof: Node has Storage<Node> children. Ordinary v0.2 publishes, builds and
runs. Concrete v0.2 rejects with the same header cycle. Direct runtime C++ checks
size/alignment-relevant instantiation and lifecycle traits before Node is defined,
then constructs/appends after definition and runs successfully. The first harness
incorrectly expected ordinary rejection; the final harness records ordinary success.

Instrumented bootstrap 1:41.84 / 765104 KiB RSS / 726 MiB heap; preparation and
validation pass, emission fails as before. Logging overhead included. No native
compiler built; minimal native programs are diagnostic proofs only.
