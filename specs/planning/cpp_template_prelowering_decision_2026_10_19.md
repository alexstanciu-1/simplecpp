# Decision: concrete specialization within the existing C++ backend
Doc Status: planning

Date: 2026-10-19
Status: agreed architectural direction; first bounded integer/vector slice implemented.
See [current coverage](../../compiler/my-try/docs/architecture/cpp_template_prelowering.md).

## Decision

Keep one C++ backend operating on the existing AST and settled prepared facts.
Introduce a dedicated C++ lowering configuration with `prelower_templates`, supplied
through the CLI/API. The existing template path remains available for comparison.
No additional semantic stage, replacement AST, AST rewriting or generated-C++
postprocessing is introduced.

The backend orchestrates concrete specialization requests. A dedicated backend
specialization engine owns their identity, names, dependencies and generated
implementations. It consumes existing canonical types and capabilities; it does not
infer source semantics or interpret arbitrary C++ templates. Enrich shared prepared
facts only for demonstrated missing semantic information needed by either emission
path. Target representation choices remain backend-owned.

Small `if (prelower_templates)` branches are acceptable. Each branch delegates to a
named method unless its implementation is only one or two lines. No prerequisite
emitter refactor or new common abstraction is required to start.

## Capability-driven refinement — 2026-10-19

The generalization target is a runtime-owned, capability-driven contract consumed
through the existing type registry and backend bindings. `vector` and `hash` already
come from runtime metadata; do not duplicate their definitions, requirements or
capability propagation in a specialization registry. Extend existing semantic owners
for genuinely missing capabilities and existing backend bindings for C++ ABI details.

The first slice's handwritten integer/vector implementations remain measured proof,
not the architecture to expand family by family. Generate concrete application
interfaces and separately compiled implementations that may use original runtime
templates privately. Application inputs should ultimately expose no templates;
templates inside cached support are allowed. Concrete layout, lifecycle, nested values
and argument/result transport require explicit contracts, not just aliases or object
precompilation. The [family-by-capability matrix](runtime_template_capability_matrix_2026_10_19.md)
records current facts and proposed extensions without installing another registry.

## Ownership and request contract

| Owner | Responsibility |
|---|---|
| Shared preparation | Source type identity, capabilities and settled semantic decisions |
| C++ lowering configuration | How existing facts are represented and emitted |
| Existing C++ emitter | Request specializations and emit consumers using their bindings |
| Specialization engine | Deduplicate requests; generate concrete declarations, definitions and dependencies |
| Existing publication coordinator | Publish the complete consistent artifact set and acknowledge changes only after success |
| Existing native builder | Compile generated implementation units with Clang; cache objects and link |

A request conceptually says: “I need this canonical vector type with these
operations.” Recipes are family-based and consume canonical arguments; `vector<int>` is a test,
not a hard-coded implementation. Concrete integer representations derived from
registered width/signedness are included from the start. Review all runtime template
families early and record explicit implementation/discussion/retention decisions.

Initially every admitted specialization generates all methods in its
supported family; selective methods are deferred.

Return a dedicated small-footprint specialization-reference structure, ideally a
single engine entry ID. The engine entry owns the C++ name, artifact dependencies,
coverage and generation state. Do not copy those collections into every reference.
The ID is scoped to its owning engine lifecycle, not a durable cache key or generated
symbol. Reused emitter fragments must not retain dangling invocation-local IDs.

Names are readable and scoped, for example `scpp::internal::vector_int`. Namespace
separation and deterministic collision disambiguation prevent conflicts with source
classes, equal short names in different namespaces and distinct representations.
Obfuscation is not a goal. Names must not depend on discovery order or transient IDs.

The engine emits real concrete C++ classes and functions. Type aliases to existing
templates and explicit template instantiation are not the selected implementation.
Clang receives ordinary headers and implementation files and compiles the latter to
objects. Ordinary C++ destruction, exceptions, inheritance and virtual dispatch remain.

## Completion and incremental behavior

Returning a reference/name is distinct from completing its artifacts. Start with
synchronous generation. Complete all requested artifacts before publication becomes
ready; generation failure must not publish partial readiness. Background generation
is a possible later implementation, not an initial thread or scheduling requirement.

Separate specialization/type identity from implementation freshness. Relevant
representation settings affect identity; dependency changes and implementation
version affect artifact validity. A method-body edit must not gratuitously rename a
concrete type. Lowering-option changes invalidate affected emission without requiring
semantic preparation to rerun merely because output representation changed.

Use existing content-aware publication and native object caching. Do not create a
second native cache or a second semantic capability registry. Initially rebuild the
engine's request bookkeeping as needed; retaining it across invocations requires a
separate demonstrated benefit and correct retirement/fragment-reuse handling.

## Evidence and initial boundary

Follow the [whole-accounting experiment](results/accounting_template_free_2026_10_18/README.md):
concrete declarations plus grouped `base.cpp`, `containers.cpp` and `objects.cpp`
support implementations, ordinary application units, no PCH for the concrete path.
Keep this grouping initially rather than introducing one native process/object per
specialization. Debug information is off by default and explicitly requestable;
source diagnostics and runtime checks are independent.

The experiment measured compile-and-link totals of 3.518 s / 7.098 s at O0 / O2,
versus 22.053 s / 45.439 s for the improved-PCH baseline, with debug enabled in both.
It passed 998 accounting checks. This validates concrete implementation generation,
not the proposed production engine or a universal speed ratio. Runtime representation
also changed; runtime throughput and full production semantic equivalence were not
established. Experimental diagnostic/ABI limitations are not production contracts.

Non-goals: full C lowering, direct LLVM work, new ASTs, a general C++ template
interpreter, selective-method dependency closure, background workers, a new native
cache, a complete debugger, or wholesale runtime replacement.

[Implementation plan](cpp_template_prelowering_plan_2026_10_19.md)
