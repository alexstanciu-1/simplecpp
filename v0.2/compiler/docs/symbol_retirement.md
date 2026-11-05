# Symbol and watcher retirement (cleanup slice 3)

Doc Status: supporting

Slice 3 verifies the existing retirement protocol with deletion, replacement,
duplicates and re-addition. No new cleanup sweep, production structure or retirement
algorithm was needed. Cleanup remains separated from publication and watch ownership.

## Ownership and gates

- `Symbol_Publication::changes()` withdraws retired declarations and publishes current
  chains after frontend completion. `Scope_Collection` exclusively relinks duplicates;
  stable `symbol_entry` objects survive with null heads when removed.
- Publication retires the replaced/removed bodies' subscriptions through
  `Dependency_Watches::retire()`. Both forward and reverse relations are removed;
  empty buckets disappear. One final invalidation is dispatched before retired bodies
  are detached from preparation membership by the frontend coordinator.
- Surviving consumers retain needed subscriptions, including missing-symbol watches.
  Before reprepare, a body forgets its former subscriptions and records the symbols it
  actually consults. On an early error it may have only a partial new watch set; it
  remains pending for retry. This is not permission to discard missing-name watches.
- Declaration replacement repoints the same entry. Body-only function edits and global
  initializer-only edits reuse readers; constant-value changes invalidate readers.
  Removal, ambiguity and restored availability invalidate affected consumers.
- Duplicates remain an error-only chain owned by the declaring scope. No candidate list
  is created. Removing a head, middle or tail must leave only current declarations;
  ambiguity must never silently select one candidate.
- `Generation_Cleanup::close()` runs only after successful output publication. It releases
  generation references; it does not remove symbols or subscriptions. Failed-generation
  history is now released separately by [failure cleanup](failure_cleanup.md), without
  acknowledging unfinished work.

## Repeatable verification

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/retention/retirement.php > retirement.json
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/incremental_executable_native.py --output /tmp/scpp-retirement-native
```

Use a fresh native output directory. CTest registers the PHP matrix as
`compiler_symbol_retirement`; the existing native suite includes the executable checks.
The separate `tests/retention/` tool remains test-only and is not loaded by production.

The PHP matrix contains nine independent resident projects: function/global/constant
in cpp/php/js. Each runs 19 transitions: initial, provider replacement, deletion,
missing retry, re-addition, three-way duplicates, middle replacement/removal, head
removal and recovery, source move, same-file duplicates and tail removal/recovery,
consumer replacement/restoration/deletion/re-addition, combined deletion/re-addition,
and final no-op.

It checks stable entry identity, tombstone state, exact prepared-body counts and
unchanged quiet-body identity/readiness. Necessary watches remain registered through
missing/ambiguous states. Forward/reverse relationships agree; no empty buckets,
retired observers or retired pending bodies remain. Chain traversal rejects cycles,
wrong-key links, retired members or missing live declarations.

After each successful recovery, history is closed and retired syntax becomes collectible.
Weak observations do not keep old objects alive. Current source-owned ASTs and valid
caches remain, and releasing a whole project leaves zero tracked lifetime survivors.
The slice 3 tool tolerates failure history; the slice 4 tool additionally proves its
retirement after failed invocations. Test reflection is read-only and localized to `inspect.php`.

The native suite now additionally introduces duplicate functions/globals/constants,
retries the failed generation, removes duplicates, deletes the provider, retries missing
symbols and re-adds it. Failed runs leave the published C++ unchanged. Recovery executes
the expected output/exit status while unchanged C++ produces zero rebuilt objects and
no relink. Existing native checks still cover contract edits, consumer propagation,
file removal, publication/native failures and retry.

## Evidence (2026-11-05)

- PHP matrix: 171 transitions — 117 successful runs and 54 expected diagnostic failures.
- Repeated-edit regression: 75 successful runs across cpp/php/js with bounded retained state.
- Native suite: 54 successful executable builds/runs across all flavors, plus expected
  parse/preparation/duplicate/missing-symbol/publication/native failures.
- No production ownership or structure changes. The inspector adds one scalar report
  field, `ambiguous_symbols`, and stronger consistency checks; it changes no compiler data.

Raw local results and native phase timings are retained in `v0.2/build/retention-slice3/`.
This confirms the bounded cases above; it does not implement failure-history reclamation,
symbol identity reclamation, overlapping generations or native synchronization.
