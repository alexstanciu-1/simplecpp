# Called-type callable instances
Doc Status: planning

## Approved direction and implemented scope — 2026-10-08

The [normative contract](../../../../specs/inheritance_v0_2.md#late-static-calls-and-shared-factories)
separates lexical declaring type from called type. `Child::method()` establishes
Child; `self::method()` and `parent::method()` select fixed implementations while
forwarding called type. `static::method()` selects through it. Expression-qualified
calls evaluate their qualifier once and establish its declared type, not the runtime
object's type. Properties retain their actual declaring storage owner; constants
retain their selected declaration. These paths now execute through shared method
preparation and C++ callable bindings.

`new static(...)` constructs shared class handles using checked constructor/result
conversions. Inline struct construction/slicing and runtime-called-type propagation
through instance methods remain outside this group. Compatible static redeclarations
use exact contracts and no visibility narrowing; they are not C++ virtual overrides.
Unlike v0.1's file-local structural lowering, cross-file families are supported.
There is no ambient called-type runtime state or backend semantic name resolution.

Approved diagnostic timing: declarations/signatures/defaults are checked eagerly;
static bodies are syntax-checked and then semantically checked per demanded instance.
Uncalled static bodies are not fully semantically checked. This lets an abstract
Base define `make(): Base { return new static(); }` without constructing Base while
checking a call to `Child::make()`. Other callable body-check timing is unchanged.

## Ownership and lifecycle

An original `collected_method` owns `called_instances`, uniquely keyed by called-record
identity. Each `callable_instance` owns private syntax/collection and one working
method. Original method and called type are weak observers, as are the working-method
context backlink and captured called-type scope identity.

`Callable_Instances::obtain` is coordinator-owned, not an AST hook or analysis callback
into parsing. It reuses member-fragment parsing and publishes only complete instances.
Repeated requests reuse identity; an instance cannot itself become an origin.
Analysis owns semantic eligibility, access, ancestry and dispatch selection.

Working bodies preserve original declaring type and lexical scope. Trait contribution
instead changes the declaring type to its consumer. Neither operation inserts helpers
into authored source member membership. Independent parameters, defaults, expressions
and scopes carry ordinary work; source and instance facts never overwrite one another.

`Source_Fragments` shares token snapshots and ordered retirement with traits. Full
token rows are copied so source cleanup cannot rebase private spans. Successful parse
joins retire stale instances before source/trait disposal; failed parsing preserves
them. Preparation reset clears facts while retaining work identity; full/syntax reset
retires private collections and analysis dependencies before releasing ownership.

The first invalidation policy is conservative. Call-bearing body, record, import and
deletion changes retire the existing family before preparation rebuilds current
demands. This removes implementations whose last call disappeared, including obsolete
body diagnostics. Origin/called-source input or scope replacement also retires affected
instances. Constant-only edits in another file preserve instance identity and use
ordinary dependency invalidation. More selective reachability/pruning and compact
token snapshots are future optimizations, not permission to share mutable facts.

## Scheduling and coordinator handoff

`Callable_Inventory` is the read-only, deduplicated source/trait/private collection
view. Original static signatures are selected eagerly, but original static body work
is skipped; existing private implementations enter normal signature/body queues.
Reference-cycle auditing follows those working identities. Private collections never
become additional prepared program files. The invocation-local context carries
`called_type` separately from lexical `member_owner` and the instance receiver.

`Preparation_Run` drives compiler and standalone preparation. An executable static
call selects an existing implementation or publishes `callable_instance_request`
and suspends. The coordinator materializes the missing original/called pair and
resumes with a fresh worker; settled work retains identity/version. Each suspension
must introduce a missing key, otherwise the coordinator rejects lack of progress.

The signal is one worker-local built-in RuntimeException, compared with portable
`same_exception`, never by message text. Suspension leaves body work pending, not
failed, without completion-version advancement. Only executable bodies can request
instances; declaration/default suspension is not enabled. No worker, request or signal
is retained by syntax. Normal errors retain ordinary failure/retry behavior.
Materialization precedes body preparation, so recursion reuses the interned identity.

Successful preparation acknowledges parsed changes on original deferred bodies without
claiming their semantic completion. Successful whole C++ publication acknowledges
changes across the complete callable collection view, including private implementations.

## C++ lowering

`CPP_Callables::implementations` supplies a backend-only emission view: ordinary
instance methods plus demanded static working methods. Source member lookup and type
contracts still contain original declarations only. Stable helper names encode source
method and called-type identity using the backend's identifier encoding.

Helpers are static C++ members of the original declaring record, preserving native
private/protected access. Their declarations belong in that record's type header;
definitions remain out of line in its source implementation unit. Header changes when
new called-type instances are demanded are intentional. No semantic resolution or
instance materialization occurs during emission. Ordinary readiness, dependency,
signature/body fragment and result-conversion paths are reused.

## Proof and remaining work

`tests/callable_instances.php` covers identity, private syntax/token isolation, lexical
lookup/access, unchanged source membership, third-file dependencies, failure/retry/
repair, parse failure, source edits/removal and resets. Explicit test-only seeding
occurs after the source preparation boundary consumes pending call-shape edits.
`tests/callable_demands.php` covers exact signal identity, pending work, coordinator
resumption, forwarding/reset, recursive identity and the standalone adapter.

`tests/late_static.php`, through `records.py --suite late_static`, proves actual
dispatch, forwarding/reset, shared factories/constructors, inherited storage, structs,
declared-type qualification, deferred bodies and cross-file removal/retry/repair with
fresh/incremental equality. Adjacent method/reference-return suites cover existing
call contracts. These are PHP-host and generated-program proofs, not a native compiler
rebuild. Dated evidence is recorded in CH11.

Remaining: runtime-called-type propagation through ordinary instance methods requires
a separate design; inline `new static` remains rejected. Reconcile the remaining CH11
catalog groups before choosing another slice. Precise demand-family pruning is an
optimization follow-up, not an extension to this execution contract.
