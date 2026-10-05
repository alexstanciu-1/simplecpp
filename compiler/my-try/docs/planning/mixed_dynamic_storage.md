# Mixed and dynamic storage: layout and shared implementation
Doc Status: planning

Recorded 2026-10-05. This is an active design discussion, not an amendment to the
normative dynamic/JSON contracts or authorization for a runtime rewrite. It supports
[CH06-MIX-DYN-01](../catalog/06_containers.md#ch06-mix-dyn-01--mixeddynamic-containment-contract).

## Recorded improvement: MIX-LAYOUT-01

Keep the existing mixed borrow counter, but move it beside the kind tag, before
the union. Current x86-64 layout is 1-byte tag, 7 bytes padding, 16-byte union,
2-byte counter and 6 bytes trailing padding: 32 bytes. Proposed ordering is
1-byte tag, 1 byte padding, 2-byte counter, 4 bytes padding and 16-byte union:
24 bytes, with the same fields and intended semantics. The counter currently exists
in both debug and release builds, although its borrow checks are debug-only.

Status: recorded improvement, not implemented; non-blocking layout work. The current
32-byte mixed size and 16-byte shared handle were measured with Clang 18 on x86-64
Linux. Reordered production mixed has not yet been compiled/measured. Confirm size,
alignment and copy/move/lifetime behavior in both debug and release when implementing.
This is an ABI change: rebuild runtime libraries and generated consumers consistently,
including stale PCH/build artifacts. Do not remove borrow safety to save space.

## Dynamic direction from discussion

The user accepts a 24-byte target for a tagged dynamic value: inline null/bool/int/
float, indirect string storage and shared container storage. No shared allocation
per numeric/boolean scalar. The current dynamic is still a shared hash handle alias;
the proposed tagged type does not exist yet.

A layout-only sketch using current wrappers measured 24 bytes at both -O0 and -O2
on the above target. Inline `string_t` increased the sketch to 40 bytes. These are
object sizes, excluding string/container allocations and ownership control blocks;
they are not portable size guarantees or functional implementation proofs.

The intended semantic direction separates mixed-family value/COW containers from
dynamic-family shared containers while allowing scalar/null leaves in each. JSON
should offer family-specific decoding, defaulting to dynamic including scalar roots.
Existing normative permissions, casts and JSON APIs still need explicit reconciliation.

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

## Shared implementation assessment (proposal, not selected architecture)

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

Next design review should choose the private storage boundary and recursive container
types before any cross-owner changes. Runtime layout/lifetime, family rejection,
copy/alias semantics, JSON modes and compiler facts require separate focused proofs.

Future callable-valued entries are recorded as
[CH15-CALLABLE-01](../catalog/15_blocking_debt.md#ch15-callable-01--callable-values-in-mixeddynamic-entries).
Their representation is a design consideration, not part of this refactor's
implementation scope or permission to introduce callable execution early.
