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
