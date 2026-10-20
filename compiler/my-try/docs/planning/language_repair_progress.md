# Overall discovered-issue repair progress
Doc Status: supporting

Snapshot: 2026-10-20, after [V-L07 bounded completion](vl07_completion.md) and
[V-L06 / S04 closeout](float_identity.md) and [V-L11 / V-D14 text](carrier_text.md), followed by [S06 conditionals](s06_unbraced_conditionals.md) and [S07 predicates](s07_mixed_predicates.md). The [S09 search slice](s09_byte_search.md) is complete; S09 remains partial. This covers all 64 original entries in
[the issue ledger](language_verification_issues.json), including deferred legacy
syntax and policy/design issues. Operator signatures are not separate closed issues.

| Status | Issues |
| --- | ---: |
| Implemented or already repaired, then verified | 26 |
| Partially implemented and verified | 1 |
| Other issues not yet closed | 37 |
| Total | 64 |

Fully closed: **26/64 = 40.625%** (about **40.6% by issue count**). This is not an
engineering-effort estimate. Remaining architecture/runtime/project issues can be
much larger than fixture repairs. There is no defensible weighted effort percentage.

| Original handling class | Fully closed | Partial | Not closed | Total |
| --- | ---: | ---: | ---: | ---: |
| Simple fix | 13 | 0 | 0 | 13 |
| Light discussion | 10 | 1 | 6 | 17 |
| Normal discussion | 3 | 0 | 31 | 34 |

V-L06 now admits the final two scalar float identity forms and re-verifies its
original carrier cases. S04's unchanged original process fixture passes through
existing repairs. These closures do not add to the V-L07 carrier-signature count.
V-R10's runtime mutation prerequisite remains separately repaired and verified.
V-L11 and V-D14 now close the approved ready-width/canonical-carrier text domain.
Their 924 source rows include existing controls and aliases; they do not rewrite
the dated V-L07 canonical inventory or imply wider text/indexed support.
S06 now admits single-statement conditional bodies. Its unchanged original fixture
now reaches S09-B1 (wrapper-to-payload runtime argument admission), so syntax
closure is not a whole-program pass. The earlier unresolved-strpos checkpoint
remains dated evidence.
S07 now exposes three exact-mixed predicates through non-retaining observation.
Its unchanged original strict fixture passes end to end; wider predicate domains
and flow-sensitive narrowing remain separate.
S09 now admits four byte-search signatures and passes the unchanged string-edge
fixture. Replacement semantics and the newly exposed S09-B1 call boundary remain
open; this partial repair adds no fully closed issue.

“Not closed” does not mean every remaining entry is an accidental regression or an
authorized implementation task. Some entries still require policy decisions or
reconciliation against later work. Original findings and historical evidence remain
in the ledger. This current summary supersedes its previous E4-era partial-V-L07
status; the dated reports retain those historical counts.

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
