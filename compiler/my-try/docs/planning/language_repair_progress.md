# Overall discovered-issue repair progress
Doc Status: supporting

Snapshot: 2026-10-22, after [V-L07 bounded completion](vl07_completion.md) and
[V-L06 / S04 closeout](float_identity.md) and [V-L11 / V-D14 text](carrier_text.md), followed by [S06 conditionals](s06_unbraced_conditionals.md) and [S07 predicates](s07_mixed_predicates.md). The [S09 search](s09_byte_search.md) and [replacement](s09_substr_replace.md) slices are complete. The [closure batch](closure_batch.md) closes S09-B1, V-R07 and V-H10; the [regex batch](regex_replacement.md) also closes V-R04 and V-R09. The [platform/alias batch](platform_alias.md) closes V-D09 and V-D12. The [boxed/selection batch](boxed_selection.md) closes V-L09 and V-L10. The [compact-layout batch](compact_layout_shifts.md) now closes V-L01 and V-L02, plus supplementary BS-R01. The [callback/composition batch](callback_composition.md) closes V-R05 and V-R11. The [tokenizer/IO batch](tokenizer_io.md) closes S11 and S13 with default prelowering verified. The [strict curl batch](curl_integration.md) closes V-R03 and V-E02. The [debug/closure batch](scalar_debug_closure_reconciliation.md) implements S08 and reconciles S05 as an intentional v0.2 restriction. The [project/startup batch](project_startup_metadata.md) implements V-P01 and reconciles V-D10. The [checked span/hash batch](span_hash.md) implements S10 and V-R06. The [verification environment batch](verification_environment.md) reconciles V-E01 and closes V-E03 with a real isolated database proof. The [trait/ownership batch](trait_ownership.md) closes V-D03 and V-D04. The [collection/advisory batch](collection_advisories.md) closes V-D11 and V-D15. This covers all 64 original entries in
[the issue ledger](language_verification_issues.json), including deferred legacy
syntax and policy/design issues. Operator signatures are not separate closed issues.

| Status | Issues |
| --- | ---: |
| Implemented or already repaired (including harnesses), then verified | 56 |
| Contract/environment reconciled and verified | 4 |
| Partially implemented and verified | 1 |
| Other issues not yet closed | 3 |
| Total | 64 |

The [V-R08 batch](ui_webview_integration.md) implements all 24 UI/WebView source APIs
and host build integration. Its default-prelowered disabled-provider, snapshot and
recovery proofs pass; enabled GTK/WebKitGTK build and GUI acceptance remain unavailable.
V-R08 is therefore partial, not an extra closure. The V-D07/V-D08 source audit
does not close either issue. The subsequent [enum-wrapper slice](enum_wrappers.md)
implements nullable/structured Result enum payloads and makes V-D08 partial.
The subsequent [helper/presence transport slice](enum_helper_transport.md) repairs
VD08-H01/H02 for three helpers and plain enum wrappers. Wider extensions and
indexed/general probes remain separate. The [sentinel-carrier slice](enum_sentinel_carriers.md)
adds falseable/boolean Result enum state transport and true-sentinel presence;
these bounded repairs add no original issue closure. The [closeout audit](enum_compatibility_closeout.md)
reconciles all eight original probes but confirms VD08-A01, shared default-less-match
failure transport. The subsequent [VD08-A01 repair](match_failure_transport.md)
verifies that failure path, matched execution, snapshots and cleanup with default
prelowering and closes V-D08 (+1.5625 percentage points). Wider extensions stay separate.

The [V-D05/V-D07 call audit](call_compatibility_audit.md) reconciles V-D05 against
pinned v0.1 execution/build evidence (+1.5625 points). V-D07 remains open for the
reference-pack spelling decision. The [reference-default repair](reference_defaults.md)
closes VD07-A01 in canonical scalar scope: read-only and writable defaults preserve
binding/snapshots/lifetime, with escaping default references rejected. The preceding
[VD07-A02 repair](value_pack_transport.md) verifies scalar value-pack prelowering
without adding an original-issue closure.
The remaining original entries are V-D01, V-D07, S02 and partial V-R08.

Resolved: **60/64 = 93.75%** (by issue count). This is not an
engineering-effort estimate. The completed async/tasks goal closed S12 and V-R02 (+3.125 points). The subsequent [V-D06 goal](finally_value_transfers.md) closes finally value/loop transfers and default-prelowered exception transport (+1.5625 points). S05 does not certify explicit callable storage under default prelowering. Remaining architecture/runtime/project issues can be
much larger than fixture repairs. There is no defensible weighted effort percentage.

| Original handling class | Fully closed | Partial | Not closed | Total |
| --- | ---: | ---: | ---: | ---: |
| Simple fix | 13 | 0 | 0 | 13 |
| Light discussion | 17 | 0 | 0 | 17 |
| Normal discussion | 30 | 1 | 3 | 34 |

V-L06 now admits the final two scalar float identity forms and re-verifies its
original carrier cases. S04's unchanged original process fixture passes through
existing repairs. These closures do not add to the V-L07 carrier-signature count.
V-R10's runtime mutation prerequisite remains separately repaired and verified.
V-L11 and V-D14 now close the approved ready-width/canonical-carrier text domain.
Their 924 source rows include existing controls and aliases; they do not rewrite
the dated V-L07 canonical inventory or imply wider text/indexed support.
S06 now admits single-statement conditional bodies. Its unchanged original fixture
now passes after the subsequent S09-B1 call-boundary repair. The earlier
unresolved-strpos and exact-signature checkpoints remain dated evidence.
S07 now exposes three exact-mixed predicates through non-retaining observation.
Its unchanged original strict fixture passes end to end; wider predicate domains
and flow-sensitive narrowing remain separate.
S09 now admits four byte-search and two replacement signatures. Both original S09
fixtures (string surface and edges) pass unchanged. S09-B1 now closes through
bounded checked-payload runtime overload selection; the false-branch fixture also
passes unchanged. V-R07 exposes all eight vector forms with explicit provider copy
requirements. V-H10 reaches its intended closure-boxing rejection and preserves
typed callable storage support. This batch adds three closures, **+4.6875 points**.

V-R04 and V-R09 now expose strict filter and value-limit replacement forms with
shared validation and preserved zero-length output bytes. The regex batch adds
two closures, **+3.125 points**, with production PCRE2 builds verified on dev002.
Callback APIs, count outputs and legacy filter migration remain separate.

V-D09 and V-D12 now close the bounded qualified microtime aliases, restored target
platform query and NUL-safe environment-name contract. Source-type shadowing and
retained failure/recovery pass on dev002, alongside native cold/warm production
builds. This batch adds two closures, **+3.125 points**. Other aliases, general
namespaces and actual Windows execution remain separate.

V-L09 and V-L10 close the approved homogeneous boxed integer expressions and
canonical-scalar wrapper coalesces, adding **+3.125 points**. Runtime-first probes
exposed two prerequisites, both subsequently authorized and repaired. Dev002
source/state/effect/recovery and production builds pass. Its BS-R01 invalid-count
reproductions remain historical evidence; wider pairs/updates remain separate.

V-L01 and V-L02 now close restricted payload unions and all four prepared layout
queries, adding **+3.125 points**. BS-R01 also closes with the approved `ValueError`
count policy, outside the frozen denominator. Snapshots, extraction precedence,
failed write-back, union lifetime/copy behavior, native layout, retained recovery
and production cold/warm builds pass on dev002. The separate CL-H01 finding records
an older enum runner's stale SIGABRT expectation; it does not add an original
issue closure. No full matrix or native compiler rebuild was run.

V-R05 and V-R11 now close strict regex callback bindings and CMake runtime
composition, adding **+3.125 points**. Four callback forms, shared zero-length
replacement traversal, ordered snapshots and six static/shared CMake configurations
pass on dev002. Baseline failures remain evidence; MySQL compilation does not close
V-E03 database integration. See [batch closeout](callback_composition.md).

“Not closed” does not mean every remaining entry is an accidental regression or an
authorized implementation task. Some entries still require policy decisions or
reconciliation against later work. Original findings and historical evidence remain
in the ledger. This current summary supersedes its previous E4-era partial-V-L07
status; the dated reports retain those historical counts.

S12 now closes through [async suspension and publication proof](async_tasks.md):
unchanged strict source, default-prelowered production builds, suspension/lifetime
and ordered/lazy evaluation, plus retained disk rejection/repair/retirement/fresh
parity. V-R02 closes all 21 names and the approved typed/boxed signature groups,
including callbacks, coordinator errors, lifecycle and retained/fresh proofs.
Boxed custom placement remains explicitly unsupported in v0.2 by user decision;
V-D06 now also verifies source exception transport and repeated-error joins; wider transfer/project domains remain separate.
See the [acceptance inventory](task_acceptance_inventory.md) for exact boundaries.

## Inside V-L07

**37 approved slices complete: 2,832 binary, 64 unary and 480 compound signatures
(3,376 supported carriers).** All canonical candidates have an agreed disposition:
576 intentional heterogeneous-equality/narrowing rejections, 17 verified existing
paths and 112 separately observed presence-path rejections. Aliases are distinct
source spellings; scalar prerequisites are excluded from carrier totals.

| Implemented domain | Binary | Unary | Compound |
| --- | ---: | ---: | ---: |
| Canonical int, same carrier/plain (first two slices) | 228 | 16 | 0 |
| Float, same carrier/plain | 144 | 12 | 0 |
| Mixed numeric, same family/plain | 192 | 0 | 0 |
| Bool logical, same carrier/plain | 48 | 4 | 0 |
| String ordering, same carrier/plain | 48 | 0 | 0 |
| Cross-family guarded operators | 660 | 0 | 0 |
| Guarded bool XOR | 24 | 0 | 0 |
| Canonical-int/string spaceship | 48 | 0 | 0 |
| Guarded numeric XOR | 96 | 0 | 0 |
| Nullable int equality/identity | 12 | 0 | 0 |
| Nullable bool/string equality/identity | 24 | 0 | 0 |
| Nullable float equality/identity | 12 | 0 | 0 |
| Structured-error Result equality/identity | 48 | 0 | 0 |
| False-sentinel bool equality/identity | 12 | 0 | 0 |
| Two-sentinel bool equality/identity | 12 | 0 | 0 |
| Non-bool sentinel equality/identity | 48 | 0 | 0 |
| Sentinel/plain non-bool equality | 24 | 0 | 0 |
| Numeric carrier prefix/postfix updates | 0 | 32 | 0 |
| Canonical-int arithmetic compounds, same carrier/plain | 0 | 0 | 48 |
| Canonical-int remainder/bitwise/shift compounds, same carrier/plain | 0 | 0 | 72 |
| Canonical-int cross-family compounds | 0 | 0 | 120 |
| Exact-float compounds, all carrier/plain arrangements | 0 | 0 | 96 |
| Mixed int/float ordering | 192 | 0 | 0 |
| Float-destination mixed compounds | 0 | 0 | 96 |
| Cross-family matching-payload strict identity | 96 | 0 | 0 |
| Mixed int/float strict identity | 96 | 0 | 0 |
| Bool/numeric strict identity | 192 | 0 | 0 |
| Heterogeneous string strict identity | 288 | 0 | 0 |
| Mixed numeric nullable/Result ordinary equality | 24 | 0 | 0 |
| Mixed numeric sentinel ordinary equality | 24 | 0 | 0 |
| Matching non-bool cross-family ordinary equality | 72 | 0 | 0 |
| Mixed-numeric cross-family ordinary equality | 48 | 0 | 0 |
| Cross-family matching-bool ordinary equality | 24 | 0 | 0 |
| Float/mixed spaceship | 72 | 0 | 0 |
| Canonical-int power compounds | 0 | 0 | 24 |
| String-carrier concatenation and assignment | 24 | 0 | 24 |
| Total | 2,832 | 64 | 480 |


The original proposed core remains **2,080/2,080 (100%)**. The agreed canonical
scope is complete; **1,296** supported carrier signatures were added beyond that
original core. See the [inventory](vl07_inventory/README.md) for exact dispositions.

V-L07's W1-W4 follow-ups remain explicit: indexed unary/numeric compound updates,
other null-comparison policy and wider fixed-width/nested/object/container/boxed/table/
selector domains. The 10,767 wider grid cells are not silently counted as tested
rejections or permanent language exclusions. Closing the finite issue scope does
not close those broader language capabilities.

## Tokenizer/IO and the prelowering integration boundary

S11/S13 add **3.125 percentage points**. Eight default-prelowered production cases
pass cold/warm builds, alongside five ordinary sanitizer controls, 190 runtime
checks, rejection/recovery and representation regressions. The source exception
unwind proof remains ordinary-mode; concrete source exceptions are outside this
goal. S02 declaration syntax is still deferred.

The original repair count retains each earlier approved proof scope. It does not
mean every historical repair passes the newly merged default prelowering mode.
Final-snapshot review confirms separate gaps for regex callback transport and
compact layout compiler-helper transport. Their prior evidence is preserved; see
[the mode-specific review](tokenizer_io.md#review-of-earlier-work-under-the-new-default).

V-P01 now composes strict manifest projects through the production CLI with the
existing dependency/export policies and native cache. V-D10 is reconciled against
immutable startup metadata: disk edits require a new session, live replacement
rejects, source edits preserve the snapshot and reset retires old identities.
The batch adds **+3.125 points**, comprising one implementation and one contract
reconciliation. Full build-planner/packaging parity and hot reload remain separate.

V-D11 restores six collection helpers and bounded owned value-callback transport.
V-D15 supplies retained reference advisories, including focused PHP/native compiler
parity. Thirty-nine focused tasks, including remote snapshot integration, and the native
parity gate pass on dev002. This batch
adds **+3.125 points**; initializer/indexing and wider callback gaps remain explicit
in [the closeout](collection_advisories.md).
