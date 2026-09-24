# Historical GitHub issue snapshot
Doc Status: historical

This snapshot predates removal of views and the separate Keyed_Storage design.
“Latest” and “current” below describe the publication checkpoint only. It is not
the current local requirement. Use [the native brief](STORAGE_NATIVE_TASK.md) and
[collection API](STORAGE.md); GitHub reconciliation remains pending.

Verified issue-body snapshot after the default shared-record update, 2026-09-24.
Detailed follow-up: https://github.com/alexstanciu-1/simplecpp/issues/242#issuecomment-5812738509

> **Latest direction (2026-09-24):** Use automatic `shared_p<T>` record wrapping for the initial compiler port. Public `T` remains the record type in Storage/Storage_View. Reads preserve shared identity; retrieved handles survive replacement/removal. This supersedes the value-row/no-automatic-wrapping decision in the first native slice. Inline layout and weak row references are deferred. See the latest direction-update comment for the complete request.

## Updated request

Implement native `Storage<T, UseStringKey=false>` and `Storage_View<T, ReadOnly=true>`
with PHP behavioral parity and native-optimized internals, plus explicit conversion
bindings. This update replaces the earlier three-argument Storage/read-only design.

Changes: separate owner and view; read-only policy belongs to view membership;
capacity is a constructor argument; views reference owner positions; explicit
`storage_append` creates and includes a row. The PHP view hierarchy is interface,
abstract implementation with optional backing owner, and empty final concrete view.
Native value layout and weak-reference semantics remain open discussion items.

The reference is in the local `compiler/my-try/` work area and is not assumed to be
published on GitHub. This issue therefore includes the self-contained delivery
brief. Relevant local files: helpers/storage.php, storage_view_iface.php,
storage_view_abstract.php, storage_view.php, STORAGE.md, STORAGE_VIEW.md; behavioral
fixtures: tests/storage.php, storage_view.php, ast.php and model.php.

## Objective and scope

Provide Storage<T, bool UseStringKey = false> as a native runtime helper for the
small compiler, preserving its fluent reads/writes and PHP reference semantics.
T names the record type in author annotations, e.g. Storage<token, false>.
Numeric specialization is the first vertical slice, followed by string mode and
secondary-index hooks. No generic ORM, persistence, concurrent mutation, automatic
weakrefs, query language, compaction, scalar boxing or model rollback in this task.

## Behavioral reference, not an implementation blueprint

PHP defines observable behavior. Native code should use a representation designed
for C++ rather than reproduce PHP arrays, the positions map, object layout or
internal method structure. The behavior and acceptance sections below are required;
the layout candidates are optional. Propose and measure alternatives freely when
they preserve those requirements. If a proposed optimization changes behavior,
identify that contract change explicitly before implementing it.

## Required behavior

- One Storage owns membership and primary indexes. Records have shared identity.
  Assignment aliases the container; constructing a new Storage creates another.
  Do not implicitly copy its native buffers on source assignment. No clone/deep
  copy API is promised; PHP's incidental clone/serialize features are not portable
  Storage APIs.
- T remains the record type at the author surface. Storage and its records retain
  shared identity: removal/replacement drops membership but existing handles still
  refer to the old record. Growth/reserve must preserve those handles and their
  observable identity. The concrete handle/allocation strategy is an implementation
  choice; no particular smart pointer, per-record allocation or physical address
  layout is required. Reuse established target handles when suitable and avoid
  redundant wrappers. Any exposed native references must honor their lifetime
  guarantees even if an alternative representation relocates internal storage.
- This preserves current sharing but does not solve cycles in the compiler graph.
  Backrefs/weakrefs and eventual cycle reclamation need a separate model ownership
  pass before claiming bounded memory for a long-lived native compiler. Do not
  silently weaken references inside Storage or change all records to values.
- Numeric positions are monotonic and local to one container. Removal leaves a
  hole. Replacement preserves the position. Readding a removed string key assigns
  a fresh position. No position reuse or implicit compaction.
- count() is live rows, not slot extent/capacity. is_empty() uses live count.
  append returns its assigned position. Use a signed 64-bit public position
  domain for the compiler profile, reserving INT64_MAX as exhausted next-position
  state; reject exhaustion before hooks or writes. Check conversions to native
  sizes. Native allocation limits can reject earlier without truncating IDs.
- Numeric [] append is allowed. String mode requires an explicit key. Integer
  assignment replaces an existing position only. String assignment inserts or
  replaces by primary key. String keys are unique, externally supplied and not
  extracted from record fields. Empty and numeric-looking strings remain strings.
- Reads of missing rows throw and never insert. isset tests membership; unset of
  an absent valid offset is a no-op. remove(position) requires an existing row.
  Wrong-mode offsets reject. A string-mode integer offset means a position.
- position_of requires an existing primary key; find_position returns absence
  without throwing for a missing key. Both accept integers only in numeric mode,
  strings only in string mode. Position zero is not absence.
- reserve(total_slots) allocates ahead without changing count, positions or
  record identity, and does not shrink. Constructor capacity is optional with
  default zero. PHP reserve validates but allocates nothing. Negative capacity
  rejects. Do not expose a fictitious PHP capacity value for parity.
- Iteration is live-row order, skipping holes: keys are positions in numeric mode
  and strings in string mode. Mutation/reserve during iteration is unsupported;
  native mutations/reserve may invalidate all iterators. Record handles survive.

## Storage and view separation (current local contract)

Storage<T, UseStringKey=false> owns records and primary keys. Remove its ReadOnly
parameter and internal_* aliases. PHP constructor is Storage(bool use_string_key
= false, int capacity = 0); native capacity is a constructor argument, not a template.

Storage_View_Iface defines collection reads and membership operations.
Storage_View_Abstract contains the reusable default implementation and optional
backing owner reference. Storage_View extends it, is final, and has an empty body.
ReadOnly=true belongs to the view (native intent Storage_View<T, ReadOnly=true>).
PHP constructor is Storage_View(?Storage storage=null, bool read_only=true).

Default views contain ordered numeric references into one owner, independent of
that owner's numeric/string key mode. No automatic owner mirroring. Missing backing
rejects all default access; specialized subclasses can implement virtual access.

internal_append(owner_position) returns a view position; internal_replace redirects
membership; internal_remove removes membership, not owned records. Ordinary
append/replace/remove and [] writes/unset respect read-only policy. View [] writes
accept owner positions; reads return records. Record fields remain editable.
storage_position(view_position) translates positions. Duplicates are allowed;
view positions preserve holes. Count and isset describe membership. Owner removal
leaves dangling references: dereference throws, and membership can be removed.
Owner replacement is observed through every view of that position.

storage_append(record, optional_owner_key) explicitly creates and includes a
record, returning its owner position, even on a read-only view. If owner validation
fails, view membership is unchanged and usable. If owner writes fail, owner failure
propagates to views. If owner append succeeds but membership writes fail, the view
fails closed and the owned row remains; no implicit rollback. Other views of a
healthy owner remain usable. Preserve guards and owner index hooks on every path.

The PHP helper split and file-owned AST stores/views are implemented and tested.
Linked AST layout and native record representation remain deferred.
See STORAGE_VIEW.md and tests/storage_view.php for the detailed contract/proofs.
This supersedes the earlier read-only-on-Storage design in issue #242. The current
delivery contract separates owned records from view membership.

## Mutation and failure protocol

Keep the six before/after hooks, with position/key/record for add/remove and
position/key/old/new for replacement. Derived classes may maintain indexes.
Every mutation entry point, including reserve and absent-row unset, rejects
same-owner reentrancy. All exits release the guard. Reads from hooks are allowed.
Base representation and mode are private; key mode is a compile-time constant.
Derived query APIs call assert_usable before reading their own indexes.

Validation/before hooks must not mutate records, indexes or external state. A
rejection leaves logical state and the position counter unchanged and owner usable.
Native allocation preparation may reserve capacity before writes; failures there
also leave logical state unchanged (capacity changes are allowed).

Once writes begin, catch an escaping exception, mark that owner failed and rethrow
the original error. Later reads/writes/count/lookup/iteration/reserve reject. There
is no revive/reset method. Discard/rebuild the affected model segment; other graph
indexes may need rebuilding too. Do not attempt arbitrary callback rollback.
Destruction of partially updated native state must remain safe. RAII must release
any candidate allocation. An after hook may allocate, but failure then follows
this fail-closed rule. No process termination is required by this contract.

Unindexed fields may be changed through record handles. Indexed fields require
replacement with a new record; the old record supplies old index keys. A handle
obtained before failure is not revoked. This is not isolation or a transaction.

## Native performance objectives

Compile-time key mode should eliminate string-index storage and mode dispatch
from the numeric specialization. Aim for amortized O(1) append, O(1) positional
access and expected O(1) string lookup/update. Keep empty and small containers
lightweight and avoid unnecessary allocations. Reserve should allow useful
preallocation. Report costs under deletion-heavy workloads as well as dense ones;
logical positions must remain stable regardless of physical placement.

These are design objectives to demonstrate with measurements, not a requirement
to copy PHP internals. Justify tradeoffs explicitly. Index representations must
cover the full live position range, including after long-running churn; compact
width selection must not truncate positions based only on live count.

## Optional implementation candidates

- Numeric mode could use ordered handle slots with tombstones, segmented storage,
  or another compact position-addressable representation. Empty-handle tombstones
  are one possibility because null records are forbidden, not a required layout.
- String mode could combine ordered slots with a key-to-position hash, or reuse
  a suitable ordered hash primitive. Key ownership, allocation strategy, physical
  placement and lookup machinery should suit native performance. Avoid redundant
  record copies while preserving identity; measure any extra indirection.
- Existing hash_t may supply useful internal mechanisms, but reuse is optional.
  Its ordinary [] behavior differs from Storage reads. Any reuse must pass the
  Storage tests, including missing keys, failures, positional access and churn.
- Controlled lowering or a tested proxy can preserve fluent assignment. Whatever
  approach is chosen, writable slot access must not bypass hooks; ordinary record
  field access must remain available under the indexed-field rules.
- Small buffers, arenas, compact index widths and hook-dispatch strategies are
  candidates for measurement. No new policy-template family or particular
  smart-pointer layout is mandated. Optimize the common numeric case first.

Native internal method names, member layout, allocation and growth strategy need
not match storage.php. Public behavior and supported source operations must match.

## Conversion integration is a separate deliverable

Recognize adjacent Storage<T, UseStringKey> and Storage_View<T, ReadOnly> annotations for properties, parameters,
returns and typed construction locals. Preserve short record names and boolean
modes. Construction must agree with that mode; a dynamic PHP constructor mode
cannot silently become a template argument. View read-only mode must also be constant. Fixed concrete uses are the first
scope. Preserve default numeric behavior and optional capacity at construction.

Prove [] read/write/append, isset/unset, foreach, count/is_empty, explicit methods
and derived hook dispatch. Runtime implementation alone does not complete these
lowering rules. Do not patch generated C++ as the final integration fix.

## Acceptance

Port the behavioral cases from tests/storage.php; add native-only allocation and
lifetime proofs. At minimum:

1. Both modes: empty/one/many, reserve/growth, aliases, record identity after
   replacement/removal, missing reads, invalid offsets, duplicates, zero position,
   empty/numeric-string keys, holes and deleted-key reinsertion.
2. Hooks: ordering and key arguments, old/new identity, before rejection with no
   consumed position, all mutation reentry paths, reads during hooks, after-error
   fail-closed state, usable independent owner, safe destruction after failure.
3. Inject allocation failures during slot/hash growth and subclass index updates;
   prove the specified usable-or-failed state without leaks or partial usable data.
4. Force overflow through a bounded test seam; test index-width boundaries and
   repeated remove/reinsert with low live count and growing physical positions.
5. PHP/native fixture parity and compiler model/sample behavior. No blanket claim
   of whole-compiler convertibility from helper success.
6. Report empty owner size, allocations/bytes per live record, append/read/iterate,
   string lookup/update, and hole-heavy churn. Separate handle/control-block costs
   from slot/index costs. No performance thresholds invented without a baseline.

The PHP compiler currently passes 38 lint files, the Storage/view/AST/model tests, 19 LLVM
native fixtures, 28 call fixtures and the sample exit value 9. These are regression
baselines for the PHP compiler pipeline, not native Storage evidence.


## Current public surface and binding examples

PHP Storage constructor: `new Storage(bool $use_string_key = false, int $capacity = 0)`.
Storage exposes append(record, optional key), replace(position, record),
remove(position), position_of(key), find_position(key), reserve(capacity), count
and is_empty. append returns an owner position. Storage has no read-only argument
and no internal_* aliases. The six before/after index hooks remain on Storage.

PHP view constructor: `new Storage_View(?Storage $storage = null, bool $read_only = true)`.
View exposes append(owner_position), replace(view_position, owner_position),
remove(view_position), their internal_* counterparts, storage_position(view_position),
storage_append(record, optional owner key), count and is_empty. append and
internal_append return view positions; storage_append returns an owner position.
PHP adapters supply [], isset/unset and foreach on both containers.

```php
$nodes /** Storage<ast_node, false> */ = new Storage(false, 64);
$children /** Storage_View<ast_node, true> */ = new Storage_View($nodes);
$node = new ast_node();
$position = $children->storage_append($node); // Owner position, also included in view.
$children->internal_append($position);       // Another membership, returns view position.
$children[0]->token_index = 7;               // Record mutation remains allowed.
// $children[] = $position;                 // Reject: ordinary read-only membership write.
```

The example illustrates the helper boundary, not complete AST construction.
Storage_View must extend Storage_View_Abstract, be final and contain no added code
at the PHP surface. The abstract implementation holds the optional backing Storage;
virtual subclasses may override access. Native internals can differ while preserving
this extensibility and the public behavior. Views support either backing key mode
without adding a key-mode argument to the agreed Storage_View<T, ReadOnly> shape;
select and document the native backing-reference representation.

Converter integration must not infer a specialization from remote receiver types.
Prove explicit construction-local annotations first. Current compiler constructors
also contain `$this->tokens = new Storage()` paired with an annotated property:
choose and document a bounded local rule or adapt to an explicit typed local. Do
not silently emit an untyped Storage or discard boolean template arguments.
Reject unsupported or mismatched mode annotations with source attribution.
The current checker rejects custom Storage annotations; native implementation
alone will not remove this conversion blocker. Keep helper PHP implementations
outside converted input and assemble the native bindings explicitly.

Additional acceptance for views:

- Both owner key modes, both membership policies, duplicate memberships, holes,
  owner/view position distinction, and record-field mutation through read-only views.
- Missing backing rejects default reads and writes; a specialized virtual subclass
  demonstrates supported overrides. Empty final concrete view needs no extra code.
- internal_* changes membership only; storage_append executes owner hooks and
  includes exactly one new membership. Other views are unchanged.
- Replacement redirects future reads; owner removal leaves membership present but
  dereference fails. Already retrieved handles follow the existing handle contract.
- Exercise owner validation rejection, owner write failure and membership failure
  after successful owner append; verify the specified usable/failed states.
- Exercise conversion of declarations/construction and every public operation,
  including iteration and typed record access, with PHP/native behavior comparison.

## Open representation discussion — do not infer a final layout

The shared-handle behavior above is the current compatibility baseline, not a
final decision that every compiler row must be separately allocated. Efficient
owned token/AST segments, inline values, bounded references and weak backlinks
remain under discussion. Flag any conflict before implementing a different lifetime
contract; do not add an ownership template policy or automatic weak-reference
lowering without agreement. Ownership annotations in the compiler are documentation,
not converter support. Static Model fields and general record-initialization rules
are separate conversion work, not added requirements of this helper issue.


---

## Direction update: default shared_p<T> records for the initial compiler port

Please switch the native helper to **shared_p<T> wrapping by default**. This is the user's latest decision and supersedes the initial value-row/no-automatic-wrapping contract in commit ce1885b9 and the earlier bounded-reference direction for this first integration.

The priority is to stabilize and convert the existing PHP compiler with natural shared record access. Inline records and a future lower-level on-memory layout layer are deferred; do not introduce a new ownership-policy template or weak row-reference mechanism for this slice.

### Requested public contract

- Keep the source/native public template shapes `Storage<T, UseStringKey=false>` and `Storage_View<T, ReadOnly=true>`. **T is the record type**; callers should not have to spell `Storage<shared_p<T>, ...>` or add another template argument.
- Store/return `shared_p<T>` record handles internally and at record-access boundaries, using the existing Simple C++ shared-handle type. Avoid double wrapping.
- Append/replace accept an existing shared record handle and preserve its identity; do not copy T or create a second record allocation. Empty/null record handles remain invalid, consistent with the PHP helper's object-only rows.
- Reading through Storage or a view returns a shared handle to the selected record. Fluent field edits must affect that same record, not an implicit snapshot copy. Source `[]` integration remains a distinct binding deliverable; native read behavior should be ready for it.
- Owner growth/reserve/physical row relocation must not invalidate retrieved record handles.
- Replacement changes the record selected by that owner position. Handles obtained earlier continue to refer to the old record. New reads, including view reads, see the replacement.
- Removal drops membership and the owner's handle; previously retrieved handles keep the removed record alive. A view membership pointing to the removed position remains dangling and throws when dereferenced, as already specified. Do not retarget old handles to a replacement.
- Read-only views restrict membership, not mutation of fields on referenced records. Indexed fields still require replacement with a new record so hooks can observe old keys; arbitrary in-place indexed-field edits are not made safe by shared ownership.
- Preserve both key modes, stable positions/holes, primary-key behavior, mutation/index hooks, failure/reentrancy guards and storage_append semantics.

### Snapshot naming, ownership and validation

Review the existing snapshot/field/set_field API and fixture assumptions. A copy of a shared handle is an alias, **not an independent record snapshot**. Rename or document accordingly; do not promise generic deep copying. Explicit independent record-copy operations can be designed separately if needed.

Container ownership remains separate from record ownership: views keep their currently agreed backing-owner handling. Shared records do not solve ownership cycles, including a stored record containing a view of its own owner. Keep that limitation explicit; for now rely on deliberate lifecycle cleanup/code discipline rather than widening this into automatic cycle collection.

Please update the native contract and draft PR #243, and add focused tests proving:
1. Same identity through append/read/view access; field mutation visible through all aliases.
2. Old handles survive replacement/removal and growth, while fresh view reads select current membership.
3. Final record destruction occurs when the last shared handle is released (acyclic fixture); null handles reject.
4. Existing numeric/string, read-only membership, hooks and failure tests still pass under handle storage.

Report the new exact commit and any remaining source-binding limits. The PHP helper work area should remain unchanged. This request chooses the simple initial shared-identity implementation; it does not abandon later native layout/performance work or claim whole-compiler conversion is complete.
