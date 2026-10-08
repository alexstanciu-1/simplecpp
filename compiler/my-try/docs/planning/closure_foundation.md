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
2. **Anonymous identity and body lifecycle — in progress.** The syntax checkpoint
   shares signature/body/scope construction, inspection and neutral maintenance
   through `Callable_Syntax`, without moving stage processing into the AST.
   `closure_node` is an expression, not a declaration. It uses the common parameter
   grammar and `function_body_node`; its body scope has an explicit strong owner
   so enclosing-body reuse does not depend on a fresh nested-scope scan. Partial
   parses retain scopes through the existing parser failure inventory. No named
   callable is published and analysis explicitly rejects execution for now.

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

   **Still pending:** connect anonymous occurrences to shared signature/body
   preparation, semantic selection, invalidation and retirement.
   Closure syntax remains an expression. Reuse `function_body_node` and stage-owned
   workers; add no AST processing hooks or generic visitor.
3. **Capture-free vertical slice — pending.** Register concrete expression types,
   prepare local storage/invocation through shared call admission, and lower direct
   C++ lambdas without implicit `std::function`. Check explicit signatures, lexical
   scope and all return paths before emission.

Non-goals: captures/arrows, escaping references, defaults/variadics and reference
returns on closures, named-call syntax, callable-boundary conversion/type erasure,
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
a fake function. Anonymous work attachment remains the next integration.
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
