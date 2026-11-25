# Nullable JSON diagnostic reference — 2026-10-25
Doc Status: planning

Approved binding correction: json_document_parse takes a nullable json_parse_error
reference, matching the runtime reset-before-parse and diagnostic-on-failure behavior.
The portability wrapper and current API fixtures declare nullable diagnostic storage.
No native representation, parser behavior, or reference variance changes.

The production metadata passes v0.1 and ordinary/concrete v0.2 native execution:
success leaves null, duplicate-key failure gives category/offset, later success clears
the old diagnostic, and a separately retained diagnostic remains valid. Initial proof
attempts exposed fixture-only legacy restrictions on take(nullable class) and top-level
integer returns; the final common fixture uses guarded handle assignment and a typed
function. No compiler workaround was added.

Measured bootstrap: 1:31.76 / 633640 KiB RSS / 600.5 MiB PHP heap.
Required-null failure clears; only nonpolymorphic construction-fact narrowing remains.
Native compiler/second-generation acceptance remains outstanding.
