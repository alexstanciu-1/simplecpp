# Combined prelowering acceptance — 2026-10-20

Doc Status: planning

Tested base: `632860cfcc31a8ca88d4a0033d7af3adc61d172a` plus the acceptance
workflow/inventory-proof overlay. Clang: Ubuntu 18.1.3, x86_64 Linux.
No compiler or runtime implementation changes were needed in this slice.

```sh
python3 compiler/my-try/tests/prelower_acceptance.py --results FRESH_DIRECTORY
```

The command composes existing proof entrypoints sequentially; each retains its
shared 12-slot runner. The results directory must be new. It records revision,
worktree status, tool versions, entrypoint hashes, phase status and detailed result
locations in `result.json`, updated atomically as work completes.

Final local recording: `/tmp/prelower-acceptance-20261020-final`.
All eight phases passed:

| Phase | Existing proof |
| --- | --- |
| Metadata | Runtime inventory, type bindings and protocols |
| Contracts | Unsupported boundaries, closure coverage and four aggregate-reporting cases |
| Public options | CLI/API parity, mode lifecycle, strict failure/repair and fixture closure |
| Provider boundary | Transitive template leakage, PCH rejection, readiness/cache recovery and ordinary PCH control |
| Headers and counters | Output/indexing/compound behavior, alternate provider, references, order and counters |
| Source lifecycle | Methods/constructors, five copy-failure stages, ownership, replay and layout/body edits |
| Layout cache | O0 lifecycle, provider edits, corruption repair, failed layout and recovery |
| Accounting | Fresh ordinary/prelowered behavior, warm reuse and complete application closure |

Accounting passed **998 checks, zero failures in each mode**, with byte-identical
output. All **16 application units** have **zero template declarations and zero
Clang class/function instantiations**. Warm builds compile/link nothing.
Runtime/middleware and layout probes remain explicit exclusions from the
application template-free contract.

Native validation stayed at O0 without debug. Strict applications used no PCH.
The provider suite deliberately also checks PCH compatibility in ordinary mode.
No O2/sanitizer run, native compiler rebuild, new performance measurement or full
runtime coverage claim is made. PHP-FPM sockets were unavailable in the sandbox;
the existing runner used its CLI fallback.

## Failure handling and inventory correction

The initial combined run at `/tmp/prelower-acceptance-20261020` failed its inventory
phase and correctly exited nonzero even though every later suite, including
accounting, passed. The stale assertion counted new concrete forward-declaration
headers as extra types. It now checks the exact demanded definition/companion set,
retaining the prohibition on unrelated eager specialization.

Four focused reporting cases prove full success, suite failure despite accounting
success, metadata failure and malformed final evidence. A fatal/interrupted report
is incomplete, never passed. Detailed failures remain in each suite's logs.

The [source-lifecycle evidence](../prelower_source_lifecycle_2026_10_20/README.md)
has been corrected: its earlier inventory task had failed on the same assertion,
although the note incorrectly listed it as passing. Type-binding and lifecycle
results were unaffected. This recording provides the fresh passing inventory and
combined acceptance result.
