# JSS Policy Backlog
Doc Status: planning

Purpose: collect policy-sensitive JSS conversion questions that should be answered before adding more syntax or converting strict runtime-heavy samples.

This note is local to the manual sample queue. It is not semantic authority; it exists to keep future JSS additions explicit and merge-friendly.

## References

Status: open

JSS should not copy PHP reference assignment syntax by accident. Before converting reference samples, decide whether JSS exposes any reference aliasing syntax at all.

Preferred constraint:

- no `=&`-style spelling in JSS
- no hidden aliasing through ordinary assignment
- any supported reference form must map directly to the documented reduced Prism++ native-reference subset

Blocked samples:

- `tests/php/references/level_01/references_001_local_reference_basic.phs`
- `tests/php/references/level_01/references_002_reference_assignment_basic.phs`

## Result Wrappers

Status: open

Strict filesystem, IO, curl, and error-path examples rely on wrapper/result flow. JSS needs an explicit spelling for success checks, error capture, and value extraction before those examples are converted.

Preferred constraint:

- no JavaScript truthiness for result objects
- no hidden `false`/`null` collapse
- extraction such as `take(...)` must remain visible in the emitted PHS
- failure branches should be explicit boolean comparisons or named helper calls

Blocked samples:

- `docs/examples/php/strict/project_samples/strict_curl/main.phs`
- `docs/examples/php/strict/project_samples/strict_str_io/main.phs`
- `docs/examples/php/strict/project_samples/strict_error_paths/main.phs`

Current note:

- the first real JSS `fs/json/take(...)` lane is now covered by `samples/jss/json/strict_level_01/strict_json_005_fs_take_roundtrip_basic.jss` plus dedicated project build/run validation
- broader wrapper/result policy remains open beyond that narrow proven lane

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

Status: open

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
- strict curl/filesystem/IO project samples

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

- unset/delete syntax
- nested append/update policy beyond direct index/keyed assignment
- mutation of dynamic/mixed carriers

## Arrow Functions

Status: blocked

JSS should not add ES6 arrow functions until PHS callable/closure lowering has a stable supported target. Lowering arrows into ad hoc generated functions would create a second callable path and should be avoided for now.
