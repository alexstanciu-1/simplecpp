# Runtime public surface: contract audit and delivery plan
Doc Status: planning

Date: 2026-10-27. Implementation baseline: `6794e6f3c`.
Status: discussion draft. No runtime implementation or semantics changed by this audit.
This expands [the migration plan](runtime_public_api.md), not its semantic authority.

## 1. Agreed boundary and sources of lowering demand

**Include every required family, including vector_t, hash_t, mixed_t and dynamic_t.**
Exclude unresolved C++ templates from the finished PUBLIC include closure, not the
language contracts those templates implement. Private runtime/middleware code may
continue to use templates.

There are two sources of concrete lowering demand, using the SAME mechanism:

1. **Runtime public API demand.** Concrete signatures and their usable operation
   paths determine what the runtime must pre-lower and publish. Resolve nested
   dependencies, storage, constructors/destructors, ownership, iterators, callbacks,
   results and configured operations. Publish by owning module, not one giant bundle.
2. **Application demand.** Application-specific types, combinations, callable
   signatures and cross-type operations are lowered by application middleware.
   Reuse runtime-published specializations when their canonical identity and target
   compatibility match. Do not emit a second competing definition.

Example: a public result returning vector<string> requires its concrete result,
vector and string types, error/state handling, extraction and usable vector API.
A vector<AppRecord>, shared<AppNode> or callback involving AppRecord belongs to app
middleware. A generic algorithm may have both runtime-demanded concrete entries
and app-demanded entries. Source generic recipes remain in existing metadata;
applications do not receive a public C++ template as a fallback.

A selected type gets its complete supported ordinary interface. This does not
materialize all possible cross-type combinations, arbitrary application types or
unbounded generic recursion. Additional cross-type operations follow actual demand.
A publication manifest records physical exports and ABI identity, not a second
semantic type/operation registry. Keep established canonical naming and friendly
pre-mappings; runtime/app origins must not change type identity.

## 2. Audit method and evidence limits

Inspected the authority map, current public headers, runtime configuration, all
15 runtime metadata modules, representation/binding protocols, owning language
contracts and representative native implementations. The machine-readable review
snapshot includes **all 752 function records**, not merely unique names, plus
all catalog types, representation recipes, properties, constants and initialization
bindings. Tasks alone contributes 406 overload records for 28 names; row counts
are not counts of independent algorithms or a completion percentage.

Evidence: [catalog snapshots and reproduction](runtime_surface_audit/README.md),
[existing public inventory](../../runtime/specs/public_api_inventory.md),
[previous measured proofs](../../runtime/specs/public_api_boundary_results.md),
[metadata contract](../runtime_module_metadata.md),
[interoperability protocol](../../runtime/specs/concrete_type_interoperability.md).

This is a comprehensive surface/dependency audit, not a claim that every backend
body, external service, historical spec or overload has been semantically tested.
Machine extraction establishes inventory; it does not establish implementation
correctness, compiler admission or usable template-free closure. Per-slice proofs
remain required. Historical prelowering results are reusable evidence for their
revision, not automatic coverage of the new public API.

Status vocabulary:

- **Public:** present in the restricted public root, with the recorded bounded proofs.
- **Partial:** some concrete API exists, but not the whole required family/path.
- **Legacy:** implementation/recipes exist behind the old include surface; public
  migration and composition proof are missing.
- **Issue:** confirmed inconsistency or unresolved architectural/semantic decision.
- **Deferred:** explicitly outside an owning contract's currently supported subset;
  retain it in the ledger without promising implementation.

## 3. Required core and semantic surface

| Family / facility | What the boundary must offer | Current position / issue |
| --- | --- | --- |
| Integers and boolean | All admitted widths, literal construction, ordinary arithmetic/comparison/bitwise/shift/mutation, explicit conversions, conditions and presence | Partial: int32/int64/uint32/uint64/boolean. Small widths and float missing; current concrete operators are bounded, not the complete language matrix. |
| Floating point | Concrete float value and configured integer/float/boxed casts, arithmetic, comparisons, formatting and failure behavior | Legacy float_t; no focused public class. No inference of extra numeric conversions. |
| Null/sentinels | Distinct source null, nullable absence, Result failure and false/true sentinel states; required initialization/comparison paths | Legacy; do not collapse all sentinel states into a null pointer or bool. Distinguish internal interop sentinels from source values. |
| String | Independent values, bytes/UTF-8, checked access, slices, concatenation/comparison, declared mutation, length/presence/empty | Public ordinary class. Module algorithms, casts and container-key composition remain separate; not every native string function is migrated. |
| error_value | Explicit presence, empty-but-real error, no-error, owned fields, lifecycle and Result failure conversion | Public standalone value; Result integration is not yet public. |
| Native exception diagnostics | Raise, retain, inspect owned fields/details/trace, preserve native rethrow, diagnose misuse | Public exception_info/failure. Not source Exception. Capture/inspection have measured exception-machinery allocation costs. |
| Source Exception and descendants | Shared root identity, factory, byte-preserving message, absent-handle rejection, dynamic catch matching, shared transport, finally and unwind support | Legacy language adapters. No public template-free root/transport integration; must not substitute exception_info. |
| vector_t<T> | Complete admitted sequence lifecycle, checked reads/writes, append, iteration, count/empty/presence, capacity/reserve/resize/clear/compact, declared initializer and map/filter paths | Legacy. Runtime-required concrete vectors and application vectors use the same lowering path. Index ABI adaptation must preserve source validation. |
| hash_t<Value, Key> | Typed keys, ordered membership, checked/no-insert reads versus inserting writes, find/presence, removal, iteration, initialization, typed map/filter and applicable identity keys | Legacy. Key traits/bridges for new concrete types are incomplete; do not normalize away exact key identity. |
| fixed_array_t<T,N> | Fixed extent/value argument, element lifetime, admitted indexing/iteration/layout operations; no implicit resize/append promise | Legacy native surface plus compact/array contracts. Current binding/materialization support for N needs a fresh targeted audit; historical coverage explicitly records limitations. |
| mixed_t | Tagged null/bool/int/float/string and mixed-only containers, value/COW copies, runtime kind guards, exact accessors, checked casts, reads/writes/iteration, reset and shared operator rules | Legacy concrete language identity, but transitive C++ templates remain exposed. Its being non-template at the outer declaration does not make the public closure clean. |
| dynamic_t<> | Independent tagged family; scalar values, shared container identity, explicit construction, binding replacement/reset, family-preserving operations and checked extraction | Legacy zero-argument template alias. Publish one concrete default-family representation; do not resurrect old typed-dynamic variants or mixed-backed dynamic. |
| Nullable and three Result families | Concrete payload specializations, all distinct states, checked extraction, conditions, supported lifted operations, sentinel comparison, owned take snapshots | Legacy. Publish runtime-demanded combinations and app-demanded ones through the same recipes; never expose generic templates to consumers. |
| maybe_value / lookup results | Distinguish missing key from found null and preserve read-only lookup behavior | Legacy derived composition, not justification for a new overlapping semantic family. |
| shared/unique/weak/value ownership | Construction, copying only where permitted, transfer, reset, dereference/absence checks, weak locking, inherited/interface identity and destruction | Legacy. Ordinary semantics depend on source contracts/capabilities; do not expose every native convenience by default. |
| References and checked borrows | Stable same-origin references, const/writable transport, no illicit heap-interior escape, bounded closure borrows, source-span invalidation | Compiler + runtime protocol. Native references are not automatically a public ref_p product; config references a missing ref_p.hpp. |
| Callables | Concrete signature-specific storage, capture lifecycle, absence/reset, invocation, exact input modes, variadic packing where admitted | Legacy + compiler protocols. Inferred closures remain compiler-owned; runtime/API callback demands and application callback demands both need lowering. |
| Cooperative async | Concrete task/value storage, coroutine ownership, scheduling/timers, await/wait, result/error cleanup | Legacy async_core templates. Separate from threaded tasks. Coroutine implementation stays behind compiled boundary; app-specific coroutine bodies remain compiler/app work. |
| Structs, unions, enums, classes/interfaces | Concrete layout/trait facts, generated records, ownership, virtual/interface dispatch, identity/key/probe bridges where admitted | Primarily compiler-generated, not prebuilt runtime classes. Runtime supplies the transport/semantic substrate; app middleware handles their generic combinations. |
| Shared operators/casts/helpers | Exact admitted unary/binary/compound/identity/condition/coalesce/cast paths; count/empty/isset/unset/clean/take; evaluation/lifetime protocols | Mostly legacy. Generate concrete dispatch to existing semantic owners, not new per-facade semantics. |
| Initialization and iteration transport | Ordered input evaluation, initialized-prefix cleanup, recognized factories, value iteration snapshots and mutation behavior | Compiler + runtime adapters. A container class alone is insufficient for a complete usable public path. |
| Output, diagnostics and hosts | Concrete formatting/output routes, CLI/env context, debug/type/memory observations and selected host integration | Legacy/language/host owners. Do not move source output policy into core merely to expose it. |

Principal authorities: [dynamic families](../dynamic_types.md), [arrays](../array_semantics.md),
[collections](../collections.md), [initialization](../data_initialization.md),
[error/take](../error_values_and_take.md), [presence/emptiness](../count_empty_isset_contract.md),
[references](../native_reference_safety.md), [closures](../language/closures.md),
[compact layout](../compact_layout_types.md), [source exceptions](../exceptions_v0_2.md),
[async](../async_await.md), [output](../output_v0_2.md).

## 4. Containers and boxed values: mandatory acceptance paths

These are first-class migration work, not optional cleanup after scalar APIs.

### vector_t and hash_t

For every selected specialization, map the actual stored type and its ordinary
interface. Preserve concrete stored elements where the representation recipe
requires them. Returning a reference to an element cannot be implemented by copying
it into a temporary native/concrete conversion. Layout equality is not permission
to reinterpret unrelated classes. A value argument keeps value semantics; a shared
element keeps handle identity. A unique payload must not silently become copyable.

Prove empty/nonempty construction; independent outer copies; shared element identity;
move/failed-copy cleanup; checked signed-source to physical-index conversion; lookup
without insertion; replacement/removal; ordering; nested containers; value iteration
and supported mutation during iteration. Distinguish public C++ uint64 indexing
choices from source-level negative-index diagnosis and signed count contracts.

For hashes additionally prove string/integer key semantics, supported identity keys,
missing versus found-null, packed/ordered transitions where applicable, and key
hash/equality agreement. Publishing vector_string does not prove hash<string, ...>
works; a focused probe already demonstrates a key-adapter gap (I-02).

### mixed_t and dynamic_t

Publish separate concrete identities. Mixed containers hold mixed values and retain
value/COW behavior; dynamic containers hold dynamic values and share identity.
Scalar copies remain values in both families. Default dynamic is null, not an empty
container or an absent old-style shared hash. Replacing/resetting a dynamic binding
must not clear other aliases' containers. Null mixed bootstrap and explicit dynamic
container construction remain different rules.

Preserve the exact runtime-kind admission matrix, checked conversions and homogeneous
boxed operators. Cross-family scalar/null conversion is explicit; container crossing
rejects. No recursive boxing/conversion shortcut may make a missing specialization
appear supported. JSON decode/encode, tasks and database rows must keep their
respective family contracts. Private reuse of implementation machinery is allowed;
public semantic identity must remain distinct.

### Wrappers and ownership around those types

Prove result<vector<...>>, nullable/hash lookup cases and admitted boxed Result
payloads as complete operation paths. Successful false/null payloads are not failure
sentinels. Failed result<T> cannot be constructed from no-error; rejected failure
assignment preserves the previous Result. Take owns its prescribed snapshot and
preserves outputs on unavailable branches; it is not move extraction or catch-all
exception handling. Pointer wrappers preserve identity/transfer/weak expiration and
must not be used to change ordinary container value semantics.

## 5. Modules: full catalog coverage, publication demand and open issues

The following counts are overload rows in current metadata. Every individual row
is present in the linked [snapshot](runtime_surface_audit/README.md).
All these catalog function bindings currently point at implementation/compatibility
headers, not migrated restricted public headers. Some have equivalent new public
operations; that is not yet a complete catalog-binding migration.

| Module | Rows | Required public paths / dependencies to pre-lower | Position |
| --- | ---: | --- | --- |
| core | 55 | Primitive helpers, container algorithms, error/Exception, probes, debug/memory, CLI/env/output-related adapters | Partial public primitives; mixed ownership/language binding issues I-01/I-04/I-05. |
| strings | 73 | Ordinary algorithms, byte/Unicode/search coordinates, hashing, split/join, number formatting, text_builder and string_parts_builder | Legacy; approved textbuilder slice only covers one builder, not all 73 signatures. |
| source | 20 | Buffer/span, line index/location, hashing and builder span append | Legacy; approved owner/span subset does not cover line mapping or all hashing. Module graph issue I-03. |
| filesystem | 35 | Checked read/write/path/scan/stat, snapshots, lock ownership and output-publication helpers; Result/string/collection/handle closure | Legacy. Keep Result/error/false distinctions; compiler throwing facades are not the contract. |
| io | 12 | Resource lifecycle, read/write/line/seek/position/eof/flush, checked failures and shared resource ownership | Legacy core/stdio.hpp provider. Resolve public target ownership without exposing FILE/STL. |
| json | 31 | Family-specific decode/encode, immutable document/node/diagnostic, pull-reader operations; results/nullable/shared handles | Legacy. Dynamic versus mixed is mandatory; document shape/duplicate-key/lifetime contracts are separate from general decode. |
| datetime | 11 | Exact strict helpers, wall-clock values, formatting/parsing/error returns | Legacy; float/string and wrapper dependencies must be concrete. |
| tasks | 406 | Batch/context/error/progress, run/start/join/cancel/status, work/publish callbacks, synchronized publication and diagnostics | Only focused worker_pool is public. Catalog overload count does not mean 406 standalone algorithms to hand-write. |
| process | 5 | Start/poll/result/stop/close, shared process/result objects and six fields | Legacy; preserve process/resource lifecycle and disabled-module policy. |
| regex | 25 | Match/filter/split/replace/callback families, JIT probes/constants, exact results and output containers | Legacy PCRE2 owner. Callback ABI and nested wrappers are dependencies, not raw public templates. |
| curl | 13 | Session/options/execute/info/errors/reset/close, records/constants/properties | Legacy optional libcurl; preserve resource ownership and option/value admission. |
| compiler | 30 | Runtime-backed storage/collection/cursor helpers and typed provider bindings | Legacy; compiler-support module is not the compiler's own semantic model. |
| tokenizer | 12 | PHS/JSS tokenization, token buffers and source positions, explicit mixed conversion | Legacy; account for buffer lifetime and uint8/uint32 widths. |
| ui | 12 | Typed UI configuration/commands and lifecycle | Legacy optional module; host/thread-affinity and external backend checks need their own slice. |
| webview | 12 | Webview host/dispatch integration over ui | Legacy; preserve ui dependency and explicit platform/backend availability. |

Catalogs are not the whole runtime: `modules/mysql`, `modules/binary`,
`hosts/fastcgi`, language adapters, cooperative async, output, initializer/iteration
protocols and compiler-generated type bridges also exist. MySQL has an owning
[builtin contract](../builtins/db/mysqli.md) but no mysqli module catalog in this
snapshot. Its v0.2 rows are tagged dynamic; live DB behavior/source registration
are not proved by a header audit. Binary/native facilities and FastCGI require
individual intake/host-contract classification; do not infer source availability
from files alone. Optional modules remain optional, not silently excluded.

## 6. Issue register

| ID | Finding and evidence | Required resolution / gate |
| --- | --- | --- |
| I-01 | **Confirmed public coverage gap:** small integer widths, float, full scalar operators/casts, sentinels and all public container/boxed/wrapper families are absent or partial. See runtime/public and core cpp_types. | Complete the family/operation ledger; no claim of general contract completeness from existing primitive tests. |
| I-02 | **Confirmed composition failure:** `hash_t<scpp::int64, scpp::string>` fails Clang syntax checking with `requires a supported typed key family`. New public string does not satisfy existing native hash-key adapters. | Establish private interoperability/key adapters at existing owners; prove exact stored/key semantics. Do not add broad trait markers that accidentally admit competing template overloads. |
| I-03 | **Confirmed graph conflict in the proposed direction:** metadata source depends on strings; the value plan permits strings to depend on source for append-span. Metadata forbids cycles. Existing strings.hpp already includes native source.hpp, so physical includes and catalog dependencies are not the same graph. | Prefer keeping source -> strings and owning the span adapter in source (possibly an explicit extension entry/target). Alternatively revise/split the dependency graph deliberately. Settle the approved member-signature/link ownership before implementation. |
| I-04 | **Confirmed layering exposure:** 20 core and 11 strings function bindings select lang/php headers; core metadata also binds microtime through language code and a string-module helper. | Classify truly shared mechanisms versus source-language policy. Move reusable implementation only at its owner; keep language adapters explicit. A core catalog name is not proof of core-linkable implementation. |
| I-05 | **Confirmed distinct exception contracts:** native exception_info accepts runtime_error only; source Exception is a shared language root, preserves embedded NUL message bytes and supports descendants/catch matching. | Expose source throwable/transport at its language/semantic owner; do not change native capture to swallow language or foreign exceptions. |
| I-06 | **Confirmed metadata drift:** config lists `scpp/ref_p.hpp`, which is absent; int_t/hash_t `template:false` does not describe their current generic C++ forms. Other config text retains older mixed/dynamic rules. | Reconcile with top-level v0.2 authority and native reference model. Do not create ref_p or filter families solely to satisfy stale fields. Audit affected generator consumers before changing machine-owned config. |
| I-07 | **Publication gap:** new public scalar/string/error classes coexist with old representation bindings selecting int_t/string_t/error_t and legacy headers. | Map published concrete exports into existing physical bindings and reuse protocol; enforce one definition/identity across runtime and app middleware. Preserve transitional compatibility explicitly. |
| I-08 | **Lowering mechanism coverage is not universal:** ordinary member/free-call, callback, variadic, reference-result, value-template-argument and suspension transports have distinct supported domains; historical proofs describe bounded revisions. | Prove each demanded path against current machinery; unsupported demand must produce an explicit diagnostic, never include private templates as fallback. |
| I-09 | **No automatic public-closure manifest yet:** probing three value layouts and generating integer definitions is not the complete runtime/app specialization pipeline. | Derive a finite dependency/operation closure from existing recipes/contracts, canonicalize names, publish exports and reuse them from application demand. No second semantic registry. |
| I-10 | **Cost/safety:** exception capture/rethrow-based inspection allocates through exception machinery; inline values require target-specific layout and real lifetimes; vector moves may preserve iteration state and cannot inherit string's noexcept assumptions. | Per-type trait/allocation probes; include malloc-level exception measurements, invalidation and failure cleanup. Never assume every facade is trivially copyable or allocation-free. |
| I-11 | **Scope/status drift:** legacy umbrella wording, old v1 semantics and partially registered optional facilities coexist with new public contracts. | Reconcile owning docs as each slice lands. Keep “contract required”, “implemented”, “registered”, “public”, and “tested” separate. |
| I-12 | **Build integration:** core is still an aggregate TU; module-disabled behavior and generated target layouts/publication must stay coherent. | Preserve explicit runtime publication, module-selective links, ABI/probe identity and relocation. Splitting implementation units is separate measured work, not a prerequisite for guessing APIs. |

I-02 reproduction (private implementation compatibility probe, deliberately not a
public-consumer proof): include scpp/hash_t.hpp and scpp/string.hpp using
`-Iruntime/include`, declare `scpp::hash_t<scpp::int64, scpp::string> value;`, compile
with Clang C++23 `-fsyntax-only`. The rejection concerns new public key compatibility;
it does not mean existing native string_t-key hashes are broken.

## 7. Proposed delivery order

Each step is a bounded vertical path with declared non-goals, not a rewrite of all
native implementations. Runtime code changes require the discussion decisions first.

1. **Freeze the audit model and resolve owners.** Adopt the two demand sources,
   track all families, settle source/strings dependency ownership, source Exception
   versus native diagnostics, and the stale reference/config entries. Deliver a
   reviewed issue ledger and selected first concrete runtime-demand path.
2. **Prove concrete composition and shared materialization.** Use the existing `filesystem.fs_scan` contract, which returns
   `result<vector<string>>`, as a candidate complete public path, plus a typed
   hash/string-key composition proof. A hash specialization ships in runtime only
   when selected public API demand requires it. Reuse existing recipes, layout/lifecycle protocols and naming; generate
   concrete exports and prove app reuse plus an additional app-owned record
   specialization. This is the key proof before mass-producing wrappers.
3. **Complete foundational scalar/operation dependencies.** Add required uint8/int8,
   uint16/int16, float, sentinel and operation/cast paths from actual contracts.
   Resolve checked widths and index adaptation; fill ordinary selected interfaces.
4. **Containers, wrappers and ownership.** Build out vector/hash, fixed-array/value
   arguments where admitted, nullable/Result families and shared/unique/weak/value
   transport. Cover nested and application-defined stored types. Keep unsupported
   combinations explicitly rejected rather than widening admission.
5. **Mixed/dynamic as a dedicated mandatory slice.** Establish independent concrete
   boxed types and their internal closure; prove COW/value versus shared identity,
   exact casts/operators/lookup/reset and Result integration. This can start during
   step 4 once its storage dependencies are understood; do not postpone it until
   optional modules or declare the runtime complete without it.
6. **Source/strings and compiler-critical modules.** Finish builders, checked spans,
   line mapping/hashing, JSON document/pull paths, filesystem/locks/output and I/O.
   Select module API demand and publish each usable closure. Migrate compiler
   consumers as a separate acceptance step, preserving baseline behavior/timings.
7. **Language execution and callbacks.** Complete source Exception/throwable
   transport, output/host context, concrete callable signatures, initialization and
   iteration helpers. Compiler retains type admission, ordering and finally lowering;
   runtime supplies the mechanisms required by those prepared decisions.
8. **Concurrency and async.** Expand tasks beyond pool management, including joined
   lifetimes, callback ownership, ordered/unordered publication, cancellation/errors
   and disabled behavior. Separately complete cooperative task/coroutine boundaries.
   Test concurrency and incremental/build costs before broad consumer migration.
9. **Remaining selected modules and closure completion.** Datetime, process, regex,
   curl, database, UI/webview, tokenizer/compiler helpers, binary/hosts according to
   their actual contracts and prerequisites. Optional external providers get focused
   enabled/disabled and lifecycle tests; no implied service availability.

This order is a proposal, not a rule that modules must wait for every possible
core combination. Runtime API demand drives each module's necessary closure;
application-specific combinations are generated only when an application needs them.

## 8. Acceptance gates for every published path

- Contract and owner identified; supported versus forbidden combinations explicit.
- Public signature closure contains no raw templates, aliases that expose template
  definitions, implementation includes, STL types, or unrestricted native access.
- Both runtime-provided and app-provided concrete demands use the same canonical
  naming/representation mechanism; existing runtime exports are reused exactly.
- Complete selected ordinary API, not construction-only; meaningful end-to-end
  operation, return/extraction, failure and destruction paths compile and run.
- Semantic parity: values/identity, states, ordering, copy/move, exact key and boxed
  families, callbacks and shared operator/cast owners. Compiler-only rules remain
  compile-time preparation rules, not accidental C++ overload admission.
- Memory safety: short/long/empty, failure cleanup, escaped-result lifetime, invalid
  borrow/handle states, O0/O2 allocations and ASan/UBSan; TSan where sharing changes.
- Include/dependency purity plus static/shared/relocated builds; optional module
  enabled/disabled links; no private PCH/template escape to ordinary consumers.
- Measure caller and implementation builds separately, runtime allocation/memory,
  relevant throughput/concurrency and changed incremental invalidation boundaries.
  No performance claim from an isolated header test alone.

## 9. Discussion decisions still needed

Recommended: keep the audit broad, but make **concrete composition plus one shared
runtime/app lowering path** the next architectural proof. Continuing isolated leaf
wrappers alone will not resolve I-02/I-07/I-09.

Recommended for span append: retain the current source -> strings module dependency
and make the cross-facility adapter source-owned, then agree how that is exposed
without contradicting the approved textbuilder member signature.

Do not expand deferred language features just to make an inventory look complete:
fibers, arbitrary public templates, unrestricted borrowed-capture escape, old typed
dynamic variants and forbidden family conversions remain outside current contracts.
Record any new required capability at its existing semantic owner before implementing it.

## 10. Composition proof checkpoint

The selected `fs_scan -> result<vector<string>>` path now uses one scan algorithm,
concrete stored string/vector arguments and generated inline native outer storage.
Runtime and application demands share naming and generation. A relocated binary
publication supplies the runtime vector/Result; an application generates only
`vector<sample::record>` and links through the published CMake targets without
private includes in its main translation unit. ABI, name, public-header and archive
conflicts are rejected.

A private concrete-string key adapter proves `hash_t<int64, string>` composition,
including embedded NUL bytes and independent copies. This addresses the observed
key compatibility issue for this path; it does **not** finish the public hash family
or all scalar key adapters. I-02/I-07/I-09 therefore have bounded evidence, not global
closure. Existing compiler facades and legacy catalog bindings have not migrated.

Next batches follow section 7: scalar/key closure; containers/wrappers/ownership;
mandatory mixed/dynamic; compiler-critical modules; language/callback boundaries;
concurrency; remaining selected modules. Each batch needs its own contract proofs,
publication closure, tests and commit. Source/strings ownership and source Exception
remain explicit design checkpoints before their dependent batches.
