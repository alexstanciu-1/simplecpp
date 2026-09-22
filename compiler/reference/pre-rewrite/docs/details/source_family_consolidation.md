# Source/native family consolidation
Doc Status: supporting

Reviewed on 2026-09-21, after the four bounded
[integration gates](source_family_integration_plan.md). This review covers automatic
source-record lifecycle exports, native specialization preparation, package import
and final source linkage. Custom lifecycle exports remain deferred.

## Findings and changes

1. **Normalize source imports at package acceptance.** `Package_Adapter` already
   validated the project receipt, but discarded its normalized import map.
   `Project_Exports` then parsed and validated the same receipt again. The package
   now retains exact symbol-to-operation associations in `source_imports`; backend
   preparation consumes those records. Reopening a package still revalidates its
   artifacts and receipt before sharing an unchanged snapshot.
2. **Capture current layout dependencies as one batch.** Backend preparation used
   to capture a dependency graph for each package export, including repeated source
   types in nested native families. It now captures all roots together and skips
   repeated checks of the same accepted layout object. Distinct snapshots still
   undergo lineage and reachable-dependency checks; equal sizes never establish
   type identity.
3. **Correct navigation and status.** The backend call map still described compiler
   routing and final linking as future work. It now describes the implemented path;
   the integration plan distinguishes completed gates from deferred capabilities.

These are local ownership and repeated-work cleanups. No source behavior moved to
Clang, and no family names or concrete source types select compiler behavior.

## Reviewed ownership and scheduling

| Boundary | Conclusion within the supported subset |
|---|---|
| Readiness and layout | `Source_Export_Coordinator` consumes ready concrete arguments and shares the update's `Layout_Coordinator`; early and final preparation use the same owner. |
| Source operations | `Source_Export_Preparation` obtains complete plans from existing lifecycle composition. Fixed workers prepare ABI associations; `Source_Export_Join` accepts selected provenance. |
| Native specialization | `Compiler_Bridge` carries direct and transitive source obligations into project-scoped preparation. Native joins precede publication; receipt capture occurs before releasing the publication reservation. |
| Package import | The adapter owns serialized receipt validation and normalized import records. Payload bindings retain canonical source definitions. Final reservations reopen packages and hold reader leases through linking. |
| Backend and emission | One `source_linkage` supplies common lifecycle ABI/emission roots. Stable entries forward to source-owned implementations; final module acceptance rejects missing or duplicate definitions. |
| Retained state | Accepted exports/layouts share fixed records and dependency graphs. Update coordinators are not retained as worker inputs; one body increment reuses native artifacts without mutating prior snapshots. |

No further blocking consolidation was identified in this bounded path. Fixed work
units and joins preserve the intended future worker boundary; this review does not
claim actual multithreaded execution or batch publication rollback.

## Validation

Four focused fixtures passed in parallel:

- [Layout preparation](../../tests/05_generate_code/prepare_backend/layout_preparation.php): fixed dependency views, shared preparation and provenance rejection.
- [Source exports](../../tests/05_generate_code/prepare_backend/source_exports.php): exact identities, six-role contracts, private ABI joins, rebuild binding and body reuse.
- [Source/native execution](../../tests/integration/source_family_execution.php): direct/transitive imports share exact operation plans; unchanged package reopening retains contracts and changed receipts reject. Managed, nested and cleanup-free records execute with balanced allocation counts, unchanged old snapshots/native artifacts after one body increment, and O0/O1/ThinLTO linking.
- [Ordinary runtime-family consumption](../../tests/integration/provider_family_runtime_types.php): the ordinary package path remains covered.

Changed PHP syntax, braces/doc-comments, whitespace and documentation file links
were checked. The previous full checkpoint remains 107 compiler fixtures plus
170 ordinary and 54 family preparation checks; those full suites were not repeated
for this local consolidation. No execution-performance improvement is claimed.

## Next boundary

Discuss custom source lifecycle exports before extending their eligibility. The
compiler must continue to own custom bodies and complete field composition;
native adapters must call their exported operations. Generic source-list migration,
owned parameters/general movement, pointer families, exception cleanup and broader
incremental recovery remain separate documented extensions.
