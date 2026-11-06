# Runtime submodules and compiler include selection

Doc Status: planning
Date: 2026-11-06
Status: string/output engine and representation-independent export/dependency slices implemented; verification recorded below.

## Agreed direction

Generated C++ will use the scpp type names exposed by runtime metadata and include
runtime C++ headers directly. Runtime modules will be divided into submodules,
each corresponding to an actual public include. Metadata owns that partitioning
and its dependencies. Compiler code generated from metadata determines the
requirements of each emitted application header and source unit.

Start with coarse groups. The first objective is a reusable partition and
include-selection engine; finer partitioning can follow through metadata when
measurements justify it. Other runtime modules initially retain one public header
each. No separate ABI framework or ABI consolidation work is requested.

The broader partition map remains a proposal, not current semantics or a claim
of implemented support beyond the bounded slices recorded below.
The [public-header contract](../runtime_public_headers.md) remains current
authority. The earlier [ABI consolidation debt](runtime_abi_consolidation_debt.md)
is historical design context, not a prerequisite for this work.

## Progress against the original discussion

The include-selection mechanism is implemented for the integrated consumers; the
full runtime migration is not complete. The string/output slice was committed as
`bb09d12bc`; representation-independent exports and their review fixes were committed
as `40695e0f4`. This status records completed work and remaining planning, and does
not authorize another implementation slice.

| Original requirement | Completed | Remaining |
| --- | --- | --- |
| Include runtime C++ headers directly | Generated C++ uses public runtime headers for string storage, output and existing checked scalar conversions. | Connect the remaining public runtime surfaces to the shared export/provider model. |
| Select small includes per source unit | Generated type/helper queries feed per-header and per-source requirement collection and deduplication. Scalar-only units avoid unnecessary string/output includes. | Extend coverage as additional consumers migrate; keep verifying isolation and obsolete-include removal. |
| Expose scpp data types through metadata | Type exports resolve canonical identities to existing public scpp names and provider headers without selecting representation or conversion strategy. | Ordinary scalar declarations still use native C++ spellings; some public helper signatures also retain native types. The scpp-types-only public surface is not complete. |
| Describe runtime partitioning in metadata | Current submodule headers, semantic export dependencies, provider edges and transitive dependency validation are implemented. | Establish the broader coarse partition below and migrate families still owned by legacy renderers. |
| Improve compile time through segmentation | Include isolation and coherent provider relocation are verified. | Measure end-to-end native compilation before claiming a speedup or choosing finer partitions. |
| Avoid a general ABI framework | No general ABI framework was introduced. | Keep it outside this work; existing helper bindings do not justify a separate ABI project. |

The implemented path is runtime metadata → generated name/header queries →
per-file compiler include selection. The application compiler does not interpret
runtime metadata directly. Verification covers cpp/php/js, edit/failure/recovery,
provider relocation, spelling-only regeneration and cached application/native
rebuilds. Regenerating PHP handlers requires a compiler process/session restart;
hot replacement is not implemented.

### Remaining work and sequencing

1. **Review and integrate the published native-alias work.** It belongs to
   `/home/alexv/__AI/simple_cpp/simple_cpp_02`; the user will provide its commit when
   ready. No published commit from that workstream has been integrated in this
   checkout as part of this plan. Do not duplicate it or assume its final aliases,
   widths, representations or conversion interfaces. Review the actual published
   contract before agreeing an integration slice.
2. **Migrate ordinary scalar lowering to scpp exports.** After that contract is
   available, use metadata-exported names and include requirements for ordinary
   scalar declarations and signatures. Review construction, extraction, conversions
   and operators against the actual runtime implementation, preserving source
   semantics. Public name selection alone must not switch lowering strategy.
3. **Establish the core scalar submodule.** Current integer/value/operator headers
   remain under their existing renderers. The proof that renames an integer renderer
   registration to `core.scalars` is not a consolidated scalar implementation.
   Coordinate actual scalar placement with the published alias work and verify
   coherent runtime/compiler paths and standalone headers.
4. **Migrate the remaining runtime groups.** Collections, boxed objects, results and
   optional numeric widths still need agreed bounded slices. Connect other modules
   to the model while retaining one public header per module initially. The proposed
   partition table remains provisional; it grants no additional language admission.
5. **Complete the scpp public-interface review.** Check output, entry, arithmetic and
   other public signatures against the original scpp-types-only requirement. Output
   provider selection is complete, but that does not mean its signature migration
   is complete. Agree treatment of remaining native boundary types explicitly; do
   not infer it from header reachability or introduce an ABI framework.
6. **Measure the resulting compilation costs.** Compare include closures and fresh
   application builds with recorded configurations and cache state. Report frontend,
   optimization/code generation and linking separately. Tune segmentation through
   metadata when measurements justify it, rather than fragmenting every type or
   operation now.

The external native-alias work is a dependency for the next scalar integration and
lowering decisions, not a missing prerequisite for the already completed include
engine. Planning the other partitions can continue independently; their
implementation order and any representation-sensitive adaptations must be agreed
before code changes. Null representation, new widths/admissions, collection/boxed
adaptation and broader public-signature changes remain deferred until their own
slices are defined.

## Runtime surfaces still to migrate

- Scalars span `integers.hpp`, `values.hpp` and `numeric.hpp`; optional integer
  widths currently join the same headers as core types.
- String dependencies now resolve through semantic exports. Its actual published
  cast list determines the required conversion-type dependencies.
- Vectors include numeric operators and strings. Carriers include vectors;
  hashes include carriers. These families retain their existing rendering paths.
- Boxed types include scalars and strings, without requiring public vector/hash
  APIs for their private container implementation.
- JSON and datetime already have one public header each; that alone does not mean
  their dependency selection has migrated to the new engine.

Relevant owners:
[runtime metadata](../../runtime/metadata/runtime.json),
[publication request](../../runtime/metadata/publication.json),
[scalar rendering](../../tools/api_generator/php/render.php),
[sequence rendering](../../tools/api_generator/php/render_sequences.php),
[module rendering](../../tools/api_generator/php/render_modules.php), and
[boxed rendering](../../tools/api_generator/php/render_boxed.php).

## Proposed initial partition

Paths and submodule names below are provisional. Dependencies describe the public
surface; private implementation dependencies remain in compiled runtime sources.
Dependencies conditional on published operations or payloads are resolved for the
actual publication, rather than attached to every configuration unconditionally.

| Module | Submodule / proposed header | Contents | Public dependencies |
| --- | --- | --- | --- |
| Core | `core.scalars` / `scpp/core/scalars.hpp` | int64, float64, bool, null, uint8/byte; core scalar operations and conversions | Foundational native declarations only |
| Core | `core.string` / `scpp/core/string.hpp` | String, its operations and core scalar/string conversions | Scalars |
| Core | `core.collections` / `scpp/core/collections.hpp` | Published vector/hash types, operations and collection carriers | Scalars, string, supporting results |
| Core | `core.boxed_objects` / `scpp/core/boxed_objects.hpp` | Mixed/dynamic, scalar/string interactions and their published result carriers | Scalars, string; results when needed |
| Core | `core.results` / `scpp/core/results.hpp` | Errors and scalar/string nullable/result carriers | Scalars, string |
| Core | `core.output` / `scpp/core/output.hpp` | Supported output operations | Scalars, string |
| Core | `core.entry` / existing entry header | Process-entry support | Scalars after adapting its public surface |
| Custom-size | `custom_size` / `scpp/custom_size.hpp` | Published additional integer widths, operations and core conversions | Scalars; string when publishing string conversions |
| JSON | `json` / `scpp/json.hpp` | Entire published JSON surface | String, results and boxed objects as required by published operations |
| Datetime | `datetime` / `scpp/datetime.hpp` | Entire published datetime surface | Scalars, string, results and custom-size for uint64, as required |
| Ownership | `ownership` / `scpp/ownership.hpp` | Entire published ownership surface | Providers of published payload types |

Custom-size corresponds to today's optional `numeric` module; renaming it is an
open decision. Keep uint8/byte in core under the current canonical identity.
Additional integer widths remain demand-selected; this grouping does not enable
every width or implement missing source-compiler support. Other modules retain a
single representation/header unless later review establishes a useful split.

Keep strings separate so scalar-only units do not acquire string declarations.
Keep scalar operations and conversions together initially rather than producing
one header per operation or width. Public scpp type spelling does not alone decide
whether a representation is an alias or a class; that adaptation needs explicit
review against existing compiler lowering and runtime behavior.

### Carriers and collections

Putting all carriers in one results header would create a dependency knot:
carriers can contain vectors, while hash lookup returns nullable values.
Proposed placement:

- Scalar/string carriers belong to `core.results`.
- Collection carriers belong beside collections.
- Mixed/dynamic result carriers belong beside boxed objects and require results.

This keeps simple scalar results independent of collections and avoids a circular
include graph for the current surface. New payload combinations require reviewed
placement and completeness dependencies; this note does not grant new carrier
admissions.

### Conversion placement

- Core scalar to/from core scalar: scalars.
- Core scalar to/from string: string.
- Core to/from custom-size and custom-size to/from custom-size: custom-size.
- Boxing/unboxing: boxed objects.
- Carrier construction/extraction: the carrier's provider.

Constructor declarations remain inside their destination class where required by
C++. Cross-group constructor signatures may use generated forward declarations.
The engine must distinguish declaration-only references from dependencies needing
complete definitions. A conversion provider may therefore require both operand
providers without forcing reverse includes into their type headers. Exact handling
of existing constructors is a design checkpoint, not an already implemented rule.

## Metadata ownership and dependencies

The model needs three related identities:

1. Module: controls language/API availability.
2. Submodule: identifies a public header and its dependencies.
3. Export: identifies a type or operation and its provider submodule.

Illustrative shape only; extend existing type/operation records rather than
creating a second catalog of their semantics:

```json
{
  "submodules": {
    "core.scalars": {
      "module": "core",
      "header": "scpp/core/scalars.hpp",
      "requires": []
    },
    "core.string": {
      "module": "core",
      "header": "scpp/core/string.hpp",
      "requires": ["core.scalars"]
    }
  },
  "type_exports": {
    "string": {
      "cpp_name": "scpp::string",
      "provider": "core.string"
    }
  },
  "operation_exports": {
    "string.equal": {
      "provider": "core.string"
    }
  }
}
```

Resolve dependencies from published signatures and representations, combined with
explicit metadata requirements. Track declaration-only references separately from
complete-definition requirements. Validate missing providers, unavailable module
dependencies, conflicting header ownership and cycles in required includes.
Type presence in an included C++ header must not grant source-language admission.

Runtime headers remain self-contained and include their direct public dependencies.
Private implementation dependencies must not propagate to application units.
The transitive closure, not just the number of direct includes, determines how
much declaration work reaches the C++ compiler.

## Generation and compiler include-selection engine

The intended flow is:

```text
runtime metadata and publication demand
    -> validated export/submodule dependency plan
    -> runtime headers + generated compiler requirement methods
    -> lowering records requirements for emitted uses
    -> each application header/source includes its required providers
```

The API generator owns the shared plan. Renderers produce declaration groups that
the publication engine assembles into headers according to metadata. Merely making
filenames configurable while renderers still own entire files would not allow
later regrouping through metadata.

Generated compiler methods supply public scpp spellings and provider requirements.
The handwritten emitter collects those requirements through one common mechanism;
it does not maintain type-to-header or operation-to-header switches. Metadata is
resolved during generation, not interpreted for every application expression.

Preparation continues to own source admission, resolved types and conversion
decisions. Lowering selects the implementation of settled facts and records what
the emitted C++ actually uses. A proven direct conversion may need only type
providers; a checked operation additionally needs its implementation provider.

Collect requirements separately for every emitted file:

- Application headers: declarations and any deliberately public definitions.
- Application source units: executable bodies and private declarations.
- Entry unit: selected entry support and its actual emitted operations.

Deduplicate and order includes deterministically. Runtime headers supply their
own dependencies, so application callers never rely on include order. Unrelated
units must not acquire dependencies simply because another unit uses a feature.
Incremental edits must remove obsolete requirements as well as add new ones;
metadata/publication changes must invalidate affected generated artifacts through
the existing publication and build dependency owners.

## Open decisions for remaining partitions

- Final module/submodule names and paths, including whether to rename numeric.
- Integration with the native-alias migration owned by
  `/home/alexv/__AI/simple_cpp/simple_cpp_02`, once its commit is published.
  Representation and conversion implementation decisions belong to that work.
- Placement of cross-group constructors and declaration-only references.
- Final carrier placement and validation of the complete public dependency graph.
- How remaining family renderers expose movable declaration groups without
  duplicating ownership or broadening into unrelated runtime redesign.

## First implementation slice: string/output providers and include selection

Status: implemented on 2026-11-06 following explicit user authorization.
The verification record below covers this slice only.

Establish a shared submodule plan driving runtime header assembly and compiler
include selection, proved through string and output usage. This establishes the
engine before changing scalar representations or reorganizing the whole runtime.

### Observable result and scope

- Metadata identifies providers of the string type and output operations.
- Generated compiler methods supply their requirements, collected separately for
  each emitted application header/source.
- Changing a provider header path in metadata updates runtime publication and
  generated application includes together.
- Moving a supported declaration group between submodules works without editing
  compiler or renderer logic.
- Scalar-only units remain independent of string/output headers unless their
  actual emitted operations require them.

Reuse the existing `cpp_headers()`, `conversion_headers()` and `output_headers()`
collection path. Repair its shared information owner rather than adding another
AST traversal or a second include-discovery mechanism.

### Changes by owning area

| Owner | Planned change |
| --- | --- |
| Runtime metadata | Define submodule identities, module membership, header paths and explicit public dependencies. Attach provider references to existing string type and output binding records. |
| API generator preparation | Build a validated submodule plan shared by runtime and compiler generation. Resolve providers and dependency order before rendering. |
| Runtime rendering | Make string/output renderers produce declaration groups. A common assembler supplies header guards/includes and places those groups using metadata. |
| Compiler generation | Derive string/output header requirements from resolved providers, replacing independently stored header spellings. |
| C++ emission | Reuse per-file requirement collection. Consolidate final include emission where needed so all requirements are collected before includes are written. |
| Publication | Track changed artifacts and retired paths through the existing inventory/freshness mechanism. |

Represent dependencies on existing scalar headers as existing provider submodules;
retain their contents and generation paths. This is a transition, not a claim that
the proposed `core.scalars` group already exists. Other unaffected headers retain
their current generation paths during this slice.

Module availability and header dependencies remain distinct. Header inclusion
cannot grant source-language admission. Resolve metadata during generation, with
no per-expression metadata interpretation in application compilation.

Explicit complete-header dependencies suffice for the initial engine. General
declaration-only dependency handling waits for the scalar/conversion partition.
The broader design above remains the intended destination, not a requirement to
implement all its capabilities in this first slice.

### Deferred work and non-goals

- Switching native scalar storage to runtime scpp scalar types, including review
  of aliases versus existing wrapper classes and their lowering consequences.
- Merging scalar headers or separating optional widths into custom-size.
- Reorganizing collections, carriers, boxed objects or unrelated modules.
- Changing output signatures, conversion rules, source admission or runtime behavior.
- General forward-declaration synthesis.
- Independently splitting runtime libraries or introducing an ABI framework.

String already uses a runtime scpp type and is the first consumer of this engine.
The native-alias migration is owned by `simple_cpp_02`, separately from the next
local export/dependency slice. Completion of this slice must not be described as
completion of that representation change.

### Verification checklist

- [x] **Metadata validation:** reject unknown providers, invalid/conflicting header
  paths, missing dependencies (including intrinsic group requirements), incompatible
  binding groups, migrated header fallbacks and include cycles. Invalid plans fail before
  publication modifies outputs.
- [x] **Metadata control:** in isolated test publications, rename string/output
  header paths using metadata and compile a consumer. Relocate the output
  declaration group to another submodule and verify runtime publication and
  compiler include selection both follow it without source edits to the engine.
  This must prove declaration placement, not only filename substitution.
- [x] **Per-file selection:** exercise a multifile fixture with a scalar-only unit,
  a string signature, string use confined to a body and output use. Verify each
  requirement appears in the correct generated header/source and does not spread
  into unrelated units.
- [x] **Native behavior:** compile each affected runtime header in isolation. Run
  existing string/output native integration coverage across cpp/php/js, preserving
  output bytes, copy behavior and failure behavior.
- [x] **Incremental recovery:** add and remove string/output use, introduce a
  source error, retry without edits and recover. Compare recovery with fresh
  compilation, check obsolete includes disappear, and check unaffected units
  remain reusable. Separately verify publication changes trigger the existing
  rebuild path, including an existing cached application after a compiler-process
  restart.
- [x] **Publication:** verify deterministic generation, freshness checks, retired
  header cleanup and unchanged-file timestamp preservation through the existing
  build/publication orchestration.
- [x] **Cost baseline:** record affected transitive include closures and a small
  fresh native-build baseline. Report frontend, optimization/code generation and
  linking separately, with optimization/debug settings, jobs, linker, PCH/cache
  state and runtime-archive reuse. No speedup is required or claimed for this
  foundation slice without comparative measurement.
- [x] **Shape and hygiene:** review that one shared plan owns provider resolution,
  no new string/output-to-filename switches or redundant traversal were added,
  and generated artifacts are not edited as source. Run affected generator and
  compiler checks, including the compiler's mechanical style check where touched.

### Completion criterion and following slice

Metadata controls both sides of a real runtime dependency; selective includes
survive incremental changes; and no new handwritten string/output-to-filename
switches exist. Both header-path changes and declaration-group relocation must
have executable proofs. Outstanding checks or ownership blockers are reported
explicitly rather than hidden by a partial green result.

The next local slice introduces representation-independent type exports and
dependency resolution, as scoped below. It does not migrate scalar representations
or establish the proposed scalar partition by changing their implementation.

## First-slice implementation and verification record

Implemented owners: `tools/api_generator/php/submodules.php` plans providers and
assembles declaration groups; existing string/output renderers supply group bodies.
Runtime `string.provider` and `output.provider` select the group locations.
Compiler binding `declaration_group` references resolve through that plan once
before type/operation generation. This replaces string/output header spellings in
bindings without making runtime publication depend on compiler strategies.

The initial catalog keeps the existing public paths (`scpp/string.hpp` and
`scpp/output.hpp`) and existing scalar providers (`integers.hpp`, `values.hpp`).
Their proposed coarse replacements above are not implemented. Existing scalar
renderers retained their publication-selected paths in that first slice. The export
review below makes metadata authoritative and rejects conflicting publication paths;
fixed scalar-renderer dependency behavior remains unchanged.
Only core module records are supported; arbitrary module loading and general
forward-declaration dependencies remain deferred. A legacy string publication
header is accepted only when it agrees with the metadata provider.

The output implementation now includes generated private `output_provider.hpp`;
CMake tracks its selected public provider and rebuilds native consumers through
normal depfiles. Runtime publication inventory is version 3. Compiler include
collection remains per-file, with global declaration requirements collected before
include emission. No additional AST traversal or application-time metadata lookup
was introduced. Source semantics, output signatures and scalar storage are unchanged.

Verification evidence:

- `tools/api_generator/tests/submodules.py --output build/submodules-proof-verified`
  passed. Default, renamed and relocated publications each built a fresh runtime
  and executed generated multifile applications in cpp/php/js (nine executables).
  Relocation places output declarations in the string provider, proving actual
  group assembly rather than filename substitution.
- Each affected public header compiled independently. The default string public
  closure is string + integers + values; output adds no other scpp header. Existing
  output closure tests additionally check private/STL dependencies do not leak.
- Body-only string/output use left exported scalar signatures and unrelated units
  unaffected. Edits, removal, repeated failures and recovery matched fresh emitted
  artifacts; no-op preparation did no work. Existing type-binding recovery tests
  also verify unchanged caller AST identity.
- Invalid providers, paths, conflicts, dependencies, cycles and module references
  preserved existing publications. Compiler bindings reject competing header and
  declaration-group owners. Metadata changes triggered freshness repair and native
  output object rebuilding; no-op publication and native builds preserved timestamps.
  Retired string/output paths disappeared after relocation.
- All 23 selected CTests passed. Existing native string/copy, ordered output/failure, public output, cross-unit
  headers, globals, project publication and generator checks passed. The broader
  frozen contracts cover 130 accepted / 247 rejected runtime cases, 19 accepted /
  537 rejected scalar cases, and 17 accepted / 37 rejected generator lifecycle cases.
- Snapshot review found only prepared-plan data, inventory, the new private bridge
  and output-header formatting changed among generated artifacts. Other runtime
  declaration/implementation hashes remain unchanged. The custom-header fixture
  now changes string placement through metadata. Scalar-only generation still
  accepts metadata without the optional string family.
- Compiler mechanical style checks and whitespace checks passed. Publication
  snapshots were refreshed explicitly; no generated runtime/compiler output was
  edited as implementation source.

The native proof records single correctness-run timings, not comparative speedup
claims. Clang/C++23, libc++, O2/g0, mold, sequential application compilation, no
PCH/LTO; fresh instrumented application objects reuse runtime objects compiled
once per variant. Other verification processes may share the host. Frontend/backend
trace sums are cumulative and overlap their optimizer/codegen subsets; they do
not add to native wall time.

Default-provider samples (milliseconds):

| Flavor | PHP incremental run | Native wall | Frontend | LLVM backend | Optimizer | Codegen passes | Link command |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 1.87 | 345.44 | 33.45 | 24.51 | 8.49 | 8.47 | 70.52 |
| php | 2.14 | 325.05 | 28.34 | 23.73 | 8.59 | 7.78 | 58.94 |
| js | 1.87 | 329.82 | 28.79 | 23.83 | 8.27 | 8.16 | 58.94 |

The default variant's separate runtime compilation took 5403.98 ms. The PHP numbers
measure the incremental source edit, not a cold compiler startup; native timings
include fresh compilation and linking of every emitted application unit. Full
variant measurements and dependency paths are retained in the local, untracked
`build/submodules-proof-verified/results.json` artifact.

## Review corrections before commit — 2026-11-06

The first review identified two validation holes and an application-rebuild proof
missing from the initial completion claim. Those findings are now addressed:

1. The assembly owner declares each implemented group's intrinsic scalar-provider
   requirements. The common dependency walk computes transitive reachability and
   rejects missing requirements before publication. Removing all string edges or
   only its values edge is rejected; reaching both providers through another
   published group is accepted and verified by isolated native compilation.
2. Group contracts describe their exported binding roles. Compiler resolution uses
   those contracts to require the correct declaration group for migrated bindings.
   Wrong groups, unknown groups and literal-header fallbacks are rejected. Other
   bindings retain their existing, explicitly unmigrated header contracts. These
   bounded role checks do not introduce native signature generation or an ABI
   framework, and runtime publication still never reads compiler metadata.
3. The resolved plan now has separate `nodes` and `groups` maps. Compiler consumers
   resolve the registered group owner without a second group-name whitelist.
4. The native proof now builds and caches an application, regenerates providers,
   restarts the real `Project_Compiler`, and reuses the same application output and
   Ninja build directories. For cpp/php/js, both header renaming and declaration
   relocation replace old includes, rebuild native application objects and execute
   successfully. Source bytes and timestamps stay unchanged; subsequent project
   and native invocations perform no work. The proof uses a copied compiler tree
   so the normal compiler-input scan observes its isolated generated PHP.

Compiler lifecycle: regenerated PHP handlers require a new compiler process.
Retained `Incremental_Project` sessions are valid only for the compiler publication
loaded when that process started. Restart the process/session after regeneration;
PHP class hot replacement and live compiler-publication watching remain out of
scope. Source edits still use the existing retained-session incremental protocol.

The corrected proof is `tools/api_generator/tests/submodules.py`; its negative
cases verify that failed generation leaves both runtime and compiler publications
intact. The cached-application results are recorded under `cached_application` in
`build/submodules-review-verified/results.json`. The extra test-only compiler copy
and native build graph do not change production ownership or caching protocols.

All 23 selected CTests passed: 20 focused compiler/runtime checks and three
generator contract corpora. The final provider proof also passed, including the
positive transitive-dependency case and cached-application checks in all three
flavors. Snapshot review for these corrections found only `prepared.json` changes: generated public headers, native implementation bodies and compiler PHP
remain byte-identical to the first-slice outputs. Existing representation and
partition deferrals remain unchanged.

## Representation-independent type exports and dependencies

Status: implementation authorized in the subsequent user request; local slice implemented.

### Workstream boundary

The native-alias migration is being implemented separately in
`/home/alexv/__AI/simple_cpp/simple_cpp_02`. The user will publish its commit when
ready. Do not duplicate that work, modify that checkout, or assume its pending
implementation details here. Integrate and verify the published changes in a
separately authorized step.

This checkout owns the export/provider/dependency engine. Its interface must remain
usable when a runtime type changes between a class and a native alias. The broader
partition map above remains proposed organization, not a reason to implement the
scalar migration locally.

### Contract and ownership

Keep four concepts separate:

| Concept | Owner and purpose |
| --- | --- |
| Semantic identity | Language metadata and prepared facts identify the source type, independently of native representation. |
| Public C++ spelling | Runtime type-export metadata supplies the name used by consumers, such as `scpp::string` or a published scalar name. |
| Provider | Metadata assigns the export to a submodule, which owns its public header and include dependencies. |
| Implementation | Runtime generation and the relevant lowering strategy own alias/class representation, construction, extraction, conversions and operations. |

The include engine consumes export identity, provider and dependency information.
It must not inspect class versus alias representation, assume `.native_value()`,
choose constructors, or determine conversion admission. Compiler preparation keeps
its semantic responsibilities; representation-sensitive lowering stays with its
existing owner until the external migration is integrated.

### Agreed changes

1. Extend the existing metadata model with public type exports and their provider
   references. Reuse canonical identities and existing spelling records rather
   than creating another independent type catalog. The implemented schema follows
   current declaration owners; integration with the external work remains separate.
2. Change intrinsic declaration-group requirements from renderer slots such as
   `types` and `values` to the semantic exports needed by their declarations.
   The declaration owner specifies those requirements; the common planner resolves
   each export to its provider and validates the required dependency closure.
3. Generate public type-name queries and include requirements from the shared
   export plan for the scoped consumers. Keep native storage/operation strategy
   facts distinct from public names; changing a name must not silently select a
   different representation or conversion implementation.
4. Reuse the existing `nodes`/`groups` plan, publication assembly and per-file
   compiler requirement collection. Preserve intrinsic requirement validation,
   binding ownership checks, failure-before-publication and deterministic output.

For example, string declarations should express the relevant scalar export needs
instead of depending on the historical integer/value renderer split. When an
export later moves to `core.scalars`, updating its provider mapping should update
the resolved includes without editing string's semantic requirements or the
compiler's include collector. Identify the exact export requirements from the
published declarations during implementation; this note does not invent that list.

A coherent publication must place the declaration and advertise its export in the
same provider. Provider remapping must not merely rewrite a header query while
leaving the actual declaration elsewhere. Existing unconverted renderers may need
a bounded adapter; do not silently expand this into their representation migration.

### Implemented schema and boundaries

- `type_exports` is a list of canonical runtime `identity`, public `name`, and either
  scalar `provider` or movable `declaration_group`. The shared plan resolves public
  `scpp::` names and providers; type bindings select `type_export` rather than owning
  a second symbol/header. Declaration groups separately identify type exports and
  helper binding roles.
- Canonical identities come from existing integer/value/floating metadata and group
  contracts. Names reuse existing declarations or integer aliases. This slice does
  not support arbitrary renaming of runtime declarations. The spelling proof selects
  the already published `scpp::byte` name for the unchanged `uint8` identity.
- `requires_exports` supplies semantic type dependencies on submodules. Resolution
  combines them with direct `requires` provider edges, deduplicates them, rejects
  cycles and checks intrinsic requirements through the transitive closure. A
  dependency within the same provider does not create a self-include.
- String's invariant group contract needs `bool_t`. String preparation validates
  the dependencies of the actual published casts, including optional numeric widths.
  Enabled but unpublished text-conversion policies add no intrinsic dependencies.
  The native count representation adds no public export. The common graph planner
  no longer infers requirements from conversion capability policies.
- The legacy scalar adapter checks that exports point to their actual renderer.
  The renderer registration can be renamed to `core.scalars` with metadata changes,
  without changing string's semantic requirements. Moving the actual scalar
  declarations between renderers remains deferred. Metadata owns scalar provider
  paths. Legacy scalar publication header fields
  must agree with those paths; conflicts are rejected before output changes. Custom
  paths remain supported when both inputs agree.
- Export metadata describes available declarations; the publication request still
  selects the APIs actually emitted. Existing publication checks retain ownership
  of required cast types. Export availability grants no source-language admission.
- Native scalar storage spellings, checked construction, extraction and operation
  strategies remain unchanged. String storage and existing checked scalar bindings
  consume the shared export name/header plan; the application include collector
  needs no new cases or metadata interpretation.

### Scope and deferred work

The local slice is metadata ownership, export resolution and generated name/include
queries. It does not replace wrapper classes with aliases, rewrite checked
conversions, change numeric promotions, add widths or source-language admissions,
decide null representation, or adapt collection/boxed/JSON/datetime internals to
native aliases. Those changes remain with their owning workstream or later slices.

Do not introduce alias-specific branches, parallel scalar implementations or a
temporary ABI layer to anticipate the unpublished commit. If export resolution
cannot be separated honestly from a wider runtime or lowering redesign, report
that concrete boundary and agree the scope before proceeding.

### Verification checklist

- [x] Validate unknown/duplicate exports, missing providers, incompatible binding
  owners, omitted intrinsic export requirements and dependency cycles before any
  publication changes. Failed generation preserves the previous artifacts.
- [x] Prove metadata-controlled public spelling and provider resolution using
  coherent isolated publications. Move a supported export between providers while
  preserving semantic identity; verify declaration placement and compiler includes
  agree, without editing the consumer's requirement logic.
- [x] Verify that consumers specify semantic export requirements rather than
  renderer slots, filenames or alias/class checks. Review construction, extraction
  and conversion paths to ensure include planning has not acquired their policy.
- [x] Compile affected public headers independently and run focused native
  consumers. Preserve current behavior and admission under this checkout's current
  representation. This is not proof of the pending native-alias implementation.
- [x] Preserve per-header/per-source isolation, obsolete-include removal, source
  edit/error/recovery equivalence and no-op reuse across cpp/php/js.
- [x] Regenerate exports/providers, restart the compiler, and rebuild an existing
  cached application in the same output/native directories. Verify updated
  includes, execution and subsequent no-op behavior without application edits.
- [x] Check publication freshness, deterministic generation, retired paths and
  timestamp preservation. Review snapshot changes and run affected generator,
  compiler and style checks; measure costs only where the changes warrant it.

### Local verification evidence

- `tools/api_generator/tests/submodules.py --output build/export-review-fixes-verified`
  exercises default, renamed, output-relocated and string-type-relocated
  publications. The post-review run is recorded below.
  The renamed variants move the integer renderer registration to `core.scalars`
  without changing string requirements, and select `byte` for the `uint8` export.
  Emitted checked conversions use that spelling and execute successfully in all
  three flavors. Independent header compilation checks actual declaration placement.
- The same proof covers invalid export identities, duplicate exports, missing or
  conflicting owners/providers, undeclared names, export-induced cycles, omitted
  requirements and incompatible compiler bindings. Failure preserves prior outputs.
  Repeated publication, freshness checks, retired files and unchanged timestamps
  are checked, along with per-unit include isolation and edit/failure/recovery.
- The real project compiler is restarted after metadata regeneration. Existing
  applications and native output directories are reused for renamed providers,
  output relocation and string-type relocation in cpp/php/js. Execution, compiler
  cache reuse and subsequent Ninja no-ops are checked without application edits.
- Generator tests check metadata purity, export/dependency order independence and
  preservation of native/proven-safe conversion paths. Existing constructor and
  extraction strategies retain ownership; no representation tests enter the graph
  resolver or application include collector.
- Frozen contract captures retain 130 accepted / 247 rejected runtime cases,
  19 accepted / 537 rejected scalar cases (2,692 maximum operations), and
  17 accepted / 37 rejected generator cases. Reviewed differences are prepared
  export/dependency plans and input fingerprints. Generated public/private artifacts
  are byte-identical to the previous baselines.
- The original 23 focused CTests passed; the post-review rerun is recorded below.
  The selection covers these three corpora plus generator input,
  numeric, compiler lowering/type bindings, string/output, global/unit headers,
  project/incremental publication, runtime freshness/boundary, cleanup and native
  string/vector/carrier consumers. The separate provider proof retains stage
  timings and include closures in `build/export-review-fixes-verified/results.json`.
  Compiler style checks cover 154 files; `git diff --check` is clean. Timings are
  verification samples under concurrent test load, not a compile-speed claim.

### Review fixes and verification

The pre-commit review found split scalar path ownership, capability-based string
requirements that rejected valid reduced publications, and a cached rebuild proof
that coupled spelling changes to provider changes. All three are addressed locally:

- The scalar adapter rejects disagreement between metadata provider paths and
  legacy publication header fields. Proofs change each side independently, check
  failure preserves output, and then match both scalar paths. Compiler queries,
  isolated headers and generated runtime source compilation agree on the custom paths.
- The common planner no longer reads text-conversion policies. String preparation
  checks its actual cast list. Reduced publications with no casts or only `bool_t`
  casts compile with only the values dependency while capability metadata stays
  unchanged. Adding an `int64` cast without its dependency fails without modifying
  output; adding the dependency restores successful publication and compilation.
- The cached application now includes a checked byte conversion through a function
  call. A separate `spelling_only` step changes `uint8`'s public name to `byte` while
  keeping provider metadata and all application sources unchanged. It asserts that
  runtime C++ artifacts are unchanged and emitted application differences are exactly
  the checked-construction spelling. Restart, execution and subsequent compiler/Ninja
  no-ops are checked for cpp/php/js, followed by the existing relocation steps.

Post-review verification:

- [x] Conflicting scalar paths fail before publication; matching paths compile.
- [x] Reduced string publications compile; missing actual cast dependencies fail
  without changing output and recover when restored.
- [x] Spelling-only cached rebuild, execution and subsequent no-ops pass for cpp/php/js.
- [x] Expanded provider proof passes; evidence is in
  `build/export-review-fixes-verified/results.json`.
- [x] Twenty focused CTests and the runtime-family/generator-parity CTests pass.
  Runtime cases remain 130 accepted / 247 rejected; generator cases remain
  17 accepted / 37 rejected. Emitted public/private artifact hashes are unchanged.
- [x] Frozen scalar-contract rerun passes (116.84 seconds), with 19 accepted /
  537 rejected and 2,692 maximum operations; only prepared plans changed.
  Together with the two preceding CTest selections, all 23 focused CTests pass.
- [x] Compiler style checks pass for 154 files; `git diff --check` is clean.

Native-alias migration remains out of scope; no representation or conversion-strategy
changes were made.

Completion means the scoped consumers resolve public type names and dependencies
through semantic exports without knowing their implementation representation.
After the external alias commit is published, a separate integration verification
must exercise the actual aliases; this slice alone cannot certify that migration.

## Verification for subsequent partitions

Agree each further bounded slice before implementation. Verify isolated public headers,
provider/dependency validation, generated publication freshness, and actual emitted
include sets for scalar-only, string, collection, boxed, custom-size and module
consumers within supported compiler coverage. Runtime-only consumers cover APIs
not yet admitted by the source compiler.

Check native behavior and incremental edit/deletion/failure/recovery equivalence
across supported flavors, including removal of no-longer-needed includes. Measure
fresh native compilation and transitive include closures before claiming a speedup;
report frontend, optimization/code generation and linking separately with the
build configuration and cache state.

Remaining non-goals: a separate ABI framework, changing
source semantics, adding unsupported types/operations, one header per function,
or splitting compiled runtime libraries solely because public headers are split.
