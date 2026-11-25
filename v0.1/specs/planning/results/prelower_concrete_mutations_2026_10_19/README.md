# Concrete mutation forwarding proof
Doc Status: planning

Date: 2026-10-19. Base revision: `b1393173`, plus this slice.

The existing prepared mutation decision demands concrete prefix/postfix members.
Members invoke runtime operators; ordinary emission retains target evaluation,
sequencing and source result snapshots. No family-specific engine branch is added.

## Validation

`python3 compiler/my-try/tests/prelower_substitution.py --mutations --results /tmp/prelower-mutations-3`
passed in 191.13 seconds through the shared runner (PHP CLI fallback).
Ordinary/concrete generated applications passed O0, O2, ASan/UBSan (leak detection
disabled), and O2 with allocator assumptions disabled. No PCH or compiler-native
rebuild was used. These are correctness/cache checks, not compilation benchmarks.

The proof covers all four integer mutations, aliases, single field-receiver
evaluation, ordered mutation expressions, native float passthrough, nullable-float
postfix success, and empty-carrier failure without changing its state.
Every mutation measurement reports `mutations 0 0 0 0 0 0 0` (allocations, bytes,
frees, tracked payload constructions, copies, moves, destructions). Primitive numeric
copies are not instrumented; prefix keeps the ordinary source copy and postfix
constructs directly in final result storage. Carrier failure checks occur outside
counters. Nested-carrier payload substitution is not established by this proof.

Incremental checks cover demand removal, replay, configuration switching, discovery
order, body edits, layout edits and restoration. Warm concrete builds compile/link
nothing and reuse three layout batches. A body edit compiles one unit; restoration
reuses all six units. Stock O2 allocation delta is zero for this fixture; previous
compound/iteration allocation-elision differences remain unresolved.

Existing PHP mutation checks and the focused portability checker also pass. The
checker does not establish native compiler portability.

## Accounting checkpoint

The unchanged accounting application publishes, and the native build passes the
previous calendar `year++` / `month++` failures. It now stops in `checks.phs` output:
`echo_one` cannot accept the concrete integer because its `to_string` constraint
has no matching conversion. This remains a prelowering representation gap; no
source workaround or unrelated semantic repair was applied. Full accounting
compilation/execution and timing comparison remain pending.

Compact counters/cache evidence: [summary.json](summary.json).
Full local logs: `/tmp/prelower-mutations-3/logs` and
`/tmp/prelower-mutations-checks/logs`.
