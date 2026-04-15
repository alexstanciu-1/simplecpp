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
scpp usability-harness --config tools/usability_harness/config.json
```

## Outputs

Artifacts are written under:

- `tests/generated/usability_harness/report.json`
- `tests/generated/usability_harness/summary.txt`
- `tests/generated/usability_harness/passing/`
- `tests/generated/usability_harness/quarantine/`

## Classification buckets

- `bug`
- `unsupported`
- `tooling`
- `generator`
