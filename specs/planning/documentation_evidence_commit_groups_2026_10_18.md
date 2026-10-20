# Documentation and evidence commit preparation
Doc Status: planning

Prepared 2026-10-18. No staging or commits performed. These are proposed commit
boundaries; counts include files currently modified or untracked, not unchanged files.

Compiler implementation, tests, lifetime slice documentation, the draft agent cheat
sheet, and accounting application sources remain outside these groups. The accounting
documentation describes the existing local baseline; committing it does not deliver
the application or establish fresh build results.

## 1. Agent guidance

1 files; 4,661 bytes in current file contents.

- `AGENTS.md`

## 2. Temporary artifact exclusions

1 files; 1,424 bytes in current file contents.

- `.gitignore`

## 3. Conversion-quality planning

2 files; 40,156 bytes in current file contents.

- `compiler/my-try/docs/README.md`
- `compiler/my-try/docs/planning/conversion_quality.md`

## 4. October 8 compiler comparison

1063 files; 1,673,998 bytes in current file contents.

- `compiler/my-try/docs/compiler_phpp_requirements_2026_10_08.md`
- `compiler/my-try/docs/compiler_phpp_requirements_manifest_2026_10_08.json`
- `compiler/my-try/docs/legacy_test_expectations_2026_10_08.md`
- `compiler/my-try/docs/status_comparison_2026_10_08.md`
- `compiler/my-try/docs/status_comparison_2026_10_08.html`
- `compiler/my-try/docs/status_samples_2026_10_08/`

## 5. Accounting workload plan and historical compatibility audit

9 files; 26,205 bytes in current file contents.

- `specs/planning/accounting_workload.md`
- `specs/planning/accounting_mytry_gaps_2026_10_09.md`
- `specs/planning/results/accounting_mytry_2026_10_09/`

## 6. S2S requirements and integer-literal experiment

5 files; 27,677 bytes in current file contents.

- `specs/planning/v0_2_compiler_s2s_requirements.md`
- `specs/planning/results/int_literal_clang_2026_09_25/`

## 7. October 16 accounting performance checkpoint

50 files; 1,309,588 bytes in current file contents.

- `specs/planning/s2s_next_generator_technical_handoff.md`
- `specs/planning/results/accounting_checkpoint_2026_10_16/`

## 8. Nullable compile-cost evidence

1013 files; 1,085,213 bytes in current file contents.

- `compiler/my-try/docs/planning/compile_performance.md`
- `specs/planning/results/nullable_compile_2026_10_03/`

## 9. October 18 compiler diagnostic evidence

5113 files; 4,501,866 bytes in current file contents.

- `specs/planning/results/compiler_diagnostic_review_2026_10_18/`

## Evidence handling

- October 8 source snapshots are unavailable: dead temporary links became historical file/line citations; surviving excerpts and hash manifest remain.
- October 16 evidence was read from commit 2f453540; provenance records source and archived hashes. Out-of-archive dependencies are historical citations.
- Diagnostic text was recovered from /tmp with hashes; binary/cache/lock state was excluded. Original paths inside raw logs remain provenance.
- Nullable sources, scripts, summaries, traces and diagnostics were archived; executables, objects, PCH and preprocessed output were excluded.
- Historical scripts retain original environment/layout assumptions, documented in archive READMEs. This preparation does not claim portable replay or fresh test success.
- Original temporary files remain on disk. .gitignore excludes Python bytecode and compiler/my-try/temp/.

## Validation

Checked local Markdown file targets, JSON syntax and evidence hashes for selected files,
and verified protected compiler sources and the dependency-cleanup test against their
preparation-start hashes. Compiler tests and benchmarks were not rerun.
