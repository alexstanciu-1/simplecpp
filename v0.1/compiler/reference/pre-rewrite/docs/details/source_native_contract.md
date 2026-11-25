# Source types at the native preparation boundary
Doc Status: supporting

Status: design contract with bounded implementation, 2026-09-21. Follows the
[ownership decision](clang_lifecycle_composition.md#ownership-decision) and
[adapter proof](source_operation_adapter_proof.md). Shared physical preparation,
selected source export contracts and explicit native project-module preparation are
implemented in [gates 1–4](source_family_integration_plan.md). Compiler demand routing,
project-result import, export emission and final native linking now execute the bounded
source-record subset, including nested source/native types and
[verified custom lifecycle bodies](custom_source_exports.md). Ordinary
preparation version 1 retains its self-contained package guarantee; project modules
have a separate version-one contract and explicit unresolved source obligations.

**Implementation order:** compiler-owned field composition is now implemented for
default construction, copy construction and destruction using already prepared
runtime fields. See [bounded composition](lifecycle_contracts.md#first-compiler-implementation-bounded-composition).
The six roles and project-module protocol below describe the native integration
boundary. The supported preparation subset is recorded above; the full protocol
was not a prerequisite for that first compiler composition slice.

## 1. Owners and initial scope

| Concern | Owner |
|---|---|
| Nominal source identity, fields and operation permissions | Source resolution and the shared type model |
| Complete source construction, copy/move, assignment and destruction | Compiler checking/lifecycle/lowering processes |
| Accepted target storage and physical calling convention | Backend layout/ABI preparation and its joins |
| Runtime implementation and concrete native family specialization | Runtime preparation through Clang |
| Native adapter generation | Runtime preparation, consuming the fixed compiler export |
| Project request selection, acceptance and link closure | Compiler coordinator and explicit joins |

Source and imported fields retain those owners inside a containing source type.
Clang's adapter only forwards complete source operations; it must not independently
compose source fields. JSON carries facts and selected roles, never arbitrary code.

The first adapter profile is `inline_source_payload_v1`: a concrete native type
containing aligned inline byte storage and forwarding special members. It supports
complete, nonempty value records and the six lifecycle roles below when available.
It does not enable classes/inheritance, custom methods, arbitrary native field
access, union/overlapping layout, pinned/self-referential representation, aggregate
value ABI, exception unwinding, or additional source syntax. These require separate
contracts. A denied requirement produces a diagnostic, not an alternate hidden path.

## 2. Source identity

Keep **semantic identity**, **contract revision**, and **artifact integrity** separate.

- `Type_Store` IDs are compact internal references valid only with their retained
  lineage. Existing instance IDs likewise remain internal. Neither is a portable
  artifact key on its own.
- The compiler export uses an exact structured key:
  `source(project_key, declaration_key, ordered_arguments)`.
- `project_key` is an explicit build-configuration namespace owned by the build
  coordinator. It is reused for that project output scope. Conflicting projects
  with the same namespace in one link are rejected, not silently merged.
- `declaration_key` identifies the definition through its resolved module/source-unit
  identity and qualified declaration components. Use separate tuple components,
  not ambiguous string concatenation. A file-based unit uses a normalized
  project-relative path. Renaming/moving that unit changes this key. Local/anonymous
  declarations are not exportable in this initial profile.
- A template instance refers to the template definition key plus its ordered,
  **tagged** arguments. A type argument carries its exact source/provider/language
  type key; a constant carries its exact type key and normalized integer value.
  Thus `hash<A, B>` differs from `hash<B, A>`; `N = 3` and `COUNT = 3` of the same
  constant type identify the same specialization. Unevaluated expressions are not keys.
- A source operation key is `(source_key, role, signature_key)`. The initial six
  roles each have one signature; future constructor overloads must have distinct
  signatures rather than overloading a single constructor identity.

Resolution owns the declaration/argument meaning; the coordinator's export map
associates these exact keys with current internal IDs. It is not a second nominal
type system. Request-local row indexes may compact wire references; the request
owns their scope, and indexes are never persisted as semantic identity alone.

Native names reversibly encode the profile and complete qualified key using the
existing [symbol format](../../src-runtime-preparation/definitions/README.md#generated-symbol-identities).
Use exact key comparison and reject duplicate rows or conflicting definitions.
Names, equal layouts or hashes alone never establish type equivalence.

Contract changes can retain nominal identity while invalidating artifacts. The
selected request carries fixed contract provenance; join acceptance checks it
against the current selected inputs. Fingerprints may detect input changes or
verify file integrity, but cannot replace semantic identity or contract comparison.

## 3. Layout and representation

The export references one **accepted source layout**, owned by backend preparation:

- Exact source key and current definition provenance.
- Target triple/data layout and the selected ABI/toolchain configuration.
- Byte size, alignment and array element stride.
- Ordered field identities, field type/layout dependencies and byte offsets,
  including nested/fixed-array layout references where supported.

Share this layout with compiler lowering; do not reconstruct a second layout from
the native adapter. Source declarations determine field meaning/order, and target
preparation supplies physical facts. Clang may measure a layout witness, which
never constructs a source object or acquires its lifecycle implementation.

Initial profile invariants: positive size and power-of-two alignment; stride equals
size; size is a multiple of alignment; fields fit and do not overlap; each field
meets its accepted alignment. Padding has no value semantics and must not be read,
compared or copied as a substitute for an owned operation.

Preparation measures the resulting adapter and checks:

```text
adapter size       == accepted source size
adapter alignment  == accepted source alignment
adapter stride     == accepted source stride
payload offset     == 0
```

The physical facts must match the selected target, including over-alignment support.
A disagreement is a rejected preparation result, not a reason to change the source
layout downstream. A layout change invalidates dependent source code and native
specializations even when the nominal source key is unchanged.

The native adapter is a distinct C++ type with a declared mapping to source payload
storage. Equal size and zero offset do **not** authorize casting a source address
to an adapter reference. Native templates requiring a particular handwritten C++
type or direct members are outside this profile.

## 4. Capabilities and object states

Each role has one explicit state: `available`, `forbidden`, or `unsupported`.
`available` references a declared complete operation the compiler can materialize;
`forbidden` is a source semantic restriction; `unsupported` is a current compiler
limitation. Both latter states reject a demand. Absence is an incomplete contract,
not permission to infer a default from C++ traits.

Preparation establishes the declaration, layout and ABI contract. Custom source bodies
are checked later through ordinary body/lifetime analysis. Before source emission,
selected export verification must accept their current implementation evidence and
ownership compatibility. An early prepared project module does not certify that the
source program is valid. See [verification and reuse](custom_source_exports.md).

A proven trivial operation may have an empty implementation, such as destruction
of a value with no cleanup. That is a compiler-owned implementation justified by
its checked contract, not a dummy body for an unavailable operation.

| Role | Before | After normal return |
|---|---|---|
| `default_construct` | Aligned destination storage, no live object | Live owned destination |
| `copy_construct` | Uninitialized destination and live readable source; disjoint storage | Live owned destination; source remains live/unmodified; resource relationship follows the type's copy contract |
| `move_construct` | Uninitialized destination and live source with transfer permission; disjoint storage | Live owned destination; source remains live/destructible in its declared moved-from state |
| `copy_assign` | Live destination and live readable source | Destination remains live with assigned value; source preserved |
| `move_assign` | Live destination and live source with transfer permission | Destination remains live; source remains live/destructible in its declared moved-from state |
| `destroy` | Live object whose cleanup is due, with no conflicting active borrow | Object becomes dead; its complete cleanup has executed once |

Copy does not universally mean independent resources: copying a record's shared
handle can share a pointee. Effects come from checked field/source contracts.
Move poststate must state the applicable guarantee, e.g. unchanged, empty, or valid
with unspecified value. Never mark the source dead merely because it was moved.

Assignment effects include replacement of old owned contents. Copy self-assignment
must preserve the value; move self-assignment must leave a valid live object with
its documented state. Other partial storage overlap is unsupported. These guarantees
belong to the complete source operation. The native forwarding member must not add
its own self-assignment algorithm or destroy/reconstruct the destination.

The complete capability table determines one consistent native adapter declaration
per source type/profile/configuration. A read-only family demand and an append demand
must not produce different declarations of the same adapter. Unavailable special
members are explicitly unavailable; never let C++ implicitly synthesize byte copying.
This profile has no implicit move-to-copy fallback. Any later permitted fallback
needs an explicit selected binding and source-state contract.

The initial adapter is nontrivial because its special members forward. Do not
advertise source triviality as native adapter triviality. Provider family definitions
must declare compatibility with this profile and required capabilities per operation
and per type-argument position; preparation compiles the actual demanded native
operations as well. For example, a two-parameter family cannot apply a value's
capability requirements to its key by accident. Hashing/equality are not supplied by
the six lifecycle roles and remain separate requirements when needed.

## 5. Function imports

The native project module **imports** complete source operations. The compiler's
object/LLVM module **defines** them. These imports refer to complete operations,
not just user-written constructor/destructor bodies.

| Role | Initial physical interface (`ccc`, LLVM result `void`) |
|---|---|
| Default construction, destruction | `(ptr destination)` |
| Copy construction/assignment | `(ptr destination, ptr source)`; semantic source borrow is const |
| Move construction/assignment | `(ptr destination, ptr source)`; semantic source borrow is mutable |

Pointers are target address-space-zero addresses to source payload storage, never
native adapter references or pointer-sized ownership handles. No aggregate passes
by value in these signatures. Physical attributes are prepared and validated, not
guessed from semantic constness. In particular, do not infer `noalias`, `readonly`
or dereferenceability merely from a borrow or a numeric size.

An import record contains:

| Field | Meaning |
|---|---|
| Operation/source/layout references | Exact identity and accepted contract provenance |
| Link symbol | Reversible full operation identity in the compiler-source namespace |
| Role and semantic parameters | Access, object state, aliasing, ownership effects and poststates |
| Physical signature reference | Calling convention, result, ordered argument types/address spaces and required ABI attributes |
| Failure contract | `terminate`, no exception crosses this interface |
| Address escape | Payload addresses are call-scoped; no retained pointer into caller storage |
| Implementation owner | Compiler source-operation module |

Address non-retention does not forbid copying ownership of separately allocated
resources as specified by the value's contract. It forbids saving a borrowed
payload/field address for later use. Address-sensitive types require another
profile/contract. A returned or stored borrowed view needs explicit lifetime and
invalidation analysis; it cannot inherit permission from the pointer ABI alone.

All hooks in this profile are non-unwinding. Emit `noexcept`/LLVM `nounwind` only
when the compiler can satisfy that contract through its selected operations.
Fatal failure has no normal poststate and promises no complete failure cleanup.
Recoverable exceptions remain deferred. ABI checking must preserve the required
physical contract and reject unsupported attributes; optional optimization promises
must also be justified before either side relies on them.

Capability declarations authorize the imports preparation may reference. The result
reports the required subset used by its generated artifacts, including transitive
native instantiations. The compiler materializes that subset and checks it against
the fixed capability table. Unexpected source imports, missing implementations or
incompatible signatures reject the join. No weak/dummy implementation is permitted.

## 6. Payload crossings and native exports

Treat these as distinct adaptation roles, not interchangeable `const T&` calls:

| Crossing | Contract |
|---|---|
| Copy source into native storage | Borrow live source payload; construct a real adapter object whose forwarding constructor calls `copy_construct` |
| Move source into native storage | Require transfer permission; construct a real adapter and apply the declared source move poststate |
| Copy/move payload out | Receive caller-owned aligned uninitialized storage; invoke the complete source operation; caller becomes cleanup owner |
| Borrow native payload into a compiler call | Derive payload from a live adapter; call-scoped const/mutable permission; no conflicting container mutation or invalidation for the full borrow |

The container retains the new adapter and its owned payload, not the caller's
borrowed address. A source constructor is called once for each new lifetime; do
not default-construct a payload and then treat it as uninitialized copy storage.
Destruction of an adapter calls the complete source destructor once.

Each exported native operation records which adaptation applies at each semantic
parameter/result and its measured physical ABI. Existing object-reference metadata
does not express these crossings and must not be reused to hide a reinterpret cast.
Long-lived interior references are outside this first profile, even though future
source borrowing may be allowed when validity can be established throughout.

## 7. Request, result and joins

These are logical records; physical JSON field spelling/version is reserved for
implementation. Use shared rows and references rather than copying contracts into
each family demand. Do not extend production schema version 1 implicitly.

| Record | Contents |
|---|---|
| Selected project request | Exact project/unit key, fixed selection provenance, target/toolchain/runtime dependencies, adapter profile, source/layout/capability tables, authorized source imports, ordered family arguments and demanded native operations |
| Private preparation result | Echoed request provenance, measured adapter/family layout and ABI, required source imports, native exports, artifacts/variants and dependency evidence |
| Accepted project module | Validated immutable contracts and retained artifact ownership; source-link obligations remain explicit |
| Link input set | Accepted native modules, selected compiler definitions satisfying imports, and explicit runtime/toolchain link dependencies |

**Preparation join:** reject missing/duplicate/unsolicited results, stale selection,
identity conflicts, layout/profile/configuration differences, unauthorized imports
and unsupported demands. Compare required import signatures with the compiler's
prepared ABI. Accept the result only as an open *project module*, never as an
already self-contained runtime package.

**Final link join:** verify each required source import has exactly one selected
compiler definition of the matching identity, revision, semantic effects and ABI.
Then perform the actual link with all declared native dependencies; reject unresolved
symbols. Successful symbol linkage alone is not a signature/effect check. Generated
C++ support symbols follow their native linkage rules, but cannot replace or
duplicate a compiler-owned source-operation definition. Stale native artifacts with
matching symbol spelling must still fail provenance validation.

Existing ordinary runtime packages retain their self-contained native-link check.
Application-specific artifacts live in a separate configurable project output root,
with stable paths and replacement under retained-use locking. They do not mutate
the shared runtime package. Artifact fingerprints verify bytes, not entity identity.

The coordinator owns the initial unit key `(project_key, build_configuration_key)`;
the build configuration names one target/toolchain/runtime selection and adapter
profile. Exact current configuration contents remain validation inputs, not an
assumption implied by the configuration's name. Selection provenance includes the
coordinator's update/request generation and fixed input references. Those generation
numbers are scoped to the retained coordinator and are not persistent cache keys.

## 8. Work ordering and reuse

### Accepted family demand and reuse model

Accepted on 2026-09-20. The bounded native-family preparation and compiler boundary
are implemented, including [nested prepared arguments](provider_family_compiler_integration.md#nested-native-argument-boundary).
Source-dependent specialization integration remains pending.

The subsequent [default generic parameter decision](generic_type_contract.md)
requires copy construction, copy assignment and valid automatic cleanup for bare
`<T>`, while distinguishing declared permissions from concrete type knowledge.
Family acceptance contracts must respect that boundary; bare source parameters
do not acquire native members through specialization. Additional explicit source
capability contracts remain documentation-only in the initial slice. Baseline
eligibility and definition permissions are enforced for the bounded source slice;
native provider families consume the same baseline through metadata; source-dependent
preparation integration remains pending.

Keep a **family definition** separate from a **prepared specialization**. The
definition supplies exact family identity, ordered parameters, semantic operation
signatures, capability requirements per operation/argument position and the native
implementation binding. The compiler can resolve concrete demands from that
contract before their native artifacts exist. Acceptance still requires preparation
to validate the demanded native operations and their layout/ABI.

A prepared specialization supplies measured layout, operation ABIs, LLVM artifacts,
any required source-operation imports and reuse dependencies. Variables and call
sites share compatible specializations; their identities do not depend on hashes.

| Dependency scope | Reuse scope |
|---|---|
| Runtime/language types only, such as `vector<int>` | Shared across projects for the selected runtime, target and configuration. |
| Any source-defined type, such as `vector<MyStruct>` | Project-scoped, with explicit imports of compiler-owned source operations. |

Follow the full dependency set when selecting scope, including nested arguments.
A body-only source edit can reuse native artifacts when the exported contracts
remain compatible; recompile the affected compiler-owned definitions and relink.
Do not regenerate ordinary runtime bindings per project or source file.

```text
resolve concrete family demand
  -> reuse a compatible prepared specialization [if available]
  -> coordinator selects preparation work [otherwise]
  -> preparation returns private metadata and artifacts
  -> join validates and accepts the fixed result
  -> dependent compiler work continues
```

Discovery may advance through successive dependency-ready batches. Workers report
missing prerequisites; they do not generate or replace packages underneath readers.
The shared artifact retention/publication policy and concrete family metadata are
the next design details to settle. Existing project-module ownership and import
validation below remain applicable to source-dependent specializations.

### Accepted family requirements and operation signatures

The family model has two distinct requirement locations: requirements to form a
type, and requirements to use an operation. Initially support only the agreed
default generic baseline; a general constraint language and user-written capability
syntax remain deferred. Individual operation needs such as copy construction must
still be explicit, using existing lifecycle capabilities rather than discovering
them only through a Clang compilation failure.

Checking a generic definition against its declared guarantees is a language contract
requirement. Forwarding `T` into a provider operation is legal only when those
guarantees satisfy its requirements. Favorable concrete substitution cannot grant
additional permissions. Family slots are ordered and independently referenced,
including families with two type parameters.

The agreed initial signature model uses a copy-based append as its example:

```text
vector<T>.append(const T& element) -> void
```

This is the agreed semantic bridge model. The configured runtime's
`scpp::vector_t<T>::append(const T&)` forwards to its native vector's `push_back`;
the existing isolated sequence adapter calls that overload. This confirms the
basic signature, not the full alias/invalidation contract. The isolated adapter
currently documents a distinct source; it is not evidence for accepting self-append.

| Signature component | Meaning |
|---|---|
| Receiver | Mutable, call-scoped borrow of the concrete family instance |
| Element type | Symbolic reference to the family's type-parameter slot |
| Element passing | Read-only, call-scoped borrow; caller keeps the original value |
| Operation requirement | Copy construction of the element through its lifecycle contract |
| Result | Void; the inserted copy belongs to the container |

Borrowing the argument and constructing the stored copy are separate operations.
Copying does not imply raw-byte copying or deep copying. Resolve symbolic type
references into the common concrete callable contracts; argument checking, borrowing
and ABI preparation should then use the existing shared paths. Physical size,
alignment and ABI remain concrete preparation outputs.

Start the integration proof with borrowed arguments and a void result. Owned
element results and borrows returned into container storage have separate lifetime
contracts and remain later discussion. The subsequent
[append-alias investigation](provider_append_aliasing.md) supports native copy-based
self-append, including forced growth, without an extra bridge temporary. Input
overlap safety and invalidation of existing element borrows are separate facts.
Typed family declarations now represent both facts; compiler acceptance still needs
integration. The native proof does not expose a source interior-reference API.

### Accepted typed records and semantic/ABI separation

Use typed records internally (record classes in the PHP prototype). JSON remains
an external definition/transport format, parsed and validated at the preparation
and adapter boundaries. Compiler consumers read compiler-owned contracts, not JSON
keys. No separate formal JSON Schema framework is required by this decision.

Family declarations retain exact identity, ordered parameters, formation requirements
and operation declarations, without concrete layout or ABI. Semantic signatures
describe parameter type references/passing modes, result type/ownership, capability
requirements and call effects. References can name a type, a formal slot or a family
application with ordered arguments; they must not invent concrete IDs/layouts for
unresolved parameters. Reuse existing owners for these concepts.

Represent a method receiver as a semantic parameter; source exposure identifies its
receiver position. For copy append, parameter 0 is the mutable family-instance
borrow and parameter 1 the const element borrow. Effects refer to these positions
and resource relationships. Owned result semantics remain separate from direct or
caller-storage ABI transport; borrowed results need separate owner relationships
and remain deferred.

The implemented `runtime_callable` associates an independent `semantic_signature`
with a verified `runtime_callable_abi`. Ordinary imports and family declarations share
the semantic records. Checking reads declared passing and result production; backend
preparation reads the physical association. Family declarations have no fabricated ABI
or concrete type IDs. The approved model migration is complete; compiler-driven
family consumption is in progress through the separate integration below.

Clang establishes layout and physical calling details. Declared ownership/effect
guarantees remain provider obligations, supported by contracts and focused proofs;
successful Clang compilation or ABI measurement does not prove those guarantees.

### Accepted specialization request and package replacement model

A typed request references the accepted family, ordered concrete arguments, required
operations (including lifecycle dependencies), preparation context and any source
layout/capability/operation-import dependencies. Worker inputs are fixed accepted
records; requests contain neither copied ASTs nor mutable compiler-private state.

Specialization identity uses exact family identity, ordered argument identities and
preparation context, with the shared/project scope already described above. The
requested operation set is coverage, not part of the concrete type identity. Additional
operation demand must not create another type. Runtime/toolchain/target/configuration
and source compatibility checks still govern reuse; hashes cannot establish identity.

Initially retain one replaceable package per specialization. A later operation
request prepares a replacement containing the previously accepted coverage plus the
new demand and dependencies. Build it privately; validate exact identity/context,
operation coverage, semantic signatures, measured ABI/layout, artifacts and required
source imports before publishing and accepting contracts. Existing readers must retain
a consistent package through the publication owner's locking/lifetime protocol.
Do not overwrite files that an accepted reader may still consume.

The published location remains stable. Temporary preparation output is removed after
completion or failure. Failed preparation/validation preserves the previous accepted
package and supplies a diagnostic to the requesting compilation; partial contracts
do not enter retained compiler state. Shared package publication is not the same
transaction as publishing the final executable. Reuse the existing preparation and
publication mechanisms rather than introducing a general transaction framework.

The compiler coordinator selects demand; preparation produces private native outputs;
the adapter normalizes contracts and joins validate fixed results before dependent
compiler work resumes. Batched execution may prepare multiple specializations, but
the replacement unit remains the specialization package. Detailed APIs, lock ordering
and source-import acceptance remain implementation-plan work.

The first package-boundary proof should prepare, reuse without regeneration, extend
operation coverage without changing type identity, and reject an incompatible
replacement while preserving the accepted package. This is a bounded preparation
proof, not a new compiler rollback/recovery requirement or a substitute for later
source-to-output integration.

### Source-dependent preparation order

The flow extends existing owners; it does not create a parallel compiler:

```text
resolved concrete types + checked operation contracts
  -> accept source layouts and source import ABI
  -> select fixed project preparation requests
  -> prepare native adapters/families in private work outputs
  -> join native results and required import closure
  -> materialize/lower the selected compiler-owned operation implementations
  -> join definitions and link
```

Clang can compile adapters with declared source imports before their definitions
are emitted. Imported runtime-field layout must already be available before its
containing source layout is accepted. By-value layout dependency cycles are errors;
function link dependencies do not themselves form a layout cycle. Nested mixed
families need explicit dependency-ready work, not speculative layout or recursion
through partially accepted results.
Semantic recursion through a container is not automatically a by-value layout
cycle: follow the provider's actual completeness/layout requirements. Incomplete-type
family support is not supplied by this initial contract and must be diagnosed
precisely if required.

Selected concrete demands can be executed in batches; the later agreed replacement
unit above is one package per specialization. Workers receive fixed requests and dependency snapshots, own private
outputs, and never allocate canonical compiler identities. Joins map validated keys
back to the current retained type lineage. Future finer scheduling uses the same
contracts. Full rebuild selects all work through the normal update flag; removed
contributions disappear independently of selection.

| Change | Required work |
|---|---|
| Source operation body only, same accepted contract | Recompile affected compiler definitions and relink; reuse native bundle |
| Source operation ABI/effects/availability or adapter profile | Recheck affected uses; rebuild affected native bundle and compiler consumers |
| Field/type layout or target configuration | Recompute dependent layouts, native preparation and affected compiler code |
| Demanded native operations/family arguments | Select updated preparation bundle and dependent consumers |
| Runtime/header/toolchain dependency | Invalidate affected native preparation and dependent ABI/layout consumers |
| Removed source operation/type | Remove its contributions; reject surviving demands/imports |

A changed initializer expression may be a body-only edit if its operation contract
and representation remain identical; changed constructor selection or capabilities
are contract changes. Do not classify by source text location alone. Reusing native
bitcode does not promise reuse of LTO-optimized machine code after relinking.

Current proof scope remains one full build plus one incremental attempt. Persistent
cross-session reuse requires exact export keys/configuration and current contract
validation; retained-session numeric IDs or generation counters cannot authorize it.

## 9. Implementation gate

The [implementation record for parts 1–3](provider_family_implementation_plan.md)
now records the approved migration: semantic signatures separated from ABI, typed family
contracts and runtime-only specialization preparation/reuse/replacement are implemented.
The production argument subset includes declared runtime scalars, ordinary managed
runtime types and nested prepared native families. The isolated source-record
experiment remains separate; source-dependent native imports remain a subsequent
boundary.
The [compiler integration plan](provider_family_compiler_integration.md) now records
accepted source-exposure, readiness, scalar-borrow and multiple-package decisions,
with runtime-only source consumption as the current bounded slice. Explicit package
composition and source-family registration/permission checking are implemented;
coordinated type-demand preparation and implicit native construction/cleanup now
work. Concrete method demands/signatures and calls using existing value/object-borrow
ABI contracts now work as well. Const integer-address arguments borrow matching
places or compiler-owned temporary storage; real native vector append is proved
through this shared path. Source-dependent imports remain outside that slice.

Compiler-owned source identity/lifecycle exports and the explicit project preparation
adapter now exist. Compiler project-result import and final link closure are now implemented.
The existing `Specialization_Request` and
`Prepared_Request` cover self-contained plain-record experiments, not this protocol.
Extending them must retain the old package guarantee and expose the new module kind.

The first source-dependent integration must prove one eligible managed source record, native
container copy-in/copy-out, correct cleanup and body-only native reuse. Rejection
coverage must include distinct same-layout types, stale type lineage/provenance,
layout or ABI mismatch, unavailable capability, missing/duplicate imports, and
misusing source payload as a native object reference. A second compatible source
shape must follow through the same data-driven path. Actual threading, general
recovery, all families and all lifecycle syntax are not requirements of that slice.

These changes cross type/operation contracts, backend preparation, project module
acceptance and linking. The approved
[source-family integration plan](source_family_integration_plan.md) maps the boundary
to four gates. Gates 1–3 implement shared dependency-ready layouts, explicitly selected
source export contracts and native project-module preparation with payload crossings.
Gate 4 now connects compiler source-argument routing, explicit project-result import,
export emission and final link closure. Compiler_Session compiles source-dependent
native families end to end with an explicit native_project configuration. The proof
covers balanced cleanup, one body increment with native reuse, O0/O1 and ThinLTO.
Custom lifecycle exports now add a post-analysis verification gate; see the
[custom export proof](custom_source_exports.md). General moves and smart-pointer
integration remain excluded; no execution-performance equivalence claim is made.
