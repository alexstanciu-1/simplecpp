# Metaprogramming: parsing and planned semantic owners
Doc Status: supporting

## Agreed scope

Metaprogramming resolves during compilation. Preserve its instructions in the
source AST, then bind, instantiate and evaluate demanded work before ordinary
concrete checking/lowering. A required constant computation that is unsupported
or invalid must report a compile-time error; it must not silently run at runtime.
Optimization level does not decide type correctness or required evaluation.

**Current-version scope:** the prototype supports literal integer constants and
explicit template instantiation. General constant evaluation is not required in
this compiler version. The parsed `constexpr`/`consteval` forms preserve design
intent; required unsupported execution or branch selection remains an error.
See the [bounded scope and implementation review](explicit_instantiation.md).

Start with simple constants and source templates, including multiple parameters
such as `hash<T1, T2>`. The first executable family proof will be our own small
list with behavior written in the source language. Reallocation on every append
is acceptable for that proof. It is not a `vector` implementation task and does
not authorize all missing list primitives now.

Provider families will use the same bound-argument and concrete-instance contracts;
their materialization goes through the runtime package adapter and preparation
path. Source bodies and provider implementations have different producers, then
join the existing concrete type/callable path. A later `vector<T>` proof must
establish that connection. Neither enumerating special instances nor selecting
compiler behavior by family spelling is acceptable.

### Agreed specialization and identity model

Follow the C++ specialization model: prepare separate concrete specializations
for demanded template arguments. For example, `list<int>::append` and
`list<string>::append` are distinct specializations; multiple objects or call
sites using the same specialization do not create new instance identities.
Size, alignment and element operations are resolved for each concrete instance.
The baseline is not one erased implementation receiving a runtime type descriptor.

A template definition has its own identity. Each concrete instance has a separate
identity referring back to that definition and its ordered bound arguments.
Source templates and provider families must share this distinction: a template
definition is not itself a usable concrete type. Exact instance keys and their
allocation/retention owner is established in [instance preparation](explicit_instantiation.md), under
the repository's identity rules.

**Future versions — optimization:** separate concrete specializations first;
possible code sharing through optimization afterward. Identical/equivalent code
may be shared where observable behavior is preserved, without conflating semantic
instance identities. This is deferred optimization work, not a prerequisite for
template correctness or part of the collection/resolution slice.

### Agreed collection/resolution boundary

The implemented binding slice establishes template definitions, parameter scopes and stable
bindings while retaining the original AST unchanged. Instantiation will consume
those results; collection/resolution does not create concrete instances or layouts.
Parameter references can bind to their declarations before concrete arguments
are known. Operations whose validity depends on those arguments remain explicitly
dependent until checking with concrete arguments; this does not defer type
correctness to runtime.

## Tokenizer and parser

The user approved these prototype spellings:

```text
template<typename T1, typename T2, int N>
struct pair_buffer { public hash<T1, T2> $items; }

template<typename T, int N>
constexpr function choose($value T): T
{
    const LIMIT: int = N + 1;
    if constexpr (N) { return $value; }
    else if constexpr (false) { return other<T>(); }
    else { return $value; }
}

const COUNT = 3;
consteval function count(): int { return COUNT; }
constexpr function select_context(): int
{
    if consteval { return count(); }
    else { return runtime_count(); }
}

$items hash<int, list<int>> = new hash<int, list<int>>();
choose<int, COUNT>(1);
```

These examples demonstrate parsing, **not executable support**. Template
application syntax is general across fields, locals, parameters, results,
construction and explicit function calls. Arity is not fixed. Arguments retain
names, nested applications and expressions without guessing whether a name is a
type or a constant. Binding to formal parameters makes that distinction later.

The tokenizer recognizes `<` and `>` separately, so adjacent `>>` closes two
nested applications. It also recognizes `template`, `typename`, `constexpr`,
`consteval`, `const`, `true` and `false`. It performs no type lookup.

The current expression subset is literals, names, variables, addition, grouping,
field access, named calls and zero-argument construction. `<` after a name
introduces template arguments; comparison and shift operators are not implemented.
Their eventual grammar must address this ambiguity explicitly. Defaults, packs,
constraints, specialization syntax, empty template argument lists and nested
template declarations are outside this parsing slice. Struct methods and member
constants remain outside the current struct grammar.

`if constexpr` retains a condition and both syntactic branches. `if consteval`
retains context selection with no condition expression; its branches are braced.
Neither is folded during parsing. Else-if chains are represented as nested
conditional alternatives. Ordinary else-if is also parsed, but its semantic
consumer currently requires a braced alternative and reports a source error.

## Compact representation and ownership

The common [syntax node](../../src/03_parse/data/structures.php) stays
unchanged: kind, byte span, first-child and next-sibling IDs. IDs are one-based
positions in one file snapshot, never semantic type IDs. New constructs add kinds
and child relationships, not payload fields to every node.

| Syntax kind | Ordered children |
|---|---|
| `template_application` | Target name, then one or more argument subtrees |
| `template_declaration` | Parameter list, ordinary declaration or evaluation wrapper |
| `type_parameter_declaration` | Name |
| `value_parameter_declaration` | Type syntax, name |
| `constexpr_declaration` / `consteval_declaration` | Ordinary function declaration |
| `constant_declaration` | Name, optional type-annotation wrapper, initializer |
| `type_annotation` | Type syntax |
| `constexpr_if_statement` | Condition, body, optional block/conditional alternative |
| `consteval_if_statement` | Body, optional alternative block |

Existing type-annotation views now expose `type_syntax_id` rather than
`type_name_id`: the node can be a name or an application. Function result syntax
keeps its existing `return_type_id` field. `Syntax_Access`, including its
[metaprogramming views](../../src/03_parse/utilities/metaprogramming_syntax.php),
owns structural roles. Consumers do not reinterpret child positions. Views return
IDs without copying subtrees or binding names.

[Expression_Parsing](../../src/03_parse/handlers/expressions.php) uses
one iterative continuation stack for type syntax, nested applications, calls,
groups and construction. Each frame holds its context, accumulator, enclosing
node and child tail. A completed subtree resumes its parent frame. This avoids
recursive parsing for deeply nested template applications and uses linear syntax
storage. Block/declaration parsing retains the existing recursive structure; this
is not a claim that all parser nesting is iterative.

[Metaprogramming_Parsing](../../src/03_parse/handlers/metaprogramming.php)
owns declarations/specifiers, while the existing control handler owns the two
compile-time condition forms. Traits execute on the one file worker. Export and
logical comparison understand all new kinds, argument order and literal spelling;
comparison does no evaluation.

## Work selection and current semantic boundary

The existing tokenizer/parser stages still select file work before execution.
Workers read fixed source/token buffers and construct private results. Joins
accept those results; an unchanged file reuses its frontend. This slice adds no
mutable global state, registry or special sequential path.

[Collection and name resolution](template_bindings.md) now bind definitions,
parameters, annotations and constant references without changing these ASTs.
[Explicit instance preparation](explicit_instantiation.md) now consumes these
bindings and supports literal constants. Required general evaluation still reports
a source diagnostic; parser/binding exports alone are not an instantiated program.

The [focused proof](../../tests/03_parse/metaprogramming_parsing.php)
covers ordered/mixed applications, all annotation sites, declarations, constants,
context selection, malformed input, exports/comparison, 2,000 nested applications,
1,000 arguments, reversed fixed workers and one real source-edit replacement.
It checks retained-input purity and public compiler rejection without publication.
The ordinary compiler suite continues to prove the preexisting executable path.

## Semantic owners and remaining work

| Owner | Responsibility/status |
|---|---|
| Symbol collection/resolution | Implemented: register templates/constants, parameter scopes and stable bindings; ordinary annotations share this path. See [binding contracts and proofs](template_bindings.md). |
| Template instantiation | Implemented for explicit source applications: bind ordered type/value arguments, own exact instance identity and dependency requests, prepare only demanded instances. |
| Literal constants / future evaluation | Current version: decode and validate integer literals only. Future evaluation must distinguish required execution, `constexpr` eligibility and `if consteval` context; unsupported required work is an error. |
| Type model and body checking | Continue to own concrete type identity, validity and checked bodies; source/provider materializers converge here. |
| Coordinator and joins | Select ready work against fixed inputs, accept private outputs and record dependency/replacement boundaries. |

These dependencies may require interleaving; this is not a promise of one global
AST rewrite pass. Establish contracts before choosing exact scheduling. Source
syntax remains retained, while resolved instance outputs carry bindings and
provenance. Explicit instantiation and literal constants now follow these contracts;
source list behavior and provider families remain the next capabilities.
