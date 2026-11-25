# Source template arguments through shared generated definitions
Doc Status: supporting

Date: 2026-10-19. Branch: `v0.2/prelower_templates`. Base: `fe3edef5` plus
this slice's header-dependency and source-argument binding changes.

## Implementation

Concrete template support can consume source-defined records/classes with concrete
fields. It includes the one generated source definition, containing layout and method
declarations. Source method bodies remain in their existing implementation units;
they are not copied into the specialization support. This does not create a second
class definition, semantic inventory or registry.

Physical bindings already provide required headers. Specialization entries now use
those headers as layout dependencies, replacing the redundant per-type specialization
name list. Header artifacts carry the declaration emitter's existing dependency facts.
The existing engine orders layout batches through only the generated headers reached
by its layout requirements. It does not re-walk source fields/signatures, parse C++
text, or treat source method bodies as layout work. The native cache is unchanged.

Inline records remain actual source records inside native containers. Nominal shared
class arguments select a concrete owner over the existing runtime shared handle.
Selection uses canonical storage facts, not names such as vector, parcel or MyClass.
No unrequested unique/weak/value representation is produced by this proof.

## Proof

```bash
python3 compiler/my-try/tests/prelower_substitution.py --source-arguments --results FRESH_DIRECTORY
python3 compiler/my-try/tests/prelower_layout.py --results FRESH_DIRECTORY
```

The first command uses real prepared source declarations and the compiler's published
support. Cases include `vector<Record>`, an independently registered `parcel<Record>`,
and `vector<MyClass>`. Record fields themselves contain concrete vectors, nested
vectors and a registered template. The fixture driver supplies container operations;
this does not yet establish general compiler-generated runtime member forwarding.

The native/direct and composed variants match exact allocation/byte/free and payload
construction/copy/move/destruction counters at O0, O2 and ASan/UBSan O1 with optional
copy elision disabled. PCH and debug symbols are off; LeakSanitizer is disabled.

```
source-values      12 1416 12 2 8 1 11
source-shared       5   96  5 0 0 0 0
source-references   0    0  0 0 0 0 0
workload           15  832 15 2 4 1 7
references          0    0  0 0 0 0 0
copy-failure        5  328  5 0 1 0 1
```

The checks prove independent record copies, real nested record/field references,
shared class identity through copied containers, record-copy failure cleanup, and
zero measured extra boundary allocation/copy work. Existing request-order, fragment,
option-switching and content-restoration checks remain active.

A source method-body edit leaves every generated type header and specialization file
byte-identical. Native rebuilding compiles one source object, reuses five others and
all four layout batches. A provider alignment edit regenerates affected layouts;
restoring original content reuses all six objects, four layouts and the executable.
See [summary.json](summary.json).

A source method signature returning `vector<Self>` demonstrates the remaining physical
header/layout cycle boundary. Generation rejects before publishing partial artifacts;
restoring the prior source recovers identical output. Unrelated header cycles are
excluded from layout scheduling. This is a backend representation limitation, not
a newly imposed source semantic rule. Supporting those graphs needs more precise
complete-definition versus declaration dependencies.

## Validation and limits

The original single-batch native layout regression passes. The portable-PHP checker
passes the touched production files; it does not prove a native compiler rebuild.
Shared runner uses 12 slots and CLI fallback when sandbox FPM sockets are unavailable.

Initial attempts corrected a fixture newline and an incorrect test expectation that
failed C++ output would remain visible: the existing compiler correctly clears it.
No compiler behavior was changed for that assertion. Final checks include cycle
recovery and exclusion of unrelated headers from layout work.

Evidence: `/tmp/prelower-source-arguments-verified`,
`/tmp/prelower-source-arguments-layout`, `/tmp/prelower-source-arguments-checks` and
`/tmp/prelower-source-arguments-portable-final`.

Full ownership access interfaces, source construction/calls through concrete handles,
general container forwarding, incompatible native-provider signatures, recursive
header/layout graphs and whole-accounting coverage remain unfinished. Source method
implementations are compiled in their source units; this slice does not introduce
method-level dead-code elimination. Existing comparison paths are retained.
