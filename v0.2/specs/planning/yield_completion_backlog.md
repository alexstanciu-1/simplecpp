# Synchronous yield completion backlog

Doc Status: planning

Date: 2026-11-18.

## Objective and authorization

Complete useful synchronous pull producers through the existing metadata-defined
Iterator protocol, with light structures and fast preparation. This is not PHP
Generator API compatibility. The user requested a backlog and discussion of open
questions one at a time. Writing this plan does not authorize unresolved contracts
or start implementation of the remaining slices. Activate the implementation goal
once its boundary and discussion gates are agreed; stop at unresolved gates.

Semantic authority: [yield contract](../../../specs/generators_v0_2.md).
Implementation/evidence: [compiler yield notes](../../compiler/docs/yield.md).

## Agreed suspension boundary

The user confirmed: this goal covers synchronous generators only. Advancing a cursor
runs on the caller's thread until yield, completion or exception. Keep lowering and
lifetime mechanisms reusable for future async, but introduce no tasks, scheduling,
completion notification or cancellation machinery in this goal. Future async/await
is a separate contract and implementation effort.

## Implemented baseline

- Free-function Iterator<Value> / Iterator<Value,Key> producers; concrete move-only cursors.
- Lazy execution, normal by-value captures, generated output conversions.
- Straight-line statements, suspended locals, nested if/elseif/else and bare return.
- Lexical cleanup, completion, exceptions, abandonment and native cursor moves.
- All three flavors, incremental/fresh equivalence and sanitizer proofs.
- Commits: 395fbe636 (initial yield), f0bdc39f2 (locals and branches).

## Current progress

Slices 1 and 2 implemented (verification recorded in compiler yield notes): shared
statement traversal and scope-exit cleanup, while/do-while/for with suspension and
transfers. No general basic-block IR. Slices 3 onward remain pending; accepted
receiver/delegation rules do not imply their implementation.

## Ordered implementation slices

| Order | Slice | Done when | Gate |
| --- | --- | --- | --- |
| 1 | Shared statement/control-flow and cleanup lowering | Ordinary and producer traversal share control-flow handling; scope-exit cleanup has one owner; current output behavior preserved | No full basic-block IR unless a concrete need is demonstrated; wide redesign requires discussion |
| 2 | While, do-while and for | Nested yields, repeated body-local lifetimes, header locals, break/continue and failure cleanup work | Reuse existing preparation; no fixed-point analysis |
| 3 | Foreach inside producers | Receiver, traversal state and outputs survive suspension; built-in and custom iteration preserve their contracts | Q1: receiver ownership, mutation between advances and cursor relocation |
| 4 | Nested generated cursors | Foreach consumes a producer; inner storage survives suspension and closes on outer abandonment | Concrete layout dependencies and cycle rejection must be reviewed |
| 5 | Yield delegation | yield from forwards values/keys through the same traversal protocol | Q2: output conversions and keyed/value-only delegation |
| 6 | Producer methods | Existing binder plus a defined receiver lifetime, including cleanup on failure | Q3: inline/shared receiver capture |
| 7 | Cursor transfer | Supported moves, replacement and forwarding preserve single ownership; accidental copies reject | Q4: source syntax and exact admitted destinations |
| 8 | Output-type completeness | Existing supported value families are reviewed; inline-struct output storage does not add observable default construction | Q5: output initialization protocol; discuss any shared Iterator change |
| 9 | Consolidation | End-to-end nested producers, repeated incremental failures/recovery, sanitizer proofs, docs and timings | Do not claim deferred PHP features are implemented |

## Lowering direction

Prefer a shared statement walk with a temporary producer lowering context: resume
labels, lexical scope stack and loop transfer targets/scope boundaries. Keep ordinary
native RAII and producer frame cleanup distinct behind shared emission operations.
No new AST facts, general CFG, liveness solver or scheduler is planned. Each source
owns private lowering; layouts join before header publication. Preserve unchanged
AST reuse, selective preparation/emission and native object rebuilding.

## Discussion gates, one at a time

### Q1 — Suspended foreach receiver lifetime

Status: accepted by the user on 2026-11-18; implementation pending.

The source is evaluated once. Support these receiver forms:

- Named producer locals and captured parameters: borrow their cursor-owned storage;
  do not add a collection copy beyond ordinary parameter capture semantics.
- Temporary collections/iterators: own them until loop exit or cursor cleanup.
- Shared-object fields: retain the containing owner through traversal, including suspension.

Initially reject relocatable interior receivers (such as collection elements) unless
safe retention is established. Do not silently snapshot collections, retain dangling
references or add a complex borrow analyzer. Retaining an outer allocation alone is
not proof that every nested interior path is stable.

Cursor movement must not leave internal references pointing into the moved-from frame.
Regenerate access to known frame storage instead of retaining self-pointers where
possible. Preserve the existing bounded mutation contract: no new stable-visitation
promise. Concurrent mutation remains deferred.

### Q2 — Delegation

Status: accepted by the user on 2026-11-18; implementation pending.

Delegated values and keys use the existing generated typed-destination conversions,
exactly as explicit yield does; no separate conversion matrix or exact-type-only rule.
Conversion happens when an item is reached. Failure propagates through ordinary cursor
exception cleanup; previously delivered items remain delivered.

- Value-only outer producers accept value-only or keyed sources and discard keys.
- Keyed outer producers require keyed sources and convert both outputs.
- A keyed outer producer delegating to a value-only source is rejected; no keys are invented.

Delegation is equivalent to traversal followed by explicit yields under the accepted
receiver-lifetime boundary. It does not add send(), injected exceptions or a separate
delegated return value.

### Q3 — Producer method receivers

Decide whether inline receivers are captured by value; shared receivers can retain an
owner. Specify where method mutations are visible and when capture occurs. Never
retain a caller's unprotected stack reference across suspension.

### Q4 — Cursor transfer

Agree explicit move syntax/contexts, replacement and forwarding. Transfers must leave
the previous owner closed and preserve cleanup. Do not infer source copy semantics
from native move constructors.

### Q5 — Output lifetime

Review common output storage for Iterator and yield, especially inline structs with
observable constructors/destructors. Avoid separate type-specific preparation paths.
Stop if this requires a wider callable/reference protocol redesign.

## Verification and reporting per slice

- All three flavors; source diagnostics and actual generated executable behavior.
- Affecting edits, deletion/error, repeated failure and recovery against fresh builds;
  unchanged AST reuse and no-op preparation checks.
- Lifetime-sensitive changes: ASan/UBSan/leaks, completion, early exit, exceptions,
  abandonment, moves and partial initialization as applicable.
- PHP compiler time, native wall, Clang frontend, LLVM backend and linker; identify
  flags, Ninja jobs, PCH state, qualification overhead and runtime reuse.
- Report structure changes, per-slice lines added/removed and compiler excerpts.
- Consolidate and commit completed slices under the agreed goal authorization.

## Outside this goal unless separately agreed

Async/await and scheduling; concurrent mutation/access; send() and yield expressions
receiving values; injected exceptions; separate generator return values/getReturn();
PHP current/key/valid/rewind API compatibility; by-reference yields; automatic integer
keys and bare yield; suspension inside try/catch/finally; liveness/slot optimization.
These are deferrals, not permanent language prohibitions. Methods, delegation and
cursor transfer are planned above but their exact contracts remain undecided.
