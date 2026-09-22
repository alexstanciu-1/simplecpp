# Parser organization plan
Doc Status: supporting

Status: all five slices implemented and verified after the statement-handler
pilot. The sections below retain the slice contracts and acceptance criteria;
the final section records the verification evidence. Timing benchmarks are
excluded from acceptance while the computer is in concurrent use.
The [organization intent](../code_organization.md#controlled-feature-extension-in-the-php-prototype)
and [current parser contracts](parsing.md) remain authoritative.

## Objective and scope

Make every existing grammar family reachable through a small, named set of
extension points, with related implementations grouped in process-local traits.
Keep one `File_Parser` worker and the existing flat syntax representation.
Completion means a maintainer can locate the dispatch, handler, representation
and structural consumer obligations for a new construct without searching the
whole compiler.

Scope is `src/03_parse/`, its bootstrap composition, documentation and
relevant tests. Preserve supported source, rejection behavior, diagnostic order
and spans, AST allocation order/IDs, exports and frontend reuse. Node IDs are
still snapshot-local; preserving them here is a refactor proof, not a new
cross-update identity guarantee.

Non-goals: new grammar (including `switch`), new operators or precedence rules,
new AST shapes, semantic decisions, changes to other compiler stages, a plugin
registry, runtime handler discovery, threading, incremental-policy changes,
PHP++ porting, or converting every class into traits. Existing recursive block
parsing is preserved; this work does not claim unlimited block nesting.

## Ownership and layout

```text
03_parse/
    parse_file.php                 File_Parser state, file assembly, core helpers
    data/
        structures.php            syntax vocabulary, records, expression cursor
        result.php                completed frontend, validation and export
        store.php                 syntax storage and frontend index
    handlers/
        statements.php            statement lists, dispatch, small statements, blocks
        control_statements.php    existing if/while grammar
        declarations.php          declaration dispatch, functions, parameter lists
        expressions.php           iterative driver, operand dispatch, completion
    utilities/
        syntax_access.php         structural access for consumers
        syntax_comparer.php       logical syntax comparison
    main_parse.php                Parser lifecycle and private work selection
    join.php                      replacement assembly and completeness checks
```

Data, utilities and process-root files keep their existing class responsibilities and
the `parse` namespace. `Declaration_Parsing`
and `Expression_Parsing` join the two original traits. Calls remain a named
method in the expression trait: their current size does not justify another
file. Split a substantial family into its own trait only when that improves
cohesion and navigation.

`File_Parser` keeps all retained worker properties, its constructor and public
`parse()` method. Its small shared token, span, node and link helpers stay local
to the owner, including `name()` and `variable()`. Traits have private methods,
no properties, no constructors and no nested trait composition. The owner
lists its traits explicitly; bootstrap loads them before the class declaration.

## Slice 1: Declaration handling and statement-list coordination

Move `function_definition()` and `parameter()` into `Declaration_Parsing`.
Extract the existing parameter-list loop into one named method returning its
finished list node, preserving allocation and delimiter order. Keep parameter
syntax together with the function contract that contains it.

Move `statements()` into `Statement_Parsing`, next to `statement()` and `block()`.
It continues to distinguish file-scope definitions from executable statements.
Introduce one declaration-dispatch method called by this list loop. It returns
the completed declaration node ID or an explicit absence when the current token
does not start a supported declaration; absence must consume no tokens and
allocate no nodes. Only the list loop appends to `definitions`. A recognized
but forbidden nested function must retain the current diagnostic and anchor.
Keep declaration recognition and dispatch together so a future declaration does
not require parallel keyword lists in the list loop and handler.

Keep the existing shared name parser for simple named type annotations. Compound
type syntax will need its own grammar contract with that future feature; do not
add empty type-dispatch scaffolding now.

Proof: interleaved definitions/statements, empty bodies, ordered parameters,
malformed signatures, nested-definition rejection and unchanged child links,
IDs and spans. Run the existing parsing, variable-parsing and parameter-parsing
tests through their usual isolated fixture setup.

## Slice 2: Extract the expression owner as a coherent unit

Move `expression()` and `begin_call()` into `Expression_Parsing` first, preserving
the current loop and `expression_cursor` protocol. This establishes the file
boundary before changing control flow. The pending stack remains local scratch
space for one expression; it does not become worker-wide state or a new retained
dataset.

Proof: calls with zero/many arguments, nested calls and groups, variable/literal
operands, addition association, source spans, malformed delimiters and exact
exports. Include existing deep/wide expression cases. Compare against the
pre-slice parser before proceeding to decomposition.

## Slice 3: Make expression extension points explicit

Separate the current loop into named responsibilities within `Expression_Parsing`:

- Operand-start dispatch: literals, variables, groups and named calls.
- Operand attachment and the existing addition-node construction.
- Argument separation and call/group completion/resumption.

Extract substantial branch bodies into methods; keep trivial cursor transitions
in the driver when moving them would hide the sequence. Operand handlers set up
new frames; the driver controls resumption and pops completed frames. Grouped
expressions retain their current representation
without an extra group node. Addition remains the only infix operator.

Document the expression-specific handler contract before splitting the loop:
an operand handler either produces a completed node or opens a continuation
for the driver. If completion-or-suspension is returned, use a typed nullable
node ID with a documented meaning: a positive ID is complete, and null means
input was consumed and a continuation was established. Do not overload zero,
return arbitrary arrays, or create a general parser-action framework. Keep the
existing cursor record; change it only if the current algorithm needs a concrete
missing fact, with no added field per token or syntax node.

An expression parser must stop before the caller's delimiter. The continuation
logic consumes only delimiters belonging to its call/group, exactly once. This
contract differs from statement/declaration handlers, which return complete
constructs including their delimiters.

There should be one visible place to recognize supported infix continuation and
one place to construct its node. Future precedence or associativity changes must
extend the expression algorithm deliberately; merely adding another operator
branch is not promised to be sufficient.

Proof: before/after equality over valid and malformed composed expressions,
including truncated prefixes; current deep calls, wide argument lists and long
addition chains; no added recursive descent for expressions. Timing benchmarks
are deferred while the computer is in concurrent use. Verify behavior and
storage/traversal structure; do not infer execution-speed changes from file layout.

## Slice 4: Map all parser-owned feature obligations

Audit the stage as a whole and add a compact extension map to `parsing.md`:

| Change | Owned extension point |
|---|---|
| Statement grammar | `Statement_Parsing::statement()` and its selected handler |
| Declaration grammar/context | declaration dispatch and its selected handler |
| Operand grammar | expression operand-start dispatch and its selected handler |
| Operator grammar | expression continuation/construction rules |
| New syntax kind or payload | `syntax_kind` and related records in `structures.php` |
| Child-role contract | named `Syntax_Access` method and relevant shape checks |
| Logical equality | explicit supported-kind/spelling rules in `Syntax_Comparer` |
| Debug representation | `File_Frontend::to_json()` when the new shape needs it |

These are controlled, distinct responsibilities; not every feature needs edits
at every site. Generic child traversal and export should continue handling
existing shapes. Keep invalid/unsupported-kind behavior explicit. Audit apparent
duplication before sharing a classification: export text, expression membership
and comparison validity are different questions. Do not introduce a universal
syntax metadata schema to replace them.

Keep `Syntax_Access`, `Syntax_Comparer`, results, stores and joins as separate
classes. Their present size does not justify mandatory trait extraction. Check
their current behavior with existing tests; unrelated discovered defects get a
separate reported slice rather than changing this refactor's behavioral target.

## Slice 5: Consolidation and acceptance

Review the completed composition for clear owners and useful method names.
Remove duplicated grammar bodies, redundant forwarding methods, misleading
comments, obsolete pilot-only descriptions and temporary comparison code.
Update the current-layout documentation only as each implementation lands.
Avoid changing expected outputs to accommodate structural regressions.

Verification builds on existing tests; add a regression case only for an actual
gap, especially continuation/delimiter interactions. No tests should assert
trait names or file layout as a substitute for source behavior.

- Compare original and reorganized parsing on representative valid/malformed
  inputs: exact trees, node ordering, exports and first diagnostic message/span.
- Verify token/source snapshots remain unchanged, each node has one parent and
  work results retain their exact input snapshot.
- Run parse-update proofs for independent workers, segmented/reversed joins,
  full/selective equivalence, reuse, removals and failure/repair.
- Run source-to-native proofs for parameters, calls, locals, addition and control
  flow, then the full prototype suite once the implementation is consolidated.
- Keep timing benchmarks outside this slice's acceptance while the computer is
  in concurrent use.

Work now starts at `Parser::init()` and `Parser::run()` in
[main_parse.php](../../src/03_parse/main_parse.php); selection is
private to the process owner. Every `File_Parser` task reads fixed tokens and
builds private output. `Frontend_Join` alone assembles
replacements. Existing token identity and the coordinator's full-rebuild flag
still determine selection; deleted contributions are removed at the join.
Trait composition must add no cache, mutation of retained inputs or alternate
full/incremental execution path.

Stop and report before a change requires different public syntax contracts or
work across compiler ownership areas. This plan is complete when all current
grammar families have documented entry points, the owner is easy to navigate,
and the preservation proofs pass. Future language features remain separate
vertical slices through the stages that own their semantics.

## Completion evidence

The statement, control-statement, declaration and expression traits are composed
explicitly by `File_Parser`. The owner now contains file assembly and shared
token/tree operations with its worker state. Declarations have one recognition
point, including nested-context rejection. Expressions retain the original
cursor record and iterative algorithm, with named operand, call, addition and
resumption methods. The [current extension map](parsing.md#feature-extension-map)
covers syntax access, comparison and exports as well as grammar dispatch.

The declaration slice passed the existing parsing, variable-parsing,
parameter-parsing and parse-update tests. A temporary before/after comparison
against the statement-pilot baseline passed after each extraction and expression
decomposition: 707 valid/malformed inputs, including truncated prefixes, deep
calls/groups, long addition chains and large statement lists. Trees, node IDs,
exports and first diagnostic messages/spans matched exactly; token snapshots
remained unchanged. Temporary comparison implementations were removed.

The complete `python3 tests/run.py` suite passed after the final code
changes, including PHP syntax checks, source-to-native execution, fixed-worker
purity, frontend reuse, full/selective equivalence and failure/repair.

The user confirmed concurrent computer use during the timing experiments.
Those timings do not establish a performance regression or improvement; the
earlier slowdown conclusion is withdrawn. No further timing benchmarks are
required for this slice. The implementation adds no node metadata, expression
recursion or persistent cache; acceptance rests on the behavior and structural
proofs above.
