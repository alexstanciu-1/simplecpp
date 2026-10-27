# Runtime-core public API completion inventory and plan
Doc Status: planning

Checkpoint: 2026-10-27, implementation baseline `6a1cb4af2`.
This is the current completion inventory under [the runtime API migration plan](runtime_public_api.md),
not a new semantic registry. No implementation is authorized merely by a row here.

## Current implementation status

Updated 2026-10-27. This summary supersedes unfinished-work statements in the
chronological checkpoint notes below; those notes retain the evidence and scope of
each slice. The original inventory is a baseline, not a current completion count.

| Batch | Current status | Remaining work |
| --- | --- | --- |
| A1 | Complete for the agreed ordinary numeric domain | Specialized and boxed extensions remain in B2/E, not implied by A1. |
| A2 | Complete for the agreed checked scalar conversions | Carrier conversion is C; ownership casts are D. |
| B1 | Complete for the agreed scalar-facing truth/text/identity/observation scope | Carrier forms remain C; boxed arithmetic and access completion are not B1 credit. |
| B2 | Binary canonical/boxed bitwise and checked shifts, canonical power, numeric spaceship, numeric unary, scalar logical negation, prefix/postfix updates, canonical numeric compound plans and homogeneous boxed arithmetic/compounds implemented | Text compounds and admitted numeric scalar peers; carrier variants remain C. |
| C | Policy agreed; completion pending | Four carrier families, state-preserving operations/conversions and conditional lifecycle. |
| D | Pending | Ownership, application targets and source Exception transport gaps. |
| E | Existing vector/hash/boxed foundations; incomplete | Capability/access/initialization/cursor gaps and identity key-allocation debt. |
| F | Pending | Callable transport and registered collection algorithms. |
| G | Pending | Remaining core helpers and requirement-by-requirement closure audit. |

Full frontend adoption and module migration are excluded. The first complete carrier
path remains a review gate before expanding ownership/callback implementation.
No completion percentage is inferred from function, header or checkpoint counts.

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
are approved but not yet implemented. The accepted scope includes native-only facilities only when required by an included
core contract; full frontend adoption remains excluded.

## Next implementation work

Continue B2 with text compound protocols and admitted numeric scalar peers
using existing operator metadata and native error authorities. Boxed bitwise/shift
compounds remain source-deferred and are not an implicit extension of this goal. Canonical numeric compound plans
now reuse the published calculations and casts.
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
