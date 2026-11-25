# Runtime and public API simplification audit

Doc Status: planning

Date: 2026-10-28. Side-conversation audit; not a change to the active runtime-core
completion goal or to language contracts. Only this new document was written.

## Handoff update — 2026-10-28

The user has now paused the runtime-core completion goal to restructure the runtime
in another chat, and requested that this audit be committed with the handoff. See
[runtime-core status and explicit resume instructions](runtime_core_completion.md).
The latest implementation checkpoint is `5a6d69f4e`; the findings, fingerprints and
counts below retain their original earlier inspection scope and have not been
revalidated against that checkpoint. Proposed goals below were not activated by
this documentation update. The opening description and historical inspection notes
record how this audit was produced, not the current goal state.

## Scope and evidence limits

Inspect runtime-core together with its public API, metadata generation and physical
publication. Identify duplicated behavior, duplicated exposure, competing mechanisms,
structural complexity and unnecessary dependencies. Evaluate internal use of public
types. Prepare two possible goals: consolidation, then a friendlier C++ public API.
Nested indexing/proxy design and module implementation audits are excluded.

The main chat may change these files concurrently. HEAD at the start and final
source inspection was `7ca84fd5f8ef38fb7b143642e1a4e5c5c156ed94`; the initial worktree
status was clean. After writing this report, HEAD had advanced to
`131bb55445041959dfb85a42c18b2aba7ac5f561`, with main-chat modifications visible in
`generator.cpp` and `prepared.cpp`. All eleven recorded evidence fingerprints still
matched at that final check. Those concurrent edits were not modified or audited as
a new slice. Findings are source-inspection evidence, not newly executed
behavioral or performance proofs. No builds, generator runs, tests, Git mutations,
or edits to existing files were performed. Existing tests are cited as evidence of
intent; their results were not rerun. Counts are a snapshot, not completion metrics.

Authorities consulted: `specs/spec_map.md`, onboarding and strict-language guidance,
`specs/architecture/runtime_layering.md`, runtime-design semantic-consistency and
structure rules, `runtime/specs/core_public_api.md`,
`runtime/specs/public_api_generation.md`, `runtime/specs/operator_generation_flow.md`.
Language contracts outrank native behavior and metadata. Consolidation must not
quietly adopt older native semantics in place of approved public promotion rules.

## Conclusion

There is actionable redundancy, chiefly in exposure/transport generation and
overlapping entrypoints. The traced public arithmetic and cast functions generally
reuse native behavior; they are not independent arithmetic/conversion engines.
The strongest direction is one operation description, one behavioral implementation,
and multiple deliberately thin spellings where necessary.

Public-only consumption is compatible with internal use of public types. In fact,
generated containers already do this. A universal second native type universe is
not a requirement of template-free public headers. Conversely, routing every private
primitive back through an out-of-line public wrapper is not inherently simpler.

## Current mechanism and scale

The paths inspected are:

1. `specs/config.json` + `tools/generate_operators.php` generate the private/native
   `include/scpp/generated/operators.hpp`, which uses native semantic helpers.
2. `tools/concrete_integers.py` separately generates concrete integer declarations,
   operator adapters and hash-key adapters from the concrete integer config.
3. `metadata/public_api*.json` + `tools/api_generator/` generate concrete classes,
   named operations, prepared operation plans and specialization units.
4. `tools/concrete_types.py` drives target probes and publication; it is not a second
   C++ declaration generator. `tools/public_package.py` seals artifacts.
5. Handwritten string/error/exception bridges and `tools/value_layout.py` predate
   the generic concrete-type transport path.

Snapshot: 415 files under `runtime/public` (565,587 bytes); 183 operator headers,
78 conversion headers, 36 identity headers and 20 access headers under its `scpp`
tree. There are 411 short compatibility forwarding headers under `include/scpp`.
The public specialization manifest lists 406 implementation units.
`include/core/runtime_core.cpp` directly includes 416 `.cpp` files, and CMake builds
the core from that bundle. These are logical units, not 406 separate CMake compile
jobs. The 27 files under `tools/api_generator` total 219,126 bytes; the four public
metadata section files total 216,360 bytes, excluding the root metadata file.

These counts expose organizational cost, not a measured runtime or compile-time
regression. Generated files do not carry the same maintenance cost as authored ones.

## Findings

### F1. Two concrete integer operation surfaces share a native authority

Evidence:

- `tools/concrete_integers.py` emits `operator+`, comparisons and compound members
  into `include/core/integers.cpp`.
- `include/scpp/generated/public_add_int64.cpp` emits `numeric_add(int64,int64)`
  through `detail::generated_operator_detail::add`.
- The former goes through native generated operators; those also reach shared
  operator helpers. Public wrapping/unwrapping is repeated.

Classification: confirmed redundant adaptation/exposure generation, not evidence
of two independent addition algorithms. Keep convenient operators and named lowering
entrypoints if both are useful, but generate their relationship from one operation
record. Preserve inline representation access and deliberate exception specifications.
Do not implement one spelling with another if that creates an avoidable extra
out-of-line call; choose one compiled operation plus appropriate thin aliases, or
shared private primitives, after measuring.

### F2. Constructors and checked casts are different contracts today

`include/core/integers.cpp` constructs narrowed native `int_t<Rep>` values.
`include/scpp/int_t.hpp` uses `static_cast<Rep>` in its integral constructor.
By contrast, `public_cast_int32_from_int64.cpp` calls `scpp::cast`, whose integer
path uses `checked_integer_cast` in `include/scpp/cast.hpp`.

This difference is intentional existing behavior: `test_concrete_integers.cpp`
explicitly checks narrowing, including uint64(256) to uint8 producing zero.
The named conversion registry is not a redundant unchecked constructor list.

Recommendation: classify representation construction, lossless promotion, checked
language cast and typed-boundary conversion separately in the operation inventory.
Decide the friendly C++ constructor policy before merging or removing overloads.
Changing constructors to checked casts also changes noexcept and existing consumers.
No semantic change is authorized by this audit.

### F3. Policy exists in multiple places, but not every difference is duplication

`specs/config.json` and the PHP operator generator own older native overload
participation. `metadata/public_api/global_operators.json` and `operators.cpp` own
normalized public operation planning. `public_api.json` separately describes
conversion source normalization and target exposure.

A concrete distinction: native `widened_int_rep_t` in `generated/operator_detail.hpp`
selects the wider of two same-signed native representations. Public metadata has
`same_or_maximum` promotion rules. Blindly replacing public planning with native
overload resolution would therefore not preserve the agreed rules.

Recommendation: one explicit inventory of source admission, promotion, representation
adaptation and execution. Generate public spellings from the agreed operation plan;
retain native primitives as execution machinery. Determine legacy consumers before
retiring native operator participation tables. This spans semantic metadata, native
operators and consumer migration and requires a separately approved slice.

### F4. Repeated foundational inline-storage machinery

`detail/string_access.hpp` and `detail/error_value_access.hpp` repeat placement
construction, laundered access and factory transport also present in
`detail/concrete_access.hpp`. Their layout/lifecycle assertions remain valuable.
String and error handwritten lifecycle adapters overlap the generic generator's
capabilities. `value_layout.py` separately probes string/error/exception storage,
while `concrete_types.py` executes probes described by generic generation.

Recommendation: converge on one representation/lifecycle/probe mechanism where
semantics fit. Keep specific byte borrowing, UTF-8 behavior and exception inspection
in their real owners. Do not turn exception_ptr inspection into a generic semantic
recipe merely to eliminate a file. Preserve direct construction of nonmovable
results, alignment assertions and nonthrowing destruction. Opaque inline storage
does not imply a separately allocated wrapper or duplicate live payload.

### F5. Internal use of public types is already a proven structural choice

`include/core/generated/vector_string.cpp` defines its private representation as
`vector_t<scpp::string>`, not `vector_t<string_t>`.
`families.json` expresses vector/result/nullable payloads using `${public:T}` and
hash storage using `${public:V}, ${public:K}`. Boxed mixed/dynamic retain their native
internal representations instead. This is already a hybrid model.

Consequences: public payloads can avoid element-by-element conversion, preserve
application concrete identity and reuse lifecycle operations. Internal generic
code may call out-of-line public payload operations; performance and linking must
be checked before expanding that approach. No claim of zero overhead follows from
the source structure alone.

### F6. Unnecessary reverse language dependencies

`include/scpp/generated/operator_detail.hpp` includes the PHP condition adapter and
calls `scpp::php::condition_truthy`. That adapter merely re-exports
`scpp::condition_truthy` from the shared authority. This is a concrete unnecessary
route through the language layer and conflicts with the documented dependency intent.

`include/scpp/vector_helpers.hpp` defines shared-looking vector primitives under
`scpp::php`; public vector recipes call them. Their signed capacity checks can
have source-facing significance, so this is an ownership review, not just a rename.
Move truly shared behavior to a shared owner and leave language aliases only where
needed. This is a good bounded consolidation candidate with low semantic scope.

### F7. Mirrored paths and tiny per-operation files obscure ownership

The public header, compatibility forwarder, generated implementation, metadata
recipe and core bundle entry may all represent one small operation.
`concrete_types.py` automatically emits a forwarder for each generated header.
This is physical duplication; the forwarder is not a second type definition.

Recommendation: make authored versus generated placement obvious. Keep one
installed public root; put generated private implementations under one recognizable
generated root, with a generated source inventory. Treat forwarding headers as an
explicit compatibility product with named remaining consumers and a retirement gate.
Do not remove them before private include paths and legacy consumers are migrated.
Consider focused family headers for operations, but compare include closure and
caller compile costs before replacing fine-grained includes with broad umbrellas.

### F8. Source layout and compilation ownership are separate problems

CMake's `scpp_runtime_core` currently compiles `runtime_core.cpp`, which aggregates
the generated operation files. Hundreds of short sources do not inherently yield
parallel core compilation or isolated incremental recompilation.
Conversely, compiling every short function separately could repeatedly parse the
large cast/operator dependency closure and make clean builds worse.

Recommendation: derive build inventories from publication ownership, then compare
a few bounded family shards with the current bundle. Keep the historical convenience
bundle only as an alternative build mode, never link it alongside the same shards.
Choose partitioning from clean/incremental measurements and archive/link behavior,
not directory aesthetics. No build-time estimate is claimed here.

### F9. Private header closure remains broad

`scpp/cast.hpp` includes scalars, strings, carriers, containers and boxed types.
`generated/operator_detail.hpp` similarly pulls many semantic families together.
Even a small public integer conversion implementation includes this broad cast root.
The consumer boundary hides that cost from application translation units, but does
not eliminate it from runtime compilation.

Recommendation: extract focused semantic primitives only at real ownership boundaries
(e.g. checked numeric conversion). Have broader dispatch reuse them. Avoid producing
a maze of microheaders. A numeric-only implementation inclusion probe and parity
tests would demonstrate whether a split earns its complexity.

### F10. Some overloads are aliases; others encode meaningful differences

Generated vector `index` and `operator[]` forward to `at`; `push_back` forwards to
`append`. These are confirmed alias surfaces, not duplicate container algorithms.
Vector members accept uint64 sizes while free source-shaped helpers use int64 and
reject negative counts. Copy/move append overloads preserve different ownership costs.
String pointer and owned-string overloads likewise need not have the same allocation
behavior. A simple count of overloads cannot determine which to remove.

Recommendation: record aliases declaratively; group overloads by semantic operation,
transport and source role. Remove only routes with no distinct contract or justified
consumer benefit. Prefer one implementation for equal semantics.

### F11. Publication/proof complexity has a real purpose

`public_package.py` hashes SDK inputs/artifacts. `concrete_types.py` validates imports,
ownership, ABI and public file checksums before publication. Generic code asserts
actual layout and lifecycle. These checks support inline opaque objects and
application-owned specialization reuse; deleting them would remove guarantees.

There is room to consolidate manifests/probe execution and reduce broad invalidation,
but dependency-scoped fingerprints require evidence of complete dependencies first.
The generator tool links runtime-core; moving all foundational generation into it
must retain a documented bootstrap route from checked-in or previously built artifacts.

The Python naming Registry already delegates canonical resolution to the C++ tool.
It is not an independent active canonical resolver merely because a Python naming
file still exists. Remaining parsing/bridge compatibility helpers need consumer
tracing before removal.

Tests for native semantics, public transport, allocation/lifetime and publication
prove different properties. Their existence is not itself redundant testing. Future
consolidation can share case data, while preserving independent public-only compile
and reuse tests. This audit did not exhaustively classify all test cases.

## Dependency-direction decision

| Model | Benefits | Costs / risks | Assessment |
| --- | --- | --- | --- |
| Fully independent native runtime, public adapters above it | Simple acyclic layer model; private generic operations can inline; compatibility straightforward | Persistent scalar/transport duplication; conversions at boundaries; public and native composition may differ | Useful for some native engines, not a universal requirement |
| All runtime code uses public facade operations | One consumer-facing type family; fewer adaptation sites; runtime exercises its own API | Wrapper-to-native-to-wrapper cycles, out-of-line inner-loop calls, bootstrap coupling; apparent simplicity can hide adapters | Too broad as a blanket rule |
| Shared foundations and selective internal public-type reuse | Consolidates concrete identities and common behavior; retains efficient private algorithms; matches existing container composition | Requires explicit dependency and representation ownership per family | Recommended direction to prototype |

Proposed rule: consumers see only public declarations. Runtime implementation may
use public concrete types and call public operations when those are the natural
owners. A public wrapper and its private implementation must not call each other
recursively to perform the same operation. Private primitives should depend on
required representations/foundations, not on unrelated public convenience APIs.
Public concrete storage and private algorithms can coexist without two competing
behavior definitions. LTO/ThinLTO evaluation remains production-build debt, not a
prerequisite or an assumed cure for poor boundaries.

## Proposed goals and sequence (not activated)

### Goal A: consolidate runtime and public API ownership

1. Approve the ownership/dependency policy and classify the integer constructor
   versus checked-cast distinction. Name remaining legacy consumers.
2. Prototype one scalar operation end to end: public `int64 + int64` and named
   `numeric_add` described together, using one native authority. Include division
   failure as a neighboring proof before generalizing. Preserve public-only includes.
3. Consolidate integer exposure generation and foundational lifecycle/probe plumbing
   in separate slices. Preserve ABI and consumer compatibility unless approved.
4. Repair direct shared-to-language dependency detours and create focused numeric
   primitives where measurements justify them.
5. Generate source inventory and reorganize generated artifacts; trial bounded build
   shards. Retire compatibility paths only after consumer migration evidence.
6. Consolidate repeated metadata facts and remove proven obsolete adapters. Record
   what remains intentionally separate and why.

Success: a change to an operation's behavior has one implementation owner; its public
spellings share one exposure definition; generated/private/public locations are
clear; public-only inclusion and specialization reuse remain valid. File count alone
is not the success metric. These steps span several ownership areas and need approval
before implementation; this document does not authorize a broad refactor.

### Goal B: make the public C++ API friendly without semantic ambiguity

Inventory constructors, checked conversions, ordinary arithmetic/comparisons,
observations and common collection methods. Select helpful C++ spellings and named
lowering primitives from the same operation definitions. Explicitly distinguish
representation conversion from language cast, and ordinary value operations from
compiler-controlled evaluation. Do not restore broad implicit coercion or expose
templates to obtain familiar syntax. Nested indexing is deliberately outside this audit.

For each proposed spelling record accepted types, result, failures, evaluation-order
limitations, ownership and lowering use. Compare ambiguity, caller compilation,
runtime call cost and public dependencies. Source type eligibility/member resolution
stays compiler-owned; generation consumes prepared facts.

### Validation budget for future implementation

Use the existing native/public scalar and conversion cases, plus publication reuse
and public-only include checks. Consolidate tests by behavior, not by deleting layers
of evidence. For one candidate slice run release correctness, focused allocation and
lifetime checks, then ASan/UBSan as appropriate. Measure caller compilation separately
from implementation compilation; measure clean and touched-family rebuilds separately.
Benchmark runtime inner-loop costs only for a changed call/representation path.
Do not repeatedly rebuild all runtime-core merely to edit the plan.

## Reinspection fingerprints

SHA-256 prefixes from the inspected working files, useful when comparing concurrent
main-chat changes (not an ABI or publication mechanism):

| File relative to runtime | SHA-256 prefix |
| --- | --- |
| metadata/public_api.json | afa2cf9c90cbb669 |
| metadata/public_api/families.json | 668b4462d7fcc956 |
| metadata/public_api/global_operators.json | 6940105d137b3e1a |
| tools/concrete_integers.py | a4ac146d357141f7 |
| tools/api_generator/conversions.cpp | 71680aea5f9ea433 |
| tools/api_generator/operators.cpp | 14793e78a094cbc6 |
| include/core/integers.cpp | a9f7d79198e71a11 |
| include/scpp/cast.hpp | 8dba1f80ed61263e |
| include/scpp/generated/operator_detail.hpp | 8a212d11867c5373 |
| include/core/runtime_core.cpp | 6343b9f7201f50de |
| CMakeLists.txt | 235ef91555e222e4 |

## Follow-up: older tools and duplicated metadata

Requested explicitly after the initial audit. Inspection-only except for extending
this document. HEAD during this extension was
`f4c9c5d621c7f5899cd90af921d363ad56a9233f`; the main chat continues independently.
The initial inventory counts above are not recomputed at this later revision.
No tool was executed merely to discover its behavior: some check/generator commands
compile, publish or modify output even when their name sounds observational.

### Tool retirement ledger

| Tool | Current consumer / responsibility | Proposed disposition and removal gate |
| --- | --- | --- |
| `runtime/tools/concrete_integers.py` | Generates public integer classes, operators and private key adapters; `tests/runtime/native/check_integer_api.py` runs its freshness check | Migrate to common C++ metadata generation, then delete script and obsolete check plumbing. Preserve constructor policy, noexcept, layouts and key adapters first. |
| `runtime/tools/value_layout.py` | Separate string/error/exception ABI probe; native CMake registers its check | Merge into generic probe orchestration, then remove. Preserve target compiler/flags, lifecycle assertions and exception_ptr requirements; bootstrap generation must still work. |
| `runtime/tools/generate_operators.php` | Emits native operator surface from config; `compiler/my-try/tests/checked_shifts.py` invokes it; runtime uses its output | Retire only after replacement native operator provisioning and legacy-consumer coverage. New public emission is not presently a complete replacement. |
| `runtime/tools/type_names.py` | Registry resolution delegates to C++; also contains Python parsing/escaping/structural naming and compiler-private binding emission | Split the retirement work by responsibility. Migrate tests using Python naming helpers to exercise the C++ owner; replace bridge emission through prepared application metadata, then remove redundant helper implementation. Keep a thin CLI facade only if a real consumer needs it. |
| `compiler/my-try-cpp/middleware/tools/type_names.py` | Compatibility loader/re-export of the runtime naming tool; called by middleware CMake | Remove once middleware CMake calls the supported common generation interface. It is not a second canonical resolver today. |
| `compiler/my-try-cpp/middleware/tools/scalar_types.py` | Generates runtime scalar re-exports and native adapters from middleware types.json; configure executes `--check` | Eliminate the private scalar-adapter generator after middleware consumers use common transport. Namespace re-exports may remain small authored declarations or metadata-generated aliases; do not generate another scalar family. |
| `runtime/tools/concrete_types.py` | ABI/probe subprocesses, metadata section loading, staging, import validation and publication; many focused tests call it | Keep as orchestration or consolidate behind one supported CLI. It does different work from the C++ emitter; deleting Python is not itself simplification. Remove family-specific remnants only where identified. |
| `runtime/tools/public_package.py` | SDK identity and sealing; imported by compiler build.py, concrete_types.py and publication tests | Keep or fold into one publication driver. Integrity/ownership checks must survive; module/file count alone does not make it redundant. |
| `tools/runtime_config/bin/validate_config.php`, `audit_runtime_vs_config.php` | Config integrity and native implementation drift checks, including compiled probes | Migrate their still-relevant obligations as config ownership changes. They are validators, not duplicate API emitters. Do not silently discard their negative checks. |
| `tools/operator_matrix/generator.php` | Generates operator matrices, validation reports and test seeds from operator-matrix data | Keep as test tooling unless its obligations are deliberately replaced. Audit policy synchronization with operation metadata; it is not another runtime implementation generator. |
| `tools/compile_latency/` generation/layout experiments | README explicitly describes an experimental, bounded output-generation path | Historical/experimental archive candidate, not a production API replacement. No deletion recommendation without a separate archival scope and consumer check. Generated experiment results are excluded from active-tool counts. |

No complete tool in this ledger is proven safe to delete immediately. This is a
retirement backlog with explicit replacement gates, not a recommendation to keep
all old tools forever. The old scan/vector-specific generation responsibility is
already replaced by the C++ path according to public_api_generation.md; do not
invent an additional obsolete script merely because tests still mention scan.

Additional evidence: the old PHP operator emitter embeds `gmdate('c')` in generated
output. Identical semantic input therefore need not produce byte-identical output.
Remove timestamp-driven churn when consolidating its output contract; content-based
freshness and package identity should not change simply because generation ran again.

### Metadata ownership and duplication ledger

| Sources | Observed overlap | Correct treatment |
| --- | --- | --- |
| `runtime/specs/config.json` casts / overload_families / compound_assignment_policy; public conversion and global operator metadata | Multiple records of admitted operations and conversion coverage | Establish one agreed operation-policy owner and generate projections for native compatibility, public exposure and test coverage. Preserve differences in native versus approved public promotion. `check_conversion_coverage.py` already checks a bounded mapping, demonstrating active synchronization rather than safe deletion of config. |
| config `runtime.concrete_integer_api`; public metadata types; core module cpp_types; middleware types.json | Scalar lists, native representations, headers and mappings repeated | Centralize concrete representation/exposure facts, derive each consumer's view. Source `int`, native `int_t` and concrete `int64` are roles, not automatically identical metadata records. |
| core module hash parameters; public family hash defaults; middleware naming registry | Hash's default key `string` repeated in three authored locations | One default declaration with generated naming/physical views, or an explicit consistency check until unified. Parameter names differ (`Value/Key` versus `V/K`); normalize structurally. |
| module `types` capabilities/indexing/iteration; public family checks/access/capabilities | Repeated descriptions of types with partly different purposes | Source eligibility stays compiler-owned. Physical C++ storage checks cannot replace language admission. Derive common facts and verify correspondence; retain distinct role-specific facts. Do not merge merely because both use the word capability. |
| public metadata string/error native bindings; module cpp_types | Actual native type spellings appear both in leaf transport and module binding definitions | Reference the native representation owner instead of repeating strings where transport supports it. `concrete_types.metadata()` already loads module cpp_types into bindings; extend that reuse instead of adding a third registry. |
| `specs/public_specializations.json` leaves/names/apis; public metadata types/operations | Runtime publication explicitly repeats string cpp/header/owner; long API demand list parallels generated operation identities | Treat the request as selection and naming policy, not a second behavior definition. Derive invariant leaf records; optionally support bounded named demand groups without publishing every possible combination. Keep application-owned naming overrides explicit. |
| `runtime/public/specializations.json` | Repeats resolved types, APIs, layouts, ownership and hashes | Derived publication output, not independently authored metadata. Keep proof and reuse data; remove redundant authored inputs rather than stripping the manifest. |
| root `public_api.json` and its four section files | Apparent multiple registries in multiple files | Disjoint section composition, not duplicate metadata. Loader rejects duplicate top-level sections. Consolidating into one giant JSON would not solve policy duplication. |

There is also literal repetition within `global_operators.json`. Comparing the
serialized `rules` field found identical tables for add/subtract/multiply/divide
assignment, and another identical table for bit-and/bit-or/bit-xor/left-shift/
right-shift/power assignment. Their operation names and semantics are not identical.
A named shared admission-policy record could remove repeated tables while each
operator retains its execution and conversion rules. This is a candidate for simple
metadata references, not an unrestricted inheritance or programming language.

### Compiler middleware transition debt

Middleware `types.json` and runtime publication describe different vector<string>
storage today: the compiler registry binds `vector_t<string_t>`, while the runtime
generated concrete vector stores `vector_t<scpp::string>`. The compiler middleware
still consumes generated `runtime_string.hpp`, `runtime_containers.hpp` and
`runtime_scalars.hpp` in its private implementation. These are live dependencies,
not stale files proven removable by the runtime's new public vector existing.

This is a concrete migration candidate: adopt runtime public string/vector types
where the compiler middleware contracts agree, remove superseded wrapper/adaptation
code, and retain genuinely compiler-owned JSON/host functionality. It touches the
compiler middleware ownership area and needs its own approved slice. Naming equality
does not prove representation/ABI interchangeability.

### Cleanup acceptance criteria

Add a retirement deliverable to every consolidation slice:

1. Identify old inputs, outputs, callers and checks before replacing the owner.
2. Move all required behavior and proofs to the common path; record intentional
   semantic differences rather than hiding them in an adapter.
3. Update build/package/test consumers and delete the superseded tool, metadata
   fields and generated outputs together when their consumers are gone.
4. Recreate the affected publication from an empty staging directory. This catches
   accidental dependence on old checked-in output that incremental builds can hide.
5. Verify deterministic regeneration, public-only consumption, application reuse,
   native compatibility where still promised, and focused compile/allocation costs.
6. Search for references to retired paths and update owning documentation. A tool
   retained only for history should be explicitly historical, not look supported.

Do not leave two authoritative generation paths as the completed state of a slice.
Temporary coexistence must name remaining consumers and its removal gate. Runtime
config, operator-matrix metadata and module catalogs must not be bulk-deleted: their
scope exceeds concrete API emission, and a whole-repository consumer migration is
outside the current side-conversation audit.
