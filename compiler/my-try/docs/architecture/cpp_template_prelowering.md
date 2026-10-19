# Concrete C++ specialization
Doc Status: supporting

The opt-in `prelower_templates` path uses the existing AST, prepared types and
operation decisions. It does not rewrite syntax or add a semantic pass. The ordinary
C++ path remains the default. The CLI retains the measured integer/vector recipe
baseline; the API also offers the growing general runtime ABI path described below.
Neither currently establishes whole-accounting coverage.

The general path follows [capability-driven generalization](../../../../specs/planning/runtime_template_capability_matrix_2026_10_19.md)
through the existing registry and bindings, using original runtime templates behind
concrete application interfaces. The handwritten recipes described below are the
current measured implementation, not a mandate to add more per-family implementations.

## Runtime-owned native representation recipes

`cpp_types` now describes both ordinary runtime definitions and representations of
existing compiler-known types/modifiers. `CPP_Runtime_Type_Bindings` resolves these
records into the existing `CPP_Type_Bindings`; no extra semantic registry is added.
The core catalog owns scalar, boxed, wrapper and modifier C++ bindings. Compiler
registration retains only the native spelling of void.

A recipe records a symbol/header and explicit native template arguments when needed.
Arguments may be fixed native types or registered formals in stored/payload
representation. Omitted arguments retain ordinary declaration-order shorthand;
explicit empty arguments mean `Symbol<>`. The C++ renderer consumes this structure
for both ordinary and composed native representations, including ownership payloads
and the value modifier. Layout and lifecycle facts still come from existing probes.

The general runtime path now selects concrete types from the installed recipe's
native-template fact, including closed templates behind nominal source identities.
Nested arguments use the same query. Concrete representations already include their
storage policy and are not wrapped a second time. Source declarations and structural
callables keep their existing representation owners. The historical accounting
recipe mode retains its bounded selection rule in `CPP_Concrete_Recipes`.

The [source-demand proof](../../../../specs/planning/results/prelower_runtime_template_demand_2026_10_19/README.md)
checks nested use, shared pointee identity, borrowed references, request retirement,
replay and option switching. Runtime operation/trait coverage remains separate,
including accounting's integer-output gap. Implicit shared storage still has a
compiler-spelled runtime wrapper; route that spelling through its installed binding
next. See also the [migration evidence](../../../../specs/planning/results/prelower_runtime_type_bindings_2026_10_19/README.md).

## Concrete mutation interfaces

`CPP_Concrete_Mutations` requests `operator++` / `operator--` members from the
existing prepared mutation decision. Existing member assembly and fragment request
replay own generation and reuse. No family dispatch, semantic admission changes or
additional layout/storage protocol is introduced.

Prefix forwarding returns the concrete receiver by reference after invoking the
native operator; ordinary emission retains the source result snapshot. Postfix
forwarding constructs the native old value directly in the existing concrete result
storage. Target evaluation and expression sequencing remain with ordinary emission.
Native scalar representations continue using their existing operators.

The [mutation proof](../../../../specs/planning/results/prelower_concrete_mutations_2026_10_19/README.md)
covers integer aliases/fields, prefix/postfix sequencing and nullable-float success
and failure, with no extra measured boundary allocations or tracked payload copies.
It does not establish every numeric carrier with a substituted template payload.
Accounting now passes the calendar increments and stops at concrete integer output
conversion (`echo_one` / `to_string`); whole-accounting compilation remains unproved.

## Concrete iteration adapter

`CPP_Concrete_Iteration` supplies concrete range/iterator/sentinel/entry interfaces
for the existing `scpp::value_iteration` protocol. Ordinary foreach emission still
owns source evaluation, source lifetime, variable binding and loop control. The
existing prepared iteration contract supplies key/value types. The adapter does
not recreate iteration semantics or import the parked guarded-iteration work.

Requests reference the existing prepared facts and replay through the existing
fragment operation inventory. Native protocol objects occupy inline storage,
with layout resolved by the existing probe/cache mechanism. Entry dereference
materializes the runtime entry exactly once. Key/value results reuse the same
physical-result adaptation as runtime calls, including already-concrete payloads.
There are no vector/hash family branches or new semantic registry entries.

This slice requires a substituted source representation. Unsubstituted source
adapters remain an explicit coverage gap. Mutation/replacement during iteration
retains the ordinary runtime's existing unsupported status. Full runtime metadata
expansion remains deferred. See the [iteration evidence](../../../../specs/planning/results/prelower_concrete_iteration_2026_10_19/README.md).

## Concrete argument substitution foundation

The agreed target is `runtime → pretemplate support → application`: recursively
substitute demanded concrete types while preserving ordinary source fields, member
calls and references. The existing AST, prepared facts and registry remain the owners.
The older ABI transport below remains a tested comparison path until equivalent
ordinary emission replaces it; it is not the target for source-field access.

`CPP_Specializations::request_composed` now reuses the lifecycle worker with
`CPP_Types::implementation_representation`: the outer runtime implementation keeps
its registered spelling, while its template arguments use concrete physical bindings.
Thus a nested container actually contains concrete inner owners. No family algorithm,
semantic type or registry is copied into the engine. Names have a bounded readable
semantic label and stable digest of semantic identity plus physical spelling.

Specialization entries reuse physical bindings' required headers as layout dependencies.
Generated header artifacts carry the emitter's existing header-dependency facts,
including source fields and method declarations. The engine publishes topological
probe batches through this header graph; the native cache
resolves them in publication order, forcing prior layout headers into later probes.
This conservatively includes all earlier batches, not just exact per-entry prerequisites.
The original single-batch path retains its identity and artifact path.

Acyclic source payload template arguments now consume the one shared generated
class/struct definition. The emitter supplies its header dependencies; the engine
does not rebuild source layout facts. Inline records retain their source representation;
nominal shared class arguments select a concrete owner over the runtime handle.
Method implementations stay in their existing source units. See the
[source argument proof](../../../../specs/planning/results/prelower_source_arguments_2026_10_19/README.md).
Original native provider signatures cannot accept substituted types based on layout
similarity alone. See the [production-binding proof](../../../../specs/planning/results/prelower_substitution_2026_10_19/README.md).

## Ordinary source fields, signatures and members

For the bounded integration path, set `prelower_templates = true` and
`concrete_type_substitution = true` on the existing `cpp_lowering_options`.
This temporary API selector takes precedence over `runtime_template_abi`; no new CLI
flag was added. Its retirement condition is equivalent coverage sufficient to make
concrete substitution the single general prelowering path. Existing comparison paths
are retained until that condition is met.

The generator configures the same specialization engine for live rendering and
cached-fragment replay. Existing source declaration/body/dispatch contexts receive
that engine, so fields, parameters and results consistently select concrete bindings.
The existing field-access, source-call and argument-sequencing emitters run unchanged.
No source object is hidden behind another facade, and no property-call bridge or
new prepared facts were introduced. Changing effective settings invalidates existing
C++ fragments without changing semantic preparation.

The [source emission proof](../../../../specs/planning/results/prelower_source_fields_2026_10_19/README.md)
uses compiler-emitted nested inline records, container fields, source method calls,
reference parameters, source reference aliases, value returns and copied record
lifecycles. Native and composed variants have identical measured allocations/copies,
including zero measured work for reference access. No-op replay reuses fragments;
method-body edits preserve unrelated fragments, and restoring edits/settings restores
output. The harness seeds/mutates native containers in fixture support; it does not
claim generated runtime-container member interfaces such as `Vector_Int::append`.

Ordinary void-returning runtime members now use `CPP_Concrete_Members`, driven by
existing runtime callable bindings and prepared signatures/argument boundaries.
The core registry exposes `vector::append`; the same worker forwards independently
registered `parcel::replace`. It contains no container-family branches. Existing
selected-call emission still evaluates the receiver and snapshots arguments in order.
Forwarders accept references to those snapshots: `T&` for the ordinary lvalue form,
`T&&` for consumed values, with the same value category passed to the native member.
This preserves native overload selection without an additional wrapper parameter copy.

Demanded overloads reuse each fragment's existing `runtime_operations` requests.
The invocation-local engine assembles member declarations into the existing concrete
class, with definitions in support output. Class closing text is a separate output
part, not patched generated text. Reference-only concrete parameters are forward
declared; implementation dependencies stay in support. Replay, demand removal and
restoration reuse the same protocol as other specialization requests.
See the [member forwarding proof](../../../../specs/planning/results/prelower_members_2026_10_19/README.md).

This slice covers void-returning members whose native parameter types match the
substituted physical argument types. Owned/reference results and variadic transport
explicitly reject; adaptation of unrelated fixed native parameter types, const receiver
interfaces and ownership-wrapper access remain follow-up work. No runtime-family
algorithms or new semantic facts were introduced.

Ordinary indexed reads/writes, append syntax and keyed removal now select demanded
concrete overloads of the existing runtime helpers through `CPP_Concrete_Index`.
`CPP_Index_Write` and `CPP_Keyed_Path` retain their sequencing/resolution algorithms.
Interface registration reads their settled operation/path facts; no type inference
or container-family implementation moves into the engine. Integer representations
publish the [runtime interoperability contract](../../../../runtime/specs/concrete_type_interoperability.md),
so hashes retain concrete keys while reusing native hashing and append semantics.
The [indexing proof](../../../../specs/planning/results/prelower_indexing_2026_10_19/README.md)
compares vector/hash operations, nested root replacement, lazy removal, actual
traversal-reference identity and throwing copies against ordinary C++ emission.

Sequence initialization now reuses `CPP_Data_Construction`'s existing prepared entry
loop and `CPP_Initializer_Buffer` for concrete substitution. The selected registered
factory is spelled against the native outer implementation with concrete arguments.
Buffers store the actual concrete elements (or the registered keyed-entry aggregate),
not unwrapped native copies. Buffer probes publish their physical header dependencies
through the existing layout graph. Empty initialization still calls the selected
factory; it does not assume that default construction is equivalent. No new family
recipe, AST, registry or semantic conversion rule is introduced. See the
[concrete initializer proof](../../../../specs/planning/results/prelower_concrete_initializers_2026_10_19/README.md).

Concrete eager binary operations now use the existing `CPP_Runtime_Operations`
request/bridge protocol instead of the accounting recipe's integer-only adapter.
Operand snapshots and conversions remain in ordinary emission; middleware borrows
their native outer objects using the existing physical type bindings and calls the
ordinary binary renderer with the same prepared decision. Owned concrete results
are constructed in final storage. Short-circuit/presence paths retain their existing
emission. No operator admission, conversion rules or operation table is duplicated.
See the [binary integration proof](../../../../specs/planning/results/prelower_concrete_binary_2026_10_19/README.md).

Concrete explicit runtime casts, text conversions and integer value casts reuse the
prepared conversion decision through the same operation bridge. The input is bound
once by const reference; opaque results are constructed in final storage. A transient
implementation context lets the existing conversion renderer spell native outer types
with concrete physical arguments through `CPP_Types`, also shared by binary operand
unpacking and result construction. It cannot recursively request another bridge.
Identity stays unchanged; ownership transfers and wrapper construction/extraction
remain separate integration work. No cast admission, parsing or range rules are added.
See the [cast proof](../../../../specs/planning/results/prelower_concrete_casts_2026_10_19/README.md).

Concrete runtime free calls now use the registered C++ binding and prepared signature
instead of the old count-vector recipe. Single observations remain one full expression,
keeping temporary owners alive; value/reference inputs reuse ordinary selected-call
sequencing and the existing copy/consume rules. Receiver methods keep their separate
member interface. Reference results, variadics and combined observation/value arguments
remain outside this transport. Native provider results must match either the selected
native outer binding or an already-concrete payload; middleware-only type checking
selects direct return versus final-storage construction without a family/function list.
Providers requiring other physical argument/result conventions need further contracts.
See the [runtime-call proof](../../../../specs/planning/results/prelower_concrete_runtime_calls_2026_10_19/README.md).

Full runtime coverage is deferred. Its later contract work will expand the existing
runtime-provided metadata, not introduce a parallel registry or eagerly instantiate
all runtime templates. This bounded adapter does not claim complete runtime coverage.

Concrete indexed compound updates reuse prepared paths, key conversions, binary
computation and write-back conversions. Existing-only one-step probes return borrowed
physical elements; ordinary emission snapshots the leaf before the RHS and revalidates
the full saved path before mutable descent/store. No interior borrow survives source
RHS code. The operation engine only supplies concrete helper overloads, with no family
dispatch. Runtime vector existing-element access now accepts its established integer
capability, as ordinary indexing already does. Boxed stored-kind write-back remains a
separate concrete integration boundary. The focused proof has equal boundary counters
with allocator effects observable, but stock O2 retains three more allocations than
inline ordinary code in that workload. Optimized allocation parity is not established.
See the
[compound proof](../../../../specs/planning/results/prelower_concrete_compound_2026_10_19/README.md).

Boundaries remain: recursive generated header/layout graphs (including a method
signature returning `vector<Self>`); concrete shared/unique/value access interfaces;
remaining runtime forwarding
contracts, boxed compound write-back, remaining conversion/operation boundaries and provider compatibility. Initializer
coverage is bounded by those physical provider/ownership interfaces; arbitrary
runtime providers and nominal-class elements are not established by the initializer proof. Existing
semantic reference restrictions remain: reference returns must refer to the declared
parameter storage type, and heap-backed property borrows (including the tested `$this`
case) reject in preparation. The old source-call/constructor transport is not removed
by this bounded inline-record proof.

## Runtime ABI expression integration

The engine now exposes `request_opaque(canonical_type_use)` and
`request_observation(prepared_call)`. `CPP_Opaque_Representation` consumes existing
read-only type/capability queries and C++ type/callable bindings. Its storage and
lifecycle generation has no family branches. The existing integer/vector recipe
selection lives in `CPP_Concrete_Recipes`, outside engine bookkeeping.

Ordinary expression emission can now select this protocol through the API:
set both `cpp_lowering_options::prelower_templates` and `runtime_template_abi` to
`true`. The CLI's existing `--prelower-templates` and the default API setting retain
the accounting recipe baseline while this bounded runtime ABI path grows.
Switching either effective setting invalidates C++ fragments without repreparation.
No new AST, semantic registry or prepared-fact cache was added.

Opaque owners contain the original runtime object in aligned bytes. A grouped
`layout_probe` artifact asks the native toolchain for size/alignment and physical
lifecycle/noexcept traits. Source copy permission still comes from existing
capabilities; native traits do not grant source operations. Shared/native objects
are not heap-boxed merely to cross the boundary. A construction callback supports
owned results directly in destination storage; constructors propagate exceptions.

The first operation mechanism accepts one prepared readonly observation and an
owned storable result. Its symbol/header comes from the selected callable binding.
Borrow transport requires a prepared observation/reference decision and rejects
conversion or copy boundaries. No operation is selected by names such as `count`,
`vector` or `parcel`. Nested runtime and source payload arguments use the same
representation worker; source payload definitions use native bindings as described below.

The [native builder](native_cpp_build.md) owns resolution and caching. Preparation
and emission never invoke Clang. Published source remains immutable; cached layout
headers are forced into generated units at native compilation. Requests deduplicate
inside the existing engine, and generated names distinguish semantic type identity
from coincident native spelling without embedding invocation-local IDs.

The expression path emits registered integer literals, default/empty and nonempty list/keyed initialization,
readonly runtime observations and zero-argument provider calls, ordinary unary/eager
binary computations, conversions, copied indexed reads and nested indexed assignment.
`CPP_Runtime_Operations` reuses existing native operation renderers and callable
bindings. Both ordinary code and bridges use `CPP_Index_Write::resolve_path` for
probe-before-descent handling after key/RHS evaluation. Source functions can pass
whole opaque owners by reference; no facade reference is fabricated for an interior
native element.

Fragments retain `cpp_runtime_operation` requests containing canonical types and
settled decisions/bindings, without source expressions or invocation-local engine
IDs. They are immutable after publication. Assembly replays only live fragments;
body removal drops unused operation artifacts. `cpp_runtime_operand` is transient
and supplies physical address transport through the `cpp_type::opaque` binding.
Layout/action caching remains unchanged.

Nonempty initialization uses the original runtime `initializer_buffer<Element>` in
layout-probed inline storage. The helper is noncopyable and nonmovable because the
native buffer can point into its own local storage. `CPP_Data_Construction` shares
one entry loop across ordinary and ABI emission: allocate the buffer first, evaluate
and convert each key before its value, append, destroy that entry's snapshots, then
call the existing selected factory. List entries keep lvalue copy semantics; keyed
entries keep aggregate-prvalue semantics. Native RAII owns partial-buffer cleanup.
The buffer recipe replays as an ordinary operation request and does not depend on
literal length. No new semantic type or registry entry describes this backend helper.
See the [initializer proof](../../../../specs/planning/results/prelower_initializers_2026_10_19/README.md).
Checked conversion failures still capture native stack traces in the runtime.
Bridge frames can increase diagnostic allocations even with `-g0`; disabling
debug symbols does not disable that capture. The initializer transport itself
preserves payload copies/moves and buffer allocations. Diagnostic capture policy
is a separate runtime concern, not changed by this slice.

This is bounded expression integration, not full backend coverage. Iteration, mutation expressions,
indexed compound updates, broader runtime call boundaries (defaults, multiple inputs,
value/consume/reference parameters, receiver calls and reference results), and the
remaining wrapper/property/callback operations are not covered by this proof.
Existing
accounting recipes remain available; do not silently fall back between representations.
See the [generated-expression evidence](../../../../specs/planning/results/prelower_expressions_2026_10_19/README.md)
and the earlier [API/layout proof](../../../../specs/planning/results/prelower_layout_integration_2026_10_19/README.md).

## Demand-driven source payloads

In runtime ABI mode, the existing class emitter uses ordinary native bindings for
source fields, method signatures/bodies, static storage and dispatch. It emits one
payload definition at the existing `types/` path. Native method/static/dispatch
implementations live in per-source `support/units/` artifacts; application function
units do not include payload headers merely because they share a source file.
There is no second class layout, AST or semantic registry.

Application type uses request opaque representations through the existing engine.
Implicit class handles, explicit ownership applications and `value<T>` consume their
existing canonical/storage/capability facts. Only requested representations and
required dependencies are emitted: requesting shared storage does not request
unique, weak or value storage. Native signatures do not request application facades.
This is specialization demand, not a new dead-source-declaration elimination pass.

Default `new` without a selected constructor call replays its settled construction
facts through the existing operation request. Original runtime allocation helpers
construct directly into the result owner. Copy/transfer/destruction remain original
runtime operations; source classes and structs can now be container arguments.
Selected positional constructors also reuse these construction facts and the
existing prepared call. `CPP_Source_Calls` shares argument categories and native
transport with instance methods; the ordinary call emitter still sequences explicit
arguments and defaults. The native allocation selector remains in
`CPP_Record_Construction`. Constructor results are built in final owner storage.
The current semantic constructor scope remains fixed by-value parameters. See the
[constructor transport proof](../../../../specs/planning/results/prelower_constructors_2026_10_19/README.md).
Factory helper output identities use resolved native bindings rather than registry
keys containing transient source type IDs, preserving deletion/restoration stability.

Source instance calls now reuse the ordinary call emitter's prepared argument
sequencing. A named backend worker requests a concrete native method bridge using
the existing prepared call, receiver and parameter facts. Shared receivers are
retained and checked before arguments; inline receivers are borrowed. Fixed value
and reference parameters, defaults, void and owned results use existing native
storage addresses. Native dispatch spelling is shared with ordinary emission.
The result callback constructs directly in final storage; mutable/reference inputs
keep their prepared access through that callback. No family-specific engine branch,
new AST or argument registry is involved. See the
[method transport proof](../../../../specs/planning/results/prelower_methods_2026_10_19/README.md).

This slice does not implement static method/property/static-property crossings,
method reference results, variadic/observation parameters, runtime-provider methods,
named construction, weak derivation/lock or calls from
native methods back into application functions. These crossings reject explicitly.
Recursive payload graphs, callback crossings and full inheritance operation coverage
remain follow-up proofs. See the [source-payload checkpoint](../../../../specs/planning/results/prelower_source_payloads_2026_10_19/README.md).
The [concrete-composition experiment](../../../../specs/planning/results/prelower_composition_2026_10_19/README.md)
supports the preferred next source-field direction: actual concrete fields with
ordinary member calls/references, including containers of those records and concrete
nested elements. It preserves tested allocations/copies and lifecycle traits.
Physical argument bindings and dependent layout resolution now have the backend API
foundation above, and bounded inline source declarations now use those bindings.
Acyclic source payload template arguments now share those definitions. Recursive
header/layout graphs and provider compatibility remain open.
The proposed [native-place contract](../../../../specs/planning/prelower_native_places_2026_10_19.md)
is deferred for source fields, remaining relevant to original native storage boundaries.

## Accounting recipe baseline: invocation

```bash
php compiler/my-try/main.php --s2s /absolute/source --output /absolute/output --prelower-templates
php compiler/my-try/tools/build_cpp.php /absolute/output /absolute/cache --no-pch -O0
```

The API owns `Compiler::$cpp_lowering`, a `cpp_lowering_options` record. Set
`$compiler->cpp_lowering->prelower_templates = true` before `exec_cpp()` or `cpp()`.
Changing this setting invalidates retained emission fragments, not semantic work.
Optimization, PCH and native debug remain independent native-builder settings.
Use `--no-pch` for the concrete comparison; native debug is off by default.

## Accounting recipe baseline: ownership

`CPP_Specializations` accepts canonical types, deduplicates representations and
synchronously generates their declarations and implementations. It uses registered
integer width/signedness and vector argument identities. There is no `vector<int>`
implementation branch: the same vector recipe receives the element's C++ binding.
`Capability_Facts` reads completed declaration capabilities and registered recursive
rules without scheduling preparation or caching another copy of semantic facts.

The dedicated `cpp_specialization_ref` carries one uint32 entry index. Its portable
value-record declaration has no name/header collections or worker link. The engine
alone initializes it; consumers treat it as immutable. It is invocation-local and
must not be persisted. Native compiler rebuilding/layout measurement is outside this
slice; PHP execution and successful conversion are separate evidence.

Retained fragments store canonical specialization requests. Every assembly replays
all live requests into a fresh engine, including requests from reused fragments.
Engine entries and processing workers are not retained in Model. Support emission
orders an output-only index by stable name, so discovery order does not change its
bytes. Source membership and entry IDs remain untouched.

Names live in `scpp::internal`, such as `int16`, `uint64`, `vector_int16` and
`vector_vector_int64`. Other element bindings use a collision-free encoding of their
C++ spelling. No source-name short-name lookup determines specialization identity.
Representation freshness is ordinary generated content: existing publication and
native object hashing own invalidation. There is no second object cache.

## Accounting recipe baseline: coverage

- Concrete signed/unsigned 8-, 16-, 32- and 64-bit integer wrappers, derived from the
  registry; ordinary `int` shares its registered representation. Integer literals,
  prepared value conversions, checked integer-to-integer casts, canonical integer
  arithmetic/comparisons, local mutations and conditions consume these wrappers.
  Existing frontend admission restrictions still apply, including restrictions on
  arithmetic involving noncanonical widths. This does not expand source semantics.
- One vector recipe for copyable elements with available representations: default
  and list initialization, count, append, checked reads/direct writes, value iteration,
  copy/move/destruction, clear and reserve. Proofs cover all integer widths, bool and
  nested vectors, and source records with equal short names in different namespaces. Native aliasing/growth probes also exercise element snapshots.
- Prepared operation/argument evaluation order is retained. Production bounds,
  zero-divisor and integer-cast error messages, codes and details are preserved through
  a separately compiled runtime bridge.

`base.hpp/base.cpp` provide the CLI/error bridge. Per-representation headers expose
concrete declarations; `containers.cpp` groups support definitions. Integer wrapper
methods are small inline concrete methods. Clang receives ordinary C++ translation
units, not aliases to `int_t<Rep>` or explicit instantiations of `vector_t<T>`.

This does **not** make every preprocessed input template-free. Existing `bool_t`
includes runtime detail machinery; CLI/error support and runtime providers retain
standard-library/runtime templates. Ordinary nontemplate semantic types retain their
existing bindings, which may themselves contain C++ templates (for example callable
or ownership bindings). Review these exposures before extending coverage.

Other applied template families, noncopyable vector elements, runtime calls without
an adapter, integer power, noninteger checked casts, nested indexed writes, indexed
compound assignment and keyed probes/removal are outside this slice. Covered dispatch
sites reject unsupported adapters during emission. This is not a claim that all
existing compiler features can yet be used under the flag; use the default path for
uncovered applications. Full accounting needs further families and operation adapters.

[Dated proof and measurements](../../../../specs/planning/results/prelower_first_slice_2026_10_19/README.md) record the first slice.

## Validation and remaining work

```bash
python3 compiler/my-try/tests/prelower_templates.py --results /absolute/fresh-results
```

The shared runner checks same-instance option switching, no-op fragment reuse,
request removal, failure/repair, discovery-order stability, default incremental
regression, matched Clang O0/O2 execution, stable runtime errors, integer representation
limits, vector copy/move/aliasing, ASan/UBSan, and native cold/warm/body-edit reuse.
Its timing report separates application, support and linking; lowering is excluded.
No PCH is used in either comparison variant. Sanitizer leak detection is disabled.

Continue with the [decision](../../../../specs/planning/cpp_template_prelowering_decision_2026_10_19.md)
and [plan](../../../../specs/planning/cpp_template_prelowering_plan_2026_10_19.md), especially
the early runtime-template inventory/discussion. General family recipes do not imply
that every family shares one implementation. Selective-method closure, background
work, persistent engine retention and runtime throughput benchmarking remain deferred.
