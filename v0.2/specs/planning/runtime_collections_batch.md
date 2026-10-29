# Runtime assessment and typed-hash batch

> Status update (2026-10-29): Rust implementation and toolchain retired.
> This note is historical context; current owners are the PHP compiler and PHP API
> generator. See [compiler status](../../compiler/README.md).

Doc Status: planning
Date: 2026-10-29
Status: implementation goal complete; H0–H6 delivered and validated.
Baseline: v0.2/develop-rust at 6eb564954.

## Assessment

The rewrite has a usable native value foundation, but not yet a complete collection
foundation or migrated module layer. No percentage-complete estimate is meaningful.

| Area | Current evidence | Remaining boundary |
| --- | --- | --- |
| Scalars | Generated integer, bool/null and float APIs; checked conversion and operation proofs | Deliberate signed-overflow policy remains; no universal checked arithmetic claim |
| Strings and vectors | Owned values, copy/move, checked reads, explicit mutation, selected casts | Pointer-backed storage costs; no public traversal or general record/nested elements |
| Carriers | Four families, selected payloads, explicit states, checked extraction, error_t | No general record/move-only composition; native exception boundary remains |
| Ownership | Concrete shared/weak/unique, const qualification and inline values | Separate proof publication; inheritance/self-retention and broader composition deferred |
| Hash/mixed/dynamic | Reviewed donors and semantic contracts | No implementation imported into v0.2 |
| Modules | Reuse candidates exist in the legacy tree | No production module migration; tool JSON is not a runtime JSON module |
| API tooling | At assessment: C++ production generator and partial Rust preparation | Prerequisite now completed: sole Rust generation path; C++ generator retired |

Inspection anchors: v0.2 family contracts, runtime/src/, tools/api_generator/
runtime.hpp and emission.cpp, CMakeLists.txt and native consumer tests. Generated
public APIs and private algorithms already have distinct owners. New family work
should preserve that separation and avoid growing a monolithic emitter.

Historical validation counts differ because compiler tests were retired and slices
were added. Use a fresh current-tree run rather than summing historical counts.

Fresh validation for this assessment: `cmake --preset default`,
`cmake --build --preset default` and `ctest --preset default` succeeded under
Clang 18.1.3/libc++/mold. All 31 current CTest checks passed (17.04 seconds test
time), including generated publication, public boundaries and ownership proofs.
Sanitizers, isolated exports and performance suites were not rerun for this
assessment. No runtime implementation was changed.

## Generator prerequisite completed

The subsequent user-requested Rust generator cutover is complete. Full preparation
and emission, production CMake integration, frozen parity fixtures and retirement
of the C++ generator are recorded in the [migration checkpoint](rust_compiler_migration.md).
All hash preparation/emission work below must use the Rust owners. The original
31-test assessment above remains a historical baseline; the cutover has 25 CTests
plus Rust tooling tests after implementation-specific C++ tests were retired.

## Agreed next batch

**Typed-hash foundation, with traversal deferred.**

Decision, 2026-10-29: keep this runtime batch simple. Do not implement mutation-
preserving traversal now. Once the compiler is in place, review cases individually
and determine which guarantees it can establish and which runtime support remains
necessary. This supersedes the initial proposal to combine hashes with traversal.

Deliver generated concrete typed hashes backed by the reviewed custom algorithms,
with explicit lookup/mutation and ordinary value ownership. This establishes a
foundation for typed module data and later packed storage and boxed families.

## Public scope

- Typed hash keys: int64 and string. Values: int64 and string. Prove all four
  combinations through requested publications; not all must be default exports.
  Document concrete names using an unambiguous value/key convention matching
  source hash<V,K>.
- Core operations: size, empty, has, at, find, set, remove and clear.
- at(key) returns a copied value and throws for absence. find(key) returns an
  existing concrete nullable payload: empty for absence, copied value for presence.
  Absence does not throw from find; allocation/copy failures may still throw.
  Both operations are read-only and never insert.
- set inserts or replaces; remove reports whether an entry existed. No public
  reference-returning operator[] or implicit insertion through reads.
- Integer-key append follows the existing next-key and overflow contract;
  string-key append is absent from publication.
- Copies own independent values. Moves leave reusable empty sources. Keep lifecycle
  mechanics simple and prove their actual exception guarantees.
- No public traversal API in this batch. Private traversal may support algorithms
  and tests without promising a source-language iteration protocol.

Use the existing nullable mechanism for lookup rather than inventing another
extraction protocol. Source-visible key, missing-value and copy semantics remain
owned by specs/array_semantics.md.

## Explicit debt: RT-DEBT-01 — traversal and mutation preservation

Status: deferred by user decision, 2026-10-29.
Revisit trigger: compiler integration of collection traversal.

The existing language contract requires traversal to start live, then preserve
remaining shallow entries before structural mutation or binding replacement.
It also requires independent nested positions, preservation-failure rollback and
safe source lifetime. That contract remains recorded; this batch does not implement
it or claim complete collection-contract support.

Do not add traversal subscriptions, preservation snapshots or cursor-driven
lifecycle changes now. Do not substitute iterator invalidation or eager snapshots
as a new source contract. Existing vector nonthrowing moves/clear and the shared
lifecycle emitter need no traversal-driven refactor in this batch.

When the compiler is available, assess each case: what it can prove or lower safely,
what remains data-dependent, and what minimal runtime support is necessary.
Compiler elimination of some machinery is a possibility, not an established proof
for all cases. Any remaining unsupported cases must remain explicit rather than
silently receiving different behavior. Public traversal design, mutation semantics
integration and associated lifecycle/failure proofs are deferred together.

## Approved details and progress

User approved the scoped defaults and implementation goal. Concrete contract:
[typed hashes](../hash_slice.md). H0 complete: baseline 0c230e0bc, clean tree and
previous cutover validation. H1 complete: explicit runtime arguments, void set,
bool_t remove, nullable find, copied at and integer append. Append uses max live
nonnegative key plus one (or zero); deletion of the maximum can permit reuse and
INT64_MAX rejects before mutation. Donor and contracts agree. Traversal remains debt.
H0–H6 are complete. See [validation evidence](../hash_slice_validation.md). No commit/push is part of this goal.

## Action backlog

| ID | Action | Acceptance evidence |
| --- | --- | --- |
| H0 | Preserve baseline and record exact donor revision | Current 31-test baseline, donor provenance and explicit scope |
| H1 | Specify concrete hash publication, key adapters and operation contracts | Signatures, nullable lookup dependencies, absence and failure behavior |
| H2 | Adapt private typed hash/index algorithms to v0.2 values | Collision, replacement, deletion, churn, order, growth and physical index-width proofs |
| H3 | Prepare and emit concrete hashes through the sole Rust generator | All four combinations; dependency/rejection checks; deterministic generation; template-free public boundaries |
| H4 | Prove value lifecycle and lookup/mutation failures | Independent copies, reusable moved-from values, self-operations, nullable absence/presence and documented failure guarantees |
| H5 | Add a standalone consumer and focused measurements | Required/optional lookup, mutation and integer append; allocation/copy and lookup/churn measurements |
| H6 | Validate and consolidate | Release, UBSan, supported focused lifetime sanitizer checks, standalone export, freshness, docs and provenance |

H2 follows the H1 ownership boundary. Preparation owns concrete facts; emission
consumes them. Each step should leave a reviewable proof. The implementation goal is complete.

## Reuse and verification requirements

Preserve the custom Swiss-style typed-hash core: control bytes, probing,
fingerprints, tombstones, growth and insertion order. Bucket index width follows
physical entry positions, not merely the live count. Reuse the meaningful churn
proof crossing 256 and 65,536 physical entries while membership stays small.
Do not replace the core with std::unordered_map or merge typed and packed algorithms.

Legacy hash_t.hpp is coupled to mixed wrappers, key pooling and iteration. Mine the
typed portion and relevant private mechanics; avoid importing that entire dependency
graph. Legacy iteration helpers remain references for deferred RT-DEBT-01; do not
import them as prerequisites for typed storage.

Use contract-based expected traces and reviewed donor equivalence where feasible;
legacy behavior conflicting with current semantics is not an oracle. Test allocation
and copy failure against each operation's documented guarantee. Keep behavioral
assertions active in Release tests. Measure relevant allocation and lookup/churn
costs; record regressions and limits, not speculative
performance claims. Sanitizer/tool limitations must be recorded explicitly.

## Deferred work and module trajectory

Packed heterogeneous storage, mixed/dynamic values/operators, object keys, generic
record/handle collections, callbacks, async, a global failure ABI redesign and Rust
compiler migration are not implementation deliverables of this batch. The Rust API
generator cutover is now an explicit prerequisite, per the subsequent user decision.

Review module needs as design input. Datetime's current header primarily needs
integers, strings and results, making it a plausible independent first module port.
Filesystem includes a broader hash/mixed/vector dependency surface, and JSON needs
family-specific boxed storage. These are header-level dependency observations, not
completed module-port feasibility audits. A module-first datetime batch is a valid
alternative if proving module reuse is the immediate priority.

After typed collections, review packed storage and separated mixed/dynamic as their
own bounded batch; use a selected module consumer to drive that work where possible.

## Goal creation

When implementation is requested, create a goal against this simplified backlog.
Suggested objective:

> Deliver concrete typed hashes in v0.2 using the reviewed custom algorithms,
> with required and nullable optional lookup, explicit mutation, value ownership,
> generated public APIs and behavioral/boundary/performance proofs. Keep traversal
> and mutation preservation explicitly deferred under RT-DEBT-01.

The implementation goal was started after user approval. Completion evidence is recorded in `../hash_slice_validation.md`.
