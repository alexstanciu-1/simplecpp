# Body checking output ownership
Doc Status: supporting

`check_bodies\Body_Output` owns per-worker mutable output membership. It replaces
prototype null/union array elements with explicit pending/completed value rows and
reserved argument/scope slots. Values, calls and scopes use one-based IDs; argument
ranges and statement positions use zero-based offsets. IDs are stable during nested
checking. Slot completion is single-use; location selection permits repeated identical
access, rejects read/borrow reclassification, and ignores non-location values/void.

All completed exports reject unfinished locations, arguments or scopes. Container
membership is copied, while completed immutable row identities are shared. Captured
pending row handles remain historical views when the owner replaces a row. Type
retention, source diagnostics, semantic permissions and cross-row validation remain
with the worker and checked-result contracts. This owner is not yet wired into the
body worker and does not establish complete analysis-stage readiness.

Validation:

```sh
python3 compiler/tests/body_output/run.py --results /tmp/body-output-NEW --target-checkout /tmp/scpp-json-240-probe
```

33 PHP/native outcomes cover nested reservations and calls, completion order,
unfinished/repeated/invalid completion, selection, snapshots and later appends.
24 host sequences compare selection with the real retained trait. The host oracle
isolates only that method; other source-checking methods are not executed.
Saved evidence: `specs/planning/compiler_migration/results/body-output-01`.
