# Overall discovered-issue repair progress
Doc Status: supporting

Snapshot: 2026-10-20, after E4 mixed-numeric cross-family ordinary equality. This covers all 64
entries in [the issue ledger](language_verification_issues.json), including deferred
legacy syntax and policy/design issues. It does not count just operators or count
individual operator signatures as separate closed issues.

| Status | Issues |
| --- | ---: |
| Implemented and verified | 19 |
| Partially implemented and verified | 1 (V-L07) |
| Not yet repaired | 44 |
| Total | 64 |

Fully closed: **19/64 = 29.7%**. A rounded progress estimate allowing for substantial
partial V-L07 work is **about 30% by issue count**. This is not an estimate of
engineering effort completed: the remaining design/runtime/project issues can be
much larger than the completed fixture repairs. There is no defensible weighted
effort percentage yet.

| Original handling class | Fully closed | Partial | Not repaired | Total |
| --- | ---: | ---: | ---: | ---: |
| Simple fix | 13 | 0 | 0 | 13 |
| Light discussion | 5 | 0 | 12 | 17 |
| Normal discussion | 1 | 1 | 32 | 34 |

V-L07 now has verified guarded canonical-int, float, mixed-numeric, bool logical and
string ordering slices, including boolean-sentinel Result support and all 660
approved cross-family signatures, 24 guarded-bool XOR signatures and 48 guarded
canonical-int/string spaceship signatures, plus 100 plain/guarded numeric XOR
signatures and 48 nullable, 48 structured-error Result and 24 sentinel-family bool and 72 non-bool sentinel equality/identity signatures. Carrier updates add 32 unary signatures. V-L07 is still
one partially repaired issue.
Cross-family and mixed ordinary equality semantics, remaining compounds and
additional payload/operator domains remain separate. V-R10's
runtime mutation gap is repaired and verified separately by its original native test.

The denominator includes explicitly deferred scope; “not repaired” does not mean
all 44 are accidental regressions or authorized implementation tasks. The issue
ledger's disposition, contract and next-action fields retain those distinctions.
Original verification findings and historical evidence are preserved.


## Inside V-L07

**33 approved slices completed: 2,712 binary, 64 unary and 432 compound
signatures (3,208 total).** E4 adds 48 mixed-numeric cross-family ordinary-equality signatures. There are no new scalar prerequisites.
Aliases count as separate source spellings; the four plain numeric-XOR signatures
are prerequisites and excluded from this V-L07 total.

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
| Total | 2,712 | 64 | 432 |

Remaining work includes cross-family bool and other heterogeneous ordinary equality semantics, remaining compounds, and other payload/operator domains. The [remaining-signature inventory](vl07_inventory/README.md) now enumerates the
canonical scalar universe: **zero remaining support targets** and **744 design/scope
candidates**, with exclusions and wider domains kept explicit. The original proposed core remains
**2,080/2,080 signatures (100%)**, with 1,128 additional approved O1/C2/E3I/E4I/E5BNI/E5SI/E4NR/E4S/E3NB/E4 signatures. This completes the bounded support inventory, not
all V-L07: design candidates and wider-domain boundaries still need disposition.
Signature counts measure verified coverage, not engineering effort. The overall 29.7% figure above measures
closed issues across the entire ledger, not progress within V-L07.
