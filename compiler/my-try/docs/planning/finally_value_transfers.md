# V-D06 exception transport and finally exits
Doc Status: planning

Approved goal, 2026-10-22. Baseline: 57/64 original issues resolved. V-D06 remains
open until the approved acceptance scope below is verified. This plan does not
change normative semantics before implementation and proof.

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
