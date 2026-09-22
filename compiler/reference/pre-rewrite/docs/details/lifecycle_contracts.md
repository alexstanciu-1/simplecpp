# Lifecycle events and field composition
Doc Status: supporting

Status: contract investigation and implemented bounded composition, 2026-09-19.
The compiler now composes default construction, copy construction and destruction
for eligible source structures, including bounded custom constructor/copy-constructor/destructor bodies
as a documented prototype extension. Broader
events below remain an inventory, not a claim of implementation. This follows the
[Clang/compiler division of work](clang_lifecycle_composition.md).

## Authority and evidence

Follow explicit Simple C++ contracts. Where they leave behavior unclear, inspect
the generated S2S C++ and the actual runtime operations. If they disagree, record
the conflict for discussion rather than adopting the implementation as a new rule.
No upstream files were changed or messages sent to upstream maintainers.

Legacy Simple C++ STAN is not semantic authority. Use authoritative contracts or,
where unclear, executable behavior plus discussion. Its diagnostics below are
observations, not prerequisites for our lifecycle work.

This investigation used the configured [toolchain](../../tools/toolchain.json),
branch `codex/compiler-vector-runtime`, commit
`fc20d73d040c4e69758bcec0b1caf40c26755f72`, and Clang 18. Evidence is version-specific.
The [reproducible probes](lifecycle_probes/README.md) distinguish successful
source compilation, native witnesses, and transpilation-only observations.

Authorities:

- [Compact layout types](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/compact_layout_types.md),
  sections 2.1–2.4: inline structs, eligible fields and generated defaults.
- [Runtime specification](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/specs/spec.md),
  sections 6.6–6.11: ownership wrappers, references, reset and cleanup.
- [Native reference safety](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/native_reference_safety.md):
  stable objects may be borrowed; heap interior references are forbidden.

## Keep three concepts separate

1. **Object lifetime:** storage does not yet contain a constructed object, contains
   a live object, or no longer contains one. Construction can be in progress.
2. **Resource ownership:** a live object can own resources, share ownership,
   observe ownership, or own nothing. An empty or moved-from handle is still live.
3. **Borrow permission:** a temporary permission to access an existing live object,
   with mutation and escape restrictions. Ending a borrow does not destroy it.

Reference counts belong to runtime shared ownership. They cannot represent local
initialization, exclusive ownership, or aggregate field construction progress.
Static analysis records object state and permissions; it does not need to know
the numerical strong-owner count to schedule destruction of a shared handle.

### Indexed ownership: agreed semantic direction

The language intent is natural, predictable behavior from local type and operation
contracts. Legacy S2S restrictions caused by missing resolved information do not
establish permanent language rules. Generated C++ and runtime execution remain
evidence where authoritative contracts are unclear, not a ceiling on what shared
analysis can safely support in future LLVM and S2S backends.

A live container element need not own an allocation. Its type may permit several
valid ownership states, and different elements may occupy different states at the
same time. A method need not restore its previous ownership state; it must satisfy
its declared operation contract. Static summaries must represent those semantics,
not impose a uniform allocation state merely to simplify analysis.

The representation and proof for dynamically indexed compiler-tracked owners remain
under discussion. If current analysis cannot establish safety, report unsupported
coverage rather than redefine valid language behavior. This clarification does not
change explicit generic permissions or authorize indexed-owner implementation yet.

## Event contract inventory

These are semantic roles, not a proposal for one opcode or stored record per row.
An existing checked operation can already supply an event; avoid duplicating it.

| Event | Preconditions | Successful result and cleanup responsibility |
|---|---|---|
| Default/direct construction | Suitable aligned storage; no live destination object; required operation available | Destination becomes live after successful initialization. Register its eventual cleanup if needed. Default behavior comes from its type/initializer contract, not blanket byte zeroing. |
| Copy construction | Live readable source; uninitialized destination; available copy operation | Two live objects. Their resource relationship is defined by the operation: independent string/container storage, shared pointee ownership, or another declared contract. |
| Move construction | Live source with transfer permission; uninitialized destination; available selected operation | Destination becomes live. Source poststate comes from the operation. Native moved-from objects generally still require destruction; do not infer that every source becomes empty. |
| Copy/move assignment | Live destination and valid source; available assignment operation | Destination stays live. Operation handles previous resources and defines alias/self-assignment and failure behavior. Assignment is not automatically destruction followed by construction. |
| Borrow begin/end | Live, suitably stable object; permission and duration accepted | Access is available for the specified interval. No ownership copy or destruction merely because borrowing begins/ends. |
| Destruction | Live owned object whose cleanup is due | Invoke required cleanup exactly once and end its lifetime. Destroying a shared handle can destroy its pointee; destroying a weak handle does not keep that pointee alive. |
| Reset/clear | Live object; supported reset operation | Object stays live in the operation's resulting state. `unset`/`clean` are not generic lifetime-end instructions. |
| Weak lock | Live weak handle | Produce a live shared handle, possibly empty. Retain the successful handle while using the pointee. |
| Ownership release | Live owner; explicit release contract and recipient responsibility | Transfer the resource obligation according to the operation; the old handle can remain live and empty. A raw pointer alone supplies no cleanup policy. |

Arguments, returns, initializers and assignments select among these operations;
they are not independent ownership systems. Result construction/transfer must be
settled before destroying source locals. Temporaries and early exits retain the
already documented [full-expression and scope cleanup rules](runtime_cleanup.md).
Copy elision can avoid an intermediate object: do not create then destroy a
fictional object just because the source contains a return or initializer.

Failure is an operation effect too. A failed constructor does not create a fully
live destination. A failed assignment can leave a live, modified destination;
do not promise rollback without the operation's guarantee. Recoverable unwinding
remains outside the current compiler implementation.

## Automatic structure composition

For the eligible generated C++ structs, the generator leaves special members to
C++. **Composition rule:** preserve that memberwise behavior,
not a custom aggregate policy invented by the LLVM path.

- Initialize fields in declaration order, recursively. Field defaults apply to
  default initialization, not in place of copying a source field. Destroy fields
  in reverse construction order. These are the native
  [initialization/order rules](https://eel.is/c++draft/class.base.init).
- Copy/move construction and assignment are separate capabilities. Native implicit
  assignment processes fields in declaration order using their assignment
  operations; it does not replace the entire aggregate with a temporary. See
  [implicit assignment](https://eel.is/c++draft/class.copy.assign).
- If construction throws, completed subobjects are destroyed in reverse completion
  order; neither the failed member nor the incomplete containing object receives
  its normal destructor. The native witness checks this
  [partial-construction rule](https://eel.is/c++draft/except.ctor).
- Fixed arrays compose over constructed elements. Dynamic storage additionally
  needs an initialized-element count distinct from allocated capacity. A source
  list's logical size does not determine how many fixed-array elements are live.
- Determine each demanded operation's availability from the selected field
  operations. Do not infer copyability from size/layout or a type-name list.
  `copy: unavailable` in our current model means unsupported by the compiler,
  not necessarily forbidden by the native type.

Use runtime operations for managed provider fields: importing layout does not
authorize copying their bytes or reconstructing private internals. A containing
type can refer to reusable field/type operations; analysis should not expand all
possible execution paths or allocate one record per runtime array element.

### Source eligibility remains explicit

Upstream permits nested first-slice structs and `vector<Struct>`, `hash<Struct>`,
and `fixed_array<Struct, N>` fields. Its allowed list excludes `string` and
explicitly rejects ownership/reference wrappers. The string-field rejection was
confirmed by a focused transpilation probe. Thus the implemented managed-composition proof uses a nested source struct with
provider-authorized container fields. String and
smart-pointer fields require an agreed upstream change or documented extension.
Our prototype's source templates/methods remain explicit extensions already recorded
in the tracker; they do not silently relax field eligibility.

## First compiler implementation: bounded composition

The agreed simplification is **default construction, copy construction and
destruction first**. The six-operation native proof establishes feasibility; it
does not require implementing move or assignment now. The approved coordinated
migration is implemented through the existing owners below. Managed assignment, moves and native source-type export
remain deferred.

### Assumptions removed by the migration

The previous implementation had three connected assumptions:

- [`lifetime_contract`](../../src/04_analyze/type_model/data/definitions.php)
  referred only to `runtime_lifecycle_operation` and had no default-construction
  contract. `struct_field` validation permitted only plain scalar/array values.
- [`Record_Definitions::materialize()`](../../src/04_analyze/resolve_types/record_definitions.php)
  gave every record value-copy/no-cleanup behavior. Body checking treated record
  defaults as zero values and recognized construction into storage only for
  opaque runtime values.
- [`Lifecycle_Join`](../../src/05_generate_code/prepare_backend/lifecycle_join.php)
  accepted runtime-package operations only; module assembly emitted source-function
  bodies without an owner for generated complete source lifecycle definitions.

Allowing managed fields without changing these assumptions would authorize byte
copying or zero initialization where real operations are required. Renaming the
runtime operation record alone would not resolve that mismatch.

### Implemented ownership

| Existing owner | Change and boundary |
|---|---|
| `type_model` | Describe construction, copy and cleanup capabilities independently of representation. Keep imported and compiler-composed implementations explicitly distinct. Field eligibility remains a semantic permission separate from layout and operation availability. |
| Runtime preparation and `load_runtime` | Supply and validate explicit field eligibility and a default-construction binding to an actual prepared operation. Keep existing copy/destruction imports. Do not infer these permissions from a type name or a constructor's signature alone. |
| `resolve_types` | Derive source operation plans from ordered field contracts after dependencies are ready; accept them with the containing type. Reuse nested type plans. Fixed arrays retain an element operation and extent, without expanding one analysis record per element. Reject by-value dependency cycles. |
| `check_bodies` and `analyze_lifetimes` | Select initialization/copy from capabilities, then record when storage becomes live and when owned cleanup is due. Keep borrowing separate. A containing object owns field cleanup; do not also register its fields as independently owned locals. |
| `prepare_backend`, `lower` and `emit_llvm` | Prepare ABI/layout facts through existing workers and joins. Generate reusable complete source operations from accepted plans, using field addresses and existing call emission. Preserve simple value operations where valid. Generated definitions need honest provenance, not fabricated source AST nodes. |
| Coordinator and joins | Select one batch of composition work with fixed inputs, private outputs and explicit acceptance. Reuse unchanged contracts on a body-only edit; use the existing full-rebuild fallback for changed contracts. |

Source default/copy construction proceeds in field order; destruction proceeds
in reverse order. Runtime fields call their imported implementations. The compiler
owns the complete containing operation. A caller marks the containing object live
only after successful construction. The agreed fatal bridge-failure policy remains
in effect; this slice does not promise cleanup during failure.

The [integration proof](../../tests/integration/lifecycle_composition.php)
uses **two observable, over-aligned runtime container adapters around real
`scpp::vector_t` storage of a plain native record**. Its local metadata authorizes
these fixture container types as fields and supplies their real operations. This
exercises mixed field ownership without simultaneously implementing
`vector<MySourceStruct>` requests, project-native modules or source-operation
imports. Several source shapes follow the same path; fixture names never select
compiler behavior.

### Validation and limits

The main risks are accidental byte copying of managed values, cleanup twice or in
the wrong order, stale operation plans, and regressions in existing plain records.
The integration proof covers nested default/copy construction, independent container
copies, reverse field cleanup, temporaries and early returns. Negative cases
reject unavailable operations and managed assignment. Existing plain assignment
stays supported. It also checks fixed inputs, reversed result acceptance,
missing/duplicate batch rejection and unchanged prior snapshots through one full build and one body-only
increment. Generated lifecycle definitions are reused across that increment.
Generated artifacts also match the native trace at O1, full LTO and ThinLTO. These
are artifact compatibility checks, not new compiler optimization-mode switches.

Validation checkpoint: the 85-test compiler run used ten jobs (94.5 seconds).
Its sole failure was the previous exact debug-export expectation; updating it for
the construction fields and rerunning that test passed. The composition proof was
rerun after the final metadata-validation changes. The separate runtime-preparation
suite passed 161 checks. No new performance claim is made by these correctness checks.

The original composition slice excluded custom constructors/destructors; the following
[custom-body slice](#custom-source-lifecycle-bodies) adds them. Managed assignment, moves,
owned source parameters/results, new borrow forms, dynamic source allocation and
native template consumption remain deferred. Their implementation stays in the accepted sequence.
No new general operation registry, scheduler or persistent artifact protocol is
needed. The source/native project-module protocol remains a later integration.

### Layout and generated-definition details

Plain aggregates retain their LLVM layout probe and ordinary value operations.
A storage graph containing opaque fields needs explicit imported alignment: Clang
measures layout-only aligned shells, without constructing objects or composing
lifecycle. A separate primitive probe rejects any C++/LLVM size or alignment
mismatch. Accepted byte size, alignment and field offsets govern allocation and
access; mixed record storage uses a byte extent, never an implied byte-copy right.

`Lifecycle_Composition` selects constituent operations once in type resolution.
Plans refer to nested type operations; fixed arrays retain one element operation
and a count. `Lifecycle_Emission` produces loop-based array operations in private
selected work. Its join accepts the complete definition set. The entry module owns
that generated set exactly once; other modules use ordinary prepared declarations.
Operation symbols use non-recycled canonical type IDs in a reserved compiler
namespace within one linked type lineage. They are not persistent native export keys.

## Runtime-specific ownership facts

The actual wrappers support the simple event model without compiler-managed counts:

| Runtime family | Observed implementation and consequence |
|---|---|
| [string_t](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/include/scpp/string_t.hpp) | Contains `std::string`; use its operations for copying, assignment and destruction. |
| [vector_t](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/include/scpp/vector_t.hpp) | Contains `std::vector<T>`. The source probe verifies independent copied storage in an enclosing struct. Do not infer element-destruction order from struct-field order. |
| [fixed_array_t](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/include/scpp/fixed_array_t.hpp) | Contains an initialized `std::array<T, N>`; physical elements have their own lifetimes. |
| [hash_t](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/include/scpp/support/hash_t.hpp) | Has explicit constrained copy construction and self-assignment handling. Copy assignment constructs a copy then move-assigns it. Its private representation and guarantees must not be replaced with vector assumptions. |
| [shared_p](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/include/scpp/shared_p.hpp) | Contains `std::shared_ptr<T>`; copies share the pointee, reset/destruction release an owner. Copying the handle is not copying the payload. |
| [unique_p](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/include/scpp/unique_p.hpp) | Contains `std::unique_ptr<T>`; copy is deleted, moves transfer ownership. The native probe verifies empty moved-from handles and release of the old assignment destination. |
| [weak_p](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/include/scpp/weak_p.hpp) | Contains `std::weak_ptr<T>`; `lock()` returns a shared handle. The native probe verifies that the locked handle keeps the pointee alive and a later lock fails after its destruction. |

A separate expiration/count check cannot pin a weak pointee across concurrent
owner release. Use the runtime's atomic
[weak-lock operation](https://eel.is/c++draft/util.smartptr.weak.obs), then keep its
result alive. Shared ownership bookkeeping does not make pointee mutation thread-safe.
These are runtime observations, not newly implemented source smart-pointer support.

## Generated source boundary observations

The source probe builds and executes with nested structs and a vector field:

- `read(outer $row)` and `change(outer $row)` become `outer row`: by-value copies.
- `borrow(outer &$row)` becomes `outer& row`: writes reach the caller's object.
- Copy initialization, assignment and self-assignment preserve independent container
  contents; returning an `outer` produces a value usable by the caller.

This is an execution proof, not clean upstream STAN acceptance: the asynchronously
completed report contains an unknown property-write receiver after a typed struct
copy (one error-bucket diagnostic) and ten initialization warnings. Keep these
analysis gaps visible; generated native behavior alone cannot validate STAN.

A separate transpilation-only probe yields `measure(const string_t& text)` for a
read-only string parameter and `edit(string_t text)` when the function modifies
it. Accordingly, source spelling alone is not a complete physical passing contract.
Before broader owned source boundaries, specify passing/effect contracts explicitly;
do not make every consumer inspect callee bodies or assume every parameter copies.
Aliasing/re-entrant edge cases of this S2S optimization were not investigated here.

## Conflicts and decisions to discuss

1. **Interior references:** the normative safety document explicitly forbids
   `vector_t::at()`/`operator[]` returning `T&`, but the selected runtime exposes
   exactly that interface (also hash indexed access). The source probe's indexed
   mutation exercises current implementation behavior; its success does not
   override the safety contract. Preserve our established stable-object borrowing
   rules until the conditional refinement below is designed and proved.
2. **`new` for a struct — semantics clarified:** the intended contract is a value. Transpiling
   `$value row = new row();` currently emits `row value = create<row>();`, while
   [memory.hpp](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/include/scpp/memory.hpp)
   declares `create<T>() -> shared_p<T>`. That is an incompatible generated type
   shape, not a reason to change our struct semantics. The positive probe uses
   a typed default local; the `new` case was inspected, not successfully built.
   **Upstream clarification relayed by the user, 2026-09-19:** Simple C++ confirms
   that `new Struct()` should produce a value under its struct semantics. The
   class-construction rule applies to classes. The compact-layout document still
   has a gap for the exact construction syntax: an implementation must construct
   a value or reject the form as unsupported, never silently introduce shared
   ownership. This confirms our agreed behavior as the intended upstream semantics,
   rather than a separate ownership extension. The clarification is recorded from
   the user's supplied response; no upstream documentation/code update is claimed.
   Upstream section 5 already defines declaration-kind metadata. The generator's
   [collectLocalDeclaredTypeKinds()](../../../../../simple_cpp_compiler/vendor/simple_cpp/generators/php/src/Generator/Generator.php)
   populates class/struct kinds and supplies them to its type mapper, but the
   ordinary `AstKind::NEW` branch emits `create<T>()` without checking that kind.
   Thus this observed mismatch does not establish an inability to distinguish
   structs/classes or a need for richer STAN before fixing that lowering.
   Reported upstream as [Simple C++ issue #229](https://github.com/alexstanciu-1/simplecpp/issues/229),
   with the reproducer, contract clarification and declaration-kind evidence.
3. **Failure boundary — agreed, further work deferred:** a failure crossing a
   runtime bridge is fatal. Recoverable exceptions and guaranteed cleanup during
   failure are deferred for now. Native C++ can unwind partial construction inside
   a bridge, but this does not establish caller-side LLVM unwinding equivalence.
   See the [future debt](runtime_cleanup.md#deferred-debt-failure-cleanup-and-recoverable-exceptions).
4. **Upstream STAN:** the executed source probe still produces the diagnostics
   described above. Its typed struct copies/defaults need investigation in the
   upstream analyzer. No `--no-stan` bypass was used and no STAN-clean claim is made.

The first two are upstream contract/implementation discrepancies. The user has
accepted the conditional direction below for the first; implementation is pending.
The second is settled semantically by the upstream clarification relayed above;
the observed generator lowering and upstream syntax documentation still need correction.
No compiler workaround or upstream change is included here. The third
is an explicitly accepted current boundary, with future debt recorded separately.
None requires changing settled successful-path
field order, handle-copy semantics, or the separation of lifetime and ownership.
The fourth is an observed analysis/compilation discrepancy, not permission to
weaken our own initialization or type checking.

## Agreed direction: shared analysis and conditional interior borrowing

Discussion decision, 2026-09-19: the next-generation S2S generator will be part
of this compiler and consume its resolved types, checked operations and
lifetime/ownership facts. It should generate C++ from the same accepted semantic
decisions as the LLVM path. The old generator's behavior is useful evidence;
an implementation limitation does not override an explicitly agreed source rule,
including by-value `new Struct()`.

Limited AST/STAN information in the older generator is a plausible explanation
for some restrictions and incorrect lowerings, as suggested by the user. This
investigation has not established that historical cause for either discrepancy.
For `new Struct()`, current declaration-kind metadata already provides the relevant
distinction; the ordinary construction lowering does not use it, as noted above.
No particular replacement S2S stage or input representation is chosen here.

**The blanket interior-reference ban may be relaxed for a defined safe subset,
provided the future S2S generator can preserve the same safety as our compiler.**
This is an agreed direction for contract refinement, not approval of arbitrary
references returned by today's runtime APIs. Before enabling such a subset:

- Establish owner lifetime and element validity throughout the borrow. Keeping
  the container alive does not alone guarantee stable element storage.
- Account for invalidation through mutation, aliases, called functions and
  callbacks. Call-scoped borrowing alone is insufficient.
- Enforce access permissions and prevent escape beyond the established lifetime.
- Define how both LLVM output and generated C++ enforce those same conditions,
  and prove the accepted and rejected cases. A C++ reference's mere compilability
  is not evidence of source-level safety.

Use shared analysis and operation-effect contracts for these decisions; neither
backend should independently guess safety from syntax. The exact supported cases,
proof mechanism and any runtime enforcement remain to be discussed. Cases whose
safety cannot be established remain rejected. Existing compiler borrowing support
and upstream documents/runtime APIs are unchanged by this decision.

## Owners and implementation boundary

| Owner | Responsibility |
|---|---|
| [type_model](../../src/04_analyze/type_model/data/definitions.php) | Shared operation availability, requirements, effects and provider/source provenance. Current `lifetime_contract` covers default construction/copy/cleanup; do not overload those fields to mean assignment or transfer. |
| Concrete type preparation | Derive per-type field composition and dependencies; reject unsupported operations precisely. Source and provider types meet through common contracts. |
| Body checking | Select construction/copy/assignment/borrow roles and check type/mutation permissions. Retain source provenance for diagnostics. |
| [Lifetime_Worker](../../src/04_analyze/analyze_lifetimes/body.php) | Establish initialization, live state, borrows, exits and cleanup obligations from checked bodies. Keep analysis separate from emitted instructions. |
| Backend preparation and lowering | Resolve operation implementations/layout/ABI, then emit accepted actions. A Clang-generated operation does not replace source lifetime analysis. |
| Coordinator and joins | Select work from exact dependencies; workers use fixed inputs/private results. Changes to field operations invalidate affected composition and bodies; unrelated body edits reuse type plans. |

STAN needs definite initialization, use permissions, ownership obligations and
eventually partial/moved state at control-flow joins. Model only states the supported
operations actually require; do not enumerate every complete path through a function.
Keep reusable type-level composition separate from body-specific lifetime facts.

**Implemented boundary:** the approved default/copy/destruction composition slice
is described above. The [source/native contract](source_native_contract.md) still
requires its later project-module/export integration. This milestone adds no
managed assignment, moves, general smart pointers, owned function boundaries or
exception unwinding.


## Custom source lifecycle bodies

Implemented as an explicit prototype extension to the current upstream compact-layout
specification, which still rejects struct methods. The existing class spelling is
reused: `public function __construct(): void` and `public function __destruct(): void`.
Default constructors and destructors take no explicit arguments. Custom copy uses
`public function __copy_construct(const record_type &$source): void`, with an exact
same-type const source and mutable destination receiver. Direct lifecycle calls are
rejected; construction and cleanup select complete operations. Field initializers,
constructor arguments, custom moves,
inheritance, recoverable exceptions and failure unwinding remain deferred.

A complete constructor initializes fields in declaration order before calling its
checked source body. A complete destructor calls its checked source body before
reverse field cleanup. Early body returns return to the complete operation;
body-local cleanup executes through the ordinary source lifetime path. Borrowed
receivers do not acquire another cleanup obligation. Custom default construction
does not run during implicit copy construction. Ordinary implicit copying still
depends on the field contracts. Owning allocation fields require an explicit source
copy policy; copying their descriptor bytes remains forbidden.

| Owner | Contract |
|---|---|
| `resolve_types/Source_Lifecycle` | Interpret source lifecycle spelling, validate the restricted signature and normalize stable member declaration IDs. Original AST and body contents stay with the source callable. |
| `type_model/source_lifecycle_operation` | A complete field plan plus an optional custom-body declaration ID. Concrete type identity distinguishes specializations; body text is not a type dependency. |
| `resolve_types/Concrete_Preparation` and `instantiate/Member_Join` | Demand custom bodies for each prepared template record, even without explicit method calls. Ordinary and implicit member requests use fixed worker inputs and the same join. |
| Signature, body, lifetime and lowering processes | Check and compile custom bodies as ordinary source methods with a borrowed receiver. No second body compiler or fabricated AST. |
| `prepare_backend/Backend_Join` | Match body declaration and concrete receiver to an accepted callable ABI; retain explicit body imports. |
| `emit_llvm/Lifecycle_Emission` and its join | Emit complete operations around the source-body call, checking the exact direct import set before acceptance. |

The [custom lifecycle proof](../../tests/integration/custom_lifecycle.php)
compares an independent C++ program with compiler output: a metadata-authorized,
over-aligned native vector field, nested source records, fixed arrays, two demanded
source template receivers, independent field copying, discarded temporaries, managed
body locals, and early constructor/destructor returns. It also checks O1/ThinLTO
linkage, reordered/private worker results and rejected incomplete or altered imports.
One template-destructor body edit updates all demanded bodies while retaining type
lifecycle plans and layouts; the prior snapshot remains unchanged. Ordinary source const references now accept managed/owning records through the same
address path. Mutable explicit references remain limited to plain records; mutable
managed method receivers retain their separate receiver contract.


### Custom copy construction

This is an explicit prototype extension: the upstream compact-layout specification
has no struct custom-copy spelling. The configured S2S generator emits ordinary
C++ structs and memberwise implicit copies; it rejects custom struct constructors.
For custom copying we follow [C++ member initialization](https://eel.is/c++draft/class.base.init#9):
initialize fields in declaration order, then execute the user body. Supported field
default contracts supply that initialization; the enclosing `__construct()` body
does not run. Missing field defaults are an error. This does not claim that C++
default initialization universally zeroes storage: our scalar fields use their
existing zero-initialization contract. Explicit initializer syntax remains deferred.
Without a custom copier, [implicit memberwise copying](https://eel.is/c++draft/class.copy.ctor#15)
continues to copy fields directly.

Each `lifecycle_member` now states its operation role. A complete custom copy has
copy ABI `(destination, const source)` but default-initialization field roles.
Automatic enclosing copies call the field's complete custom copy. Backend body
imports validate the two borrowed parameters; LLVM emission follows the accepted
plan without source-body duplication. Field noncopyability does not prohibit an
explicit enclosing copier that creates independent ownership from field defaults.

Ownership summaries describe zero-based parameter positions and relative field
paths. Complete copy summaries track the destination and unchanged source
separately; each field retains two input-state lanes, without combinations of
fields. Const managed/owning source parameters therefore use the ordinary checked
call path. Fixed tasks and joins verify parameter/path coverage and const-source
preservation. See [owning-field analysis](owning_storage_fields.md).

The custom-lifecycle test proves managed-field defaults, omitted enclosing default
body, nested fixed-array composition and rejection of unavailable field defaults.
The [growing-list proof](../../tests/integration/growing_list.php) proves
independent allocations, preserved order, two element types, ordinary const borrowed
calls, nested automatic copies, early-return cleanup, O1/ThinLTO and one copier-body
increment with retained snapshot/layout/runtime-package reuse. It rejects malformed
copy signatures, explicit calls, mutation of the source, unsatisfied allocation
requirements and altered ownership summaries. Copy assignment remains separate:
no live destination is silently destroyed or overwritten by this operation.

### Copy assignment

Implemented for source-defined records as the explicit prototype extension
`public function __copy_assign(const record_type &$source): void`. The receiver
is mutable, the source is exact same-type const storage, and direct lifecycle
calls are rejected. This is separate from `__copy_construct`: the destination
already exists. A custom assignment body owns the whole update; no automatic
field initialization, assignment or destruction surrounds it. Self-assignment
executes that body normally; the compiler does not insert an identity shortcut.

Without a custom body, field assignment composes in declaration order, including
fixed-array element order. All-value fields retain the direct value-write path;
nontrivial fields use a complete prepared assignment operation. An unavailable
field capability makes automatic assignment unavailable. Raw owning descriptors
cannot be shallow-assigned; a containing custom body can implement replacement
through the supported storage operations. Copy construction and assignment are
independent capabilities. Zero initialization is also independent of copying.
Zero-default records needing destruction use an owned construction destination,
including discarded temporaries; a custom destructor does not forbid value copying
or assignment when the fields permit them.

The source list allocates and copies replacement contents before clearing/releasing
the destination. This same algorithm supports self-assignment and retains exactly
one owner after transfer. Source analysis proves its resource requirements and
alias restrictions. No list-specific compiler behavior is involved.

Code owners:

- `type_model/lifetime_contract` carries `assignment` and `copy_assignment`.
  `resolve_types/Lifecycle_Composition` derives the complete operation; a custom
  assignment has an empty automatic member plan and its ordinary body import.
- `check_bodies/Statement_Checking` selects the checked write mode and an existing
  source borrow. `analyze_lifetimes/Ownership_Worker` and `Resource_Aliasing` prove
  complete effects through fixed tasks, private results and join acceptance.
- `lower/Local_Lowering::assign_local()` emits the selected prepared two-address
  call, preserving projected destinations. `emit_llvm/Call_Emission::emit_copy_assign()`
  and `Lifecycle_Emission` consume that operation without reconstructing semantics.

[Custom lifecycle tests](../../tests/integration/custom_lifecycle.php)
verify that custom assignment preserves native managed-field identity, composes
through fixed arrays and does not call the copy constructor for value assignment.
[Growing-list tests](../../tests/integration/growing_list.php) cover
independent storage, self-assignment, alias constraints, O1/ThinLTO and one body edit.
Fatal failure remains unchanged. Owned source results and construction from expiring
locals are now implemented through the [shared result boundary](owned_source_results_plan.md).
Managed temporary assignment, general move operations, owned parameters and exception
cleanup remain separate work.

Runtime-provider assignment now uses the same lifecycle model. Ordinary definitions
and native families explicitly bind `copy_assign`; Clang compiles the native
assignment and exports its two-address/void bridge. The adapter validates a live
mutable destination and live const source, both borrowed for the call, a null semantic
result and `self_assignment: native_call`. Both objects retain their cleanup
obligations. Assignment owns replacement of old contents; no compiler-inserted
destruction or self-alias shortcut surrounds the call. Native traits verify declared
capabilities but do not grant missing permissions. Provider assignment does not imply
deep copying: the native type's contract determines the value/handle semantics.

The [runtime assignment proof](../../tests/integration/runtime_assignment.php)
uses real strings and an instrumented provider type inside ordinary/generic functions
and source fields. The [family proof](../../tests/integration/provider_family_append.php)
checks the same role on prepared native vectors. Existing checking, lifecycle
composition, ownership, lowering and emission owners require no new assignment path.

### Consolidated consumption model

Body checking retains a resolved location before deciding how to consume it.
Arguments, initialization and assignment select their own access from their contracts.
A completed read is never changed into a borrow, or the reverse. Private pending rows
are completed in their existing value slots before a checked body can be published;
no pending representation reaches lifetime analysis or lowering. Initialization and
assignment have separate small selectors in `check_bodies/handlers/writes.php`.

`lifecycle_operation_kind::composition()` supplies constituent roles, body placement
and member direction through `lifecycle_order`. Complete source operations retain
this contract. Ownership preparation uses it for semantic child dependencies,
including resource obligations without executable cleanup. Emission uses it for body
placement and array direction. The role also describes source presence and whether
it starts a destination lifetime; ABI and resource-state interpretation remain with
their existing consumers. There is no general lifecycle interpreter or expanded
per-element plan.

These are implementation consolidations. Supported language forms, failure policy,
alias restrictions and runtime-preparation metadata remain unchanged. All 91 fixtures
are verified across a full run (10 jobs) and focused reruns after updating two copy
diagnostic expectations. Existing proofs cover native lifecycle order, list copying/
assignment, ThinLTO, worker purity, retained snapshots and incremental replacement.

### Custom-body verification checkpoint

The full prototype suite passed **86/86** with 10 jobs in **111.8 seconds**.
Final focused validation additionally covers exact exported source identities and
rejection of a record task with a substituted symbol snapshot. No execution-speed
claim follows from these correctness checks. Runtime preparation implementation
was unchanged in this slice.


## Dynamic storage direction and implementation status

The full typed-storage direction below remains incomplete. Its first
[local allocation-ownership slice](allocation_ownership.md) is now implemented:
metadata effects, native allocation/transfer/release, static normal-exit obligations
and active-call-borrow checks. The subsequent [typed-storage slice](typed_storage_plan.md) adds checked element
access and prefix operations for locals and static resource fields.
[Managed elements](managed_element_storage_plan.md) now use ordinary selected
copy/destruction at checked dynamic destinations. See [owning fields](owning_storage_fields.md).

Compiler semantics remain platform independent, consuming prepared target facts.
LLVM owns ordinary stack mechanics; Clang-prepared runtime bridges supply dynamic
allocation and matching release, including allocator-library integration. Source
element lifecycle stays compiler owned. Allocator identity and size/alignment
contracts must be explicit; no list name or field-name pattern selects behavior.

The source list will implement its own constructor/destructor and growth methods.
Allocation ownership is declared, not inferred from a raw pointer. The agreed
bounded storage descriptor carries address, slot count and constructed count;
its element type is static per concrete specialization. The list's length reads
the constructed count without duplicating it. Live elements form the prefix
`[0, constructed_count)`; arbitrary initialized holes are outside this slice.

Checked operations construct the next element, borrow a live element, destroy the
last element, release empty storage and transfer allocation ownership. Successful
construction/destruction updates the count with the operation. Transfer carries
address and both counts and leaves the previous owner empty. The descriptor does
not automatically duplicate the source destructor's element cleanup or release.

Static analysis tracks declared ownership effects and active borrows; it does not
infer safety from arbitrary pointer manipulation. Runtime checks cover unknown
bounds, count preconditions and allocation-size overflow. Release requires no live
elements; successful release consumes ownership. Normal exits must discharge local
ownership obligations. Unresolved conflicting ownership states at joins are initially
rejected. Fatal failure remains the agreed policy; cleanup during failure is not
promised. Local byte-allocation calls now have a concrete metadata contract;
typed prefix operations and static owning-field contracts are now implemented.

The initial source-written list uses no-cleanup elements and allocates replacement
storage on every append. Managed slot lifecycle is now proved separately; applying
it to that list is the next concrete-element proof. Its initial noncopyability and limited allocation transfer are
agreed; custom copy construction and assignment are now proved for the initial element subset. Typed local storage now supports call-scoped element borrowing with allocation
provenance. Broader container borrowing must preserve validity across its entire borrow.
