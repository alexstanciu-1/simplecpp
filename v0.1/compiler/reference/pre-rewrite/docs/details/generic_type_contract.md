# Default generic type parameter contract
Doc Status: supporting

## Decision and implementation status

Agreed and revised on 2026-09-20. This is a prototype language extension, not a
claim about existing Simple C++ contracts. The approved bounded implementation
enforces the **default generic contract** for bare `<T>` through definition
permission checks and concrete argument eligibility. Additional explicit capability
contracts remain documentation-only. See the [implementation](generic_contract_implementation_plan.md).

**Generic means generally usable under a defined baseline, not compatible with
every possible type.** Bare `<T>` implicitly requires copy construction, copy
assignment and a valid compiler-managed cleanup contract, together with concrete
type/layout information. A no-op cleanup contract is valid. There is no implicit
requirement for default construction, zero initialization or move operations.

This supersedes the earlier borrowing-only proposal in which bare `T` accepted
any type and copying/owned returns were excluded. Do not describe the revised
parameter as unconstrained: its baseline is implicit, but mandatory. This is our
own contract, not an alias for a C++ standard-library concept.

The compiler's knowledge of a concrete type does not grant a template body
permission to use that type's members or operations. A template definition must
describe what its body may do with each parameter before concrete arguments are
supplied. Concrete substitution must not silently widen those permissions.

## Baseline permissions and requirements

Accept a type argument only when it satisfies the baseline. Check positive
capabilities and available implementations, not a blacklist of exceptional type
names. Multiple parameters, such as `T1` and `T2` in `hash<T1, T2>`, have independent
contracts. Additional key operations such as hashing/equality are not supplied by
this baseline. Concrete specialization does not authorize arbitrary operations.

| Use of `T` | Agreed direction |
|---|---|
| Type identity and representation | The compiler retains the actual type and can obtain its size and representation from the type model. This does not expose its fields to the template body. |
| `sizeof($value)` | Permitted in principle. Intrinsic syntax and implementation support still need to be settled. |
| `typeof($value)` | Permitted in principle as type information. Its result representation and printing behavior are not settled; it must not unlock additional operations. |
| Read-only borrowing | Call-scoped borrowing is allowed. The caller retains ownership; the borrow cannot escape or authorize mutation/destruction of the borrowed value. |
| Forwarding | The receiving parameter must accept a compatible generic contract and passing mode. Borrow forwarding does not copy; by-value passing requires a real copy. Substitution cannot enable an otherwise unauthorized concrete overload. |
| Copy construction | Guaranteed by the baseline; initialize a new owned value using the type's copy operation. |
| Copy assignment | Guaranteed by the baseline; replace an existing mutable value using its assignment operation. A read-only borrow still cannot be assigned through. |
| Return an owned copy of `T` | Permitted and implemented for the bounded [owned-result contracts](owned_source_results_plan.md). Returning a borrow constructs an owned copy; it does not implicitly transfer ownership. |
| Automatic cleanup | Perform required cleanup for owned values using the type's lifecycle contract. Borrowed values remain the owner's responsibility. |
| `$value.length` or other members/methods | Rejected for bare `T`, even if a supplied concrete type has that member. |
| Arithmetic, ordering, equality or hashing on `T` | Not granted by the baseline; require an additional explicit contract. |
| `new T` | Deferred and forbidden for bare `T` in the initial contract. |
| Zero initialization | Not guaranteed. Knowing size or supporting copying does not make zero bytes a valid value. |
| Borrowed returns or ownership transfer | Their general contracts remain deferred for this slice; copy permission does not implicitly grant either. |

Copy permission does not mean raw byte copying, deep copying, allocation-free
execution or failure-free execution. Follow each type's semantics: copying a
shared handle shares its pointee; copying an owning value may create independent
resources. Preserve the agreed fatal runtime-bridge failure boundary; recoverable
exceptions and guaranteed failure cleanup remain deferred.

## Ownership categories and excluded types

Value/shared/weak/unique describe how an element is held. Borrowed/copied/transferred
describe how a function receives or returns it. Keep these dimensions separate
from the operations authorized by a template parameter contract.

| Category or example | Default generic compatibility |
|---|---|
| Ordinary copyable/assignable values | Eligible with valid cleanup and supported representation. |
| `shared_p<U>` | Handle copy/assignment/cleanup fits the baseline; copying the handle does not require copying `U`. Compiler integration remains pending. |
| `weak_p<U>` | Weak-handle copy/assignment/cleanup fits the baseline. Copies do not keep `U` alive; weak handles still require cleanup. Compiler integration remains pending. |
| `value_p<U>` or an inline source structure | Depends on its actual copy/assignment/cleanup capabilities. Value storage alone guarantees none of these. |
| `unique_p<U>` | Deliberately excluded: transfer does not satisfy copying. A future explicit alternative contract may admit it. |
| Directly stored mutex or another noncopyable/nonmovable value | Excluded when baseline operations are unavailable. Construction and destruction alone do not satisfy the contract. |
| Type with copy construction but no copy assignment | Excluded: both are required, even if a particular body only copies. |

Compose source capabilities from fields and custom lifecycle contracts; import
runtime capabilities through preparation metadata. A structure containing a unique
owner normally fails the copy requirement; a valid explicit custom copy contract
must be assessed on its own semantics. Never select eligibility by these category
names or fixture identities. Distinguish unsupported compiler integration from
semantically forbidden operations in diagnostics.

Non-generic-compatible types remain legitimate language/runtime types. This
decision excludes them from the **default template contract**, not ordinary code
or all future templates. Future weaker or transfer-oriented parameter contracts
have no agreed syntax yet.

## Fit with the source lists

The [growing list](source_list_plan.md) uses copies for insertion and replacement
growth, assignment for updates and an owned result for `get()`. These operations
fit the revised baseline without exposing `T`'s members. The list may access its
own declared fields and perform arithmetic on integer counters; neither grants
arithmetic or member access on its element type.

Current list execution proofs cover concrete scalar and managed runtime/source-record
elements, including growth, copy/assignment and cleanup. [Owned results](owned_source_results_plan.md)
and the [managed growing-list proof](source_list_plan.md#managed-growing-list-proof)
are implemented. Generic list migration remains blocked by dynamically indexed
compiler-tracked allocation ownership; smart-pointer integration remains deferred. The
[fixed-array list](fixed_array_list_plan.md) additionally relies on zero-initializable
elements; that is a separate requirement, not part of bare `<T>`. The user parked
this generic fixed-array case as review debt; see below. It does not block designing
the default contract or the growing list. Do not silently widen the baseline or
exempt the fixture when enforcement is implemented.

Growing storage invalidates borrows into old slots even when a slot holds a shared
handle. A separately copied handle has its own lifetime. Existing ownership and
borrow analysis must preserve that distinction.

## Deferred review debt

The following cases are parked outside the initial default-generic slice:

- **Non-baseline element types**, such as directly stored mutexes and unique
  owners: review future explicit alternative contracts. Their exclusion from bare
  `<T>` remains intentional; do not weaken the copyable baseline to admit them.
- **Generic fixed-array initialization**, such as
  `template<T> struct list_16 { T data[16]; int size = 0; }`: copyability does not
  establish how the sixteen elements become initialized. Review an explicit
  initialization capability or storage that constructs only live elements later.
  Neither solution is selected or required now. This is a limitation of the
  template's operation, not evidence that an otherwise copyable `T` is ineligible
  for all default-generic uses.

Bounded array proofs now use concrete element types and templated capacities.
The unsupported dependent-array definition is rejected without instantiation.
The growing list’s generic migration is also deferred.
[Managed slot lifecycle](managed_element_storage_plan.md) now works for runtime values
and source records, but dynamically indexed compiler-tracked allocation ownership
remains unsupported. The default baseline does not exclude those owners, so favorable
concrete arguments cannot authorize dependent storage use. Storage, ownership and
lifecycle proofs remain covered with concrete elements.

## Future capability contracts

Use **capability contract** as the working term for an explicit constraint beyond
the baseline or a future alternative to it.
Its spelling and full design remain open. Such a contract would authorize only
the named operations and, where needed, specified layout information. For example,
a counter contract could authorize `reset` and `next` without exposing other
members of its concrete type. Do not implement this constrained form in the first
slice.

Default construction will require an explicit predefined capability, with
**default-constructible** as a working name. The earlier proposal to permit
`new T` on bare `T` and infer a construction requirement from the body was
superseded: do not build that construction-specific checking path now. When
capabilities are implemented, supplied types must be checked against the declared
contract; a flag cannot claim an unavailable implementation.

## Checking and ownership

Definition-level checking should reject uses not authorized by the parameter's
contract before a concrete instance is needed. Name binding, permission to use an
operation, and concrete operation preparation remain distinct responsibilities.
Retain the original AST and the formal parameter's identity so concrete checking
cannot erase the origin of a generic value and bypass the restriction.

At type-argument acceptance, check the full declared baseline even when the body
does not exercise every operation. This is compile-time contract validation, not
a runtime type test or requirements inferred from body use. Diagnose missing
operations at the application with their contract context. Acceptance does not
promise every semantically permitted ABI/passing form is implemented; unsupported
forms must fail explicitly without publication.

Demand-driven concrete instantiation remains necessary for layout, supported
signatures, lifecycle and code generation. The
[specialization decision](metaprogramming_parsing.md#agreed-specialization-and-identity-model)
still applies: separate concrete implementations first, possible optimization-based
sharing later. This design does not replace instantiation with runtime type checks
or eliminate all per-instance validation.

Source templates and provider families should express acceptance through explicit
semantic contracts. Provider preparation continues to own native implementations
and measured ABIs; it must not grant source bodies extra permissions. See the
[family definition/specialization boundary](source_native_contract.md#accepted-family-demand-and-reuse-model).

## Implementation scope and next discussion

The [bounded implementation](generic_contract_implementation_plan.md) enforces the
baseline and definition permissions through fixed workers, private results and
joins. Accepted checks survive a supported ordinary body increment. Concrete
specialization and lifetime analysis retain their existing owners.

Provider-family type and operation requirements now use the accepted
[declared requirement model](source_native_contract.md#accepted-family-requirements-and-operation-signatures).
Next discuss indexed allocation ownership before enabling the generic growing list.
The analysis must accommodate the type’s valid ownership states; it must not invent
a uniform state restriction. See the [agreed semantic direction](lifecycle_contracts.md#indexed-ownership-agreed-semantic-direction).
Favorable concrete arguments cannot bypass definition permissions. Generic fixed-array
initialization, reflection, explicit capability syntax and `new T` remain deferred.
