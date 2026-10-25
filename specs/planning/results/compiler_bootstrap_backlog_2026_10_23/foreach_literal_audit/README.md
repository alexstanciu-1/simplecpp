# Foreach literal target audit — 2026-10-25
Doc Status: planning

Independent audit while runtime argument fixes await approval; no production changes.
The previous partial owner audit records data-construction failures in nine owners:
Native_CPP_Build::build/require_template_free, Native_Ninja_Build::compile_edge,
CPP_Concrete_Iteration::entry, Native_CPP_Publication::runtime_inputs,
Initialization_Registration::install, Indexing_Registration::install,
Iteration_Registration::install, CPP_Initialization_Registration::install.
All contain bare foreach list literals; source inspection establishes candidates,
not exhaustive expression-located diagnostics.

Two minimal PHS probes in ordinary/concrete modes confirm bare string-list foreach
fails for missing concrete target, while an explicitly typed vector<string> local
prepares and emits. This is not native execution evidence. An initial probe omitted
whitespace between > and = and failed parsing; corrected probe/results are retained.

Recommended PHP adaptation:
$names /** vector<string> */ = ['mixed', 'dynamic'];
foreach ($names as $name) { ... }

Keep order, evaluate elements once at the original statement location, and preserve
loop-local name scope. Dynamic argument lists should receive their own accurately
named vector local. No collection inference, converter or language extension is
needed for this bounded source adaptation. Implementation and cross-route behavior
proofs are deferred; current pending approval concerns the earlier runtime-call slice.
