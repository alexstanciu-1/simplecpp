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
