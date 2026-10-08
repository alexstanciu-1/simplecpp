# Called-type callable instances
Doc Status: planning

## Approved direction — 2026-10-08

Late static binding separates the lexical declaring type from the called type.
`Child::method()` establishes Child; `self::method()` and `parent::method()` select
fixed implementations while forwarding that called type. `static::method()` selects
through it. Expression-qualified calls evaluate their qualifier once and establish
its declared type, not the runtime object's type. Static properties retain their
actual declaring storage owner; constants retain their selected declaration.

The first `new static(...)` group constructs shared class handles, checking the
selected constructor and result conversion. Inline struct construction/slicing and
runtime-called-type propagation through instance methods are not part of this group.
Compatible static/static redeclarations are approved with exact callable contracts
and no visibility narrowing; they must not be lowered as C++ virtual overrides.

Legacy `generators/php/specs/rules.md` limits late-static lowering to file-local
structural families. The v0.2 semantic compiler does not retain that restriction.
No ambient global called-type state or backend-only name/type resolution is allowed.

## Model and lifecycle checkpoint

**Implemented, not connected to source call selection yet:** an original
`collected_method` owns `called_instances`, uniquely selected by called-record
identity. Each `callable_instance` owns private parsed syntax/collection and one
working method. Original method and called type are weak observers, as are the
working-method context backlink and captured called-type scope identity.

`Callable_Instances::obtain` is a coordinator API, not an AST hook or an analysis
callback into parsing. It reuses the existing member fragment parser and publishes
only after successful materialization. Repeated requests return the same instance;
an instance cannot itself be used as the origin of another instance. Semantic
eligibility/ancestry and dispatch selection remain analysis responsibilities in
the next checkpoint; this API alone grants no new source-language capability.

Working bodies preserve the original declaring type and lexical scope. This differs
from trait contribution, which changes the declaring type to its consumer. Neither
operation adds specialized implementations to authored record membership. Independent
parameters/defaults/expressions/scopes carry ordinary signature and body work; source
and instance facts must never overwrite one another.

`Source_Fragments` shares token snapshots and ordered private-source retirement with
traits. Full token rows are copied so source cleanup cannot rebase working spans.
Successful parse joins retire stale callable instances before trait/source disposal.
Origin and called-source input/scope changes conservatively retire the affected
instances. Failed parsing preserves them until a successful join. Trait retirement
also retires instances of its working methods. Preparation reset clears private
facts while retaining work identity; full/syntax reset retires private collections.

The first policy is deliberately conservative: edits anywhere in either source file
rebuild affected instances. Narrower invalidation and compact immutable token storage
are optimizations, not permission to share mutable preparation facts. External
dependencies observed during private preparation use the existing analysis graph.

## Following checkpoints

### Ordinary scheduling integration — implemented

`Callable_Inventory` provides a read-only, deduplicated view of source, trait-private
and callable-private collections. The preparation worker selects existing private
implementations through ordinary signature/body work; it never materializes them.
These collections participate in lookup invalidation, failure/retry and reference-cycle
auditing, but are not returned as additional prepared program files. No-op preparation
retains body versions. Invocation-local `preparation_context::called_type` comes from
the working method's instance, separately from lexical `member_owner` and receiver.

The normal compiler preparation path now proves third-file dependency edits,
retained private failures on retry and identity-preserving repair. Explicit instance
materialization remains the test entrypoint; source calls still do not request one.

### Remaining integration

1. Connect demand-driven instance requests to coordinator materialization and the
   existing scheduler. Analysis must not call the parser. Intern before preparing recursive
   bodies; calls and dependency edges select the existing working identity.
2. Consume the separate called-type preparation context to resolve late-static operations
   there, and admit compatible static redeclarations without virtualizing them.
3. Emit distinct stable implementation identities, preferably out-of-line in the
   original declaring source's implementation unit. Include private implementations
   in readiness guards, fragment enumeration and successful change
   acknowledgement. Never emit them as additional ordinary class member declarations.
4. Prove forwarding, recursion, cross-file dependencies, constructors, storage owner,
   retries/edits and clean/incremental equality with generated-program execution.

Until those checkpoints land, `static::`/`new static` remain rejected and normal
calls never select these instances. Runtime instance dispatch remains separate.

## Proof

`tests/callable_instances.php` exercises key reuse, two independent prepared bodies,
lexical private access/type lookup, unchanged authored membership/facts, token-row
isolation, no-op output, failed-parse preservation, both source edit directions,
preparation reset and full retirement. The scheduling extension additionally exercises
ordinary compiler preparation, stable no-op versions, third-file constant dependencies,
failure/retry/repair and private-versus-program-file membership. The model runner includes this proof alongside
trait and incremental regressions. This is PHP-host evidence, not generated late-static
execution or a native compiler rebuild.
