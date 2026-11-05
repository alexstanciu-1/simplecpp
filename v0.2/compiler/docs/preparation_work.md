# Incremental preparation work

Doc Status: supporting

Body preparation consumes explicit work. Publication now uses
[changed source units](incremental_publication.md); diagnostics and import validation
retain their existing scans. No new syntax, native scheduler or cleanup protocol is introduced here.
The subsequent [retained executable path](incremental_executable.md) consumes a separate
source-key set that survives body-queue completion.

## Structures and ownership

```text
compilation_session
└── preparation_work: preparation_work — shared across its sequential lineage

preparation_work
├── bodies: array<string,set<ast_function_body>> — source key → deduplicated pending bodies
└── total: uint32 — number of attached live bodies, for existing reuse statistics

ast_function_body
├── preparation_work: ?preparation_work — native non-owning lifecycle backlink
└── preparation_source_key: string — stable attachment key; empty when detached
```

The work record lives in `04_analyze/prepare/structures.php`. The AST still owns
syntax and facts; the queue owns neither in the native model. PHP object references
keep pending bodies alive. There is one grouped pending index, not an additional flat set or live-body
inventory. Buckets are created on demand and removed when their last body completes
or retires. Keys come from `compilation_session::source_key()`, never source-unit
object identity; unchanged ASTs retain their attachment as unit records are replaced. The total is maintained at attachment/retirement, avoiding a
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
iterates pending source buckets, then the bodies within each bucket, not all
functions looking for dirty flags. The transient snapshot clones each inner
SplObjectStorage; copying only the outer PHP array would share mutable buckets.
Completion can therefore remove live entries and empty buckets without skipping
adjacent snapshot entries. The AST bodies themselves are never cloned.

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
- Source keys group pending work; multiple bodies share a bucket and remain individually deduplicated.
- Successful peers leave a failing body pending; completing live work preserves snapshot membership.
- Moving a failed source removes its old bucket and uses its new source key; recovery empties the bucket.

49 selected compiler/generator CTest checks passed, including existing function,
constant, global, import, duplicate, signature and incremental recovery proofs.
Six native unary/multifile recovery executables passed, using fresh full preparation
for native emission in that earlier slice. Incremental emission is now covered by the
[retained executable proof](incremental_executable.md).

Native proof configuration: O2/g0, libc++, Ninja -j8, mold, no PCH/LTO, prebuilt
runtime. These are single instrumented correctness runs, not a comparative
performance benchmark. The grouped-queue validation reran all 49 checks and six executables.

| Fixture | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Code generation ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 218.884 | 22.070 | 23.778 | 11.788 | 8.356 | 67 |
| php | 119.749 | 8.751 | 9.251 | 3.761 | 3.920 | 56 |
| js | 122.260 | 8.296 | 9.817 | 3.843 | 4.509 | 60 |

Trace categories overlap and accumulate across translation units, not elapsed wall
time. PHP fixture generation plus semantic/incremental test assertions: 76.174 ms.
Raw fixtures, C++, logs, traces and results: `v0.2/build/grouped-preparation-work-proof-2026-11-05/`.
