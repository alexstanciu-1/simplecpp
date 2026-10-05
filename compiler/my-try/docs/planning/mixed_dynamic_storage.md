# Mixed and dynamic storage: layout and shared implementation
Doc Status: planning

Recorded 2026-10-05. This records the approved staged implementation; language
meaning belongs to the [v0.2 family-separation amendment](../../../../specs/dynamic_types.md#v02-separated-mixeddynamic-families--2026-10-05),
not this plan. It supports
[CH06-MIX-DYN-01](../catalog/06_containers.md#ch06-mix-dyn-01--mixeddynamic-containment-contract).

## Recorded improvement: MIX-LAYOUT-01

Keep the existing mixed borrow counter, but move it beside the kind tag, before
the union. Current x86-64 layout is 1-byte tag, 7 bytes padding, 16-byte union,
2-byte counter and 6 bytes trailing padding: 32 bytes. Proposed ordering is
1-byte tag, 1 byte padding, 2-byte counter, 4 bytes padding and 16-byte union:
24 bytes, with the same fields and intended semantics. The counter currently exists
in both debug and release builds, although its borrow checks are debug-only.

Status: implemented and measured at 24 bytes, alignment 8, with Clang 18 on x86-64
Linux in debug (-O0) and release (-O2, NDEBUG). The borrow counter is retained;
duplicate preprocessor branches declaring the same field were consolidated.
This is an ABI change: rebuild runtime libraries and generated consumers consistently,
including stale PCH/build artifacts. Do not mix the old 32-byte ABI with this one.

Focused evidence: `python3 compiler/my-try/tests/mixed_layout.py --results
/tmp/my-try-mixed-layout-20261005-2` passed two matching runtime builds and four
native test programs. The new `test_mixed_layout.cpp` checks size/alignment, inline
scalars, string ownership, copy/move/self-assignment, kind replacement, table COW,
owned/weak payloads and balanced shared-container lifetime. Its behavioral checks
remain enabled under NDEBUG; the existing array-semantics program also passes in
both modes. Existing dynamic-box behavior is intentionally unchanged at this first
checkpoint, not endorsed as the target separated-family contract.

The initial broader probe (`/tmp/my-try-mixed-layout-20261005-1`) also ran the older
`test_mixed_t_dynamic.cpp`. It fails in debug on its expectation that bool/int
equality throws, and in release on its string `+=` expectation. Both failures were
reproduced with the unmodified HEAD header/32-byte layout in matching fresh runtime
builds (`/tmp/my-try-mixed-layout-baseline`). They are baseline operator-test drift,
not layout regressions; no operator behavior or old expectations were changed here.
That suite is not counted as passing or included in the focused layout runner.

Generated-program integration: `/tmp/my-try-mixed-layout-calls-20261005-1` passed
five PHP suites and 28 PCH-backed container-call programs against a fresh runtime.
FPM socket startup was unavailable, so PHP used the recorded CLI fallback. This
is not a native compiler rebuild or a broad regression run.

## Dynamic direction from discussion

The user accepts a 24-byte target for a tagged dynamic value: inline null/bool/int/
float, indirect string storage and shared container storage. No shared allocation
per numeric/boolean scalar. The public dynamic is still a shared hash handle alias;
the internal tagged value foundation is implemented below, with public migration pending.

A layout-only sketch using current wrappers measured 24 bytes at both -O0 and -O2
on the above target. Inline `string_t` increased the sketch to 40 bytes. These are
object sizes, excluding string/container allocations and ownership control blocks;
they are not portable size guarantees or functional implementation proofs.

The intended semantic direction separates mixed-family value/COW containers from
dynamic-family shared containers while allowing scalar/null leaves in each. JSON
should offer family-specific decoding, defaulting to dynamic including scalar roots.
The core normative permissions and casts are reconciled in the v0.2 amendment.
JSON's exact API/registration migration remains outstanding; its currently available
`result<mixed>` signature is not changed by a planning note.

### Confirmed semantic decisions — 2026-10-05

- New dynamic has one null state; default initialization produces null, distinct
  from a constructed empty container. Retire the old absent-handle/boxed-null
  distinction rather than carrying it into the tagged representation.
- Explicit mixed/dynamic container-family casts reject initially. Do not preserve
  the old shared-handle bridge under the new separated-family model. Recursive
  conversion is deferred; explicit scalar conversions preserve their scalar value.
  Known invalid crossings reject preparation, value-dependent ones require runtime
  checks without modifying the destination on failure.
- Continue deriving array-like/object-like shape from the internal hash query
  (`is_packed()` today; `is_pure_array` in discussion). Do not add persistent `[]`
  versus `{}` identity now. The user deferred that design to
  [CH15-SHAPE-01](../catalog/15_blocking_debt.md#ch15-shape-01--preserving-array-versus-object-identity).
- Callable values, source `->` support and reference foreach remain outside the
  current refactor. The 24-byte target and private shared-mechanics direction remain;
  concrete storage layout and integration need implementation proof.

## Shared implementation direction

Prefer independent public types backed by private reusable tagged-value machinery.
Neither type should derive publicly from the other or expose an unrestricted common
value that lets callers bypass family admission. A small policy-parameterized internal
storage implementation is a candidate; a virtual abstract base is not needed for
sharing algorithms and would introduce polymorphic layout/dispatch concerns.

Candidate common work: inline scalar storage, indirect string lifetime, exact scalar
access, scalar dispatch and diagnostic construction, and active-union lifetime code
where the family storage alternatives genuinely match. Keep existing scalar operators
as the owners of scalar semantics. Share code only after confirming equal behavior;
do not infer JS numeric/key/truthiness rules merely from the dynamic family name.

Family-owned work: allowed container alternatives, recursive entry type, COW detachment
versus shared mutation, container equality/identity, key admission and future property
access. No implicit mixed/dynamic conversion follows from shared implementation.

Important integration checks:

- Current mixed's union contains `dynamic_t<>` directly. Replacing that alias with
  a 24-byte tagged value while retaining this member would grow mixed's union and
  defeat its proposed 24-byte target. Resolve the obsolete cross-family alternative
  and compatibility requirements explicitly rather than mechanically changing the alias.
- `hash_t<mixed_t, mixed_t>` has a specialization. A dynamic-family container must
  not accidentally fall through to unsuitable generic hashing/storage. Reuse suitable
  table machinery with an explicit key/entry contract; public key types need not be
  identical to entry-value types.
- A policy name or template is not sufficient if internal storage still accepts
  arbitrary cross-family values through mutation, casts, native adapters or aliases.
- Current scalar-returning dynamic reads expose mixed facts. Preparation, access and
  iteration contracts must preserve the selected family's type after the redesign.
- Keep substantial implementation out of consumer headers where practical; explicit
  instantiation/out-of-line helpers may contain template recompilation costs. Measure
  with the existing PCH workflow; no compile-time improvement is claimed yet.

The shared table boundary is now selected and implemented below. The tagged value
must choose its private scalar lifetime and recursive container representation.
Runtime layout/lifetime, family rejection,
copy/alias semantics, JSON modes and compiler facts require separate focused proofs.

## Implementation sequence and current handoff

1. Mixed layout checkpoint: implemented as above; dynamic and its old bridge API
   are unchanged. No native compiler rebuild is claimed.
2. Shared table mechanics are implemented in the checkpoint below. New tagged
   dynamic's internal storage is implemented below; switching the public alias and
   consumers is next. Keep scalar
   semantics in their existing owners; remove obsolete mixed/dynamic coupling as a
   coordinated change rather than leaving both representations partially active.
3. Migrate runtime consumers together: initialization, reads/writes/paths, iteration,
   casts, truthiness/probes/count/identity, formatting/debugging and JSON. The inventory
   also finds dynamic assumptions in tasks and MySQL adapters and typed JSON conversion;
   reconcile these consumers rather than claiming only compiler-local impact.
4. Update shared compiler capability/conversion/access facts and backend bindings;
   add scalar-dynamic, same-family nested data and rejected-crossing proofs. Keep
   old object-only/shared-hash expectations separate from new normative semantics.
5. Core family contracts are reconciled below. Reconcile JSON API registrations, prove focused
   runtime/generated-program behavior and record remaining debt. Native compiler
   validation remains an explicit requested checkpoint.

### Shared packed/associative table foundation — 2026-10-05

Extracted the existing mixed-table algorithm into private implementation machinery
`hash_detail::packed_table<Value>`. The existing `hash_t<mixed_t, mixed_t>` now uses
that engine through private inheritance, with no virtual dispatch or extra wrapper
storage. Its setters preserve their public hash-reference result; `_find_val` keeps
its out-of-line mixed API and delegates to the engine's copied/default read.

Packed-to-associative shape, normalized int/string keys, insertion order, tombstones,
rehashing, storage accounting and traversal remain one implementation. Values are
now typed independently of those keys. The new dynamic can reuse the engine with
dynamic-valued entries instead of boxing them into mixed or duplicating hash logic.
Internal key carriers still use mixed scalar keys; that does not admit mixed-valued
entries. This does not register new source-language hash/key capabilities.

Entry-view/iterator implementation types now belong to the engine. Obtain them
through the existing range/begin/end APIs, not by constructing them from private
storage-owner pointers. Internal references remain subject to the existing access
boundary; no new source reference capability or mutation-during-iteration guarantee
is introduced. This foundation does not implement the new dynamic representation,
scalar storage sharing, separated-family casts or JSON changes yet.

`test_packed_table_storage.cpp` instantiates the engine with an independent value
that cannot be converted to mixed. It checks packed shape, key normalization and
wrong-kind rejection, copied/default/checked reads, 600-entry growth, tombstone
iteration order, container copy/move, failed value-copy replacement and the existing
mixed wrapper. Static checks prove private implementation separation, no polymorphic
base and no additional wrapper size. Behavioral checks stay enabled under NDEBUG.

Evidence: `/tmp/my-try-packed-storage-20261005-3` passed debug/release runtime and
JSON builds plus eight native test programs (mixed layout, array semantics, hash
and independent-value storage). The JSON object is linked because the existing hash
suite uses JSON encoding in debug assertions. Initial probe corrections were test
accessor spelling and that missing JSON link, not production algorithm changes.
The final static assertions were recompiled and executed in both modes under
`/tmp/my-try-packed-storage-final-20261005` using the matching runtime objects.
`/tmp/my-try-packed-storage-calls-20261005-1` passed five PHP suites and 28 generated
container-call programs. `/tmp/my-try-packed-storage-iteration-20261005-1`
passed five PHP suites and 34 generated iteration programs. These are focused
proofs, not a native compiler rebuild.
Final review corrected the engine's unsupported-reference overload to keep its
mixed scalar key parameter independent of `Value`; a non-mixed-value test now
exercises that rejection. `/tmp/my-try-packed-storage-20261005-4` rebuilt both
matching runtimes/JSON objects and passed all eight native programs after this fix.

Future callable-valued entries are recorded as
[CH15-CALLABLE-01](../catalog/15_blocking_debt.md#ch15-callable-01--callable-values-in-mixeddynamic-entries).
Their representation is a design consideration, not part of this refactor's
implementation scope or permission to introduce callable execution early.

### Contract reconciliation before the representation switch — 2026-10-05

The top-level dynamic/data-initialization/array/index/probe contracts now record
the approved v0.2 separation. This supersedes the earlier mixed-only dynamic-entry
proposal; it does not retroactively change v0.1 or claim the runtime is migrated.
Dynamic initialization interfaces now name dynamic input values, and dynamic reads
and iteration retain dynamic results. Scalar/null explicit family conversions and
container rejection are consistent across construction and mutation. Scalar copies,
shared-container aliases, null versus empty construction and non-container iteration
are explicit. Keys remain independent of entry-value types.

At this documentation checkpoint implementation remained at shared-table storage. No public tagged dynamic type,
family rejection, new JSON default or compiler capability change is claimed by
this documentation checkpoint. The old boxing/crossing tests describe the old
implementation and must be replaced during migration, not preserved as new semantics.
JSON family-specific decoding is recorded as direction without inventing a mode
parameter or function name before its callable/API migration.

### Tagged dynamic storage foundation — 2026-10-05

The user approved a coordinated v0.2 breaking runtime change, without preserving
the old v0.1 mixed/dynamic behavior. Migrate JSON, typed JSON conversion, tasks,
database adapters and compiler facts together; do not install a legacy-family
conversion bridge. Regenerate/rebuild consumers and PCH after the public switch.

`scpp::detail::dynamic_value` now implements the target storage independently of
mixed: a variant of null, inline bool/int/float, an owned string wrapper and a shared
`packed_table<dynamic_value>`. The existing table engine supplies recursive storage,
key normalization and traversal; no second hash implementation was introduced.
Scalar operator semantics have not been duplicated into this storage type.

Copies preserve scalar values, copy the owned string wrapper and share container
identity. Moves leave null; self-copy/move is safe. String copying completes before
destination replacement, and payload moves are statically required to be nonthrowing.
Only the internal factory can create a container payload, so an absent shared handle
cannot create a second null state. Exact access rejects wrong kinds without mutation.
Mixed construction/assignment is deleted; no implicit cross-family storage admission
is exposed. Explicit checked scalar conversions belong to the later cast integration.

This is an internal staging type, not a second source-language family or a
compatibility mode. The public `dynamic_t` alias, obsolete mixed `dynamic_v` carrier
and existing consumers are still unchanged. The next coordinated switch must use
this implementation and remove that old relationship. Do not claim source-level
family separation, dynamic scalar support or the new JSON default yet.

The focused `test_dynamic_value.cpp` measures 24 bytes/alignment 8 on this target
and checks exact scalar access, string lifetimes, copy/move/self-assignment, null
versus empty container, parent/child aliases, variable replacement, child survival
after removal, same-family missing reads, growth and typed iteration. Compile-time
checks reject mixed and the old dynamic handle. It is included in the shared
debug/release runner and CMake runtime tests.

Evidence: `/tmp/my-try-tagged-dynamic-20261005-2` rebuilt matching debug/release
runtime and JSON objects and passed all ten programs, including the existing mixed,
array/hash and shared-engine checks. `/tmp/my-try-tagged-dynamic-sanitize-20261005-2`
passed the new value test with AddressSanitizer and UndefinedBehaviorSanitizer.
Leak detection was disabled for that run: the initial sanitizer attempt failed
because LeakSanitizer cannot run under this sandbox's ptrace environment, not from
a reported payload defect. No leak-check pass, migrated source-program behavior,
native compiler rebuild or completed public dynamic migration is claimed.
