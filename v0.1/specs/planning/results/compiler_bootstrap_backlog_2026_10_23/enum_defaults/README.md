# Explicit compiler enum defaults
Doc Status: planning

2026-10-25, baseline 6457aeb52 plus this source-only slice.
Reuse: ordinary enum-case property constants already convert and prepare/lower in
both routes. Write an existing case explicitly; no invented sentinel, automatic
first-case language rule, compiler exemption or converter semantic analysis.

41 fields: 40 active-source fields from the initial audit plus llvm_parameter.mode,
which the full bootstrap still converts despite LLVM being parked. That parameter's
producer assigns the parsed mode immediately after allocation; no LLVM algorithms
or emitted semantics were changed. Existing constructors/builders still establish
final values; incomplete prepared facts must not be published/read as completed facts.

The native fixture extracts enum declarations and default spellings into a bounded
holder; it proves PHP/v0.1/v0.2 ordinary/prelowered default-value agreement, not that
all production constructors compile natively. Production reflection checked the 40
initial fields; the final fixture also covers the parked record's enum default.
Actual type_catalog/type_capabilities/conversions/operators/runtime_catalog/
runtime_type_bindings/incremental_preparation host suites pass. Initial runtime_catalog
invocation omitted its required scratch directory; corrected invocation passes.
Native fixture formatting cleanup after the first pass changes no source behavior.

First bootstrap: 148.15 s / 626456 KiB RSS / 592.5 MiB heap; 24 -> 20 distinct
messages. Eight named constructor initialization diagnostics cleared and four newly
reachable ownership/local-flow messages appeared. Generic enum-field diagnostic
remained solely at parked llvm_parameter.mode in the converted PHS inventory;
that declaration received the same explicit default in the final candidate.
Final bootstrap measurement is recorded separately below when completed.
Native compiler build/run and second-generation proof remain unproven.

Final 41-field fixture passes all routes. Final bootstrap metrics are in bootstrap.time; enum-valued field diagnostics clear. Prototype parking was authorized by the user after this measurement began and is a separate slice.
