# Plan: accounting-shaped concrete C++ specialization
Doc Status: planning

Date: 2026-10-19
Status: first bounded integer/vector slice implemented; remaining work follows the [agreed decision](cpp_template_prelowering_decision_2026_10_19.md).
This document records the implementation sequence. The [current coverage guide](../../compiler/my-try/docs/architecture/cpp_template_prelowering.md) distinguishes implemented behavior from remaining work.

## Active integration sequence: concrete type substitution

1. Reuse the type-binding owner to spell native implementations with recursively
   selected concrete arguments. Reuse the existing lifecycle generator and engine.
   Resolve dependent layout probes in publication order through the native cache.
   First prove nested runtime applications with real registry/prepared types; this
   foundation is implemented; the source integration below consumes it.
2. Integrate concrete source fields and signatures in the existing declaration
   emitter. Publish one source payload definition and resolve its layout dependencies.
   Preserve ordinary field/reference access and evaluate recursive source-type graphs
   explicitly. No new AST, semantic pass or parallel registry. The [inline source
   checkpoint](results/prelower_source_fields_2026_10_19/README.md) now covers ordinary
   fields/signatures/source methods and fragment replay. The [source argument
   checkpoint](results/prelower_source_arguments_2026_10_19/README.md) adds acyclic
   source class/record payloads through emitted header dependencies. Recursive
   header/layout graphs remain open.
3. Bind ordinary operations/members to concrete interfaces from existing prepared
   decisions and runtime bindings. Prove nested mutation, constructors, references,
   providers and allocation/copy parity before enabling broader application coverage.
4. Consolidate the lowering setting around the proven substitution path. Keep
   ordinary C++ as the baseline; remove superseded experimental routing rather than
   maintaining an expanding matrix of permanent flags.

Cleanup is incremental and proof-gated. Keep specialization identity/demand, native
lifecycle generation, layout probes/cache, publication and reusable tests. Retire
source-call/constructor opaque transport once ordinary emission with substituted
types passes equivalent tests. Retire handwritten accounting recipes after the
general path covers their workload. Keep native-address transport only for interfaces
that still require original native storage. Archive historical performance evidence;
rewrite current guidance so it describes one intended architecture.

## First-slice checkpoint

The [integer/vector proof](results/prelower_first_slice_2026_10_19/README.md) covers
general recipes, runtime integer widths, source-record elements, fragment replay,
option switching, error parity and native object reuse. Steps below remain the
expansion sequence; full accounting coverage and the runtime-family review are not
marked complete by this checkpoint.

## Target and baseline

Implement `prelower_templates` inside the existing C++ backend, with a dedicated
specialization engine and compact reference structure. Use the current AST and
prepared facts directly. The first vertical slice is general integer-wrapper and vector-family recipes.
`vector<int>` is the first test, not a hard-coded implementation. Exercise additional
element types, integer widths and signedness through the same recipes; expand toward
the accounting inventory after that slice is proved.

The measured reference is [the accounting conversion](results/accounting_template_free_2026_10_18/README.md):
15 application units, 44 concrete handles, 24 concrete vector/hash representations,
and three grouped support units. Its `manifest.json`, `converted/concrete.hpp`,
`base.hpp`, `base.cpp`, `containers.cpp`, `objects.cpp` and behavioral probes provide
concrete examples. The bounded text converter is evidence, not production code to
insert after emission.

The completed first experiment used these choices (the generalization below supersedes
handwritten family recipes; original runtime templates may remain in support units):

- Concrete declarations and out-of-line implementations, not aliases or explicit
  instantiations of the existing runtime templates.
- Group support into base/container/object implementation units as applicable.
  Do not split every method/type into a separate native compilation yet.
- Generate all methods in each admitted implementation family. Unsupported semantic
  operations are rejected/reported explicitly, never silently implemented differently.
- Ordinary C++ automatic cleanup and dispatch; no full C or new lifetime lowering.
- No PCH for concrete proof builds, debug disabled by default, explicit debug opt-in.
  Keep native optimization and debug configuration separate from lowering options.

## Early runtime-template review

The initial [family-by-capability matrix](runtime_template_capability_matrix_2026_10_19.md)
and [runtime source index](runtime_template_source_inventory_2026_10_19.md) now distinguish
existing registry facts, missing bindings and internal helper exposure. Extend those
existing owners; do not introduce another registry or multiply handwritten family recipes.
The [representation and ownership audit](prelower_representation_audit_2026_10_19.md)
identifies the existing owners, current duplication and the next proof: direct or
in-place opaque storage, borrowed transport, target layout and nested-reference ABI
compatibility. The [native counter/identity experiment](results/prelower_boundary_2026_10_19/README.md)
proves the storage/borrow mechanism without extra measured allocation or payload
copy. The [first ABI integration slice](results/prelower_layout_integration_2026_10_19/README.md)
now consumes prepared observation signatures and existing bindings, publishes grouped
layout probes, and resolves/cache-validates them in the native builder. The [ordinary-expression checkpoint](results/prelower_expressions_2026_10_19/README.md)
now adds opt-in runtime ABI emission, operation replay and shared prepared-path lowering.
The [nonempty-initializer checkpoint](results/prelower_initializers_2026_10_19/README.md)
adds the existing runtime buffer through the same request/layout owners. Broader
call/iteration/reference transports remain deferred.
The [source-payload checkpoint](results/prelower_source_payloads_2026_10_19/README.md)
adds one native source definition, demand-driven ownership/value representations,
default construction and source payload container arguments. Only requested forms
and their dependencies are generated; do not eagerly produce every ownership form.
Fixed-parameter source instance calls now reuse existing prepared calls and the
ordinary argument-sequencing emitter, including retained shared/borrowed inline
receivers and owned results. Selected positional constructors reuse this call
transport, existing allocation selection and final result storage. Property crossings,
static/provider methods, reference results and variadic/observation transport remain
follow-ups using the same prepared call/place owners.
The [concrete-composition proof](results/prelower_composition_2026_10_19/README.md)
supports the user's preferred next direction: define source fields as real concrete
owners, allowing ordinary member calls/references. Integration must consistently
select physical template arguments and resolve dependent layouts through existing
bindings/build owners. The [backend binding foundation](results/prelower_substitution_2026_10_19/README.md)
now integrates concrete physical arguments and dependent layout caching. The [source-field
checkpoint](results/prelower_source_fields_2026_10_19/README.md) adds bounded inline-record
fields/signatures and ordinary source member emission. The [source argument
checkpoint](results/prelower_source_arguments_2026_10_19/README.md) now covers acyclic
source payload dependencies while leaving method bodies in source units. Runtime
forwarding interfaces and recursive header/layout graphs remain the next steps.
The [native-place proposal](prelower_native_places_2026_10_19.md) is deferred for
source fields and retained for boundaries that still expose original native storage.
The [ordinary-expression contract proposal](prelower_expression_integration_2026_10_19.md)
details the missing backend request replay and native-address transport, and the
prepared nested-read/write distinction. Its retained contract is agreed; the linked expression checkpoint records implemented coverage.

Before locking the initial representation interfaces, inventory runtime template
families and review them explicitly. Discovery must include runtime headers, operator
helpers, metadata bindings and generated adapters; metadata alone is not a complete
inventory. For each family record its purpose, canonical arguments, prepared facts /
capabilities consumed, internal template dependencies, current measured cost (or
unknown), ABI/behavior obligations and proposed disposition. Discuss every discovered
family; do not assume that unmeasured means cheap or that every template needs removal.

The initial checklist includes fixed-width/signed integer wrappers; vector/hash;
shared/unique/weak and value ownership; nullable/result/sentinel wrappers; dynamic
representations; callable adapters; initialization buffers/factories; iteration,
indexing and keyed-path helpers; conversions, arithmetic/operator helpers and traits;
compiler Storage/Keyed_Storage where exposed to generated native units; and templates
inside runtime implementation providers. Expand the checklist from actual discovery.
Distinguish source-facing generic types, compiler-only storage and implementation-only
helpers so the discussion does not silently widen the application backend scope.

Record an explicit disposition: implement in the initial slice, discuss for the next
slice, or retain templates for now with a reason. Integer representations are included
from the start: derive native width/signedness from registered facts, rather than
leaving `int_t<Rep>` beneath the concrete vector. Runtime bindings may differ; concrete
type combinations must be generated from data, without family-specific engine branches.
Selective methods, operation closure and ABI bridges remain explicit follow-up topics.

## 1. Establish configuration and engine ownership in one vertical slice

Inspect existing generator invocation/configuration and retained fragment structures
before choosing exact field/class names. Add a dedicated typed C++ lowering-options
structure containing `prelower_templates`, carried from CLI/API through emission.
Keep the existing path as the default during initial opt-in integration.

Place the engine, its entry data and compact reference alongside the existing C++
backend. Keep workers and records separate according to local ownership rules. A
request receives existing canonical type information and the supported operation
family; it returns a dedicated reference. Start with one entry ID in that reference
if existing storage conventions permit it without introducing a new retention model.

The engine resolves readable, namespace-scoped names, deduplicates requests and
provides their declaration dependencies. Record representation policy and versioned
implementation inputs explicitly. Separate the stable emitted name from artifact
content hashes and process-local IDs. Prove collision handling for distinct source
types sharing a short name, and request-order-independent names.

The emitter's boolean branches dispatch to named methods. The engine, not those
branches, generates specialization bodies. Avoid a broad emitter refactor first.

## 2. Produce and execute one concrete integer vector

Request `vector<int>` from a supported source program using existing prepared types
and resolved operations. Generate its concrete declaration, typed storage, growth,
indexing, iteration, copy/move/destruction and admitted family methods. Reuse the
accounting implementation shape after checking each behavior against the owning
language/runtime contracts.

Inventory scalar and ownership dependencies first. Generate concrete integer
bindings from registered width/signedness and preserve the production conversion,
arithmetic and error contracts. Do not assume the experiment's signed-64 wrapper
implements every integer rule. Audit remaining template exposure and label it
explicitly; no silent integer-template fallback is allowed in the concrete path.

Compile generated application and grouped support units with Clang through the
existing native builder, then link and execute them. Keep lowering and native build
configuration distinct. Unsupported concrete families must produce a clear coverage
diagnostic during this bounded opt-in mode; no silent fallback concealed by the flag.

Acceptance: equivalent results through both paths for empty/growing vectors,
indexing and bounds failure, independent copies, self-assignment, iteration and
cleanup. Add negative/error probes appropriate to the admitted production contract.
Audit preprocessed exposure, not just the absence of angle brackets in app.cpp.

## 3. Prove publication, reuse and invalidation

Integrate generated headers/support units into the existing artifact membership and
publication transaction. Finish engine generation before publishing readiness.
Clang object reuse remains owned by the existing native builder.

Explicitly account for retained emitter fragments: their unchanged consumers must
still contribute required specialization artifacts to a new publication. Either
reconstruct requests from existing dependency records or retain them with an explicit
valid lifecycle. Do not let an invocation-local engine silently omit support needed
by a cached fragment. Do not introduce a new generic lifecycle framework by default.

| Change | Required proof |
|---|---|
| Repeated request in one emission | One specialization definition; consistent reference/name |
| Unchanged rebuild | Same generated bytes; native objects reused |
| Application body only | Unchanged specialization support reused |
| Toggle lowering option | Correct artifact membership and executable; no stale cross-mode reuse |
| Toggle native debug | Semantic/lowering output unchanged; correct native variant selected |
| Change implementation recipe | Affected support rebuilt even if source type identity is unchanged |
| Request removed/source deleted | Unused support membership removed without breaking retained consumers |
| Generation failure then repair | No partial ready publication; valid recovery |

As handle/vector-of-class support is added, prove body versus payload-layout versus
capability changes separately. Avoid depending on payload layout merely because a
handle names the payload; retain dependencies wherever destruction, construction or
actual field access needs them. Do not infer ABI compatibility from matching names.

## 4. Expand toward the measured accounting representation

Expand through concrete shared handles/factories and vectors of handles, then the
ordered hash families needed by accounting. Include scalar/string/common ownership
support necessary to avoid pulling the original template-heavy umbrella into every
consumer. Follow the measured grouped support layout first.

Use existing canonical identities and capability queries for each family. Repair
shared preparation only for a demonstrated missing semantic fact. Representation
names, generated method bodies and artifact dependencies belong in the engine.

Compare production contracts with the experiment before adapting each implementation:
integer overflow/conversions, string byte/codepoint behavior, ownership and copy
identity, iteration/removal order, failure types/metadata and allocation behavior.
Do not import the experiment's simplified failure hierarchy, unused argv substitution
or lack of weak/reflection support as silent production behavior. Existing runtime
ABI boundaries need explicit support or a clear rejection; concrete and templated
objects are not interchangeable by default.

Run the accounting application's existing 998 checks when its source features are
supported by this backend. First audit that source against my-try coverage: the
historic experiment started from another generator's output. Any source-feature
blockers must be reported separately, not solved by adding an unplanned language
feature or claiming the artifact conversion proves my-try source support.

Reuse the additional accounting probes for container growth/copy/order, aliases,
shared replacement, cleanup and string/numeric boundaries. Use ASan/UBSan where
relevant, recording the existing LeakSanitizer limitation and what owner counts do
and do not prove. Runtime behavior/performance must be checked before making the
concrete path a general default.

## 5. Measure the production path against the experiment

Use identical supported workloads and toolchain configuration for both emission
modes. Keep application partitioning and support grouping comparable. Compare the
existing runtime PCH path, improved PCH where available, and concrete/no-PCH output.

Report separately:

- Keep source preparation, emission and engine generation outside the initial native
  timing comparison, as agreed. Measuring the extra lowering cost is deferred.
- Application compilation, grouped support compilation, linking and PCH setup.
- Cold native work, unchanged reuse and representative edit/rebuild work.
- O0 and O2 with debug disabled on both sides; an explicit matched debug-enabled run
  connects the new measurements to the historical accounting table if needed.
- Artifact/source hashes, commands, repetitions and exact behavioral results.

Start with serial native measurements and at least three varied-order repetitions,
with no object reuse inside fresh-compilation rounds. Do not compare a cached support
build with a cold baseline without labelling the difference. Do not attribute all
savings to template syntax: concrete runtime representation also contributes.

The historical sixfold whole-build advantage is evidence and a comparison point,
not a guaranteed threshold for the first integer-vector slice. Investigate material
regressions before expanding families. Fine-grained native partitioning, further
header reduction and parallel scheduling need their own later measurements.

## Deferred extensions

Selective method generation must include the transitive dependencies of requested
methods. Clang cannot supply omitted concrete definitions. Keep the type name stable
when additional methods are requested; operation coverage and artifact freshness are
separate concerns. Generate all family methods first.

Background generation may later separate request/name resolution from completion.
Workers would consume settled immutable inputs, not mutate the AST/preparation state.
Publication must wait for all required outputs and propagate failures.

Persistent engine caches, one-object-per-specialization layouts, full debugger
support and generalized template families are outside the first implementation.
Source-facing debugging remains compiler-owned in direction; see the
[debugging note](compiler_owned_debugging_2026_10_18.md).

## Completion criteria

The first production slice is complete when configuration reaches the existing
backend, one real specialization is generated through the engine, its compact
reference is used by consumers, both paths execute equivalently, and publication /
reuse / configuration switching are proved. Record coverage and remaining limitations
before expanding. The broader accounting checkpoint additionally requires its full
behavioral proof and a matched native compilation comparison.
