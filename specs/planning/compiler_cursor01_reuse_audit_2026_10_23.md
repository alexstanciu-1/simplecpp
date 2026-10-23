# CURSOR-01 reuse analysis
Doc Status: planning

**Current status:** accepted bounded integration and required route/diagnostic proofs
are complete; whole-compiler bootstrap and CH15 closure remain separate.

Accepted scope is constructor Storage_Cursor<T>(Storage<U>) with safe shared-record
U -> T, ordinary and template-prelowered output. No mutable Storage covariance,
conversion between cursor applications, new lifetime model or general constructor
redesign. Earlier paused wording is superseded by the accepted-backlog authorization.

## Existing owners and evidence

- runtime/include/modules/compiler/storage_cursor.hpp already provides the constrained
  constructor, shared cursor state and owning current() handles. Copies share traversal
  state; source membership stays retained. The existing native test proves widening,
  reverse-construction rejection, sparse keys, alias advancement, exhaustion, rewind
  rejection and retention after the source variable dies. Fresh shared-runner execution
  at /tmp/cursor-reuse-baseline-01 passes (1.32 s build/run).
- helpers/storage_cursor.php is the executable PHP binding, explicitly excluded from
  conversion. Existing compiler AST iterators construct both exact and widened cursors.
- Object_Construction_Preparation::runtime_factory already lowers registered new into
  a typed static factory and requires its exact owner return type.
- Runtime_Pattern_Registration::contract assigns owner parameters first and independent
  function parameters after them. Runtime_Call_Preparation::match seeds owner arguments
  then infers input variables. Thus T from the owner and U from Storage<U> already fit;
  neither inference nor mutable-container conversion needs a new algorithm.
- runtime_generic_contract already owns immutable positional requirements checked
  after inference (replacement results, boxed results, transfer policy). A directed
  shared-handle requirement belongs beside those, not in the type registry.
- Ownership_Conversions::decide plus Conversion_Relationships supplies identity/upcast
  admission with observed base/interface dependencies. Do not use full checked casts:
  a cursor cannot dynamically test every record or turn nullable records into required
  records. Only identity or safe shared ownership_upcast satisfies this relation.
- CPP_Callables::call_reference binds generic static factory members through the owner
  mapping. The existing runtime-call transport path is the prelowering reuse point.
  Native proof of the runtime alone does not prove that transport works for cursors.

## Bounded implementation shape

Register Storage_Cursor with shared_record parameter and inline copyable storage,
construction via make, and valid/current/key/next/rewind members. Add a static make
adapter forwarding to the existing constrained constructor; the existing C++ member
binding can call it without a new constructor-emission kind.

Add a named directed relation to runtime callable metadata, resolving declared source
and destination parameter names to positions at registration. A suitable bounded
spelling is shared_conversions: [{from: Source, to: Record}]. Validate unknown names,
wrong shapes and non-generic use at catalog ingestion. After structural inference,
check the relation through shared ownership/ancestry admission before admitting the
call. This is a constraint on the provider operation, not an implicit conversion of
its Storage<Source> argument to Storage<Record>. Keep pure structural matching free
of dependency publication. Do not rank overloads using the new constraint.

Retain source/destination names in diagnostics via resolved type formatting; explain
that ancestry does not authorize arbitrary generic conversions when no conversion
operation is registered. Keep missing-contract failure distinct from reverse or
unrelated ancestry, weak/unique ownership and nullable-presence failures. Never
suggest a PHS annotation that has no authored syntax.

## Remaining proofs and workload

Runtime substrate is proven. The first metadata/preparation slice is implemented;
full CURSOR-01 verification and explanatory diagnostic reconciliation remain open.
Remaining scope is medium (roughly 4–7 small bounded slices), reduced from 5–10 by
existing owner/input inference and runtime transport. This is not a completion claim.

Required proofs: renamed provider control (no Storage_Cursor semantic name gate),
exact/class-base/interface widening, reverse/unrelated rejection, absent metadata,
ownership/nullability rejection, malformed metadata, retained ancestry edit/recovery,
and ordinary/concrete native identity/lifetime/traversal behavior. Extract a portable
compiler cursor fixture for PHP and legacy native parity as well. Retry measured
bootstrap only after implementation and focused proofs; no new bootstrap for this audit.

If concrete transport reveals a new class-self/lifetime facade requirement, stop that
dependent slice for discussion; do not widen the accepted runtime constructor scope.

## First integration slice

`shared_conversions` records declared from/to names and resolves them to positional
requirements beside existing generic callable constraints. Runtime call instantiation
uses shared ownership admission with observed relationships; matching remains pure.
The source argument remains Storage<Source>. Registered construction now permits
ordinary input-inferred variables while requiring the exact owner return type.
The cursor exposes make/current/key/valid/next/rewind through compiler metadata;
make forwards to the existing constrained native constructor.

Ten focused tasks pass: ordinary/concrete native class widening and aliased record
mutation, plus reverse, unrelated, nullable-record and unique-record rejection in
both output modes. Catalog/type catalog regressions pass. Interface, renamed-provider and retained ancestry proofs now pass (follow-up below);
missing-contract diagnostics and portable-route proofs remain open.
The initial catalog regression invocation lacked its required output directory; the
corrected invocation passes. No production workaround was needed for that harness error.

## Provider and retained follow-up

Class/base and interface widening execute in ordinary/concrete modes with original
and renamed metadata source exposures. Three malformed relation forms reject at
catalog ingestion. The retained test removes/restores the base relation across files,
checks rejection again without further edits, compares recovery output and resets
preparation in both modes. All pass. This validates observed ancestry dependencies,
not just a fresh-build relation query. Production code unchanged for this follow-up.

## Portable route follow-up

Same authored cursor fixture now passes PHP, legacy native and ordinary/concrete
v0.2. It uses actual host helpers and runtime bindings, and verifies retained source,
shared traversal position, sparse keys and base-view record identity. The typed local
is declared once and subsequently assigned. Missing-contract/source-target diagnostics
remain open; this proof does not close that requirement or the full bootstrap.

## Diagnostic completion

Type_Descriptions renders existing canonical identities without type inference or
backend spelling. Failed construction names owner and supplied argument applications
and explains the missing compatible metadata contract. Shared-conversion failures
name both inferred element types; original ownership admission remains the validator.

An exact-only factory control rejects Storage<Derived> for Storage_Cursor<Base>,
with both types and the missing-contract explanation in ordinary/concrete output.
This intentionally removes the independent Source parameter as well as its relation:
leaving two independent generic parameters unconstrained would advertise a different
provider API rather than remove widening support. It must not trigger a hidden cursor
name rule. All 18 current cursor tasks pass, including renamed providers and retained
ancestry recovery. Earlier open-proof statements above describe prior checkpoints.
