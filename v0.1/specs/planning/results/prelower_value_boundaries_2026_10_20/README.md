# Native take and match failure boundaries
Doc Status: supporting

2026-10-20. Bounded continuation of strict prelowering enforcement.

`take` used the templated runtime helper directly; match without a default included
`stdexcept` and emitted a runtime exception fallback. Both now reject in concrete
mode through the existing `CPP_Syntax` native-only guard. This does not implement
new transport or change ordinary-mode source semantics. Match with an explicit
default keeps the existing supported emission path; no static exhaustiveness
inference or new semantic decision is added.

Before the fix the expanded fixture failed on `Template fallback/partial output: take`.
After the fix, all fourteen unsupported-boundary cases pass with ordinary emission,
explicit rejection, no partial output, failed disk readiness, ordinary recovery,
source repair and exact artifact/manifest replay. The repair program now includes
an explicit-default match, proving that supported emission remains available.
All ten closure-recording coverage tests pass as well.

```sh
python3 compiler/my-try/tests/prelower_contracts.py --results FRESH_DIRECTORY
```

Local evidence: `/tmp/prelower-value-contract-red-20261020` and
`/tmp/prelower-value-contract-green-20261020`. The shared runner used CLI fallback
because sandbox FPM sockets are unavailable. These are PHP-host emission and
publication proofs, not native execution or runtime allocation/copy measurements.
No accounting regeneration, native compiler rebuild, O2 or benchmark was run.

The native-interface audit remains incomplete. Runtime-backed truthiness/probes,
boxed extraction, null output and enum compiler helpers are explicit follow-up
inspection targets in the TODO. Public CLI/API migration remains pending.
