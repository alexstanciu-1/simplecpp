# Portability review debt
Doc Status: planning

[Conversion status](conversion_review.md) separates recorded proof from current
claims. [Incremental/v0.2 debt](../planning/incremental_strategy.md) owns language,
selection, cleanup and partitioning work; do not duplicate that backlog here.

| Owner | Remaining review |
| --- | --- |
| Source reads | Metadata/read consistency and filesystem failure boundaries; mtime/size limitation remains accepted. |
| Token representation | Per-token object/text costs, byte spans and retained-buffer bounds; measure before replacing handles with views. |
| AST/scopes | Native lifetime across retained/replaced/retired graphs; explicit scope owners, weak acquisition and observer survival. |
| Collected/prepared records | Required publication fields, typed weak returns, dependency/index cleanup and strong cycles. |
| Native analysis | Constructor delegation and externally staged initialization; use meaningful rejected-read proofs, not dummy initialization. |
| Host boundary | Reporting, process/resource APIs and truthful dynamic-result stabilization; distinguish host-only code from conversion inputs. |
| Parked LLVM | Preserve regression contracts. Its token-index maps and preparation ownership await shared-fact convergence, not new semantic work. |

## Explicit nullability audit

For a touched field, follow construction, assignment, read, publication, failure and
reset. Required fields must be initialized before use; `?T` is for meaningful absence.
Empty collections remain required. Weak-reference intent does not imply optionality.

Check both missing initialization and unnecessary nullable fields. PHP typed-property
failures, converter acceptance and STAN reasoning are separate evidence. Scope/link
expiration and actual concurrent publication need native tests. The
[earlier initialization audit](../archive/initialization_audit.md) records historical
field decisions, not a current per-class inventory.

## Representation discipline

Preserve shared record identity and stable sparse positions. Use explicit container
and compact-integer annotations only within supported bounds. Indexes are references,
not additional semantic owners. Do not introduce value-record promotion, ID tables,
serialization or collection compaction as incidental portability fixes.

Do not remove narrowing casts solely because PHP accepts the assignment. Keep actual
class narrowing separate from same-type nullable extraction and report unsupported
native forms to their converter/runtime/STAN owner.

## Legacy S2S covariance and source convention

Project builds now supply ancestor/accessor signatures to the legacy emitter, with
cache invalidation for signature changes. Split-file named-object accessor dispatch
is covered by `tests/tools/test_scpp_cross_file_covariance.py`. The compiler still
uses invariant expression accessor returns and distinct specialized accessor names.
See the [generator boundary](../../../../generators/php/specs/rules_catalog.md#cross-file-covariant-object-accessors).

File-local class imports now expand at declaration/expression use sites rather than
emitting shared C++ aliases. The separate-file native proof is
`tests/tools/test_scpp_cross_file_import_aliases.py`; header dependencies remain explicit.

## Native and STAN checkpoint (2026-09-30)

The earlier candidate `11d70185` plus declaration-identity normalization passed
193/236 broad comparisons; its 43 failures involved struct lookup or related rejection
diagnostics. After the shared-identity repair on `cf14bc1a`, native validation passes:
15 scalar and 36 function/struct S2S executions, 27 S2S rejections, all 142 parked
LLVM/sample comparisons (48 executions, 94 rejections), the initial S2S smoke proof,
repeated-compilation/recovery checks and incremental rebuild. STAN was explicitly
skipped. Evidence: `/tmp/my-try-native-20260929-bfd12958/summary.json` and `logs-11/`.

The native identity blocker is repaired in the shared runtime: compatible base/derived
shared handles compare adjusted object pointers, including nullable normalization.
`Scope_Publication::register()` can now match `?collected_struct` with `collected_name`
without a compiler-side cast or algorithm change. The focused runtime test covers
both comparison directions, distinct objects, empty/present nullable values, and
multiple/virtual inheritance; it passes with Clang and GCC. Unrelated static interface
views retain the existing fallback and are not expanded by this slice.

STAN analyzes 71 converted units in about 3.6 seconds and reports 1,049 diagnostics
(70 compile-error bucket, 760 STAN-error bucket, 219 warnings). These are analyzer
classifications, not 70 observed C++ compilation failures. Triage order:

1. Runtime/container type recognition: 421 unresolved dependencies, mainly `Storage`,
   `nullable`, `shared`, `Key_Storage_List`, `Keyed_Storage` and `weak`.
2. Namespace-aware resolution: two `Token_Buffer` ambiguities conflate the compiler's
   declaration with a runtime declaration in another namespace.
3. Ordinary override compatibility: 91 override diagnostics; the current exemption
   only recognizes eligible zero-argument object accessors, so identical parameterized
   hooks are flagged too.
4. Reassess type flow after those repairs: casts/weak acquisition, derived/base returns,
   wrapper boundaries and initialization. Do not suppress all diagnostics or add dummy
   initialization; some may still indicate real source issues.

Detailed report: `/tmp/my-try-native-20260929-bfd12958/phpp/.prism/cache/stan_report.json`.
STAN repair remains a separate follow-up; no analysis behavior changed in this slice.
