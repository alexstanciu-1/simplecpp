# Prelowering solidification sign-off — 2026-10-20

Doc Status: planning

**The agreed supported opt-in path is solidified. No remaining blocker was found
within that scope.** Broader runtime coverage remains deferred, not implicitly
accepted. Unsupported operations must continue to fail explicitly.

Tested commit: `da17e1bad6ab3751a0dbe3c1729273d55dcff5f7`.
Clang: Ubuntu 18.1.3, x86_64 Linux. Existing unrelated worktree changes were recorded
by the runner and preserved; the acceptance entrypoints were committed. Subsequent
changes in this slice only record evidence and reconcile documentation.

## Fresh acceptance

```sh
python3 compiler/my-try/tests/prelower_acceptance.py --results /tmp/prelower-solidified-acceptance-20261020
```

Exit status: **0**. Aggregate status: **passed**. All eight phases passed:

| Phase | Covered obligation |
|---|---|
| Metadata | 39 invalid schemas with recovery; used-only closure, discovery-order and deep-name proofs; runtime protocols |
| Contracts | Explicit unsupported-operation rejection/repair, publication non-readiness, closure completeness and aggregate failure reporting |
| Public options | One CLI/API concrete path, mode replay/recovery, strict PCH rejection and fixture closure |
| Provider boundary | Transitive template leakage, readiness, cache separation/recovery and ordinary PCH compatibility control |
| Headers and counters | O0 ordinary/concrete parity, evaluation order, nested references, allocation/copy counters, disk demand retirement/restoration and warm reuse |
| Source lifecycle | Methods/constructors, ownership identity, five copy-failure stages, replay and method/layout edits |
| Layout cache | Dependency changes, corruption/failure repair, layout/object reuse |
| Accounting | Fresh builds and execution in both modes, identical output, warm reuse and complete application closure |

Accounting results:

- Ordinary: **998 passed, 0 failed**.
- Prelowered: **998 passed, 0 failed**, byte-identical output.
- All **16 application units**, including entry and transitive headers: **0 template
  declarations and 0 Clang class/function instantiations**.
- Both warm builds: **0 compiled, 0 linked**.

Runtime/middleware implementations and layout probes intentionally retain templates.
All native acceptance is O0 without debug; strict application builds use no PCH.
The provider phase separately proves ordinary-mode PCH compatibility. PHP-FPM sockets
were unavailable in the sandbox, so the shared runner used its standard CLI fallback.

[Recorded summary](summary.json) retains revision/tool identities, entrypoint hashes,
accounting source hashes, phase results and warm-build counts. Complete logs and
per-phase artifacts are in the local recording above. Phase durations are suite
wall times, not comparable compilation benchmarks. This is PHP-host/generated-program
acceptance, not a native compiler rebuild, O2 evaluation or performance measurement.

## What remains

There is no need to expand runtime coverage or pursue another optimization to close
this solidification scope. Keep these separate, non-blocking follow-ups:

1. **Additional operation/representation coverage.** Runtime member value/reference
   results, reference-return/variadic transport, broader observation combinations,
   callback/source-exception transport and the other explicit gaps in the
   [coverage matrix](../../runtime_template_capability_matrix_2026_10_19.md#current-coverage-contract--2026-10-20).
   Metadata registration does not certify arbitrary providers.
2. **Naming/platform debt.** Unbounded source-path and proof-only opaque-name encoding,
   richer debug mapping, explicit hash-collision handling and cross-target execution
   of layout probes remain outside the current host-native proof.
3. **Future runtime/optimization work.** The tentative v0.3 prepared-facts/runtime
   review, wrapper removal, full incremental-compiler maturity, O2 work, ThinLTO,
   linker integration and background/persistent specialization remain deferred.

Operational contract remains unchanged: the caller explicitly selects
`--prelower-templates`, and the native strict application build uses `--no-pch`.
Known unsupported source transports reject at their existing owners; active template
leakage rejects during native validation. Neither failure silently switches mode.
Source-ready publication is distinct from a successful native build.

The [solidification checklist](../../prelower_templates_solidification_todo_2026_10_20.md)
now separates completed supported-path obligations from deferred coverage. Future
changes should continue to run the combined acceptance; any newly found regression
is a repair obligation, not permission to relax this boundary.
