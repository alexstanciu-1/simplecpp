# Simple C++ changes deferred for a batch
Doc Status: planning

Collect target-language/runtime work discovered during portability discussions.
Review and resolve the accumulated items together in a later implementation batch;
listing an item does not start implementation or resume the compiler migration goal.
This list does not define current language semantics.

GitHub tracking: [Simple C++ issue #231](https://github.com/alexstanciu-1/simplecpp/issues/231)
collects all three requirements below. API names, signatures and platform scope
remain open; filing the issue does not start implementation.

## Current update (supersedes the historical request statuses below)

Issue #231 now reports the native batch implemented on branch
`codex/file-lock-runtime`, commit `394164c0f376443c31c96660dee250719a7dd804`.
This is neither a release nor a new selected compiler target. Collection, Linux
process and Linux file-lock contracts are frozen in that commit's normative docs.
PHP framework/converter integration and migration proofs remain outstanding.

The [collection integration checkpoint](../../portability/collection_helpers.md)
now covers PHP/conversion support and native parity. The missing constrained
adapters were supplied by v0.1 commit `541413ac`; combined candidate `08c8206a`
also fixes #232. Process/lock parity remains open; no compiler file was added.
The earlier sections below retain the original requirements and design questions.

## SCB-01: Typed map and filter helpers

Status: deferred for the Simple C++ batch, by user request.

Provide a small typed collection-helper surface so straightforward transformations
do not require every author to rewrite callbacks as loops. The selected v0.1.76
already has typed closures/arrow functions and capture lowering; this item does not
propose implementing a general closure system from scratch. Public helper names
and exact signatures are not yet decided.

Proposed starting scope:

- Map: one typed input collection, an explicitly typed callback, one output per
  input, preserving iteration order.
- Filter: one typed input collection, an explicitly typed boolean predicate,
  retaining selected elements in order.
- Immediate synchronous invocation; helpers do not retain callbacks.
- Explicit callback parameter/return types. Portability conversion preserves
  authored syntax without inferring receiver, capture or whole-program types.

Decisions for the batch:

- Supported collection types, generic signatures and public names.
- Key preservation versus dense output. PHP `array_filter` preserves keys; do not
  silently use that name for a helper with different behavior.
- Capture policy: capture-free callbacks first, or explicitly read-only inputs.
- Mutation discipline for callbacks and traversed collections.
- Allocation/call overhead, especially existing `std::function` lowering. Measure
  hot-path behavior before recommending these helpers there.

Implementation owners and proof:

- Simple C++ runtime helpers, strict registrations and STAN-visible signatures.
- PHP framework counterparts and locally explicit portability syntax/mappings.
- PHP/native tests for ordering, empty input, all/none selected, result element
  types, chosen key semantics, permitted captures and exception propagation.
- Focused performance evidence where intended usage is performance-critical.

Full PHP array/callable compatibility, multiple-input map behavior, dynamic callback
names and stored/asynchronous callbacks are not part of the proposed first scope.
Keep this item deferred while reviewing the remaining portability issues; do not
start a broad prototype callback rewrite as a substitute for the batch decision.

## SCB-02: Managed child processes

Status: deferred for the Simple C++ batch, by user request.

Provide a small typed API covering the behavior required by
[`Tool_Process`](../../../compiler/tool_process/process.php):

- Launch a command with explicit arguments and supplied stdin.
- Poll completion without blocking, retaining exit status and separate stdout
  and stderr. Input/output handling must avoid pipe deadlocks.
- Enforce timeouts using monotonic elapsed time.
- Terminate the owned process group, including descendants, and reap the child.
- Release resources on failures and normal completion, with idempotent cleanup
  and explicit ownership.

Existing `shell_exec()` covers synchronous stdout collection, not this lifecycle.
Reuse existing IO and monotonic-clock facilities where suitable. Public names,
typed handle/result shapes, capture strategy and platform scope remain batch
decisions; the prototype's current process-group behavior is Linux-specific.

Implementation owners and proof:

- Simple C++ runtime process API, strict registrations and STAN-visible signatures.
- PHP framework counterparts under lowercase `scpp`, backed by PHP process
  facilities, with explicit portability mappings.
- PHP/native behavioral proofs for input/output, nonzero exits, launch failures,
  polling, timeout, descendant cleanup, large output and repeated cleanup.

Full PHP process/resource compatibility and a general job scheduler are outside
this item. Existing cleanup language support and missing converter support for
`finally` are separate from adding the native process API.

## SCB-03: Cross-process file locks

Status: deferred for the Simple C++ batch, by user request.

Provide typed OS-backed file locking for independent compiler invocations,
covering [`Project_Lock`](../../../compiler/src/compile/lock.php) and
[`Package_Reservation`](../../../compiler/src-runtime-preparation/reservation.php).

Required behavior and batch decisions:

- Exclusive nonblocking acquisition, distinguishing contention from IO errors;
  review shared-lock callers before freezing the API.
- Stable lock-file identity: acquisition must not truncate or replace the file,
  and release must not unlink it.
- Explicit ownership and transfer where required, idempotent release and cleanup
  on failure.
- Defined child-process inheritance behavior, including close-on-exec and explicit
  unlock while an inherited descriptor may still exist before exec.
- Decide public names, typed handle/result shapes and supported platforms.

Implementation owners and proof:

- Simple C++ runtime lock API, strict registrations and STAN-visible signatures.
- PHP framework counterparts under lowercase `scpp`, using PHP file locks, with
  explicit portability mappings.
- Tests using separate processes for contention, release/reacquisition, ownership
  transfer, failure cleanup and child inheritance. In-process task publishing
  locks do not prove this behavior.

Ordinary `io_*` operations already exist; they do not expose this locking contract.
Distributed locks and full PHP resource compatibility are outside this item.


## Package acceptance dependencies found on the pinned migration target

Target inspected: `9b4b33f35f053b487e018c94d6a4a7888d77c64a`.
The retained package adapter hashes exact manifest/artifact/toolchain bytes with SHA-256,
checks the canonical driver's executable status, and hashes catalog identity plus raw
manifest bytes for composition. No exposed SHA-256 or executable-file query was found
in this target's runtime headers/specs, PHP builtin registry or portability function map.
This is a pinned-target inspection finding, not a claim about current upstream releases.

Before full acceptance, establish supported byte-string/file SHA-256 and executable-file
query APIs with target-owned behavior and PHP adapters. Prove empty/binary/NUL input,
known digest vectors, changed content, missing/unreadable files, executable/non-executable
files and platform policy. Preserve canonical driver lookup and artifact link checks.
Do not replace checks with expected digest strings, shell commands or a constant host
answer. Target implementation belongs to the v0.1 owner. No new GH request has yet been
sent for these findings. Receipt validation and guaranteed lock cleanup are separate
compiler/framework work; schema parsing is already independently proved.
