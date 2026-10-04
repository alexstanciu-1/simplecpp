# Retained compiler model
Doc Status: supporting

The compiler has one incremental path. A fresh build starts with empty retained
state and processes all work; later runs select affected work through the same
owners. `Model` holds static roots shared by Compiler instances. Workers are transient.

| Root/owner | Retained data |
| --- | --- |
| `Model::$modules` | Ordered keyed modules; each owns sources keyed by module-relative path |
| `source_record` | File input, current token/parse results, scan revision and pending change state |
| `parsed_file` | Typed file AST, scope owners, collection, completion state and token snapshot |
| `collected_file` | Canonical occurrences and semantic/generation handoffs for that source |
| `language_scope`, `global_scope` | Built-in/runtime definitions and shared source indexes; global parents to language |
| AST/collected definitions | Specialized facts and retained declaration/body work |
| `prepared_files` | Successfully completed file records referencing the retained syntax |
| `cpp_output_program` | Retained declaration/body fragments and cached text/includes |
| `cpp_files`, `llvm_files` | Published backend artifacts |

`Model::tokens()`, `syntax_files()` and `collected_files()` are snapshots derived
from source records, not parallel retained indexes. `Compiler_Lifecycle` owns root
initialization/reset; Model does not call processing code.

## Source and symbol identity

Modules save their key/name, declared path, resolved path and input position. Any
configuration/order change forces a full rebuild. Files save module-relative paths;
IO boundaries compute full paths. A source record observes its owning module.

The parser reuses matching declaration nodes and their canonical collected identities.
Only applicable name-bearing syntax has an occurrence backlink. Retained names are
independent of token indexes. Duplicate names remain distinct candidates. The
collector registers symbols during parsing; there is no collection pass or second
symbol model. See [analysis](../../04_analyze/README.md) for typed collected roles.

Function signatures and bodies have separate work identities. Fields/parameters
settle with their definition; locals settle with their body. A body is a processing
unit, not a symbol. Replacing body syntax transfers its retained work identity.
Unchanged bodies retain nodes, scopes, occurrences and facts.
Parameters own optional default expressions; normalized scalar default snapshots
belong to their prepared facts and participate in signature equivalence. Source calls
retain only explicit arguments, with omitted defaults supplied by the declaration.
Variadic syntax marks one trailing parameter independently of passing mode; its
prepared element type is distinct from its ordinary vector storage type. Prepared
calls retain the fixed-prefix/pack boundary without rewriting source arguments.

## AST and scopes

The property-free `ast_node` base supplies operation contracts; specialized node
families own typed syntax fields and uint32 spans. `statement_body_node` shares
ordered body storage/traversal between executable roots and nested blocks.
Conditional arms own explicit body/next-arm edges. The common `loop_node` owns a block
body; concrete while/do-while/for nodes own typed headers. An abstract foreach
specialization reserves the same protocol for Chapter 06. Break/continue facts observe
their prepared enclosing target; transient completion composes four exit possibilities
without a CFG. Conditional expressions own condition, optional true branch and
fallback; absence of the true branch represents shorthand without duplicating the
condition edge. Prepared facts retain truthiness and the value-result join.
Binary logical expressions retain ordered `operand_truthiness` decisions on
`operator_decision` for condition-based AND/OR. These replace operand conversions
for that operation family; the emitter consumes the decisions without type discovery.
`value_probe_node` owns one operand and an `isset`/`empty` enum kind. Prepared probe
facts retain the admitted operation and boolean value type; workers own admission
and the runtime helpers own the distinct presence/emptiness semantics.
`take_expression_node` owns source, value binding and optional alternative binding.
Assignment and helper spellings share this node. `prepared_take` records the admitted
wrapper operation; preparation validates existing output places and copy capability.
Its named edges participate in normal cleanup, traversal and incremental maintenance.
The runtime helper owns selected-output behavior, including clearing error on success.
Match expressions own a selector and ordered arms; each arm owns expression labels
and one result. Empty arm labels mean default. Arm facts retain exact integer labels
and a result conversion. Match retains scalar-only admission; conditional preparation
owns symmetric scalar/nullable-scalar and matching Result/scalar joins and records
both branch conversions. Result joins preserve a single exact wrapper identity;
they neither extract a selected payload nor merge wrapper families.
Switches own selectors and ordered label groups; each group owns
labels and one ordinary block body. Exact case values are attached to labels after
bounded constant resolution, before duplicate checking. There are no payload objects, generic sibling chains
or inspection parents. [AST layout](ast_layout.md) owns detailed traversal rules.

Parsed files retain file/executable scopes. Functions own signature scopes; structs
own member scopes. Occurrences observe their enclosing scope. Global pools index
the same declarations; built-ins live in the language/runtime scope. Scope methods
encapsulate membership; `Scope_Lookup` performs lexical/publication lookup.

Type definitions live under `compiler/types/`; constant definitions live under
`compiler/constants/`. Built-ins are initialized into independent type and constant
scope indexes at startup. Source records and prepared references retain canonical
definition identity rather than copying semantic values. New runtime-library JSON
intake, source constant declarations and broader constructed types remain future
work. Reserved-name validation is deferred; ordinary parent lookup currently applies.

Concrete nominal definitions share `abstract_nominal_type_definition`; the ordinary
`nominal_type_definition` remains final. Final siblings `mixed_type_definition` and
`dynamic_type_definition` implement separate marker interfaces `mixed_semantics` and
`dynamic_semantics`. Both reuse `nominal_type` identity and registry interning, without
source-name tests or new top-level families. Mixed has value-box storage; dynamic is
the shared object/table form. Neither role is an unknown compiler type or a blanket
operation capability. Registered copy/storage contracts do not claim deep copying,
hashability, comparison, iteration or member dispatch. Runtime payload tags and
value-dependent validation remain runtime-owned; backend bindings are independent.

Template definitions share `abstract_template_type_definition` for ordered parameters,
arity/defaults and result kind. The ordinary `template_type_definition` remains final;
its final sibling `nullable_type_definition` implements `nullable_semantics` with
typed payload access for an application of that exact definition. Applications and
registry validation/interning depend on the abstract base. The interface identifies
language interpretation without a role flag, spelling checks or a second application
hierarchy. Result wrappers similarly share one final `result_type_definition`
implementing `result_semantics`, with constructor-set `result_kind` distinguishing
structured errors, false sentinels and boolean sentinels. `result_family()` exposes
that immutable family; inherited `result_kind()` still classifies template output
as class/struct. Separate registered definitions and backend bindings preserve each
family's canonical identity. Payload access validates the owning application.
Result construction and checked required-value extraction decisions live in
`conversions/results.php`; explicit casts, other operators and profile availability
remain separate follow-up work. Explicit
boolean sentinel constants use ordinary internal runtime nominal tag identities
with no storage capabilities/exposure; singleton constant definitions carry those
identities through existing lookup. They are not boolean payload values. `nullopt`
uses the existing null identity. No additional AST or template class is introduced.
Conversion/comparison/truthiness policy remains worker-owned.
Concatenation retains ordinary operand conversion decisions with a dedicated text
context and `text_conversion` operation; no new AST or conversion-chain structure
is needed. Text normalization is distinct from explicit casts and required extraction.
Compound preparation retains computation, conversion and `compound_write_back` mode:
converted native assignment versus mixed stored-kind validation. The latter needs
the destination's current runtime kind, so it is not an ordinary source/target type
conversion. Prefix/postfix mutations retain their selected operation and return an
owned old/new snapshot, not an addressable reference into the target.
The nominal runtime `error` is a storable value; its no-error state is a registered
`no_error_constant_definition`, not a singleton type (the same type holds real errors).
Its qualified source name uses the existing constant lookup and prepared identity path.
`coalesce_expression_node` owns left/fallback syntax and receives selected scalar
or mixed result/conversion facts; it is distinct from truthiness-based conditional syntax.
Prepared `coalesce_presence` separates always-present values, wrapper value states
and mixed non-null testing from conversion identity. Direct mixed/scalar selections
share bounded join admission, preserve the mixed carrier and copy the selected value;
they do not alter scalar-only match. Coalescing facts separate `left_extraction`
(source to selected payload, identity for direct values) from `left_conversion`
(selected value to result). Approved wrapper-left/mixed-fallback joins compose
existing extraction and boxing decisions, without a general conversion chain model
or implicit wrapper-to-mixed boundary rights.
`null_type_definition` / `null_type` give null literals a canonical,
non-storable sentinel identity, without source exposure or a storage backend binding.
`nullable_type_node` owns `?T` syntax and resolves through shared application interning;
`null_literal_node` carries the sentinel fact until a typed destination conversion.

## Structure and processing boundary

Structures may initialize data, enforce local invariants, expose typed access,
clear attached facts, and forward operations to their workers. Workers own semantic
algorithms, scheduling, dependency maintenance and publication. Retained structures
do not keep processing contexts or workers.

Preparation dispatch passes one invocation-local context directly to nodes. Typed
work records dispatch scheduling/rebuild/settlement to `Preparation_Worker`.
`File_Preparation` is the standalone entry adapter to that scheduler. Facts are
attached to syntax, not token-indexed maps. See [preparation](../../04_analyze/prepare/README.md).

Maintenance is node-owned traversal through named owning fields. Workers implement
`enter`, `edge` and `token_index`; edge handling chooses recursion. Inspection
iterators are not used to run preparation, generation or cleanup.

## Retained C++ generation

Successful semantic changes/deletions accumulate in `collected_file.preparation_changes`.
The backend retains fragments keyed by existing work owners, with completion versions,
dirty state, text, includes and record dependencies. It renders selected fragments;
unchanged bodies are not traversed. Assembly consumes the handoff only on success.
Function signature fragments retain separate prototype and definition spellings so
default values appear only on prototypes, without rerendering syntax during assembly.
Failures retain pending work and withhold completed output; unexpected failures
request a full rebuild.

Assembly preserves the current single-`main.cpp` layout and orders record dependencies.
It reconstructs includes from live fragments. Generated names use role prefixes and
saved source names; temporary numbering is per body. Future namespaces, overloads
and richer shadowing require a naming review. No disk writer or Ninja integration
is implied. Partitioning is tracked in [debt](../planning/incremental_strategy.md).

## Lifecycle and legacy boundary

[Incremental lifecycle](../lifecycle/incremental.md) owns synchronization, appended
tokens, failure and deletion policy. [Ownership](ownership.md) owns reference intent;
[Storage](../storage/STORAGE.md) owns membership semantics.

The [LLVM experiment](../../05_backend/llvm/README.md) retains separate token-indexed
name/template preparation only for regressions. Its maps and native type choices
must not define shared semantics. Future LLVM development must consume shared facts.
