# Complete application coverage for the template-free audit
Doc Status: supporting

2026-10-20. Bounded acceptance-gate slice under the
[solidification TODO](../../prelower_templates_solidification_todo_2026_10_20.md).

## Change

The closure audit previously accepted any nonempty recording of app compilation
commands. A missing entry.cpp or app unit could therefore produce an incomplete pass.
It now obtains the ready manifest using the existing `Output_Publication` owner,
verifies published artifact digests and requires exact coverage of the manifest's
application units. The existing output convention identifies units/ and entry.cpp;
specializations/ implementation units and layout probes are explicitly excluded.
Unclassified compilation artifacts fail instead of silently becoming exclusions.

Missing/unknown app units, conflicting recordings, failed object compilations and
PCH use reject before tracing. Identical repeated commands remain accepted. The audit
writes coverage.json in addition to its existing per-unit template/instantiation
results. No compiler representation, runtime metadata or build cache was changed.

## Validation

The shared `prelower_contracts.py` runner passes the five existing callable boundary
cases and ten closure-coverage tests (including subcases for missing units and PCH
forms). Fresh preprocessing and Clang tracing of the saved accounting publication
passes **all 16 app units**, with zero template declarations and zero instantiations.
Two middleware translation units and three layout probes are explicitly excluded.
See [coverage](coverage.json) and [per-unit audit](audit.json).

This used the prior `/tmp/accounting-concrete-handles/prepared` publication and
`/tmp/accounting-template-free/timed_clang.jsonl` recording. It is a fresh validation
of saved generated artifacts, not a fresh generation, accounting execution, compiler
rebuild or performance comparison. The runtime/middleware still contain templates.
The runner used CLI fallback because sandbox FPM sockets are unavailable.

Reproduction:

```sh
python3 compiler/my-try/tests/prelower_contracts.py --results FRESH_CONTRACT_RESULTS
python3 compiler/my-try/tests/prelower_template_closure.py \
  --commands RECORDED_JSONL --published PUBLISHED_ACCOUNTING --results FRESH_AUDIT_RESULTS
```

Final local results: `/tmp/prelower-closure-contracts-final-20261020` and
`/tmp/prelower-complete-closure-final-20261020`.

Automatic command capture in the unified acceptance workflow and production strict
contract enforcement remain pending. This test gate does not switch the CLI mode.
