# JSS Policy Backlog
Doc Status: planning

Purpose: collect policy-sensitive JSS conversion questions that should be answered before adding more syntax or converting strict runtime-heavy samples.

This note is local to the manual sample queue. It is not semantic authority; it exists to keep future JSS additions explicit and merge-friendly.

## References

Status: first narrow slice implemented

JSS should expose references only through an explicit narrow form that mirrors the current PHS reference model. There should be no hidden aliasing through ordinary assignment and no invented `ref` keyword; `ref` is not JavaScript syntax and would create an extra JSS-only concept.

Preferred constraint:

- use `&` / ampersand in the same spirit as current PHS references
- no hidden aliasing through ordinary assignment
- any supported reference form must map directly to the documented reduced Prism++ native-reference subset
- no broad JavaScript object-reference mental model should be inferred from this feature

Blocked samples:

- none in the current level-01 reference queue

Implemented first slice:

- `let alias = &value;`
- `alias = &value;`
- reference targets must be simple identifiers

## Result Wrappers

Status: first useful lanes resolved; broader policy still constrained

Strict filesystem, IO, curl, and error-path examples rely on wrapper/result flow. JSS needs an explicit spelling for success checks, error capture, and value extraction before those examples are converted.

Preferred constraint:

- no JavaScript truthiness for result objects
- no hidden `false`/`null` collapse
- extraction such as `take(...)` must remain visible in the emitted PHS
- failure branches should be explicit boolean comparisons or named helper calls

Blocked samples:

- legacy curl samples remain blocked because they use legacy PHP curl behavior, not the strict typed curl surface

Current note:

- real JSS `fs/json/take(...)`, `fs/io/string`, strict wrapper error-path, regex, and strict curl lanes now have sample coverage plus dedicated project build/run validation
- `take(...)` remains the explicit result-wrapper spelling; do not add JavaScript truthiness for result objects

## Ternary

Status: partly resolved first slice implemented

JSS may eventually support `condition ? when_true : when_false`, but strict control flow should not inherit JavaScript truthiness.

Preferred constraint:

- conditions must resolve to explicit `bool`
- nested ternaries are rejected in the first slice
- ordinary `if` blocks remain acceptable for samples where ternary support is not essential

Implemented first slice:

- direct `cond ? a : b` expression support
- condition must validate as `bool`
- branches must currently resolve to the same type or a `T` / `null` pair
- emitter lowers directly to PHS ternary syntax

Remaining follow-up:

- decide whether nested ternaries should remain blocked for readability or gain a typed right-associative rule
- decide how much STAN should own result-type unification beyond the current same-type / `T`-or-`null` slice
- decide whether explicit `mixed` / `dynamic` ternary branches need a boundary helper or can stay direct

Blocked samples:

- `docs/examples/php/strict/project_samples/strict_error_paths/main.phs`
- any strict runtime sample whose PHS source uses ternary fallback text

## Null Coalescing

Status: partly resolved first slice implemented

JSS may eventually support `value ?? fallback`, but it should map to strict nullable rules rather than JavaScript truthiness or broad falsy behavior.

Preferred constraint:

- only `null` should trigger the fallback
- first slice now covers only a single `lhs ?? rhs` site
- left operands are limited to explicit nullable types or explicit `mixed` / `dynamic` boundaries in the initial subset
- operands should be type-compatible with an explicit result type
- chains such as `a ?? b ?? c` still wait until single-site behavior is documented, type-checked, and tested
- explicit `if (value === null)` remains the preferred sample spelling until the policy is settled

Implemented first slice:

- parse `??` as a dedicated binary operator
- emit direct PHS `??`
- keep chain support blocked
- require a nullable or explicit `mixed` / `dynamic` left operand
- require fallback compatibility for the narrow typed-nullable subset

Remaining follow-up:

- decide whether chain support is worth adding or whether explicit nested temporaries are preferable
- decide how much STAN should own result-type unification once `??` moves beyond the first nullable/mixed slice
- decide whether `??` should accept broader nullable unions before `undefined`/absence design is settled

Blocked samples:

- `tests/php/types/nullable/level_02/nullable_003_null_coalesce_basic.phs`
- `tests/php/types/nullable/level_02/nullable_004_mixed_null_coalesce_basic.phs`
- `tests/php/types/nullable/level_02/nullable_005_mixed_null_coalesce_chain.phs`

## Runtime Modules

Status: direction partly implemented

JSS project samples need a policy for runtime module requirements before converting curl, filesystem, IO, regex, or similar module-dependent examples.

Working proposal:

- see `specs/planning/jss_module_namespace_proposal_2026_06_12.md`
- modules are project-selected capabilities, not source-level JS imports
- module surfaces should be reached through reserved helper families such as `fs.get(...)`, `json.decode(...)`, `io.open(...)`, and `dt.format(...)`
- safe current expansion should prefer helper-family coverage whose runtime contract is already known, rather than adding new frontend-local semantics

Preferred constraint:

- JSS source should target strict runtime APIs, not legacy PHP wrapper behavior
- sample metadata or project config should identify required modules
- emitted PHS should preserve the same runtime-module dependency surface as equivalent strict PHS

Blocked samples:

- `tests/php/curl/level_01/curl_001_legacy_file_surface.phs`
- `tests/php/curl/level_01/curl_002_legacy_http_get.phs`
- strict runtime-heavy samples have first useful-project coverage; legacy module samples remain blocked unless explicitly mapped to strict typed helper contracts

## Append And Mutation

Status: partly resolved

Vector/hash append and update syntax should be decided before converting samples that build arrays, headers, or accumulator maps.

Preferred constraint:

- use a spelling that maps cleanly to supported strict PHS operations
- avoid PHP-only magic append behavior unless it is explicitly part of the JSS surface
- keep key update, value append, and object/hash literal construction distinct

Candidate spellings to evaluate:

- `items.push(value)` for vector append: accepted for statement-form vector append and lowers to `$items[] = value;`
- `items[key] = value` for keyed hash update: accepted for existing index/keyed target shapes and lowers directly to PHS index assignment
- no `items[] = value` spelling unless a direct JSS policy says so

Remaining open edges:

- `delete expr` in JSS lowers to PHS `unset(expr)` for explicit keyed/member targets; this is not full JavaScript `delete` semantics
- nested append/update policy beyond direct index/keyed assignment
- mutation of dynamic/mixed carriers

## Arrow Functions

Status: first narrow slice implemented

JSS is typed, so arrow functions may be supported only where the callable shape is explicit and maps to the current PHS callable/lambda model.

Allowed direction:

- local explicit lambda shape such as `let f = (x: int): int => x + 1;`
- parameter types required
- return type required
- expression-body first slice
- no broad JavaScript inference/capture promise beyond what PHS already supports

Constraint:

- lowering arrows into ad hoc generated functions would create a second callable path and should be avoided

Implemented first slice:

- local explicit expression-body arrow values such as `let f = (x: int): int => x + 1;`
- parameter and return types are required
- invocation of local arrow variables lowers as ordinary local callable invocation

Still pending:

- block-body lambdas
- explicit capture policy beyond the existing PHS closure/arrow behavior
- callable container/storage surfaces beyond local concrete values

## Late Static

Status: narrow method/constant access implemented; broader forms pending

JSS may expose the PHS late-static spelling directly where needed:

```js
static::make()
static::VALUE
```

This is not JS-looking, but it avoids inventing an unclear JavaScript-shaped replacement for an advanced class feature. Users who dislike the spelling can avoid the feature.

Still pending:

- `static::$prop`
- `new static(...)`
- inherited static method classification, such as calling `B.run()` when `run()` is inherited from `A`
- broader validation around using `static::` outside class methods

## Optional Chaining

Status: first narrow slice implemented

JSS optional chaining should return `null` on a failed chain, not `undefined`.

Preferred constraint:

- reuse or adapt the existing guarded-path / `isset(...)` machinery where possible
- do not make optional chaining depend on future `undefined` semantics
- keep broad type/narrowing truth in STAN/PHS rather than in JSS-local heuristics

Implemented first slice:

- `object?.member` lowers to PHS nullsafe `?->`
- failed chains return `null`

Still pending:

- project build/run validation for non-nullable scalar member results; current downstream PHS nullsafe lowering can still hit a `T` versus `null_t` ternary mismatch
- optional method-call project coverage
- chained optional paths
- optional indexing policy
