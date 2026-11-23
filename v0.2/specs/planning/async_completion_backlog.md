# Completing direct-call async/await in v0.2

Doc Status: planning

This milestone covers cooperative async free functions consumed directly by await or async_wait in the PHP, C++ and JS flavors. Its starting point is commit `c61e6ad8d`. The estimate is 12 reviewable runs, with a range of 9–15. Revisit the estimate after the statement audit and the first suspending-handler proof. Group work by evaluation and lifetime requirements across all flavors and types.

The [async contract](../../../specs/async_await.md) owns language semantics. The [implementation inventory](async_publication.md) describes the current boundaries. Async methods as producers, callable adaptation, first-class task values, cancellation, cross-thread execution, asynchronous iteration and a returnable fixed_array representation are separate milestones.

## Backlog and acceptance

| Run | Work | Evidence to close the run |
|---|---|---|
| 1 | Audit ordinary statements in an async body. Start with switch, case bodies and ordinary writes between suspension points. | Exercise selection, fallthrough, break and case-local cleanup across await. Record which restrictions can be removed together and the reason for each remaining one. |
| 2 | Support suspension during storage writes. Cover receiver selection, keys, append, and the old value in compound operations. | An awaited child mutates the receiver or collection. Check single evaluation, conversion timing, destination identity and failure before write-back against the ordinary operation's contract. |
| 3 | Construct nontrivial staged expression results. Cover initializers, nested operands and selected branches with a common lifetime rule. | A heap-backed string and a record with observable cleanup reach the destination once. Failure and skipped arms introduce no dummy construction or destruction. |
| 4 | Extend lazy selection and audit remaining expression sites. Cover match, coalescing, constructors, collection expressions, subscripts, loop operands and timer arguments. | Only the selected path executes. Required prerequisites execute inside the coroutine. Classify every residual rejection against an explicit boundary. |
| 5 | Admit suspending arguments to ordinary methods, runtime calls and calls with reference parameters where the ordinary binder permits them. | Preserve receiver identity, by-value conversions and reference-place identity across an awaited sibling. The async producer itself continues to own its parameters. |
| 6 | Construct a task result only on successful return. | Test a result requiring constructor arguments through await and the blocking bridge. Failed producers leave storage inactive. Successful payloads are destroyed exactly once. |
| 7 | Apply the owned-payload rule to application types. Review nested records, authored lifecycle methods and class-handle presence. | Prove transitive layout invalidation, cleanup and presence preservation using representative types. |
| 8 | Support suspension in a typed catch. | Preserve handler choice, original exception identity, rethrow, replacement exceptions and catch-local lifetime after suspension. |
| 9 | Run an ordinary finally around suspending work. | Execute cleanup exactly once on fallthrough, return, exception and loop transfers, including nested regions. |
| 10 | Support suspension in finally. | Preserve the pending exit across await. Verify normal replay, replacement by an escaping exception, and destruction of any saved return value. |
| 11 | Combine the mechanisms in incremental and native tests. | Exercise an owned return from a loop through a suspending finalizer. Edit, delete and restore a child. Compare diagnostics, artifacts, effects, cleanup and native rebuild counts against fresh builds. |
| 12 | Run milestone acceptance and reconcile the public contract, implementation inventory and diagnostics. | Run the full async suite and affected ordinary regressions. Record native header dependencies, compilation cost, incremental reuse and documented exclusions. |

For each implementation run, use focused native proofs across all three flavors and include an affecting edit, failure/deletion and fresh-equivalent recovery. Record completed runs here with their verification evidence.

## Adopted decisions

- **Reference arguments (run 5).** Apply the existing binder to an ordinary call such as `update(slot, await child())`. A reference to an admitted named local denotes the same storage when the child completes. Broader borrowing from fields or collection elements needs its own lifetime contract. See [writable parameter rules](../../compiler/docs/collection_references.md).
- **Task payload transfer (runs 6–7).** Allow source evaluation and construction to fail through the existing producer exception path. Construct promise storage on successful return, retain nonthrowing native transfer and destruction, and remove the eager default-construction and move-assignment requirements where those operations disappear. Supporting a throwing move during delivery to the caller would require a separate failure and cleanup contract.
- **Handler and finalizer semantics (runs 8–10).** The ordinary [exception contract](../../../specs/exceptions_v0_2.md) already defines replacement, rethrow, pending returns and cleanup order. Suspension should preserve those rules. Reopen discussion only if an async scenario exposes a genuinely unspecified observable behavior.

## Progress

**Run 1 complete.** Removed the async statement whitelist. The ordinary statement preparer and emitter handle a switch with a synchronous selector, case fallthrough and ordinary writes between suspensions. The native async fixture covers append, indexed assignment, indexed compound and increment, and field compound and increment. The selector runs once per dispatch. A case local survives the child await and is destroyed before fallthrough; break exits the switch inside a loop. Both incremental and fresh programs produce the same trace in PHP, C++ and JS. Deleting the child fails preparation, and restoring its edited body rebuilds one native object per flavor.

Remaining boundaries after the audit:

- Suspension during operand evaluation still goes through the await-context and staged-result checks. Writes during suspension belong to run 2; selectors and other expression sites needing prerequisite statements belong to run 4.
- Catch and finally have their own exception-region restrictions (runs 8–10). Rejected foreach receiver lifetimes and nested yield cursors retain their separate ownership boundary.
- Yield requires a generator result. Base initialization is only parsed for derived constructors. Neither requires a duplicate async statement whitelist.

Baseline at `c61e6ad8d`: rebuilt the Clang async runtime and frame-boundary fixtures. CTest passed `async_runtime`, `async_frame_boundary` and `async_header_isolation` (3/3). The frame fixture verifies nested blocking waits on both success and failure and observes both guard destructions. The task recipe routes blocking extraction through the same `await_resume()` used by suspension; it checks the saved exception before extracting the payload. Run 6 needs to prove construction and destruction of the payload itself, including a never-started task, in addition to retaining these baseline checks.

Runs 2–12 remain open.
