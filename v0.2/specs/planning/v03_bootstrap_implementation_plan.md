# Proposed implementation order for the v0.3 bootstrap

Doc Status: planning

Date: 2026-11-14. Based on the [language audit](v03_bootstrap_language_audit.md) and the subsequent implementation-order discussion. This is a proposed sequence, not authorization to implement every item or a claim that the features are delivered.

## Approach and scope

The audit is organized by subject. Implementation should follow dependencies and prove small, executable parts of the actual compiler. Preserve behavior, type relationships, ownership, identity and failure recovery. A readable native-oriented translation may use deliberate local adaptations instead of reproducing every PHP mechanism, but that translation policy remains a discussion point.

Use existing metadata, type handlers, argument binding, collection machinery and incremental ownership. Do not implement every PHP construct simply because it occurs in the compiler. Discuss substantial architectural substitutions and wide refactors before proceeding. Coordinate collection changes with the workspace that owns them.

Numbers give the proposed implementation order within each milestone. They are discussion-sized groups, not single batch implementation requests. Reassess the next group after each executable proof. Language readiness and runtime/host readiness are separate tracks. Inventory host dependencies during milestone 1, even though their integration is milestone 7.

## 1. Typed data foundation — tokens and source snapshots

| Order | Group | Initial scope |
| --- | --- | --- |
| 1.1 | Integer representations and literals | Activate the widths actually needed by the compiler through metadata. Add hexadecimal and octal literals with checked bounds. |
| 1.2 | Backed enums | Named cases, integer/string backing, equality, backing-value access and checked construction. Reuse method machinery for enum methods. |
| 1.3 | Application-defined collection elements | Start with vector of token, copying, parameters, returns and field storage. Coordinate with collection work. |
| 1.4 | Collection traversal | Ordered value iteration, then keyed map iteration. Set explicit mutation and element-copy behavior. |
| 1.5 | Construction-time immutability | Readonly fields initialized during construction. Keep delayed AST attachment separate. |

Proof: translate the token representation and source-location lookup. Construct, pass and iterate a token list. Verify spans and line/column results in a native executable.

Do not block this proof on every optional width, populated collection literals or full PHP readonly behavior. Start the host/runtime binding inventory alongside this work.

1.2 status: the [first integer-enum slice](../../compiler/docs/enums.md) supplies
named literal cases, compact/exact storage, same-enum equality, typed calls and
`enum_value`, with incremental recovery. Enum instance fields with explicit case
defaults and function/method/constructor enum parameter defaults are also implemented.
Checked integer reverse construction is also implemented, reusing generated integer conversions.
`enum_name` and byte-exact string backing/helpers are implemented using compact ordinal storage.
Public instance enum methods now reuse the function binder and common method ownership.
The bounded 1.2 scope is implemented; unit enums, uint64 backing, static/private enum
methods, reflection and broader enum integration remain separate follow-ups.

## 2. Readable selection and lookup — a tokenizer path

| Order | Group | Initial scope |
| --- | --- | --- |
| 2.1 | Selection | Match with one subject evaluation, lazy selected arms and a clear result-type rule. Start with enum/scalar subjects, then match on true. |
| 2.2 | Container presence and mutation | Contains/find, removal, missing-key handling and ordered keyed traversal. Distinguish missing from stored null. |
| 2.3 | Lazy fallback | Coalescing, then coalescing assignment through the same presence model. Avoid repeated lookups and eager defaults. |
| 2.4 | Text and table construction | Raw/multiline strings and bounded populated collection forms required by compiler tables. |
| 2.5 | Tokenizer runtime bindings | Byte-scanning or regex facilities needed by the selected translation. These are runtime facilities, not new language constructs. |

Proof: tokenize representative input through a translated path and compare token kinds, positions and diagnostics with PHP.

Keep ternary, switch, assignment expressions and general destructuring outside the critical path unless actual translation demonstrates a need. Isolated uses may have clear alternatives, while extensive enum maps warrant a direct language facility.

## 3. Object relationships — a small AST family

Discuss inheritance, handles and interfaces together before implementing this milestone.

| Order | Group | Initial scope |
| --- | --- | --- |
| 3.1 | Base storage and inheritance | One base, derived fields, inherited access, protected access and construction. No multiple class inheritance. |
| 3.2 | Object contracts, then behavior through base views | First class/shared defaults and explicit function nullability with the temporary relaxed property rule below; then ownership-preserving shared upcasts; then virtual/abstract methods and overrides. Preserve destruction and allocation identity. |
| 3.3 | Type tests and narrowing | Subtype tests, then interface-capability tests. Keep exact concrete type identity distinct. |
| 3.4 | Interfaces | Interface-typed parameters/references and multiple capabilities using the dispatch model. |
| 3.5 | Non-owning AST links | Define validity of parent links and retained-generation references. Reuse weak handles where suitable; discuss borrow/reference machinery where needed. |
| 3.6 | Staged initialization and composition | Write-once attachment, settling ranges and a declaration trait/interface use. Evaluate bounded traits versus a simpler truthful owner. |

Proof: one base node, two concrete expressions, child attachment and a base-typed operation. Check overrides, original-object identity, destruction and invalid access.

Do not translate every PHP object reference into an owning shared handle. Ownership cycles and generation lifetimes must be addressed explicitly.

### Agreed implementation direction — 2026-11-14

Use native C++ inheritance and virtual dispatch for the C++ backend. Do not build a
custom vtable, object-layout engine or general ABI framework for this milestone.
This is a planning decision, not implementation or a new source-language contract.

- **Compiler / static analysis:** resolve bases and interfaces; check inheritance
  cycles, visibility, construction, abstract obligations, overrides and admitted
  handle conversions. Prepare these facts before emission. Emit source-oriented
  diagnostics rather than relying on native compiler errors as semantic validation.
- **C++ backend / native compiler:** lower the accepted hierarchy to native base
  classes, virtual methods and abstract interface classes. Let C++ perform layout,
  dispatch, base/interface pointer adjustment and base/member destruction. Use a
  virtual destructor for polymorphic bases/interfaces; do not add source-level
  explicit delete as part of this work. Preserve source mappings for debugging.
- **Runtime ownership:** retain/release, weak observation, concrete payload
  destruction and allocation release belong to the ownership mechanism. Native
  dispatch does not determine which source links own objects or how long a
  generation must remain alive.

An upcast changes the accessible view, not the owned allocation. Converting a
shared Concrete to a shared Base/interface must preserve one control block,
reference counts, complete-object destruction and allocation identity. Copies
retain that owner; moves transfer it; absent handles remain absent. A base/interface
view can have an adjusted address, so view-address equality alone must not become
allocation identity. Never construct a second independent owner from an adjusted
raw pointer. Weak locking must preserve both the owner and the applicable view.

Current implementation evidence: application-record shared/weak handles are
exact-type generated owners in `compiler/05_backend/cpp/emit.php` (`owner_methods`).
Their allocation embeds the payload and strong/weak counts; final release directly
invokes the statically known payload destructor. There is no generic concrete
payload destruction callback in this path today. This differs from the published
runtime ownership wrappers. Generalizing application ownership requires an explicit
runtime/generator ownership design, not a reinterpret-cast between typed allocation
structures or extra semantic decisions embedded in the C++ emitter. Decide the
control/view representation before implementation; no two-pointer layout or extra
allocation is mandated by this note. Review any wider ownership relocation first.

### First extends slice — base storage, inherited fields and construction

Scope: the bounded base-storage part of 3.1. The first proof does not require
virtual methods, abstract classes, base-typed owning links or shared-handle upcasts.
Those belong to the subsequent polymorphic AST slice, not prerequisites hidden in
this one. Source spelling must follow the active flavor/record contracts; this
planning note does not introduce a new class-versus-struct ownership default.

| Compiler stage | Responsibility in this slice |
| --- | --- |
| Tokenizing | Recognize the flavor's base-declaration syntax (`extends` where applicable), retaining normal keyword/source-position handling. |
| Parsing | Store one optional owned written base-type reference on the record declaration. Preserve its spelling and source location even when resolution fails. Do not infer the base or copy inherited members into syntax. |
| Declaration collection / resolution | Use existing namespace/import and declaration lookup to resolve the base. Store one non-owning resolved-base definition reference, separate from the written syntax. Register dependencies through the established incremental owner. |
| Preparation | Validate an admissible single base and acyclic hierarchy; resolve inherited field lookup and visibility, field-name conflicts, base construction and argument conversions. Diagnose unsupported/invalid forms before emission. Prepare reusable facts rather than repeating semantic work in the emitter. |
| Lowering | Emit native single inheritance, the derived type's own fields, prepared inherited accesses and base-before-derived constructor initialization. Reuse published names and include dependencies. Let C++ implement base layout and destruction; do not introduce custom layout or dispatch. |

Start inherited lookup by checking the current declaration and following its
resolved base through the existing member-resolution owner. Do not add a flattened
inherited-members index unless evidence warrants it and invalidation is defined.
Use existing dependency tracking for base edits rather than an independently owned
reverse index. Written base syntax is owned; resolved bases and lookup results are
non-owning references with explicit publication/invalidation boundaries.

Preparation remains bounded but is not merely index population: STAN must detect
invalid inheritance without requiring C++ emission/compilation. Lowering consumes
the settled relationship and construction plan. The exact field-conflict and base-
constructor source rules must be checked against their owning contracts before
implementation; this note does not silently adopt all PHP or C++ behavior.

Proof: a concrete Node record with a small source-range field, and two concrete
records such as IntegerLiteral and Addition extending it. Give each derived record
its own scalar fields. Construct and use each through its exact concrete type,
read inherited public fields and access protected base fields from derived code.
Exercise supported base-constructor arguments/defaults and prove base-before-
derived initialization. Addition need not own polymorphic children yet; the complete
AST tree and base-typed operation remain the later milestone proof.

Implementation status: the first single-base **struct** slice is implemented.
See [the compiler support boundary](../../compiler/docs/inheritance.md) and
[the repeatable native proof](../../compiler/tests/extends_native.py).
The written base is owned syntax; the resolved base is a non-owning canonical
layout dependency. Inherited fields/constructors retain their declaring owner.
The emitter uses native inheritance and braced base initialization, with existing
exact-type ownership and split-header publication. Production runtime storage is
unchanged. Ordinary inherited-method/static-member lookup and protected ordinary
methods/static members remain follow-up work alongside the polymorphic slice.

Verification checklist:

- [x] Execute the same bounded proof across cpp/php/js on Clang and GCC.
- [x] Verify inherited storage and derived fields are independent and initialized
  correctly; inherited access does not duplicate or copy the base payload.
- [x] Verify base construction, applicable defaults/conversions and failure cleanup
  follow the source contract. A failed base construction must not run the derived
  constructor body or leak already-created resources.
- [x] Reject unknown/invalid bases, cycles, unsupported multiple bases, inaccessible
  fields and invalid constructor arguments before emission (grammar rejects
  multiple bases; preparation checks semantic errors). Apply the owning
  field-conflict rule rather than relying on native C++ rejection.
- [x] Keep virtual overrides, abstract declarations and derived-to-base shared
  conversions outside this slice; do not accidentally admit them through C++.
- [x] Preserve existing exact-type inline/shared/weak behavior, including copies,
  alias mutation, absence, destruction and weak expiry where currently supported.
  Use native destruction instrumentation if source destructors are unavailable.
- [x] Incremental base/member/constructor edits, failed resolution and recovery
  match fresh output; replace stale resolved references and inherited lookup facts.
- [x] Verify declaration ordering, include selection and cross-unit base lookup
  through existing owners, without emitter-specific semantic decisions.

Verified with `XDEBUG_MODE=off python3 -B v0.2/compiler/tests/extends_native.py
--memcheck`: cpp/php/js passed on Clang and GCC. The native tests cover explicit
and implicit base initialization, inherited constructor contracts/defaults,
protected receivers/constructor visibility, private/private field-name reuse, checked
conversion failure before later arguments/body, namespace and split-header
publication with default or disabled application namespaces, inline copies, shared
alias mutation and weak expiry. Qualified adapter calls remain visible in native
base initializers outside constructor-body imports. Generated code
passed ASan/UBSan; separate Valgrind runs reported all heap blocks freed. The
unchanged runtime archives were compiled with the matching native compiler and
standard library, without sanitizer instrumentation.

Retained/fresh tests include base removal/cycles, field conflicts, constructor
signature/default/access edits and error retries/recovery. Equal-size edits
explicitly advance filesystem timestamps under the existing mtime/size policy;
unchanged child sources retain their timestamps. CLI parse/prepare inspection
recognizes the new relationship and base-initialization nodes in all flavors.
Existing constructor, weak-owner and static-property native suites passed all
three flavors; AST ownership/location checks and compiler writing checks passed.
The broader `tests/cli.py` suite has an existing diagnostic-text expectation
failure at line 46, reproduced against an isolated HEAD compiler using the same
unchanged generated bindings. It is not an extends regression and remains outside
this slice. No execution-speed or compilation-speed claim is made.

Review fixes verified before commit:

- `this` now subscribes its prepared body to the declaring record's stable symbol.
  The existing layout chain propagates inherited field type/access changes to
  ordinary methods; no inherited-member index or separate invalidation engine
  was introduced.
- Allocation factories for effective authored constructor contracts are emitted
  once in each payload's source, with complete signature types collected there.
  Locally authored and inherited constructors share that publication path. The
  inline inherited-factory special case was removed; empty shared/weak consumers
  and resource-bearing record headers require only declarations.
- `extends_regressions.py`, invoked by `extends_native.py`, covers a three-level
  hierarchy, field type/access/removal errors, actual base-file deletion, repeated
  errors, recovery, changing the selected base and retiring the old subscription.
  Retained output/diagnostics match fresh compilation. A no-op prepares/emits
  nothing; an unrelated function remains reused and its source is not emitted.
- Constructor publication tests cover empty shared/weak handles, handles stored
  in another record, inherited record/shared-handle parameters, actual calls,
  parameter-type edits and restoration. Each derived factory has one `.cpp`
  definition. Native compilation/execution passes on Clang and GCC in all three
  flavors, including each successful incremental recovery.

Verification after the fixes: the full extends suite with `--memcheck` passed
cpp/php/js on Clang and GCC (including ASan/UBSan and Valgrind cleanup checks).
The expanded factory-signature edit matrix passed separately for all six
flavor/compiler combinations. Existing constructor and weak-owner native suites
passed all three flavors; compiler writing checks and `git diff --check` passed.
The previously recorded baseline CLI diagnostic-text failure remains unrelated.
Virtual overrides and shared-handle upcasts remain deferred.

Deferred: virtual/abstract methods and overrides; base/interface shared-handle
upcasts and cross-type identity operations; interface declarations and multiple
capabilities; base-value slicing conversions; downcasts/type tests; parent method
calls beyond base construction; multiple class inheritance; polymorphic child
collections; non-owning parent/generation lifetimes; write-once attachment; traits;
reflection; explicit deletion; custom dispatch; ownership-layout redesign and
optimization campaigns. Plan those against the agreed native-C++/runtime division
above, but do not implement them as part of this first extends slice.


### Next slice — class defaults and object nullability boundaries

Doc Status: planning. Agreed direction after the first extends slice; not an
implementation claim. Establish these contracts before generalizing shared base
views. Object borrowing is deferred and is not a prerequisite for this slice.

#### Agreed defaults and temporary property rule

- A bare class type `Node` uses shared object storage by default. Copies share
  object identity; they do not copy the payload. A bare struct type uses inline
  value storage and the existing memberwise copy rules. The keywords do not adopt
  C++'s merely visibility-based distinction.
- Keep nullability explicit at function/method parameter and return boundaries:
  `Node` requires an object; `?Node` accepts absence. Known null rejects for a
  required boundary; a potentially empty value requires a presence check before
  the callee body or return boundary completes. Optional boundaries accept null
  without inventing an object. Required-to-optional adaptation preserves identity.
- Temporarily relax **shared-object instance properties**, including properties
  declared inside either classes or structs. `public Node $left;` starts empty,
  accepts `= null`, and accepts later assignments of `null`, despite having no `?`.
  This is not restricted to construction or initialization.
- Do not introduce property initialization/definite-assignment analysis, additional
  presence checks on property reads/copies/writes, or constructor-completion
  validation for those properties. Preserve ordinary member type/access checks
  and the runtime's existing empty-handle dereference guard; this relaxation does
  not authorize undefined native dereferences.
- Passing such a property to a required parameter or returning it as required
  still uses the function-boundary presence check. Its written `Node` annotation
  is not proof that its current value is present. `?Node` remains meaningful for
  explicitly nullable interfaces even though these properties temporarily share
  the same empty-storage permission.

Examples of the agreed property policy (proposed class support, not current
compiler coverage):

```php
class Addition {
    public Node $left;
}

struct Node_holder {
    public Node $left;
}
```

Both `left` properties may remain empty and may later be reset to null. This rule
concerns shared-object storage, not nullable inline struct payloads, scalars or
containers. Static-property policy is not expanded by this instance-property
slice. Do not broaden required local/value contracts by implication.

This decision deliberately supersedes the **initialization-only** restriction in
[type-use modifiers](../../../specs/type_use_modifiers.md#temporary-required-field-storage-exception--2026-10-25)
for the planned instance-property slice. The owning normative contract must be
updated alongside implementation; this planning note is not semantic authority.
Do not silently restore the old later-null-assignment rejection or stronger
property validation while implementing the agreed exception.

#### Bounded implementation scope and owners

The next slice admits class declarations with shared-by-default type uses and
exact-concrete construction, assignment, instance properties and callable
parameters/returns. Reuse existing field/method/constructor machinery and the
current exact-type ownership mechanism. Preserve existing struct behavior and
explicit shared struct handles. Class inheritance/upcasts are not prerequisites
for this proof; no control-block/view redesign or two-pointer representation is
selected here.

| Stage / owner | Work in this slice |
| --- | --- |
| Parsing / collection | Retain class versus struct declaration kind and written nullability in the common model. Reuse nominal publication and member ownership; do not create a separate class-only compiler pipeline. |
| Type/signature preparation | Resolve nominal payload identity separately from default storage and required/optional value contracts. Keep required and optional class uses distinct even if their C++ storage is identical. Reuse existing conversion/binding owners. |
| Property preparation | Record the relaxed property contract locally. Property reads remain potentially empty; do not mark all occurrences of the canonical class type nullable or infer presence from its spelling. No property initialization dataflow analysis is required. |
| Boundary preparation | Decide required versus optional parameter/default/return admission and prepare any necessary presence conversion. A null default requires an optional parameter. A reference to handle storage remains distinct from borrowing the object. |
| Lowering / ownership | Consume prepared storage/conversion facts, reuse exact-type allocation/destruction and native payload layouts, and emit checks only at required function boundaries or existing guarded dereferences. No emitter-owned nullability inference or new shared ownership ABI. |

A plain class parameter receives a shared handle by value; object mutation is
visible through aliases, while rebinding the parameter does not replace the
caller's handle. Existing explicit reference-parameter rules concern caller
storage and must not become implicit borrowed-object parameters. General object
reference-parameter extensions are outside this slice.

#### Incremental contract and verification checklist

Use stable nominal/signature symbols and the existing dependency owner. Consumers
must observe declaration kind/default storage, property contract, and callable
nullability/default changes. A cached body must not retain an old requiredness or
presence-conversion decision after a signature edit. Keep `this` record watches
and original member ownership from the extends review fixes. Semantic
re-preparation, source re-emission and native rebuilding are separate concerns;
unchanged emitted text does not prove cached semantic facts are current.

- [x] Run equivalent proofs in cpp/php/js on Clang and GCC.
- [x] Prove bare class copies/arguments preserve object identity and mutation;
  parameter rebinding leaves the caller unchanged. Struct copies remain values.
- [x] Exercise an uninitialized and explicitly null shared-object property in
  both a class and a struct; allow null reset after a non-null assignment.
  Confirm no extra property presence checks or initialization analysis are emitted.
- [x] Required parameters/returns reject known null; optional parameters/returns
  and optional null defaults accept it. Empty property values fail required
  function boundaries before the callee body executes; populated values pass
  without payload copies or duplicate evaluation.
- [x] Empty property dereference retains the existing runtime failure behavior;
  resetting a property releases its ownership normally. Verify alias lifetime,
  exact-type weak expiry and cleanup without introducing owning parent cycles.
- [x] Edit class/struct declaration kind, callable required/optional annotations,
  defaults and property types with unchanged consumers. Compare diagnostics and
  generated output with fresh compilation through errors, retries and recovery.
- [x] Verify watch replacement after a property target/signature changes. No-op
  compilation prepares/emits nothing; unrelated bodies and sources remain reused.
- [x] Check cross-unit declarations/includes and native rebuilds after storage
  or signature changes; retain one factory definition per concrete payload.
- [x] Update the owning nullability contract and implementation documentation,
  explicitly retaining this temporary relaxed property policy.

#### Implementation and proof

Implemented the common `ast_record_declaration` / `ast_instance_field` model with
one immutable declaration-kind enum. The nominal payload owns the optional and
required class identities; structs reuse their existing shared identity. There
are no new per-expression flags, field initialization indexes or preparation
traversals. Existing member ownership, layout preparation and signature watches
remain authoritative. Failed nullable signature lookups register their watch owner
before resolution so recovery/retirement also works after failure.

`classes_native.py --memcheck` exercises cpp/php/js with Clang and GCC: shared
mutation versus parameter rebinding, struct value copies, constructors/methods,
all four empty property forms, null reset, optional defaults, required argument
and return failures, single evaluation, weak expiry and Valgrind cleanup. It
compares fresh and retained output/diagnostics after kind, property target,
nullability/default and missing-type edits, including retries. Generated checks
are asserted at boundaries and absent from ordinary field assignment/reset bodies.
No-op and unrelated-source reuse are asserted. CTest registers the repeatable proof.

The existing extends, constructor, weak, enum-method and failure-cleanup proofs
also pass. The general retained/fresh suite covers 100 revisions and 24 fresh
checkpoints per flavor. This establishes functional reuse, not a measured compiler
speedup or native parallel preparation proof.

Review follow-up closes the remaining gaps: the repeatable class suite now recovers
by adding/deleting/restoring the missing provider with signatures and callers
unchanged. Successful return-contract edits (`?Class` ↔ `Class`) assert that the
unchanged caller adds/removes its presence conversion, matches fresh output and
runs correctly. Source `weak()`/`lock()` exercises optional typing, live identity,
retention, expiry and a required argument failure before callee execution. The
nullable resolver reuses its resolved element in the common specialization lookup;
it no longer repeats runtime element resolution. Existing carrier type/value
proofs cover that path. No new caches or preparation passes were introduced.

Coverage limits remain explicit: required class locals need an initializer until
definite-assignment support lands; obtaining an owning `$this` value is deferred
(field/method receiver use works). Static-property policy is not broadened by this
instance-property exception. See [implementation details](../../compiler/docs/classes.md).

#### Follow-up boundaries

After this slice, discuss ownership-preserving shared derived/base views, then
native virtual/abstract behavior. Upcasts must preserve allocation identity and
concrete destruction, but their representation is still undecided. The existing
struct extends implementation is not authorization for cross-type shared handles.

Defer `ref Node`, `Node*`, borrowed object parameters, stored/returned borrows,
new unique/value overrides, interfaces, virtual overrides, shared upcasts,
downcasts, parent/generation lifetime rules and cycle management. Ordinary PHP-like
object passing does not require borrows: shared identity already supports object
mutation. Explicit PHP-style reference parameters alias variable/handle storage;
they are not a reason to introduce object borrowing now.

## 4. Failures and cleanup — failed preparation and retry

| Order | Group | Initial scope |
| --- | --- | --- |
| 4.1 | Source-authored exceptions | Throw, typed catch and rethrow, diagnostic payloads and ordinary value cleanup. |
| 4.2 | Guaranteed cleanup | Finally, or an explicitly agreed equally direct construct. Cover return, throw and nested cleanup. |
| 4.3 | Failure expressions | Throwing fallback/default arms through the established expression and exception models. |
| 4.4 | Host failures | Typed bindings for expected misses and operational failures without importing PHP warnings. |

Proof: fail midway through preparation, restore its context and retry successfully. Separately fail output publication and verify staging cleanup.

Basic exception infrastructure may move earlier if required by preceding proofs. Complete recovery behavior before translating substantial analyzer code, to avoid spreading a temporary error protocol throughout it.

## 5. Parser, preparation and emission — one complete translated path

| Order | Group | Initial scope |
| --- | --- | --- |
| 5.1 | AST collections and scopes | Base-typed node collections, declaration indexes and duplicate chains. |
| 5.2 | Canonical type instances | Type-owned static handles and lazy initialization preserving singleton identity. |
| 5.3 | Generated definitions | Existing metadata generator emits target-language definitions and registration for required types. |
| 5.4 | Remaining signatures | Review actual unions/intersections and nonliteral defaults individually. Preserve guarantees without automatically implementing all PHP type syntax. |
| 5.5 | Traversal callbacks | Named callable references first. Add captures where they materially simplify the translation. |

Proof: parse a real expression, collect its declaration, prepare through the common AST, emit C++, compile and execute. Include a diagnostic and successful retry. Review readability and ownership as well as results.

## 6. Incremental identity and retention — multiple generations

| Order | Group | Initial scope |
| --- | --- | --- |
| 6.1 | Identity-keyed storage | Symbol/watcher keys and non-owning references with explicit lifetime boundaries. |
| 6.2 | Collection snapshots | Copy membership while retaining referenced objects. Distinguish snapshots from draining a pending queue. |
| 6.3 | Watch dispatch and invalidation | Reuse interfaces and stable symbol identities. |
| 6.4 | Retirement and recovery | Removed/replaced declarations, failed generations, pending work and re-addition. |

Proof: edit a file, retain an unchanged AST, invalidate appropriate bodies, recover from failure and compare with a fresh compilation. Repeat sessions to reveal retention problems. Translate the existing incremental model instead of redesigning it in this milestone.

## 7. Host integration and bootstrap — a native compiler

| Order | Group | Initial scope |
| --- | --- | --- |
| 7.1 | Host data | Manifest/JSON shapes, validated records and inspection output. |
| 7.2 | Runtime facilities | Files, paths, regex if retained, process invocation and other bindings from the early inventory. |
| 7.3 | CLI compiler | Compile a project through the native compiler and compare artifacts and diagnostics. |
| 7.4 | Resident service | Socket protocol, retained sessions, idle shutdown and repeated requests. |
| 7.5 | Bootstrap verification | Build the native compiler using PHP, then assess compiling its source with the resulting native compiler. |

Host dependencies must be inventoried early, not discovered only at milestone 7. Their implementation can proceed separately from language work.

## Features outside the demonstrated required path

- General generators and yield.
- Arbitrary closure captures and recursive closures.
- General-purpose source templates.
- Full PHP union/intersection syntax.
- General cloning and PHP array copy-on-write.
- Every expression-valued assignment/update form.
- Named arguments everywhere.
- Full dynamic-object semantics.
- Language-level threading and cross-thread ownership.

These are not rejected. Review the actual use before deciding whether a bounded feature or a clearer translation is preferable.

## Next discussion and verification policy

Suggested immediate order is 1.1 then 1.2 in this workspace, coordinating collections separately, followed by the token/source-snapshot proof. After the foundation, prioritize AST relationships over peripheral syntax. The tokenizer work can proceed independently where its prerequisites are available.

Open decision: confirm a readable native-oriented translation with deliberate local adaptations, rather than near-verbatim PHP. This determines which traits, unions, closures and collection behaviors are mandatory. Writing this plan does not settle that question.

For each implemented slice, test the real caller and failure/lifetime behavior, not only syntax acceptance or C++ emission. Apply relevant all-flavor and incremental recovery checks. Native proofs must compile and execute. Report compiler work, qualification, native wall, end-to-end and Clang frontend/backend/link timings when benchmarking, plus change counts. No performance or completion claims follow from this proposed ordering alone.
