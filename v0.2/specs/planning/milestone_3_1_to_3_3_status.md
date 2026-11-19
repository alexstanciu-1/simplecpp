# What remains in milestones 3.1–3.3

Doc Status: planning

Audit of committed v0.2 PHP compiler work through `cced34796`, 2026-11-19. The reference points are the [bootstrap milestones](v03_bootstrap_implementation_plan.md#3-object-relationships--a-small-ast-family), the [language contract](../../../specs/inheritance_v0_2.md), compiler admission and committed executable proofs. This was a source and test inventory. The preceding parser-fix run executed the inheritance suite on all three flavors. Other linked suites are evidence from the repository.

## Verdict

| Group | Bootstrap acceptance criterion | Status |
| --- | --- | --- |
| 3.1 | One base, derived storage, inherited access, protected access and construction | Covered for the supported class and struct models. |
| 3.2 | Class presence contracts, shared base views preserving identity and destruction, virtual/abstract methods and overrides | Covered for classes with the agreed relaxed instance-property policy and exact callable signatures. |
| 3.3 | Subtype and interface-capability tests, with an explicit checked narrowing operation | Covered for shared classes and interfaces. Exact concrete-type identity has been inventoried separately. |

The [AST-family proof](../../compiler/tests/ast_parent_lifetime_native.py) combines a base-typed operation, concrete nodes, virtual behavior, capability access and weak parent links. Passing this proof demonstrates the design on a small tree. Translating the production parser and retaining a real edited tree are later bootstrap checks.

## 3.1 — Base storage, construction and inherited access

Implemented: one inline base subobject, inherited field lookup, distinct private/private fields, protected receiver checks, inherited constructor contracts/defaults, explicit and implicit base initialization, access checks and base-before-child initialization. Static data resolves to its declaring owner. Explicit type, lexical self and ancestor access work. Expression-qualified and late-static data support reads, plain writes and constant reads. [Layout and collision checks](../../compiler/types/struct.php) · [Constructor and inheritance suite](../../compiler/tests/extends_native.py) · [Static data tests](../../compiler/tests/protected_static_data_native.py)

| Remaining area | Scope and decision |
| --- | --- |
| Compound and increment/decrement updates with the newer static qualifiers | The preparation path explicitly limits expression-qualified and late-static data to plain writes. Ordinary type-qualified static properties already have compound/update rules. Start with statement-form late-static updates if desired. Expression qualifiers add single evaluation, side effects and lifetime. Value-producing updates and indexed mutation need their own coverage. [Preparation](../../compiler/04_analyze/prepare/prepare.php) · [Property update contract](../../../specs/static_members_v0_2.md#property-updates--2026-10-07) |
| Additional construction surfaces | Derived keyed `data` construction, base-first synthesized inputs and constructor `set:` are broader initialization contracts. The completed proof covers ordinary inline/shared construction and base constructor binding. Reference/variadic and abstract constructors are outside that constructor slice. Coordinate broader initialization work with its owner. [Construction contract](../../../specs/inheritance_v0_2.md#construction-copying-and-cleanup) |
| Authored destructor behavior in a hierarchy | Native subobject cleanup and concrete destruction through shared base views have evidence. Source-authored destructor bodies, visibility and lifecycle rules require the destructor feature and its own proof. The bootstrap audit found no production source-authored destructor requirement. [Inheritance support](../../compiler/docs/inheritance.md) · [Language audit](v03_bootstrap_language_audit.md) |

Keep single record-base inheritance, existing collision diagnostics and current protected receiver rules. Multiple interface capabilities are a separate relationship. Static storage for nullable object handles and lazy type singletons belongs to **5.2**, even when the factory allocating the object uses `new static()`. [Implementation order](v03_bootstrap_implementation_plan.md#3-object-relationships--a-small-ast-family)

## 3.2 — Behavior through base views

Implemented: required/optional class function boundaries, shared derived-to-base class conversions, concrete destruction and allocation identity through adjusted views, ordinary/virtual/abstract/final methods, mandatory override intent and explicit ancestor calls. Static calls distinguish lexical and called types. Self/parent forwarding, explicit-type reset, descendant static redeclaration, late-selected data/methods and shared factories have landed. The earlier plan entry listing late-static lookup and shared factories as future work is historical. [Class contracts](../../compiler/docs/classes.md) · [Shared upcasts](../../compiler/tests/shared_upcasts_native.py) · [Dispatch and factories](../../compiler/tests/called_methods_native.py)

| Remaining area | Scope and implementation consequence |
| --- | --- |
| **Inherited public final method satisfying a new interface** | The clearest outstanding spec/implementation gap. An inherited public compatible method already satisfies a new interface, but preparation explicitly rejects a final implementation on this path. The final-virtual case needs a native interface entry that reaches the existing final implementation. Reuse the signature check and forwarding map; prove the native shape first. [Admission](../../compiler/04_analyze/prepare/methods.php#L83) · [Emitter](../../compiler/05_backend/cpp/emit.php) · [Spec](../../../specs/inheritance_v0_2.md#interface-implementation) |
| Shared-value callable variance | Current override/interface checks require exact types. The spec allows a broader by-value shared parameter and a narrower by-value shared return. This needs directional signature checking and dispatch adaptation. Defaults remain selected from the caller's contract. Static redeclarations and references retain exact contracts. [Signature check](../../compiler/04_analyze/prepare/methods.php#L103) · [Variance contract](../../../specs/inheritance_v0_2.md#shared-value-callable-variance) |
| Struct polymorphism and shared struct views | Explicitly shared structs exist. Shared derived-struct to base-struct conversion, abstract structs and polymorphic struct contracts have broader spec coverage than the active class-oriented implementation. Separate owner-preserving upcasts from abstract-value admission and virtual dispatch. Inline derived-to-base slicing stays excluded. [Shared conversion](../../compiler/types/struct.php#L551) · [Abstract declaration checks](../../compiler/04_analyze/prepare/methods.php) |
| Additional callable shapes | Reference-return virtual/interface calls and broader callable forms such as variadic forwarding require callable support, lifetime/origin checks and separate executable proofs. Exact writable reference parameters already supported by the binder are a different capability. [Virtual method contract in the compiler](../../compiler/docs/virtual_methods.md) |
| Presence and owning the current instance | Keep relaxed instance fields as agreed. Required class locals currently need an initializer. Definite assignment and obtaining an owning class `this` value are separate work; field/method access through `this` already works. [Class boundaries](../../compiler/docs/classes.md) · [Preparation](../../compiler/04_analyze/prepare/prepare.php) |
| Other ownership families | Exact weak observation and locking work for covered owners. Weak derived/base view conversions and unique derived/base transfers have their own contracts. Borrowing, stored references and cross-thread ownership require separate lifetime decisions. None is needed to dispatch through a shared base class. [Ownership contracts](../../../specs/inheritance_v0_2.md#weak-derivedbase-upcasts) |
| Broader called-type propagation | Called-type instances currently belong to static bodies. Carrying a runtime called type through an ordinary instance method, or selecting a static implementation through an interface-typed qualifier, needs a separate design. Shared factories in static bodies are implemented. [Called-type ownership](../../compiler/docs/protected_static_methods.md) |

## 3.3 — Type tests and narrowing

Implemented: same-type/upward tests, checked class downcasts, class/interface capability checks and interface cross-casts where overlap is possible. Empty handles test false; a checked cast on absence or mismatch raises `invalid_object_cast`. Preparation rejects impossible final-class/interface pairs and narrowing from a nonpolymorphic source. The tests exercise required/optional handles, single evaluation, alias identity, adjusted views and incremental ancestry edits. [Admission](../../compiler/04_analyze/prepare/object_access.php) · [Class tests](../../compiler/tests/object_access_native.py) · [Interface tests](../../compiler/tests/interface_access_native.py)

| Remaining area | Scope and decision |
| --- | --- |
| Checked access for explicitly shared structs | The language contract includes shared struct operands and struct targets; current admission explicitly excludes both. First prove same-type/upward views alongside shared struct upcasts. Downcasts require an established polymorphic source. Struct-to-interface cases also depend on admitting struct implementers. |
| Automatic flow narrowing and repeated-check elimination | Today a successful `instanceof` is a boolean fact at execution; source uses `object_cast` to obtain a typed handle. Branch-local smart casts would change source behavior. Eliminating a redundant check while keeping the explicit cast is a separate optimization requiring validity across calls, writes and aliases. Neither is required for the current proof. |
| Exact concrete-type identity | Subtype membership, allocation identity and exact concrete type answer different questions. The inventory found a duplicate-category invariant and compiler-internal type-dispatch uses. A public exact-type operator, reflection API or per-object tag has no agreed bootstrap requirement. Revisit at the real translation use. [Exact-type inventory](class_contract_completion.md#exact-type-inventory) |

Dynamic target names, implicit downcasts, bare-null operands, and checked casts directly on weak/unique owners are outside the bounded object-access contract. Interface inheritance/diamonds and struct interface implementations belong primarily to **3.4**; non-owning generation links to **3.5**; traits and staged attachment to **3.6**. These affect future coverage without reopening the existing class/interface cast proof.

## Recommended next decision

Take inherited-final interface satisfaction first. Use one public final implementation on a base, an interface first introduced on a descendant, and calls through base, descendant and interface views. Include both final nonvirtual and final virtual implementations. The latter is decisive because emitting a same-name native override would violate C++ finality. Preserve the existing source override rejection in a further descendant. Verify edits to finality, signature and the interface edge, deletion, retry and recovery in all three flavors. Keep the original method as the semantic owner and measure whether an extra native entry is needed only for the affected declaration.

If that proof requires a wholesale dispatch ABI change, pause for design review. Late-static statement updates are the smaller independent alternative. Variance, general flow narrowing and required-field policy can wait.
