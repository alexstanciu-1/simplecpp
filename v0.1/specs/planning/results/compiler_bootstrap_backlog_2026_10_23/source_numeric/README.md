# Source numeric boundary repairs
Doc Status: planning

Candidate: d3c2768a4 plus four production PHP source files. Full ownership/reuse
review: ../../../compiler_source_numeric_audit_2026_10_23.md.
Baseline identity probe records PHP/native divergence and v0.2 narrow arithmetic
rejection. Final identity+revision proof /tmp/type-identity-bounds-after-04 passes PHP,
legacy (21.55 s), ordinary (24.24 s), concrete (26.49 s). Production token-buffer proof
/tmp/token-buffer-width-02 passes PHP, legacy (30.49 s), ordinary (33.68 s), concrete
(35.13 s). Host capacity/recovery, tokenizer, publication, AST and lifecycle checks pass.

Type-catalog binding-count assertion still fails and reproduces in unchanged-source
HEAD overlay. The static-initializer prelowering bug has saved source/diagnostics;
final method fixture uses runtime endpoint assignment, not that unsupported initializer.
No claim of a clean full type-catalog suite, whole native compiler, or real 4-GiB input.

Bootstrap 1:14.22 GNU wall / 342396 KiB RSS / 312 MiB PHP heap; same first syntax_node
blocker. Diagnostic differences are in diagnostic_diff.json. No OOM/fatal/native build.
