# Language verification report
Doc Status: planning

Verification closeout: evidence through checkpoint 75. All planned checks and discovered entries have evidence or an explicit blocker, exclusion or deferral. Verification completion does not mean complete language support.

The frozen v0.2 audit has 105 passed checks, 5 failed, 6 blocked and 3 explicitly deferred across 36 language families. A passed audit check can establish a complete inventory or reproduce intended rejection; it does not necessarily mean every feature in that family is implemented. No compiler/runtime fixes were made by this verification goal.

## Scope and provenance

Tests ran on `alex-ai@dev002.travelfuse.ro` under `/home/alex-ai/language-verification/20261018`, using shared runners and at most 32 workers. Local work processes evidence and reports only. The frozen source includes the previously approved S01/scalar working-tree changes; HEAD alone is not its identity.

- Frozen HEAD: `d675aca8251374555c9191067793175b43d8a79c`.
- Snapshot: 6178 files; source archive SHA-256 `b7958fd2f53b3ee901595ff6844cd220147d8d48cb1cd287c3d7fe402c9fd71a`.
- v0.1 reference: `75de94c71e37060c564b6ade19850b7686bd457a`, checked against local/GitHub references at checkpoint 32; latest tag and release branch are separately recorded.
- Evidence: [coverage ledger](language_verification_coverage.json), [progress chronology](language_verification_progress.md), and [checkpoint 75 archive](language_verification_evidence/logs-checkpoint-75.json.gz). Per-check runs and earlier checkpoints retain the original and adapted lanes. The archived environment records Clang 18.1.3 and PHP 8.2 for the initial host; subsequent integrations use the private PHP 8.5 launcher recorded in their command evidence. Environment failures remain separate from compiler failures.

## Coverage by family

Counts describe checks, not numbers of supported features. Exact assertions, proof limits, sources, commands and issue links remain in the coverage ledger.

| Family | Scope | Passed | Failed | Blocked | Deferred |
| --- | --- | ---: | ---: | ---: | ---: |
| LV01 | Lexing, comments, identifiers, reserved words, literal boundaries, escapes/bytes, precedence/token ambiguity, malformed-source diagnostics and source locations | 3 | 0 | 0 | 0 |
| LV02 | Local declarations/inference, explicit types, initialization/defaults, reassignment, shadowing, duplicate names, scope exits, uninitialized/unknown reads and source constants | 3 | 0 | 0 | 0 |
| LV03 | Type identity and aliases | 3 | 1 | 0 | 0 |
| LV04 | Bool equality/identity, numeric arithmetic/comparison, bitwise/shifts, power, unary signs/not, increment/decrement, compound updates | 3 | 0 | 0 | 0 |
| LV05 | Assignment, explicit casts, arguments, returns, fields, elements and reference boundaries | 3 | 0 | 0 | 0 |
| LV06 | Left-to-right eager evaluation/conversion, each operand once, snapshots before RHS mutation, failure ordering, receiver/argument order, lazy `&&`/`//`, word forms and `xor` | 3 | 0 | 0 | 0 |
| LV07 | Conditions and negation across families | 3 | 0 | 0 | 0 |
| LV08 | if/else forms including brace-free strict fixtures | 2 | 1 | 0 | 0 |
| LV09 | Functions, recursion, forward calls, value/reference/variadic parameters, defaults, named-argument status, return/void/fallthrough rules and rejected signatures | 4 | 0 | 0 | 0 |
| LV10 | Null, nullable, Result, falseable and bool-able states | 3 | 0 | 0 | 0 |
| LV11 | Mixed/dynamic family separation, scalar/table states, explicit crossing, typed boundaries, COW versus shared identity, failed mutation guarantees and prohibited nesting | 3 | 0 | 0 | 0 |
| LV12 | String bytes/UTF-8 distinctions, embedded NUL, interpolation, concatenation and `.=` conversions, identity/equality/ordering admission and errors | 4 | 0 | 0 | 0 |
| LV13 | Data notation and contextual literals | 3 | 0 | 0 | 0 |
| LV14 | Vector/hash/mixed/dynamic reads, writes, append, nested paths and property-root paths | 3 | 0 | 0 | 0 |
| LV15 | count/empty/isset/unset on values and paths | 3 | 0 | 0 | 0 |
| LV16 | foreach keys/values, empty and nested iteration, loop variable lifetime, mutation during iteration, copy/alias behavior | 2 | 0 | 0 | 1 |
| LV17 | References/aliases, reference arguments/returns, same/different origins, escape prevention, addressability, readonly places, nested calls and lifetime failures | 3 | 0 | 0 | 0 |
| LV18 | Shared/unique/weak handles, move/copy/lock/reset, null handles, expired access, ownership conversion, temporary receivers, destructors and exceptional cleanup | 3 | 0 | 0 | 0 |
| LV19 | Classes/structs, fields/defaults/visibility, constructors/data construction, instance methods, readonly/inline receivers, property mutations and storage identity | 3 | 0 | 0 | 0 |
| LV20 | Inheritance, abstract/interface conformance, overrides/dispatch, traits/conflicts, parent/static/self resolution, casts/instanceof and access-control errors | 3 | 0 | 0 | 1 |
| LV21 | Static properties/methods/constants, initialization/visibility, called-type identity, late static behavior and incremental declaration replacement | 3 | 0 | 0 | 0 |
| LV22 | Enum creation, backing values, equality/identity, declared helpers, conversion/invalid values, exhaustiveness and explicitly deferred extensions | 3 | 0 | 0 | 0 |
| LV23 | Closures/callables, value/reference/receiver captures, defaults and signatures, invoke/adaptation, storage/returns, escape/borrow checks and cleanup | 4 | 0 | 0 | 0 |
| LV24 | Error values versus exceptions, throw/catch/finally, propagation, cleanup order, return/break/continue through protected regions, uncaught presentation and recovery | 4 | 0 | 0 | 0 |
| LV25 | Namespaces/imports, qualified/unqualified lookup, cross-file symbols, visibility/export, dependency ordering, collisions, cycles and unresolved names | 3 | 0 | 1 | 0 |
| LV26 | Echo/output conversion, multiple-operand order, stream behavior, program entry/exit/CLI args, diagnostics and source locations | 3 | 0 | 0 | 0 |
| LV27 | Generic/specialized callable and runtime type contracts, argument inference, constraint rejection, dependent instances, invalidation | 2 | 0 | 0 | 1 |
| LV28 | Async/await, tasks/scheduling and suspension/lifetime obligations: determine v0.1 and v0.2 applicability, discover runnable source tests, explicitly classify absent compiler support | 0 | 1 | 2 | 0 |
| LV29 | Inventory all public modules/symbols against spec, runtime exports and compiler metadata | 3 | 0 | 0 | 0 |
| LV30 | Core/string/JSON APIs, builders, parsing/encoding, malformed inputs, state/ownership, byte handling and scalar/container return boundaries | 3 | 0 | 0 | 0 |
| LV31 | Filesystem/IO/datetime/process/environment/regex APIs | 5 | 0 | 1 | 0 |
| LV32 | Remaining providers and language-adjacent APIs: network/curl, databases, UI/WebView, source/tokenizer, binary codec and public Storage | 2 | 1 | 0 | 0 |
| LV33 | Actual compiler entrypoints, project composition, entry selection, exports/imports, multi-file C++, standalone headers, provider linking, build/run success and diagnostics | 2 | 1 | 1 | 0 |
| LV34 | Fresh versus incremental equivalence after body/type/signature/import/provider edits, deletion/re-add, rejection/repair, cleanup, stale facts and no-op behavior | 3 | 0 | 0 | 0 |
| LV35 | Original strict v0.1 corpus and project examples | 2 | 0 | 1 | 0 |
| LV36 | Cross-feature scenarios: containers across calls/returns, wrappers plus selection, closures plus ownership, inheritance plus construction, exceptions plus mutation/cleanup, optional API results plus typed boundaries | 3 | 0 | 0 | 0 |

## Proof layers

| Layer | Established evidence | Limits |
| --- | --- | --- |
| Parse/collection and preparation | Catalog sources and diagnostics; 370 operator admission observations with stage classification | Admission is not native behavior; unsupported operators remain issues |
| Generated C++ compilation | Focused native suites, independent integer oracle, wrapper grids and standalone-header checks | Native compiler self-host/parity is deferred |
| Runtime values, effects and errors | 100 integer type pairs, 504 boundary values, 2520 site assertions; 22 wrapper/payload state combinations; exact cleanup, failure and evaluation-order observers | Not a full Cartesian product; arithmetic overflow and untested ownership/value combinations remain bounded |
| Multi-file/export/provider integration | Project suite: 32 executions, 34 standalone headers and 3 actual Clang dependency assertions; production JSON and CMake failures independently reproduced | Multi-project CLI/export policy remains V-P01; optional service adapters remain blocked |
| Incremental and recovery | Retained-feature ledger covers 34 families with scoped edit/reject/repair/fresh/native evidence | Async blocked before retained facts; LV35 is a provenance axis; provider hot replacement explicitly deferred |

The [runtime ledger](language_verification_runtime_coverage.json) maps 198 declarations individually. The [provider comparison](language_verification_provider_comparison.json) distinguishes absent registrations, metadata exposure and internal/language-only surfaces. Registration or runtime-only execution does not prove source-language availability.

## Inventory reconciliation

- 330 source cards and 661 catalog sections have scoped dispositions.
- 997 discovered entries: 729 deferred legacy-profile, 126 strict, 122 runtime-compatible, 8 intentional runtime changes, 4 intentional runtime rejections, 2 contract reviews, 3 adapter blockers and 3 baseline-unproven runtime cases.
- Original strict corpus (126 entries): 14 runtime-compatible, 83 compile-compatible, 22 compatibility gaps, 2 rejection-review cases and 5 adapter blockers. These are raw differential outcomes; intentional changes and fixture issues are separated in the reconciliation ledger. Compile-compatible entries do not establish equivalent runtime behavior.
- Semantic review of that strict corpus: 14 runtime-compatible, 83 compile-compatible, 9 missing-support, 1 compatible rejection, 1 fixture migration, 3 intentional changes, 5 adapter blockers, 6 deferred legacy-syntax, 3 deferred v0.2-support and 1 contract-review case. See [strict reconciliation](language_verification_strict_reconciliation.json) for case-to-issue mappings.
- All 36 planned probe designs link to evidence or explicit blockers/deferrals. The original inventory retains its starting-state labels.
- Operator audit: 370 cells, 139 admitted and 231 rejected; 196 contract matches, 120 missing-support cells, 48 deferred-scope cells and 6 contract-decision cells. These are preparation/emission observations, not 370 native behavioral passes.

## Failed, blocked and deferred checks

| Check | Status | Issue groups | Remaining boundary |
| --- | --- | --- | --- |
| LV28.001 | failed | S12 |  |
| LV28.002 | blocked | S12 | Suspension/task-lifetime proof is blocked by absent source admission; no callback translation is permitted. |
| LV32.002 | failed | V-R01 |  |
| LV33.001 | failed | V-R01, V-R11 | JSON automatic provider linkage fails independently; tracked by V-R01 and LV33.003. Aggregate runtime CMake consumer fails with static-core/shared-provider link order (V-R11); isolated and my-try production-builder proofs do not close this distinct build route. |
| LV35.001 | blocked | V-E02, S04, S05, S06, S07, S08, S09, S10, S11, S12, V-H10, V-D09 | Five curl cases need a local provider/project adapter; no compiler/runtime pass is inferred for them. Recorded missing-support cases require separate repair goals, not changes during verification. |
| LV08.003 | failed | S06 | Brace-free if/else prevents compiling original strict fixture; nested braced loop transfers are independently covered by LV08.001. |
| LV16.003 | deferred | V-D01 | Await agreed iteration mutation semantics and lifetime/invalidation design (CH06-ITER-01). No live-mutation or guard behavior is promised. |
| LV20.003 | deferred | V-D03, V-D04, V-D05 | Lifecycle admission blocks constructor/destructor conflict-policy testing; unique join stops at consuming-boundary admission; weak join stops at shared-selection admission. These are explicit current deferrals, not permanent compatibility exceptions. |
| LV27.003 | deferred | V-D10 | Live provider constraint-edit/repair and selective instance preservation are not supported or verified; covered by explicit deferral V-D10. |
| LV28.003 | blocked | S12 | Suspension/task-lifetime proof is blocked by absent source admission; no callback translation is permitted. |
| LV31.003 | blocked | S13 | IO open/use/close, declared failure and unwind/release execution are blocked until S13 source bindings exist. |
| LV33.003 | blocked | V-P01 | Multi-project configuration and selected/missing-provider CLI cases are blocked by V-P01. A checked run/current-build reader remains explicitly deferred in docs/architecture/native_cpp_build.md; direct execution of a freshly returned build path is not that reader. |
| LV03.004 | failed | V-L01, V-L02 | Union admission blocks recursive forbidden-payload and native union-layout probes. Layout helpers also fail independently on an admitted struct. |
| LV25.004 | blocked | V-P01 | Project-level public/private export policy and dependency composition remain blocked by V-P01; lexical/class visibility is verified separately. |

## Intentional changes and blind spots

Identical v0.1/v0.2 lowering is not required. Strict declarations, separated mixed/dynamic families, collection-only empty, and earlier preparation rejection of statically invalid operations are evaluated against current contracts. Original fixtures and explicitly adapted sources/observers remain separate. Deferred legacy comment declarations (S02) are not a strict compiler repair. Settled division_by_zero behavior remains unchanged.

Legacy syntax/profile execution, JSS, LLVM, STAN, native compiler parity and additional platform sweeps remain deferred. Async suspension/lifetimes, strict IO resources, multi-project CLI composition and optional curl/MySQL integration are not established by adjacent runtime successes. Iteration mutation/reference rules and live provider replacement remain explicit contract deferrals. Per-section limits retain narrower untested combinations even where the containing check passed.

## Handling queues

These queues are independent of semantic disposition: a normal-discussion entry may be an explicit deferral rather than a regression. V-E01 is a resolved environment finding retained for provenance. A simple label does not itself authorize repair; the selected batch is narrower.

### Simple Fix

| ID | Finding | Semantic disposition |
| --- | --- | --- |
| V-H01 | Stale entry-boundary expectations and observers | Test/harness defect |
| V-H02 | Type-catalog totals predate the Exception registration | Test/harness defect |
| V-H03 | Nullable edit test matches an obsolete diagnostic | Test/harness defect |
| V-H04 | Negative operator fixtures predate admitted effectful expressions | Test/harness defect |
| V-H05 | Artifact transport host template has an unfilled runtime-catalog placeholder | Test/harness defect |
| V-H06 | Indexed-container test observer invokes unavailable native string equality | Test/harness defect |
| V-H07 | JSON native observer omits complete table definitions | Test/harness defect |
| V-R01 | JSON module omits its value decoder/encoder provider from production builds | Missing support: runtime build integration |
| V-H08 | Interpolation suite retains obsolete scalar-concatenation rejection | Test/harness defect |
| V-H09 | Matrix generator omits 39 supported probe rows | Test generation coverage gap; supplementary audit probes now cover all rows |
| V-H11 | Native negative fixtures require GCC-specific diagnostic wording | Test/harness defect |
| V-D02 | Catalog status and prerequisite prose predates current supported contracts | Documentation defect |
| V-H12 | Data parser suite retains obsolete closure-expression syntax rejection | Test/harness defect |

### Light Discussion

| ID | Finding | Semantic disposition |
| --- | --- | --- |
| V-E01 | Verification host requirements differ by stage | Environment/provider blocker |
| S06 | Brace-free if/else bodies remain unsupported | Missing strict v0.1 source support |
| V-L03 | Ordinary string comparison operators lack admission | Missing support; current bounded compiler gate is not a permanent compatibility exception |
| S04 | Falseable string result identity | Missing/broken support |
| S07 | Mixed type predicate source bindings | Missing/broken support |
| S09 | String search and replacement source API gaps | Missing/broken support |
| V-H10 | Closure container negative fixture masks its intended assertion | Test/harness defect |
| V-D09 | php::microtime alias requires a compatibility decision | Contract requires discussion |
| V-R04 | Regex filter registration is held by conflicting replacement semantics | Contract conflict |
| V-R07 | Strict vector capacity and construction helpers lack source bindings | Missing/broken support |
| V-D12 | Platform query and environment contract authority needs clarification | Contract review |
| V-L04 | Float comparisons and mutation lack source admission | Missing support |
| V-L05 | Canonical numeric logical operations lack source admission | Missing support |
| V-D13 | Nullable arithmetic configuration contradicts its lifting policy | Contract conflict |
| V-L08 | Nullable logical negation silently treats absence as false | Semantic mismatch |
| V-L11 | Configured nullable and fixed-width text operands reject concatenation | Missing support |
| V-D14 | Result text helper behavior lacks configured source contract | Contract review |

### Normal Discussion

| ID | Finding | Semantic disposition |
| --- | --- | --- |
| S12 | Async callable syntax and suspension are absent | Missing/broken support |
| V-D01 | Iteration mutation semantics remain explicitly unresolved | Explicit v0.2 deferral |
| V-L01 | Restricted union declarations have no v0.2 admission path | Missing promised source support |
| V-L02 | All four declared layout probe helpers are unresolved | Missing promised source support |
| V-D03 | Trait lifecycle methods and conflict policy are deferred | Explicit v0.2 deferral |
| V-D04 | Unique and weak conditional joins are deferred | Explicit v0.2 deferral |
| V-D05 | Static interface implementation selection is deferred | Explicit v0.2 deferral |
| V-D06 | Non-scalar returns and loop transfers crossing finally are deferred | Explicit v0.2 deferral; not a permanent compatibility exception |
| V-D07 | Named arguments, unpacking and reference default/variadic forms remain deferred | Explicit bounded call-contract exclusions; not permanent compatibility exceptions |
| S13 | Strict IO resource type and source bindings are absent | Missing/broken support |
| V-D08 | Enum extension surface remains explicitly deferred | Explicit current deferral |
| V-P01 | Project composition and public export policy are not exposed by the my-try CLI | Missing/broken support |
| S05 | Reference-captured closure explicit callable boundary | Missing/broken support |
| S08 | Debug helper source exposure | Missing/broken support |
| S10 | Borrowed source span admission is explicitly deferred | Explicit current deferral |
| S11 | Typed tokenizer source facade is absent | Missing/broken support |
| V-E02 | Strict curl corpus lacks a provider/project adapter | Environment/provider blocker |
| S02 | Legacy comment declarations are deferred | Deferred legacy syntax |
| V-D10 | Live provider constraint replacement is outside immutable startup metadata contract | Explicit current deferral |
| V-R02 | Strict task runtime module is absent | Missing/broken support |
| V-R03 | Strict curl runtime module is absent | Missing/broken support |
| V-R05 | Regex callback bindings need provider ABI integration | Missing/broken support |
| V-R06 | Stable hashing violates its stability prerequisite | Contract conflict |
| V-R08 | UI and WebView preview modules have no v0.2 source exposure | Missing/broken support |
| V-D11 | Collection provider migration has no established replacement contract | Contract review |
| V-R09 | Regex replacement limit overload is absent | Missing/broken support |
| V-L06 | Carrier equality and float strict identity lack source admission | Missing support |
| V-L07 | Guarded carrier operator lifting lacks source admission | Missing support |
| V-L09 | Boxed integer bitwise and shift operations lack admission | Missing support |
| V-L10 | Wrapper-to-wrapper coalescing lacks admission | Missing support |
| V-R10 | Runtime nullable increment is missing despite the lifting contract | Missing support |
| V-E03 | Native MySQL integrations lack a configured verification harness | Verification blocked |
| V-D15 | Return-by-reference advisory lacks retained warning delivery | Missing support |
| V-R11 | CMake aggregate runtime links static core before dependent shared modules | Build integration defect; reproduced historical debt |

### Red Flag

No confirmed finding is currently assigned this class. This does not certify architectural soundness beyond the audited evidence.

## Proposed first repair batch

`fixture-oracles-01`: **V-H02, V-H03, V-H05, V-H06, V-H07, V-H12**. These six bounded fixture/observer adaptations have passing archived replays. Their exact changes and acceptance checks are recorded in the [issue ledger](language_verification_issues.json). Rerun each affected original suite after repair on dev002, retaining the audit failures as historical evidence. No compiler/runtime changes belong to this batch.

The proposal is not approval and no repair goal has started. Entry observers and operator fixture migration are separately eligible within the enumerated issue scopes; production JSON metadata, matrix seeds and catalog prose also remain separate candidates.

## Completion boundary

The assertion-level card/section reviews, per-declaration runtime mapping, strict/discovery reconciliation and planned-check evidence satisfy the verification plan. Final route corrections distinguish reference/method/static/interface contracts and runtime behavior from artifact evidence. Broad family routes are navigation; exact observations and limits in each record define what was proved.

The [completion audit](language_verification_completion_audit.json) records the gates, ledger hashes and evidence-locator checks. Failed, blocked and deferred behavior remains unresolved implementation or discussion work. No repair goal starts automatically.


## Subsequent fixture repairs — 2026-10-19

The user expanded the initial proposal to include V-H01. V-H01/H02/H03/H05/H06/H07/H12
are now implemented and verified on dev002; see the [repair report](fixture_repairs_01.md).
All 1,409 full S2S cases, the 20 named H01 exception observers and the affected
original suites pass, including the corrected H03 follow-up. No compiler/runtime
fixes were made. The verification counts and findings above describe their original
frozen snapshot; they are not recomputed from this repair run.


## Fixture repair follow-up — 2026-10-19, batch 02

V-H04, V-H08, V-H09 and V-H11 are implemented and verified on dev002; see
[repair report](fixture_repairs_02.md) and [results](fixture_repairs_02_results.json).
The affected property/dynamic/interpolation suites passed. H09 covers exactly 39
emitted rows (six preparation rejections, 33 runtime rejections), positive controls
and generator regressions. H11 passes the forbidden-copy and valid-ownership checks
on GCC and Clang. No compiler/runtime changes, full matrix rerun or new blocker.
Original audit counts and findings above remain frozen; repair status is tracked
separately in the issue ledger.

## Simple repair follow-up — 2026-10-19, batch 03

V-R01 and V-D02 are repaired and verified; see [report](simple_repairs_03.md) and
[results](simple_repairs_03_results.json). JSON metadata now publishes both value
and document providers; production linkage, exact values, changed requirements,
cold build and warm reuse passed on dev002. The existing module-build regression
also passed. The 141 identified documentation sections now distinguish current
scoped support from historical guidance, retaining genuine gaps and original evidence.
All 13 originally classified simple fixes are complete. This changes repair status,
not the frozen audit counts or remaining discussion classes. V-R11 remains separate;
no compiler/runtime implementation fixes were made.

## V-L04 implementation follow-up — 2026-10-19

The six exact float/float ordinary comparisons and four float prefix/postfix mutations
are implemented and verified on dev002; see [bounded report](float_operators_l04.md)
and [results](float_operators_l04_results.json). Prefix/postfix retain value snapshots.
Scalar/sequencing, property and exact zero-division regressions passed. No runtime
implementation changed or full matrix was rerun. Strict float identity, spaceship,
cross-type comparisons, compounds and wrappers remain separate gaps. Original audit
counts and findings above remain frozen; the issue ledger records repaired V-L04.


## V-L05 implementation follow-up — 2026-10-19

Canonical numeric !/AND/OR, including approved and/or aliases and int/float pairs
in both orders, are implemented and verified on dev002. See [report](numeric_logical_l05.md)
and [results](numeric_logical_l05_results.json). Lazy evaluation, exact results,
error paths, left-truth snapshots and recovery passed, together with existing
bool/boxed logical, conditional and scalar regressions. Numeric xor, wrappers and
V-L08 remain separate; no runtime implementation changes or full matrix rerun.
Original audit findings/counts remain frozen; V-L05 repair status is separate.

## V-L03 repair follow-up — 2026-10-19

All seven exact string/string comparisons are implemented and verified on dev002.
The [report](string_comparisons_l03.md) records 9,072 value/error cases and 4,536
local/property snapshot cases, plus passing identity, scalar, float and numeric-logical
regressions. Complete-byte equality, lexicographic byte ordering and ordered value
snapshots are preserved. Two fixture-authoring failures are archived separately;
no runtime changes, wrapper expansion or full matrix rerun occurred. Original
verification findings/counts remain historical; V-L03 now carries a repair status.

## First guarded-wrapper repair follow-up — 2026-10-19

[Guarded wrapper report](guarded_wrappers_01.md) records V-D13 reconciliation,
nullable-int V-L08 correction and partial V-L07 implementation. The approved
canonical-int carriers now use checked extraction before ordinary payload operators,
with left-to-right snapshots and lazy RHS guards. Equality, mutation/compounds,
other payload/carrier domains, spaceship and xor remain separately queued.
Original audit counts and failed fixture/diagnostic attempts remain historical.
No runtime implementation changes, full matrix rerun or native compiler rebuild.

## Second guarded-wrapper repair follow-up — 2026-10-19

[The result_or_bool extension](guarded_wrappers_02.md) adds canonical-int guarded
operators with both true and false sentinel failures. It reuses existing extraction,
snapshots and lazy evaluation. Condition/Elvis/presence/take behavior remains
separate; coalescing remains a static rejection. V-L07 remains partially repaired:
equality, writable updates, cross-carrier pairs and other payload domains are deferred.
The original audit and first-slice evidence remain unchanged.

## Guarded-float repair follow-up — 2026-10-19

[The guarded-float report](guarded_float.md) extends all four carriers to canonical
float signs/negation, arithmetic, ordering and lazy AND/OR. It corrects nullable-float
absence negation while preserving direct conditions, signed-zero division errors,
source snapshots and lazy checks. V-L07 remains partial: mixed pairs, equality,
writable updates and other payload domains remain separate. No runtime changes or
full matrix rerun; original audit findings and earlier repair evidence are retained.

## Guarded mixed-numeric repair follow-up — 2026-10-19

[The mixed-numeric report](guarded_mixed.md) extends same-family int/float and
wrapper/plain pairs for arithmetic and lazy AND/OR. Extraction preserves each
payload type; existing scalar owners handle promotion/truth conversion. Mixed
ordering, equality, cross-family pairs and writable updates remain separate.
Newly admitted negative fixtures were retired; genuine rejection cases and
historical audit/repair evidence remain. V-L07 stays partial. No runtime changes
or full matrix rerun.

## Guarded-bool repair follow-up — 2026-10-19

[The boolean logical report](guarded_bool.md) adds four unary and 48 binary
signatures across all four carriers. Payload false/true remain distinct from
explicit sentinel branches; reached sentinel/absence/error states throw through
existing extraction. Nullable-bool ! is corrected, while conditions/selectors/take
retain separate contracts. V-L07 stays partial: cross-family and bool/numeric pairs,
equality, writable updates, xor and other domains remain separate. No runtime changes
or full matrix rerun; original findings and prior repair evidence remain historical.

## Guarded-string ordering repair follow-up — 2026-10-19

[The string ordering report](guarded_string.md) adds 48 signatures across all four
carriers. Operator-specific admission reuses scalar byte comparisons and checked
left snapshots. Equality, logical/unary string operations, cross-family pairs and
updates remain separate; no runtime changes or full matrix rerun. The ordinary
string suite retires only the 24 newly admitted ordering negatives; historical
findings/evidence remain intact. V-L07 stays partial. See [overall repair progress](language_repair_progress.md)
for the complete issue-ledger denominator rather than operator-only progress.

## Cross-family guarded repair follow-up — 2026-10-19

[The cross-family report](guarded_cross.md) verifies all 660 approved signatures
across the 12 ordered distinct-family pairs. Independent extraction replaces the
same-family gate; scalar rules remain authoritative. All ten bounded regressions
pass on dev002, including existing guarded and scalar/string/Result/nullable suites.
This supersedes earlier cross-family deferrals only for the implemented payload
operator sets. Equality, mixed ordering, updates, spaceship, xor and other domains
remain separate. V-L07 stays partial; no runtime changes or full matrix rerun.
Historical evidence and original findings remain intact.

## Guarded-bool XOR repair follow-up — 2026-10-19

[The bool-XOR report](guarded_bool_xor.md) verifies all 24 same-family,
cross-family and wrapper/plain signatures on dev002. Independent extraction now
admits XOR through the existing exact-bool scalar operation and eager renderer.
The proof covers both sentinel states, left-error precedence, eager RHS reach,
local/property snapshots, assignment precedence and retained recovery. Numeric XOR,
equality, mixed ordering, updates and spaceship remain separate. V-L07 stays partial;
no runtime changes or full matrix rerun. Original evidence remains unchanged.

## Guarded spaceship repair follow-up — 2026-10-19

[The spaceship report](guarded_spaceship.md) verifies all 48 canonical-int/string
signatures on dev002. Payload admission is explicitly bounded; existing scalar
decisions and eager rendering return exact int -1/0/1 after checked extraction.
The proof includes integer boundaries, byte semantics, all missing/sentinel states,
error precedence, local/property snapshots and retained repair. Other types, mixed
ordering, numeric XOR, equality and updates remain separate. V-L07 stays partial;
no runtime changes or full matrix rerun. Historical evidence remains unchanged.

## Numeric XOR repair follow-up — 2026-10-19

[The numeric XOR report](numeric_xor.md) verifies all 100 canonical int/float
signatures (four plain, 96 guarded) on dev002. Preparation retains independent truth
conversion facts; eager emission saves converted bools after checked extraction.
Signed zero, NaN/infinities, large integers, all carrier states, error precedence,
snapshots and assignment precedence are covered. Bool/numeric mixing, strings,
fixed-width types, equality and updates remain separate. V-L07 stays partial;
no runtime changes or full matrix rerun. Historical evidence remains unchanged.

## Nullable-int equality follow-up — 2026-10-19

[The nullable equality report](nullable_int_equality.md) verifies 12 equality and
identity signatures on dev002. A bounded nullable comparison owner preserves whole
operand states; existing eager emission snapshots both values before helper calls.
Empty/empty compares equal; empty/payload compares unequal, including zero, and an
empty left operand still evaluates the RHS. Source errors, state replacement,
recovery and guarded-error preservation are covered. Other carriers/payloads,
cross-family equality, mixed ordering and updates remain separate. V-L07 stays
partial; no runtime changes or full matrix rerun. Historical evidence remains intact.


## Nullable bool/string equality follow-up — 2026-10-19

[The extension report](nullable_bool_string_equality.md) verifies 24 additional
matching-payload signatures on dev002. `Nullable_Equality` retains the existing
whole-state decisions and now explicitly admits matching int/bool/string payloads.
Empty states differ from false, empty string and zero string. Native proofs cover
byte equality, eager source-error precedence, local/property snapshots and recovery.
Eleven regression suites pass. V-L07 now covers 1,524 binary and 32 unary signatures
across 12 completed slices, but remains partial; its remaining scope has no settled
percentage denominator. No runtime changes or full matrix rerun. Historical evidence
is preserved; only newly admitted negative expectations are retired.


## V-L07 remaining-signature inventory — 2026-10-19

The [static inventory](vl07_inventory/README.md) enumerates the canonical scalar
carrier space and reproduces the completed 1,524 binary plus 32 unary signatures.
It lists 524 remaining support targets and 1,872 design/scope candidates, with
explicit exclusions and separate existing paths. All 376 indexed wrapper operator
definitions are reconciled, including 144 separately owned selection definitions.
This is planning/data reconciliation, not a new admission or native verification
run. Historical failures and proof artifacts are unchanged. The proposed core
signature metric is 74.8%; it is conditional on accepting the 524-target core scope
and does not close wider V-L07 decisions or estimate engineering effort.


## E1 nullable-float equality follow-up — 2026-10-19

[The E1 report](nullable_float_equality.md) completes 12 nullable/plain matching
float equality/identity signatures. Value equality treats signed zeros as equal
and NaN as unequal; independent bit-level observers check storage preservation.
Eager errors, local/property snapshots and recovery pass on dev002. Nine regression
suites pass. V-L07 now covers 1,536 binary + 32 unary signatures across 13 slices.
The inventory moves E1 to verified: 512 support targets remain, with 1,872 design
candidates unchanged. Proposed core coverage is 75.4%; overall issue closure stays
18/64. No runtime changes or full matrix rerun. Prior evidence remains historical.


## E2R structured-error Result equality follow-up — 2026-10-19

[The Result proof](result_equality.md) completes 48 signatures over matching canonical
int/float/bool/string payloads. The comparison owner and operations now describe
wrapper-state comparisons, retaining exact carrier/payload admission and eager value
snapshots. Failure equality compares failure states; identity compares structured
error contents. All 48 signatures and 15 regression suites pass on dev002. The first
attempt's generated declaration-spacing failure is retained as fixture evidence.
V-L07 now has 1,584 binary + 32 unary signatures across 14 slices, with 464 core
support targets left. Proposed core coverage is 77.7%; the 1,872 design candidates
and overall 18/64 closed-issue count are unchanged. No runtime changes/full matrix.


## E2FB false-sentinel bool equality follow-up — 2026-10-19

[The focused report](false_bool_equality.md) verifies 12 signatures plus ten regression
suites on dev002. Two carrier operands retain sentinel/payload distinction for
ordinary equality; identity normalizes visible false. Wrapper/plain bool comparisons
follow visible bool values. Source false and explicit false_sentinel construction
remain distinct. The initial native fixture symbol-name failure is preserved.
V-L07 now has 1,596 binary + 32 unary signatures across 15 slices, with 452 core
support targets left. Proposed core coverage is 78.3%; the 1,872 design candidates
and 18/64 fully closed issues are unchanged. No runtime changes or full matrix rerun.


## E2BB two-sentinel bool equality follow-up — 2026-10-19

[The four-state proof](bool_sentinel_equality.md) verifies 12 signatures and eleven
regression suites on dev002. Both boolean sentinels remain distinct from payloads
for two-carrier equality, while identity and wrapper/plain comparisons normalize
visible bool values. Both sentinels still fail guarded negation. V-L07 now has
1,608 binary + 32 unary signatures across 16 slices; 440 core targets and 1,872
design candidates remain. Proposed core coverage is 78.8%, with 72 E2 non-bool
sentinel signatures needing their separate review. No runtime changes/full matrix.


## E2NB non-bool sentinel equality follow-up — 2026-10-19

[The bounded proof](nonbool_sentinel_equality.md) verifies 48 signatures on dev002.
Same-carrier equality and matching carrier/plain strict identity preserve states,
eager evaluation and snapshots. Separate probes confirm native instantiation failures
for all 24 deferred ordinary carrier/plain comparisons, which remain preparation
rejections. V-L07 now has 1,656 binary + 32 unary signatures across 17 slices;
392 core targets and 1,872 design candidates remain. Proposed core coverage is
81.2%. No runtime changes or full matrix rerun.


## E2 sentinel/plain equality repair — 2026-10-19

[The approved contract/runtime repair](sentinel_plain_equality.md) resolves the
24 prior native failures and completes matching-payload E2. Sentinel/plain non-bool
equality is false; present payloads compare normally. Bool behavior and snapshots
are preserved. The source proof now covers 72 non-bool signatures; progress counts
only the 24 new ones. V-L07 has 1,680 binary + 32 unary signatures in 18 slices,
368 core targets and 1,872 design candidates remaining: 82.3% proposed-core coverage.
Runtime generator/header changes were authorized; no full matrix rerun.


## U1 and V-R10 carrier updates — 2026-10-19

[The bounded update proof](carrier_updates.md) verifies 32 canonical numeric carrier
updates and closes V-R10 independently with the original nullable runtime test.
States, source value snapshots, native prefix-reference/postfix-value behavior,
stable destinations and failure ordering are preserved. V-L07 now has 1,680 binary
and 64 unary signatures across 19 slices: 83.8% proposed-core coverage, with 336
C1 compounds and 1,872 design/scope candidates remaining. Overall closure is 19/64
(29.7%); V-L07 remains partial. No indexed updates, compounds or full matrix rerun.


## Canonical-int arithmetic compounds — 2026-10-19

[The C1IA proof](int_carrier_compounds.md) verifies 48 same-carrier/plain `+= -= *= /=`
signatures on dev002, with existing extraction, mutation and write-back owners.
No runtime or emitter changes. All 18 regression suites and 14 property programs pass.
V-L07 has 1,680 binary, 64 unary and 48 compound signatures across 20 slices:
86.2% proposed-core coverage, 288 support targets and 1,872 design candidates remain.
Overall closure stays 19/64 (29.7%); V-L07 remains partial. Prior evidence is retained.


## Canonical-int remainder/bitwise/shift compounds — 2026-10-19

[The C1IB proof](int_bitwise_compounds.md) verifies 72 same-carrier/plain signatures
on dev002 through existing extraction, mutation and write-back owners. The preceding
48 arithmetic signatures and focused regressions pass. Runtime/emitter are unchanged.
V-L07 now has 1,680 binary, 64 unary and 120 compound signatures across 21 slices:
89.6% proposed-core coverage, 216 support targets and 1,872 design candidates remain.
Overall closure stays 19/64 (29.7%); V-L07 remains partial. Prior evidence is retained.


## Canonical-int cross-family compounds — 2026-10-19

[The C1IX proof](int_cross_compounds.md) verifies 120 signatures across twelve ordered
carrier pairs on dev002, using independent extraction and destination-typed wrapping.
The previous 120 same-carrier/plain compounds, focused regressions and property programs
pass. Runtime/emitter are unchanged. V-L07 now has 1,680 binary, 64 unary and 240
compound signatures across 22 slices: 95.4% proposed-core coverage, 96 support targets
and 1,872 design candidates remain. Overall closure stays 19/64 (29.7%); V-L07 remains
partial. Historical evidence is retained; no full matrix rerun.


## Exact-float compounds and proposed-core completion — 2026-10-19

[The float proof](float_compounds.md) verifies four scalar prerequisites and all 96
carrier compounds on dev002. Division_by_zero, ordered extraction, snapshots, states,
RHS effects and stable write-back are preserved. All 240 int compounds and selected
regressions pass; runtime/emitter are unchanged. V-L07 now has 1,680 binary, 64 unary
and 336 compound signatures across 23 slices: **2,080/2,080 proposed-core coverage**.
The four scalar prerequisites are separate. The 1,872 design/scope candidates and
wider domains remain; V-L07 stays partial and overall closure remains 19/64 (29.7%).
Historical failures/evidence are retained. No full matrix rerun.


## O1 mixed numeric ordering — 2026-10-19

[O1](mixed_ordering.md) verifies 192 carrier signatures plus eight scalar prerequisites
with approved int-to-float conversion, NaN behavior, eager snapshots and error order.
The original core remains 2,080/2,080; O1 brings total verified carrier coverage to
2,272 (1,872 binary, 64 unary, 336 compound), across 24 slices. Design/scope candidates
fall to 1,680. V-L07 remains partial; overall closure stays 19/64 (29.7%). No runtime
changes or full matrix rerun; historical evidence is preserved.


## C2 float-destination mixed compounds — 2026-10-19

[The directional C2 proof](widening_compounds.md) verifies 96 carrier signatures and
four scalar prerequisites with canonical int RHS promotion, division_by_zero,
snapshots and stable write-back. V-L07 now has 2,368 verified carrier signatures
(1,872 binary, 64 unary, 432 compound) across 25 slices; the original core remains
2,080/2,080. Design/scope candidates fall to 1,584. Narrowing and indexed compounds
remain separate. V-L07 stays partial and overall closure remains 19/64 (29.7%).
No runtime changes or full matrix rerun; historical evidence is preserved.


## Cross-family strict identity E3I — 2026-10-19

[The E3I repair](cross_identity.md) resolves runtime dispatch ambiguity and verifies
96 matching-payload strict-identity signatures, including state preservation and
eager snapshots. V-L07 now has 2,464 verified signatures across 26 slices and 1,488
remaining design/scope candidates. Ordinary equality and mixed payloads remain
separate. Historical runtime failure evidence is retained. Overall closure remains
19/64 (29.7%); V-L07 is partial.


## Mixed numeric strict identity E4I — 2026-10-19

[The E4I proof](mixed_identity.md) verifies 96 carrier signatures plus four scalar
prerequisites with original numeric kinds, normalized states, eager snapshots and
source failure order. Runtime support passes unchanged. V-L07 has 2,560 verified
signatures across 27 slices and 1,392 design/scope candidates remaining. Ordinary
mixed equality and other heterogeneous payloads remain separate. Overall closure
stays 19/64 (29.7%); V-L07 remains partial. No full matrix rerun.


## Bool/numeric strict identity E5BNI — 2026-10-19

[The E5BNI proof](bool_numeric_identity.md) verifies 192 carrier signatures plus
eight scalar prerequisites with independent state normalization, original payload
kinds, eager snapshots and source failure order. Runtime code is unchanged.
V-L07 now has 2,752 verified signatures across 28 slices and 1,200 design/scope
candidates remaining. Ordinary equality, strings and logical mixing remain separate.
Overall closure stays 19/64 (29.7%); V-L07 remains partial. No full matrix rerun.


## V-L07 bounded completion — 2026-10-20

[The completion goal](vl07_completion.md) verifies the final 168 approved carrier
signatures: bool cross-family equality 24, float/mixed spaceship 72, integer power
compounds 24 and string concatenation/assignment 48. Total supported coverage is
**3,376 signatures across 37 slices** (2,832 binary, 64 unary, 480 compound).
All **576 heterogeneous-equality/narrowing exclusions** have rejection/recovery
proofs. There are no unresolved canonical candidates. The 129 prior existing-path
cells reconcile to 17 supported operations and 112 observed rejections.

V-L07 is closed within this agreed finite scope; original issue closure is now
**20/64 (31.25%)**. Indexed unary and numeric compound updates, wider payloads,
boxed/table/selection semantics and the separate presence-policy boundary remain
explicit W1-W4 follow-ups with owners. Four indexed string-carrier `.=` probes pass
through the existing protocol. No full matrix or native compiler rebuild ran.

Evidence retains initial runtime/fixture failures and all repairs. Final acceptance
covers 44 suites and 4,115 expected command exits on dev002; the generated runtime
header is reproducible apart from its existing timestamp. See [exact results](vl07_completion_results.json)
and [the reconciled inventory](vl07_inventory/README.md).


## V-L06 and S04 closeout — 2026-10-20

[The focused proof](float_identity.md) adds `float === float` and `float !== float`
through existing identity preparation. Runtime support passes unchanged; signed
zeros compare identical and NaNs non-identical without throwing. Ordered snapshots,
source failures and retained recovery pass. All twelve original carrier cells and
the unchanged pinned-v0.1 S04 process fixture pass, including deterministic sentinel
controls. No newly exposed implementation blocker remains.

V-L06 and S04 close; original issue closure is **22/64 (34.375%)**. V-L07's total
stays 3,376 carrier signatures: these two new forms are scalar. Thirteen accepted
suites account for 895 expected command exits on dev002. Runtime files remain
unchanged; no full matrix or native compiler rebuild ran. Failed fixture attempts
and historical findings are preserved in [the evidence](float_identity_results.json).


## V-L11 and V-D14 text closeout — 2026-10-20

[The bounded text proof](carrier_text.md) admits ready integer widths and all four
canonical scalar carrier families for concatenation and string-destination text
updates. Present payloads match plain scalar formatting; nullable/sentinel absence
becomes empty text and structured Result failure throws. Runtime-first composition
uses existing helpers without runtime implementation changes. Contract metadata
now distinguishes concatenation from display and explicit-cast behavior.

The 924 source rows include 60 literal-null controls. States, errors, ordered
snapshots, captured destinations, write-back and recovery pass on dev002, along
with focused preservation regressions. V-L11 and V-D14 close; overall original-issue
closure is **24/64 (37.5%)**. uint64, wider payloads, new boxed/indexed combinations
and other conversion contexts remain separate. Historical findings and failed
fixture attempts remain in [the evidence](carrier_text_results.json). No full
operator matrix or native compiler rebuild ran.


## S06 conditional syntax closeout — 2026-10-20

[The S06 proof](s06_unbraced_conditionals.md) admits single-statement conditional
bodies through the existing parser/block model. Nearest-if binding, branch scopes,
lazy conditions, completion and retained recovery pass on dev002: 46 dedicated
native programs, 23 rejection controls, three span checks and eight recovery cycles.
Existing regressions pass 12 PHP suites and 137 native programs.

The unchanged original fixture advances from a required-brace parser failure to
unresolved `strpos`, recorded under S09. It is not an end-to-end compatibility pass.
S06 closes; overall original-issue closure is **25/64 (39.0625%)**. Other statement
syntax remains separate. No runtime changes, full matrix or native compiler rebuild
ran. Historical findings and failed test-observer attempts remain in the evidence.

## S07 exact-mixed predicate closeout — 2026-10-20

[The S07 proof](s07_mixed_predicates.md) exposes `is_bool`, `is_int` and `is_float`
for exact `mixed` inputs through the existing non-retaining observation contract.
The original strict fixture passes unchanged with exact output. Runtime-first and
source probes each cover 69 predicate checks across 23 mixed states. Readonly and
temporary inputs, temporary owners, once-only evaluation, saved results, mutation,
errors, 47 rejection controls and six recovery cycles pass on dev002. Existing
observation, mixed and conditional suites add 375 native regression programs.

Only core metadata and documentation changed in production; compiler and runtime
implementations remain unchanged. S07 closes, bringing original-issue closure to
**26/64 (40.625%)**. Wider types, additional predicates and flow-sensitive narrowing
remain separate; the frozen original finding above remains historical evidence.

## S09 byte-search slice — 2026-10-20

[The search proof](s09_byte_search.md) admits two/three-argument `strpos` and
`strrpos` with normative byte positions, signed offsets and false-sentinel results.
Dedicated adapters preserve the historical code-point helpers used by portability.
The unchanged string-edge fixture passes exact output. Runtime and source checks
each cover 1,760 calls, including arbitrary bytes, empty needles, extreme offsets,
errors and input preservation; source observers prove snapshots and evaluation order.

The original false-branch fixture now reaches **S09-B1**: direct passage of
`result_or_false<int>` to `substr`'s integer parameter is rejected at exact-signature
admission. Explicit extraction works, but no implicit conversion or narrowing repair
was made. `substr_replace` remains deferred for separate semantic reconciliation.
S09 is partial; fully closed original issues remain **26/64 (40.625%)**, with one
partial repair and 37 other open issues. Original failure records remain historical.

## S09 replacement slice — 2026-10-20

[The replacement proof](s09_substr_replace.md) completes both `substr_replace`
signatures and reconciles shared runtime behavior with the normative byte contract.
Omitted or zero length inserts; unusable negative-length windows preserve the input.
The full original strict string-surface fixture passes unchanged with exact output.
Runtime and compiled-source observers each pass 3,312 checks, including extrema,
arbitrary bytes, aliasing and input preservation. Source observers also prove
argument snapshots, evaluation order, no-op argument evaluation, error propagation,
11 invalid calls and two recovery cycles. Focused string/search regressions pass.

All six search/replacement API signatures and both original S09 fixtures now pass.
S09 remains partial for separately recorded S09-B1 in the false-branch fixture;
no compiler conversion or narrowing rule was changed. Fully closed original issues
remain **26/64 (40.625%)**, with one partial repair and 37 other open issues.

## Closure batch follow-up — 2026-10-20

[S09 / V-R07 / V-H10](closure_batch.md) are implemented and verified on dev002.
S09-B1 closes through bounded checked-payload overload selection. Eight vector
forms pass strict behavior and production builds; the separately authorized
provider copy requirement rejects moved noncopyable fills. V-H10 reaches the
intended unsupported boxing boundary while typed callable storage passes.
Current progress is **29/64 (45.3125%)**, zero partial and 35 other open issues,
**+4.6875 percentage points**. Dated earlier findings/counts remain historical.
No runtime implementation changes, full matrix or native compiler rebuild.

## Regex filter/limit closeout — 2026-10-20

[V-R04 and V-R09](regex_replacement.md) are implemented and verified on dev002.
Strict filter supports positional backreferences and both arities; replacement
limits have an unambiguous provider binding. Limit-before-pattern validation now
also runs for empty input and zero limit, and zero-length replacement preserves
untouched bytes. Runtime/source oracles and production PCRE2 builds pass.
Current progress is **31/64 (48.4375%)**, zero partial and 33 other open issues,
**+3.125 percentage points**. Earlier counts remain dated historical checkpoints.
Compiler implementation, callback APIs and legacy adapter source are unchanged.

## Platform query and qualified aliases closeout — 2026-10-20

[V-D09 and V-D12](platform_alias.md) are implemented and verified on dev002.
Free and explicit `php::microtime` calls use the original runtime identities;
visible source types win and missing methods never fall back. The unchanged
strict fixture and retained type-add/remove/recovery proofs pass. `fs_is_windows`
restores the v0.1.77 target-host contract. `getenv` now has normative authority and
rejects embedded NUL before lookup while preserving missing/empty/copied values.
Current progress is **33/64 (51.5625%)**, zero partial and 31 other open issues,
**+3.125 percentage points**. Earlier findings/counts remain historical. General
namespaces, other aliases and native Windows evidence remain separate; no full
matrix or native compiler rebuild.

## Boxed integer / wrapper coalescing closeout — 2026-10-20

[V-L09 and V-L10](boxed_selection.md) close within the approved homogeneous scope:
12 boxed integer forms and 12 canonical-scalar wrapper coalesces. Boxed families,
ordered snapshots and lazy usable-value selection are preserved. Runtime-first
probes exposed absent dynamic operators and wrong homogeneous coalesce result
types; the user subsequently authorized both bounded runtime repairs. Source,
retained recovery, focused native regressions and production cold/warm builds
pass on dev002. No full matrix or native compiler rebuild was run.

At the boxed/selection checkpoint, progress was **35/64 (54.6875%)**, zero partial and 29 other original issues,
**+3.125 percentage points**. Separate open finding **BS-R01** records unchecked
primitive shift counts: -1 and 64 trigger UBSan in scalar, mixed and dynamic
controls. No safe invalid-count behavior is claimed or repaired here. Earlier
findings/counts remain historical; wider pairs and updates remain separate.


## Checked shifts and compact layout repair — 2026-10-20

[V-L01 and V-L02](compact_layout_shifts.md) close restricted union declarations and
all four prepared layout queries. The original LV03.004 failure and frozen issue
findings above remain historical; the repair proves recursive payload restrictions,
explicit member construction and copy/write semantics, imported/cross-file layouts,
source-function shadowing and retained error/retry/repair behavior. General reflection,
managed unions and external ABI promises remain separate.

Supplementary **BS-R01** closes under the separately approved `ValueError` policy
for negative or width-or-greater shift counts. Native/source checks preserve guarded
extraction precedence, operand snapshots and failed compound write-back. The old
UBSan reproductions remain archived, not current acceptance expectations.

Current progress is **37/64 (57.8125%)**, zero partial and 27 other original issues:
**+3.125 percentage points**. Dev002 verification records 192 acceptance executions
plus one baseline comparison, 49 rejected source cases, 29 PHP regression checks,
175 native regression programs and cold/warm production builds. No full operator
matrix or native compiler rebuild ran.

**CL-H01** is separate harness debt: the old `enums.py` expects SIGABRT for an
unhandled match, while both the pre-goal compiler and this candidate produce the
existing caught diagnostic and exit 1. The combined goal harness verifies the actual
contract; the original runner is unchanged. This neither reopens a compiler issue
nor inflates the frozen denominator. Normalized PHP union parameter syntax is also
distinct from V-L01's payload unions; an older catalog cross-reference was corrected.

## Regex callback and CMake composition closeout — 2026-10-20

[V-R05/V-R11](callback_composition.md) are implemented and verified on dev002.
The original issue inventory now has **39/64 closed (60.9375%)**, zero partial and
25 open; this batch adds **3.125 percentage points**. Four callback source forms,
runtime validation/traversal, snapshots and six static/shared CMake configurations
pass. The baseline unresolved calls, nine runtime oracle failures and aggregate-link
failure remain in the evidence. No full matrix or native compiler rebuild ran.
MySQL/legacy-adapter compilation is not database integration or a V-E03 closure.

## Typed tokenizer and strict IO closeout — 2026-10-20

[S11/S13](tokenizer_io.md) are implemented and verified on dev002: **41/64 closed
(64.0625%)**, zero partial and 23 open, adding **3.125 percentage points**. The
original tokenizer fixture and both existing declaration-only IO migrations pass
under default template prelowering. Eight production cases pass cold/warm builds;
five ordinary sanitizer controls, 190 runtime checks, fourteen source rejections
and three retained recovery cycles pass, with focused representation regressions.
The IO provider repair preserves embedded NUL bytes in line reads.

Authorized prelowering prerequisites use the existing metadata, representation and
compiled-operation owners. The proposed nullable schema extension was unnecessary
and was not implemented. Original failures and intermediate transport gaps remain
archived; source exception unwind is verified in ordinary mode, while concrete
source exception transport remains outside scope. S02 legacy syntax stays deferred.

The earlier callback and compact-layout repairs were also reviewed under the new
default. Their production probes now explicitly reject missing concrete callable
and compiler-helper transport. Those separate follow-ups do not invalidate their
historical ordinary-mode evidence, and this repair count is not a claim that all
41 scopes have been reverified under default prelowering.

## Strict curl closeout — 2026-10-20

[V-R03/V-E02](curl_integration.md) close with **43/64 closed (67.1875%)**, zero
partial and 21 open: **+3.125 percentage points**. All 15 production cases pass on
dev002 under default prelowering, cold/warm. Five original strict fixtures compile
in both versions; four local executions match their original exact oracles. The
external HTTPS fixture remains compile-only. Declaration, argv/stabilization and
JSON-family migrations are recorded separately, without editing original sources.

All nine names/13 forms, provider fields/constants, states, snapshots, write-back,
header-vector and bounded mixed index/conversion transport pass. Provider discovery
and explicit unavailable-libcurl failure pass, alongside focused recovery checks.
Earlier runtime sanitizer evidence is retained; no runtime implementation changed.
No full matrix or native compiler rebuild ran. Historical blocked rows above remain
original verification evidence, superseded only in this approved bounded scope.
