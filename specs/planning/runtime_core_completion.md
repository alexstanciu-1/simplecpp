# Runtime-core public API completion inventory and plan
Doc Status: planning

Checkpoint: 2026-10-27, implementation baseline `6a1cb4af2`.
This is the current completion inventory under [the runtime API migration plan](runtime_public_api.md),
not a new semantic registry. No implementation is authorized merely by a row here.

## Scope and evidence

Proposed finish line: every currently admitted runtime-core contract has a usable,
focused, template-free public path, with runtime-owned specializations published and
application-owned specializations generated/reused through the same C++ mechanism.
A generic recipe is coverage only for its admitted payloads and operations; one
published specialization is not evidence that the whole family is complete.

Sources inspected: `runtime/metadata/modules/core.json`, `runtime/specs/config.json`,
`runtime/metadata/public_api.json`, the actual `runtime/public/scpp/` headers and
specialization manifest, native core helpers, and the owning specs for expression
order, dynamic families, conditional selection, ownership/reference safety, collections,
initialization, errors and checked power. The older
[capability matrix](runtime_template_capability_matrix_2026_10_19.md) supplies the
source/native/internal classification, not current public completion claims.

Excluded: filesystem, IO, JSON, tasks, source/tokenizer, strings utilities, datetime,
regex, process, networking, UI/webview, database modules and host adapters as module
migrations. Existing `fs_scan` and tasks proofs remain evidence for composition and
boundaries, not core-completion credit. Module-owned dependencies stay visible below.

Core-registered helpers are included even when their current implementation lives
under a language adapter: `microtime`, `getenv`, `shell_exec`, CLI argument access and
`Exception` must not disappear from the inventory because of their include paths.
Their placement needs review; this audit does not move them to another owner.

Correction to the preceding conversational estimate: **textbuilder is not implemented
in the new public root**. It and source-byte borrowing have approved plans, but are
owned by the strings/source modules. Neither counts as completed core API work.
The previous 40% estimate was a judgment, not a measured completion ratio; this
inventory replaces it as the planning basis. Header/overload counts are not effort
percentages, and no new weighted percentage is asserted here.

## Current surface and remaining contracts

Status meanings: **published** = an actual restricted public header/compiled path;
**recipe** = application demands can generate selected concrete forms; **missing** =
no equivalent new public path identified. Published/recipe does not mean complete.

| Area | Evidence now | Remaining work / limits | Batch |
| --- | --- | --- | --- |
| Signed/unsigned integers, boolean, byte alias | Published concrete 8/16/32/64-bit integers and boolean; same-type arithmetic/comparisons/mutations; global addition promotion | Complete shared operator coverage and checked casts; keep byte canonicalized to uint8. Explicit public constructors are not a checked source-cast contract. Consolidate old scalar emission under the shared metadata path without duplicate definitions. | A |
| Floating scalar | Published float64 storage, construction and value access; global addition | Ordinary arithmetic/comparisons, admitted unary/update operations, casts, truthiness, identity. Do not invent float16/32 support to fill a table. | A/B |
| String | Published ordinary owned string API: bytes, UTF-8/code-point operations, slices, comparisons, append, length/empty/presence | Explicit scalar conversions and source text contexts through shared operation metadata; native C++ string `+` is not permission for source arithmetic `+`. Keep existing string methods, rather than expose unrelated strings-module helpers here. | A/B |
| Null and other sentinel identities | Published semantic null | Concrete forms needed by nullable/Result/ownership construction and comparisons; nullopt, pointer-null and boolean sentinels must stay distinct where contracts distinguish them. Public factories may avoid exposing private sentinel machinery. | B/C/D |
| In-band errors and retained exceptions | Published error_value, failure construction, exception_info inspection/rethrow | Source `Exception` construction/identity/throw-catch transport is separate and still absent. Finish wrapper integration and any required diagnostic callbacks; no need to expose native STL trace containers. | C/D/G |
| Global operators and conversions | C++ resolver + normalized addition; boxed equality/inequality; native helper enforces checked scalar equality | Most arithmetic, ordering, strict identity, shifts/bitwise, power, unary/mutation/compound, checked casts, truthiness, concat and wrapper dispatch. Existing support in concrete integer/string headers is not shared-resolver coverage. | A/B/C |
| vector_t<T> | Recipe + published vector_string; indexing copies, set/append, size/capacity/resize/compact, iteration, count/empty/isset | Conditional lifecycle rather than unconditional copyable payloads; source initializer paths, full keyed/lvalue/probe protocols and helper spellings; stable shared-element extraction where admitted. Review end-idempotent cursor next() debt. | C/D/E |
| hash_t<V,K> | Recipe, defaults, typed key capability checks, checked access/find/set/remove/append and cursor proofs | Complete admitted key bindings beyond currently selected string/integer policies; application-defined H/E bindings; initialization and nested keyed paths; move-only/conditional-copy support; cursor debt. No public insertion through read-only `operator[]`; source array notation follows its existing hash/container lowering, not a new public array family. | D/E |
| nullable<T> and result<T> | Recipes with checked owned extraction and take; published result_vector_string | Conditional payload capabilities; complete construction/reset/swap/extraction where admitted; wrapper casts, state comparisons, lifted operations and lazy selection. Result errors cannot silently become values. | C |
| result_or_false<T>, result_or_bool<T> | Native contracts; no public recipes | Both carrier recipes, sentinel/payload distinction including bool payloads, take outputs, conditional copying/moving, admitted lifted operations and nested demands. | C |
| mixed_t and dynamic_t | Published scalar/null construction and extraction, family-preserving containers, mutation/removal/iteration, explicit scalar family crossing, checked equality | Numeric/bitwise/order/unary/compound public paths; condition/text conversion; count/empty/isset/probe helpers; nested keyed operations. Preserve mixed value/COW versus dynamic identity. Cycle reclamation remains separately accepted debt, not an API migration. | A/B/E |
| shared/unique/weak and value storage | Native contracts only | Concrete ownership recipes, destruction/retain/release/transfer/lock; absence and identity; admitted up/downcasts, stable extraction, shared-self; application-defined target layout dependencies. `value<T>` is an existing storage modifier, not a new unrelated template identity. | D |
| Struct/class/enum and reference argument transport | Application leaf descriptors and simple record composition proofs | General prepared capability/representation facts, recursive declaration versus layout dependencies, noncopyable payloads, object identity/ancestry and safe references. Native references remain a passing/binding contract, not another public wrapper family. Do not expose heap-interior native references. | C/D/E |
| Initializers and keyed protocols | Native typed/dynamic initializer and index/path/probe owners; individual public methods cover only part | Ordered initialization with failure cleanup; indexed read/write/append, captured destinations, nested path operations, keyed isset/empty/unset and alias-safe mutation. Source evaluation remains compiler-owned. | E |
| Callables and higher-order collections | Native callable/collections implementations; registered map/filter functions | Concrete signature-based callback ABI, captures and destruction, invocation/error propagation and reference restrictions; then collection/sequence/keyed map/filter for admitted families. Do not expose std::function or generate per-function-name exceptions. | F |
| Core utilities, debug and memory | Some per-value storage estimates and predicates; no general utility public surface | Registry helpers in the appendix, debug flags, required value_copy/reset/memory/identity helpers. Distinguish actual accounting semantics from estimated storage bytes. Audit language-adapter dependencies. | E/G |

The recipe registry currently contains exactly seven families: vector, hash,
nullable, result, float64, mixed and dynamic. The first four all impose copy
construction/assignment and nothrow-destruction checks. This is a real coverage
restriction: supporting `unique<T>` cannot be fixed by merely adding another name
to the current copy-required recipes.

## Non-module native surfaces requiring an explicit disposition

These exist in native core headers, but their existence alone does not make them
source-language contracts. They must not silently inflate either completion credit
or the mandatory rewrite scope.

| Surface | Disposition proposed |
| --- | --- |
| fixed_array_t<T,N> | Separate native-consumer extension if required. Physical recipe value-parameter emission is missing even though the naming machinery has some value-argument support. |
| row_arena_t<T,Id>, bitset_t, work_queue_t<T> | Compiler/native support candidates; no new public recipes. Select concrete demands only for identified consumers and document storage/invalidation rules. Do not infer thread safety from a queue name. |
| async task/promise/awaiter and scheduler core | Separate native-only boundary review. Tasks module API remains excluded; compiling async code into core does not imply all coroutine templates need public source identities. |
| Internal hash indexes/key_ops, allocation/iteration traits, STL templates | Keep private. Expose only the operation/capability hooks needed by admitted types. |
| Compiler Storage/Keyed_Storage and module provider records | Compiler/module-owned migrations, outside this core finish line. Their use as core-container arguments must still work through ordinary descriptors. |
| Legacy PHP/resource/host bridges | Inventory actual admitted callers; retain compatibility privately or explicitly retire obsolete paths. Do not reproduce every historical header as public API. |

## Cross-cutting gaps and risks

1. **Conditional lifecycle and operation admission.** Existing capability conditions
   select methods/checks, but lifecycle is statically native/disabled and recipes
   blanket-require copying. Extend the same mechanism for real payload capabilities,
   using an admitted noncopyable proof before advertising general container coverage.
2. **Conversion and semantic ownership.** The new operator resolver selects operand
   types; it does not emit source evaluation or implement general checked conversion.
   Numeric constructors, explicit casts, text rendering and condition conversion are
   different contracts. The current frontend still uses its existing promotion rules.
3. **Special operation groups.** Shift counts, checked integer power, NaN spaceship,
   compound write-back and lazy selection cannot all use symmetric max-family
   promotion. Metadata needs explicit group policies, not per-type name exceptions.
4. **Borrowing and callbacks.** Public references to whole stable objects are distinct
   from forbidden references to movable heap interiors. Define guarded borrowing and
   callback lifetime before wrapping native methods that return references.
5. **Publication and build shape.** API and type ownership now reuse the same inventory,
   but broad metadata identity checks/probes and the core implementation bundle remain
   build-cost concerns. Audit invalidation granularity and output freshness as coverage
   grows; do not introduce a separate cache/registry for each family.
6. **Existing semantic/test drift.** The old mixed fixture still names removed dynamic
   boxing APIs; historical config/docs also retain older mixed/dynamic wording. Migrate
   touched expectations to their normative contracts, and list unrelated gaps explicitly.
7. **Completion is not frontend adoption.** Native public-path completion and compiler
   integration are separate milestones. A source-level proof must eventually exercise
   prepared admission, casts, evaluation order and diagnostics, not merely C++ calls.

## Proposed implementation batches

Each batch is a coherent ownership area, not a mandate for one large commit. Keep
review points between its sub-slices, use the same emitter/publication protocol, and
batch compilations after metadata-only checks.

| Batch | Work that belongs together | Dependency / meaningful completion proof |
| --- | --- | --- |
| A1: ordinary numeric operations | Concrete numeric subtraction/multiplication/division, integer remainder and numeric equality/ordering through global metadata; reuse addition normalization and native arithmetic/error authorities | Extend the current slice, without wrappers or new widths. One combined matrix checks same/mixed widths, signedness rejection, float promotion, zero divisors, boundaries and comparison return types. No original operand-pair code expansion. |
| A2: checked conversions | Concrete scalar checked casts, string parsing/formatting, admitted boxed scalar extraction/conversion; distinguish casts from raw representation constructors | A1/common resolver. Range errors, invalid whole-string parses, NaN/infinity policies and accepted precision loss; metadata names selected native cast authority. No silent unchecked narrowing. |
| B1: scalar truth/text/identity | Existing truthiness, eager logical xor, concatenation, strict identity and primitive presence/empty/reset protocols | A2; preserve false-like strings and context-specific text formatting. Prove identity does not promote and predicates do not accidentally admit arithmetic. |
| B2: special numeric/mutation groups | Bitwise, shifts, checked power, unary/update/compound and spaceship where already admitted | A1/A2. Preserve left-width shift checks, power errors, NaN behavior and validate-before-write. Lazy &&/||/??/ternary expose required observations while evaluation stays in the compiler. |
| C: carrier family completion | Nullable/Result gaps plus false/bool-sentinel recipes, take, state observations, admissible lifted operations, conditional payload lifecycle | A/B, with conditional-copy design shared with D. Test all states including false sentinel versus false payload, Result failure, noncopyable cases where admitted and nested carriers. No Cartesian product of wrapper combinations. |
| D: ownership and application targets | Shared/unique/weak, value storage, ancestry/casts/identity, stable extraction, source Exception transport | Conditional lifecycle and prepared target facts. Begin with a single application-defined record; prove weak lock, unique transfer, destruction once, failed casts and publication reuse. Then complete the families. |
| E: container and access completion | Vector/hash capability gaps, initialization, safe nested indexed mutation/probes/removal, cursor debt, boxed count/empty helpers and required key policies | C/D where payloads need them. Prove alias/reentrancy, mutation during iteration, partial-init cleanup, stable handles and noncopyable storage only where admitted. |
| F: callbacks and collection algorithms | Concrete callable-signature transport, then the 12 registered collection/sequence/keyed map/filter signatures | D/E. One capturing callback path first, including exception cleanup; then batch implementations through one callback mechanism. |
| G: remaining core helpers and closure | Debug/constants, host/env/CLI/time, memory accounting, source Exception gaps not completed in D, representation/publication consolidation | Can progress independently where dependencies are ready. Audit every appendix row; test returned ownership and error forms. Finish with public-only consumers, freshness/relocation and representative compiler adoption. |

A source-contract ambiguity discovered during a batch is a discussion gate, not an
excuse to follow whatever broad native overload happens to compile. Core build
reorganization and LTO/ThinLTO evaluation remain measured build work; they are not
prerequisites for writing new operator semantics.

## Wrapper-policy discussion gate

The [wrapper policy and constrained-argument backlog](runtime_wrapper_policy.md)
now records the accepted arithmetic, comparison, truthiness and conversion decisions.
It records the bare-source `copyable_value` contract, family-specific argument
requirements, explicit ownership/nullable composition restrictions and supported
Result-of-nullable states. Uniform one-layer carrier arithmetic/comparisons and checked conversion policies
are approved but not yet implemented. The remaining goal-scope choices are the
native-only surface denominator and separation of frontend adoption.

## Recommended next slice for discussion

**A1: complete ordinary concrete numeric arithmetic and comparisons.** It builds on
the newly proven resolver, has existing native semantics to bind, and can be checked
with one focused test matrix rather than a new representation/lifetime mechanism.

Proposed boundary: `-`, `*`, `/`, integer `%`, `==`, `!=`, `<`, `<=`, `>`, `>=` on the
already published integer widths and float64, using the agreed admission/promotion.
Comparison results are boolean; `%` remains integer-only. Reuse the runtime's
zero-divisor and overflow contracts, not raw C++ expressions where they lose checks.

Non-goals: new widths, checked general casts, boxed arithmetic, wrapper lifting,
bitwise/shifts/power, compound write-back, source compiler integration and wholesale
migration of the old scalar generator. Keep those named in their owning batches.
Explicit cast APIs follow as A2; A1 must not advertise raw constructors as checked casts.

Before coding A1, confirm this boundary. No additional language decision appears
necessary for this bounded group; any native/source inconsistency found while binding
an operation should be surfaced before changing that operation's semantics.

## Validation and completion gates

For each batch: metadata-only positive/negative resolution and generated-shape checks
first; then one combined release build and focused tests. ASan/UBSan/lifetime checks
for changed runtime paths, allocation counts with setup separated from measured work,
and public-caller versus adapter compile cost. TSan only for changed shared/concurrent
behavior. Preserve independent header inclusion and no active public template tokens.
Use Ninja eight jobs by default; avoid recompiling core just to test metadata changes.

The core finish line requires every included contract row to be implemented or have
an explicitly accepted exclusion, with: usable concrete signatures; correct lifecycle
and failure behavior; runtime/application definition reuse; no extra boundary heap
wrappers; independently compilable public headers; and focused proofs. Documented
native-only extensions and excluded modules remain visible rather than counted done.
Do not use generated header count, recipe count or a successful sample as a coverage
percentage. Frontend integration has its own final source-level acceptance gate.

## Appendix: every currently registered core function

The core catalog contains 55 function signatures and 19 debug constants. This is an
inventory denominator for **registered functions only**, not for all core methods,
operators, compiler-interpreted types or native-only helpers. The table maps every
signature to a public path or a completion batch. "Method equivalent" means a public
value API exists, not that source lowering has already migrated to it.

| Catalog identity | Public status / remaining work | Batch |
| --- | --- | --- |
| `core.hash.make` | Hash recipe default construction; source factory/initialization path remains | E |
| `core.collection_map.vector` | Missing callback/algorithm public path | F |
| `core.collection_map.hash` | Missing callback/algorithm public path | F |
| `core.collection_map.mixed` | Missing callback/algorithm public path | F |
| `core.collection_map.dynamic` | Missing callback/algorithm public path | F |
| `core.collection_filter.vector` | Missing callback/algorithm public path | F |
| `core.collection_filter.hash` | Missing callback/algorithm public path | F |
| `core.collection_filter.mixed` | Missing callback/algorithm public path | F |
| `core.collection_filter.dynamic` | Missing callback/algorithm public path | F |
| `core.sequence_map.vector` | Missing callback/algorithm public path | F |
| `core.sequence_filter.vector` | Missing callback/algorithm public path | F |
| `core.keyed_map.hash` | Missing callback/algorithm public path | F |
| `core.keyed_filter.hash` | Missing callback/algorithm public path | F |
| `core.dbg.int.value` | Missing public debug function and flags | G |
| `core.dbg.int.flags` | Missing public debug function and flags | G |
| `core.dbg.float.value` | Missing public debug function and flags | G |
| `core.dbg.float.flags` | Missing public debug function and flags | G |
| `core.dbg.bool.value` | Missing public debug function and flags | G |
| `core.dbg.bool.flags` | Missing public debug function and flags | G |
| `core.dbg.string.value` | Missing public debug function and flags | G |
| `core.dbg.string.flags` | Missing public debug function and flags | G |
| `core.vector.append` | Vector recipe/published specialization method equivalent; helper/source mapping remains | E |
| `core.microtime.0` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.microtime.1` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.vector_reserve.2` | Vector recipe/published specialization method equivalent; helper/source mapping remains | E |
| `core.vector_capacity.1` | Vector recipe/published specialization method equivalent; helper/source mapping remains | E |
| `core.vector_resize.3` | Vector recipe/published specialization method equivalent; helper/source mapping remains | E |
| `core.vector_filled.2` | Missing filled-construction helper; do not infer identical failure/allocation behavior from resize | E |
| `core.vector_clear.1` | Vector recipe/published specialization method equivalent; helper/source mapping remains | E |
| `core.vector_clear_keep_capacity.1` | Clear method exists; bind/check capacity-retention helper contract explicitly | E |
| `core.vector_compact.1` | Vector recipe/published specialization method equivalent; helper/source mapping remains | E |
| `core.vector_compact.2` | Vector recipe/published specialization method equivalent; helper/source mapping remains | E |
| `core.Exception.make` | Missing source Exception object path; exception_info is not this type | D/G |
| `core.Exception.get_message` | Missing source Exception object path; exception_info is not this type | D/G |
| `core.error.make` | Published error_value factory/getter equivalent | C (integration) |
| `core.error.get_message` | Published error_value factory/getter equivalent | C (integration) |
| `core.error.get_line` | Published error_value factory/getter equivalent | C (integration) |
| `core.error.get_file` | Published error_value factory/getter equivalent | C (integration) |
| `core.getenv` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.shell_exec` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.cli_argc` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.cli_argv` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.cli_args` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.memory_get_usage.0` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.memory_get_usage.1` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.memory_get_peak_usage.0` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.memory_get_peak_usage.1` | Missing focused public helper; retain current registered core ownership pending review | G |
| `core.is_bool.mixed` | Published mixed predicate method equivalent | B/E (helpers) |
| `core.is_int.mixed` | Published mixed predicate method equivalent | B/E (helpers) |
| `core.is_float.mixed` | Published mixed predicate method equivalent | B/E (helpers) |
| `core.strlen` | Published strlen(string) | Complete baseline |
| `core.count.vector` | Published count(vector_string), generic vector recipe | E (capabilities) |
| `core.count.hash` | Hash recipe count; no permanent hash demand required | E (capabilities) |
| `core.count.mixed` | Missing boxed count free function | E |
| `core.count.dynamic` | Missing boxed count free function | E |

## Active-goal progress — A1

Scope recommendations accepted 2026-10-27; implementation goal active. A1 ordinary
numeric arithmetic/comparison bindings are implemented through the existing global
metadata mechanism, with integer-only remainder and native zero-divisor checks.
The normative promotion rule has been revised, with the existing frontend gap
explicitly recorded. A2 checked conversions is next; no ownership/callback or module
scope has been added. Validation is recorded in the runtime boundary results.

## Active-goal progress — A2a

Before publishing checked conversions, harden their shared native owner in
`scpp/cast.hpp`: check finite/truncated floating values against exact integer
bounds before conversion, and enforce the configured whole-string finite-decimal
parse without a temporary lowercase string. Focused correctness, zero successful
allocation checks and ASan/UBSan/leak checks pass; measurements are recorded in
`runtime/specs/public_api_boundary_results.md`.

This is a prerequisite, not completion of A2. Public conversion metadata and
publication remain pending. The follow-up native integer parser now uses the target signedness family and
accepts the full uint64 range. The remaining A2 work is metadata-driven public
conversion admission, generation and publication, including boxed conversions. No carrier,
ownership, callback or module scope is added by this slice.

## Active-goal progress — A2b numeric-target conversion publication

The existing API generator now accepts metadata-defined checked conversions and
returns normalized source plans. Numeric-width sources normalize losslessly to
their signedness family's maximum; string and float64 retain their type. Runtime
publication provides 36 adapters for four normalized sources and nine numeric
targets, with ordinary API import/reuse and no template-bearing public headers.
The generic registry owns admission, normalization and snippets; the shared native
cast owner remains responsible for value checks. Raw representation access is not
repurposed as a language cast.

Remaining A2 work: boolean targets/sources, string formatting results, admitted
boxed conversions, and their complete publication/validation. Carrier conversions
remain in C. No frontend integration, ownership/callback or module completion is
claimed. Evidence is in `runtime/specs/public_api_boundary_results.md`.
