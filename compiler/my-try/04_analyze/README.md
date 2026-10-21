# Analysis
Doc Status: supporting

[Collection](../03_parse/collect/) belongs to parsing and is called directly by the parser, not run as a separate pass.
It reuses existing declaration identities and registers new declarations as they
are recognized. File/member/signature scopes are private to their worker; global
function/type registration goes through `task_synchronize` and `Scope_Publication`.
File executable variables remain local. Unresolved occurrences retain syntax and
scope context for resolution after all parsing/collection workers have joined.

Collected identities are specialized records in [03_parse/collect/structures.php](../03_parse/collect/structures.php):

- `collected_name` holds shared occurrence provenance, revision/change state and
  accessors. Each concrete record owns one typed syntax link exposed by `syntax()`.
- `collected_declaration` adds reconciliation/publication state. Functions, constants
  and source types extend `collected_definition`, which owns a preparation-work slot.
  `collected_type_definition` groups structs and enums for the shared source-type
  association, publication and retirement paths. Enum declaration work resolves
  backing, exact case values, storage and capabilities before case-reference use.
  Fields, parameters and explicit local variables have distinct concrete records;
  members are prepared with their enclosing definition and locals with their body.
- `collected_reference` groups variable, function, field and type lookups without
  declaration state. `collected_variable_write` is a separate unresolved-write
  role: first-write declaration versus assignment is decided during preparation.

`collected_name` declares retention, publication and preparation-owner queries
abstract so their polymorphism is explicit in both compiler routes. Declaration
records report their existing state; the declaration branch defaults to no work,
while definitions return their existing preparation slot. References and unresolved
writes return false/false/no work. This adds no state or ownership edge and does
not make ordinary methods virtual. The portable dispatch proof extracts these
query bodies from the real hierarchy; v0.2 still rejects the raw null return in
no-work implementations, independently of virtual dispatch.

Preparation never replaces a write occurrence with a declaration record. Its existing
identity is used for inferred storage and retained by later references. Scope indexes,
syntax backlinks and dependency facts continue to reference these same objects;
there is no second symbol graph. The collector owns allocation/registration and
workers own resolution, preparation and lifecycle decisions.

During parsing, participating syntax nodes forward `collect()` to typed collector
methods. Plain variable writes use `collect_write()`; member/index bases use normal
read collection. The collector reuses declarations, registers new identities and
publishes globals under the existing lock. Typed `previous_*` queries select retained
syntax before the parser updates it. No collection pass or kind-based factory exists.
The existing end-of-parse inventory refresh invokes each record's `index_collection()`
hook; the collector maintains the lists and retires obsolete body occurrences.

`kind()` computes the existing enum classification from the concrete class. It is
retained for diagnostics and parked LLVM consumers, not stored as a mutable tag
or used by the parser/collector. Parameters and explicit variables share its
legacy `variable_declaration` classification while exposing different typed syntax.
Generic syntax access and optional preparation-owner access support mixed inventories;
consumers with a known role narrow the collected record and use its typed syntax.

Parsing ends before resolution. After its successful join, standalone `prepare()`
selects changed declarations and affected bodies and retains unaffected facts.
Before preparation, the lifecycle coordinator orders semantic retirement followed by
frontend-owned collected/index disposal. Combined `sync` uses the same retirement
protocol after its successful parse join. See the [incremental strategy](../docs/planning/incremental_strategy.md).

[prepare/](prepare/) owns the active backend-neutral semantic direction:
`Preparation_Worker` schedules typed declaration/body work; semantic algorithms live
in `Declaration_Preparation`, `Body_Preparation`, `Expression_Preparation` and
`Type_Preparation`. `File_Preparation` provides the standalone file entry into that
same scheduler. Prepared facts remain attached to AST specializations.
Completed-file records reference that syntax; they do not own token-indexed fact maps.
[The C++ backend](../05_backend/cpp/) currently consumes those shared facts.

Shared [type definitions](../compiler/types/) live at the application level and language
definitions are installed when Model initializes its language scope. Shared
[scope representation and plain lookup](../03_parse/scopes/) live with parsing.
Analysis-owned `Observed_Lookup` in [prepare/lookup.php](prepare/lookup.php) traverses
those same memberships while registering visited-pool dependencies. Shared lookup
has no preparation-context parameter and cannot subscribe semantic consumers.

The old name-resolution and template-checking stack is parked under
[the LLVM backend](../05_backend/llvm/README.md). Its `LLVM_Legacy_*` workers and
`llvm_legacy_prepared_names` are retained only for existing LLVM regressions; they
are not shared semantic owners.

The intended destination is one backend-neutral semantic preparation path. New
semantic work must extend `File_Preparation` and specialization-attached facts,
not independently add semantics to the parked LLVM stack. Before LLVM development
resumes, review and adapt it to consume shared facts, retaining only LLVM-specific
lowering in that backend. This isolation does not merge the implementations or
change language support, fact layout, or retained storage.
