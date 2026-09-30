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

Candidate `11d70185` plus declaration-identity normalization builds the native compiler
with `--no-stan`. Broader PHP/native parity and generated-program execution:
193/236 pass (S2S 71/94; parked LLVM/sample 122/142). The 43 failures involve struct
lookup, including 16 rejection-diagnostic mismatches. This is not a full native pass.
Evidence: `/tmp/my-try-native-20260929-bfd12958/{broad-sweep,llvm-sweep}/summary.json`.

Priority native blocker: `Scope_Publication::register()` compares
`?collected_struct` with `collected_name`. Runtime strict identity compares same-type
shared handles by pointer but returns false for different handle types, including a
derived/base view of the same object. Struct definitions consequently fail publication.
A standalone native probe confirms both direct and nullable-wrapped derived/base
comparisons return false. Review the object-identity contract/runtime boundary before
choosing a runtime correction or explicit source normalization; also inspect removal
comparisons. No parked LLVM semantic changes are needed to investigate this shared path.

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
