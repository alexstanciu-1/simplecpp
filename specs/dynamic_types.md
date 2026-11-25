# Mixed and dynamic families

Doc Status: normative

### v0.2 scalar results and typed destinations — 2026-11-07

For scalar expressions involving `mixed` or `dynamic`, preparation uses the
possible scalar kinds of each operand. An operation is admissible when at least
one permitted kind combination succeeds. At execution, evaluate each required
operand once, inspect its actual kind and apply the corresponding scalar rule.
An unsupported combination fails at runtime. Existing short-circuit evaluation
and operator-specific admission still apply.

A typed scalar destination authorizes the same conversion that it would authorize
for an ordinary scalar of the stored kind. An explicit cast uses the explicit-cast
context instead. The runtime checks the kind and any value-dependent limits before
committing the converted value. This rule covers value conversions at typed
boundaries. Reference binding continues to require compatible addressable storage.

An operator with a fixed scalar result keeps that result type for every admitted
boxed operand form. Preparation can settle its result type before runtime kind
checks. For example, ordering comparisons return plain bool whether one or both
operands are boxed. An unsupported payload pair still fails its runtime guard.

For arithmetic whose result type depends on promotion, such as addition, a
`mixed` operand gives the expression type `mixed` in either operand order. Scalar promotion determines the payload kind. The static result
stays mixed even when a float peer guarantees a floating result on every
successful path. An explicit scalar operand cast participates in type resolution
before arithmetic. A typed destination converts the completed expression.
The same rule preserves the dynamic family for dynamic/scalar, scalar/dynamic
and dynamic/dynamic addition. Implicit mixed/dynamic crossing remains unavailable.

| PHP-flavor example with boxed input of unknown kind | Required behavior |
| --- | --- |
| `$a int = $x;` with float payload 8.75 | Apply the float-to-int typed-destination conversion and store 8. |
| `$a int = $x + 2;` with float payload -1.75 | Add as float to obtain 0.25, then convert to int and store 0. Converting the input first would incorrectly yield 1. |
| `$y = $x + 2;` | Infer mixed storage and preserve the selected integer or floating payload. |
| `$y = $x + 2.0;` | Infer mixed storage with a float64 payload on success. |
| `$y = 2.0 + (float)$x;` | Check the explicit cast, then add two float64 operands. Infer float64 storage. |
| `$ok = 2 < $x;` | Ordering declares bool. Infer bool storage. |
| `$ok = $x > $y;` with two mixed operands | Check both payload kinds. An admitted comparison returns plain bool. |

The result examples use a source operand declared mixed. Both boxed families
use runtime scalar-kind dispatch and context-aware typed scalar extraction. Same-family whole-box
copies preserve the box. Explicit family crossing, boxed null equality, container
identity, indexed reads and compound write-back retain their separate contracts.

### v0.2 separated mixed/dynamic families — 2026-10-05

This amendment records the approved replacement contract. It overrides the older
object-only dynamic and cross-family boxing rules below **for v0.2**, not for v0.1.
The core runtime now uses separated tagged dynamic and mixed families. Compiler
integration remains in progress; runtime proofs alone do not establish source-level
compliance. See the implementation handoff.

- Both families represent null, bool, int, float, string and their own containers.
  `dynamic` is no longer an object-only handle. A scalar dynamic is still statically
  dynamic, not a mixed value or a one-element object.
- Mixed containers contain mixed values; dynamic containers contain dynamic values.
  Scalar expressions are boxed into the destination family. Neither family may
  contain a container from the other, including through native adapters or aliases.
- Mixed containers retain value/COW behavior. Dynamic container copies share identity;
  mutation is visible through aliases. Scalar copies have value semantics. Replacing
  a dynamic variable or by-value parameter replaces only that binding's value,
  whether the old or new value is scalar or container.
- Default dynamic is null. There is one null state, distinct from a constructed
  empty container. No old absent-handle versus boxed-null distinction survives.
- `clean($value)` resets mixed or dynamic storage to its own null state, including
  through a native alias or reference parameter. It does not clear a shared dynamic
  container in place; other copied bindings preserve their object. Whole-variable
  `unset` is not the spelling for either boxed family. See the
  [reset/alias contract](native_reference_safety.md).
- Cross-family scalar/null conversion is explicit and preserves kind and value.
  Cross-family container conversion rejects, including explicit casts; recursive
  conversion is deferred. An explicit target annotation does not authorize a crossing.
  Reject statically known invalid crossings during preparation. Where the source
  kind is unknown, check at runtime before replacing the receiving value.
- Shared boxed operator rules apply to both families, not separate dynamic coercion
  rules. The November scalar-results amendment governs numeric expression results. Scalar peers follow the
  established numeric admission rules. Numeric compound updates preserve the stored
  numeric kind and check before replacement. Ordinary assignment and text compound
  assignment may replace the kind. Comparisons return bool, and concatenation returns
  string. Operator normalization must not silently cross mixed/dynamic families.
  Container identity/value behavior remains family-specific as described above.
- Indexed reads and iteration values retain the receiver's family. A missing entry
  yields null in that family. Key admission remains a separate int/string contract;
  a private mixed scalar key carrier is not permission for mixed-valued dynamic entries.
  This amendment does not add dynamic-typed keys to the supported source key domain.
- Null mixed may bootstrap a table on write. Null dynamic requires explicit container
  construction before mutation. Null or other non-container iteration throws.
  Existing ordering, lazy probe/removal and write-failure guarantees remain in force.
- Container shape remains derived from the internal hash's packed-array query.
  Persistent `[]` versus `{}` identity is deferred, as are callable-valued entries,
  dynamic `->`, recursive family conversion and reference foreach.

JSON uses family-specific decoding: `json_decode` returns `result<dynamic>`, including
scalar roots; `json_decode_mixed` returns `result<mixed>` with value/COW trees.
`json_encode` accepts either family without cross-family conversion and returns
`result<string>`. Public tagged runtime support precedes compiler registration and
source integration; see the
[JSON migration status](json_module.md).

Implementation constraints: independent public types, reusable private mechanics,
inline numeric/bool payloads rather than per-scalar shared allocations, and indirect
string/shared-container storage for dynamic. The agreed 24-byte target is an ABI
measurement goal, not a portable language size guarantee. Shared machinery must not
provide an admission bypass or duplicate scalar operator semantics.

### v0.2 boxed integer operators — 2026-10-20

Homogeneous `mixed` operands and homogeneous `dynamic` operands admit `&`, `|`,
`^`, `<<`, `>>` and unary `~`. Each successful expression produces an integer under the November scalar-results amendment. Both binary payloads (or the unary payload) must have integer
kind; bool, float, string, null and container kinds throw instead of coercing.
Dispatch inspects the kinds of the original operands directly.

Binary expressions evaluate left then right exactly once, capturing the left
value before later source mutation. Runtime kind validation occurs after both
operand expressions have evaluated. Primitive integer bitwise/shift behavior is
reused; this slice does not introduce a new invalid-shift-count policy.
The subsequent [checked-count contract](numeric_module.md#checked-shift-counts)
requires `ValueError` for negative counts or counts of 64 or greater on these
canonical integer payloads, after integer-kind validation.

The v0.2 admission slice is homogeneous boxed expressions only. Cross-family and
boxed/scalar pairs, compounds and indexed updates remain separate. Existing
numeric arithmetic/comparison/concatenation contracts are unchanged.

### v0.2 checked boxed scalar equality — 2026-10-27

For same-family mixed/mixed and dynamic/dynamic `==` and `!=`, scalar kinds
follow the same checked rule. Numeric int/float pairs compare numerically, with
int-to-float conversion and its documented precision loss. Bool compares only
with bool; string compares only with string by complete bytes, without parsing.
Other non-null scalar kind pairs throw for both operators. A boxed null compares
equal only to null; null versus a present scalar is false, without truth conversion.
For admitted pairs `!=` negates `==`; it does not turn an invalid comparison into true.
Statically known unsupported plain operand types remain preparation errors.

This supersedes coercive boxed bool/numeric, numeric/string and null/empty-string
runtime behavior. It reconciles the bool/int failure expectation tracked as
REL-DEBT-04. It does not change container identity comparisons, cross-family
admission, wrapper error/sentinel comparisons, strict identity, truthiness, explicit
casts or concatenation. Evaluation follows the [expression contract](expression_evaluation.md).
