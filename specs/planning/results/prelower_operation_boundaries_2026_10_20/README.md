# Explicit rejection of native-only object and ownership operations
Doc Status: supporting

2026-10-20. Bounded continuation of the
[strict no-fallback contract](../../cpp_template_prelowering_decision_2026_10_19.md#agreed-solidification-contract--2026-10-20).

## Finding and change

Runtime free calls already route through the concrete operation adapter, and runtime
members through the member adapter. The inspected unsupported call shapes reject.
Two nearby expression handlers still used ordinary runtime helpers in concrete mode:
object type tests/checked casts and weak-handle creation/locking. They could expose
runtime implementation headers and templates instead of rejecting missing transport.

The existing `CPP_Syntax` owner now uses a shared native-only operation guard before
those handlers emit operands or add headers. It rejects only concrete substitution;
ordinary source semantics/emission remain unchanged. No family dispatch, new registry,
AST pass, runtime implementation or prepared semantic fact was introduced. These
operations remain unsupported in concrete mode rather than acquiring partial adapters.

## Proof

The expanded shared-runner fixture failed before the change with
`Template fallback/partial output: object_test`. After the fix, nine cases pass:
the preceding five callable boundaries plus object tests, checked object casts,
weak creation and weak locking. Every case verifies ordinary native spelling,
explicit concrete rejection, no partial in-memory artifacts, exact ordinary-mode
recovery, source repair and deterministic no-op replay. The fixture is now named
`prelower_unsupported_boundaries.php` to reflect its shared purpose.

All ten closure-command coverage tests also pass via the same runner:

```sh
python3 compiler/my-try/tests/prelower_contracts.py --results FRESH_DIRECTORY
```

Local evidence: `/tmp/prelower-operation-contract-red-20261020` and
`/tmp/prelower-operation-contract-green-20261020`. PHP used the runner's CLI fallback
because FPM sockets are unavailable in this sandbox.

This is a backend emission/rejection proof. No new native execution, allocation/copy
measurement, full accounting run, benchmark or native compiler rebuild was performed.
The change does not alter supported operation execution; it prevents unsupported
emission. On-disk failure-publication testing, additional template escapes and the
public configuration migration remain separate work.
