# Name resolution
Doc Status: supporting

Status: implemented in the PHP prototype. Inspection stops before native building with `completed: false` and `stopped_before: "build_native"`.
[Logical definition/child comparison](symbol_comparison.md) now follows name
resolution before the compiler reports that stopping point.
[Declared return-type resolution](return_type_resolution.md) now follows the frontend stages.
[Declared body checking](body_checking.md) follows signatures and separately
tracks dependencies on callee contracts.

Declaration annotations, template definitions/parameters and constants now share
this process; see [template and declaration bindings](template_bindings.md) for
their identity domains, selection dependencies and current semantic limits.

## Work and ownership

`Symbol_Resolver::select()` reads the complete candidate `Symbol_Store` and the
previous `Resolution_Set` and fixed provider catalog. It selects missing/stale
source-owner results, or all source owners when `full_rebuild` is set. Tasks reference existing symbol
records without allocating another work record. Collection no longer owns a
resolution work list or exports pending resolution flags.

Each `Resolution_Worker` reads fixed project/catalog inputs and one owner's AST.
It binds declaration annotations and template parameter slots, then walks any
body with an explicit block cursor stack. Calls bind through `Function_Lookup`.
The body root's sibling is excluded: an entry block must not walk into the file's
neighboring definitions. Both entry bodies and named functions use this path.
There is no dependency on resolving or executing the callee's body first, so
forward calls, self recursion and mutual recursion work through ordinary lookup.

The current resolver supports unqualified project function calls
with exact identifier spelling. Each binding stores the callee's name-node ID
and target symbol ID. A `Symbol_Resolution` owns the source-owner ID, exact AST
reference, immutable flat binding list and an index into that list. Consumers use
`target_for(use_node_id)`; body checking does not build its own lookup. Workers
construct private binding rows and deliver a complete result. `Resolution_Set` owns project lookup by source-owner
ID and shares unchanged result objects. No target AST or symbol data is copied
into a binding. Unknown calls produce source-anchored diagnostics, including
calls in functions that are never called. Type names bind to declarations here;
concrete representation and operation validity remain with type/body checking.
Imported runtime callables participate through the project index. Methods and
overloads remain unsupported.

The parser preserves typed parameters and call arguments. Parameter names now
bind before the function body; names inside argument expressions use the same
call and local binding paths. The type stage resolves their declarations;
parameterized functions pass body checking, scalar lifetime analysis and native
lowering, including unused parameters. No empty signature is fabricated.

## Organization and extension points

```text
resolve_symbols/
    data/
        structures.php
        declarations.php
        store.php
        result.php
    handlers/
        declarations.php
        names.php
        statements.php
        expressions.php
    utilities/
        function_lookup.php
        declaration_lookup.php
        resolution_validity.php
        binding_coverage.php
    main_resolve_symbols.php
    body.php
    join.php
```

The [shared folder conventions](../code_organization.md#controlled-feature-extension-in-the-php-prototype)
preserve the `resolve_symbols` namespace and process ownership. `Symbol_Resolver`
keeps selection and worker entry points. Its `finalize()` delegates to
[Resolution_Join](../../src/04_analyze/resolve_symbols/join.php),
which validates batches and assembles current bindings. Selection and joining
share `Resolution_Validity` for retained-binding checks. `Resolution_Worker`
keeps its constructor/run entry, scope/local/binding arrays, active name maps
and shared declaration, lookup, binding and diagnostic operations.

The worker explicitly composes four private method groups:

- [Name_Resolution](../../src/04_analyze/resolve_symbols/handlers/names.php)
  binds annotations, template parameters and scoped constants without preparing
  concrete types or evaluating expressions.
- [Declaration_Resolution](../../src/04_analyze/resolve_symbols/handlers/declarations.php)
  binds parameters in root order and registers body locals before resolving
  their initializers with the self-read guard.
- [Statement_Resolution](../../src/04_analyze/resolve_symbols/handlers/statements.php)
  owns the explicit scope-cursor traversal, statement dispatch, assignment,
  return/expression and control-statement handlers. A handler returns a
  `scope_cursor` for further traversal or null when its work is complete.
- [Expression_Resolution](../../src/04_analyze/resolve_symbols/handlers/expressions.php)
  owns the explicit operand/argument traversal, node dispatch and call binding.
  A node handler binds its own names and schedules operands with type/value roles.
  The driver visits children in order, excluding the root's siblings, and carries
  the same scope and initializer guard throughout.

The statement driver retires an active name map only when its cursor owns that
scope. Control statements resolve their condition first, then schedule their
body/alternative sibling blocks with a non-owning cursor. Entering each actual
block creates its own scope; completing those blocks must not retire their
enclosing scope. Both branches are visited regardless of the condition's value.
The traversal creates no execution plan or new reachability rules.

[Function_Lookup](../../src/04_analyze/resolve_symbols/utilities/function_lookup.php)
owns the stateless lookup shared by call binding and retained-binding checks.
It reads exact callee spelling from the owner's AST/source snapshot and looks
up the function in the owner's namespace through `Symbol_Store`. Zero means
missing; the worker owns the source diagnostic and the reuse check treats a
changed/missing mapping as stale. The former internal
`Symbol_Resolver::find_function()` helper is replaced by this shared utility.
The phase owns selection/execution, and a separate join accepts each fixed batch.

| Feature obligation | Owned extension point |
|---|---|
| Statement names and nested-scope traversal | `Statement_Resolution::statement()` and its handler |
| Parameter/local declaration visibility | `Declaration_Resolution` handlers and the worker's `declare_local()` |
| Expression names and child traversal | `Expression_Resolution::expression_node()` and its handler |
| Direct project-call lookup | `Function_Lookup::find()`, shared by execution and reuse |
| Nearest local binding and self-read checks | Worker-owned `find_local()` and `bind_local()` |
| Result selection, validity and replacement | `Symbol_Resolver`, shared `Resolution_Validity`, and `Resolution_Join` |

Declaration binding adds separate parameter/reference rows alongside the existing
local/call tables. It keeps iterative traversal and private worker state. Later
concrete type, body and lifetime processes retain their own responsibilities.

## Locals and block scopes

Agreed initial language rules for explicitly declared locals:

- Each callable body and nested block introduces a lexical scope.
- A local must be declared before a read or assignment. Assignment alone does
  not declare it. Names are case-sensitive; lookup uses the nearest enclosing
  declaration. Locals never cross callable boundaries, including file entries.
- Inner blocks may shadow outer locals. Same-block redeclaration is an error;
  sibling blocks can declare the same name independently.
- A declaration hides an outer namesake in its own initializer. Reading itself
  there is an error, including when an outer local has the same name.
- Leaving a block restores the enclosing lookup. This stage checks names even
  after a return; reachability and lifetime rules belong to later stages.

These explicit-declaration rules define this prototype slice; upstream examples
of inferred assignment do not establish the rules for it. Inferred declarations,
closures and definite-initialization flow analysis remain deferred.

`Symbol_Resolution` also owns three immutable linear datasets:

| Record | Facts |
|---|---|
| `lexical_scope` | Block node ID and parent scope ID; parent zero means the callable root. |
| `local_record` | Parameter or body-local declaration node ID and owning scope ID. Kind/name/type syntax stays in the AST. |
| `local_binding` | Variable-use node ID, local ID and read/write role. Declarations are not uses. |

Scope/local IDs are one-based positions **within this callable result**, never
project symbol IDs or identities across reparses. Consumers use `scope_for`,
`scope_for_block`, `local_for`, `local_for_declaration`, `parameter_for` and `binding_for`; call targets still use
`target_for`. Accessors return existing records. Binding rows follow source
traversal; their read/write roles do not specify execution order or storage.
Only worker scratch state indexes names per active scope; it does not copy an
entire visible-name map on block entry, and it discards indexes on block exit.
Local names/types are not copied into project symbols or a second AST.

Calls in initializers and assignment values use the same project lookup as
returned or standalone calls. The result accepts a local annotation's syntax
without asserting that the type exists or that the initializer fits it.
[Declared local type resolution](local_type_resolution.md) now checks those
annotations and prepares their shared IDs before body checking.
Body checking now checks initializers, reads, assignments and nested blocks.
Scalar local lifetime analysis and storage lowering now carry these results
through LLVM emission and native execution.

## Parameter bindings

Formal parameters occupy the function body's root scope, in declaration order,
before any body statement is visited. Their names are available throughout that
body, including for assignment. Same-root local redeclaration and duplicate
parameter names use the common duplicate-name error. Nested locals can shadow
parameters; a shadowing local still cannot read itself in its initializer.
Parameter bindings never leak into sibling functions or file entries.

Parameters form the ordered prefix of `Symbol_Resolution.locals`;
`parameter_count` marks its length. A one-based parameter position is also its
callable-local ID. `parameter_for(position)` returns the existing `local_record`.
There is no separate parameter binding dataset, copied name/type, or fabricated
initializer statement. The AST declaration distinguishes parameters from body
locals; later typing and lifetime stages must respect entry initialization.

Name binding alone establishes neither parameter types nor passing/copy rules.
The type stage now resolves declared parameter types. The ordinary pipeline
checks and analyzes scalar parameterized bodies and arguments, then prepares
backend contracts and lowers them through LLVM to native execution. Unsupported
conversions and richer passing contracts still fail before publication.

## Argument-expression names

Each call binds its callee, then visits its argument expressions in source order,
including nested calls and local/parameter reads. This is name traversal, not an
execution plan: arity, types, conversions and runtime evaluation order remain
later responsibilities. Literal arguments need no name binding.

An explicit cursor stack follows AST sibling links with one cursor per nesting
level. It copies neither argument lists nor AST nodes. Reads retain the current
lexical scope and initializer guard throughout nesting, so passing a local in
its own initializer is still an error. Nested callees enter the existing call
binding table; its reuse check already tracks those targets without another
dependency dataset. Workers, joins and replacement boundaries are unchanged.

## Refresh and validation

A previous result is reusable only when its owner ID and exact AST still match
and every recorded call name still maps to the same target ID in the candidate
index. Local bindings depend entirely on the exact immutable owner AST; all
their scopes and identities are replaced together when it changes. This rechecks
project lookup dependencies without walking an unchanged body.
A renamed/removed target therefore selects an unchanged caller for resolution;
its missing function is reported normally. A callee's body or annotation change
can leave the caller's name bindings valid, but says nothing about later type,
ABI, lifetime or generated-code reuse. Broader incremental impact rules remain
unimplemented. These are binding-validity rules; the coordinator's shared gate
still selects full work for declaration changes in both inspection and native
requests, overriding otherwise reusable results.

The serial phase runner selects the entire task batch before executing workers.
The coordinator join checks task/result membership, duplicate owners, exact AST
identity and current target mappings. Missing or stale results reject the phase.
It uses current symbol membership, withdrawing results owned by deleted symbols,
and assembles results in candidate symbol order regardless of completion order.
These are the same interfaces future workers will use; no threads run yet.

The session replaces its complete `observed` snapshot only after all requested
stages succeed. Errors preserve all previous observations and leave the
published generation untouched. The session retains current results only;
returned change catalogs may retain old AST origins until their readers finish.
`--debug=json` adds `resolutions`, listing owner/file IDs and actual use/target
bindings, plus `scopes`, `locals` and `local_bindings`. Without the flag, no
exporter runs. Stage results can also be inspected directly; successful native
compilation requires all stages and publication to finish.

## Verification

[Resolution checks](../../tests/04_analyze/resolve_symbols/symbol_resolution.php) cover the real
three-file call chain, empty/entry/function boundaries, recursion and forward
calls, deterministic independent workers, object reuse, stale-result rejection,
unknown-name rollback, unchanged callers after target removal/rename, deleted
result retirement, comparison with fresh-build bindings, and 5,000-call bodies.
[Local resolution checks](../../tests/04_analyze/resolve_symbols/local_resolution.php) cover
scope identity/lookup, read/write roles, shadowing, duplicate/unknown/self-read
diagnostics, independent reversed workers, joins, reuse, full-selection
equivalence, AST replacement, initializer call dependencies, exports, large
local tables and deep blocks, failure/repair and the body-checking boundary.
The existing suite also checks collection, source/parse reuse, CLI exports,
project exclusion and simulation restoration after failures.

[Parameter binding proof](../../tests/04_analyze/resolve_symbols/parameter_binding.php) covers
root-scope reads/writes, shared local identities, shadowing, duplicates,
self-initializer and cross-callable errors, ordinal lookup, wide lists, exports,
fixed workers and joins, warm/full/fresh reuse, reordering, removal and repair.

[Argument resolution proof](../../tests/04_analyze/resolve_symbols/argument_resolution.php) covers
nested cross-file callees, local/parameter reads, scope and initializer errors,
nested-target invalidation of an unchanged caller, reversed independent workers,
stale/incomplete joins, unchanged reuse, full/fresh equivalence, exports, deep and
wide argument lists, downstream validation and failure followed by native repair.

The handler reorganization also passed 110 before/after callable comparisons
against the original worker: named functions and file entries, branch/scope
ordering, initializer guards, deep/wide arguments, exact bindings and exports,
first diagnostic messages/spans and unchanged retained inputs. This was a
temporary equivalence probe, not a second compiler path or a timing benchmark.
The full prototype suite passed after the reorganization, including native
execution, incremental updates, failure/repair and worker isolation checks.
