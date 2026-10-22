# V-D06 exception transport and finally exits
Doc Status: planning

Completed goal, 2026-10-22. Baseline: 57/64 original issues resolved. V-D06 closes
within the approved scope: 58/64 (90.625%), six open and none partial. The normative
exception contract and catalog now record value returns and crossing loop transfers.
The initial implementation-order notes and checkpoints below are historical; the
final acceptance table is the current evidence boundary.

## Approved scope

- Restore source throw/catch/finally under default template prelowering.
- Admit supported non-reference value returns crossing finally, using ordinary
  prepared return conversions before cleanup and independent saved value storage.
- Admit break/continue crossing protected regions, replaying the original target
  only after all intervening finalizers complete.
- Keep reference returns crossing finally and returns/transfers escaping a finally
  body rejected. Local loops inside finally retain their ordinary internal exits.
- Preserve shared exception identity, unmatched/runtime exception propagation,
  replacement of pending exits by a finalizer exception, source snapshots,
  once-only evaluation and reverse-order cleanup.

## Existing owners and implementation order

1. `CPP_Syntax` currently explicitly rejects throw/catch/finally in prelowered
   application output. Runtime `make_thrown` and `catch_as` require template-bearing
   runtime headers. Publish concrete exception operations through existing compiled
   specialization owners; source handlers must distinguish language exceptions from
   runtime guard failures without leaking runtime templates into application units.
2. `Exception_Preparation` owns canonical exception admission and finally exit
   restrictions. `CPP_Finally` owns saved exception and optional return storage.
   Generalize this saved-exit model locally rather than adding per-type branches.
3. Preparation currently clears loop targets at a finally boundary. Preserve target
   identity for protected bodies and record crossing exits; retain a separate hard
   boundary for finalizer bodies. Backend replay must not break/continue inside the
   implementation lambda or skip outer finalizers.
4. Reconcile normative exception rules, existing ordinary-mode tests and default
   prelowering proofs. Preserve historical rejected fixtures as dated evidence.

## Verification on dev002

- Production cold/warm builds, template-free application manifests and warm-cache
  reuse. No compiler rebuild or full matrix.
- Nested catch/rethrow, absent throw, handler lifetime, runtime guard bypass,
  constructor/unwind cleanup and exception identity.
- Value families: containers, nullable/Result wrappers, inline records and supported
  shared/unique/weak handles. Verify snapshots, moves and destruction; invalid or
  nontransferable return expressions retain normal preparation rejection.
- Nested finalizers, normal completion, return/conversion failure, finalizer failure
  replacing a return/exception and cleanup before finalizer execution.
- Crossing break/continue for supported loops/switches, inner local targets, loop
  update execution and nested regions. Finalizer/reference-return negative controls.
- Retained valid edits, rejection, repair, replay and fresh publication parity.
- Previously blocked task repeated-error source join cases with default prelowering.

Use the shared 12-slot runner. Commit verified coherent slices and push after each
commit. Any newly discovered wider cross-owner prerequisite requires discussion.
Dev002's `/home/alex-ai/curl/20261020-work` is an uploaded source snapshot, not a Git
checkout; validate file manifests rather than relying on remote Git revision.

## Exception bridge checkpoint — 2026-10-22

Source throw/catch now use two concrete requests through the existing runtime
operation bridge. Throw snapshots its canonical Exception operand and invokes a
nonreturning compiled helper. Catch uses a catch-all source boundary and invokes
a helper while that handler is active: it rethrows into the existing runtime
`thrown_object`/`catch_as` contract. Foreign/runtime errors propagate unchanged;
matching returns the same shared object in its concrete representation. Runtime
semantics and implementation are unchanged. Finally remains guarded pending its
separate saved-exit implementation.

Dev002 `20261022-vd06-throw-01` and `...-02` prove seven cold/warm production cases:
catch, throwing callee, nested rethrow, once-only operand evaluation, absent throw,
runtime guard bypass and uncaught reporting. All application manifests forbid
templates; warm builds compile/link zero units. The second run additionally proves
four invalid retained edits, repair/replay, nested-handler edits, removal and fresh
parity. `20261022-vd06-tasks-01` executes both previously blocked repeated-error
source join cases (pool sizes 0/2) and the two module selection controls. Both joins
print the original worker error, then report completion.

[Evidence](vd06-exception-evidence.tar.gz), SHA-256:
`b4df5396cadfd890f2aa6aff76fd8b9036b021b6a60b535650e241a70d414796`. Sources, logs and summaries are retained; build caches are excluded.
The unsupported-boundary suite retires only its throw/catch rejection rows; finally
and unrelated rejections remain. Full identity/lifetime and finally acceptance
still belong to the active goal. V-D06 is not closed by this checkpoint.


## Final acceptance — 2026-10-22

The shared preparation model retains original loop targets and records which exits
cross each protected region. Finalizer bodies keep a hard outward-transfer boundary.
`cpp_finally_exit` names the backend's combined return/loop-exit context. Pending
values use ordinary prepared conversions; no per-family return algorithm or runtime
semantics was introduced. The concrete storage request reuses specialization and
layout publication to keep exception/optional storage inline, without heap allocation
for the saved-exit owner or template headers in application units.

Async protected scopes use the existing owned coroutine invocation. Async catch
matching retains the shared object, then enters the source handler outside the
native catch block so it can suspend. These adaptations preserve source scope and
cleanup, rather than treating synchronous proof as async coverage.

| Requirement | Authoritative proof |
| --- | --- |
| Canonical throw/catch, foreign guard bypass, once-only evaluation and exception identity | `exception_prelower.py`: callee/rethrow/once/absent/guard cases plus native pointer-identity observer through nested throw/finally/catch |
| Non-reference values and snapshots | vector/hash, mixed COW, dynamic shared identity, nullable/null, Result payload/error, false sentinel, inline record, enum, callable, shared/unique/weak cases; async container snapshot |
| Cleanup and replacement precedence | protected reverse cleanup, unique-owner destruction once, discarded pending owner, finalizer replacement of return/exception/loop exit, handled inner failure and return-evaluation failure |
| Loop targets | while/for/do-while, switch break versus enclosing-loop continue, nested crossing regions, loops local to protected/finalizer bodies; suspended nested continue |
| Reference/escaping-finalizer restrictions | Nine retained invalid edits include reference returns, return/break/continue escaping finally and nested forbidden return |
| Retained/fresh publication | Six valid edits cover values, loop transfers, handlers, async and removal; failed disk readiness, repair/replay manifests and fresh-output parity all pass |
| Default prelowering | 52 source cases pass production cold/warm execution; all 52 archived application manifests forbid templates and warm builds compile/link zero units |
| Lifetimes and undefined behavior | Seven ASan/UBSan source builds pass: unique lifetime/discard, reverse cleanup, weak lifetime, identity and two suspended handler/transfer cases |
| Ordinary-mode regression | `records.py --suite finally --no-pch`: 37 programs pass, plus its admission/recovery checks; no compiler rebuild |
| Tasks integration | Both repeated-error source joins pass for pool sizes 0/2; final lifecycle subset also passes both module controls |

Final directories on dev002: `20261022-vd06-finally-final`,
`20261022-vd06-ordinary-finally-01` and `20261022-vd06-tasks-final`.
[Final and historical evidence](vd06-finally-evidence.tar.gz), SHA-256:
`9959286b7a6de3be8f0fe664b0d5c20a5cb61d1b9c147f9c1bda73dc1c92b365`.
The final source hash manifest matches the committed candidate. The closeout audit
also read all 52 application manifests and representative cleanup/identity/retained
logs, not only the success summaries. Build caches/binaries are excluded.

Earlier runs preserve the async C++ rejection that prompted sequencing repairs,
the unrelated `isset(unique)` prelowering gap (the corrected fixture uses ordinary
handle truth testing), loop-local visibility and explicit-sentinel authoring mistakes,
and the test-provider metadata failure. A malformed Python filter failed before
creating a results directory; it changed no compiler behavior. These failed attempts
are not counted as successful execution.

### Separate limits and follow-up

- Reference returns crossing finally and transfers escaping a finalizer remain
  rejected by explicit agreement. Local finalizer loops and nested callable returns
  retain their existing independent rules.
- Multiple/union catches, source exception subclasses, `new Exception(...)`, bare
  rethrow, throw expressions/chaining, broader async task storage and LLVM remain
  outside this goal. No full matrix or native compiler rebuild was run.
- **VD06-H01:** the broader `prelower_contracts.py` run stops at its callable `local`
  case's `std::function<` ordinary-output expectation. This is outside the exception
  slice; its failure is archived and no suite-wide pass is claimed. The throw/catch/
  finally rejection rows were retired in favor of the focused positive/recovery
  proofs. Other existing prelowering gaps, including value probes, remain explicit.
