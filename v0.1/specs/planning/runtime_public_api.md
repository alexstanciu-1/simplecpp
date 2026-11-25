# Runtime public API migration plan
Doc Status: planning

Date: 2026-10-27. Planning baseline: `94361018b`.
Status: paused by the user on 2026-10-28 pending runtime restructuring in another chat.

## Current core completion inventory and resume instructions

The [runtime-core completion plan and paused handoff](runtime_core_completion.md)
contains the current implementation status through `5a6d69f4e`, explicit remaining
work and resume instructions. Read it before continuing; the original phases below
are planning history and must be reconciled with the restructuring outcome.

The [runtime/public API simplification side audit](runtime_public_api_simplification_side_audit_2026_10_28.md)
records redundancy, ownership and retirement concerns for that restructuring. Its
older inspection snapshot requires revalidation. Neither this pause nor the audit
changes the agreed language contracts or declares runtime-core completion.

## Goal and terminology

Make the Simple C++ runtime directly consumable through focused, fully template-free
public C++ interfaces. This is the target for the migrated surface; existing
compatibility exceptions are transitional, not part of the finished public API. Runtime-owned generation and compiled adaptation implement
that boundary. **The public API is the boundary; there is no separate public
"runtime middleware" product, semantic layer, or additional namespace to learn.**

Preserve the runtime's source-language contracts, native behavior, ownership,
memory safety, concurrency and useful performance. Consumers should include a
facility without parsing unrelated facilities or its template implementation.
Keep templates where they are useful behind the boundary. Do not merely move the
same transitive includes between public headers.

This plan covers runtime core, shared operations, modules, their build/publication
boundaries, and eventual consumer migration. It does not implement the C++ compiler's
parser or semantic stages, invent language semantics, promise a stable cross-toolchain
ABI, or make every conceivable generic application a prebuilt runtime type.

## Evidence and existing assets

Concrete integers and the focused worker-pool API already demonstrate independent
inclusion. Compiler middleware now re-exports those exact integer classes.
Compiler-only inputs were already template-free; compiler support still includes
full runtime APIs. Moving scalar ownership alone therefore did not establish a
whole-compiler build speedup.

Fresh include-only preprocessing on WSL/Clang 18.1.3, C++23, tasks enabled:

| Header | Preprocessed bytes |
| --- | ---: |
| `scpp/integers.hpp` | 5,232 |
| `modules/tasks/worker_pool.hpp` | 5,358 |
| `scpp/string_t.hpp` | 1,485,067 |
| `scpp/runtime_error.hpp` | 1,248,519 |
| `modules/json/document.hpp` | 1,631,240 |
| `modules/filesystem/filesystem.hpp` | 2,550,196 |
| `modules/tasks/tasks.hpp` | 2,959,658 |

These are include footprints, not measured compile times or independent costs that
can be summed. Shared headers occur through multiple paths. Audit complete consumer
include graphs as well as individual headers.

Existing reusable foundations:

- [Runtime-core public API rules](../../runtime/specs/core_public_api.md).
- Runtime-owned catalogs and native representation recipes in
  [module metadata](../runtime_module_metadata.md).
- [Concrete interoperability protocols](../../runtime/specs/concrete_type_interoperability.md):
  layout/trait probes, native outer storage, concrete stored arguments, lifecycle,
  borrowed arguments and construction of owned results in final storage.
- The old compiler's [specialization architecture](../../compiler/my-try/docs/architecture/cpp_template_prelowering.md)
  and [coverage evidence](runtime_template_capability_matrix_2026_10_19.md).
  Reuse the protocols and proven mechanisms; do not treat historical coverage as
  proof that the new API works, or transplant the whole semantic compiler into runtime.
- Existing runtime operator generation/configuration and compiler publication,
  header-closure checks, sanitizer tests and timing tools.

## Contract constraints

| Authority | What migration must preserve |
| --- | --- |
| [Layering](../architecture/runtime_layering.md), [semantic ownership](../architecture/runtime_design/semantic_consistency.md) | Core does not depend on modules/languages; one semantic implementation per operation family. Concrete overloads delegate rather than create a competing truth table. |
| [Runtime spec](../../runtime/specs/spec.md), [operator generation](../../runtime/specs/operator_generation_flow.md), `runtime/specs/config.json` | Supported operators, conversions and failures remain configured and explicit. Focused concrete declarations must become a documented route to the same authorities; reconcile the existing mandatory-umbrella wording when expanding beyond the bounded integer exception. |
| [Module metadata](../runtime_module_metadata.md) | Source identity/capabilities and physical C++ bindings stay separate. Publication lists select existing types/operations; they are not another semantic type registry. |
| [Array semantics](../array_semantics.md), [initialization](../data_initialization.md) | Checked reads, mutation, ordering, copying, references and exception cleanup. Typed containers remain independent values; do not recursively unwrap and copy their elements just to cross the boundary. |
| [Mixed/dynamic](../dynamic_types.md) | Separate families: mixed containers retain their value/COW contract; dynamic containers retain shared identity. No generic boxing fallback to avoid specialization. |
| [Error/take](../error_values_and_take.md), [presence helpers](../count_empty_isset_contract.md) | Preserve null, false, true, no-error and failure distinctions; checked extraction, output-preservation rules, lazy probes and collection-only emptiness. Do not expose compiler convenience throwing calls as replacements for runtime Result contracts. |
| [Concrete interoperability](../../runtime/specs/concrete_type_interoperability.md), [compact layout](../compact_layout_types.md) | Actual object lifetime, target-derived size/alignment and special-member traits. Layout equality is not permission for byte-copying unrelated objects. |
| [Source buffers](../compiler_support_runtime_contract.md), [async/tasks](../async_await.md) and module-specific specs | Owned snapshots versus checked borrows, invalidation, callback lifetime, concurrency, cancellation and publication semantics remain owned by their existing facilities. |

The current concrete integer API is bounded; it is not automatically the full
language integer surface. Existing interoperability uses explicit opt-in traits
such as `scpp_runtime_integer_rep`. Audit their interaction with the new classes
before composition; simply adding a marker could admit overlapping template
operators. Preserve one intended concrete overload route.

## Target organization

Consumers see only public facility headers and link their owning targets:

```text
consumer source
    -> focused runtime public headers
    -> compiled core / selected module implementation
                         -> private templates and semantic helpers
```

Generation produces concrete declarations and implementation units where required.
It is a build mechanism behind this API, not a fourth user-facing library layer.
Logical ownership remains core, shared semantic families, modules and language
adapters. Avoid a single generated library/header that pulls every module together.

Proposed physical boundary, to establish incrementally:

```text
runtime/public/          # published include root; focused scpp/ and module paths
runtime/include/         # existing implementation/compatibility tree during migration
runtime/tools/           # reusable physical API generation/probes
runtime/metadata/        # existing source contracts and representation recipes
```

The exact folder name is a mechanical choice. The important rule is that ordinary
public consumer targets receive only the public include root. Implementation targets
receive private implementation roots. Existing broad-header consumers use an explicit
compatibility target during migration. CMake's current configuration target exports
the whole implementation include root, so moving files alone is insufficient.
Preserve module feature selection and transitive linking in static/shared builds.

Keep `scpp` and existing module namespaces. Public types may contain small native
representation fields and inline accessors/constructors. Headers must include only
what their declarations/layouts require. Use forward declarations where sufficient;
by-value fields still require complete types. No global `using namespace`, giant
mandatory include, or universal opaque object interface.

Runtime-owned common types have one owner. The compiler gradually stops carrying
parallel string/container/host facades where the runtime API can serve directly.
Compiler-specific model/processing remains compiler-owned; publishing a generic
runtime API does not move tokens or source-processing policy into runtime core.

## Generic types and representation

There are two demands on the same API-generation mechanism:

1. Runtime-owned common types: ship selected concrete types required by public
   module signatures and demonstrated common usage, with their compiled operations.
2. Application-owned types: generate the required concrete interfaces for actual
   applications such as `vector<MyRecord>` or `shared<MyRecord>`, outside the
   reusable runtime's own build. Their implementations may instantiate runtime
   templates privately and include the application's required model definitions.

Do not enumerate every type/operation product. Normalize canonical demands and
reuse existing metadata, capabilities and physical recipes. A finite common-type
publication cannot cover arbitrary future application types. Until the reusable
physical generator is ready, bounded handwritten/generated declarations are valid;
unsupported demands must fail clearly, not silently fall back to public templates.

Reuse the agreed [concrete naming rules](../../compiler/my-try-cpp/docs/concrete_names.md)
(short-name mappings, registered families, canonical identity and depth encoding).
Promote reusable naming tooling when this phase needs it; do not grow an independent
runtime naming algorithm. Preparation still decides semantic validity; generation
materializes an already selected physical interface.

### Agreed specialization reuse — 2026-10-27

Use **one prelowering mechanism, one naming scheme and one concrete definition per
canonical specialization**. The runtime generates the types its public operations
need, including their transitive type dependencies, and publishes concrete headers
and compiled implementations. Compiler middleware and other consumers reuse those
specializations instead of generating parallel classes with their own identities.
For example, a runtime API returning `result<vector<string>>` must publish the
required string, vector and Result representations and the operations it exposes.

Application-specific demands such as `vector<MyRecord>` use the same mechanism
and canonical naming rules, but build with the application because the reusable
runtime cannot know future application types. This is a distinction in generation
inputs and build ownership, not a second generator or public API layer.

Publish a specialization inventory with each package, extending existing publication
metadata. It records canonical specialization identity, concrete name, owning package,
public headers, implementation artifacts, provided operations, dependencies and
representation/build compatibility. This is an inventory of physical artifacts,
not another semantic type registry. Names must agree across runtime and application
generation; names alone do not establish compatible definitions.

Before generating a demanded specialization or operation:

1. Resolve its canonical identity through the existing type/recipe machinery and
   inspect the selected dependency inventories.
2. Reuse compatible published definitions and operations directly.
3. Generate missing specializations in the package owning the demand. If only an
   operation is missing, use the existing concrete type and generate an admissible
   operation against it; never regenerate the type to supply that operation. If the
   published interface cannot support it, reject and request an owning API extension.
4. Reject conflicting name claims, duplicate definition ownership and incompatible
   representation/build identities. Do not silently select a different definition.

### Approved API completeness and core linkage — 2026-10-27

For each selected concrete type, publish its complete supported ordinary API,
including its supported construction, lifecycle, access, mutation and iteration
operations, rather than only the methods the runtime itself happens to call.
Operations requiring additional types are generated when demanded. This does not
add operations forbidden by the owning contracts or eagerly enumerate all type pairs.

The new public API always permits a runtime-core link requirement, including when
optional modules are disabled. Preserve the existing disabled-module errors. Retain
header-only behavior only for legacy compatibility consumers during migration.
Task-specific disabled policy and diagnostic arguments remain owned by tasks; core
supplies general compiled error machinery and must not acquire task-module policy.

Template freedom applies to the complete operation surface: constructors,
destructors, indexing, iteration, conversions, callbacks and returned wrappers,
not merely class declarations. Implementations may instantiate templates privately.
Unsupported public paths must reject rather than expose templates as a fallback.

Choose representation per contract, not one global pimpl policy:

- Native-sized concrete scalars, with cheap representation operations inline.
- Existing handles where ownership already is handle-based.
- Inline concrete or layout-probed opaque storage for value objects when appropriate,
  with real construction/destruction, alignment and exceptional cleanup.
- No extra heap wrapper, refcount layer, full container copy or element conversion
  solely to hide a template without measured and explicitly accepted justification.

Keep nested stored arguments concrete where the interoperability recipe requires it.
Methods may own representation/invariants; processing stays with its owning workers.
Use friendship only for required privileged access. Keep files below 200 KB and
split generated units by actual dependency/operation groups rather than arbitrarily
creating many tiny files.

## Migration phases and review gates

Each phase is implemented in reviewable vertical slices. A phase is not permission
to change all its owners at once. Report a newly required cross-owner redesign before
implementation. Check the shape and evidence before selecting the next slice.

| Phase | Work | Completion evidence |
| --- | --- | --- |
| 1. Inventory and enforce the public boundary | List public operations, their argument/result/ownership/error types, all transitive dependencies, semantic owner, current compatibility limits and consumer paths. Establish the restricted public include target using integers and worker-pool as existing proofs. | Independent consumer builds with only public roots; no private headers/PCH reachable; positive/negative include tests; baseline include/compile graph. |
| 2. Complete a useful core path | Design string/byte input, error inspection and textbuilder together, then implement a bounded path such as build text, inspect bytes and report a checked failure. Reuse compiler facade knowledge but preserve native runtime contracts and avoid inheriting its extra wrapper allocations automatically. | Entire caller include closure is clean, including failures. Embedded NULs, copies/moves, lifetime and cleanup tested; allocations/copies compared at O0 and production O2. |
| 3. Resolve wrappers and common collections | Use the shared prelowering mechanism and published specialization inventory to add/reuse selected nullable/Result and ownership/container specializations needed by real public APIs. Start with a complete operation, not just class declarations. Exercise nesting, references, moves, extraction and failure. | For example, a collection of strings and a Result carrying it can be created, accessed, returned, copied/moved and destroyed without template leakage or extra boundary copies. Unsupported combinations reject. |
| 4. Migrate module paths | Expose filesystem, JSON and other used facilities through their correct runtime Result/handle contracts. Migrate generic task execution after its collection, callback and result dependencies have concrete interfaces. Inventory remaining optional modules rather than declaring coverage from these examples. | Standalone module clients; enabled/disabled and static/shared linking; no unrelated module inclusion; exact behavior and ownership parity. Tasks also prove worker reuse, concurrency, cancellation/errors and callback lifetime. |
| 5. Remove heavy paths from compiler support | Replace general compiler facades/adapters with direct public APIs as each operation path becomes complete. Generate compiler-specific specializations against its model where required; keep processing in support. | Compiler and relevant support units no longer reach the displaced templates through alternative paths. Full src/support rebuilds, incremental edits and sample behavior/performance compared. |
| 6. Consolidate runtime implementation and consumers | Split broad private dependency hubs and the core implementation bundle where measured dependency ownership warrants it. Migrate other approved consumers; retire obsolete facades and compatibility paths only after their inventory is resolved. | An implementation edit rebuilds its real dependents, not unrelated modules; public API coverage/exception inventory complete; no duplicate semantic owners or stale exposed headers. |

Private include cleanup may accompany an earlier phase where it belongs to the
same touched owner. Public API hiding improves caller compilation; reducing the
runtime's own rebuild cost additionally requires focused implementation dependencies
and compilation units. Measure both instead of assuming one implies the other.

## Errors and callbacks need explicit choices

The new disabled task API links core and calls its general compiled error machinery
from a small task-owned adapter. It requires no tasks implementation library.
The task adapter owns the disabled-module condition and diagnostic arguments;
core only constructs and throws the requested runtime error. The legacy template
API retains its no-link fallback during migration. This linking decision is approved;
error inspection remains part of the subsequent string/error API slice.

Callbacks should have concrete signatures and retain their required lifetime.
Evaluate a narrow typed callback/callable representation using existing runtime
transport before choosing an ABI. Avoid exposing `std::function`, per-lambda
instantiations or scheduler internals to public callers, but also avoid introducing
a new scheduling abstraction just to erase types. Whether callbacks are borrowed
synchronously or retained asynchronously is a contract distinction, not a formatting
choice. Preserve exceptions and cleanup across the selected compiled boundary.

## Build, caching and publication

Keep reusable runtime builds and publication explicit, as in the current compiler
workflow. Runtime implementation edits must not silently rebuild a consumer's
pinned publication. Application/model-dependent concrete implementations rebuild
with their owning application/support, not the whole reusable runtime.

Generated outputs require canonical demand identities and precise dependencies:
recipes/config, public representation/layout, target ABI/toolchain/flags, selected
modules and relevant model definitions. Reuse existing cache/publication machinery;
do not use one global runtime hash to force every generated implementation rebuild.
Keep stable package directories, relative references and atomic publication.

Runtime public headers should ultimately be published by their runtime owner.
The compiler's current copy of `integers.hpp` is a transitional packaging mechanism;
remove that copying responsibility when the runtime publishes a narrow public root.
Verify freshness, selected-header/library agreement, relocation, failed-publication
recovery, and invalidation after relevant layout/model/recipe changes.

## Validation and definition of success

For each complete API path:

- Build a caller with public headers only, without runtime PCH; inspect the full
  preprocessed closure for active templates and forbidden implementation headers.
  Track justified compatibility exceptions explicitly, with owners and removal gates.
- Prove runtime/application demands reuse the same concrete names and definitions;
  check nested dependency reuse, missing-operation handling, duplicate ownership
  rejection and incompatible-publication rejection.
- Prove behavior against the owning contracts and existing route, including invalid
  operations, Result states, lifetime, aliasing, borrowing and exceptional cleanup.
  Representation registration or one successful call is not complete API coverage.
- Count boundary allocations, copies/moves and retained bytes; check sizes/alignment.
  Use O0 for unintended transport costs and O2 for production behavior. Run focused
  ASan/UBSan; TSan and concurrency/locking measurements for affected parallel paths.
- Measure cold/repeated caller and implementation builds, include footprint, no-op,
  leaf implementation edits, public-header/model edits and relevant cache reuse.
  Use Ninja 8 jobs by default; report WSL and dev002 separately for milestone timings.
- Check MT/incremental behavior when ownership, publication, storage or scheduling
  changes; do not repeat expensive checks after unrelated presentation edits.
- Keep usual compiler edits/builds below the existing approximate 15-second target
  where practical, without treating a timing target as permission to complicate APIs.

The overall goal is achieved when the selected runtime surface has usable complete
public operation paths, ordinary consumers cannot reach implementation headers,
unmigrated compatibility scope is explicit, the migrated public closure contains no
templates, common types have one runtime owner,
application-specific instantiation stays with its application, and measured behavior,
memory, concurrency and build costs meet the agreed phase gates. No claim of universal
template-free runtime coverage follows from the first migrated modules.

## First-slice completion and next review

The first restricted boundary slice is implemented: runtime-owned public include
root, concrete integer and worker-pool APIs, core-linked task-owned disabled errors,
direct compiler consumption, and a string/error/textbuilder dependency inventory.
See [the inventory](../../runtime/specs/public_api_inventory.md) and
[test/build measurements](../../runtime/specs/public_api_boundary_results.md).
This completes the first slice, not the full runtime inventory or all migration phases.

The ordinary value signatures and representation choices are approved, including
source-span dependencies, copying/transfer, allocations and failure inspection.
Implementation proceeds through the bounded slices in the value plan. Shared specialization generation and
published reuse remain later work under the agreed mechanism; no parallel semantic
registry or separate public middleware layer is introduced by this first slice.

## Inline value storage decision and signature review

Inline, target-probed storage for string, in-band error and textbuilder is approved.
The [approved value APIs](runtime_core_value_api.md) record lifecycle,
ordinary operations, exception inspection and source-buffer/span borrowing.
String, error_value and retained exception_info are implemented. Source borrowing
and textbuilder follow separately.

The value signatures and names are approved, including runtime-only exception capture,
foreign exception propagation and diagnosed outside-catch misuse. Delivery starts
with the complete inline string API and now includes error_value/exception_info;
see the value plan for the remaining slices.


## Comprehensive surface audit for discussion

The [contract audit and proposed delivery order](runtime_surface_contract_audit.md)
now covers containers, boxed families, wrappers/ownership, shared operations,
language/host protocols and all current module catalogs. Runtime public API demand
and application middleware demand use the same lowering mechanics; runtime-published
concrete exports are reused by applications. Raw public templates are excluded,
not the contracts of vector_t, hash_t, mixed_t, dynamic_t or other generic families.
The audit flags composition, dependency and metadata issues; its proposed sequence
and owner corrections remain discussion items, not implementation authorization.

## Composition checkpoint and next batch

The complete scan/vector/Result publication proof and application-owned specialization
reuse are implemented; see the audit checkpoint and runtime boundary results.
The first bounded follow-up batch covers the missing small integer widths and their
concrete key compatibility. It must delegate to existing numeric/key authorities,
keep public closure template-free, and verify boundary behavior before broadening
operators or consumers. Subsequent family/module batches follow the comprehensive
audit; mixed/dynamic are mandatory, not omitted because they are harder to lower.

Small-width/key batch implemented and validated: the public integer family now has
all eight configured widths; private key adapters cover those exact types. This
checkpoint does not claim full numeric operators or canonical value-argument demand
publication. Remaining scope and batch order are tracked in the surface audit.

## Future debt: production executable LTO / ThinLTO evaluation

Review whether and how full LTO or ThinLTO should be offered as an optional
production executable build configuration after the metadata-driven public API
boundary is established. Keep the normal fast development build as the baseline;
this debt does not enable either mode now.

Evaluate cross-boundary optimization between generated adapters, runtime libraries,
application middleware and application code. Verify the required compilation and
publication configuration for each participating artifact, including toolchain and
linker compatibility; do not assume a final executable link flag alone is sufficient.
Keep differently configured dependency publications distinguishable and reusable.

Compare the baseline, ThinLTO and full LTO using representative production workloads:
execution time, executable size, allocation behavior, clean/incremental compilation
and link time, peak build memory, and correctness/sanitizer coverage as applicable.
Determine whether any runtime gain justifies the production build cost, which
artifacts must participate, and whether a separate production profile is worthwhile.
Do not assume that LTO removes boundary overhead or that either option is beneficial
without measurements.

## Metadata-driven C++ generation checkpoint

The follow-up design replaces family-specific emission with a reusable C++ library
and thin CLI. Type/operation knowledge belongs in API metadata, including small C++
binding snippets where useful; generator mechanics remain independent of named
families. The first implemented closure is scan/vector/string/Result with its
nonmovable cursor. See [the generation contract](../../runtime/specs/public_api_generation.md)
for ownership, schema, transport, probing and publication. Python retains only build
orchestration; canonical naming resolution also delegates to the C++ library.

Do not interpret this checkpoint as migration of every leaf or runtime module.
The existing audit batch order remains, with new families described as metadata and
existing native behavior reused. Generalize reusable mechanics only when a concrete
contract requires them; do not accumulate named-family branches in the generator.

### Defaults and nested-container follow-up

Template defaults now flow from API metadata through shared C++ resolution, canonical
naming, dependency planning and generation. Earlier-parameter references, nested and
chained defaults resolve before specialization reuse. Compile/lifetime coverage is
in `tests/runtime/native/test_defaults_composition.py`: generated defaulted families
and `vector<vector<string>>`, reusing the runtime's concrete inner vector. This does
not add template-template arguments or general value-parameter API emission.

Validation for this follow-up: seven naming tests; metadata tests for defaults,
canonical reuse, conflicts and malformed/recursive declarations; Release and
ASan/UBSan/leak-detection compile/lifetime tests; all 13 compiler tests passed.
The compiled fixture seals its own generated archive and proves that a subsequent
request using omitted defaults reuses the explicit published identity with zero new
implementation units. Only generation tooling and test specialization units needed
compilation; runtime-core implementation was reused.

Reproduce the composed proof with `python3 tests/runtime/native/test_defaults_composition.py
--package compiler/my-try-cpp/dependencies/runtime`. Add `--sanitized-core
compiler/my-try-cpp/build/sanitizers/runtime/libscpp_runtime_core.a` to instrument the
generated fixture and link the existing sanitized core (run with leak detection in
an environment without ptrace). `SCPP_API_GENERATOR` can select the sanitized C++ tool
as well. Temporary fixture outputs are cleaned after the test.

### Typed hash / nullable proof completed (2026-10-27)

The metadata generator now supports named capability requirements and canonical
parameter expressions in dependent signatures. Typed hash and nullable are recipes
in the existing metadata, including an ordinary companion cursor. Required native
checks share the layout probe; no family-specific generator switch or additional
per-method compilation is introduced. Concrete integer append uses a private hash
policy and the existing runtime algorithm. Structural exported names now reuse
correctly without being registered as authored aliases.

Release, allocation parity, sanitizer/leak and application publication/reuse proofs
are recorded in [boundary results](../../runtime/specs/public_api_boundary_results.md).
No new permanent runtime specialization demand was needed for this proof. Shared
identity keys/reference operations and mixed/dynamic families remain explicit later
work, not implicit coverage of the typed recipe. Generation/probing still costs more
than the tiny public caller; consolidating probes is a possible future optimization,
not a reason to reintroduce per-family generation paths.

### First mixed / scalar publication completed (2026-10-27)

The runtime now publishes metadata-generated mixed and float64, plus its existing
null sentinel through the public include root. Zero-parameter recipes fit the existing
pipeline; canonical identity import needed a local naming correction. The public
surface uses native checked read/write and binding-aware iteration helpers, retaining
owned extraction and mixed value semantics. Native deep-copy/string allocation costs
remain, with no extra wrapper allocation.

The proof covers allocation parity, failed self-alias snapshot, nested values,
replacement/move/destruction during iteration, ASan/UBSan/leaks, and relocated
runtime/application specialization reuse. See the [measurements](../../runtime/specs/public_api_boundary_results.md#first-mixed-value-and-scalar-publication-2026-10-27).

Remaining scope: full floating and mixed operator/conversion surfaces; dynamic and
cross-family scalar conversion; recursive signature versus layout dependency handling;
shared/weak native ownership hooks. These are not implicitly exposed by the recipe.

Follow-up: vector/hash cursor recipes currently guard `next()` with `valid()`. Review
that contract when preservation exhausts the remaining entries immediately after the
last value is consumed. The new mixed cursor handles this with an end-idempotent
`next()`; existing family behavior was not changed in this slice.

### First dynamic / explicit scalar conversion slice (2026-10-27)

Dynamic is another zero-parameter metadata recipe, with native shared-container
identity, owned scalar extraction, explicit container construction, checked reads and
writes, reset and binding-aware iteration. Explicit `to_dynamic(mixed)` and
`to_mixed(dynamic)` delegate to existing scalar/null cast rules and reject containers.
No C++ generator changes were needed. Runtime/application publication reuse, allocation
parity and sanitizer/lifetime proofs are recorded in the boundary results.

#### Runtime debt: dynamic cycle reclamation

Owner: native dynamic container ownership (`detail::dynamic_value` and its shared
table), not API metadata or the generator. Containers currently retain strong shared
references to nested dynamic containers. Self and multi-container cycles are admitted;
reference counting alone cannot reclaim an unreachable cycle. Dropping/resetting the
last external binding is therefore not a reclamation guarantee. The public API
preserves this behavior by explicit user decision.

A future design must decide how to reclaim cycles while preserving shared identity,
alias-visible mutation, reset semantics and active cursor lifetimes. Evaluate ownership
tracking/collection and its allocation, latency and threading costs separately; do not
silently reject cycles or weaken stored edges as a generation workaround. Required
future evidence includes unreachable self/multi-node cycles, reachable aliases, cycles
with active cursors, exceptional writes and concurrent ownership changes. Current leak
proofs break their cycle edges explicitly and make no collector claim.

Next discussion: conversions/casts, truthiness and operators. The current slice only
publishes the already-specified explicit scalar/null crossing between mixed and dynamic;
it does not settle or expand those broader surfaces.

### Agreed numeric promotion direction (2026-10-27)

Accepted design, now implemented by the first global metadata resolver below;
frontend integration remains pending.
Operator admission happens **before** promotion. A conversion ordering is never
permission to use null, bool or string as a numeric operand.

- Identical numeric operand types retain their type.
- Different signed integer widths select int64; different unsigned widths select
  uint64. Byte is the uint8 alias, not a separate promotion family.
- Different floating widths select float64; integer/floating pairs select float64.
  This does not itself introduce float16/float32 runtime types.
- Signed/unsigned integer pairs reject and require an explicit cast. Ordinary
  integer literals remain signed; there is no value-dependent literal exception.
- Explicit casts establish operand types before ordinary promotion. For example,
  int16 plus an operand explicitly cast to int16 stays int16; casting that operand
  to int32 instead selects int64. Casting the final result is a separate operation.
- Documented floating precision loss is accepted. This does not authorize unchecked
  out-of-range integer casts, new overflow behavior or implicit assignment narrowing.
- Arithmetic returns the selected numeric type; numeric comparisons return bool.
  Strict identity does not promote. Shifts, power, compound write-back and other
  special operation contracts remain separately governed, not implicitly expanded.

This changes the current [integer contract](../compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons),
which selects the wider operand rather than the family maximum. That normative
contract and its implementation remain unchanged until the semantic migration.
The global metadata mechanism should select a common type and reuse same-type
operations, rather than enumerate every original operand pair. Runtime publication
and application specialization reuse must use the same mechanism.

#### Admission review findings

- Numeric boxed arithmetic requires int/float payloads; integer boxed bitwise
  operations reject null, bool, float, string and containers. Present numeric
  wrapper delegation is not permission to treat an absent payload as zero.
- [Text contexts](../expression_evaluation.md#bounded-scalar-and-carrier-text-contexts--2026-10-20)
  explicitly admit literal null and bool for `.`/`.=`: null and false render empty
  text, true renders `"1"`. This does not grant arithmetic or equality conversions.
  The current bounded text surface excludes uint64 and has separate carrier rules.
- [Condition truthiness](../conditional_expression_matrix.md#condition-rule-for-ternary)
  is separate from casts: strings `""` and `"0"` are false, other strings true.
  Wrapper absence, errors and sentinels retain their individual contracts.
- Before this slice, ordinary boxed scalar equality had coercive behavior, including
  null equal to zero, bool truth comparisons and numeric-string comparisons
  (`runtime/include/operators/identity/boxed_scalar_equality.hpp`). The explicitly
  approved tightening below replaces those coercions.
- Native string ordering is lexical; boxed ordering is currently numeric-only.
  A uniform string-ordering proposal would therefore require an explicit extension.

Native helper availability alone does not establish source-level admission.

#### Agreed boxed equality and preserved conversions (2026-10-27)

Accepted direction, implemented by the first slice below and recorded in the
checked boxed scalar equality amendment in `specs/dynamic_types.md`:

- Tighten boxed scalar `==` and `!=`. Numbers compare numerically using the
  admitted numeric promotion rules; bool compares with bool; string compares with
  string by bytes; null matches null. Do not parse numeric strings or perform
  truth conversion for equality. Null must not compare equal to zero, false or
  empty text. `!=` remains the negation of `==` for admitted comparisons.
- Preserve existing truthiness, including false strings `""` and `"0"`, and
  existing wrapper-specific condition rules. Equality changes do not redefine
  condition conversion or explicit boolean casts.
- Preserve existing concatenation and text-context conversions, including
  null/false rendering empty text and true rendering `"1"`. Existing bounded
  domains, carrier extraction and errors remain in force; no blanket admission
  of every public type is implied.
- Strict identity retains its separate kind/state-preserving contract. Boxed
  container equality and mixed/dynamic family crossing are outside this scalar
  equality change and retain their current contracts.

Contract review correction: do not introduce a blanket false result for unsupported
scalar equality pairs. The mixed comparison fixture explicitly expects bool/int
equality to throw (`tests/runtime/native/test_mixed_t_dynamic.cpp`,
`test_value_t_comparisons_and_logical`). The conflict with coercive runtime behavior
is already recorded as REL-DEBT-04 in
[release debt disposition](release_debt_disposition_2026_09_09.md).
The higher-level mixed contract delegates to admitted native scalar operations;
it does not authorize additional operators through implicit extraction.

Implementation direction: statically known unsupported pairs reject; boxed pairs
whose active kinds do not admit equality throw, for both `==` and `!=`. Explicitly
admitted null/absence and wrapper-state comparisons remain separate. The previous
recommendation to return false for all incompatible kinds is withdrawn. This is a
semantic reconciliation task completed by the first slice below: the helper now
throws for unsupported non-null scalar kinds, and the old null/empty-string equality
expectation has been replaced. The legacy fixture still has unrelated migration debt.

Null checks should observe the existing null/absence contracts, not falsiness;
carrier error and sentinel states must not silently become null. Any wider
wrapper equality migration needs its own explicit scope.

### First global operator slice (2026-10-27)

The reusable C++ metadata tool now resolves numeric addition and same-family boxed
ordinary equality demands and publishes normalized functions through the existing
operation emitter. Numeric promotion is available through this resolver; existing
frontend integer promotion has not yet migrated. Numeric functions use one selected
type on both sides; mixed original operand pairs do not produce extra definitions.

Checked boxed scalar equality is implemented in the shared native helper and exposed
through metadata-generated public functions. Null checks use null state alone;
incompatible non-null scalar kinds throw. Truthiness and concatenation are unchanged.
The source contract amendment is in `specs/dynamic_types.md`. Broader operation groups,
frontend integration, heterogeneous handwritten C++ syntax and wrapper migrations
remain outside this slice. Existing function publication now records and reuses API
ownership alongside type ownership; no second publication mechanism was introduced.

Validation and single-run compile measurements are recorded in
[runtime boundary results](../../runtime/specs/public_api_boundary_results.md#first-global-operator-metadata-slice--2026-10-27).
Six focused tests pass normally and with ASan/UBSan/leak detection; successful
measured operations allocate zero times. The old mixed fixture's removed dynamic-box
API usage remains separate test-migration debt.
