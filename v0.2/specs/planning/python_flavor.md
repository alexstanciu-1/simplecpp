# Python syntax flavor

Doc Status: planning

## Concept

Python is a fourth per-file syntax flavor of Simple C++. Borrow typed Python spelling and indented blocks. Shared preparation owns types, binding, conversions and evaluation guarantees. The existing backend lowers the common model to C++. Mixed-flavor projects use the ordinary source and symbol pipeline. See the [frontend contract](../compiler_frontends.md).

Decide the contract before admitting spellings with different observable behavior. Examples include Python division, floor division, operand-valued boolean operators and function-scoped assignment. CPython execution and library compatibility are outside this proposal.

## First tokenizer slice

Keep the shared scanner and add Python-specific layout handling. Track open delimiters and an indentation stack starting at zero.

- Emit `NEWLINE` at the end of a nonempty logical line. Ignore blank and comment-only lines. Support LF, CRLF and `#` line comments outside literals. Suppress layout within open parentheses, brackets and braces.
- Compare leading spaces at the next significant line to the stack. Emit `INDENT` for a greater width and one `DEDENT` per popped level for a smaller width. Diagnose unmatched dedents and tabs in leading indentation. The parser decides where an indentation change is legal.
- At EOF, emit a final `NEWLINE` if a complete logical line is pending, then emit the outstanding `DEDENT` tokens. An unclosed delimiter remains an error.

Lex adjacent plus and minus signs separately in Python (for example, a--b is subtraction of a negated operand). Retain original source snapshots and byte offsets. A physical `NEWLINE` spans its LF or CRLF. Anchor `INDENT` and `DEDENT` at the next significant token with zero-width spans. Suite AST spans end at the final statement, before dedent lookahead and following trivia. Anchor EOF layout tokens at EOF. Point indentation diagnostics to the offending tab or indentation prefix and report line and byte column through the existing source-position model. [Python lexical reference](https://docs.python.org/3/reference/lexical_analysis.html#line-structure)

## First parser slice

Select the Python grammar through the existing per-file flavor mechanism. Parse a multiline suite as colon, `NEWLINE`, `INDENT`, statements and `DEDENT`. Build the existing shared block AST. Associate `elif` and `else` with the matching `if` at the same suite level. Diagnose missing colons, missing suites and unexpected indentation at their original positions.

Support typed top-level `def` declarations with explicit parameter and return types, typed initialized locals using the shared declaration parser, existing-local assignment, positional calls, parentheses, scalar literals including `True` and `False`, and basic arithmetic and comparisons where behavior aligns. Include `if` / `elif` / `else`, `while` and value `return` under the shared function and entry contracts.

Defer single-line suites, semicolon-separated statements, backslash continuation, first-assignment inference, division and remainder spellings, boolean operators, chained comparisons, classes, collections and Python import semantics.

## Verification checklist

- [x] Check exact layout-token sequences for nested and sibling suites, multiple dedents, blank and comment-only lines, multiline calls, LF and CRLF, and EOF with and without a trailing newline.
- [x] Check original byte spans and diagnostics for unexpected indentation, tabs, unmatched dedents, missing colons and missing suites. Include UTF-8 before an error on the same line to distinguish byte columns from character columns. Verify adjacent signs and assert that nested suite and function spans exclude trivia before the next sibling.
- [x] Compare common AST shapes for equivalent Python, cpp, php and js fixtures. Build and run generated C++ with Clang.
- [x] Build a mixed project where a Python-flavor entry calls a typed function in an existing flavor and that function calls a Python-defined helper. Compare stdout, stderr and exit status with the equivalent existing-flavor project.
- [x] Edit only the Python helper body and check reuse of the unchanged caller. Introduce a layout error and check that the last good bundle survives. Restore the source and compare with a fresh build. Run the resident sequence with cpp, php and js bridges. A compatible helper signature edit must rebuild the helper and its bridge while reusing the Python entry. Check native results and fresh artifact equivalence, then exercise incompatible signature failure and recovery.
- [x] Run existing tokenizer and parser regressions for cpp, php and js.

The first slice uses .py for project discovery and py for explicit CLI selection. CTest registers compiler_python_frontend and compiler_python_flavor_native. Existing syntax fixtures explicitly select the flavors they generate source for.

## Second slice: boolean expressions

Admit Python `not`, `and` and `or` with canonical `bool` operands and a `bool` result. Reuse the shared comparison and logical AST nodes. Keep Python spelling and precedence in the frontend. Shared preparation checks both operands of a binary expression, including a right operand that runtime short-circuiting would skip. Require boolean types without applying the wider shared truth conversions. Existing lowering preserves single evaluation and short-circuiting. [Shared logical contract](../../compiler/docs/logical.md)

Comparisons bind tighter than `not`, then `and`, then `or`. Thus `not a < b and ready` means `(not (a < b)) and ready`. Grouping can override precedence. Python returns the selected operand from `and` and `or`; for boolean operands the shared result has the same value. Operand-valued expressions such as `name or "fallback"` are deferred. [Python boolean operations](https://docs.python.org/3/reference/expressions.html#boolean-operations)

Keep `/` and `//` deferred. Python true division of integers produces a floating result and floor division rounds toward negative infinity. The existing shared integer quotient truncates toward zero. Decide precision, rounding, and failure rules in a separate numeric slice. [Shared arithmetic contract](../../compiler/docs/arithmetic.md)

### Verification checklist

- [x] Check the AST and native results for comparison under `not`, `not` under `and`, `and` under `or`, and parentheses that override precedence.
- [x] Run Python callers with a boolean probe supplied by each of the cpp, php and js flavors. Verify that `False and probe()` and `True or probe()` skip the probe, while the complementary forms call it exactly once. Use an effectful left operand to verify it runs once and before any selected right operand.
- [x] Reject non-bool operands of `not` and on either side of `and` or `or`. Include decisive left operands such as `False and 1` and `True or "fallback"`, and check diagnostics point to the offending operand.
- [x] Store a selected false RHS from each existing flavor in a typed Python bool local. Verify the callee runs once and the local retains false.
- [x] Edit a skipped RHS callee from bool to int for cpp, php and js. Check that the unchanged Python caller reports the error at the call operand, the last good manifest and C++ artifacts survive, and restoring bool matches a fresh build and restores native behavior.
- [x] Verify that the Python frontend still rejects both division spellings. Run the existing Python frontend and mixed-flavor regressions and the shared logical-expression tests.

The Python frontend and mixed-flavor native proofs passed along with the existing shared logical and native logical regressions.

## Batch backlog after the boolean slice

The C++ flavor adapter is 3,712 bytes. Most grammar lives in the shared parser, namespace parser and record parser. Judge additions by whether they reuse established AST and semantics. Keep Python layout in the tokenizer, spelling and precedence in parsing, type checks in preparation and native execution in shared lowering.

| Batch | Scope | Readiness |
| --- | --- | --- |
| 1. Everyday bodies | Conditional expression, pass, unlabelled break/continue, and bare return in explicitly void functions | Reuse the shared ternary with Python precedence and right association. Preserve shared branch type agreement and selected-arm evaluation. Represent pass with an empty statement span. Reuse loop exits and void-return checks. Keep typed declarations and block-local scope. |
| 2. Typed updates and traversal | Numeric +=, -= and *= on existing locals, then for name in collection for published typed collections | Reuse compound assignment and foreach preparation. Verify one value binding and the published iteration contract. Range, general iterators, loop else clauses and Python's after-loop binding lifetime need separate decisions. |
| 3. Records and typed failures | Bounded dot access and class construction, followed by raise, typed except and finally | Settle constructor and self spelling and typed exception binding before implementation. Reuse shared class identity, typed catches, rethrow and cleanup. Dynamic attributes and interpreter exception behavior are outside this batch. |

### Decisions before widening semantics

- Division and modulo stay deferred. Agree floating quotient precision, bounded integer floor division, zero divisors and minimum-integer overflow before exposing the Python operators. Consider modulo with floor division because Python couples quotient and remainder.
- Keep typed declarations and shared block scope. Bare first assignments, optional-value spelling, operand-valued and/or and chained comparisons require explicit contracts. Chains need short-circuiting and single evaluation of the middle operand.
- Keep project discovery and ordinary symbol lookup. Decide Python import spelling and module mapping together. If we add print syntax, settle separators, newline, formatting and evaluation before selecting an existing output binding.
- Defer comprehensions, decorators, generators, pattern matching and Python package interoperability until an application needs them.

### Batch 1 ownership notes

- The shared ternary uses parser-owned start and end token positions so its source span includes the true arm and closing parentheses on a grouped false arm. Preparation and emission still evaluate the condition first.
- Pass uses an empty shared block statement. Its scope contains no declarations or effects, and its source span includes the pass token and newline.
- Construct the existing break, continue and return nodes after consuming NEWLINE. Share loop-depth and return-contract diagnostics with preparation. Keep Python exits unlabelled and use the existing void return annotation.

### Batch 1 acceptance

- [x] Check conditional expression precedence with not/and/or, right association, grouping, complete source spans and single evaluation of each selected arm. Reject mismatched branch types.
- [x] Check a pass-only suite and its source span, nested loop exits with an observable skipped iteration, invalid exits outside a loop, and bare return acceptance in void functions and rejection in value-returning functions.
- [x] Run a common native matrix with cpp/php/js peers, including a body edit, preservation of the prior bundle after an invalid edit, and recovery matching a fresh build. Run the affected shared regressions and confirm division remains deferred.
- [x] Review the batch together. Pause for a new language contract or cross-owner refactor that requires agreement.

Batch 1 passed 10 focused CTests with Clang, covering the Python frontend, the native cpp/php/js peer matrix, and shared ternary, logical, loop and transfer behavior. The resident matrix verified unchanged peer reuse after a Python edit, execution of both conditional arms, and publication preservation after a Python branch-type error or a peer signature edit. Both failure paths recovered to fresh-equivalent artifacts and native behavior. The remaining batches and semantic decisions above stay deferred.
