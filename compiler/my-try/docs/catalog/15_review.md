# Chapter 15 — compact decision inventory
Doc Status: planning

Update 2026-10-23: CH15-RUNTIME-TYPE-METADATA-01 (group 7 below) is now an
explicit final-catalog blocker covering all compiler/my-try type consumers, beyond
the historical four runtime-family gates. See the [owning debt](15_blocking_debt.md#ch15-runtime-type-metadata-01--review-metadata-driven-types-not-hardcoded-families)
for scope, required dispositions and closure proofs. No audit execution is claimed.

Update 2026-10-23: [CH15-SHARED-PROVIDER-CONSTRUCTION-01](15_blocking_debt.md#ch15-shared-provider-construction-01--generic-shared-runtime-construction)
records deferred review of generic shared-provider constructors. BIND-01 reviews
an ordinary shared-returning factory first; this is not a new release gate.

Review date: 2026-10-16. Implementation baseline: `82659125` (Chapter 14 native
closeout). This is the complete grouped index of the debt currently recorded in
[Chapter 15](15_blocking_debt.md), including its unnamed CH07/09/13/14 follow-ups.
It is not a claim that every restriction elsewhere in the catalog has already been
transferred or audited. That remaining catalog-wide audit is item 49.

Numbers are selection handles for this review; existing debt IDs remain the owners.
Rows group closely related work, not necessarily one implementable slice. Every
listed extension remains open unless explicitly described as a partial foundation.
Priorities are recommendations, not new release gates or authorization to implement.

- **G**: existing final-catalog blocker. Only items 1–2 have this status.
- **P1**: high-value correctness, prerequisites or dependable development workflow.
- **P2**: useful feature/performance expansion, scheduled by actual demand.
- **P3**: optional ergonomics, broad compatibility or parked work.
- Dependencies name decisions/owners that must be settled before enabling the item;
  a shared owner does not imply that every sibling feature must be implemented first.

## Full grouped inventory

| # | Priority | Remaining work / current boundary | Dependency or owning debt |
| --- | --- | --- | --- |
| 1 | Closed | Value-iteration mutation/replacement: live until first structural mutation, then preserve remaining entries; aliases, COW, lifetime and exits verified. | CH06-ITER-01 / [V-D01](../planning/iteration_mutation.md), closed 2026-10-22; reference safety remains item 2. |
| 2 | G | Reference foreach: guarded loop-scoped element binding, write-through/COW, source replacement, non-escape, key independence; still disabled. | CH06-REF-01; 1 → runtime safety foundation → compiler integration. |
| 3 | P1 | Structured diagnostics: source origins, file/line/column, invalid-language versus unsupported categories; messages exist. | CH13-DIAG-01; source provenance + shared preparation + presentation. |
| 4 | P2 | Advisory warnings: retained non-fatal delivery, publication/retraction, reference-return/performance advice; Clang stderr is not this API. | CH07-WARN-01; coordinate with 3, do not add an emitter-only path. |
| 5 | P1 | Stable hashing: fixed algorithm or narrowed stability promise, persisted-key version/migration policy, binary/UTF-8/span goldens. | CH15-RUNTIME-HASH-01; runtime hashing + cache consumers; byte spans also need 6. |
| 6 | P1 | Safe borrowed source views: owner destruction/move/replacement, escape rules and endpoint/column limits; owning source APIs already work. | CH15-RUNTIME-SOURCE-01; runtime view lifetime before span exposure. |
| 7 | P1 | Runtime type audit: replace four vector/hash capability gates when extending default/clean/empty/callable-wrapper operations; registry foundation exists. | CH15-RUNTIME-TYPE-METADATA-01; registered operation contracts, metadata-only unfamiliar-type proofs. |
| 8 | P1 | String contracts: byte/code-point strpos/strrpos, substr_replace windows, boxed implode admission, builder span append. User-deferred performance follow-up (2026-10-21, non-blocking/P2): small-call lowering overhead, replacement algorithms, bounded snapshot-elision coverage and character-operation measurements before any UTF-8 default review. | CH15-RUNTIME-STRINGS-01; normative reconciliation; append also needs 6. |
| 9 | P2 | Boxed number formatting: accepted kinds, null/bool and failures; concrete int/float formatting exists. | CH15-RUNTIME-FORMAT-01; boxed-family contract, no zero fallback for unsupported kinds. |
| 10 | P2 | Regex remainder: filter semantics, unambiguous count/limit adapters, callback/array provider ABI and retention, matching edges. | CH15-RUNTIME-REGEX-01; provider contracts; callback/reference metadata already exists. |
| 11 | P2 | Unicode length and cross-surface strlen reconciliation; v0.2 strlen remains byte length. | CH15-TEXT-LENGTH-01; audit legacy/portable callers before changing mappings. |
| 12 | P1 | Integer domains: checked overflow, signed/unsigned interoperability, literal ergonomics, unary/compound/bitwise/shift/identity widths and full uint64 parsing. | CH15-INTEGER-DOMAINS-01; same-signedness binary arithmetic/comparison already exists. |
| 13 | P2 | Environment/shell edges: NUL inputs, partial/read failures, pclose status, capture limits, concurrency and Windows. | CH15-RUNTIME-PROCESS-01; preserve current falseable helper contract; managed-process metadata exists. |
| 14 | P2 | Filesystem edges: Windows directory/link removal, clock conversion, iterator/close failures; stream APIs separate. | CH15-RUNTIME-FILESYSTEM-01; locks/provider linkage and fs_scan Result payload prerequisite resolved. |
| 15 | P2 | Datetime edges: valid epoch -1, time_t range/formatting, DST/timezones/platforms. | CH15-RUNTIME-DATETIME-01; fixed-width binary operations no longer a blanket prerequisite. |
| 16 | P1 | Library redesign/mixed-dynamic migration: Tasks scheduling/results/cancellation, transfer/synchronization, registrations/disabled paths; MySQL registration/consumers. | CH15-LIB-DYN-01; library-owned records and family separation; callback metadata alone is insufficient. |
| 17 | P2 | Remaining core APIs: debug/profile/microtime, boxed predicates, vector capacity/resize/fill/clear. | CH15-RUNTIME-REMAINDER-01; explicit mutable-container/API contracts; mutation helpers coordinate with 1–2. |
| 18 | P2 | IO resource APIs and tokenizer source facade: handles, compact columns, diagnostic records and ownership. | CH15-RUNTIME-REMAINDER-01; resource lifetimes; tokenizer coordinates with 3/6. |
| 19 | P2 | Curl, UI and WebView: optional packaging, boxed getinfo, enabled/disabled/network proofs, platform resources/event lifetimes. | CH15-RUNTIME-REMAINDER-01; separate whole-provider batches; no assumed installed dependencies. |
| 20 | P2 | Binary-codec facade and public compiler Storage/cursors; retained-reference and borrow contracts. | CH15-RUNTIME-REMAINDER-01; keep internal native helpers internal until truthful source APIs exist. |
| 21 | P2 | Extended runtime acceptance: large catalogs/fuzzing/provider edges, ownership/callable/protocol combinations, platforms/toolchains and PCH costs. | CH15-RUNTIME-INTEGRATION-TESTS-01 + CH14; bounded native rebuild/linkage now proved, exhaustive coverage is not. |
| 22 | P1 | Runtime CMake static/shared linkage ordering: reproduce core-independent consumer failure before selecting a fix. | Unnamed CMake debt; runtime build composition, independent of mixed/dynamic semantics. |
| 23 | P1 | Named arguments: functions/methods/constructors, names/defaults/mixing/variadics, evaluation order, ownership and incremental name changes. | CH15-NAMED-ARGS-01; shared binding contract; lambda support explicitly non-blocking, erased names need a decision. |
| 24 | P2 | Callable property defaults: instance/static and vector/hash leaves; capture admission, creation/initialization order and overrides. | CH15-CALLABLE-DEFAULTS-01; property-default + callable lifecycle owners; previous proposal unapproved. |
| 25 | P2 | Defaults across erased callable boundaries: preserve, adapt or require explicit arguments without silently discarding defaults. | CH15-CALLABLE-PARAM-DEFAULTS-01; shared signature contracts; coordinate with 23. |
| 26 | P2 | Owning/escaping receiver captures: explicit ownership, class/inline behavior, shared/weak choices, cycles and destruction. | CH15-CALLABLE-RECEIVER-01; borrowed $this remains invocation-bounded; lifetime design first. |
| 27 | P2 | Precise erased-call targets: assignment/parameter/return/property/container flow and incremental invalidation. | CH15-CALLABLE-TARGETS-01; retain conservative recursive-reference safety until proved. |
| 28 | P2 | Callable values in mixed/dynamic: storage/copy/invoke, captures, runtime signatures, receiver independence, non-escape and JSON failure. | CH15-CALLABLE-01; callable + boxed-family contracts and measured representation. |
| 29 | P2 | Preserve array versus object identity independently of keys, including empty/numeric-key JSON and mutations. | CH15-SHAPE-01; representation/ABI decision; packed-shape query is current behavior. |
| 30 | P2 | Dynamic -> access: fixed/computed names, reads/writes/probes/removal, missing/scalar receivers and aliases. | CH15-DYNAMIC-PROP-01; decide relation to 29; callable members additionally need 28, no implicit method binding. |
| 31 | P2 | Broader ownership and reference returns: selection/variadics/const; different or recursive origins, runtime returns, loop-header aliases. | CH07-OWN-EXEC-01 + CH07-REF-EXT-01; lifetime contracts; same-origin returns/body aliases already exist. |
| 32 | P1 | Method receiver lifetimes: unique, projected inline and temporaries; declared read-only methods; weak requires explicit lock. | CH15-METHOD-LIFETIME-01; call-scoped lifetime/invalidation, receiver-before-arguments. |
| 33 | P2 | Inheritance extensions (7): instance late static binding; struct new static; unique/weak casts; unique/weak joins; sibling joins; trait adaptations; abstract constructors. | CH15-INHERITANCE-EXTENSIONS-01; each has its own called-type/ownership/selection/member contract; not one slice. |
| 34 | P3 | Interface-qualified static dispatch and broader PHP-style protected receiver access. | CH15-INTERFACE-STATIC-01 + CH15-PROTECTED-01; explicit dispatch/controlled lowering contracts; current declared-type/C++-compatible rules stand. |
| 35 | P2 | Trait constructors/destructors: precedence/conflicts, set: inputs, visibility, parent/data construction and nonthrowing cleanup order. | CH15-TRAIT-LIFECYCLE-01; consumer-owned member + constructor/destructor paths; adaptations separate. |
| 36 | P3 | PHP-style constructor property promotion; separate declared properties with set: inputs already implemented. | CH15-CTOR-SYNTAX-01; normalize to existing initialization owner if selected. |
| 37 | P2 | References inside initialization data: tagged identity, source versus constructed target, forward/cyclic references, COW, lifetime and cleanup. | CH15-DATA-REF-01; notation and ownership undecided; proposed #tags are not approved syntax. |
| 38 | P2 | Enum extensions: cases/from/tryFrom/reflection, string backing, nullable/Result payloads, defaults/fields, methods/interfaces, constants and exhaustiveness. | CH09 follow-ups; enum_name/value/from_value exist; shared CH10/11 foundations alone do not enable extensions. |
| 39 | P1 | Finally transfers: non-scalar/reference/owning returns; break/continue crossing protected regions; transfers leaving finally. | CH13 follow-ups; cleanup/transfer lifetime rules; scalar/void returns and propagation already proved. |
| 40 | P2 | Exception extensions: multiple/union catches, source subclasses/runtime inheritance, new Exception metadata, bare rethrow, throw expressions/chaining. | CH13 follow-ups; exception identity/dispatch/constructor contracts; single-type factory/throw/catch already exists. |
| 41 | P2 | Error/Result extensions: broader getter receivers/payloads, result<error>, handle ownership/identity APIs, static exception-effect analysis. | CH13 follow-ups; metadata/type/copy/lifetime contracts; bounded error::make/getters/conversion exist. |
| 42 | P1 | Native cache cost: measure and reduce remaining PCH validation, dependency/hash/process costs without weakening recovery/search correctness. | CH15-NATIVE-CACHE-PERF-01; PCH template instantiation and opt-in dependency snapshot already implemented. |
| 43 | P1 | Automatic runtime identity/refresh: covered inputs, header/provider additions/removals, lookup/catalog changes and failed-refresh recovery. | CH15-NATIVE-RUNTIME-IDENTITY-01; snapshot refresh owner; current opt-in mode requires a fresh cache after runtime edits. |
| 44 | P2 | Configurable Mold linking: selected linker/flags fingerprinting, reuse/revert/failure proofs and properly labelled measurements. | CH15-MOLD-01; native link owner; currently hardcoded /usr/bin/ld, historical benchmark is not integration. |
| 45 | P1 | Checked current-build reader/run boundary: validate ready publication, matching native status and executable under reservations. | CH14 follow-up; publication/native-build owners; historical executable path is not freshness, runtime refresh remains 43. |
| 46 | P2 | Native parallel actions and cache garbage collection; CLI module selection and broader package/build-root/toolchain acceptance. | CH14/native-guide follow-ups; scheduling, reservations and artifact lifetime contracts; not a hermetic-cache claim. |
| 47 | P2 | Less conservative template/handle incomplete-type includes. | CH14 follow-up; C++ declaration requirements + per-runtime bindings; current conservative includes are correct. |
| 48 | P1 | Stale test expectations: model built-in counts and data-construction lambda rejection; preserve real assertions and parked LLVM boundary. | CH09 + runtime-integration maintenance; inspect/reroute appropriate tests, not blanket expected-count edits. |
| 49 | P1 | Inherited S2S-limit audit: trace every restriction to solved/proved, safely removable, deliberate or deferred with owner. | CH15-S2S-LIMITS-01; catalog-wide disposition ledger; review does not automatically enable syntax. |
| 50 | P2 | Fixture deduplication and cleanup/IDE performance: transport-aware dedup, runtime reuse/timings; retirement measurements and explicit debug endpoint. | CH15-TEST-FIXTURES-01 + CH15-CLEANUP-PERF-01; distinct test/cleanup owners, no coverage reduction or inspection side effects. |
| 51 | P3 | Optional missing spelling/custom collection emptiness registration and string-producing to_json alias. | CH15-MISSING-01 + CH15-JSON-01; separate naming/capability decisions; !isset and checked json_encode already available. |
| 52 | P3 | Formatting/broad warning cleanup; legacy STAN and LLVM remain parked. | CH14 exclusions + existing handoff; explicit request before STAN/LLVM work; no new release gate. |

## Reconciliation findings and evidence

This was an implementation/document review, not a new test sweep. The last executed
native evidence remains [Chapter 14's gate](14_output_runtime.md#bounded-closeout-and-native-gate--2026-10-16).
No outstanding feature is closed merely because its infrastructure now exists.

- [Runtime closeout inventory](../planning/runtime_module_closeout.md) and
  `runtime/metadata/modules/process.json` confirm managed-process handles/outputs
  and provider requirements. The old process-entry prerequisite was stale.
- [Integer contract](../../../../specs/compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons)
  now permits bounded same-signedness binary arithmetic/comparisons. Datetime's
  uint64 returns no longer await all fixed-width arithmetic; wider operations remain 12.
- [Metadata inventory](../planning/runtime_module_closeout.md) and preparation's
  `object_construction.php`, `cleanup.php`, `value_probes.php`, `types.php` preserve
  four named capability gates. Construction/index/iteration/member/storage foundations
  must not be re-queued wholesale. Regex callback recipes/reference overloads exist.
- `06_native/cpp_build.php` implements `-Wshadow`, provider linkage, PCH template
  instantiation, opt-in dependency-input reuse and status publication. It still
  selects `/usr/bin/ld`. [Native guide](../architecture/native_cpp_build.md) distinguishes
  a checked reader, runtime refresh, search completeness and GC/parallel extensions.
  Historic timings are not measurements of today's native compiler.
- `runtime/include/scpp/stable_hash.hpp` still promises cross-stdlib stability while
  delegating to the string-key hash; `runtime/include/modules/source/source.hpp`
  still keeps a raw owner pointer in `byte_span`. Their contract/lifetime debts remain.
- `tests/model.php` still hardcodes registry counts and exercises LLVM paths;
  `tests/data_construction.php` still lists `{a: function() {}}` as invalid syntax.
  These are inspected stale expectations, not newly reproduced test failures.
- CH13 and CH14 native gates cover their stated bounds. They do not close CH12's
  broader native parity/rejection/incremental coverage gap, the module-wide acceptance
  matrix, or the inherited-limit audit. Preserve those evidence boundaries in 21/49.

## Added bootstrap debt — 2026-10-21

**CH15-READONLY-FIELDS-01:** authored readonly instance fields need an initialization
and write-enforcement contract across compiler routes. By user decision, the compiler's
21 readonly properties are temporarily ordinary properties, with constructor-only
writes by convention and source TODOs. PHP enforcement is deliberately lost during
this deferral. This adds no final-catalog gate and does not change read-only references.
See [the owning debt](15_blocking_debt.md#readonly-instance-fields--ch15-readonly-fields-01).

## Recommended sequence and first bounded slice

Update 2026-10-22: item 1 is complete through [V-D01](../planning/iteration_mutation.md),
including runtime/source proofs and measured costs. Item 2 remains separate and
reference foreach stays disabled. The proposal below is historical and superseded
for value iteration; it does not reopen the completed implementation.

Start with **1: CH06-ITER-01 policy/ownership design**, then agree **2's runtime
foundation** before compiler reference-foreach integration. These are the existing
catalog gates; broad feature additions do not remove them. Independent high-value
work can be selected from 3
(diagnostics), 23 (named arguments), 42–45 (build workflow), or 48 (test maintenance).
Runtime libraries should proceed in demand-led whole-provider batches after their
specific safety/contract prerequisites, not as one universal library rewrite.

**Proposed first slice: CH15-ITER-DESIGN-01 — decision-ready iteration contract.**
This is deliberately a design slice before cross-owner implementation, not an
attempt to enable reference foreach with a local emitter patch.

- Deliver one matrix for vector/hash/mixed/dynamic: existing-element update,
  insertion/removal/clear, source replacement/move, alias/call mutation and COW copies,
  including normal exit, break/continue, return and exception cleanup.
- Compare value-iteration choices (reject structural changes, snapshot or documented
  live traversal) against the existing bounded contract. Retain the agreed interim
  reference direction: current-element loop-scoped binding; update allowed; structural
  mutation/replacement rejected before invalidation; no escaping native reference.
- Map actual mutation/replacement, iterator, wrapper, COW and `noexcept` boundaries.
  Identify who owns a guard and when it is released. A table-storage guard alone is
  insufficient to protect source-binding replacement.
- End with a proposed normative amendment and a reviewable first runtime-only vertical
  slice, including which families it can honestly cover and the direct runtime tests
  for rejected-mutation unchanged-state, aliasing, COW and all exits. If the foundation
  necessarily spans several owners, state that scope/cost before requesting implementation.

**Non-goals:** no new syntax, no reference-foreach enablement, no unapproved snapshot
semantics, no wholesale container redesign, no runtime guard implementation or broad
benchmark/native rebuild in the design slice. Completion means an explicit policy
choice and agreed ownership/proof plan, not declaring items 1–2 resolved.

If immediate daily workflow value is preferred over catalog-gate design, **45** is
an independent bounded candidate: a reservation-held verifier of the published
revision/native-status/executable association. It must not claim detection of edits
to unrepublished sources or arbitrary runtime changes, nor introduce auto-build/run.
