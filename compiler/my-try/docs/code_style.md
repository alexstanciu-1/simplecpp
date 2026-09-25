# PHP and PHP++ writing rules
Doc Status: supporting

Read this before editing active `my-try` PHP or PHP++ code. These rules adopt the
formatting, grouping, comments and method-order guidance from
`/home/alexv/__AI/scpp_compiler_3/docs/code_formatting.md`. They apply to production
code, helpers, tests and PHP tools; generated output and historical evidence are
excluded. They do not import compiler 3's lifecycle or join architecture.

- Separate distinct logical steps with one blank line. Group by purpose rather
  than inserting a blank line at a fixed interval.
- Explain non-obvious purpose, invariants, ownership or control flow immediately
  above the relevant declaration or group. Avoid comments that merely repeat code.
- Functions and methods with more than five counted body lines need a purpose
  documentation comment immediately above the declaration. Type annotations alone
  are not purpose documentation. Short methods need comments when intent is unclear.
- Named function/method opening braces always go on their own line.
- Other opening braces go on the header line for bodies of up to five counted
  lines, and on their own line for longer bodies. This includes classes, control
  blocks and anonymous functions.
- Every block body is multiline, including one-statement bodies. Closing braces
  occupy their own line; enclosing punctuation such as `};` may follow.
- Start `else`, `elseif`, `catch`, `finally`, and the final `while` of a `do` block
  on a new line after the preceding closing brace.
- Count nonblank code lines, including nested blocks but excluding the body's own
  braces and comments. Code plus a comment still counts as one code line.
- Parenthesize each logical operand of `&&`/`||`, except direct calls, and group
  mixed logical operators explicitly. Group arithmetic comparison operands with
  three or more terms (two-term grouping is preferred).
- Order methods by useful call flow: constructor/initialization, entry, supporting
  helpers. Keep existing ownership and an already clear order; do not move methods
  between classes or invent wrappers to satisfy presentation preferences.

Use tabs for block indentation, matching the active compiler sources. Keep required
conversion annotations next to their declarations. Formatting must not rewrite
strings, embedded sample programs or other executable content.

## Verification

Run `python3 compiler/my-try/tools/style_check.py` from the repository root. It
checks block layout and the presence of documentation on longer named functions.
`--write` applies whitespace-only block layout after verifying that PHP tokens,
including comments and string contents, are preserved. It does not generate comments.

The PHP test runner includes this check. Human review still owns meaningful blank
line grouping, comment quality, expression grouping and method order; a mechanical
pass is not a claim that those judgment-based rules have been proved.
