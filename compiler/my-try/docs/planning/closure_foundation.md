# Closure callable foundation
Doc Status: planning

## Agreed scope — 2026-10-08

Start CH12 with capture-free concrete closures, explicit parameter/return types,
local initialization and exact-arity invocation. Follow the normative
[closure contract](../../../../specs/language/closures.md): distinct expressions
have distinct concrete types; inferred locals do not automatically use type erasure.
Explicit callable boundaries are a separate follow-up.

Preserve authored parameter names for future named calls. Anonymous callable
identity belongs to its source occurrence and enclosing context, not its local
variable name or a backend spelling. A traversal counter alone is insufficient
for retained identity. Incremental reconciliation must explicitly handle insertion,
replacement and deletion; a new ambiguous occurrence may receive a fresh identity.
Generated names remain backend-owned. Names at erased callable boundaries require
their own contract; `function<int(int)>` does not itself specify parameter names.
See [CH15-NAMED-ARGS-01](../catalog/15_blocking_debt.md#ch15-named-args-01--named-arguments-across-source-callables).

## Approved prerequisite and checkpoints

The existing source callable base combines named lookup, syntax and independently
scheduled signature/body work. Do not model a closure as a fake named function or
introduce a second preparation pipeline. The user approved a coordinated separation
across syntax/collection, analysis lifecycle and backend consumers.

1. **Authored signature composition — implemented.** `callable_signature_syntax`
   owns parameter nodes, optional return syntax and the explicit reference-return
   marker. Named functions/methods own one instance each. Parameter names remain on
   their original nodes; there is no duplicated name list. Parser, preparation,
   inspection and maintenance consume these same edges. Existing bodies, scopes,
   collected identities and work ownership are unchanged at this checkpoint.
2. **Anonymous identity and body lifecycle — implemented for the bounded foundation.** The syntax checkpoint
   shares signature/body/scope construction, inspection and neutral maintenance
   through `Callable_Syntax`, without moving stage processing into the AST.
   `closure_node` is an expression, not a declaration. It uses the common parameter
   grammar and `function_body_node`; its body scope has an explicit strong owner
   so enclosing-body reuse does not depend on a fresh nested-scope scan. Partial
   parses retain scopes through the existing parser failure inventory. No named
   callable is published; execution was gated until the vertical slice below.

   **Source identity implemented:** `collected_file::anonymous_callables` owns
   `anonymous_callable_identity` records separately from named entries. Each records
   its original expression, collection context and non-reused Storage position.
   Collection/backlinks are weak; syntax ownership is strong. Parser registration
   precedes body parsing, retaining partial occurrences for recovery. One retained
   token-range rule reconciles named occurrences and anonymous identities. Successful
   parse/join cleanup removes obsolete identities before releasing retired scopes.
   IDs are not C++ names or fresh-build-stable serialization keys. Replacing an
   enclosing body currently creates fresh anonymous identities; unchanged bodies
   preserve theirs without ordinal rematching.

   **Contract scheduler separated:** `contract_work` is the dependency-target base;
   named `declaration_work` is a specialization. Source-callable signatures use the
   contract base directly. Worker-owned queueing, demand, retries and retirement
   share this path; optional declaration lookup is not a scheduler requirement.
   Named lookup remains an adapter into direct contract demand. Existing type
   relationships, lifecycle and interface contracts use the same demand helper.

   **Anonymous work integrated:** source identities own signature contracts and
   their bodies own independent body work. First expression demand prepares both;
   retained work participates in the existing queues, dependency invalidation,
   failure/retry and join-ordered retirement. Work backlinks to anonymous identities
   are weak. Shared parameter admission, parameter binding and return-path checking
   serve named and anonymous callables. Enclosing-body cleanup stops at closure
   boundaries; each callable clears its own body facts. Equivalent signatures retain
   parameter fact identity. No fake declaration or name is published.

   **Value integration:** implemented by the vertical slice below.
   Closure syntax remains an expression. Reuse `function_body_node` and stage-owned
   workers; add no AST processing hooks or generic visitor.
3. **Capture-free vertical slice — implemented.** Each expression has a distinct
   `closure_type_definition`; `Source_Closure_Types` weakly associates its canonical
   type with the anonymous identity. Registry definitions contain no syntax/signature
   facts or C++ spelling. Retirement removes the association. Expression callees
   share `call_node`, argument grammar and prepared argument admission with named
   calls. C++ uses native capture-free lambdas and inferred `auto` storage, without
   `std::function`; the callee is evaluated once before ordered arguments. Explicit
   signatures, lexical scope and return paths are checked before emission.

   Anonymous body changes currently invalidate executable fragments at source-file
   granularity because lambdas render inline. This is conservative but correct;
   finer nested-fragment dependency tracking is a later optimization.

4. **Explicit callable boundaries — implemented.** `callable_type_node` preserves
   signature type syntax. Registry-owned `callable_type_definition` identities are
   interned by canonical return/ordered parameter types, including modifiers. Their
   contracts contain no source names, defaults or syntax. Preparation checks exact
   closure compatibility through the dependency-aware conversion query; C++ consumes
   a `callable_wrap` decision and emits `std::function` only at explicit boundaries.
   Initialized locals, copying/reassignment and by-value function/method parameters
   share existing admission and lowering. Callable returns, empty storage, reference
   signatures, properties and wrapper/container storage reject during preparation.

5. **Explicit value captures — implemented.** Creation-site preparation resolves
   capture references through the current local environment and requires ordinary
   copy capability. `anonymous_body_work::captures` owns independent read-only
   `prepared_capture` fields; it never retains the outer transient environment or
   turns captures into callable parameters. Rebuilding the enclosing expression
   supplies fresh inputs and rebuilds the anonymous body. Independent dependency
   rebuilds reuse the established inputs; retirement clears them. Shared-payload
   access is distinct from rebinding. The ordinary place/access checks protect
   inline and value-container storage; shared pointees/dynamic contents stay mutable.
   C++ uses value captures, with semantic read-only enforcement before emission.

Non-goals: reference/this captures, mutable capture bindings, arrows, escaping references, defaults/variadics and reference
returns on closures, named-call syntax, callable-return support,
dynamic/container storage and LLVM semantics. Mechanical adaptations to parked
LLVM's shared syntax accesses preserve its existing shape only.

## Validation boundary

The signature checkpoint must preserve AST child order, signature/parameter identity
through incremental parsing, existing functions/methods/constructors, bodyless
transitions, reference contracts, purity, failure/retry and emitted artifacts.
The anonymous checkpoint additionally needs insertion/removal and retirement proofs.
The execution checkpoint needs independent expected results and generated-program
execution. None of these alone proves a rebuilt native compiler; native rebuilding
remains explicitly requested work.

Signature checkpoint evidence (2026-10-08): 25 distinct focused PHP suites passed
across `/tmp/my-try-ch12-signature-model-01` and
`/tmp/my-try-ch12-signature-model-02`; the latter reran the new ownership/identity
assertions. `records.py --suite method_parameters` passed all 50 generated C++
programs with fixture PCH in `/tmp/my-try-ch12-signature-execution-01`.
The shared 12-slot runner used CLI fallback because FPM socket startup was denied.
No native compiler rebuild or new LLVM behavior was tested.

Anonymous syntax checkpoint: `closure_model.php` proves distinct expression/scoped
identities, original parameter names, child ordering, neutral maintenance, no-op
reuse, enclosing-body replacement, deleted parameter retirement, partial-parse
recovery and restoration of ordinary named execution after closure removal.
Syntax identity currently survives only when the enclosing body is reused; it is
not yet an independently reconciled anonymous work identity or a concrete value type.
Thirteen focused PHP suites passed in `/tmp/my-try-ch12-closure-model-01`;
`/tmp/my-try-ch12-closure-model-02` reran the expanded nested/top-level scope proof.
This checkpoint did not rebuild the native compiler or execute generated programs.

The source-identity checkpoint extends that proof with stable no-op IDs, distinct
same-signature occurrences, non-reused deleted slots, speculative-parse disposal,
repair and whole-source removal. Scheduler integration remains pending: the current
dependency graph at that checkpoint accepted only named `declaration_work`.
The contract-scheduler checkpoint removes that requirement without subclassing
a fake function. Anonymous work attachment was the following integration.
Nine focused PHP suites passed in `/tmp/my-try-ch12-anonymous-identity-01`; the
expanded deletion/rollover proof and parser/token cleanup regressions passed in
`/tmp/my-try-ch12-anonymous-identity-03`. These are host behavior and
artifact-generation checks, not native compiler or generated-program execution.

Contract-scheduler checkpoint: eight focused suites passed cleanly in
`/tmp/my-try-ch12-contract-work-02`, including work-role/direct-demand assertions,
failure/retry, retirement, callable/trait instances and incremental C++ artifacts.
The ninth suite (`s2s`) initially lacked its required output-directory argument:
assertions passed but artifact writes warned. Its corrected run passed in 13.35
seconds under `/tmp/my-try-ch12-contract-work-03`, with generated artifacts retained.
The shared runner used CLI fallback. No native compiler rebuild was performed.

Anonymous-work checkpoint: eight focused suites passed in
`/tmp/my-try-ch12-anonymous-work-03` (longest: `s2s`, 13.71 seconds).
The expanded whole-source retirement check passed in
`/tmp/my-try-ch12-anonymous-work-04`. Proofs cover independent settled work,
parameter fact preservation, enclosing-retry cleanup boundaries, callee-contract
invalidation/recovery, missing returns, deferred parameter rejection and retirement.
These are host PHP and artifact-generation checks, not native compiler rebuilding
or closure execution. CLI fallback was used after FPM socket startup was denied.

Executable checkpoint: eight generated C++ programs passed with PCH in
`/tmp/my-try-ch12-closure-native-programs-03`: local calls, copying, nested closures,
void returns, named-function bodies, immediate invocation, ordered arguments and
incremental regeneration after a called function's default argument changes.
The fixture also checks six preparation rejections, no-op output and clean versus
incremental agreement after a body edit. Eight focused compiler suites passed in
`/tmp/my-try-ch12-closure-validation-02`. This executes generated programs, not a
rebuilt native compiler.

Explicit-boundary proof: thirteen generated C++ programs passed with PCH in
`/tmp/my-try-ch12-callable-boundaries-05`, adding typed local replacement/copying,
function arguments, instance/static class methods, struct methods and void callable
contracts. Nine focused compiler suites passed in
`/tmp/my-try-ch12-callable-validation-01`; the expanded no-op type-count assertion
passed with `closures.php` in `...-validation-02`.
Exact interning and the expanded signature-arity rejection passed in
`/tmp/my-try-ch12-callable-validation-03`.

Legacy test debt: `tests/model.php` expects old startup counts (26/17/0 instead of
30/22/3) and excludes `structures_declarations.php` from its passive-data whitelist.
The prior committed registry/bootstrap and current versions produce identical
counts (`/tmp/my-try-ch12-callable-counts`). A diagnostic-only count correction
exposed the stale whitelist; the test was restored unchanged. It also exercises
parked LLVM and is not claimed passing. No native compiler rebuild was performed.

Value-capture proof (2026-10-08): 28 generated C++ programs passed with PCH in
`/tmp/my-try-ch12-captures-05`. They cover creation-time snapshots, closure copying,
nested/callable/parameter/alias captures, vector/mixed value semantics, shared-object
identity after outer rebinding, shared methods, dynamic writes/removal, vector returns
and explicit callable arguments. Preparation rejects duplicate/missing/colliding
captures, noncopyable values, captured-binding mutations and value-container writes.
Deferred reference/this capture failures recover on the same compiler instance.
Inspection, no-op versions, enclosing-signature edits, retirement and fresh/incremental
agreement are checked. Ten focused suites passed in `...capture-checks-01`; thirteen
passed in `/tmp/my-try-ch12-capture-checks-02`, including reference, property mutation,
indexing, cleanup and method-parameter regressions. The shared runner used CLI fallback.
These are host compiler checks and generated-program execution, not a native compiler rebuild.
