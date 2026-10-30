# Ownership foundation review and proposed v0.2 contract

Doc Status: planning

The [PHP compiler](../../compiler/README.md) supersedes the retired compiler
implementations. Adapting their ownership handles is no longer planned.
Runtime ownership/value support remains useful for generated programs and plain C++
consumers. Earlier compiler-adaptation recommendations below retain historical context.
Date: 2026-10-29
Status: design baseline with the first implementation checkpoint below. Remaining
proposals are not implemented contracts; compiler adaptation remains deferred.

## Reviewed owners and evidence

- Existing runtime: runtime/include/scpp/{memory,shared_p,unique_p,weak_p,value_p}.hpp.
- Boundary composition: runtime/include/scpp/shared_conversion.hpp and shared_self.hpp.
- Semantic authorities: specs/type_use_modifiers.md, especially explicit ownership,
  nullable records, owned construction and value normalization; runtime/specs/spec.md
  sections 6.6/6.7. Older runtime/specs/config.review.md describes a broader legacy
  API and nullable-handle lowering; its examples do not override later amendments.
- Tests inspected: ownership shared/unique/weak basics, unique upcast, weak assignment
  and lock; lifecycle last-shared-owner destruction; value move/copy churn;
  native/test_shared_self.cpp. These are reusable proof inputs, not claims of a
  fresh legacy test run. Compile-fail unique-copy and value-of-handle fixtures were
  located for future adaptation.
- Current v0.2 consumers: compiler/src/token_buffer.cpp and token_sequence.cpp.
  Their private state allocations wrap std::shared_ptr handles; copying a public
  handle allocates a new state even though the payload is shared.

## What the legacy implementation actually does

| Family | Representation | Copy/move and access |
| --- | --- | --- |
| shared_p<T> | Inline std::shared_ptr<T> member | Copies ownership, not T; moves transfer ownership; checked dereference |
| unique_p<T> | Inline std::unique_ptr<T> member | Copy deleted, moves transfer; checked dereference; legacy raw release/reset |
| weak_p<T> | Inline std::weak_ptr<T> member | Observes shared ownership; lock returns shared or empty; legacy bool observes expiration |
| value_p<T> | Inline T member | Payload copy/move semantics; always present; no wrapper-owned heap allocation |

Factories use std::make_shared and std::make_unique. They do not implement a custom
reference-count algorithm. Therefore the compiler's extra state allocation is not
an inherent cost of the legacy ownership model; it was introduced to hide template
representations behind a concrete header.

`value<T>` is neither a nullable box nor an automatic recursive deep copy. Payload
fields keep their own copy semantics: a shared field still shares its pointee.
The wrapper does not allocate, but its payload constructor or members may allocate.
Existing value types may normalize directly without an extra wrapper. Source
class/struct storage defaults and redundant-modifier rules remain compiler-owned.

## Proposed public behavior

Generate requested concrete specializations and constructor/factory signatures;
no public template, standard smart-pointer member or transitive template header.
Names such as shared_record and weak_record are illustrative, not a frozen naming
scheme. Do not create an ownership-by-numeric-operator matrix.

| Family | Default | Copy | Move | Access |
| --- | --- | --- | --- | --- |
| shared | Empty | Same object/control ownership | Transfer; source empty | Checked * / ->; has_value and explicit C++ condition |
| unique | Empty | Deleted | Explicit transfer; source empty | Checked * / ->; has_value and explicit C++ condition |
| weak | Empty/expired | Same observer | Transfer; source empty | lock only; no * / -> or bool conversion |
| value | Payload's valid default, or deleted default ctor | Only if payload copyable | Payload behavior; source remains a valid value | Inline payload access; no empty/reset-to-null state |

Shared/unique/weak reset and swap preserve their family meaning. Self-assignment
and self-move preserve valid ownership without duplicate destruction. Payload
construction remains explicit. Proposed plain-C++ null construction is explicit;
source null assignment/default admission remains governed by compiler semantics,
with reset/null assignment published as selected concrete operations. No implicit
handle-to-payload conversion: accessing T never silently clones or extracts T.

Keep one preferred public spelling per operation rather than importing all legacy
aliases (deref/arrow/get/native_value plus operators). Start with checked * / ->,
has_value for owners, reset/swap and weak.lock(). An explicit weak-from-shared
constructor is sufficient for the initial C++ surface. expired() may be published
as a diagnostic observation; it is never a substitute for lock(). No direct weak
null comparisons or expiration-based bool. No eager logical operator overloads.

Empty owner dereference throws using the current v0.2 standard-exception boundary;
do not import the legacy structured runtime_error subsystem merely for this slice.
The exact exception type/message must be frozen before implementation. lock() on
expired/empty weak returns an empty shared handle, with no nullable/result layer.
Keep use_count/debug helpers private or proof-only initially; counts do not grant
exclusive access or establish synchronization.

## Lifetime, concurrency and constness

- Last strong owner destroys the payload exactly once. Weak references do not
  prolong payload lifetime; they may retain control/allocation storage afterward.
- Successful lock acquires a strong owner atomically relative to final release;
  failed lock never resurrects a destroyed payload.
- Distinct handles may share ownership across threads with standard smart-pointer
  guarantees. Concurrent mutation of the same handle needs synchronization; payload
  data races remain the caller's responsibility. No cycle collection is implied.
- const handle and handle-to-const are different. const shared handle does not
  freeze a mutable payload. Publish a concrete read-only payload handle where
  compiler snapshots require immutability; never discard pointee constness.
- Borrowed references from owner access last only while a suitable owner retains
  the object. Compiler reference/escape rules remain separate from runtime RAII.
- Destruction must be nonthrowing. Value copy/move exception specifications and
  capabilities must follow the actual payload, never unconditional noexcept.

## Formation and conversion boundaries

Preserve existing v0.2 source rules: no direct ownership-of-ownership, no
value-of-ownership/ref, no owned void, and no redundant nullable layer on explicit
shared/unique/weak. This is not a recursive ban on records with owning fields.
Shared/weak copyability does not require a copyable pointee. Inline values do.
No numeric/text casts or carrier extraction rules implicitly unwrap owners.

Initial publication can be exact-payload only. Later admitted derived-to-base and
mutable-to-const conversions must be concrete requested entries: preserve object
identity and destruction, perform correct base adjustment, and forbid downcasts
or const removal. Legacy unique upcast specifically requires a virtual base
destructor (except same underlying cv type); retain that safety restriction if
using default-delete semantics. Shared upcasts retain the original control block.
Do not use an unadjusted void-pointer cast as an inheritance conversion.

Do not port nullable shared-conversion machinery into the runtime blindly:
required/optional source object identities can share the same physical handle,
while the symbol-aware compiler inserts required-presence checks. That differs
from adding nullable<shared> as a public ownership specialization.

Result-of-shared/value support is a later explicit capability decision. Current
carrier extraction copies payloads, so result-of-unique must not be admitted until
an explicit consuming extraction contract exists. Direct ownership handles do not
inherit the carrier implicit extraction policy.

## Representation recommendation and alternatives

Prefer retaining standard smart-pointer lifetime machinery before writing a new
runtime reference-count implementation. The legacy algorithm to reuse is the
standard library's ownership implementation, not its publicly templated wrapper.

A candidate for shared/weak is inline opaque storage containing a privately
constructed std::shared_ptr<T>/std::weak_ptr<T>. Generated concrete special members
operate on that storage out of line. This avoids per-handle heap allocation while
keeping the tested lock/control-block behavior. It needs a native-target binding
for size/alignment, validated against EACH actual private specialization, and
correct placement construction, access and destruction. Do not assume two pointers
or memcpy smart pointers. A mismatch must fail the build, not silently overflow.
The project already uses native-target-specific generation; stable cross-toolchain
ABI is not promised. Validate feasibility before committing to this representation.

For unique, either a concrete T* with generated destruction/move or the same private
inline-storage scheme is viable; default-deleter exact-payload scope is sufficient
initially. For value, prefer a direct concrete payload member or normalized payload
identity, using defaulted operations where correct. A genuinely inline value needs
a complete template-free payload representation in its header. Opaque arbitrary
payloads cannot be made inline by merely hiding their definition behind a pointer.

Alternative: a runtime-owned shared control block with strong/weak counts and
private typed destruction, exposed through concrete pointer fields. This avoids
standard-library layout binding but requires implementing and proving lock races,
control-block lifetime, pointer adjustment and reclamation. A simple intrusive
count inside the payload alone is insufficient for weak handles that outlive it.
Do not choose this merely to avoid private standard-library templates.

The generator should own repetitive lifecycle emission. Runtime private storage
helpers own representation mechanics; compiler source lowering owns move syntax,
required-object checks, source constness/admission and sequencing. Metadata declares
roles, requested payloads, constructor signatures and conversions; actual C++
capabilities/layout must be verified, not trusted because JSON asserts them.

## Allocation targets and compiler application

- Default empty handles, owner/observer copy and move, reset/swap, weak derivation
  and lock: no wrapper heap allocation. Payload destruction may release its own data.
- Unique creation allocates payload storage; shared creation should use the normal
  make_shared path. Measure concrete supported-toolchain allocations; do not claim
  a universal standard-mandated single allocation or count payload-internal work
  as ownership overhead.
- Inline value introduces no wrapper allocation.
- No blanket promise that unique-to-shared transfer is allocation-free: it is not
  a legacy public operation here and may need a control-block allocation. Defer it
  or specify an explicit fallible operation separately.
- Token-builder publication can instead build under a private shared owner from
  the beginning, expose no mutable aliases, then transfer into a read-only handle.
  This is a compiler construction protocol, not a generic ownership cast that
  magically freezes other mutable aliases. Source/token adoption happens only
  after the runtime ownership proof; keep current compiler implementations meanwhile.

## Bounded implementation sequence, if approved

1. Agree on the family behavior and representation binding. Use a small concrete
   registered test record, including a noncopyable record and destructor counters.
2. Add shared/weak as one lifetime unit and unique alongside them, using the existing
   metadata/preparation/emission pipeline. Establish template-free consumer proofs
   and no-allocation handle copies before expanding conversions or consumers.
3. Add value construction for a concrete template-free record, proving memberwise
   copying, noncopyable propagation and potentially-throwing moves. Normalize
   already-value payloads rather than emit pointless wrapper classes.
4. Adapt compiler snapshots and generations in a separate consumer slice. Recheck
   allocations, ownership tests and focused compiler build timings.
5. Review inheritance, shared-self, result/container composition and other consumers
   individually. Keep hash/mixed/dynamic's independent reviewed queue active.

Required proofs: last-owner destruction, weak expiration/lock success/failure,
copy/move/self-assignment/reset, throwing construction cleanup, noncopyable payloads,
noexcept propagation, empty access failures, raw adoption/unique-copy rejection,
public include purity, deterministic generation, per-type layout assertions and
allocation counters. For shared/weak, include concurrent lock-versus-final-release
coverage; sanitizer passes alone are not a complete concurrency proof. Later
inheritance/shared-self work must include multiple-base pointer adjustment and
exactly-once destruction using the existing shared-self fixture as a donor.

## Explicit reuse and change summary

Reuse semantics, private standard smart-pointer engines, checked access principles,
factory construction policy and bounded legacy lifetime/negative test cases.
Replace publicly templated wrappers with requested generated concrete APIs; remove
per-copy pimpl allocation, legacy weak truthiness, public native smart-pointer
exposure, raw adoption/release and duplicated accessor spellings from the initial
surface. Fix value exception specifications and propagate real payload capabilities.
Do not bulk import detail.hpp, shared_conversion.hpp, shared_self.hpp, the error
subsystem or an ownership arithmetic matrix. No source/compiler behavior has been
changed by this review.

## First implementation checkpoint — 2026-10-29

Shared/weak/unique now have a bounded metadata-driven concrete-record publication
and inline private standard-pointer storage. The precise implemented API, omissions,
allocation measurements and native-target constraints are in
[ownership_slice.md](../ownership_slice.md). Two test records prove copyable and
noncopyable payload ownership. Generated artifacts stay ignored; no compiler
consumer was adapted. Inline value generation and the remaining proposal entries
above remain future slices.

## Inline value / const-pointee checkpoint — 2026-10-29

The next bounded slice implements direct inline record values with payload-derived
special members and exception specifications, plus shared/weak/unique const-pointee
specializations and explicit add-const conversions. The generator prepares concrete
qualification signatures; no runtime type deduction or ownership overload matrix is
introduced. Four proof records cover copyable, immovable, over-aligned move-only and
throwing-move payloads. See [ownership_slice.md](../ownership_slice.md) for the current
contract and measurements. Source normalization, inheritance/self protocols and
compiler adaptation remain future work; the earlier checkpoint is historical.
