# Exception boundaries and failed-publication recovery
Doc Status: supporting

2026-10-20. Bounded continuation of the strict prelowering contract.

## Change

Source throw/catch emitted `php_exceptions.hpp` and template helpers in concrete
mode. Finally emitted standard exception machinery and, for value returns,
`std::optional`. `CPP_Syntax` now rejects these native-only operations through its
existing guard before emitting the protected bodies/operands. Their concrete
transport remains unimplemented. Ordinary-mode emission and the already compiled
application entry's runtime-error reporting are unchanged.

The test fixture now uses the existing publication protocol for every attempt:
construct a fresh `Output_Publication`, invoke the compiler, then close in finally.
It checks actual disk readiness and manifests in addition to in-memory artifacts.
No publication implementation or lifecycle owner was changed.

## Evidence

With the publication fixture using the documented protocol, the pre-fix run failed
on `Template fallback/partial output: throw`. After the fix, all twelve cases pass:
five callable shapes, object test/cast, weak creation/locking, throw, catch and finally.
Every case checks:

- Ordinary native emission and ready publication.
- Explicit concrete rejection with no partial in-memory artifacts.
- `Output_Publication::require_ready` rejects the failed attempt on disk.
- Ordinary-mode recovery restores identical artifacts and manifest.
- Source repair publishes supported concrete code; no-op replay preserves its manifest.

All ten closure-command coverage tests pass in the same shared runner.

```sh
python3 compiler/my-try/tests/prelower_contracts.py --results FRESH_DIRECTORY
```

Local evidence: `/tmp/prelower-exception-contract-red2-20261020` and
`/tmp/prelower-exception-contract-green-20261020`. An initial fixture attempt reused
one publication worker across revisions; it was corrected to the documented
one-worker-per-attempt protocol before obtaining the red result above. That fixture
mistake is not a compiler publication defect. FPM sockets are unavailable in the
sandbox, so the runner used CLI fallback.

No new runtime feature, allocation/copy claim, native execution, accounting generation,
benchmark or native compiler rebuild is claimed. This slice rejects unsupported
paths; it does not establish universal template-free coverage. Other uncovered
operations/header exposures and the public CLI/API migration remain pending.
