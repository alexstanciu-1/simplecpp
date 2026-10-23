# v0.2 source self observer — first proof
Doc Status: planning

User authorized the v0.2-specific observer design using prepared construction and
existing specialization machinery, with v0.1 unchanged. Base a52fad24a plus this
slice; 796caa274 separately records the requested CH15 simplification debt.

## Implementation and reuse

Method_Preparation publishes owning acquisition separately from borrowed receiver
access and struct copying. CPP_Self uses the existing cpp_runtime_operation and
specialization/layout pipeline, following internal finally storage's precedent.
The native source_self_state holds a weak_ptr<void>; source_shared constructs via
the unchanged shared helper, then binds before publication. Ordinary and outlined
source-class allocation select this helper. Prelowered application classes use one
virtual template-free observer base with probed inline storage, never raw template
headers. Acquisition receives a payload pointer, locks the existing owner and aliases
to the adjusted receiver address. Source object copy/move construction starts unbound;
assignment preserves destination identity. No separate heap allocation for the observer.
Measured native state is 16 bytes on this host, excluding inheritance/layout overhead.

v0.1 shared_self.hpp, shared_p.hpp, memory.hpp and generators/php remain unchanged.
No general native adoption hook, method ABI rewrite, ownership promotion or owning
receiver-capture redesign is introduced. Source classes allocated outside the v0.2
construction bridge remain unbound; broad adoption coverage is still pending.

## Proofs

- /tmp/source-self-04: shared_self.py passes retained preparation/recovery (0.73 s),
  ordinary native (18.27 s), concrete native (17.99 s). Output
  9:9:12:0:12:1:17:2 proves inherited/interface retention, local/argument transport,
  identity-preserving mutations after original handles clear, final destruction,
  and explicit construction of a final derived class. Concrete native token gate
  remains enabled. These are build/task times, not runtime benchmarks.
- /tmp/source-self-runtime-01: source observer lifecycle/adjusted-owner tests pass
  (1.45 s), unchanged legacy shared_self control passes (1.32 s).
- /tmp/source-self-struct-regression-01: struct self preparation/recovery and both
  native modes pass. The obsolete class-self rejection was replaced with direct
  constructor/destructor acquisition rejections.
- /tmp/compiler-bootstrap-source-self-01: 75.87 s / 349960 KiB peak RSS / 320 MiB
  PHP heap. Removes Class self values require shared-owner retention support;
  adds no diagnostics relative to staged_marker. First syntax_node initialization
  blocker remains. No successful whole-compiler native build is claimed.

Initial source-self-01 ordinary output passed, concrete failed because acquisition
used handle-storage transport for a raw receiver. The correction uses borrowed
payload transport; source-self-02 and expanded 03/04 proofs pass. FPM socket startup
was unavailable, so the shared runner used its normal CLI fallback.

## Remaining acceptance

Full SELF-01 remains open beyond this requested first proof: native adoption review,
additional lifecycle/closure interactions, source-layout costs and broader integration.
Do not infer whole-program lifetime analysis or exact PHP cleanup timing from these
native proofs. Shared-struct retention remains deferred. Bootstrap is still blocked
on the existing independent preparation diagnostics.
