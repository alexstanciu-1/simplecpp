# Type lifecycle operations and contracts
Doc Status: supporting

Four type_model files migrate lifecycle roles/composition order, imported and source
operation descriptors, shared lifetime permissions and explicit provider policy codecs.
They describe language semantics; they do not execute constructors/destructors, schedule
cleanup, infer native C++ traits or implement ownership analysis.

Lifecycle_Operation is a validated tagged record: imported operations retain provider,
ID, linkage and calling convention; composed operations retain a canonical type ID,
ordered members, optional repeat count and optional body symbol ID. Named factories
keep these two producer paths explicit. Inactive payload fields must be empty/zero.
Members retain either the exact shared operation or explicit null for primitive value
behavior. Role mismatches reject. Linkage/ID/range validation now happens at construction
rather than relying solely on later producer validation.

The original ordering algorithm is preserved: custom copy construction first defaults
members, custom assignment owns all field updates, destruction runs its body before
members and marks reverse traversal, and move composition permits member copy fallback.
The descriptor preserves these instructions; it does not reorder the supplied member
list itself. Returned order views are independent scalar records. Source member lists
are copied while immutable operation identities remain shared.

Lifetime_Policy is a compact producer input, copied into a private Lifetime_Contract.
Copy, cleanup, construction, assignment and expiring construction remain independent
permissions. A role-keyed map stores only present operations. Required bindings must
exist, forbidden extra bindings reject, duplicate roles reject, and expiring-copy
fallback requires a copy capability. Consumers use has_operation before operation;
missing implementation lookup raises rather than fabricating a primitive operation.
Provider strings are converted explicitly by Lifetime_Policies, without reflection.

154 independent PHP/native outcomes cover role vocabulary, ordering, imported/composed
identity, primitive members, fallback, policy independence, every required/missing/extra
binding, duplicate bindings and policy codecs. Retained prototype role/contract code
runs directly in a host oracle. Four host serialization assertions additionally verify
snapshot independence. Full lifetime analysis, debug exports, ABI lowering and native
execution of described operations remain later migration components.

See [evidence and timings](../planning/compiler_migration/results/type-lifetimes-01/README.md).
No converter/framework/target change was required. Next is scalar named-definition
catalog ingestion and its authoritative entry return binding; these descriptors do not
stand in for a completed catalog or source type analysis.
