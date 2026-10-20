# Default template prelowering — 2026-10-20
Doc Status: planning

Base: `b86cf7f3` plus this change. CLI, API and standalone C++ generation now use
`cpp_lowering_options` with prelowering enabled. Ordinary compilation requires
`--no-prelower-templates` or `prelower_templates = false`. Existing explicit enabling
remains accepted; the last CLI mode flag wins. No automatic fallback was added.
`Compiler` accepts the existing options structure at construction; the public field
still permits mode changes. No new retained protocol or semantic data was introduced.
Native configuration defaults to no PCH; `--pch` explicitly enables ordinary-mode
PCH and is still rejected for strict concrete application input.

Ordinary regression fixtures now explicitly select ordinary mode, including direct
standalone generator calls and CLI comparison paths. This mechanical migration does
not claim expanded concrete coverage. The public-default fixture intentionally uses
unconfigured CLI/API/standalone generation. Historical benchmark scripts/results
retain the settings and measurements from their original revisions.

## Verification

- `python3 compiler/my-try/tests/prelower_null_output.py --results /tmp/prelower-default-public-final-20261020`
  passed both groups: default artifact parity and complete template-free closure,
  default native build/execution, explicit ordinary/PCH build/execution, strict
  unsupported/PCH rejection, publication repair, warm reuse and mode lifecycle.
- `python3 compiler/my-try/tests/run.py --php-only --results /tmp/prelower-default-regression-20261020`
  ran 197 suites: 176 passed, 21 failed. The broad glob includes argument-dependent
  fixtures and existing stale assertions. All 21 failures were rerun against a
  `git archive` of `b86cf7f3`: each has the same exit code and first fatal diagnostic.
  The already-dirty `dependency_cleanup.php` was copied unchanged into that baseline
  to preserve its pre-existing state; no production baseline files were overlaid.
  Results: `/tmp/prelower-default-baseline-check-20261020`; cases are retained in
  `baseline-failures.json`. The broad suite is not reported as green.
- Shared runner used 12 slots with its supported CLI fallback because the sandbox
  prevented FPM socket creation. These are correctness results, not timings.
- `git diff --check` passed. This change did not rebuild the compiler natively;
  generated C++ execution is distinct from a native compiler rebuild.

Full runtime coverage and unsupported transport adapters remain deferred. The
strict failure policy and documented ordinary escape hatch remain part of the
new default's contract.
