# Remaining portable source boundaries — 2026-10-25
Doc Status: planning

Typed special-operation list; required observation enum local; optional runtime storage
extraction; explicit nullable payload return; base-typed default pattern and trait
member locals. Existing semantics only. Trait properties/methods/constants keep order.

Three cross-route proof sets pass PHP, v0.1 native and both v0.2 native modes:
production list ordering; trait member ordering/identity and shared views; extracted
runtime optional queries/observation/default-pattern statements. Host type_catalog,
type_capabilities, incremental_preparation, trait_instances and traits pass.
nullable_handles fails at an old required-null case and runtime_generics fails while
parsing a reference default; both failures reproduce on unchanged parent sources.
Logs are retained separately; do not count those suites as passed.

Completed owner-instrumented bootstrap: 1:29.97 / 633292 KiB RSS / 600.5 MiB heap;
categories 7 -> 3. Five failing owners remain: scpp::json_read,
Call_Snapshot_Preparation::prepare, CPP_Data_Construction::generate,
CPP_Default_Constructors::decision, CPP_Runtime_Catalog::install.
Timing includes the temporary deduplicated observer; source semantics are unchanged
by logging. No native compiler/second-generation acceptance yet.
