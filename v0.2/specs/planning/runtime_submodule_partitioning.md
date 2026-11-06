# Runtime submodules and compiler include selection

Doc Status: planning
Date: 2026-11-06
Status: export/provider engine and core.scalars consolidation implemented; native-alias integration pulled at 64a2e0437. Verification is recorded below; other runtime partitions remain planned.

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
as `40695e0f4`. The native-alias work and merge repairs arrived through `64a2e0437`.
The user authorized repairing that integration and then implementing the bounded
core.scalars consolidation below. Further partitions require their own scope agreement.

| Original requirement | Completed | Remaining |
| --- | --- | --- |
| Include runtime C++ headers directly | Generated C++ uses public runtime headers for string storage, output and existing checked scalar conversions. | Connect the remaining public runtime surfaces to the shared export/provider model. |
| Select small includes per source unit | Generated type/helper queries feed per-header and per-source requirement collection and deduplication. Scalar-only units avoid unnecessary string/output includes. | Extend coverage as additional consumers migrate; keep verifying isolation and obsolete-include removal. |
| Expose scpp data types through metadata | Type exports resolve canonical identities to public scpp names and provider headers. The pulled alias work also uses those names for supported ordinary scalar declarations, signatures and expression casts. | Some public helper signatures still retain native types; compiler null remains decltype(nullptr). The scpp-types-only public surface is not complete. |
| Describe runtime partitioning in metadata | Core/numeric scalars, separate checked conversions, string/output providers, semantic export dependencies and transitive validation are implemented. | Establish the broader coarse partition below and migrate families still owned by legacy renderers. |
| Improve compile time through segmentation | Include isolation and coherent provider relocation are verified. | Measure end-to-end native compilation before claiming a speedup or choosing finer partitions. |
| Avoid a general ABI framework | No general ABI framework was introduced. | Keep it outside this work; existing helper bindings do not justify a separate ABI project. |

The implemented path is runtime metadata → generated name/header queries →
per-file compiler include selection. The application compiler does not interpret
runtime metadata directly. Verification covers cpp/php/js, edit/failure/recovery,
provider relocation, spelling-only regeneration and cached application/native
rebuilds. Regenerating PHP handlers requires a compiler process/session restart;
hot replacement is not implemented.

### Remaining work and sequencing

1. **Native-alias integration is now present.** Pulled `64a2e0437`, containing
   the checked-conversion commits, native-alias migration and provider merge from
   `simple_cpp_02`. The local follow-up adapts representation-sensitive proofs and
   reviews regenerated snapshots. Combined verification is recorded below.
2. **Ordinary scalar lowering now consumes scpp exports.** The incoming compiler
   changes already resolve supported scalar names and headers through the shared
   export plan. Expression emission collects result-type headers. Checked conversions
   call named helpers; public alias names do not choose their semantic policy.
   No new source widths or container admissions are implied.
3. **Core scalar consolidation is implemented.** Core declarations and optional-width
   declarations now have distinct metadata providers. Checked conversions remain
   separate, with dependencies derived from published signatures. Legacy integer/value
   headers are retired; the implementation and verification record is below.
4. **Migrate the remaining runtime groups.** Collections, boxed objects and results
   still need agreed bounded slices. Existing optional-width declaration placement
   is bounded by the scalar slice below; broader numeric-module redesign is deferred. Connect other modules
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

The external native-alias dependency has been supplied and integrated. The user has
chosen to keep checked conversions separate and authorized the concrete scalar slice
and checklist below. It supersedes the earlier
suggestion to defer all optional-width placement: the bounded placement of existing
custom-size declarations is included so `core.scalars` does not absorb them. No new
numeric-module policy, representation or source admission is implied.

## Combined native-alias integration at 64a2e0437

The pull was a fast-forward. A full native build regenerated target facts, runtime
headers, runtime libraries and compiler handlers together. This avoids mixing old
wrapper archives with alias headers. Ordinary scalar lowering was already implemented
in the incoming code; local repairs do not duplicate that work.

The provider proof now uses explicit byte declarations and named checked conversions.
Its spelling-only mutation changes declarations/casts from `scpp::uint8` to
`scpp::byte`, while canonical checked-helper names remain unchanged. It still checks
unchanged source files, exact emitted differences, runtime C++ stability, native
execution and subsequent compiler/Ninja no-ops across cpp/php/js. Scalar-only units
now legitimately include their scalar alias provider, while remaining isolated from
string/output headers. Custom scalar header proofs also compile the generated runtime
source, which includes the checked conversion declarations.

All three corpus captures retain their acceptance counts: runtime 130/247, scalar
19/537 and generator 17/37 accepted/rejected, with 2,692 maximum scalar operation
recipes. Compared with the alias-only snapshots, reviewed changes are the submodule
plan/input fingerprints, output-provider bridge, corresponding inventory/counts and
one blank line removed from the assembled output header. Other generated public and
private artifact hashes match. Scalar case keys change with their metadata inputs.

Verification status: all 179 CTests pass across the combined runs. The four
provider/frozen-contract CTests passed in 107.36 seconds. The other 175 passed
across the ordinary run (131.57 seconds, with one socket-restricted test) and a
focused frontend-service rerun (3.28 seconds) with Unix socket access. The sandbox rejected Unix socket bind with EPERM; the service test passed
outside that restriction. The separately run provider proof passed. Raw evidence:
`build/alias-integration-proof/results.json` and the CTest logs.

Default-provider native application timing samples across cpp/php/js (milliseconds):

| Native wall including link | Clang frontend | LLVM backend | Optimizer | Code generation | Link command |
| --- | --- | --- | --- | --- | --- |
| 337.9–376.4 | 26.6–31.0 | 30.4–34.2 | 10.7–12.2 | 10.7–12.0 | 62.4–76.2 |

Clang/C++23, libc++, O2/g0, mold, sequential application compile/link commands,
no PCH/LTO; each variant's runtime objects were built once and reused. Tests ran
concurrently. Frontend/backend phase totals sum translation-unit traces, and nested
optimizer/code-generation totals must not be added to their parent totals. These
are integration samples, not a controlled before/after speedup claim. Comparative
compile-time evaluation remains outstanding.

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

Status: original export slice implemented at 40695e0f4. The following records its
pre-alias scope and verification; current representation details are superseded by
the combined native-alias integration recorded above.

### Workstream boundary

The original export slice left native-alias migration to
`/home/alexv/__AI/simple_cpp/simple_cpp_02`. That work was subsequently published
and pulled through `64a2e0437` under separate user authorization. The integration
record above covers its actual implementation; the original boundary below explains
why the export engine did not implement scalar representations itself.

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

## Implemented slice: core.scalars consolidation

Status: implemented and verified; the completion evidence is recorded below.
Checked conversions remain separate from scalar declarations. This slice builds on
the verified alias/provider integration at `64a2e0437`; it does not reimplement the
native-alias migration or change that external checkout.

### Intended partition and dependency contract

Provider identities and default paths below are the implemented targets.
Metadata remains authoritative for their paths; tests must also exercise relocation.

| Provider | Default header | Contents | Public dependencies |
| --- | --- | --- | --- |
| `core.scalars` | `scpp/core/scalars.hpp` | Published core integer aliases selected by `core_integers` (currently int64 and uint8), byte, bool_t, float64, existing null_t/null and their existing declaration-associated aliases/operations | No string, conversion, collection or custom-size provider |
| `numeric.scalars` | `scpp/numeric/scalars.hpp` | Published non-core integer aliases: currently int8, int16, int32, uint16, uint32 and uint64; any existing aliases attached to those identities | Only dependencies actually required by its declarations; independent builtin aliases do not require core merely because they are numeric |
| `core.conversions` | `scpp/conversions.hpp` | Existing named checked numeric conversions and selected string parsers; existing strict input fences | Providers of source/result types appearing in actually published functions; core alone for the default numeric surface, plus numeric.scalars when selected functions use custom-size types |

`numeric.scalars` is the custom-size partition under the existing optional `numeric`
module. The slice does not rename that module to `custom_size`, enable it by default,
add widths, or admit additional source-language scalar types. In particular, uint32
and the 16-bit types remain optional runtime types under current metadata; spelling
them as native aliases does not make them core. Core membership comes from metadata,
not a new handwritten width list in the compiler or renderer.

Preserve publication demand: a catalog export describes availability, while the
publication request selects actual declarations and functions. Core-only publication
must not emit or pull custom-size declarations. Reduced/optional-width-only runtime
publications must retain their existing supported behavior; a provider without
selected declarations must not accidentally be pulled by generated dependencies.
Byte remains an alias of uint8; source-default aliases follow their existing target
and publication rules rather than being duplicated into both headers.

String parsing helpers may retain their existing concrete string forward declaration,
owned by their renderer. This slice adds no generic forward-declaration engine and
must not create a scalar/string/conversion include cycle. String formatting remains
with string construction. A declaration-only scalar unit includes core.scalars;
a checked conversion adds the conversion provider through its generated operation
requirements. Proven conversions retain their existing helper-free lowering.

### Ownership and implementation scope

1. **One declaration placement model.** Replace the legacy assumption that integer
   and value renderer slots uniquely determine scalar ownership. Prepare scalar
   declaration groups from existing canonical identities, core membership and
   publication demand. Each export has one declaration owner and provider. The
   renderer emits those declarations into the same providers advertised to consumers;
   do not allow duplicate paths as a special case or advertise one header while
   continuing to declare the type in another.
2. **Reuse the existing submodule engine.** Feed prepared declaration fragments into
   the current assembly process. Keep export resolution, cycle/reachability checks,
   deterministic output and failure-before-publication in their shared owners. Lists
   of declarations and dependencies represent valid multiplicity; this slice adds
   no AST duplicate chains or new symbol publication protocol.
3. **Keep conversions separate.** Connect the existing checked-conversion planner
   and renderer to a provider and derive declaration dependencies from its actual
   function rows. Preserve canonical helper names, native-identity input fences,
   failure/lossless facts, implementation algorithms and admission. Compiler checked
   operation queries and runtime declarations must resolve the same provider path.
4. **Migrate configuration and consumers coherently.** Replace scalar output-path
   ownership through legacy `headers.types`/`headers.values` with provider-owned
   placement in metadata/publication preparation. Update repository publication
   fixtures, runtime includes, CMake byproducts and native consumers in the same
   slice. Conflicting/obsolete configuration must produce explicit diagnostics;
   do not silently accept two independent path authorities. Retire generated
   integers.hpp/values.hpp after their consumers migrate, with cleanup verification.
   Do not introduce new forwarding compatibility headers.
5. **Preserve existing aggregates without making them compiler defaults.** The
   existing numeric.hpp convenience aggregate and short_names.hpp may retain their
   supported roles, with includes derived from selected scalar providers. They must
   not merge checked conversions into core.scalars or become blanket compiler
   includes. Do not retain a file with no truthful consumer/API role merely to satisfy
   stale snapshots; record any retirement and update its owning contract/tests.
6. **Keep compiler changes declarative.** Supported ordinary scalar names already
   use exports. Connect the new placement and conversion provider through generated
   type/operation header queries. The application include collector must not branch
   on core/custom-size names, paths, widths or alias implementation details.

The owning areas are API-generator scalar/conversion preparation and rendering,
runtime metadata/publication, CMake output registration, existing native consumers
and generated compiler queries. No new semantic preparation, AST/fact, watcher or
session ownership model is needed. If a wider module-loading or public-signature
redesign proves necessary, report that boundary instead of extending this slice.

### Deferred work and non-goals

- No changes to native aliases, scalar construction/initialization semantics,
  conversion permissions, promotions, checked arithmetic, exceptions or null
  representation. Runtime null_t/null stay concrete; compiler null remains
  decltype(nullptr).
- No new source widths or vector/hash/carrier admissions; no numeric-module rename,
  installation/discovery framework or new profile policy.
- No migration of collections, boxed objects, results, JSON, datetime or ownership
  into new providers. Update their existing includes only as required to consume
  the relocated scalars/conversions; preserve their current declaration ownership.
- No output/entry/arithmetic signature redesign or general ABI framework. Existing
  helpers keep their current contracts. Their scpp-types-only surface review remains
  a separate slice.
- No one-header-per-type/function fragmentation, new runtime libraries or PHP handler
  hot reload. Restart compiler processes after regeneration.
- No compile-time speedup claim from include counts alone; broad comparative
  benchmarking and further partition tuning remain later work.

### Verification checklist

- [x] Validate unique export/declaration ownership, missing/conflicting providers,
  obsolete or conflicting scalar path fields, unknown dependencies and cycles before
  output changes. Failed generation must preserve the prior publication.
- [x] Verify core-only, optional numeric, reduced scalar and existing optional-width-
  only publications. Check exact exported names, native alias identities (including
  both 16-bit types and byte), null contracts and unchanged module/source admissions.
  Core-only headers must not contain custom-size declarations or include their provider.
- [x] Compile each selected scalar and conversion header independently. Verify
  conversions include precisely the declaration providers their signatures need,
  including optional-width and selected string-parser cases. Check no cycles,
  implementation/STL leakage or new public templates.
- [x] Prove ordinary scalar declarations use core.scalars without conversion/string/
  output/custom-size includes. Prove checked conversions add their provider, proven
  conversions need no checked helper, and runtime optional-width consumers get the
  numeric provider. Inspect both emitted application headers and source units.
- [x] Relocate core, numeric and conversion providers coherently through metadata.
  Verify actual declaration placement, generated compiler queries, native depfiles,
  CMake output tracking, retired paths and execution agree. Use the existing provider
  proof infrastructure rather than another include-selection implementation.
- [x] Preserve scalar/conversion boundaries, strict input filtering, overflow and
  failed-write behavior. Run affected native scalar, string, vector, carrier, boxed,
  hash, JSON, datetime and ownership consumers after their include migration.
- [x] Across cpp/php/js, compare affecting edits, deletion/repeated failure/recovery
  with fresh compilation; verify obsolete include removal, unrelated unit isolation
  and no-op reuse. Keep optional-width source admissions unchanged.
- [x] Regenerate and restart the compiler against unchanged cached applications and
  native build directories. Exercise provider relocation and spelling-only changes
  separately; verify updated includes/spellings, executable behavior and subsequent
  compiler/Ninja no-ops.
- [x] Check metadata purity, deterministic generation, publication freshness and
  no-op timestamp preservation. Review snapshot differences by declaration/header
  ownership and admission; do not accept hash changes without explanation. Run
  relevant generator contracts, compiler style checks and diff checks.
- [x] Record focused include closures and native compilation timings using consistent
  compiler flags, optimization/debug settings, jobs, runtime reuse and cache state.
  Separate frontend, LLVM optimization/code generation and linking; report scope
  and limitations rather than implying an overall speedup.
- [x] Update owning contracts and this note with actual paths, retired artifacts,
  verification results and remaining work before declaring the slice complete.

Completion means core scalar declarations have one metadata-owned provider, existing
custom-size declarations remain separately demand-selected, and checked conversions
have a separate provider whose dependencies match published signatures. The compiler
selects those includes through the existing engine, with source semantics unchanged.
It does not mean the entire runtime partition map or scpp-only helper surface is done.

### Implementation and verification record — 2026-11-06

- Scalar declarations and their aliases now render as fragments owned by
  `scalar_core` / `scalar_numeric` declaration groups. All type exports reference
  their declaration group; the legacy scalar renderer-slot adapter is removed.
  Metadata core membership and publication demand determine placement. Checked
  helpers use `core.conversions` and requirements derived from actual function rows.
  String cast requirements also derive from selected publication rows, allowing
  optional-width casts without pulling numeric.scalars into core-only publications.
- Publication preparation now validates catalog plus selected signature dependencies,
  preserves transitive requirements and rejects unpublished dependencies/cycles.
  Rendering consumes the finalized graph; see the review-fix record below.
  No application include-collector special cases were added.
- Default paths are exactly those in the table above. `integers.hpp` / `values.hpp`
  and publication `headers.types` / `headers.values` are retired without shims.
  Native consumers and existing family renderers use the selected scalar providers
  or existing numeric aggregate. No other family acquired a new declaration owner.
- `provider-headers` exposes managed output paths to `cmake/ProviderOutputs.cmake`.
  The production inventory helper is exercised in an isolated CMake/Ninja build:
  all three scalar/conversion providers relocate through metadata, Ninja triggers
  reconfiguration, old outputs disappear, missing outputs regenerate, and the
  following build is a no-op. The normal native build also passes; a subsequent
  build runs only existing freshness checks, with no native recompilation/linking.
- `tools/api_generator/tests/submodules.py --output build/core-scalars-proof-3`
  passes. It covers core-only/all-width/reduced/optional-only demand, native identity
  of both 16-bit types and byte, selected string casts, standalone headers, rejected
  obsolete configuration, export/provider errors, dependency cycles, failure
  atomicity, cleanup, metadata relocation and actual compiler queries/depfiles.
  Across cpp/php/js it executes fresh and incremental emission after affecting edits,
  deletion, repeated failure and recovery. Restarted project compilers reuse unchanged
  applications/native directories for independent spelling-only changes and provider
  relocation, checking execution and subsequent compiler/Ninja no-ops.
- All 179 registered CTests pass across selections: the main 175-test run,
  the three frozen contracts (102.45 seconds), and the final provider CTest
  (99.05 seconds), including explicit scalar-only header/source exclusion checks.
  The frontend-service test
  requires Unix sockets denied by the sandbox and passes when rerun with that access.
  Native coverage includes scalar/conversion boundaries, failed writes, string,
  vectors, carriers, hash, boxed/results, JSON, datetime and ownership. Compiler
  writing checks pass for 154 files; diff whitespace checks pass.
- Reviewed frozen contracts retain **130 accepted / 247 rejected runtime cases**,
  **19 accepted / 537 rejected scalar cases (2,692 maximum operations)** and
  **17 accepted / 37 rejected CLI cases**, plus publication lifecycle proofs.
  Changes are declaration placement, dependent header includes, prepared provider
  and signature dependency records, inventories and input fingerprints. Custom-header
  cases now relocate the conversion provider too. Direct before/after rendering of
  default and numeric publications confirms their runtime implementation sources are
  byte-identical; their existing declaration headers differ only in required includes.
  The combined scalar declarations preserve existing aliases and null operations.
  Rejection tests targeting retired path slots now target remaining aggregate paths;
  the provider proof separately rejects both retired scalar fields.
- Public contracts and generator documentation now describe the actual placement,
  configure-time inventory and retirement. Native-alias representation, source
  admissions, public helper signature redesign and all other deferred work are unchanged.

Focused measurement from `build/core-scalars-proof-3/results.json`, default provider
publication, milliseconds:

| Flavor | Native wall | Clang frontend | LLVM optimizer | Code-generation passes | LLVM backend total | Link command |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 465.7 | 40.7 | 13.5 | 12.6 | 36.9 | 84.9 |
| php | 410.5 | 32.3 | 13.6 | 11.5 | 36.1 | 79.6 |
| js | 483.7 | 40.0 | 15.3 | 15.1 | 42.2 | 98.6 |

Clang C++23/libc++, O2/g0, mold, sequential application commands, fresh application
objects, no PCH/LTO; each variant runtime is compiled once and reused (default runtime
build: 9.95 seconds). Timings are instrumented single samples under concurrent test
load. Frontend/backend are sums of translation-unit traces; optimizer/codegen are
subphases, not additional independent totals. Native wall includes driver overhead
and linking. These numbers do not establish an overall speedup.

Default native depfiles show core.scalars has no further project-header dependencies;
conversions and string each depend only on core.scalars. Optional numeric scalars
compile independently with no includes. Core-only declarations exclude the conversion,
string, output and custom-size providers. Output's existing C-system-header dependencies
remain unchanged (`-MM` intentionally reports only project headers). Wider benchmark
comparisons and public-interface cleanup remain deferred.

### Dependency ownership review fixes

The review reproduced a preparation/emission mismatch: a catalog edge from
core.scalars to core.conversions passed `prepare-runtime`, while rendering added
the reverse signature dependency and rejected the cycle. The effective graph was
also missing from serialized preparation output.

- `finalizePublicationProviders` now owns publication graph finalization in the
  generator preparation layer. It combines catalog edges and selected signature
  requirements, canonicalizes edges, validates cycles and published dependency
  availability, and records effective nodes/closures plus selected providers in
  `submodules.published`.
- `prepareScalars` finalizes scalar publications. `prepareRuntime` shares the scalar
  preparation stage but waits until all family demand is prepared before finalizing.
  Construction-only queries still return their existing recipe projection, without
  treating it as a header publication. No new framework or cache is introduced.
- Render fragments no longer carry dependency requirements. The assembler emits
  includes from the prepared graph and checks fragment/provider agreement; it does
  not resolve graph edges or perform semantic dependency validation.
- Compiler dependency queries remain generated PHP code. No application-time JSON
  interpretation, graph traversal, new width admissions or include-collector branches
  were added. The relocated compiler publication remains byte-identical to the
  pre-review version. Checked conversions retain their separate provider and existing
  signatures/behavior; all previously listed deferred work remains out of scope.
- Regression coverage compares preparation output with serialized emission plans,
  checks effective signature edges and closures, and requires matching rejection of
  cycles and unpublished dependencies before modifying existing outputs. Both scalar
  and runtime entry points are covered. A runtime publication depending on a later
  string family succeeds; its scalar-only projection correctly fails.
- The CMake regression removes numeric demand in an existing relocated build, checks
  automatic reconfiguration, retirement of the optional header and registered output,
  and a subsequent no-op. Restoring demand recreates the same output inventory and
  again reaches a no-op, without manual clean/configure.

Verification for these fixes:

- The dedicated provider proof at `build/scalars-ownership-fix` passes, including
  CMake numeric-demand removal/restoration, all-flavor incremental recovery, cached
  rebuilds and publication freshness. The focused preparation regression also passes
  the later-family finalization case added during the fix.
- Reviewed captures retain 130 accepted / 247 rejected runtime cases, 19 accepted /
  537 rejected scalar cases (2,692 maximum operations), and 17 accepted / 37 rejected
  CLI cases plus lifecycle checks. Only `prepared.json` hashes change in all three
  frozen contracts; all other artifact hashes and case inventories are unchanged.
  The eight generated compiler PHP files in the relocated proof are byte-identical.
- The native build passes; the subsequent build performs only freshness checks,
  without native compilation/linking. The 174-test regression selection passes
  (109.42 seconds); the frontend-service test passes with required Unix socket access
  (4.51 seconds). All four final contract/provider CTests pass as well, including
  the complete preparation/emission and CMake demand-removal regressions. Together
  these runs cover all 179 registered tests after the ownership fix.
- Compiler writing checks pass for 154 files, and `git diff --check` passes.
  No new application compilation overhead or speedup is claimed: compiler queries
  and emitted C++ are unchanged. The existing focused timing record remains applicable
  to those unchanged artifacts; this fix concerns generation-time ownership.

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
