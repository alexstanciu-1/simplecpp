# Incremental preparation work

Doc Status: supporting

This slice makes body preparation consume explicit work. Declaration publication,
contract comparison, watch dispatch, diagnostics and import validation keep their
existing algorithms. No new syntax, native scheduler, cleanup protocol or
incremental emission path is introduced.

## Structures and ownership

```text
compilation_session
└── preparation_work: preparation_work — shared across its sequential lineage

preparation_work
├── bodies: set<ast_function_body> — deduplicated pending references
└── total: uint32 — number of attached live bodies, for existing reuse statistics

ast_function_body
└── preparation_work: ?preparation_work — native non-owning lifecycle backlink
```

The work record lives in `04_analyze/prepare/structures.php`. The AST still owns
syntax and facts; the queue owns neither in the native model. PHP object references
keep pending bodies alive. There is one persistent pending set, not an additional
live-body inventory. The total is maintained at attachment/retirement, avoiding a
scan of every body just to compute the existing prepared/reused report.

A body outside incremental ownership has no queue link. Invalidation/reset marks
its facts unready and enqueues it when attached. Successful completion removes
it. Queue methods manipulate references/counts only; they do not traverse source
or run preparation. Existing dependency interfaces retain their signatures and
remain independent of body representation.

## Frontend boundary

Read/tokenize/parse still operate from source-unit change statuses. Parsing creates
no queue entries and mutates no active queue. After all files and symbol publication
succeed, `Incremental_Frontend` adopts the previous work record, detaches bodies in
replaced/removed roots, then attaches bodies in added/changed roots. The file's
existing declaration inventory includes implicit main. Unchanged bodies need no
reattachment or scan by this new scheduling step.

Existing watch dispatch during publication already enqueues invalidated attached
consumers. A retired watcher may receive its existing final invalidation; subsequent
detachment removes that obsolete work before frontend returns. Bodies without any
watches are also detached, including ones never prepared. Parse failure happens
before publication and leaves the current queue unchanged. This does not add rollback
for arbitrary internal exceptions after publication has started mutating live symbols.

The queue is shared live state, like symbols/watches, not a historical snapshot.
Retained old sessions remain ineligible for preparation. Pending failures survive
no-op frontend generations and repeated preparation attempts. Clearing previous
session history remains separate work.

## Consumer and failure behavior

`Incremental_Preparation` still runs declaration/import validation first. It then
iterates a snapshot of pending references, not all functions looking for dirty
flags. The transient snapshot allows successful completion to remove entries
without skipping adjacent entries during PHP SplObjectStorage iteration.

The selected entry body has one explicit readiness check for target changes:
entry-target context is not a watched source symbol. Failure before body processing
leaves all pending work intact. A body diagnostic leaves that body queued, while
successfully prepared peers are removed. Further retries rebuild only pending
bodies. Existing diagnostic ordering and prepared/reused report fields are retained.

Declaration signatures and literal facts still use their existing readiness-guarded
scans. Global duplicate/local-scope diagnostics and unused-import validation still
scan their inventories. Making those selective needs its own dependency/retry
boundary; this slice does not claim no-op preparation is constant-time.

## Verification — 2026-11-05

`tests/preparation_work.php` covers all three flavors:

- First pass and multiple frontend passes before preparation; queue identity survives.
- Repeated symbol notifications and explicit invalidation deduplicate one consumer.
- No-op, body-only edit, and constant-value invalidation with refreshed consumer facts.
- Failed parse preserves the current generation and queue.
- Failed preparation retries in-place and across no-op frontend generations.
- Missing-name addition and duplicate-declaration repair recover pending work.
- Replaced/removed unprepared bodies retire even without dependency subscriptions.
- Detached bodies cannot requeue themselves; explicit reset enqueues live bodies.
- Entry-target changes enqueue only the entry body.

49 selected compiler/generator CTest checks passed, including existing function,
constant, global, import, duplicate, signature and incremental recovery proofs.
Six native unary/multifile recovery executables passed, using fresh full preparation
for native emission; incremental emission is still deferred.

Native proof configuration: O2/g0, libc++, Ninja -j8, mold, no PCH/LTO, prebuilt
runtime. These single instrumented correctness runs overlapped the regression suite;
they are not a comparative performance benchmark.

| Fixture | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Code generation ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 245.643 | 43.643 | 65.526 | 30.872 | 25.804 | 86 |
| php | 153.314 | 10.313 | 14.607 | 5.048 | 7.050 | 75 |
| js | 235.816 | 23.881 | 26.553 | 12.007 | 10.157 | 101 |

Trace categories overlap and accumulate across translation units, not elapsed wall
time. PHP fixture generation plus semantic/incremental test assertions: 88.981 ms.
Raw fixtures, C++, logs, traces and results: `v0.2/build/preparation-work-proof-2026-11-05/`.
