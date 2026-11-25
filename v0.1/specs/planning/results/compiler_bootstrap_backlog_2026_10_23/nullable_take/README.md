# Capability-based nullable take checkpoint
Doc Status: supporting

Reuse: nullable formation already requires value_storable; copyable_value includes
copy assignment under specs/metaprogramming_contract.md. Take retains exact writable
output admission and source snapshot/copy behavior. The scalar/enum gate is removed;
explicit payload copy admission now makes the operation requirement visible. No
new capability or payload-name whitelist was added.

Runtime nullable<T> already copies generic payloads. Optional classes/interfaces erase
to shared handles, so php::take adds that representation overload with identical
presence/copy/unchanged-on-absence behavior. The initial concrete proof exposed an
output transport mismatch. CPP_Wrapper_Transport::payload now determines whether
take passes a concrete payload output or native handle storage. Both caller address
selection and adapter unpacking use the same query; no semantic inference in emission.

/tmp/nullable-take-03 passes 17 tasks: both-mode vector/hash/nested provider, struct and
class/interface execution; exactness/noncopyability/explicit-owner rejections; retained
copyability edit/reject/revert/reset. The unfamiliar tray<tray<int>> uses an existing
registered provider fixture. /tmp/nullable-take-result-regression-01 passes all 23
conditional Result extraction tasks. /tmp/nullable-take-runtime-01 compiles/runs the
existing runtime helper fixture extended with shared absence, identity and self-aliasing.

/tmp/nullable-take-portable-04 passes PHP, legacy native (portable task 21.71 s), ordinary
v0.2 (19.94 s) and concrete v0.2 (22.88 s). It checks PHP array/native vector copy behavior
and shared class identity using the existing take_nullable spelling. Earlier fixture
attempts exposed unsupported nullable generic local comments and used a free function
outside the converter's authoring subset. The final fixture uses a static method with
supported ?array parameter annotation. The nullable local grammar gap is recorded in
the backlog; it is not fixed or claimed covered by this source adaptation.

/tmp/nullable-take-inline-01 additionally passes both native modes for nullable
value<Class> copies (13.77 s ordinary / 18.44 s concrete).

Bootstrap /tmp/compiler-bootstrap-nullable-take-01: 77.21 s wall time /
341,376 KiB RSS (333.4 MiB) / 312 MiB PHP allocation. Preparation still fails first
on required file-field initialization. Aggregate diagnostic texts are unchanged
from the prior conditional-take checkpoint. No fatal/OOM, emission or native compiler
rebuild. These early-failure measurements do not establish full-bootstrap performance.
 Nested Result<nullable<T>>, runtime empty-handle
contracts and the broader backlog/CH15 audit remain open. No native compiler rebuild
or full-suite claim is made.
