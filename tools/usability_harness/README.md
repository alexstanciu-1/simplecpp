# Usability Harness

Deterministic v1 usability validation for Simple C++ / PHP-like.

## Goal

Exercise small spec-aligned user programs through the real project workflow:

- `scpp init`
- `scpp run`
- compare deterministic output against PHP when possible
- classify failures
- store pass/fail artifacts

## Run

```bash
scpp usability-harness
```

Optional flags:

```bash
scpp usability-harness --limit 10
scpp usability-harness --stop-after-bugs 5
scpp usability-harness --include-scenarios
scpp usability-harness --all
scpp usability-harness --kind scenario
scpp usability-harness --campaign scenarios_multifile
scpp usability-harness --template scenario_bool_null_gate_001
scpp usability-harness --config tools/usability_harness/config.json
```

## Outputs

Artifacts are written under:

- `tests/generated/usability_harness/report.json`
- `tests/generated/usability_harness/summary.txt`
- `tests/generated/usability_harness/feature_summary.json`
- `tests/generated/usability_harness/campaign_summary.json`
- `tests/generated/usability_harness/passing/`
- `tests/generated/usability_harness/quarantine/`

Failure quarantine artifacts also retain generated C++ when available under `generated_cpp/` and `generated_build/`.

## Classification buckets

- `bug`
- `unsupported`
- `tooling`
- `generator`
