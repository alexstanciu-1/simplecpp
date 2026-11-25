# Bootstrap concrete-layout dependency proposal
Doc Status: planning

Status: approved by user on 2026-10-25; implemented with focused proofs and successful PHP-hosted C++ publication. Baseline 77f853ce3.
[Evidence](results/compiler_bootstrap_backlog_2026_10_23/layout_cycle_audit/README.md).

## Finding

Compiler source is admitted and project validation completes. Concrete prelowering
requires a topological order for native size/alignment probes and generated source
headers. Captured dependencies have seven cyclic components and 226 unresolved
headers (many downstream dependents). A minimal source class containing
Storage<Self> emits/builds/runs in ordinary v0.2, but concrete mode rejects. The
actual runtime Storage supports an incomplete element record at layout/trait probing.
This is a representation dependency limitation, not an invalid PHP object graph.

Existing CH15 Chapter 14 closeout debt already names template/handle incomplete-type
dependencies and per-binding proofs. Conservative dependencies now block bootstrap.
Do not redesign the compiler's recursive data model to evade this backend issue.

## Recommended bounded design

Reuse cpp_type_requirement, native type recipes, forward declarations, physical
specialization identities and existing layout batches. Distinguish what a provider
requires for layout probing from what its operation implementation needs.

Add an explicit per-template-argument native layout requirement to C++ binding
metadata, defaulting to complete. A declaration-only requirement must be proved
against the actual runtime's size, alignment and probed lifecycle traits. Keep this
independent of payload-versus-stored spelling, semantic capabilities, and pointee
metadata (which also controls pointer/member transport). Do not identify Storage or
weak by source name and do not set pointee merely to suppress a dependency.

Carry that fact through existing C++ binding recipes. The layout mapping emits
required forwards and only genuine layout includes. Operation/constructor/destructor
implementation mapping retains complete definitions wherever necessary. Reuse
CPP_Opaque_Representation's layout aliases and implementation separation; do not
introduce a second type registry, graph owner or hardcoded layout sizes. Keep the
existing cycle rejection for genuinely recursive by-value layouts.

Prove Storage/Keyed_Storage/Key_Storage_List and weak bindings individually before
opting them in. Review the remaining object-keyed hash and composed-container edges
using their actual native argument representations; a safe direct payload argument
does not establish safety for arbitrary nested value types. Do not promise that one
Storage metadata row clears every captured cycle.

## Scope, cost and validation

Owners touched: runtime C++ binding schema/loading, prepared binding recipe data,
CPP_Types physical dependency selection, opaque layout/implementation publication,
and focused tests. This is a medium coordinated backend/binding change, larger than
the JSON correction and comparable to or somewhat larger than typed construction
facts. No Simple C++ ownership, nullability, cast or initialization contract change.
Main risks: incomplete types reaching trait queries or operations too early, and
retained specialization replay losing dependency requirements.

First vertical slice: recursive Storage<Self> plus mutual weak links, ordinary and
concrete native execution, complete-type-required negative control, retained/fresh
output agreement, and native per-binding lifecycle/layout proof. Extend analogous
proved bindings, then retry the measured compiler bootstrap. Stop if deeper layout
representation or lifecycle redesign is needed; never drop all dependency edges or
replace the full compiler acceptance criteria with an ordinary-only build.

This proposal extends the binding model across metadata and backend ownership areas;
compiler/my-try/AGENTS.md requires agreement on the new retained protocol before
implementation. No production fixes were made during this audit.

Implementation evidence: [layout requirements](results/compiler_bootstrap_backlog_2026_10_23/layout_requirements/README.md).
