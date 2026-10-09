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

6. **Arrows and automatic value capture — implemented.** `closure_syntax_kind`
   distinguishes the two spellings on the existing concrete node. The parser
   normalizes the arrow expression into the ordinary callable body/return node.
   `Capture_Preparation` owns both capture admission paths; automatic discovery
   uses existing collected references and a transient scope-to-callable map.
   It treats signature/body scopes as one boundary, excludes parameters, propagates
   nested-arrow requirements and stops at explicit closure bodies. It does not
   rewrite source capture lists, attach outer bindings to inner references, add
   retained indexes or introduce an AST visitor. C++ consumes the body work's
   prepared capture fields for both spellings. Callable invocation is unchanged.

   Remaining shared-return debt: a void-valued return expression is rejected by the
   ordinary function return path, so void arrows are not independently enabled.
   Revisit this in that owner rather than special-casing arrow body emission.

7. **Parameter parity — implemented.** Anonymous signatures now use the ordinary
   parameter admission without an extra exact-arity/value-only gate. Defaults,
   typed variadic packing and writable/read-only reference inputs reuse shared
   callable contracts, call admission, storage access and C++ lowering unchanged.
   Erased callable conversion explicitly rejects defaults, variadics and reference
   signatures, preserving its current fixed required by-value contract.

8. **Bounded reference returns — implemented.** Reference preparation consumes shared
   signature syntax and prepared callable facts; parameter seeding establishes the
   same whole-parameter return obligation for named and anonymous bodies. Call
   admission substitutes caller storage without anonymous-specific lowering. Erased
   conversion rejects reference returns explicitly. The temporary cycle graph indexes
   callable targets and optional bodies, including anonymous identities; named virtual
   dispatch remains an adapter. Expression calls now register ordinary call occurrences
   without named lookup. This fixes missing graph edges and preserves lexical callable
   boundaries without adding a retained graph or capture escape analysis.

9. **Lexical lifetimes and bounded reference captures — implemented.** Shared local
   environments establish neutral region identities and storage order. Aliases retain
   their target origin; capture facts retain weak places and neutral lifetime snapshots.
   Anonymous invocations have independent roots and explicit captured-borrow guarantees,
   not artificial parentage under the creation environment. Shared destination checks
   preserve transitive obligations; erased callable boundaries reject borrowing values.

10. **Borrowed receiver captures — implemented.** Explicit `use ($this)` and automatic
    arrow discovery reuse the existing borrow origin and invocation-region checks.
    Anonymous bodies recover receiver/member access from the original signature-owned
    receiver. Native `[this]` captures do not copy or retain the instance. Local copies
    preserve obligations; erased conversion and `use (&$this)` reject. No retained
    receiver-context structure or separate closure lowering path was introduced.

11. **Explicit callable returns/factories — implemented.** Named/anonymous signature
    preparation no longer blanket-rejects callable results. Recursive callable type
    preparation admits callable results too. The existing return-value conversion,
    exact erased-signature admission and transitive borrow checks own all validation;
    C++ type spelling/conversion already supports nested `std::function` signatures.
    No new retained model, factory path or ownership exception is needed.

12. **Contextual signature completion — implemented.** Anonymous signature work retains
    an optional canonical expected-type input from creation-site preparation. Shared
    parameter/return preparation fills omissions without synthesizing AST types.
    Existing consumer paths supply typed storage, selected arguments and returns.
    Query-only demands preserve inputs; changed inputs reschedule signature work;
    retirement clears them. Exact conversion and borrow gates are unchanged.

Non-goals: owning receiver/captured-field captures, mutable by-value capture bindings, captured-storage reference
returns, erased reference signatures, named-call syntax, inferred callable returns,
dynamic/container storage and LLVM semantics. Mechanical adaptations to parked
LLVM's shared syntax accesses preserve its existing shape only.

## Validation boundary

Contextual checkpoint (2026-10-09): 12 generated C++ programs passed with PCH in
`/tmp/my-try-ch12-contextual-04`. Ten focused PHP suites passed in
`/tmp/my-try-ch12-contextual-checks`; final contextual/closure/reference-capture checks
are recorded in `/tmp/my-try-ch12-contextual-final`. Coverage includes partial types,
all four consumer sites, nested factories, incompatible/absent signatures, reference
and receiver rejection, unchanged AST omissions, stable anonymous identity on provider
edits, arity-error recovery, fresh/incremental agreement and retirement. Earlier
multi-file fixtures needed explicit entry selection and a float argument after the
provider changed to a float contract. No native compiler rebuild or conversion run.

Callable-return checkpoint (2026-10-09): all 83 generated C++ programs passed with
PCH in `/tmp/my-try-ch12-factories-02` (70 existing cases plus 12 factory cases and
one factory edit/recovery case). Seven focused PHP suites passed in
`/tmp/my-try-ch12-factory-checks`. Proofs cover function/class/struct/static/anonymous
factories, nested erased result signatures, exact forwarding, shared identity,
value-container snapshots, direct/transitive borrow and receiver escape rejection,
and fresh/incremental agreement after signature failure. No native compiler rebuild
or converter run was performed.

Borrowed receiver checkpoint (2026-10-09): 34 generated C++ programs passed with PCH
in `/tmp/my-try-ch12-receiver-05`, including nine receiver execution/edit cases and
the prior 25 reference-capture cases. Tests cover private/protected access, class/struct
identity, trait consumers, nested explicit/arrows, transitive erasure rejection,
signature edits, invalid-mode recovery and fresh/incremental agreement. Nine focused
PHP suites passed in `/tmp/my-try-ch12-receiver-checks-01`; four final suites (closures,
reference captures, storage lifetimes and trait instances) passed in
`/tmp/my-try-ch12-receiver-final-checks`. Earlier runs corrected two
fixture mistakes: an undemanded static method and unsupported typed-constant spelling.
No native compiler rebuild, converter run or LLVM extension was performed.

### Reference-capture prerequisite discovered — 2026-10-09

The user approved a bounded reference-capture slice: whole locals/parameters and
eligible aliases, inherited readonly access, checked local copies, transitive borrow
obligations through value-captured closures, and rejection at erased callable
boundaries. Arrows stay by-value; captured fields, `$this`, callable returns and
captured-storage reference returns are not enabled by this agreement.

**Historical blocker, now resolved by the approved prerequisite.** Collected `scope` records describe
callable/name lookup boundaries, not every executable block's storage lifetime.
`Body_Preparation::prepare_block` creates a transient `local_environment`; the parser
does not create a matching collected scope for an ordinary `block_node`. Consequently,
testing only collected-scope ancestry and declaration position is not a truthful
lexical lifetime proof. A direct checker test accepted a hypothetical destination
outside the block owning the borrowed local:

```php
if (true) {
    $n = 3;
    $f = function () use (&$n): int { return $n; };
    $g = $f; // valid local copy
}
$later = 0; // this outer storage must not be an eligible destination for f's type
```

This was a model-level counterexample, not a demonstrated source-level runtime
escape: existing lexical lookup and erased-conversion gates restrict how such a
destination could be expressed. Those gates must not substitute for the approved
lifetime proof. Incremental testing also showed that reparsed signatures and reused
bodies can carry token indexes from different generations; physical token order
across those boundaries is not lifetime order.

**Approved and implemented prerequisite:** give shared semantic storage preparation
an explicit lexical lifetime-region identity, independent of collected name scopes.
Transient local membership identifies region and establishment order; aliases preserve
their target origin. Structured-body/loop/switch preparation supplies the appropriate
region, and parameters use their callable invocation lifetime. Captures retain only
neutral lifetime/origin facts, never transient environments or workers. Copy admission
and erased-boundary checks consume the same borrowing obligations. Rebuild/retirement
refresh those facts and prevent stale origin observers. Regions retain only upward
parent links, never storage, syntax, environments or workers. Parameter signature facts
remain invocation-independent. Captured-borrow guarantees permit invocation-local
copies but cannot authorize writes into external longer-lived destinations.

This touches shared local/storage preparation, structured environments, anonymous
work lifecycle and copy boundaries, rather than just capture parsing/lowering.
It unlocks checked reference captures without pretending name scopes are lifetimes.
Validation must cover nested and sibling blocks, loop iterations/header storage,
switch arms, parameter origins, aliases, transitive captures, no-op reuse, signature
edits, failure recovery and retirement. No general escape analysis, heap promotion,
exclusive borrowing, escaping callbacks or new callable storage forms are proposed.

The original incomplete implementation was removed before the prerequisite was approved.
The first experimental execution run passed 85 generated
programs (`/tmp/my-try-ch12-reference-captures-01`) and 12 focused suites passed
(`/tmp/my-try-ch12-reference-capture-checks-01`), but those results do not prove the
missing lifetime model and are not claims of shipped support. Expanded runs `...-02`
and `...-03` exposed the incremental-order issue and the block-lifetime counterexample.
No native compiler rebuild was performed.

Approved implementation evidence (2026-10-09): 25 generated C++ programs passed with
PCH in `/tmp/my-try-ch12-region-captures-02`; all 70 existing closure programs passed
in `/tmp/my-try-ch12-region-closure-regression-01`. Fourteen focused suites passed in
`/tmp/my-try-ch12-region-checks-01`; six final suites passed in
`/tmp/my-try-ch12-region-final-checks`, including the exact same-name-scope/different-
storage-region counterexample, signature recovery, mode edits and retirement.
The earlier foundation run used two nonexistent suite names; the corrected run uses
`control_flow`. Capture run `...-01` rejected an order-dependent test expression;
the fixture was rewritten to use an explicit temporary, preserving the language rule.
These are PHP compiler and generated-program proofs, not a native compiler rebuild.

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

Arrow proof (2026-10-09): 43 generated C++ programs passed with PCH in
`/tmp/my-try-ch12-arrows-04`, including nested propagation, parameter shadowing,
explicit-closure boundaries, repeated names, captured callable invocation, expression
precedence, value snapshots, shared mutation and explicit callable conversion.
Signature and capture-set edits agree with fresh compilation. Missing signatures,
explicit arrow use lists and unsupported captures reject and recover on the same
compiler instance. Nine focused compiler suites passed in
`/tmp/my-try-ch12-arrow-checks-01`; that run's closure fixture initially assumed
void-expression returns, and was corrected to assert the existing shared rejection.
The final `closures`, `closure_model`, `ast` and `token_cleanup` suites passed in
`/tmp/my-try-ch12-arrow-checks-final`; all 43 regenerated C++ artifacts are byte-identical
to the executed ones. The shared runner used its expected FPM-to-CLI fallback.
No native compiler rebuild or LLVM semantic change was performed.

Parameter-parity proof (2026-10-09): 59 generated C++ programs passed with PCH in
`/tmp/my-try-ch12-parameters-03`. New cases cover omitted/overridden/copied defaults,
reference aliases, readonly observation of caller mutation, value/reference snapshots,
container references, empty/nonempty variadic packs, ordered reference-plus-pack calls
and arrows using the same contracts. Capturing a reference parameter remains a value
snapshot. A default edit agrees with fresh compilation and executes the new value.
Preparation rejects erased extended signatures, invalid reference arguments, readonly
mutation and the same parameter-order/default/variadic violations as named functions.
Ten focused suites passed in `/tmp/my-try-ch12-parameter-checks-01`, including method
parameters, references, anonymous lifecycle/recovery and incremental C++. No native
compiler rebuild was performed; the shared runner used CLI fallback.

Reference-return proof (2026-10-09): 70 generated C++ programs passed with PCH in
`/tmp/my-try-ch12-reference-returns-02`. New cases cover aliases, copied closures,
copy-versus-borrow results, immediate temporary closures, captures used independently
of the returned parameter, bidirectional named/anonymous forwarding, defaults,
variadics and vector copy semantics. Preparation rejects captured/local/interior
escapes, unsupported signatures and invalid arguments. Cycle rejection includes
anonymous bodies and by-value helpers, while uninvoked closure creation adds no call
edge. Signature-marker edits and cycle repairs recover on the same compiler instance.
The initial run caught missing expression-call inventory entries; parser collection
now records them through the existing call-occurrence path. Thirteen focused suites
passed in `/tmp/my-try-ch12-reference-checks-02`, including named/method reference
returns, forwarding, parser retention, cleanup and incremental artifacts. An earlier
check used the nonexistent suite name `method_references`; the corrected run uses
`method_returns`. The shared runner used CLI fallback; no native compiler rebuild.
