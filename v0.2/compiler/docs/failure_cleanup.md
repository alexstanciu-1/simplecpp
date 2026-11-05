# Failed-generation cleanup (slice 4)

Doc Status: supporting

`Generation_Cleanup::failed()` releases only the current published session's previous
history and removed-source references. It never acknowledges preparation/emission work,
forgets watches, clears facts or rolls back symbol publication. Success and failure
share the same private history-release operation, but retain different gate checks.
No new model fields or retained cleanup records are introduced.

`Incremental_Project::run()` first adopts a successfully published frontend session.
Its private `compile()` continues preparation, emission and bundle publication. If that
continuation throws, the coordinator invokes failure cleanup and rethrows the original
exception. Jobs/readers must have finished before this gate in a future native executor.

Read/tokenize/parse failures do not adopt the candidate. The existing frontend catch
clears candidate source roots; the project retains its previous current session. An
initial frontend failure leaves the project without a session. This path does not call
failure cleanup on an unpublished candidate. Frontend-only API/service history policy
is unchanged; this slice integrates the retained executable coordinator.

After declaration/body failure, the current published declarations and stable entries
remain, including tombstones. Valid unaffected caches remain reusable; dirty/failed
bodies remain pending. Missing-symbol watches remain registered. Declaration/duplicate
errors can stop preparation before any body traversal; their pending membership is
preserved too. Retrying without edits does not lose the error or grow retained history.

After lowering/emission/publication failure, prepared facts and downstream dirty-source
membership survive for retry. The last successful in-memory output baseline remains.
Existing bundle failure handling removes newly created uncommitted artifacts; this is
not a new filesystem transaction or rollback of all overwritten files. Repairing the
output destination permits retry without source edits and without re-preparing bodies.
Native failure occurs after C++ publication and needs no prior AST generation.

## Lifetime qualification

Existing cross-source expression facts use stable symbol entries, not direct links to
retired declarations. Local facts stay inside their source generation. In the tested
failures no extra dirty-fact sweep was necessary: releasing history made retired syntax
collectible while preserving current facts and watches.

Diagnostics intentionally retain source snapshots. PHP exception traces can also retain
objects/arguments; callers keeping exceptions or old sessions extend those lifetimes.
Cleanup does not mutilate diagnostics or caller-owned objects. The verification drops
exception objects before weak-lifetime checks and keeps only formatted messages. PHP
cycles remain the responsibility of the existing collector; production does not force
GC per run. This is bounded project-owned history, not a promise of constant process RSS.

## Verification

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/retention/failures.php > failures.json
XDEBUG_MODE=off php v0.2/compiler/tests/retention/close_timing.php failed > failed-close.json
```

The failure tool uses one resident project per flavor. Each runs an initial failure
before any session, then ten failures per category: invalid UTF-8, incomplete parsing,
unknown return type, missing function body dependency, duplicate declaration and blocked
output publication. Semantic failures mix replacement edits and no-edit retries.
Recovery introduces the missing declaration, removes duplicates or repairs the output
path. Exact preparation counts prove quiet caches survive and publication retry performs
zero body preparation. Stable slots, missing-name watches, queue membership and unchanged
manifest on publication failure are checked.

At every observed gate the project has one session, no retired roots, no stale watches,
no retired pending bodies and consistent duplicate chains/indexes. Weak-observed retired
families become collectible; releasing the project leaves no tracked survivors.

Local evidence in `v0.2/build/retention-slice4/`:

- 198 measured project invocations across cpp/php/js: 180 expected failures and 18
  successes; additionally three initial frontend failures before session creation.
- Slice 3 regression: 171 transitions, including duplicates and re-addition.
- Repeated-edit regression: 75 successful invocations; lifetime gates also pass.
- Native regression: 54 successful executable checks across all flavors, plus expected
  failures and retries. Raw Clang frontend/LLVM/link timings are in `native/results.json`
  and summarized in `native-report.md`. No new forced native rebuild policy.

## Timings

Small two-file fixture, 25 changed-source failures and 25 no-edit failure retries per
flavor (75 samples per row). The timing tool invokes the production cleanup method at
the failure gate without adding clocks to compiler code. Graph inspection is excluded.

| Failure mode | History release avg (min–max), ms | Explicit PHP GC avg (min–max), ms |
| --- | ---: | ---: |
| Changed source | 0.00151 (0.00064–0.00288) | 0.02431 (0.01494–0.06658) |
| No-edit retry | 0.00101 (0.00047–0.00295) | 0.01581 (0.00919–0.11327) |

Whole failed invocations in the separate three-file fixture averaged 0.38–0.56 ms for
lexical/parse/declaration/body/duplicate failures and 1.48 ms for blocked publication.
These are small-fixture observations including timer overhead, not large-program
performance promises. GC is process-wide and test-invoked, not exclusive history-release
cost. Success-close timing remains separately recorded in `success-close-timing.json`.

Lowering uses the same catch boundary, but this fixture does not inject an artificial
lowering defect. Crash-safe output transactions, native synchronization, symbol-slot
reclamation and frontend-service integration remain outside this slice.

[The long-session equivalence proof](incremental_equivalence.md) compares repeated
failures/recoveries against fresh compiler processes without changing cleanup policy.
