# Template definitions and declaration bindings
Doc Status: supporting

## Implemented boundary

Collection and name resolution now retain template definitions, ordered parameter
scopes and occurrence bindings against the original source AST. Ordinary source
annotations use this same boundary. Concrete type preparation follows accepted
bindings; it no longer decides what a source type spelling refers to.

This stage binds definitions; [instance preparation](explicit_instantiation.md)
now consumes its outputs to prepare demanded concrete work and literal constants.
Neither stage implements general constant evaluation, a list or provider families. The agreed
[specialization model](metaprogramming_parsing.md#agreed-specialization-and-identity-model)
remains separate concrete instances per demanded argument set, with possible code
sharing through optimization in future versions.

## Owners and flow

The [generic parameter contract](generic_type_contract.md) adds an implemented
definition-permission boundary and mandatory copy/assignment/cleanup baseline for
bare `<T>`. Binding records the contract; `check_templates` checks permissions and
instantiation validates argument capabilities. The descriptions below cover name
binding; concrete type knowledge does not grant additional generic operations.

| Owner | Input and result |
|---|---|
| [Collection](../../src/04_analyze/collect_symbols/handlers/declarations.php) | Collect ordinary and template structs/functions, project constants and entries. The existing session symbol allocator owns definition IDs. Template wrappers and body syntax remain source references. |
| [Declaration lookup](../../src/04_analyze/resolve_symbols/utilities/declaration_lookup.php) | Resolve a type, type-family or constant name against fixed project declarations and the provider catalog, before layouts or canonical types exist. |
| [Resolution worker](../../src/04_analyze/resolve_symbols/body.php) | One source owner per selected task; bind annotations, parameter slots, calls, locals and constants into private output. |
| [Binding result](../../src/04_analyze/resolve_symbols/data/result.php) | Retain occurrence references, ordered template parameters, block scopes, runtime locals, constants and application dependencies under one owner and exact syntax snapshot. |
| [Resolution join](../../src/04_analyze/resolve_symbols/join.php) | Validate selected results, dependency currency and structural coverage; accept in any order, retain valid unchanged results and remove deleted owners. |
| [Concrete annotations](../../src/04_analyze/resolve_types/utilities/annotation_types.php) | Follow accepted source/provider declaration references into prepared definitions. Representation, source passing restrictions, layout and canonical type allocation remain with the existing type process. |

The coordinator passes the already loaded catalog to name resolution. Source
records, function signatures, local annotations and construction all consume the
accepted binding set. Provider callable signatures continue to consume their
normalized metadata contracts. No family or type spelling selects compiler logic.

`Symbol_Store::resolution_records()` includes source declarations and entries.
`body_records()` selects executable bodies: a template function can retain a
body without being an executable callable. Templates never enter concrete record,
signature, body, lifetime or backend preparation through ordinary selection.

## Identity and storage

- A definition has an allocated project symbol ID, retained by the existing symbol
  store across supported replacements. Definition identity is not a concrete type ID.
- A template parameter is identified by its owner definition ID and zero-based
  position. Its declaration/name/type syntax IDs belong to that owner's AST.
- Runtime parameters remain the one-based prefix of the existing local table;
  template parameters do not acquire storage slots.
- A project constant reference uses its symbol ID. A block constant uses its
  declaration node ID within the exact owner snapshot and records its lexical scope.
- A provider type/record reference retains the authoritative catalog declaration.
  Equal spelling in another catalog does not authorize reuse of the old reference.
- A template application retains its target definition and ordered source argument
  syntax. This records a formal-parameter dependency, not an instance identity.

Bindings use compact tagged rows and existing source references. ASTs are neither
copied nor edited. The PHP prototype uses objects/arrays; native representation
should use typed rows and references under the repository's existing porting rules.
There is no global mutable registry, hash-derived entity identity or per-instance
cache hidden in this slice.

## Name rules and diagnostics

Type/value roles come from the declaration or the target family's ordered formal
parameters. Arity is general: one, two or more parameters use the same algorithm.
Earlier parameters are visible in later value-parameter type annotations, as in
`template<typename T, T N>`. Forward project declarations and recursive family
references bind without requiring layouts or instantiation.

Template parameters cannot duplicate each other or the owner name. A field,
runtime local/parameter or local constant cannot redeclare a template parameter.
Runtime variables and plain-name constants otherwise keep their distinct syntax
and datasets. Constants obey block visibility and declaration order; self-reads
are rejected. Initializers and both compile-time conditional branches remain
unevaluated syntax. Known free names must resolve even inside an unselected-looking
`if constexpr` branch: this phase does not decide which branch is selected.

Explicit template calls bind their definition and ordered arguments. Deduction,
calling a template parameter, overload resolution and dependent free-function
lookup are unsupported. A field receiver binds now; member eligibility and
operation validity belong to future concrete checking. Binding a value argument
does not yet prove that it is a valid constant expression.

Explicit applications and global literal integer constants proceed to instance
preparation. Required expression evaluation and compile-time statements/specifiers
remain unsupported by their semantic consumers; these paths cannot silently
publish incomplete code.

## Scheduling, replacement and cost

The existing full/incremental selection algorithm chooses source-owner work
before execution. Workers read fixed symbols, catalog and frontend data and
produce private rows; only the join builds the accepted set. Execution is serial
through these work units, ready for the same units to be scheduled later.

Reuse checks the exact syntax snapshot, call and declaration targets, parameter
identities, provider references and formal parameter schemas of applied templates.
Changing a target's parameter roles can select an unchanged using file for
rebinding. New outputs additionally undergo an iterative structural coverage walk;
this verifies name/application coverage and constant scope ownership without
performing lookup or evaluation again.

Expression/application binding and acceptance use iterative traversals. Storage
tracks syntax occurrences and declarations, not combinations of possible types
or runtime control-flow paths. A binding pass visits both compile-time branches;
instance expansion is deliberately absent. Lookup through nested local scopes and
formal-schema comparison retain their ordinary depth/size costs; this is not a
claim of constant-time processing or measured native-memory performance.

The supported public increment remains a function-body edit under unchanged
contracts. Template definition edits currently use the existing conservative full
rebuild policy, through the same stages. Stage-level replacement tests also prove
stable definition IDs and dependency selection without promising a new public
incremental category.

## Proofs

[template_bindings.php](../../tests/04_analyze/resolve_symbols/template_bindings.php)
checks multi-parameter families, dependent annotations, forward/recursive names,
constant scopes, explicit calls, two thousand nested applications, exports,
fixed-worker purity, reversed and rejected joins, formal-schema invalidation and
provider identity changes. It also compiles and runs ordinary struct/function
code beside unused templates, then performs one real body increment while retaining
the previous snapshot unchanged. Required unsupported work fails without publication.

The existing compiler suite covers ordinary annotation migration, source/provider
records, runtime calls, cleanup, conversions, console behavior and incremental
publication. The next stage is [explicit instantiation and literal constants](explicit_instantiation.md),
consuming these bindings and joining the existing concrete type/body owners.
General constant evaluation is outside this compiler version. Provider family
registration remains a later adapter extension.
