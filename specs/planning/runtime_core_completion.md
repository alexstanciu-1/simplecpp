# Runtime-core public API completion inventory and plan
Doc Status: planning

Checkpoint: 2026-10-27, implementation baseline `6a1cb4af2`.
This is the current completion inventory under [the runtime API migration plan](runtime_public_api.md),
not a new semantic registry. No implementation is authorized merely by a row here.

## Current implementation status

Updated 2026-10-28. This summary supersedes unfinished-work statements in the
chronological checkpoint notes below; those notes retain the evidence and scope of
each slice. The original inventory is a baseline, not a current completion count.

| Batch | Current status | Remaining work |
| --- | --- | --- |
| A1 | Complete for the agreed ordinary numeric domain | Specialized and boxed extensions remain in B2/E, not implied by A1. |
| A2 | Complete for the agreed checked scalar conversions | Carrier conversion is C; ownership casts are D. |
| B1 | Complete for the agreed scalar-facing truth/text/identity/observation scope | Carrier forms remain C; boxed arithmetic and access completion are not B1 credit. |
| B2 | Complete for the agreed non-carrier special/mutation and text public paths; closure evidence below | Carrier forms C; indexed retention E; native exception inspection and overflow disposition G. |
| C | Four carrier recipes and bounded scalar paths implemented; first prepared canonical path reviewed in C38 | Guarded negation and canonical lazy logical plans implemented in C32/C33; remaining source admission, broader selection and payload-specific integration remain; C38 passes the first-path checkpoint. Both decisions accepted 2026-10-28. |
| D | Handle storage, presence planning, checked retention, shared ancestry/casts and weak/unique transports implemented (D1–D6) | Typed member access, operation-planner integration, ownership selections, value storage and source Exception transport remain. |
| E | Existing vector/hash/boxed foundations; incomplete | Typed nested access and remaining key policies; conditional lifecycle, cursor fixes, boxed access/probes, and positional/keyed built-in factories are implemented for the tested domains. |
| F | Pending | Callable transport and registered collection algorithms. |
| G | Core utility and scalar debug bindings implemented | Exception/helper gaps and requirement-by-requirement closure audit. |

Full frontend adoption and module migration are excluded. C38 passes the first complete canonical carrier
path review, permitting the planned ownership prototype; callbacks still depend on
the ownership/transport evidence they require.
No completion percentage is inferred from function, header or checkpoint counts.

The [current carrier review](runtime_core_carrier_review.md) records why C is not yet
ready for sign-off and the scoped-observation implementation/proof boundary.

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

## Baseline surface and remaining contracts

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
| result_or_false<T>, result_or_bool<T> | Recipes, sentinel factories, conditional lifecycle, state/take and nested/noncopyable physical proofs | Broader payload/operator domains, source formation constraints and complete state-preserving plan execution matrix. | C |
| mixed_t and dynamic_t | Published scalar/null construction and extraction, family-preserving containers, mutation/removal/iteration, explicit scalar family crossing, checked equality | Numeric/bitwise/order/unary/compound public paths; condition/text conversion; count/empty/isset/probe helpers; nested keyed operations. Preserve mixed value/COW versus dynamic identity. Cycle reclamation remains separately accepted debt, not an API migration. | A/B/E |
| shared/unique/weak and value storage | Native contracts only | Concrete ownership recipes, destruction/retain/release/transfer/lock; absence and identity; admitted up/downcasts, stable extraction, shared-self; application-defined target layout dependencies. `value<T>` is an existing storage modifier, not a new unrelated template identity. | D |
| Struct/class/enum and reference argument transport | Application leaf descriptors and simple record composition proofs | General prepared capability/representation facts, recursive declaration versus layout dependencies, noncopyable payloads, object identity/ancestry and safe references. Native references remain a passing/binding contract, not another public wrapper family. Do not expose heap-interior native references. | C/D/E |
| Initializers and keyed protocols | Native typed/dynamic initializer and index/path/probe owners; individual public methods cover only part | Ordered initialization with failure cleanup; indexed read/write/append, captured destinations, nested path operations, keyed isset/empty/unset and alias-safe mutation. Source evaluation remains compiler-owned. | E |
| Callables and higher-order collections | Native callable/collections implementations; registered map/filter functions | Concrete signature-based callback ABI, captures and destruction, invocation/error propagation and reference restrictions; then collection/sequence/keyed map/filter for admitted families. Do not expose std::function or generate per-function-name exceptions. | F |
| Core utilities, debug and memory | Some per-value storage estimates and predicates; no general utility public surface | Registry helpers in the appendix, debug flags, required value_copy/reset/memory/identity helpers. Distinguish actual accounting semantics from estimated storage bytes. Audit language-adapter dependencies. | E/G |

The original inventory contained nine recipe families: vector, hash,
nullable, result, result_or_false, result_or_bool, float64, mixed and dynamic. At the original inventory, the first four all imposed copy
construction/assignment and nothrow-destruction checks. Nullable and Result now use conditional
lifecycle and payload APIs (C10/C11); vector follows in E1 and hash in E2; their remaining native constraints are recorded below. This is a real coverage
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

## Implementation batches

Each batch is a coherent ownership area, not a mandate for one large commit. Keep
review points between its sub-slices, use the same emitter/publication protocol, and
batch compilations after metadata-only checks.

| Batch | Work that belongs together | Dependency / meaningful completion proof |
| --- | --- | --- |
| A1: ordinary numeric operations | Concrete numeric subtraction/multiplication/division, integer remainder and numeric equality/ordering through global metadata; reuse addition normalization and native arithmetic/error authorities | Extend the current slice, without wrappers or new widths. One combined matrix checks same/mixed widths, signedness rejection, float promotion, zero divisors, boundaries and comparison return types. No original operand-pair code expansion. |
| A2: checked conversions | Concrete scalar checked casts, string parsing/formatting, admitted boxed scalar extraction/conversion; distinguish casts from raw representation constructors | A1/common resolver. Range errors, invalid whole-string parses, NaN/infinity policies and accepted precision loss; metadata names selected native cast authority. No silent unchecked narrowing. |
| B1: scalar truth/text/identity | Existing truthiness, eager logical xor, concatenation, strict identity and primitive presence/empty/reset protocols | A2; preserve false-like strings and context-specific text formatting. Prove identity does not promote and predicates do not accidentally admit arithmetic. |
| B2: special numeric/mutation groups | Bitwise, shifts, checked power, unary/update/compound and spaceship where already admitted | A1/A2. Preserve left-width shift checks, power errors, NaN behavior and validate-before-write. Lazy `&&`, `\|\|`, `??` and ternary expressions expose required observations while evaluation stays in the compiler. |
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
are approved and partially implemented as recorded in the current status above. The accepted scope includes native-only facilities only when required by an included
core contract; full frontend adoption remains excluded.

## Next implementation work

B2 non-carrier public-path closure is recorded below, with limitations retained.
Proceed to C with the accepted one-layer carrier rules; the first complete carrier
path must be reviewed before D/F expansion. Do not treat existing native template
instantiations as source admission or skip state/lifetime proofs to close a row.

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
| `core.hash.make` | Hash recipe static empty make() factory; collection initialization remains separate | E |
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
| `core.dbg.int.value` | Published scalar debug binding and flags, G5/G6 | G |
| `core.dbg.int.flags` | Published scalar debug binding and flags, G5/G6 | G |
| `core.dbg.float.value` | Published scalar debug binding and flags, G5/G6 | G |
| `core.dbg.float.flags` | Published scalar debug binding and flags, G5/G6 | G |
| `core.dbg.bool.value` | Published scalar debug binding and flags, G5/G6 | G |
| `core.dbg.bool.flags` | Published scalar debug binding and flags, G5/G6 | G |
| `core.dbg.string.value` | Published scalar debug binding and flags, G5/G6 | G |
| `core.dbg.string.flags` | Published scalar debug binding and flags, G5/G6 | G |
| `core.vector.append` | Published append member, matching the catalog instance dispatch; full frontend adoption excluded | Complete public path |
| `core.microtime.0` | Published focused core binding, G1 | Complete public path |
| `core.microtime.1` | Published focused core binding, G1 | Complete public path |
| `core.vector_reserve.2` | Published helper binding and vector recipe; frontend adoption remains excluded | E |
| `core.vector_capacity.1` | Published helper binding and vector recipe; frontend adoption remains excluded | E |
| `core.vector_resize.3` | Published helper binding and vector recipe; frontend adoption remains excluded | E |
| `core.vector_filled.2` | Published copy-gated filled helper; partial-copy failure cleanup proved | E |
| `core.vector_clear.1` | Published helper binding and vector recipe; frontend adoption remains excluded | E |
| `core.vector_clear_keep_capacity.1` | Published helper; capacity retention and element destruction proved | E |
| `core.vector_compact.1` | Published helper binding and vector recipe; frontend adoption remains excluded | E |
| `core.vector_compact.2` | Published helper binding and vector recipe; frontend adoption remains excluded | E |
| `core.Exception.make` | Missing source Exception object path; exception_info is not this type | D/G |
| `core.Exception.get_message` | Missing source Exception object path; exception_info is not this type | D/G |
| `core.error.make` | Published error_value factory/getter equivalent | C (integration) |
| `core.error.get_message` | Published error_value factory/getter equivalent | C (integration) |
| `core.error.get_line` | Published error_value factory/getter equivalent | C (integration) |
| `core.error.get_file` | Published error_value factory/getter equivalent | C (integration) |
| `core.getenv` | Published core Result/string composition, G3 | Complete public path |
| `core.shell_exec` | Published core Result/string composition, G3 | Complete public path |
| `core.cli_argc` | Published focused core reader, G2 | Complete public path |
| `core.cli_argv` | Published focused core reader, G2 | Complete public path |
| `core.cli_args` | Published focused core reader, G2 | Complete public path |
| `core.memory_get_usage.0` | Published concrete process-memory observation, G4 | Complete public path |
| `core.memory_get_usage.1` | Published concrete process-memory observation, G4 | Complete public path |
| `core.memory_get_peak_usage.0` | Published concrete process-memory observation, G4 | Complete public path |
| `core.memory_get_peak_usage.1` | Published concrete process-memory observation, G4 | Complete public path |
| `core.is_bool.mixed` | Published exact-mixed predicate function, G7 | Complete public path |
| `core.is_int.mixed` | Published exact-mixed predicate function, G7 | Complete public path |
| `core.is_float.mixed` | Published exact-mixed predicate function, G7 | Complete public path |
| `core.strlen` | Published strlen(string) | Complete baseline |
| `core.count.vector` | Published count(vector_string), generic vector recipe | E (capabilities) |
| `core.count.hash` | Hash recipe count; no permanent hash demand required | E (capabilities) |
| `core.count.mixed` | Published checked count(mixed), E4 | Complete public path |
| `core.count.dynamic` | Published checked count(dynamic), E4 | Complete public path |

## Chronological implementation checkpoints

The following entries record their own checkpoint state. Consult the current status
summary above for remaining work; earlier “pending” statements are historical.

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

## Active-goal progress — A2c boolean, text and boxed conversions

The same registry/publication path now provides 57 adapters, including boolean and
string results, canonical scalar casts from boolean/mixed/dynamic, and null-to-string.
Native string return transport constructs the owned public result in final storage;
boxed arguments borrow the existing representation. No new family-specific generator
branch or native scalar semantic change was required.

The currently exposed native scalar cast paths are covered, but before closing A2
reconcile fixed-width boolean/boxed destination admission with source contracts:
those native overloads currently support canonical int64 only. Keep these rejected
rather than silently inventing an intermediate conversion policy. A2's remaining
review must also check config/matrix casts to boxed results and ensure any already
published methods are correctly represented by the global metadata mechanism.
Carrier conversions remain C; this does not mark runtime-core complete.


## A2 closure audit — payload-origin decision

The user confirmed that mixed/dynamic must retain native payload identity during
casts: integers are int64 and floats are float64, and use the same checked rules as
those native types. The shared cast owner now applies the requested integer target
to each original payload, with native boolean zero/one conversion extended uniformly
to fixed widths. This supersedes the fixed-width admission question in A2c above.

The publication now has 78 conversion adapters. Existing scalar-boxing constructors
and explicit family conversions already satisfy those paths; no duplicate adapters
were added. See `runtime_core_conversion_audit.md` for the complete A2 coverage and
routing of carrier/ownership/container conversions to C/D/E. After this slice's
validation and publication, A2's scalar scope is complete. B1 scalar truthiness,
context-specific text conversion, strict identity and observation protocols is next.
The full goal and the first-carrier review gate remain active.

## Active-goal progress — B1a condition observations

One shared function-set recipe publishes 14 concrete condition observations through
the ordinary operation emitter/publication path. It preserves native string and
boxed rules, including truthy "false", false boxed null, rejected mixed tables and
truthy constructed dynamic objects. Error values delegate to native error presence.

The generic function-set expansion is metadata-only factoring, not another family
emitter. Publication now rejects generated output-path collisions as well as API
ownership collisions. B1 remains open: strict identity, context-specific text/concat,
eager xor and presence/empty/reset consolidation are still pending. Carrier condition
completion belongs to C; ownership/callable conditions remain in D/F.

## Active-goal progress — B1b bounded plain-scalar concatenation text

The same function-set mechanism now publishes 11 plain `concat_text` variants,
with context-specific native formatting and owned string return transport. Primitive
concatenation/append already exists on the public string and is reused, avoiding
pair combinations. The bounded domain follows `expression_evaluation.md`: uint64,
boxed values and carrier payloads are not added by this plain-scalar slice.

Formatting differences from explicit casts, embedded bytes, lifetime, allocations
and publication reuse are covered. Carrier text behavior remains C. B1 strict
identity, eager xor, and presence/empty/reset consolidation remain open; source
sequencing/write-back and full frontend adoption are not claimed by these APIs.

## Active-goal progress — B1c same-type strict identity

Two existing function-set recipes publish 14 adapters for seven same-type domains
and their negation. Primitive/boxed comparisons preserve kinds and use the native
identity owner. Tests distinguish ordinary numeric boxed equality from strict
identity, exercise both 81-pair boxed scalar matrices, NaN/signed zero, complete
string bytes and native container semantics.

B1 still needs heterogeneous identity planning without promotion or pair explosion,
boxed/plain admission, eager xor and presence/empty/reset consolidation. Carrier
state-preserving identity remains C and must not reuse legacy sentinel-as-payload
normalization unchanged.

Container debt (E): native `identical_hash_contents` obtains keys through
`packed_table::const_entry_view::key()`, constructing temporary mixed strings. A
one-entry table with a short string key allocates twice during identity, while
measured scalar/string/dynamic-identity observations allocate zero. Design a safe
borrowed/internal key-comparison path with the table owner; preserve key kind,
complete bytes and order. Do not add public borrowed references as a shortcut.

## Active-goal progress — B1d heterogeneous plain-scalar identity plans

Global metadata now resolves all 50 canonical plain-scalar identity/negation pairs
without promotion or new pair adapters. Same types reuse existing function-set API
records; mismatched types produce a constant only after ordered eager evaluation.
Domain-set selectors and exact input admission keep the policy compact. A compiled
public-only consumer proves both operand effects, exception order and zero successful
allocations against the native helper. Runtime API source and archive are unchanged.

This completes plain canonical scalar planning, not all identity work. Boxed/plain
and carrier dispatch remain; fixed-width identity requires its own source admission
rather than assuming every native template instantiation is part of the language.
B1 eager xor and presence/empty/reset consolidation are also still pending.

## Active-goal progress — B1e eager canonical logical xor

One boolean backend plus existing conversion metadata handles bool/bool and the four
canonical int/float pairings. Numeric operands convert independently to bool; no
arithmetic promotion or pair matrix is generated. A compiled public-only/native
consumer checks 85 value combinations, complete left conversion before RHS,
unconditional RHS reachability for either left truth value, failure suppression at
each stage and zero successful allocations.

Plain canonical xor is covered. Carrier numeric xor remains C and must require its
payload before converting; direct carrier condition truthiness is not equivalent.
B1 boxed/plain identity and presence/empty/reset consolidation remain open, alongside
explicit audit of fixed-width identity admission.

## Active-goal progress — B1f primitive observations and resets

The public API now fills the scalar presence gaps (float64/null/mixed/dynamic),
boxed collection emptiness and numeric/boolean/string cleanup. Seventeen generated
adapters reuse ordinary function-set metadata. Existing integer/string observations
and boxed reset functions retain their original owners.

Focused proofs cover false/zero versus absence, empty string versus "0", boxed
unsupported-kind failures, null-containing nonempty tables, reset through aliases,
and dynamic copies retaining the old container after another binding is cleaned.
Successful observation/reset paths allocate zero times with setup excluded.
This closes the primitive observation/reset portion of B1; keyed operations,
container capability completion and carrier rules stay in C/E. Boxed/plain identity
and explicit fixed-width identity admission remain B1 work.

## Active-goal progress — B1g boxed/plain identity and admission audit

Boxed/plain strict identity preserves both argument types through a generic ordered
pair recipe selection. The public path reuses the existing native kind-sensitive
identity authority, without normalizing scalar peers into allocating boxes. Both
source operand directions share each implementation after ordered eager evaluation.

Admission audit: `Boxed_Operators::decide_comparison` retains the original scalar
operand for strict comparison, admits matching boxed/canonical scalar/null peers,
and rejects mixed/dynamic crossings. `compact_layout_types.md` section 7 explicitly
does not extend fixed-width strict identity; `Scalar_Identity` likewise admits
canonical scalar kinds. Native fallback templates do not authorize additional widths.
The metadata continues rejecting these domains rather than inventing new semantics.
This closes the identified B1 strict-identity gaps; a B1 closure audit still needs
to account for boxed text conversion. Carrier identity remains C, and mixed-table
key-allocation debt stays E. Compiler adoption remains excluded from this goal.

## Active-goal progress — B1h boxed concatenation text

Two variants of the existing text recipe publish mixed/dynamic text conversion.
Native formatting and family-specific container labels remain unchanged; primitive
string concatenation is still separate. Tests cover null/bool/numeric/string kinds,
embedded bytes, temporary lifetime and allocation counts. There is no pairwise
concatenation implementation matrix and no new generator logic.

B1's agreed scalar-facing truth/text/identity/observation surface is implemented.
Carrier text/identity/truthiness remain C; lazy source evaluation and compound
write-back still belong to the compiler and B2/C's respective protocols. This is
not a claim that core completion or all boxed operations are finished.

## Active-goal progress — B2a binary integer specials

Sixteen ordinary recipe variants expose canonical integer and homogeneous boxed
bitwise and/or/xor, checked left/right shifts, and canonical checked power. Mixed
and dynamic preserve their family and require integer payloads. Source admission
stays explicit: no fixed-width, cross-family or boxed/plain expansion. Native
helpers retain their error and valid-count behavior without duplicating algorithms.

B2 remains open for unary operations, updates/compounds and spaceship. Carrier
variants remain C; publishing a calculation is not publishing ordered write-back.

B2a audit finding for G: native checked shifts throw `ValueError`, a
`std::runtime_error` subclass outside the structured `scpp::runtime_error` family.
`capture_runtime_exception()` intentionally rethrows such foreign exceptions.
The public operation preserves the original failure, but template-free inspection
of ValueError and legacy boxed kind errors is not yet covered. G must resolve this
required core error surface; do not count catch-all delivery as complete inspection.
The focused C++ harness currently checks the native standard-exception message.

## Active-goal progress — B2b numeric spaceship

Nine normalized public functions cover same-signedness integer spaceship and the
canonical floating/int64 combinations. A generic per-side type restriction on
metadata rules preserves the narrower floating admission without a pair matrix.
The native numeric comparison owner now contains same-representation integer
comparison as well as the existing NaN-checking float helper. Integer comparison
never subtracts; all results are canonical int64 -1/0/1.

B2 still needs unary operations and updates/compounds. Carrier extraction remains C;
source evaluation and mutation protocols are not implemented by a value comparison.

## Active-goal progress — B2c unary numeric values

The operator resolver now represents unary calls directly, reusing function-set
signature validation, native transport and publication. Eleven implementations
provide positive/negative canonical numeric and boxed values, plus integer/boxed
complement. Boxed results retain their family and native numeric/integer kind
requirements. No binary-pair scaffolding or type-specific generator branch is used.

Native signed-overflow limitations remain unchanged; the tests do not claim checked
negation of minimum int64. Logical negation's condition admission, updates/compounds
and carrier variants remain open. Legacy boxed error inspection remains the G gap
identified in B2a.

## Active-goal progress — B2d scalar-facing logical negation

Unary result composition reuses six existing condition observations and public
boolean negation; no runtime adapter or core archive rebuild is required. Metadata
requires one result marker and retains evaluate-once semantics. Plain string and
fixed-width condition availability does not widen the current logical admission;
carrier/ownership/callable forms stay with C/D/F. The condition contract explicitly
separates truthiness from logical-operator admission.

A plan-driven public consumer verifies false-like boxed strings versus explicit
casts, numeric/bool/error states, rejected mixed containers, truthy dynamic objects,
operand evaluation once and zero successful allocations. Observation failure must
propagate before result transformation. B2's remaining scalar-facing work is update
and compound protocols; broader carrier/ownership behavior remains unfinished.

## Active-goal progress — B2e prefix/postfix updates

Sixteen function-set variants publish prefix/postfix increment/decrement for the
canonical numeric types and both boxed families. Unary operand passing now records
mutable reference requirements and one writable destination resolution. Results are
owned snapshots: later destination mutation cannot change either prefix or postfix
results. Existing public integer prefix methods and native numeric/boxed operators
retain representation ownership and failure behavior.

Tests verify exact mutable signatures and by-value return types, alias updates,
old/new snapshots, single destination resolution, invalid boxed kinds preserving
storage, safe integer boundaries and floating NaN/infinity. Successful operations
allocate zero times. Source property/index owner capture and carrier updates remain
D/E/C work; B2 still needs compound snapshot and write-back protocols.

## Active-goal progress — B2f canonical numeric compound plans

The C++ metadata resolver now returns an ordered compound protocol and existing
calculation/conversion API identities. Eleven canonical integer compounds and four
float-destination arithmetic operations with int/float RHS yield nineteen admitted
plans without additional runtime adapters. Directional admission is metadata;
fixed-width, boxed, carrier and text cases are not silently inferred.

Public-only generated consumers prove destination resolution once, preserving the
old left before RHS mutation, owned result snapshots, failure before RHS, and no
compound write-back after RHS/calculation failure. Successful paths allocate zero.
Publication reuse emits no duplicate definitions. This is runtime-plan evidence,
not full compiler frontend adoption. Boxed stored-kind validation and text compound
replacement remain B2 work; carrier write-back remains C and indexed owners E.

## Active-goal progress — B2g boxed arithmetic prerequisite

The existing global add/subtract/multiply/divide/remainder identities now resolve
homogeneous mixed and dynamic operands to ten public implementations. Plain numeric
implementations retain their generated code; their descriptors and boxed descriptors
share ordinary function-set ownership. Arithmetic admission does not widen comparison
or ordering admission. Duplicate normalized signature checks cover both recipe forms.

Public consumers test all four numeric-kind pairings, family-preserving results,
integer-only remainder, nonnumeric rejection, zero divisors, source snapshots and
zero allocations on successful numeric paths. This is the calculation prerequisite
for boxed compounds, not their completion: the preserved-left stored-kind check and
text write-back remain B2, scalar-peer extension remains open, and carriers remain C.

## Active-goal progress — B2h saved-kind boxed compound replacement

Ten homogeneous mixed/dynamic arithmetic compound plans now use two shared checked
replacement recipes. Metadata links the write-back operation and requires borrowed
saved-left/calculated arguments with an owned result. The native numeric kind check
runs on private saved-left storage before any live destination commit.

The public execution proof exercises RHS changes to the opposite live numeric kind:
original int plus float still rejects, while original float plus int still succeeds.
Failures preserve RHS effects; successful results remain independent snapshots and
allocate zero times. This completes homogeneous boxed arithmetic compounds, not text,
scalar peers, bitwise compound admission or carrier/indexed write-back.

## B2 admission audit — boxed bitwise compounds are source-deferred

The previous remaining-work phrase “bitwise admission” was too broad. The
[boxed integer amendment](../dynamic_types.md#v02-boxed-integer-operators--2026-10-20)
admits homogeneous binary expressions and explicitly leaves compounds separate.
[Checked shift counts](../compact_layout_types.md#71-checked-integer-shift-counts)
expressly do not expand boxed compound admission. The legacy compiler's
`Boxed_Operators::decide_integer` rejects non-expression contexts with “Boxed bitwise
updates remain deferred”; this is supporting evidence, not the semantic authority.

The native mixed compound operators and the general runtime compound derivation
policy do not override that narrower source scope. Under this goal's accepted
native-only rule, exposing additional boxed bitwise/shift compounds is not required
without an included core contract demanding them. Keep them deferred, not implemented
or counted as API coverage. Boxed power likewise remains outside the canonical-only
power scope. Negative metadata proofs now retain all twelve homogeneous boxed
bitwise/shift/power compound rejections. No runtime behavior or normative semantics
changed in this audit.

Numeric scalar-peer arithmetic/compounds and text replacement remain required B2
work. Carrier forms remain C; this audit does not waive those families or the review
gate before ownership/callback expansion.

## Active-goal progress — B2i canonical numeric scalar peers

Binary arithmetic now admits canonical int/float peers for mixed and dynamic in both
orders, with integer-only remainder. Thirty-six binary plans select the ten existing
boxed arithmetic implementations. Eighteen boxed-destination compound plans reuse
the same arithmetic and saved-kind replacement APIs. Two metadata operand recipes
use existing public constructors; no new runtime adapter or template is published.

Plans explicitly describe left/right operand conversions and preserve sequencing.
Public proofs exercise both binary orders with RHS mutation of the original left,
all compound numeric kind pairings, failure effects and zero successful allocations.
Cross-family, bool/string, fixed-width/unsigned peers, float remainder and native
scalar destinations with boxed RHS remain rejected. This closes the admitted numeric
scalar-peer arithmetic/compound gap; text compound work remains B2 and carriers C.

## Active-goal progress — B2j bounded scalar text composition

Global metadata now composes the existing text-conversion family with one primitive
string calculation. It resolves 121 bounded scalar text pairs and eleven string
compound RHS forms, selecting context-specific conversion signatures rather than
explicit casts. Compounds reuse the calculated operand plans. Owned snapshots may
be borrowed for identity conversions instead of copied again.

Public proofs cover the distinct float formatter, RHS mutation, self concatenation,
independent results, embedded NUL/UTF-8 bytes, failures before RHS and in right
conversion/calculation, and allocation parity with primitive long-string calculation.
This completes the bounded plain scalar/string-storage composition path. Boxed text
replacement remains B2, carriers remain C, and full frontend adoption is excluded.

## Active-goal progress — B2k boxed text replacement

Text metadata now resolves 143 binary pairs and 25 compound forms. Boxed text uses
canonical scalar/null or matching-family peers; string destinations also accept a
boxed RHS. Cross-family and compact integer/boxed pairs stay excluded. A rule must
explicitly declare the differing write-back source (string) before constructing a
boxed replacement. Numeric saved-kind checks remain separate and unchanged.

Allocation measurement exposed unnecessary raw boxed snapshots in the shared plan.
Text now saves the converted string before RHS evaluation; numeric compounds retain
the original value needed by their check. This avoids deep copying original boxed
strings or containers just to render text. Public proofs cover all original boxed
kinds, preserved aliases, both boxed binary orders, byte preservation, independent
result storage, and injected conversion/replacement failures. Short boxed compounds
match direct native construction plus independent destination/result storage costs;
they are not claimed allocation-free. No runtime implementation units were added.

The text composition paths are implemented. Before marking B2 complete, audit its
remaining admitted operation rows against the current metadata and proofs. Carrier
text/state/lifecycle work remains C; the first complete carrier path still requires
review before ownership/callback expansion.

## B2 closure audit — non-carrier public paths

This closes B2's agreed public-path slice, not runtime-core completion or a blanket
claim of safe behavior for every native arithmetic input. The audit inspected the
current operation/rule descriptors, publication inventory and public headers, read
the proof bodies, and resolved/imported all 58 normalized API identities behind the
15 special-operation identities with zero duplicate units. Existing compound/text
proofs cover their ordered plans separately. Counts are inventory checks, not a
completion percentage.

| Included requirement | Current public evidence | Boundary retained |
| --- | --- | --- |
| Canonical/boxed bitwise and checked shifts | `check_special_integer_api.py`, `test_public_special_integers.cpp`: same-family admission, both operand kind failures, valid/invalid count boundaries and zero successful allocations | Boxed bitwise compounds/scalar peers remain source-deferred; fixed-width expansion is not inferred. |
| Checked canonical power and power compound | Special-integer proof checks negative exponent/overflow codes and boundaries; `check_compound_api.py` proves no write-back after failure | Float/boxed/fixed-width power is not admitted. |
| Numeric spaceship | `check_compare_api.py`, `test_public_compare.cpp`: same-signed width normalization, bounded mixed float admission, extreme integers, NaN errors, zeros/infinities | No boxed, bool or string spaceship expansion. |
| Unary numeric and logical negation | `check_unary_api.py`, `test_public_unary.cpp`, `check_logical_not.py`: exact signatures, admitted kinds, failures and observation composition | Condition helpers alone do not authorize every unary source type. |
| Prefix/postfix updates | `check_update_api.py`, `test_public_updates.cpp`: mutable signatures, owned old/new snapshots, invalid-kind unchanged storage, allocation checks | No new checked-overflow rule; carrier updates remain C. |
| Canonical and boxed arithmetic compounds, scalar peers | `check_compound_api.py`: destination once, saved/converted left order, RHS effects, result lifetime and preserved original boxed kind | Plain scalar destination with boxed RHS and fixed-width compounds remain deferred. |
| Scalar/boxed text and replacement | `check_text_compound_api.py`: context-specific conversion, converted snapshot, byte preservation, kind replacement, allocation/error ordering and independent results | Carrier text remains C; indexed storage retention remains E. |
| Required observations for lazy expressions | Published `condition_truthy`, boxed `is_null`, and owned scalar/boxed copies provide the non-carrier observations | Branch evaluation and source expression joins remain compiler-owned; carrier/ownership/callable observations remain C/D/F. |
| Publication/purity/ownership | Existing focused metadata proofs import normalized implementations without duplicates; public-only consumers and sealed relocation proofs compile the planned paths | Full frontend adoption and module migration remain excluded. |

The four compiled special-operator consumers were relinked against current release
and sanitizer core archives for this audit. No C++ source or runtime-core rebuild was
needed. The previously measured allocation and compile costs remain attached to each
slice in `runtime/specs/public_api_boundary_results.md`.

Remaining issues are not erased by this batch closure:

- G still owns template-free inspection of native `ValueError`/foreign boxed
  diagnostics; catch-all propagation is not complete structured error inspection.
- Existing signed arithmetic overflow limitations, including minimum-int negation
  and overflowing increment/decrement, were not given new checked semantics by this
  migration. The expression contract expressly excludes introducing those semantics.
  Boundary proofs use the supported range; no all-input UBSan/memory-safety guarantee
  is claimed. Keep their disposition visible at final G closure.
- Boxed comparison/ordering completion beyond the already published checked equality
  and identity paths remains in the core backlog; the original boxed row's remaining
  requirements are not waived by completing B2's special/mutation groups.
- C, D, E, F and G remain incomplete. Wrapper, indexed, ownership and callback paths
  are not credited from scalar/boxed proofs.

## C entry path and review boundary

Start with an application-demanded `nullable<int64>` path using the existing family
recipe and the public int64 operations. That recipe already provides construction,
assignment, owned checked extraction, reset, defaulting and `take`, but blanket copy
requirements and missing operator/state policies prevent calling the family complete.

The first vertical path must add metadata-owned one-layer projection and preserve
left-before-right extraction failure, reuse scalar arithmetic, distinguish presence
from truthiness, and exercise take/reset/owned lifetime plus publication reuse. Then
complete its accepted equality/identity/conversion behavior and test the complete
path before the agreed review gate. Do not delegate blindly to native wrapper
operator templates: the recipe stores the public payload type, which must use its
published scalar API rather than assume native template traits recognize it.

This path does not authorize recursive peeling, promote all nullable payloads into
conditions, or claim move-only support. Conditional lifecycle remains required C
work shared with later ownership/container completion. The three Result-family
recipes and their state distinctions remain explicit subsequent work. No D/F
implementation should start before review of the first complete carrier path.

## C preparation — shared family identity and argument binding

The metadata-to-naming adapter now belongs to the existing naming component,
accessible to both physical generation and forthcoming carrier operation planning.
`naming::bind` returns the normalized family application and its ordered named
arguments. Physical generation consumes that binding instead of independently
pairing positional arguments with recipe parameters. The existing owner still
resolves aliases, dependent defaults, structural names and publication assertions;
there is no second carrier type registry or family-name dispatch.

This is preparation, not carrier-operation completion. It adds no runtime API,
operator admission, recursive peeling or allocation guarantee. Next: metadata-owned
one-layer projection for the first `nullable<int64>` operation path, using the
shared binding and published payload operations.

Validation: existing metadata tests exercise constant/dependent/chained defaults,
explicit overrides, nested specializations, renamed recipes, capability selection,
invalid metadata and publication reuse. The public generator/concurrency test and
global-operator/compound resolution suites are also included. Release: 4/4 pass
in 6.52s; ASan/UBSan with leak detection: 4/4 pass in 26.61s.
Generator plus public test incremental build: 7.40s release and 22.02s
ASan/UBSan (eight jobs); runtime-core was not rebuilt. These are tool rebuild
measurements, not public-caller compile costs or full compiler rebuild times.

## C1 — checked arithmetic projection entry path

Family descriptors identify the checked owned-payload member; the global metadata
policy selects arithmetic operations and permitted plain numeric payloads. The
resolver binds each operand with the existing naming/default machinery and then
reuses the ordinary numeric operation/conversion plan. It emits no wrapper-pair
runtime adapters and rejects recursive peeling. Nullable and Result have descriptors;
false/bool-sentinel families remain pending, as do conditional lifecycle rules.

`check_carrier_projection.py` checks the five-operation nullable/Result/plain matrix,
width/float normalization, signedness rejection, one-layer and operation boundaries,
renamed/defaulted families, application names and malformed projection descriptors.
The compiled public-only application proof covers nullable<int64> arithmetic,
left-before-right extraction failure, copy/reset/take, zero-payload presence and owned
extraction surviving reset/destruction. Successful measured operations allocate zero;
exception paths are not included in that claim. Sealed application publication reuse
produces no implementation units.

All 330 runtime generation plans compare equal before/after the metadata addition,
so only the metadata identity in the publication inventory changed. No layout probe
or runtime-core rebuild was needed for existing runtime units. The application-owned
nullable layout and implementation were generated and compiled normally.

This is an arithmetic entry slice, not a complete carrier path. Next: state-aware
nullable truthiness, equality/strict identity and conversions, then review the complete
path before ownership/callback expansion. Result errors/sentinels, remaining families
and source frontend adoption are not inferred from the nullable proof.

## C2 — carrier state descriptions and required-scalar casts

The shared private `carriers` owner now validates family descriptors and returns
checked projections for both arithmetic and conversions. Ordered state descriptions
name const boolean observation methods and a final fallback. Per-state scalar
factory recipes express nullable absence-to-empty-string without generator branches
for nullable or string. Ordinary payload cast admission is checked before selecting
any state exception; nested payloads are not recursively extracted.

Required-scalar plans cover nullable and Result descriptors. The public-only
nullable<int64> proof adds explicit bool casts (zero versus absent), checked int8
narrowing with unchanged source on failure, and string casts including the absence
exception. These measured success cases allocate zero, and the existing arithmetic,
take/reset/lifetime and sealed-publication reuse proof remains included. Runtime
implementation generation is unchanged across all 330 runtime units; only the
publication metadata identity changes. No runtime-core rebuild was performed.

Remaining before the first complete carrier-path review: conditions, state-aware
equality/strict identity, and same-family nullable conversion/reconstruction where
admitted. C still includes Result execution/error transport, sentinel recipes,
conditional lifecycle and the remaining four-family operation matrix. The new state
owner is the foundation for these paths, not evidence they are already implemented.

## C3 — condition dispatch and carrier logical negation

The condition resolver binds the existing scalar observation function set and the
shared carrier state/projection descriptors. Payload states delegate; non-payload
states return their declared boolean literal without extraction. Payload admission
is checked even for absent states. Admitted nested condition delegation is preserved,
while arithmetic/cast projection remains one layer. Logical negation composes the
condition plan with its existing result expression; plain unary admission is unchanged.

The public-only nullable<int64> proof covers absent, zero, positive and negative
conditions, negation, and branch selection, alongside arithmetic, explicit bool
casts and failure/lifetime tests. Measured success cases allocate zero. Scalar
condition variants, nullable/Result state plans, nested Result-of-nullable, aliases,
unsupported collection conditions and malformed policies are checked in metadata.
A synthetic true-state family confirms that truthiness does not imply permission
to bypass the payload requirement of an explicit bool cast.

**Required C follow-up:** the currently copy-required family recipes still use
owned extraction for payload conditions. Noncopyable observation must not acquire
a copy requirement; borrowed/scoped observation and large-payload copy costs need
proof before general family coverage is claimed. This is not waived by the scalar
zero-allocation measurement. Result/sentinel execution and type-formation coverage
also remain separate from physical plan composition.

Next for the first complete nullable path: state-aware equality/strict identity and
same-family conversion/reconstruction. The review gate and full C completion remain
open. Existing runtime generation still has 330 identical units; no core rebuild or
new runtime adapter is required by condition planning.

## C4 — numeric carrier state comparisons and checked ordering

Numeric ordinary equality/inequality and canonical numeric strict identity now
compose the shared state observations with existing payload operator plans. Whole
operands are snapshotted left-to-right before state comparison. Constant state
results skip extraction; two payloads use promotion or strict identity as selected
by the scalar owner. The registered result identity is retained, including synthetic
metadata aliases. There is no exact-carrier-family restriction or wrapper-pair
implementation generation. Numeric ordering reuses one-layer checked projection.

The public-only execution proof now generates nullable<int64> and nullable<float64>.
It checks present/absent equality, both negations, promoted equal values versus strict
numeric identity, matching absence across payload types, RHS effects despite absent
left, alias mutation after the left snapshot, and ordering extraction failure.
Successful measured scalar cases remain allocation-free. Both application-owned
specializations reuse their sealed publication without new implementation units.

Ordinary Result error equality is resolved in metadata. Strict Result-to-Result
comparison with matching error states rejects explicitly: the structured-error
identity public path remains required work, not a constant-true fallback. Other
remaining comparison work includes bool/string/boxed carrier domains and literal
null/sentinel routes; the latter must be audited against state-preserving strict
identity before the first complete nullable review. Numeric incompatible payloads
cannot be rescued by absence.

Next for that review: same-family nullable conversion/reconstruction and the literal
null comparison audit. Broader four-family state/error/lifetime tests, conditional
lifecycle, and noncopyable/large-payload observation remain mandatory C work. All 330
existing runtime generation plans are unchanged, so no runtime-core rebuild was needed.

## C5 — same-family nullable/Result scalar reconstruction

State metadata now supplies payload or non-payload reconstruction recipes. Payload
casts complete before destination construction; non-payload states preserve their
state data. The same generator path handles nullable absence and Result structured
errors, including preserved message/line/file after source mutation. Different
families, recursive peeling and implicit plain-value conversion-plus-wrapping reject.
Native exact success constructors remain available separately.

The public proof uses four application specializations (nullable/Result of int64 and
float64), verifies widening/round trips, absence/error preservation, and failed
narrowing without modifying the source or an existing destination. Both configurations
pass allocation/lifetime/publication checks. Successful scalar paths allocate zero;
preserving the test's non-SSO error message allocates twice, recorded separately.
Measured public storage matches native layout: nullable 16 bytes, Result 96 bytes
for each of the two tested payloads on this target. These are measurements, not
portable fixed-layout promises.

**Required C allocation follow-up:** native result already supports error_t&&,
while the generated Result recipe exposes only const error_value& construction.
The owned error snapshot is therefore copied again into the destination. Extend
and prove move-aware error construction with the remaining Result-family lifecycle
publication work, without moving from the original source. This is not waived by
the scalar zero-allocation proof or by postponing the associated core rebuild.

The next review prerequisite is the literal-null/sentinel comparison audit. Full
C still requires sentinel-family recipes, structured-error strict identity,
noncopyable/scoped observations, broader payload capabilities and the remaining
state/lifetime matrix. All 330 existing runtime generation plans remain unchanged;
this slice requires no runtime-core rebuild.

## C6 — nullable literal-null presence

The literal-null audit follows expression_evaluation.md and type_use_modifiers.md:
`=== null` and `!== null` observe nullable presence once without copying/extracting
the payload. The existing my-try presence preparation admits strict forms only;
Result-to-null is not nullable absence, including Result-of-nullable. Broad native
strict-identity overloads are not used to invent source admission.

Shared state metadata now declares literal matches; a global operator policy
selects the strict forms. One generator route handles both operand orders without
family-name exceptions or payload adapters. Public execution covers nullable<int64>
and nullable<string>, including a non-SSO payload and once-only evaluation. Ordinary
null equality and Result/null reject. Nested vector payload presence resolves
without requiring a vector payload equality API.

This closes the initial nullable literal-null route, not full carrier completion.
False/true sentinel handling remains with their pending family recipes; ownership
presence remains D. Strict structured-error identity, conditional lifecycle,
noncopyable/scoped observation, large-payload condition copy avoidance and the C5
move-aware error allocation follow-up remain required. The first complete carrier
review is still open. Existing runtime generation remains 330 identical units.

## C7 — move-aware Result error transport

The C5 allocation follow-up is implemented in the shared Result recipe: error
construction and assignment now accept owned error rvalues as well as const
references. Reconstruction consumes its owned snapshot, preserving the original
Result while avoiding a second copy. The generator's existing move-reference
passing and native-access substitution handle both application-owned and
runtime-published specializations without new generator logic.

The focused proof checks the application scalar Result and published
result<vector<string>>, moved-from error reuse, destination metadata lifetime,
and rejected no-error construction/assignment. Failed assignment preserves the
existing error. Public storage/layout is unchanged. This closes the extra error
copy debt, not conditional lifecycle or the wider carrier review.

## C8 — structured Result error identity

Matching Result error states now select a shared published strict error comparison
through metadata. The comparator checks presence/message/line/file; ordinary
Result error equality remains true regardless of that metadata. A generic owned
state-data accessor and a function-set policy extend the existing state-comparison
plan. Payload admission and whole-operand evaluation order remain unchanged.

The first public proof covers Result<int64>/Result<float64>, both strict forms,
ordinary equality contrast, each differing metadata field and aliased RHS mutation.
No error or Result type spelling is hard-coded in the resolver. Missing/mismatched
state accessors and comparator signatures reject. This closes the initial numeric
Result structured-error identity gap, not other payload domains or all carriers.

**State observation allocation follow-up:** the long-message Result comparison
uses whole operand copies and then owned error projections. Scoped state observation
must eventually avoid redundant projected copies while retaining the required
left snapshot against RHS mutation. Do not expose an unguarded interior reference
or remove operand snapshots merely to reduce the count. This joins the existing
noncopyable/scoped-observation work; direct error comparison borrows its operands.

## C9 — conditional lifecycle mechanism and physical proof

The generator now applies its existing declared-capability selection to lifecycle
entries as well as ordinary methods. Target assertions substantiate selected facts;
resolved native/disabled modes feed the existing probe and publication pipeline.
No separate trait-discovery or lifecycle-generation mechanism is introduced.

The first physical proof uses application optional storage backed by nullable,
with a move-only, non-default-constructible record and a copyable peer. The generated
API deletes copying and copy extraction/take for the first record, while permitting
absence, move, reset and destruction. It proves resource destruction counts,
self-move protection, no measured allocations, rejected copy compilation and sealed
specialization reuse. A false copyability declaration must fail the target probe
without overwriting the existing publication.

This is foundational C lifecycle progress, not production nullable completion.
The production recipes still impose blanket copying, and carrier descriptors still
require unconditional owned payload extraction. Next, migrate capability facts and
conditional extraction through the shared type/operation owner so absent-state
observation can remain valid when payload copying is unavailable. Capability
propagation through nested generated types and imported publications must be proved
before removing those blanket checks. Source formation rules remain separate from
these physical C++ capability facts. The first complete carrier review stays open.

## C10 — shared capability owner and production nullable migration

Capability selection/propagation and leaf/import checks now have one C++ owner
shared by physical generation and carrier planning. Conditional family guarantees
travel as resolved facts in publications. Imported contradictory claims reject;
this is not a second registry inferred from native type names.

Production nullable now selects copy/move lifecycle and payload APIs using those
facts. It supports physical move-only application records without requiring payload
default construction. Copy/extraction/take are absent when not guaranteed; reset,
absence and strict null presence remain available. Owned-extraction plans reject
before referencing an omitted method. This advances the C9 foundation into the real
recipe rather than retaining a test-only optional family.

The proof includes newly generated vector<int64> inside nullable, effective fact
propagation, independent copied values and sealed publication reuse. It also rejects
forged imported capabilities and false target copyability claims without overwriting
a publication. All 332 existing runtime generated code units remain byte-identical;
only the runtime capability inventory/fingerprint changes.

Remaining: migrate Result/sentinel lifecycle, finish source formation constraints,
expand payload domains and provide scoped observations that avoid copies. This
slice does not make noncopyable hash/vector operations generally usable or close
those recipes' blanket checks. Source generic copyable_value and result_payload
remain distinct from physical constructor/assignment guarantees. First complete
carrier review remains open.

## C11 — Result default and payload lifecycle

Result now uses the shared capability mechanism for default/copy/move lifecycle,
payload construction/assignment, owned extraction and take. The new physical
`default_storage` guarantee propagates independently of copy/move. Default success
requires a default-constructible payload; error construction and inspection do not.
No generator source changes or per-family generator branches were needed.

The physical lifecycle proof adds non-default/noncopyable Result payloads, both
success/error transition directions, rejected no-error assignment preserving a
live success payload, and Result-of-nullable default success containing absence.
Copyable payloads still support take and preserve its output/error contracts.
Deleted copy/default APIs reject compilation. All eight application specialization
units retain publication reuse and effective capability validation.

Physical fixture records test lifecycle mechanics; they are not new source-level
Result payload admissions. Existing source result_payload and transfer constraints
remain to be enforced in the broader formation work. Sentinel recipes, remaining
operator domains, scoped observations and the first complete carrier review remain
open. Existing runtime generated code stays byte-identical (332 units).

## C12 — metadata-owned static factory transport

The shared operation emitter now supports static class methods with the existing
parameter and native-result transport. This supplies named sentinel factories
without adding public native tag types or family-name branches. Static methods
cannot use a receiver, const qualification or constructor/free-function syntax.

The existing application nullable lifecycle proof exercises an absent factory with
a noncopyable, non-default-constructible payload. It constructs the wrapper directly
in owned storage, checks absence/reset, and retains the allocation/lifetime and
sealed reuse checks. This is factory infrastructure, not completion of either
sentinel recipe. Next add result_or_false/result_or_bool metadata and prove their
state/take contracts, including false payload versus false sentinel and true sentinel
without payload output assignment. The complete carrier review remains open.

## C13 — false/bool sentinel recipes

Both sentinel families now use the existing metadata operation, lifecycle and
carrier mechanisms. Default construction is false independently of payload default
construction. Named from_false/from_true factories preserve sentinel identities;
payload constructors/assignment remain distinct, including boolean false/true.
The take recipes snapshot once, copy only on payload success and preserve unrelated
outputs. A true bool sentinel returns true while leaving the payload output alone.

The eight-unit application proof covers integer/boolean payloads, nested nullable
success/absence, noncopyable non-default application records, moves/reset/destruction,
checked missing-payload access and sealed reuse. Physical record proofs do not grant
new source result_payload admission. Shared plan tests cover numeric arithmetic and
state comparisons across all four carriers, conditions, required scalar extraction,
empty-string sentinel exceptions and same-family reconstruction. They do not yet
execute the complete operator/conversion matrix as public C++ consumers.

Remaining C work includes boolean/string/boxed comparison domains, source formation
constraints, remaining mutation/lazy protocols, scoped observations and the full
carrier state/lifetime matrix. The first complete carrier review is still open.


## C14 — matching boolean/string/boxed carrier equality

The existing ordinary nonnumeric equality owner now covers matching boolean,
string, mixed and dynamic payloads. Its public names are value_equal/value_not_equal
instead of the early boxed-only names; normalized API identities and header paths
are retained. Numeric promotion keeps its existing numeric equality owner. Four
additional published implementations handle boolean/string equality and inequality.
Native operand conversions remain type metadata; no generator dispatch was added.

All four carrier families reuse this equality owner and their existing state policy.
Strict identity reuses its existing scalar/boxed rules, including distinct scalar
kind results after eager snapshots. Incompatible ordinary payload domains still
reject before observing state. This does not admit arbitrary boxed/plain ordinary
comparisons or string/boolean ordering.

A public-only consumer executes 676 boolean state comparisons across all four
families and all four equality/identity operators, plus string and checked boxed
cases, from 69 metadata-derived plan consumers. Short/scalar cases allocate zero.
Long-string equality currently allocates four times: two whole operand snapshots
and two owned payload extractions. Scoped observations should remove redundant
extraction copies while preserving snapshots and their evaluation semantics; retain
this cost alongside the existing Result error-identity observation debt.

Remaining C work: full ordering/special/mutation/lazy and conversion execution
coverage, source formation constraints and scoped observation. This slice is broader
comparison coverage, not closure of all possible payload pairs or the carrier review.


## C15 — sentinel operation execution and failure sequencing

The sentinel public proof now renders conditions, logical negation, checked bool/
int8/string casts, same-family int64-to-float64 reconstruction and seven numeric
operations directly from resolved plans. It executes both false and true sentinel
states as well as payloads. Bool/numeric casts require payloads, sentinel string
casts return empty strings and reconstruction preserves the sentinel category.

Ordered arithmetic/ordering checks verify failed left extraction skips later RHS
work, right extraction failure retains prior effects, and saved left payloads survive
mutation during RHS work. Checked narrowing preserves its source and old destination;
zero-divisor cases throw after both payloads are available. The existing state/take,
nested and noncopyable lifecycle cases and application publication reuse remain.
The proof generates ten application units and needs no runtime/generator rebuild.

This closes the initial sentinel operation execution gap. It does not imply every
width/payload pair, source formation rule, compound/update or lazy protocol is
covered. Those and scoped observations remain before the complete carrier review.

## C16 — source formation ownership audit and decision gate

[The formation-boundary audit](runtime_core_formation_boundary.md) identifies the
existing semantic owners and separates generic definition validity, application
formation, boundary/operation admission and physical realization. These cannot be
collapsed into C++ copy traits or one source-admitted flag. In particular, existing
Result tests distinguish a formed application from permission to transport its
payload; nullable result_payload and payload copyability are also independent.

The proposed scope-preserving route keeps source semantic checks in the compiler
and provides a prepared-fact boundary for physical demands. Extracting a shared C++
semantic formation model is a wider cross-owner refactor requiring confirmation.
The question has been raised; neither direction is silently implemented or credited
as complete. Scoped observations and other admitted core work can proceed meanwhile.


## C17 — carrier text concatenation through operand conversion metadata

Binary concatenation now admits the agreed canonical int/float/bool/string carrier
payloads and bounded plain text peers. A per-operation metadata policy selects
payload/peer admission and existing state-cast exceptions; the shared conversion
planner builds state branches while the ordinary operator's conversion owner formats
present payloads. No new family/type dispatch or carrier-pair implementations were
introduced. All 336 existing runtime implementation units remain unchanged.

The public proof covers all four boolean carrier families, float formatting, embedded
NUL/non-ASCII string bytes, cross-carrier concatenation, true payload versus true
sentinel, Result failures on either side and failed RHS preserving the destination.
Metadata checks reject boxed peers, uint64, noncanonical fixed-width carrier payloads,
nested carriers and containers, and validate the selected normalization target.

This is binary text completion for that bounded domain, not carrier .= completion.
Compound/update/lazy protocols, wider ordering, scoped observations and the pending
formation-boundary decision remain before the first complete carrier review.


## C18 — bounded carrier compound text replacement

The shared compound planner now accepts the four string-carrier destinations and
plain string with an admitted carrier RHS, using explicit `carrier_targets` metadata.
It reuses binary operand conversions, the destination's existing payload construction
recipe and the ordinary ordered compound stages. No family-pair code is generated.
Success replaces absence/sentinel states with a string payload and returns a snapshot
of the destination type; it does not preserve a non-value state through write-back.

The public proof now has nine application units. It covers all four destinations,
plain string/carrier RHS, aliased RHS mutation after saved left conversion, returned
snapshot independence, one-time member selection, failed left conversion, failed RHS
conversion and thrown RHS work after mutating the destination. Failed compound
execution preserves prior source effects without committing a calculated value.
All 336 runtime generated units remain unchanged; runtime-core needs no rebuild.

This completes the bounded binary/compound text plan paths exercised here, not full
frontend adoption or indexed extension. Numeric carrier compound/update and other
remaining protocols, scoped observations and the formation decision remain open.


## C19 — unary values and canonical numeric carrier updates

Unary plus/minus, canonical integer complement and canonical integer/float prefix/
postfix increments/decrements now use explicit per-operation carrier metadata. The
ordinary scalar owner retains calculation semantics. Updates perform checked owned
projection, mutate a local payload and use the same carrier payload-construction
helper as compound text write-back. Prefix/postfix return independent updated/old
carrier snapshots, respectively. Unavailable states fail before mutation.

The public proof covers 52 operation plans, 52 successful value/snapshot cases and
65 unavailable-state cases across all four families. It checks source preservation,
independent returned snapshots and retained structured error message/line/file.
Metadata rejects noncanonical/fixed-width, boxed, nested and incompatible payloads
and contradictory mutation/result policies. These tests use ordinary in-range
numbers; no new integer overflow contract is claimed.

Eight application units reuse the existing scalar publication. All 336 runtime
implementation units stay unchanged and no core rebuild is required. Numeric
compound/special-binary/lazy coverage, scoped observations and the pending formation
boundary still remain before the complete carrier review.

## C20 — canonical arithmetic carrier compounds

The shared ordered compound planner now admits `+=`, `-=`, `*=`, `/=` and integer
`%=` for canonical numeric payloads in all four carrier families. Payload admission
reuses the ordinary directional compound rules: float destinations accept int or
float RHS payloads; int destinations require int RHS payloads. Metadata explicitly
opts the operations into carrier processing without enumerating family pairs.

Plans preserve the destination snapshot, perform checked left extraction/conversion
before RHS evaluation, then checked RHS extraction/conversion, calculation and payload
reconstruction before committing. The result is an independent destination-typed
snapshot. Failed extraction or calculation performs no write-back; prior RHS effects
remain. This is not fixed-width, indexed, nested or boxed-carrier admission.

Special binary/compound groups, lazy protocols, wider ordering, scoped observations
and the source-formation decision remain open before the complete carrier review.

## C21 — canonical carrier bitwise and shift paths

The existing checked-payload binary and ordered compound mechanisms now cover
canonical integer `&`, `|`, `^`, `<<`, `>>` and their compound forms across all four
carrier families and canonical integer peers. This slice only changes metadata
admission; it introduces no generator branches, runtime overloads or implementation
units. Existing scalar owners retain valid-count behavior and checked shift errors.

Power remains distinct: `integer_power.md` explicitly admits carrier `**=` through
its compound extension, while the binary source section does not grant carrier
`**`. The next power slice must resolve the compound's scalar calculation without
accidentally admitting binary carrier power. This is an implementation separation,
not permission to widen the source contract. Lazy/selection protocols, wider ordering,
scoped observations and the formation-boundary decision also remain open.

## C22 — independently admitted carrier compound power

Canonical carrier `**=` now uses the ordinary checked integer-power calculation,
with left snapshot/extraction before RHS and payload reconstruction before commit.
Negative exponents and overflow do not write back; earlier RHS effects remain. The
returned value is an independent destination snapshot. Binary carrier `**` remains
rejected under the narrower binary contract in `integer_power.md`.

The operator owner now has one shared one-layer projection worker. Binary policies
and compound policies call that worker only after their own admission checks; a
compound no longer needs to publish its carrier binary counterpart. This repairs
that distinction without a power-name exception or a separate family mechanism.
All previously emitted runtime units remain unchanged.

Lazy/selection protocols, wider ordering, scoped observations and the source-formation
boundary still remain before the complete carrier review.

## C23 — string relational ordering through ordinary metadata

The four relational operations now select existing string byte-order behavior via
four reusable concrete bindings. A numeric-or-text ordering group preserves numeric
promotion and admits only string/string in its text branch; no string-to-number
comparison, arithmetic, boolean ordering or string spaceship is inferred. Checked
one-layer carrier projection reuses those bindings across the four families.

The implementation uses the existing ordered-pair function binding mechanism; there
are no generator code changes or carrier-pair runtime overloads. Missing payloads
fail at their ordered extraction point. String payloads are copied before later
operand effects so alias mutation cannot change the saved left bytes. Scoped borrowing
and removal of avoidable long-payload copies remain separate work.

Lazy/selection protocols, scoped observations and the pending source-formation
boundary remain open before the complete carrier review.

## C24 — logical/selection boundary audit and correction gate

The [logical/selection audit](runtime_core_logical_selection_boundary.md) found a
mismatch: earlier C3/C15 carrier negation reuses general condition truthiness, whereas
existing bool/numeric logical-operator contracts require guarded payload extraction.
Those negation tests are evidence of current implementation, not semantic completion.
Direct-condition and conversion proofs remain distinct. The user has been asked to
confirm preservation of guarded rules or an explicit contract revision; no behavior
is changed while that choice is pending.

The audit separates direct conditions, guarded logical operators, usable-value
coalescing and ternary/Elvis joins. It identifies an independent exact scalar
coalescing proof and records the noncopyable inline-record condition gap that scoped
observations must address. The first complete carrier review remains open.

## C25 — first exact scalar coalescing path

A dedicated selection owner now plans exact canonical int/float/bool/string
coalescing for plain/plain, admitted carrier/plain and homogeneous carrier pairs.
Nullable, structured Result and false-sentinel Result are admitted by metadata;
bool-sentinel Result rejects on either side before runtime selection. Zero, false
and empty string are usable payloads and never select the fallback merely because
they are falsey. Both branch types validate before lazy execution.

The plan observes usability, extracts only the selected branch and returns an owned
payload. If the selected fallback has no payload, one concrete public check delegates
to the same native error owner used by legacy coalescing. It preserves the documented
error code/component/operation; no template callback crosses the public boundary.
The generator has no family-name branch and renamed carrier metadata is tested.

This is a first scalar path, not general coalescing completion. Boxed, record,
ownership and other admitted joins, ternary/Elvis, scoped observation and the logical
operator decision remain open. The source-formation boundary is also still pending.

## C26 — exact scalar/carrier ternary and Elvis joins

The selection owner now resolves exact canonical scalar joins for full ternary and
Elvis. Plain pairs return the scalar; admitted same-carrier and carrier/payload pairs
in either order return the carrier. It validates both branch types and physical
copying before execution, without using copyability as general source eligibility.
Cross-family, widening and non-admitted payload joins remain rejected.

Full ternary converts its independent condition and evaluates only the selected
branch. It can return a selected absence/error/sentinel unchanged; no payload
extraction is needed merely to return that carrier. Elvis snapshots its left value
once, uses direct condition truthiness, and either returns that saved value or
lazily evaluates/converts the fallback. A true bool sentinel remains a true sentinel;
false/error/absent and falsey payloads take the fallback. Coalescing retains its
separate usable-value behavior.

Existing condition and payload-construction metadata provide all operations; no
runtime implementation units are added. This does not resolve the pending logical
operator question or implement general boxed/record/ownership joins. Scoped
observations, broader admitted selection domains and source formation remain open.

## C27 — constant observations without payload extraction

Condition metadata can now declare an explicit constant boolean observation for an
application leaf. The source/preparation owner supplies that fact; the generator
neither infers it from native traits nor grants general source template eligibility.
A direct observation still evaluates its source once. A carrier with that payload
can select the constant using its state predicate, without invoking owned extraction.
This implements the no-copy observation path required for nullable inline records.

The noncopyable record proof uses a production nullable recipe with deleted payload
and carrier copy constructors. It checks direct truth, nullable presence, repeated
observations without moves, reset/destruction once, source evaluation/throw behavior
and zero allocations. No payload pointer/reference escapes or is exposed at all.
State-dependent nested noncopyable observation still rejects rather than flattening;
long-string/error observation copy removal remains unfinished.

Prepared condition facts are supplied to the planner independently of the physical
storage package. Sealed reuse proves storage reuse, not source-fact inference from a
published native type. The pending formation-boundary decision is unchanged, as is
the logical-operator policy question.

## E1 checkpoint — conditional vector storage and companions

Vector now selects copy lifecycle, copying APIs and its copying cursor from payload
capabilities. Move-only application storage can append/move/remove/clear without
instantiating the cursor. Companion class selection uses the existing shared
capability mechanism; no vector-specific generator branch was added. Whole-vector
move transfers storage and does not require moving individual elements.

The release and sanitizer proof checks three owned records destroyed exactly once,
no element moves during container transfer, zero allocations after initial reserve,
copyable vector copy independence and cursor preservation across clear. It also
proves public-only compilation and sealed application specialization reuse.

This is physical storage coverage, not new source admission. E remains incomplete:
hash conditional lifecycle, source initialization, safe access/borrowing and cursor
protocol completion remain. Copy-only native types with explicitly deleted move
operations need a separate capability/native-cursor review (`std::copy_constructible`
is stricter than a copy trait). Indexed writes through current `set()` remain visible
to a live cursor, unlike structural clear preservation. E2 confirmed that this is
required by `specs/array_semantics.md`, Mutation during value iteration; no change is needed. No ownership/callback expansion occurred.

## E2 checkpoint — conditional typed-hash lifecycle

Hash now uses the shared conditional lifecycle, method and companion mechanism.
Noncopyable values omit copying access, find, copy lifecycle and cursors; moving
insertion and whole-container transfer remain available. Integer append and key
capability conditions compose with payload requirements. This slice adds no generator
branches and does not change native hash representation or source type admission.

The native tombstone implementation assigns `K{}` and `V{}` on removal. Public
removal now advertises that requirement through default/move capabilities rather
than emitting an unusable definition for non-default payloads. Removing that native
restriction remains E work; this checkpoint does not mark generic hash access complete.
Native `std::copyable` constraints are also stronger than copy traits for explicitly
nonmovable copy-only records, as noted for vector in E1. Application key policies,
initializers, safe access and failure guarantees still need their planned work.

The earlier indexed-write cursor concern is resolved: `specs/array_semantics.md`,
Mutation during value iteration, explicitly requires existing-value updates to remain
visible before structural preservation. No semantic or native change is needed.

## E3 checkpoint — consistent finished-cursor advancement

Vector and hash cursor `next()` now follows the existing mixed/dynamic public
protocol: it is a no-op when finished. Finished key/value reads still reject.
The metadata snippets own the boundary behavior; native traversal/consumption and
preservation semantics are unchanged. This closes the end-idempotent next debt,
not the broader container access/initialization backlog.

Tests cover empty cursors, normal exhaustion, preservation after the final value was
consumed, repeated end advancement without allocation, and rejected end reads.
They exercise both application-owned vector/hash specializations and the runtime's
published vector_string implementation. Ownership and callback expansion remain
behind the first-complete-carrier review gate.

## C28 — scoped callable payload condition observation

All four carrier recipes now expose a checked boolean payload-observation method
when the existing condition function set has a callable for their payload. The
method borrows an exact const payload reference internally and returns only boolean.
The global condition plan still selects outer states first and now uses a scoped
operation instead of owned extraction for those payload branches. Constant leaf
observations remain separate; no generic public callback/reference was introduced.

Generation tracks condition helpers as API demands, with ordinary publication and
reuse. The focused long-string proof covers all four families, absent/error/false/
true states, source evaluation once, invalid direct payload access and zero repeated
observation allocations. A nullable retained-error case is a physical runtime proof,
not a new source Result<error> permission. Release and ASan/UBSan/LSan pass; the
compiled sentinel regression also passes after adopting the scoped plan.

Nested state-dependent carrier observations still use owned extraction and may copy;
noncopyable nested observation remains a gap. Broader selection domains and the two
pending logical/source-formation decisions still prevent complete carrier sign-off.

## C29 — scoped nested condition composition

Nested conditions now compose through the existing scoped observation method and
state plan. A successful Result containing an absent nullable remains different from
outer failure, although both are false in a condition. Successful inner payloads and
boolean sentinels follow their own rules. This does not extend arithmetic/cast lifting.

The explicit `conditions.scoped_delegation: carrier_states` policy changes publication
identity, so an older generated nested type lacking the method cannot satisfy the
new demand. A negative sealed-package test checks that boundary. Unsupported payload
conditions still reject; removing the policy retains the earlier owned fallback.

The long-string proof now includes all three Result families containing nullable
strings. A separate native-only record fixture supplies an explicit constant condition
fact and proves Result<nullable<move-only record>> observation without copies, moves,
allocations or premature destruction. Fixture metadata is explicitly prepared and
sealed; it is not an implementation of frontend fact production or source eligibility.

This closes the demonstrated nested scoped-observation gap from C28. The pending
logical-policy/source-formation decisions, broader admitted selection domains and
full carrier acceptance review remain. Ownership/callback expansion is not unlocked.

## C30 — exact boxed ternary/Elvis selections

The existing exact conditional metadata policy now admits mixed/dynamic payloads,
covering plain and matching carrier joins through the same resolver. This follows
`conditional_expression_matrix.md`, including its boxed Result amendment. No
cross-family joins, boxed/scalar conversions or wrapper-payload widening were added.
No generator branch or runtime implementation was needed for the extension.

The separate boxed mode in the existing conditional proof covers all four carriers,
exact branch directions, saved-left Elvis, nonselected throwing branches, preserved
sentinel/failure states and successful boxed-null payloads. Container cases check
mixed value independence and rejected condition use, dynamic shared identity and
truthy empty containers. Existing scalar planning cases remain unchanged.

Boxed coalescing is still outstanding: direct boxed null versus a successful wrapper
containing boxed null have distinct usable-value rules, and must not inherit ternary
condition behavior. Inline-record/ownership and contextual joins also retain their
separate admission/proof work. The pending logical/source-formation decisions and
complete carrier review remain open.

## C31 — exact boxed coalescing and selected-value observations

Coalescing metadata now separates fallback usability from selected-value validity.
Exact mixed/dynamic plain pairs and admitted carrier-left/plain-boxed fallbacks use
this shared mechanism. A direct null box takes fallback; a successful carrier with
a null-box payload skips fallback; a selected null box remains a valid result.
No generated implementation for every operand pair or boxed-name resolver branch
was introduced. Existing canonical scalar coalescing retains its original rules.

The native coalesce helper previously treated direct dynamic null as always usable,
unlike mixed null. It now follows the documented boxed-null rule, with a compiled
native comparison proving fallback execution and valid selected null. Public proofs
also retain mixed value independence and dynamic container identity.

This is bounded exact-domain coverage. Broader mixed cross-type joins, record and
ownership coalescing retain their planned work; no blanket homogeneous boxed-wrapper
admission is claimed. result_or_bool remains rejected even in an unselected branch.
The pending logical/source-formation decisions and carrier review remain open.

## Active-goal progress — E4

Publish `count(mixed)` and `count(dynamic)` through the existing observation
function-set mechanism, returning the canonical source integer (`int64`). Both
bind the native count authority: tables report logical membership, including null
entries; null, bool, numeric and string boxes throw. This does not add keyed
protocols, wrapper forwarding, new countable families or compiler adoption.

The public-only observation test checks empty/populated tables, mixed COW versus
dynamic identity, unsupported kinds and repeated successful observations with zero
allocations after setup. Release and sanitizer evidence and focused compile costs
are recorded in `runtime/specs/public_api_boundary_results.md`. Metadata admission
and imported-definition reuse checks include the two new published functions.

## Active-goal progress — E5

The vector recipe now publishes all eight strict helper signatures: reserve,
capacity, resize, filled, clear, clear_keep_capacity, and both compact forms.
They use source `int64` counts/capacities through the existing native provider;
negative values throw `value_error` before mutation. Existing unsigned storage
methods remain available. Fill helpers require `copy_storage`; explicit-capacity
compact retains the native move requirement. These physical gates do not claim
source formation validation or frontend migration.

Extract the unchanged native helper definitions from the broad PHP support header
into `scpp/vector_helpers.hpp`. The PHP support header includes that owner, preserving
its API. The generated adapters now include the focused header, avoiding unrelated
ownership-header include-order dependencies revealed by standalone compilation.

Application-generated move-only, integer and throwing-copy record vectors plus
runtime-published vector_string exercise helper semantics, partial-construction
cleanup, nonallocating reserved operations and sealed application reuse. Source
argument evaluation/value snapshots remain compiler-owned; this slice exposes the
provider and does not add indexed destinations or alias capture lowering.

## Active-goal progress — metadata packaging consolidation

After E5, the API metadata entry point approached 200KB. Move families, function
sets and global operator rules into disjoint section files, assembled by the existing
package loader before C++ generation. The semantic object, API identity, generated
343 implementation units, public headers and publication manifest remain unchanged.
The largest authored metadata section is now about 83KB. Update direct test readers
to use the same loader and retain flat metadata fixtures.

This is a file-layout consolidation supporting the remaining backlog, not additional
runtime contract coverage. Section validation and sealed relocated publication tests
prove the packaging boundary; no native runtime implementation changed.

## Active-goal progress — E5 composition closure

Re-run nested/defaulted vector composition against current runtime artifacts. This
caught a test-recipe collision introduced when E5 added the result-only filled
factory: the copied sequence family exported the same parameters as vector_string
but a different return type. Give copied-family factories `${name}_filled` through
the existing metadata substitution. Production vector API and generator stay intact.

Extend the proof to nested filled construction, inner copy independence, defaulted
family factories, zero-allocation observations and zero-count construction. The test
can now stage a disposable sealed SDK from `--core`/`--tool`, allowing current-build
validation without republishing the compiler's pinned dependency package. Defaulted
and explicit identities still reuse one sealed application specialization.

## Active-goal progress — G1 wall-clock helpers

Publish the two registered microtime overloads through ordinary function metadata,
using concrete string/float64/boolean types and existing inline return transport.
Extract the unchanged native provider into a focused private core header; the PHP
string adapter includes it. Core catalog ownership and source alias rules remain
unchanged. This is not datetime-module migration or a monotonic timing API.

The bool-false path still throws logic_error. Numeric observations allocate nothing;
string formatting retains its native allocations, recorded separately. Public-only
execution, lifetime/sanitizer checks, relocated sealed reuse and focused caller
compile costs are recorded in the runtime boundary results.

## Active-goal progress — G2 CLI argument readers

Publish cli_argc/cli_argv/cli_args through ordinary function metadata, retaining
canonical int64 and mixed result types. The unchanged native reader implementation
moves into a focused private header included by the PHP process adapter. The existing
startup context setter remains private; no new source globals or setter are exposed.

A separate native test translation unit initializes context, while a public-only
caller verifies cross-TU observations, independent snapshots, empty/spaced/long
arguments, survival after context reset and input lifetime end, zero-allocation count
queries and native/public snapshot allocation parity. This does not promise concurrent
context mutation: startup initialization still precedes authored execution.

## Active-goal progress — G3 environment and shell string producers

Publish getenv/shell_exec and runtime-owned result_or_false<string> through ordinary
metadata. Both native compatibility and concrete public calls use one payload-factory
provider, preserving false for missing environment variables/pipe-open failure and
successful empty strings. Capture owns the pipe before reading or constructing the
payload, including exception cleanup. This is the existing core catalog surface,
not a wider process-module migration or a new exit-status policy.

Tests cover environment byte ownership, present empty versus absent, embedded-NUL
name rejection, output NUL preservation, nonzero exit status, null-pipe dispatch,
throwing payload construction and native/public allocation parity. Existing carrier
fixtures now respect the runtime-owned result_or_false<string> name and reuse it;
they must not force an application-owned duplicate of a published specialization.
The carrier semantic review gates remain open.

## Active-goal progress — G4 process-memory observations

Expose both forms of current/peak process-memory observation through metadata,
returning concrete int64. Public C++ names are process_memory_usage and
process_peak_memory_usage; existing native zero-argument scpp names cannot coexist
with concrete overloads differing only by return type. Source registry names remain
unchanged. Extract the unchanged byte-observation implementation into a focused
private header shared by native compatibility wrappers and public bindings.

Preserve process resident/peak byte reporting, the same policy for both bool values,
and zero when measurements are unavailable. This does not promise allocator-internal
accounting or introduce new memory counters. Tests check public forms, native/public
allocation parity, independent header use, sanitizers and sealed publication reuse.

## Active-goal progress — G5 constant declarations

Add generic constant sets to the existing C++ API emitter and publication records,
then publish the 19 core debug flags as concrete constexpr int64 values in
scpp::debug. Their values are checked against the core catalog. The public namespace
keeps native compatibility constants distinct. Public-only compile/link/run without
a runtime archive proves literal initialization; unrelated bool/int32 fixtures prove
that the generator has no debug-specific type/name branch.

Constant sets reuse request.apis, imported API equality, sealed ownership and ordinary
output files. Their source unit only validates header declarations, so it is not
included in the core aggregate. Runtime debug-call overloads are still outstanding;
this slice does not claim the complete debug surface or change source flag names.

## Active-goal progress — G6 scalar debug bindings

Publish all eight registered scalar debug signatures through two existing function-set
recipes. Public dbg_at calls forward explicit file/line context, with value-only and
labelled/explicit-flags forms. Native payload conversion preserves the formatter's
scalar type names. Source default flags still belong to catalog preparation; the
public labelled call can receive the published DBG_DEFAULT value.

The public-only proof compares native/public output and allocation counts across
int/float/bool/string, default and explicit options, binary string escaping and invalid
depth flags. This closes the registered scalar debug surface with the G5 constants;
unregistered gates and generic native object inspection are not silently added to
core source coverage. Source frontend adoption remains excluded.

## G7 — exact-mixed predicate bindings and empty hash factory

Use one function-set recipe for the three non-retaining exact-mixed predicates.
Public C++ names live in `scpp::predicates`: native `scpp::is_bool` already names
a concept, so placing the function there would collide. Source-language names
and exact-mixed admission remain unchanged. The bindings call existing public
kind observers; they do not extract, convert, lock or copy the payload.

The hash family now includes static `make()` through the existing static-method
recipe mechanism, delegating to native empty construction. This does not impose
payload copying or default construction and does not implement collection
initializers. The vector append inventory is corrected: the catalog registers an
instance member, already exposed, rather than a missing free helper.

Release and ASan/UBSan/LSan checks pass: predicates allocate nothing across the
publicly constructible kind matrix; ordinary and move-only hash factories allocate
nothing. Public headers compile independently, runtime publication reuses with zero
generated units, and application-owned hash specializations reuse from a sealed
package. Single-run observation compilation: 0.0564s public / 2.9348s native.
Weak/shared-table predicate fixtures remain outside this proof; existing kind
observers are unchanged. See the boundary results for timings and limits.

## E6 — runtime-checked mixed keys for boxed reads

Both boxed recipes now expose `get(const mixed&)`, in addition to their existing
integer/string overloads. They use the unchanged `null_index_read` owner: valid keys
on null/missing receivers return same-family null; invalid runtime key kinds throw
even on null, and scalar receivers throw. Results are owned values with mixed COW
or dynamic identity semantics. No heap-interior reference is exposed.

The mixed recipe uses the existing `self` parameter identity; spelling its own
canonical name there instead incorrectly requests a layout dependency on itself.
This slice needs no generator changes. Nested writes, captured destinations, keyed
probes/removal and source key-evaluation staging are still incomplete or compiler-owned.

The focused public-only proof checks valid and invalid runtime keys, unchanged entry
counts, nested/null reads, temporary receivers, result lifetime after owner reset,
mixed independence and dynamic alias identity. Warmed repeated scalar/missing reads
allocate nothing. Initial string-key interning is explicitly outside that measured
window and remains storage debt. Numeric-string behavior follows the existing build
profile: the tested core has PHP-target normalization disabled. This is not evidence
of PHP-target normalization execution or of weak-table public construction.

ASan/UBSan/LSan: the new public read test and existing mixed/dynamic regression
tests pass (three tests, 0.06s). Both public headers compile independently; generic
metadata generation/admission tests pass (1.20s). The sanitizer core compiled once.
No shared-state mutation or new synchronization was introduced.

## E7 — staged mixed-key direct writes

Expose `require_write_key(const mixed&)` as a public lowering-support function and
add mixed-key `set` methods to both boxed recipes. The function uses the existing
native key authority, without adding a source builtin or new key coercions. Callers
materialize the key, validate it, evaluate/convert the RHS, then call set. A direct
C++ `set(key_expression(), value_expression())` is not source evaluation staging.

The methods retain existing receiver validation, mixed null bootstrap, dynamic null
rejection and self-assignment snapshots. Key validation does not inspect receiver
storage: a valid key permits RHS evaluation before a storage failure. No captured
heap-interior reference or callback escapes across these stages. Source field-owner
retention and frontend expression sequencing remain compiler-owned. Nested writes,
keyed probes/removal and complete prepared access-plan metadata remain unfinished.

The public-only fixture checks invalid-key RHS suppression, failed RHS without
placeholder insertion, valid-key/null-dynamic failure after RHS effects, key value
snapshot and local-root replacement before commit, ordinary replacement, mixed COW,
dynamic identity, self-assignment and allocation-free repeated numeric replacement.
These manually staged C++ fixtures prove the API can express the protocol; they do
not claim source frontend adoption.

Validation: four boxed read/write and lifetime tests pass under ASan/UBSan with
leak detection (0.08s). Six release runtime/metadata checks pass (1.33s), independent
public header compilation and relocated sealed reuse pass. Repeated numeric writes
and key validation allocate zero times. Single-run public/native caller compilation:
0.0548s / 2.1175s. Core was rebuilt once per configuration. Broader nested/access
protocols and the carrier review gate remain open.

## E8 — single-step boxed probes and mixed-key removal

Publish native readiness and leaf-probe helpers for each boxed family, plus the
existing invalid-empty error path. Add mixed-key removal through the same existing
removal body as integer/string methods. The ordinary function-set and method
mechanisms suffice; no generator special cases or source builtins are added.

Readiness precedes key evaluation; reached key effects are followed by a fresh
lookup on current receiver storage. `isset` is null-sensitive, keyed `empty` requires
an existing string/collection leaf, and removal reports membership (including stored
null). All calls return owned boolean values and expose no interior pointer.

The public-only proof covers false payload versus missing/null, empty/long strings,
invalid keys, null/scalar roots, invalid-empty error codes, skipped key effects,
root replacement during a reached key, mixed COW/dynamic identity during removal,
missing-removal idempotence and zero repeated probe allocations. Nested prefix
re-resolution, typed-container probe publication and source frontend staging remain
unimplemented by this slice. Weak-table public construction is not claimed.

Validation: seven release runtime/metadata tests pass (1.38s); five boxed access and
lifetime tests pass with ASan/UBSan and leak detection (0.11s). Independent headers
and sealed relocation/reuse pass. Single-run probe caller compilation: 0.0564s
public / 2.1155s native. One core rebuild per configuration. The overall completion
goal and carrier review gate remain open.

## E9 — shared owning initialization backing

The input audit distinguishes source call-scoped `initializer_list<T>` from the
backend-owned temporary buffer. Publish only the owning `initializer_buffer<T>`
recipe in this slice: fixed-capacity construction, ordered copying append, complete-
input count and checked owned reads. The native inline/heap strategy is retained;
copy/move/default construction of the buffer is deleted. No borrowed view escapes.
The first source input contract requires copyable values; native-only move-only
requests are rejected by this recipe rather than advertised as source permission.

The four materialization proofs use string/int64/mixed/dynamic inputs, covering the
value domains needed for vector, hash and the two boxed factories under one mechanism.
This is backing-storage coverage, not factory or complete initialization coverage.
Private factory adaptation must next reconcile public stored elements with native
boxed factory inputs; it must remain one adaptation path rather than generator
type-name exceptions. Keyed entry records and duplicate-key factory validation,
source non-escape admission and actual factory dispatch remain open.

A metadata-only experiment confirmed that a mixed member factory taking a
`initializer_buffer<mixed>` creates a cycle in current signature/layout materialization.
A separately demanded function binding resolves the same dependencies without a
cycle. The experiment used placeholder factory bodies and proves graph resolution
only; it does not prove construction behavior or justify changing source factory
semantics. This evidence favors ordinary separate public function bindings before
considering a general declaration/layout graph refactor.

A syntax-only trait proof also confirms that native immutable initializer_entry is
copy-constructible but not copy-assignable. The current generator copy lifecycle and
copy_storage fact bundle both. Keyed entry transport must resolve that mismatch
truthfully before reuse in this buffer, without weakening source non-escape or
falsely assigning capabilities. Positional factory adaptation can proceed separately.

Validation: four demanded buffers pass release and ASan/UBSan/LSan lifetime/allocation
and sealed reuse proofs. Parallel generated storage compilation: 1.0313s release /
1.0806s sanitized; caller: 0.1409s / 0.1721s. Three metadata checks pass (1.49s). No
runtime-core rebuild was needed. These figures do not imply factory completion.

## E10 — common private positional factory bridge

Vector, integer-keyed hash, mixed and dynamic now share adapted positional input
population under the existing native initialization owner. The input view copies
the adapted value directly; a reference-returning representation adapter does not
first copy the public wrapper. Existing from_list paths retain identity adaptation.
The receiving factory still owns result construction and cleanup, and publishes a
result only after all entries are populated.

The focused native proof uses public concrete integer/string values and public boxed
representations, tests ordered invocation and empty outputs across all four factories,
compares long-string allocation counts (native 3 / adapted 3), and injects adaptation
failure after a populated prefix. Tracked payload lifetimes and sanitizer checks
cover cleanup. This is private bridge coverage, not newly published factory bindings
or frontend initialization. Representation adapters cannot defer source conversions
until after all entry effects.

Next: concrete factory publication through metadata, then keyed entry lifecycle and
duplicate-key construction. Borrowed source views and arbitrary user callbacks remain
behind their semantic non-escape requirements; no new callback boundary is exposed.

Validation: release and ASan/UBSan with leak detection pass; long-string factory
allocations match at 3 native / 3 adapted. Final focused build took 4.19s release /
8.35s sanitized. No core rebuild or public API publication change was required.

## E11 — positional factories through ordinary metadata recipes

Publish the existing native factory scopes as concrete classes with static from_list
methods. Vector and integer-keyed hash recipes are application-demanded; mixed and
dynamic scopes plus their buffers are runtime-owned. All use the E10 adapter and
E9 backing, with ordinary generator lifecycle, return transport and publication.
There are no type-name branches or special initialization emitter paths.

The public-only proof constructs all four families, preserves entry order, verifies
valid empty containers, rejects incomplete input, retains results beyond buffer
destruction, injects partial-copy failure and checks independent dynamic parents
while preserving nested dynamic identity and mixed value semantics. String-key hash
list helpers reject. The positional helper's integer-key default does not change
source hash defaults. Runtime buffers now reuse from the SDK in the older input
fixture, so its generated-unit assertion counts only application-owned demands.

Keyed entry lifecycle, keyed duplicate validation, source interface registration and
semantic non-escape proof remain unfinished. This completes a positional built-in
public path within the selected copyable input domain, not all data initialization.

Validation: release and ASan/UBSan/LSan public-only factory, allocation, lifetime
and sealed-reuse proofs pass. Four factory headers compile independently. Four
metadata checks pass (1.61s). Application compilation: 3.1937s release / 4.5106s
sanitized in parallel; caller: 0.1488s / 0.2064s. Core rebuilt once per configuration.
The older buffer fixture passes with runtime-owned buffer reuse.

## E12 — immutable entry lifecycle and truthful construction capability

Resolve the E9 native entry mismatch in the physical model: independent assignment
overrides in lifecycle metadata, and a construction-only capability. Ordinary
copy_storage implies copy_construct_storage through a generic validated implication
graph. The buffer requires construction, not assignment. No source capability is
inferred from those physical facts and the source-formation gate remains open.

The initializer_entry<V,K=string> recipe retains native const fields and returns
owned key/value reads. Copy/move constructors work; assignment and default
construction are deleted. Composition with initializer_buffer now preserves ordered
duplicate entries, owned values beyond input lifetime, failed append recovery and
partial cleanup without claiming native assignment. Existing shorthand lifecycle
recipes remain unchanged.

Tests cover implication validation (including transitive/diamond paths and cycle
rejection), independent/conditional assignment shape, actual immutable native probes,
public traits and lifetime, allocation-free scalar entries and sealed specialization
reuse. Assignment-only shape checks are emission tests, not permission to assign the
immutable native entry. Keyed factory execution and duplicate rejection remain next.

Validation: release and ASan/UBSan with leak detection pass, including independent
headers, immutable native traits, failed append recovery and sealed application reuse.
Scalar entries and small input buffers allocate zero times. Parallel generated storage
compilation took 1.2039s release / 1.0997s sanitized; public caller 0.1506s / 0.1814s.
Existing conditional, vector, hash and sentinel lifecycle metadata regressions pass.
Only the generator required rebuilding; existing runtime-core implementation output
was unchanged. These are single-run fixture costs, not full compiler build timings.

## E13 — shared keyed factory adaptation

The existing keyed population owner now accepts a representation adapter for a
complete immutable entry. Native identity paths delegate to it, retaining receiver
normalization and presence-based duplicate rejection. Typed hash, mixed and dynamic
factories own their temporary result until all entries succeed. The shared-hash
native path remains on the same identity population helper; no ownership publication
is added here. Source expressions/conversions still finish before materialization.

The focused native proof exercises public boxed entry transport, typed keys, legacy
entry calls, duplicate rejection with a null first payload, and tracked destruction
after a throwing adapter has populated a prefix. This is private bridge coverage;
public keyed recipes, sealed reuse, normalized-key matrices and keyed allocation
parity remain for the next publication slice. No generator family exceptions or
new source key rules were introduced.

Validation: focused release and ASan/UBSan/LSan runs pass. Existing positional
long-string allocation parity remains 3 native / 3 adapted; this is not a keyed
allocation measurement. The final release build took 5.53s with core compilation
already completed during the initial attempt; sanitized build took 46.37s including
core recompilation. These are build observations, not comparable isolated caller
costs. The initial compile caught an accidental shared-wrapper edit, corrected before
both successful test runs. Public keyed compile/allocation costs remain next.

## E14 — keyed factory publication through ordinary recipes

Publish mixed/dynamic keyed factories, immutable entries and their owning buffers in
runtime-core. Typed hash factories use the same application-demanded recipe with
string key default; integer keys remain explicitly selectable. Ordinary class/static
method, dependent-type and representation adaptation rules suffice; no generator
family branching was added. All factory parameters are owning buffers passed by const
reference, and result publication occurs only after successful native construction.

The public-only proof covers typed string/integer and boxed empty/nonempty factories,
incomplete typed input, duplicate typed/boxed keys (including null/false first values),
invalid boxed keys, escaped owned results, mixed value semantics and dynamic nested
identity with independent parent containers. It compares long-string typed-hash native
and public allocations (9/9), and injects failure at every measured allocation point
while checking the old destination survives. Empty typed outputs allocate zero times.

This fixture explicitly requires SCPP_LANGUAGE_TARGET_PHP=OFF: integer 10 and string
"10" remain distinct, as does "08". PHP-enabled normalization remains a separate
proof obligation; no new key semantics are declared. Source admission, non-escape
and compiler factory dispatch remain outside this native publication proof.

The earlier assignment-only entry shape fixture now omits imports: its synthetic
non-copyable entry recipe intentionally contradicts runtime entry buffers. Real
entry and factory tests continue validating actual published imports.

Validation: release and ASan/UBSan/LSan public-only runtime tests pass, as do independent
factory-header compilation and sealed runtime/application reuse with no duplicate
implementation units. Six focused metadata checks pass (the entry shape regression
was rerun after import isolation). Parallel application storage compilation took
4.6994s release / 6.0052s sanitized; public caller 0.1825s / 0.2841s. These are single-run
fixture timings. Runtime-core rebuilt once per configuration; generator code did not
change. The runtime now publishes 383 implementation units; this is not a coverage
percentage.

## E15 — keyed initialization configuration proof

Extend the E14 public fixture to derive key policy from the supplied core's CMake
configuration and use matching flags in layout probes, application implementations
and the native allocation reference. It tests both distinct integer/numeric-string
keys in non-PHP mode and normalized duplicate rejection in PHP mode. Leading-zero
"08" stays distinct from integer 8 in both modes. Typed hash iteration checks source
entry order, and incomplete boxed inputs reject before factory materialization.

Separate PHP-enabled release/sanitizer core builds keep the compiler's existing
non-PHP configuration unchanged. This closes a configuration-specific native public
proof; it does not implement source factory dispatch or broaden key admission.

Validation: non-PHP release and PHP release/ASan/UBSan/LSan public execution and sealed
reuse pass. Allocation parity remains 9 native / 9 public, with allocation-failure
cleanup checked. PHP generated application compilation took 4.8618s release / 6.3038s
sanitized; caller 0.2121s / 0.3584s. Non-PHP release rerun: 4.8334s / 0.2159s. These
single-run fixture timings exclude runtime generation and core builds. Each new PHP
core configuration built once; no runtime implementation or generator changes were
needed. Existing carrier logical-policy and prepared-fact ownership decisions remain
pending; this proof does not advance ownership/callback implementation past that gate.

## E16 — private homogeneous boxed-prefix resolution

The existing keyed-path owner gains a shared saved-key prefix resolver and boolean
readiness/final-probe operations. Each call starts at the current bound root and
uses only already-evaluated keys. Interior pointers remain inside the operation;
no root snapshot or pointer survives a source expression. Missing/invalid prefixes
stop before the later saved-key adapter, isset remains null-sensitive, and empty
retains its documented error path. Empty complete paths are rejected.

The adapter is internal synchronous representation access, not a source callback.
This slice supports homogeneous mixed/dynamic paths; heterogeneously typed paths,
owner retention for field roots, and mutation commits remain separate work. Next,
ordinary metadata bindings can accept a pre-lowered vector<mixed> of saved keys;
this does not require a new public borrowed cursor or a new storage family.

The native proof stages root/prefix mutation between observations, checks null/false
leaf behavior, missing-prefix short circuit, invalid receiver behavior and zero
allocations during repeated numeric-key observations. It establishes the private
bridge, not source-expression lowering or public publication.

Validation: focused release and ASan/UBSan with leak detection pass. Numeric saved-key
observations allocate zero times after setup. Build including runtime-core dependency
recompilation: 23.30s release / 47.64s sanitized, one build per configuration. These
are not isolated public caller measurements; publication and its compile/reuse proof
remain the next slice. No generator changes or source semantic changes were needed.

## E17 — public saved-key prefix observations

Two ordinary function sets publish keyed_prefix_ready and keyed_path_probe for mixed
and dynamic. Their shared vector<mixed> key sequence is runtime-owned and generated
through the existing vector recipe. The adapter borrows stored keys only inside the
native call, returns boolean, and does not expose traversal pointers or callbacks.
No emitter changes or separate path-storage family were needed.

The public-only proof stages one root binding and reached key effects, removes a
prefix during a later key expression, replaces a dynamic child through an alias,
skips keys for absent roots, and verifies saved key ownership. Numeric-key observations
of long-string leaves allocate zero times after setup. Null/false/empty leaves,
invalid key kinds, invalid receivers and empty complete paths retain their distinct
outcomes. These helpers support homogeneous boxed paths; typed heterogeneous paths,
field-owner retention, nested mutation and real frontend lowering remain unfinished.

Validation: release and ASan/UBSan/LSan public execution and relocated sealed reuse
pass, with zero new implementation units on import. Four public headers compile
independently; six metadata regressions pass (2.33s). Single-run nested-probe caller
compilation: 0.0559s public / 2.2876s native; direct-probe control 0.0558s / 2.1471s.
Core rebuilt once per configuration. The runtime publishes 388 implementation units;
that count is not a completion percentage. Existing carrier decisions remain pending.

## E18 — homogeneous boxed nested removal

The existing keyed-path owner now performs a complete non-mutating saved-key recheck
before mutable descent and final erasure. Missing/null parents or missing leaves
return without COW detachment; existing wrong-kind receivers and invalid keys throw.
A present null leaf is removed, and empty parents are retained. The internal saved-key
adapter contains no source callbacks; allocation failure during the later mutation
phase does not introduce a transaction guarantee.

One ordinary function set publishes mixed/dynamic keyed_path_erase using the same
runtime-owned vector<mixed> keys as prefix readiness/probes. Public tests cover
allocation-free no-ops with shared storage, mixed independent copies, dynamic aliases,
remaining key identity, null leaves, empty-parent retention, invalid paths and root
mutation between stages. Homogeneous boxed removal is now published; typed paths,
field owner retention and actual frontend lowering remain separate requirements.

Validation: release and ASan/UBSan/LSan removal and existing nested-probe tests pass;
relocated sealed publication reuses both APIs with zero new units. Both removal
headers compile independently. Single-run caller cost: 0.0541s public / 2.5304s native.
Core rebuilt once per configuration. Missing paths, missing leaves and null roots
allocate zero times in the focused proof. Runtime inventory: 390 implementation
units, not a coverage percentage. Carrier decisions and ownership/callback review
remain pending.

## E19 — homogeneous boxed nested store and append

The existing index-write-path owner now validates the full saved-key destination
before committing through its existing probe/descent/store/append operations. Mixed
may bootstrap missing/null tables; dynamic requires existing child containers. One
shared helper handles store and final append, with a root-self RHS snapshot only when
the same native object is supplied as both operands. This is value preservation, not
a full-root transaction; mutation-time allocation failure keeps the existing contract.

Ordinary function sets publish mixed/dynamic index_path_store/index_path_append over
the existing saved-key vector. Public tests cover three-level paths, invalid-key and
wrong-kind rejection without partial creation, dynamic missing-child rejection, mixed
COW and dynamic identity, next-integer append keys, staged invalid-key/RHS behavior,
RHS root replacement, mixed self snapshots and dynamic self identity (the intentional
cycle is removed before teardown). Repeated numeric replacements allocate zero times.

These are final commit helpers. Lowering still evaluates and validates each explicit
key before later keys/RHS, performs the value conversion, and only then calls them.
Typed heterogeneous paths, field-owner retention, compound existing-only writeback
and real source-level lowering remain open; no source admission is inferred here.

Validation: release and ASan/UBSan/LSan nested store/append, probe and removal tests
pass (three runtime checks per configuration). Relocated sealed import emits no new
units and links all four new APIs. Four headers compile independently. Repeated
numeric replacement allocates zero times after setup. Single-run four-operation
caller compilation: 0.0699s public / 2.6039s native. Core rebuilt once per configuration;
no generator changes were needed. Runtime inventory is 394 implementation units,
not a completion percentage.

## E20 — existing-only boxed snapshots and writeback

The existing-index owner gains saved-key read and store adapters for homogeneous
mixed/dynamic paths. Reads return owned values. Stores recheck every segment before
mutable descent and cannot recreate a missing leaf or parent. Ordinary and existing-
only writes share the same alias-safe commit-value helper; their validation policies
remain distinct. Metadata publishes four functions without emitter changes.

The public-only proof stages old-leaf read, RHS replacement, ordinary numeric compound
calculation/conversion and existing-only writeback. It covers deletion and wrong-kind
replacement failures, a valid replacement receiving the old-leaf result, missing-before-
RHS behavior, mixed copies/dynamic aliases, escaped long-string snapshots, existing
null versus missing, root-self RHS safety, invalid keys and empty paths. Numeric
read/writeback loops allocate zero times. Existing-only store is a transport step;
the compiler still owns compound operator planning, key/RHS evaluation and field-owner
retention. Heterogeneous typed paths and full source lowering remain open.

Validation: release and ASan/UBSan/LSan existing-only and ordinary-write regression
tests pass; relocated sealed reuse links all new APIs without new implementation
units. Four headers compile independently. Numeric read/writeback allocation count
is zero after setup. Single-run existing-only caller compile: 0.0570s public /
2.5540s native; ordinary-write control: 0.0640s / 2.5909s. Core rebuilt once per
configuration. Runtime inventory: 398 implementation units, not a coverage ratio.

## E21 — allocation-free stored-key identity

Close the B1c temporary-key debt inside the packed-table representation owner.
Const entry views compare validated stored key codes directly. The process-wide pool
already gives equal complete string bytes one stable tagged id, and integer/string
codes remain distinct under existing storage normalization. Strict table identity
retains entry-order and recursive-value comparison, without constructing mixed/string
key temporaries or exposing a borrowed public key. The existing key extraction path
shares the validated physical-key accessor.

Public identity tests now require zero allocations across repeated short/long/binary
and nested string-key table comparisons. They preserve embedded-NUL distinctions,
insertion-order sensitivity, packed/associative equivalence with tombstones, and
integer/string kind distinctions. Existing scalar/boxed pair matrices remain in the
same proof. This does not change key ranges, normalization, pool concurrency rules,
cycle handling or any public signature; it closes the measured key-boxing debt only.

Validation: release and ASan/UBSan/LSan strict-identity and heterogeneous-pair tests
pass, including zero allocations for the table-key cases. Sealed relocated publication
continues to reuse the unchanged APIs with no new implementation units. Single-run
boxed identity caller compile: 0.0566s public / 2.9023s native. Core rebuilt once per
configuration. No metadata generation or public ABI change was required.

## E22 — typed access composition audit

A current metadata-only probe confirms vector<vector<int64>> and
hash<vector<int64>,string> compose, but their public at() returns owned inner wrappers.
Chaining accessors therefore cannot implement nested writes in place. The new
[runtime_core_typed_access_boundary.md](runtime_core_typed_access_boundary.md)
records the missing physical access-protocol composition, current owners, a concrete
two-step demand and acceptance sequence. It avoids per-shape emitter cases and
copy-modify-replace as substitutes for actual in-place semantics.

No runtime or generator behavior changed and no compilation was needed. This audit
narrows the next action: resolve the already-pending prepared-fact ownership decision,
then implement one two-step physical path using supplied fixture facts. It does not
move source semantic validation into the API tool or count typed paths as complete.

## G8 — focused native value/type error owner

ValueError and TypeError now live in a focused private native header. The former
core/string_support.hpp owner includes it for compatibility; core shift validation
and process-string helpers include the focused definition directly. Names, inheritance,
constructors, messages and throw/catch behavior are unchanged. No template-bearing
native exception is added to the public root, and capture_runtime_exception retains
its existing runtime_error-only behavior.

This removes an unrelated string/numeric implementation dependency from core error
sites and prepares the remaining G diagnostic-inspection boundary. It does not publish
foreign error inspection or source Exception transport. Those signatures/policies
must remain distinct from the already agreed structured runtime_error capture API.

Validation: four focused release and ASan/UBSan/LSan checks pass (native error types,
public structured errors, special integers and process strings). A final cross-library
shift throw is caught as the same native ValueError in release and sanitizer runs.
Existing error/process allocation checks remain green; no new capture wrapper is
allocated or published. Single-run error-caller compile: 0.4435s focused header /
1.2257s former aggregate include. Dependency inspection confirms shift validation
includes neither core/string_support.hpp nor scpp/int_t.hpp. Core rebuilt once per
configuration; the final cross-library proof rebuilt only its small test caller.

## B3 — same-family boxed ordering (2026-10-28)

The global ordering group now publishes `<`, `<=`, `>` and `>=` for
mixed/mixed and dynamic/dynamic through the ordinary operator recipes. A private
relational adapter keeps existing scalar comparisons and delegates boxed operands
to native numeric-only ordering, preserving the original diagnostic operator.
No generator type-name branches or operand-pair expansion were added.

Non-goals: cross-family boxes, boxed/plain operands, carrier boxed payloads and
spaceship admission are unchanged. This closes the same-family ordering gap only;
it does not complete all remaining boxed or carrier contracts.

Evidence: `check_boxed_ordering_api.py` verifies eight generated APIs, published
reuse and rejected operand/carrier domains. `test_public_boxed_ordering.cpp`
passes in release and ASan/UBSan with leak detection: all int/float combinations,
canonical int64/float64 rounding, signed zero, infinity, NaN, rejection of stored
null/bool/string/table, unchanged operands and zero allocations on successful
numeric comparisons. Existing numeric and boxed arithmetic regressions also pass
in both configurations. Eight standalone public headers compile independently.
The sealed relocated publication test links and executes all eight APIs in release
and sanitizers with zero application-generated units.

Single-run caller compile cost (`measure_boxed_ordering_api.py`, clang++ C++23 -O2):
public **0.0543s**, native **3.1721s**. These are caller translation-unit timings,
not full runtime rebuild times. Core compiled once per configuration; a test-only
vexing-parse correction required a caller retry, not another core compilation.

## C32 — guarded canonical logical negation (2026-10-28)

The user confirmed existing guarded bool/numeric logical semantics and compiler-owned
source eligibility/formation/boundary validation with prepared facts consumed by the
API generator. No shared semantic-model extraction is authorized or implemented.

Logical negation now combines metadata-selected `carrier_unary` payload domains
(boolean/int64/float64) with the existing condition owner. Matching carrier payloads
use checked one-layer extraction and the ordinary scalar result expression; absent,
error and sentinel states throw. Present false/zero produces true. Direct condition
observation remains separate. The shared unary consumer test now honors calculation
result expressions, rather than assuming every operation is a bare function call.
No new runtime adapter or public template is needed; the publication manifest is
regenerated. Non-numeric/nested negation admission and lazy and/or are still review
work, not implicitly approved by preserving their pre-existing implementation.

Validation: metadata projection/unary/sentinel checks pass. The compiled unary proof
passes release and ASan/UBSan with leak detection: 64 plans, 76 successful value/
snapshot cases and 80 unavailable-state cases across all four families; successful
operations allocate zero and failure states are preserved. Both builds prove sealed
application specialization reuse. Broader projection and sentinel execution/reuse
fixtures pass release, keeping direct conditions, bool casts and logical negation
distinct. The projection fixture's newly throwing case was moved outside its
success-only allocation interval after that assertion correctly detected exception
allocation; the zero-allocation assertion was not weakened.

Single-run application storage/caller compile measurements: unary release
16.5505s/1.1221s; unary sanitizer 21.2525s/2.2131s; sentinel release
12.4949s/0.3023s; projection release 5.2790s/0.3122s. Some runs overlapped and these
are workload timings, not controlled before/after comparisons. Runtime archives
were reused without recompilation. Generator-only rebuilds covered the policy
change and a final duplicate-projection cleanup; metadata checks passed afterward.

Remaining C work: lazy logical execution plans, explicit non-numeric admission,
prepared-demand validation and broader admitted selection joins before carrier
sign-off and ownership/callback expansion.

## C33 — lazy canonical logical plans (2026-10-28)

The existing selection owner now resolves `logical_and`/`logical_or` through
metadata-defined operand groups and a short-circuit boolean. Both operand types
validate before a plan is returned. Each executed operand uses the ordinary
checked carrier projection followed by its scalar condition call. The RHS source,
projection and observation occur only after the left condition requires them.
The result is plain boolean; source evaluation remains compiler-owned. No eager
binary adapter, public callback template or family-specific generator switch was
introduced. Direct conditions retain their separate semantics.

The policy admits canonical bool/bool and numeric/numeric pairs, including int64/
float64, across plain values and all four carriers. It rejects bool/numeric mixing.
Boxed, string, fixed-width and nested-carrier logical admission remain explicit
review work; this slice is not a claim that all condition types support logical ops.

Evidence: `check_carrier_logical.py` checks all 450 operation/type pair requests
(250 admitted, 200 rejected), invalid RHS types and malformed metadata. Its public
execution fixture derives 36 consumers from plans, covering 132 success cases and
54 failure/skip groups: truth tables, mixed numeric and cross-carrier/plain pairs,
skipped throwing RHS effects, skipped unavailable RHS, required RHS failure and
left failure before RHS. Successful paths allocate zero. Release and ASan/UBSan
with leak detection pass; both prove sealed application specialization reuse with
no regenerated units. Four existing carrier/boxed coalescing and conditional
metadata regressions pass in 11.78s; these are metadata-only regression checks.

Single-run application storage/caller compile: release 13.9585s/1.1164s; sanitizer
19.3550s/2.3235s. Runs overlapped, so these are workload observations rather than
controlled comparative measurements. The runtime archives were reused; only the
C++ API generator and application proof units were compiled. Runtime publication
metadata was regenerated through the existing tool.

Next: audit non-numeric logical admission and implement prepared-demand consumption
under the accepted compiler-owned source-validation boundary. Carrier sign-off,
ownership and callback expansion remain incomplete.

## C34 — non-numeric audit and boxed logical paths (2026-10-28)

Read-only inspection of the existing semantic owners confirms boxed AND/OR admit
the same boxed family or plain bool/int/float peers. Cross-boxed families and
boxed/carrier combinations reject. Evidence: `Boxed_Operators::decide_logical` and
`Guarded_Operators::try_decide`; runtime condition semantics remain owned by
`operators/conditional/condition_truthiness.hpp`, under `dynamic_types.md` and the
conditional matrix. Historical compiler execution reports were not rerun here.

The shared selection metadata now has optional `plain_operand_groups`, used only
when both projections are identity. This expresses the boxed groups without
accidentally granting boxed/carrier combinations or adding generator type-name
exceptions. Existing canonical checked carrier groups are unchanged. Public
consumers reuse the existing condition functions; no runtime adapter or archive
recompile is required.

`check_boxed_logical.py` derives 28 public-only consumers from returned plans.
Release and ASan/UBSan with leak detection pass: boxed truth tables across null,
bool, int, float and string; all plain peer directions; skipped/reached invalid
mixed tables; and dynamic object truth (including an empty constructed object).
Successful paths, including long strings, allocate zero. Caller compile single
runs: 0.2195s release / 0.3635s sanitizer; these include the fixture and are not
public/native comparisons. Canonical logical metadata regression passes, with
added cross-boxed and boxed/carrier rejection checks. Publication metadata is
regenerated; compiled consumer checks use public headers and the existing archive.

The audit also distinguishes unary domains: nullable-string negation is an existing
condition route; guarded Result/string negation is unresolved in the old compiler's
string slice. Ordinary unary preparation admits explicit condition-negation roles
(records, ownership, callable, nullable, boxed and error), rather than every type
with a condition helper. The physical generator's broad fallback must not be used
as evidence of source admission. Prepared semantic facts must carry that operation
permission; narrowing or broadening source semantics is not inferred here.

Next work remains the prepared-demand consumer and its source/physical distinction,
then the remaining selection/ownership/callback review. Non-numeric logical source
admission is not marked universally complete by this boxed slice.

## C35 — first prepared semantic request consumer (2026-10-28)

The C++ API tool now distinguishes native physical requests from source-mode
requests. The first source path is `resolve_logical`; other source actions reject
explicitly. A prepared record binds the exact action/request to compiler provenance,
admission evidence, source/physical type bindings and source argument mappings.
Explicit semantic requirements must match supplied facts with evidence. Physical
imports and native storage traits never fill a missing semantic fact. The existing
physical planner still validates the operation after the prepared boundary passes.
Returned plans retain the preparation record; physical publication manifests do not
store a reusable source-admission flag.

The compiler remains responsible for the truth/completeness of its requirements,
source identities and argument semantics. This consumer checks binding consistency
and required evidence, not arbitrary source programs or authenticity of JSON.
The test producer is explicitly a fixture. No frontend semantic extraction,
frontend producer, new source semantics or general source-formation completion is
claimed. Native calls without mode remain backward-compatible physical-only calls.

Validation: `check_prepared_requests.py` passes release and instrumented generator
ASan/UBSan with leak detection. It covers exact-request binding, canonical physical
identity and aliases, provenance, missing/duplicate evidence, unknown subjects,
explicit native/source separation, unsupported source actions, and physical admission
failure despite a supplied source attestation. Missing copyable_value/result_payload
facts reject even with imported physical capabilities; native copy_storage does not
substitute. The compiled logical fixture in `--prepared` mode passes release and
ASan/UBSan/LSan: 36 consumers, 132 success cases, 54 failure/skip groups, zero success
allocations and sealed application specialization reuse. Runtime archives are reused.

Single-run application storage/caller compile: release 14.4375s/1.0981s; sanitizer
17.9429s/2.2202s. Runs overlapped, so these are workload observations. Five-process
planning means including JSON/startup were native/prepared 0.0355s/0.0343s release
and 0.3160s/0.3206s instrumented; the small differences are not a speedup claim.

Remaining: adopt this same boundary in other operation and generation requests,
validate request-specific physical bindings and prepared dependencies there, then
review the complete carrier path before ownership/callback expansion. Source-level
producer integration remains excluded from this goal.

## C36 — prepared facts across operation planners (2026-10-28)

The same prepared validator now covers all current operation planning actions:
conditions, conversions, unary/binary operators, compounds, coalescing, ternary,
Elvis and logical plans. Binding slots follow the existing request protocols;
there is no new source type/capability registry or family-name branch. Exact
request/provenance checking and per-slot required evidence remain centralized.
Both source and destination/branch facts are required when declared by the producer.
Application formation does not satisfy a separately required boundary fact.

`check_prepared_requests.py` passes release and ASan/UBSan with leak detection,
covering nine additional request shapes, every type slot's missing-fact case,
extra bindings, stale requests, literal-null identity, nested condition observation,
casts, compounds and lazy selections. For every positive case the prepared plan,
after removing its evidence record, equals the native physical plan exactly.
The C35 import/non-bypass/provenance tests remain green. The full prepared logical
metadata regression also passes; it is not a fresh compiled execution claim.

No runtime behavior or generated consumer body changed, so runtime archives and
consumer execution builds were not repeated. C35's compiled allocation/lifetime/
publication proofs remain their recorded evidence. Only prepared.cpp was rebuilt
in each generator configuration: Ninja recorded 1.301s release / 2.494s sanitizer.
Five-process logical planning means including startup/JSON were 0.0344s native /
0.0363s prepared in release and 0.3139s / 0.3183s instrumented; not a runtime cost.

Source-mode storage generation and dependency demands remain the next adoption
step. Actual frontend fact production and complete compiler integration remain
excluded. The carrier review is still open, not marked complete by protocol coverage.

## C37 — prepared storage generation and dependencies (2026-10-28)

Source-mode `generate` now validates root-index bindings and explicitly declared
source dependencies through the same prepared validator before materialization.
Source argument references must resolve to declared source bindings; conflicting
bindings reject. Requirements for dependency subjects need explicit evidence even
when every physical type is imported. Private recipe/layout dependencies are not
classified as source declarations. Source argument semantics and completeness of
requirements remain compiler-owned; the consumer does not infer them from native
parameter names or storage traits.

The existing Python driver accepts an explicit source envelope around its ordinary
request and forwards it through every layout-probe pass. Native request files remain
unchanged. Physical manifests contain no source-admission flag. New source requests
must validate again against their own evidence even when a sealed specialization
can be reused without emitting units.

`check_prepared_generation.py` passes native-plan identity, missing binding/evidence,
conflicting source identity, duplicate identical roots and imported-type tests.
The validator cases also pass using the ASan/UBSan generator with leak detection.
The end-to-end driver generates two nested-vector units, compiles/executes public-only
consumers in release and ASan/UBSan/LSan, seals the application and reuses it with zero
new units. Removing required facts rejects before the destination is published.
Owned extraction survives parent destruction and remains independent after mutation;
empty construction and repeated scalar reads allocate zero. This proves the fixture
path, not arbitrary source-template formation or full frontend production.

Single-run application storage/caller compile: release 3.2566s/0.0917s; sanitizer
5.3723s/0.1055s. Runs overlapped; no controlled speedup is claimed. Runtime archives
were reused. The first release run proved execution/reuse but its negative-test
handler expected the wrong Python exception class; correcting the handler and
rerunning verified the driver's existing CalledProcessError rejection protocol.
All operation prepared-request regression checks also pass.

Next: combine prepared carrier storage with prepared operation execution and review
the first complete carrier path. Broader selection domains, ownership and callbacks
remain incomplete; no goal-completion claim is made.

## C38 — combined prepared carrier path review (2026-10-28)

The logical fixture's `--prepared` mode now uses prepared source storage generation
as well as prepared operation planning. Twelve canonical carrier specializations
carry explicit primitive source-dependency facts through layout probes, public-only
compilation/execution, sealed publication and source-mode reuse. Missing facts on
reuse reject before output is installed; source permission is not taken from the
physical manifest.

Release and ASan/UBSan/LSan pass: 36 consumers, 132 successful value/effect cases,
54 failure/skip groups, zero successful-path allocations and no regenerated units
on sealed reuse. Runtime archives were reused. Application storage/caller compilation
was 13.7637s/0.9627s release and 18.5086s/2.0954s sanitizer (overlapping single runs).

The detailed review in runtime_core_carrier_review.md now passes the **first-path**
checkpoint for canonical bool/numeric carriers, allowing the planned D application-
record ownership prototype. This is not completion of C or a change to the overall
scope. Broader joins, non-numeric/source-role admission, ownership/callback integration
and all other unfinished batches remain backlog. Actual frontend fact production
remains outside this public-runtime goal.


## D1 — application-record ownership and nonthrowing lifecycle (2026-10-28)

Shared, unique and weak recipes now use the same metadata generation pipeline for
one application-defined, noncopyable and nonmovable record. Shared copies retain
ownership without copying the target; unique moves transfer ownership; explicit
weak lock yields a retained or empty shared handle. Default construction/reset and
capability-selected default allocation are exposed. A weak-only physical demand
materializes its shared signature dependency. Source-mode demands require prepared
application/target facts even on sealed import reuse; fixtures are not a frontend
semantic producer.

The compiled prototype exposed a missing public exception specification. Lifecycle
metadata now accepts an explicit `noexcept` operation list; layout probes and emitted
implementation assertions verify each native guarantee. This is a shared lifecycle
mechanism, with no ownership-name branch. Disabled operations remain disabled.
Existing recipes without annotations retain their output. A deliberately false
nonthrowing default-construction promise is rejected by the compiled proof.

Release and ASan/UBSan/LSan pass: default-empty construction, shared copying and
assignment, weak derivation/successful and expired locking, unique move construction
and replacement, destruction exactly once, and throwing constructor cleanup. Both
shared and unique creation allocate once, matching the native reference; empty,
copy, lock and transfer paths allocate zero. The native reference uses an external
observer to prevent allocation elision (the initial fixture permitted it and failed
the parity assertion). Public headers compile independently with no private runtime
include root. Application publication reuses with zero new units, and missing source
facts reject before output installation.

Five focused generator regressions pass (ownership, vector/conditional lifecycle,
prepared operation requests and prepared storage). Single-run application storage
plus record fixture / public caller compilation: release 3.6346s / 0.1435s; sanitizer
3.9442s / 0.1636s. Runs overlapped, not controlled performance comparisons. Generator
translation-unit rebuilds took 6.173s release / 20.269s instrumented. Runtime archives
were reused; regeneration changed only publication metadata identity, with the same
406 runtime implementation units.

D remains incomplete: no raw or interior-pointer API, retained member-access protocol,
ancestry/cast/identity support, value-storage migration or source Exception transport
is claimed by this slice. The next step is the retained-access/identity boundary for
these same application targets, preserving the compiler-owned eligibility decision.

## D2 — checked shared retention and same-target identity (2026-10-28)

The shared recipe adds `retain_present()` and same-target `identical(other)` through
ordinary metadata bodies and native transport. Retention takes an owned shared
snapshot and checks presence with the existing native dereference authority. It
exposes no raw/interior reference and never copies the noncopyable target. Identity
calls the existing strict-identity helper; no separate pointer-identity semantics
were invented. Unique and weak gain neither operation.

Contract audit: instance_methods_v0_2.md requires receiver selection/check before
arguments and retention through invocation/unwinding. Unique receivers remain a
source lifetime gate. expression_evaluation.md permits shared strict identity with
same/compatible target views, but not ordinary equality or unique/weak identity.
This slice covers the same-target public primitive; ancestry-aware demand/planning,
typed member access and complete prepared-operation integration remain unfinished.
Arbitrary C++ call syntax does not acquire the source evaluation-order guarantee.

The expanded ownership fixture passes release and ASan/UBSan/LSan: absent receivers
reject before argument effects, original-handle reset cannot destroy the retained
receiver, argument exceptions release exactly once, and strict identity preserves
absence, distinct objects and left-before-right snapshots across replacement. All
previous transfer/allocation/publication tests remain green. Successful retention
and identity allocate zero; sealed reuse emits no new units. Source eligibility
checks still reject missing target facts even when importing generated handles.

Application storage plus fixture / public caller compile: 5.9874s / 0.1599s release,
6.1607s / 0.1989s sanitizer (overlapping single runs, not a controlled comparison).
No generator or runtime archive rebuild was required. The existing broad native
strict-identity header is now included privately by the shared implementation; its
compile cost is visible here. Consider a focused native ownership-identity header
when adding ancestry support, preserving one semantic authority and its existing
multiple/virtual-inheritance tests. Public callers still see only concrete headers.

## D3 — demanded functions and shared ancestry (2026-10-28)

Ancestry exposed the next physical-generation need: operations parameterized by two
application types, without making a fake storage class or enumerating all pairs.
`function_families` now reuses the existing parameter/default/structural-name owner,
function-set substitution pass, operation emitter and publication protocol. Only
requested `apis` demands emit units. The function naming registry is owned once per
generation request; it is separate from the storage-type registry. Defaulted and
explicit demands canonicalize identically, and function identities cannot be used
as storage types. This is no new semantic compiler or ownership-specific emitter.

Source generation requires bindings for every function argument (including defaults)
and validates declared formation/ancestry evidence before imported reuse. The
fixture supplies compiler facts; actual frontend producer integration is excluded.

Initial recipes provide shared upcast via the existing native shared-boundary
constructor and compatible shared strict identity via the existing native authority.
An explicit native compatibility assertion prevents identity's unrelated-type
fallback. Release and ASan/UBSan/LSan proofs pass: same-target/defaulted functions,
multiple/virtual-inheritance view adjustment, empty handles, operand snapshots,
retention after original handles reset, zero view-operation allocations, and sealed
reuse with zero emitted units. Unrelated identity and reverse/downcast construction
fail compilation; missing ancestry facts reject before publication even on imports.
Conflicting API definitions, bad defaults/arity and function-as-storage reject.
Six focused generator regression tests also pass.

The proof emits three concrete handle units and six demanded API units, rather than
all target combinations. Single-run sequential storage/API compilation: 22.8248s
release / 24.1012s sanitizer; public caller 0.1177s / 0.1382s. Runs overlapped; these
are not controlled speedup figures. Runtime archives remain reused and runtime-owned
inventory stays at 406 units. The broad private strict-identity header dominates
repeated identity compilation and remains a focused dependency improvement target.

Remaining D work includes checked downcasts, other admitted ownership transports,
typed member access, identity/conversion planner integration, value storage and
source Exception. Neither these physical APIs nor compiler-provided fixture facts
claim complete source operator adoption or full ownership-family completion.


## D4 — checked object casts and weak/unique transport (2026-10-28)

Four function-family recipes bind existing native authorities without generator
changes: shared_object_cast/shared_object_is use object_cast.hpp, weak_upcast uses
the native weak converting constructor, and unique_upcast uses its explicit native
converting move constructor. The inheritance_v0_2.md contract owns ancestry,
polymorphism, finality, virtual destruction and permitted transfer contexts. These
source facts remain compiler-owned; physical API existence does not relax them.

Release and ASan/UBSan/LSan pass with a noncopyable polymorphic application model:
checked narrowing and interface cross-casts preserve shared identity/control blocks;
empty/mismatched checks return false; required casts throw invalid_object_cast.
Failures preserve the assigned destination and source. Retained interface views
keep their original object alive. Weak upcasts allocate/retain nothing, work for
expired observers and acquire ownership only on lock. Unique upcasts empty the
source, destroy replaced destinations and eventually invoke the derived destructor
exactly once through a virtual base destructor. Successful operations allocate zero.

Negative native compiles reject nonvirtual unique targets, nonpolymorphic narrowing,
weak downcasts and a named unique argument without explicit move. Prepared metadata
checks require declared ancestry, polymorphism, virtual destruction and transfer
facts even for imported APIs. Sealed publication reuse emits zero units. Four focused
generator regressions pass. The initial caller compilation caught a fixture's implicit
public-boolean-to-native-bool assignment; it now uses the required explicit bridge.

The fixture emits nine handle and nine operation units. Compile wall times with
**eight jobs**, run sequentially by configuration: release 5.6593s storage/APIs and
0.1585s public caller; sanitizer 6.3003s and 0.1935s. These parallel timings are not a
speedup comparison with D3's sequential compilation. No generator or runtime archive
recompilation was needed; the runtime-owned inventory remains 406 units.

Ownership completion remains open. Next are typed member/access transport and
operation/selection integration, then value storage, shared-self and source Exception
requirements. Interface/finality source admission is represented by prepared fixture
facts here, not by a newly implemented semantic frontend.

## D5 — ownership condition and negation planning (2026-10-28)

Condition metadata now selects an unconditional const boolean method on a physical
family's primary class. Shared and unique select handle presence; weak has no entry.
The planner emits evaluate-once, const-reference `member_call` observations, and
unary logical-not delegates through that same owner outside the guarded scalar
carrier domain. Family capability checks and method-signature/owner validation are
reused; there are no hard-coded shared/unique branches. A renamed recipe proof
exercises the same path. No public bool cast or logical binary admission was added.

Prepared condition/negation requests pass for shared/unique and reject missing
observation facts. Weak condition/negation, explicit bool casts and logical binary
use reject. Release and ASan/UBSan/LSan consumers observe present/absent and moved/reset
handles, evaluate operand expressions once, and allocate zero on success. The existing
lifetime/destruction/publication tests remain green. Seven focused condition,
carrier-logical/unary/selection and prepared-request regressions pass.

The scoped carrier emitter also consumes member observations. A supplemental **native
physical** result<unique<record>> fixture proves checked observation of a noncopyable
payload without copying, moving or escaping its reference; success/empty-handle/error
states behave as planned. This adds no blanket source Result-of-ownership admission.
Prepared source generation remains the original handle roots; the additional physical
composition is explicitly generated in native mode and carries no semantic permission.

Single-run storage plus record fixture / public caller compilation: 7.0149s / 0.1682s
release, 7.5573s / 0.2468s sanitizer (overlapping sequential-TU runs). Runtime archives
were reused; regeneration changed metadata identity, with 406 runtime-owned units.
The stricter validation correctly rejected the old negative noexcept fixture after it
removed a method but retained its observer registration; the fixture now removes both
before testing the intended false native exception promise.

Presence/null operators beyond condition negation, ownership selections and remaining
conversion/identity planner integration still require their own coverage. Typed member
access, value storage, shared-self and source Exception remain outstanding.


## D6 — focused native shared-identity boundary (2026-10-28)

The compatible shared-view identity overload now lives in
operators/identity/shared_identity.hpp. The broad strict-identity header includes
that owner; both same-target shared methods and demanded ancestry identity APIs use
the focused include. The overload body/signature is unchanged, with one definition.
This closes the specific broad-identity include cost noted in D2/D3; it does not
claim that all private ownership headers are now minimal.

A standalone native header proof verifies multiple/virtual base adjustment and
absence. Dependency inspection excludes aggregate operators, mixed/hash definitions
and the full identity header. The existing broad identity fixture also passes,
including nullable normalization, unrelated-type fallback and scalar overloads.
Both focused and compatibility paths pass release and ASan/UBSan/LSan execution.
Generated ancestry APIs likewise pass both configurations, zero-allocation operations,
negative admission/native compile cases and sealed reuse. Four focused CTest checks
pass. No semantic admission or public-header contract changed.

Same native caller, different includes, single runs: release full 3.2770s vs focused
0.9697s; sanitizer full 3.0471s vs focused 1.0188s. Object sizes remain about 13.3KB
release / 56.8KB instrumented (filename/assertion metadata differs). These are focused
compile observations, not runtime speed measurements. The release include comparison
overlapped the sanitizer runtime rebuild.

The nine-unit ancestry fixture now compiles sequentially in 8.4011s release /
9.3847s sanitizer, with public callers 0.1074s / 0.1402s. D3 recorded 22.8248s /
24.1012s for those generated units before the focused include; these are single-run
historical comparisons, not a controlled statistical benchmark. The two fixture runs
overlapped. Runtime core was rebuilt once per configuration: its translation unit
recorded 21.648s release / 49.641s sanitizer. Publication metadata was regenerated;
there remain 406 runtime-owned implementation units.

Functional ownership/access/callback backlog remains open. This slice improves the
shared semantic dependency boundary rather than marking additional source contracts
complete.
