# PHP and PHP++ coding style
Doc Status: supporting

Read this guide before writing or modifying PHP (`.php`) or Simple C++ / PHP++
(`.phs`). Its rules override existing formatting conventions and do not apply to
other languages. This guide also defines adjacent plain-text navigation maps.
PHP++ follows the language/API guidance required by
[AGENTS.md](../reference/source-repository/working_rules.md). Examples below use PHP syntax.

## Expression parentheses

Apply these rules wherever expressions occur:

- Parenthesize each operand of `&&` and `||`, including longer chains, except
  direct function or method calls. Explicitly group mixed logical operators.
- Do not add grouping parentheses around a call itself. Preserve parentheses
  required by control syntax or by a larger expression containing the call.
- Within comparisons, parentheses around arithmetic operands are required for
  three or more terms and preferred, but optional, for two terms.

Examples of expressions:

```php
($byte === '/') && ($offset + 1 < $length)
($byte === '/') && (($offset + 1) < $length)
($byte === '/') && (($offset + 1 + 3) < $length)
($a === $b) || (($c === $d) && ($e === $f))
($byte === '/') && str_contains('=>', $text[$offset + 1])
($byte === '/') && (strlen($text) > $offset)
```

## Code blocks

All code blocks have multiline bodies, even with one statement. Put the closing
brace on its own line. Start `else`, `elseif`, `else if`, `catch`, `finally`,
and the trailing `while` of `do ... while` on a new line after `}`. Punctuation
belonging to an enclosing statement or expression, such as `};` or `},`, may
follow the brace.

For all line-count rules, count nonblank lines in the formatted body, including
nested blocks but excluding the body's own opening/closing braces and all
comment text (line, block, and documentation comments). A line containing code
and a comment counts once; comment-only lines do not count.

| Block | Opening brace |
|---|---|
| Named function or method, any length | On its own line |
| Any other block with up to five counted body lines | On the header line |
| Any other block with more than five counted body lines | On its own line |

This includes class/declaration bodies, control blocks, and lambdas. Small
lambdas still require multiline bodies.

```php
/** Advance the cursor only while its input is ready. */
private function advance(): void
{
    if ($this->ready) {
        $this->offset++;
    }
    else {
        return;
    }
}

$identity = static function (int $value): int {
    return $value;
};
```

## Grouping and comments

Group distinct steps by purpose, with one blank line between groups. Choose
boundaries by meaning, not line count; do not artificially divide a
straightforward step.

Every function or method with more than five counted body lines must have a
documentation comment (`/** ... */`) immediately above its declaration. This
requirement is additional to the unchanged five-line brace-placement rule.
Use the line-count definition under Code blocks; signatures and comment text do
not count. This includes constructors and private helpers. Explain the purpose and,
where relevant, the input/output contract, ownership, side effects or invariants.
Keep an adequate existing doc comment rather than adding a duplicate; parameter
or return-type annotations alone do not explain a method's purpose. Shorter
functions and methods still need a comment when their intent is not obvious.

Within bodies, separate distinct steps with blank lines and comment groups,
blocks or individual branches when their purpose, reasoning, invariants or
control flow would otherwise take effort to infer. A method doc comment does
not replace this segmentation. Choose meaningful boundaries, not a fixed number
of lines; avoid comments that merely restate obvious operations. For example,
field initialization may explain itself, while an iterative parser's pending-call
stack and resumption decisions may need explanation.

Put comments immediately above their statements or relevant declaration/control
header. For group comments, leave one blank line above the comment unless the
group begins the enclosing block. Explain individual `if`, `elseif` / `else if`,
`else`, and similar branches when needed, even if the whole chain has a comment.
Avoid restating obvious operations or duplicating an adequate existing comment.

## Quick navigation maps

Keep navigation readable as plain text in an editor; Markdown files may use
fenced text diagrams but must not require rendering, Mermaid or another tool.
Use two or three levels normally, four only when an essential relationship
cannot otherwise be explained. Show the main path, important conditional/reuse
paths and task loops; omit routine getters and exhaustive helper expansion.

- Each folder grouping compiler steps has a `README.md` with a short ordered
  list of its steps, checked against actual coordinator execution. State any
  preparation dependencies or interleaving that a simple list would conceal.
- Each process has `calls.md` showing its callers, entry points, workers,
  dispatch/handler groups, joins and main outputs, with filenames for navigation.
  A numbered folder that is itself a process needs only `calls.md`. The overall
  coordinator map is `compile/calls.md`; shared diagnostics and simulation maps
  explain their supporting roles rather than inventing pipeline stages.
- Each active compiler code file starts with a short `Role` and `Call map`
  comment, normally five to eight lines. Put it after the PHP opening tag and
  strict-types declaration, before namespace/imports. Data files use `Used by`
  and `Flow` instead of pretending records have a call stack. Trait maps name
  the composing owner: trait methods execute on that owner, not another worker.

Use exact class/method names for callable entries. Prefix descriptive work with
`[action]`, mark alternatives with `[if ...]` and repeated work with `[each ...]`.
An arrow means a call/delegation; ordered siblings mean sequential calls, not
that one sibling calls the next. Data-flow arrows describe handoff, not calls.
These are navigation summaries, not literal runtime stacks or full contracts.
Keep method-level API/ownership documentation; avoid repeating it in the map.
Update maps when ownership, main calls, dispatch or execution order changes.

Apply this convention to `src/` and `bootstrap.php`.
Tests, benchmarks and tools keep their own explanatory comments. The retained
PHP++ reference is updated only during an explicitly authorized port.

## Step lifecycle

Adopt the small step contracts from
[compile/step.php](../src/compile/step.php) when giving a compiler
phase an explicit lifecycle. All numbered prototype stages follow this contract;
workers, utilities, stores and retained coordinator/tool services do not.

- Put the process owner and its lifecycle in `main_<process>.php`, for example
  `main_parse.php` containing `Parser`. Keep the meaningful class name; do not
  add a separate `*_Step` wrapper or a forwarding entry file. Workers, handlers,
  joins and data keep their responsibility-based files. Adopt this convention
  for phase owners, including separately scheduled preparations within a process.
- One step instance executes one update phase. Its constructor only captures
  arguments (including promoted properties); initialization work belongs in
  `init()`. Default property values may establish the initial empty state.
- Make the main sequence visible: `init()`, `run()` when supported,
  `finalize()`, then `result()`. Runnable means one call completes processing;
  internal worker calls are still allowed. `supports_run()` reports the fixed
  capability and agrees with implementing `Runnable_Step`.
- Guard public lifecycle/processing entry points, not inner loops or private
  handlers. Invalid timing throws `LogicException` naming the operation,
  expected state and actual state, without changing the state. `status()` and
  `supports_run()` remain available in every state.
- Runnable states are `created -> ready -> running -> processed -> finished`.
  Initialization, processing or finalization failure enters terminal `failed`
  and rethrows the original error. No reset/retry on the same step instance.
- `finalize()` completes candidate output; it does not publish the compilation
  or replace cleanup after failure. It runs even for zero selected tasks.
- `result()` and optional `store()` are available only after finishing and
  repeatedly return the same completed objects, shared read-only by agreement.
  Marker interfaces preserve concrete return types; they do not enforce deep
  immutability or require result/store wrappers. A step without a store does
  not implement `Store_Providing_Step`.

Keep state transitions explicit and local. Do not add a lifecycle engine,
generic event machinery or a base class merely to share a few assignments.
Request-driven steps will define their specific operations and completion
conditions when adopted; do not invent a dummy `run()` for them.

## Join contract

Every implemented task-result join uses [compile/Join](../src/compile/join.php),
including owners in descriptive `*_join.php` files. The concept is acceptance of
selected worker results; path concatenation and control-flow merging are separate.

- Capture concrete context, previous data and selected tasks in an assignment-only
  constructor. Validate them when acceptance starts.
- Expose instance `join(array $results)` with the owner's concrete return type.
  Keep result element types on that method and captured array element types on the
  constructor. Keep algorithms and private candidate ownership in the process.
- The owning step controls execution and failure status. Joins do not need another
  step lifecycle or result wrapper. Construct an owner for its fixed batch.
- Stateful segmented acceptance may coexist with the common entry: Frontend_Join
  keeps merge()/finish(), with join() accepting a batch and finishing accumulated
  results. A rejected segment adopts nothing; incomplete finish preserves accepted
  segments. Private-candidate joins retain their documented discard-on-failure rules.
- `mixed` is only the PHP interface return envelope. Concrete joins keep their
  object/array returns. Before an authorized Simple C++ port, replace mixed and
  unparameterized arrays with explicit result types and typed container contracts;
  do not carry unclear types into native code or invent wrappers now solely for it.

See the [join inventory](details/join_organization.md) for all owners and outputs.

## Method declaration order

Reorder methods within their existing class or trait to follow the main call
flow: constructor/initialization first, main entry next, then its supporting
methods in first-call order, descending into each helper's supporting methods.
Group multiple entry paths in their usual process order; follow dispatch case
order for branches. Shared helpers used by several paths normally go near the
bottom, with lifecycle cleanup/destructors where their role is clear.

Use judgment for shared, recursive and externally invoked methods: a call graph
does not have one total execution order. Keep one definition of each method and
apply the same convention within trait files. Preserve an already useful order
when no clearer call sequence exists. Maps explain significant exceptions.

Move only methods and their attached comments. Leave properties, constants,
enum cases, classes and other declarations in their existing relative positions;
do not move methods between owners to achieve a visual order. Preserve method
bodies, signatures, visibility and attributes. Validate method/declaration
preservation and run relevant checks after broad ordering changes.
