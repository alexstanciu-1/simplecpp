# Preparation concurrency audit and proposed first slice
Doc Status: planning

Status: implemented and validated for bounded per-source preparation and emission. User approved serial contract settlement, per-source parallel
bodies and coordinator publication, then explicitly requested threaded emission too.
This document is not a language contract.

## Finding

Per-source batching is a useful scheduling boundary, but source files are not independent
semantic compilations. Launching one existing `Preparation_Worker::prepare(file)` per
file would mutate shared identities and dependency graphs, and could report a false
cycle when another worker is merely processing a declaration. A global preparation
mutex would retain correctness but provide no useful parallel speedup.

The measured local O2 run spends 61.75 seconds preparing and 188.12 seconds emitting /
publishing, out of 251.76 seconds under stage observation. Even eliminating all
preparation time would improve this workload by only about 1.32x while emission stays
unchanged. This does not make preparation parallelism unimportant; it identifies the
separate, larger emission-copy bottleneck. See the [performance report](compiler_native_performance_2026_10_25.md).

## Existing owners and concrete shared writes

| Existing path | What can be reused | Why a per-file parallel loop is currently unsafe |
| --- | --- | --- |
| `compiler/my-try/compiler/compile.php:273` | Source inventory, parse join, retirement, trait synchronization, final entry/diagnostics publication | These are whole-program barriers; prepared files must not publish before every source succeeds |
| `compiler/my-try/compiler/preparation.php:5` | Existing analysis/materialization rounds and progress guard | Called-type implementations are created between rounds; tasks must not mutate that inventory independently |
| `compiler/my-try/04_analyze/prepare/worker.php:66` | Existing selected contracts, callable bodies, file bodies and semantic dispatch | One worker currently owns all queues; contract selection can recursively discover more work |
| `worker.php:602` and `:638` | Existing versioned dependencies and invalidation semantics | Register/detach writes both the consumer and shared provider's reverse-dependency map |
| `worker.php:651` | Existing contract readiness and cycle checks | Even an unchanged contract is temporarily marked `processing` and recursively inspected; that state means recursion, not another thread running |
| `worker.php:710` | Body-local contexts, cleanup, facts, failure paths | Bodies also update source change sets, subscriptions and demanded contracts |
| `worker.php:1026`, `03_parse/scopes/structures.php:201` | Observed lookup subscriptions and typed candidate pools | Find-or-create lookup records and reverse subscribers are shared mutable state |
| `03_parse/types/model.php:1269` | Canonical interning and existing capability validation | Index creation, type IDs and publication must remain unique under simultaneous misses; preparation calls this directly |
| `04_analyze/prepare/lookup.php:30`, `03_parse/scopes/namespaces.php:40` | Namespace identity registry | Qualified lookup can intern a previously absent namespace |

The existing retained-type lookup lock used by frontend collection does **not** by
itself protect all these preparation paths. Existing joined task permissions admit
programmer-synchronized shared data; they do not make these mutations thread-safe.

## Recommended bounded design

1. **Keep declaration settlement serial initially.** Reuse relationship, layout,
   signature, lifecycle and type-contract work and its existing cycle detection.
   Preserve retirement/trait/materialization barriers.
2. **Batch selected executable bodies by owning source.** Claim each body exactly
   once. Each task owns its contexts and body/anonymous-syntax work. Use the existing
   joined task runner with `Compiler::$jobs`; publish graph changes on the
   coordinator after the call returns.
   Files provide scheduling and locality, not a replacement semantic ownership model.
3. **Provide a settled-contract read phase.** Body queries consume completed facts
   without re-entering the mutating contract scheduler. Unexpected missing work or a
   called-type materialization demand yields to the coordinator; it must not be
   interpreted as a declaration cycle or silently prepared in another source's task.
4. **Publish dependencies through the coordinator.** Tasks collect invocation-local
   dependency and lookup observations. After join, merge reverse edges, versions,
   source change sets and diagnostics in stable source order using existing owners.
   Keep lexical/body facts exclusively owned during execution and preserve rollback /
   cleanup on failure. No retained record may retain a worker.
5. **Make remaining shared interning honest.** Preserve canonical type and namespace
   identity with a defined synchronization/publication boundary. Capability/dependency
   validation must not run while holding a non-reentrant registry lock. Decide which
   misses can use a short synchronized publish and which must yield to the coordinator.
6. **Reuse rounds and final checks.** Join before materialization, coalesce demands,
   settle serial prerequisites, retry affected bodies, then perform reference-cycle,
   entrypoint and advisory checks and publish the completed program.

No new source-language semantics or converter behavior is proposed. Parallel contract
settlement, a new whole-program thread-safety analysis, duplicate type registries,
and changing incremental invalidation rules are non-goals. Parallel emission was
subsequently authorized as a separate stage: private source rendering engines and
fragment maps, with joined coordinator merging and serial artifact publication.

## Complexity and proof boundary

This is a **coordinated medium-to-large change**, not a worker-count setting: scheduler /
body consumption, shared lookup bookkeeping, interning and coordinator rounds all
participate. Reusing semantic workers and the joined task machinery avoids a second
analyzer, but the concurrency/publication protocol is new and must be agreed first.

First vertical proof: independent multi-source bodies on at least two actual workers,
then shared signatures/types and namespace misses, same-type concurrent interning,
anonymous callables and called-type demand/retry. Compare jobs 1/2/all CPUs for
semantics, diagnostics, identity and cold/warm/edit/failure recovery; exercise cleanup
and cancellation after worker failure. Run native race checks where supported, plus
ordinary/concrete output and bootstrap acceptance. PHP alone cannot prove concurrency.

The user approved this boundary. Native O2 execution, full 32-worker ThreadSanitizer,
retained recovery and 1/2/32-worker measurements pass. The 32-worker run takes
43.20 seconds versus 61.05 seconds at one worker. Serial assembly, numeric-ID-based
callable output names and oversized generated units remain follow-up work. See
[dated evidence and limitations](results/compiler_stage_concurrency_2026_10_25/README.md).
PHP task callbacks still run sequentially; PHP alone is not the concurrency proof.
