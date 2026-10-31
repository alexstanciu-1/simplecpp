# Echo follow-up debt

Doc Status: planning
Date: 2026-10-30

The user requested a small echo subset to make function execution and later
incremental tests observable. This is implementation debt, not a replacement for
[the output contract](../../../specs/output_v0_2.md).

## Current bounded slice

One operand per statement: existing int64 literals/locals or a quoted string literal.
No implicit separator/newline. Literal bytes, including NUL and UTF-8, are preserved.
Single quotes decode only escaped quote/backslash. Double quotes support backslash,
double quote, dollar, newline, carriage return, tab and isolated zero escapes.
Unsupported double-quoted escapes and unescaped dollar signs diagnose rather than
silently implementing incomplete interpolation. Strings are currently output-only
literals, not a general expression type or local-variable type.

## @todo: return to echo before claiming full output support

- Admit ordered multiple operands, preserving evaluate-once and print-before-next
  semantics; reject an empty list and trailing comma.
- Integrate general string values/variables, complete literal escape handling and
  the separately specified interpolation behavior with their semantic owners.
- Add other output families only through the normative type-admission/formatting
  rules: integer modules, booleans, floats, null, wrappers and dynamic values.
- Replace the bounded backend-owned stdout helpers with the appropriate v0.2
  runtime output API when available. Preserve exact bytes, locale-independent
  integer formatting and output/error ordering. Review buffering and IO failure
  behavior with that API; do not invent user-defined formatting or coercions.
- Add broader runtime error/formatting proofs and unsupported-type diagnostics as
  those types become available. Preserve source-owned lowering and immutable facts.

The initial proof must cover integer variables and literals, exact string bytes,
function call order across source units, no automatic newline, and observable
output change after a function-body edit. Rebuilding after an edit is not evidence
of incremental reuse until the incremental compiler exists.
