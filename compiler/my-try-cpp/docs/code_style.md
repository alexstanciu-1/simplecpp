# C++ source style
Doc Status: supporting

Applies to handwritten `.cpp` and `.hpp` files in `src/`, `middleware/` and
`tests/`. This adapts the presentation rules from `../../my-try/docs/code_style.md`;
it does not import PHP semantics or change the runtime/middleware boundary.
Generated files, published dependency snapshots and build output are excluded.

## Layout

- Use tabs for indentation, displayed at four columns. Indent continuation lines
  at least one additional tab; avoid alignment to distant opening parentheses.
- Indent everything inside a namespace by one level, including declarations,
  definitions and their comments. Nested namespace blocks add another level.
- Put named function and method opening braces on their own line, including
  constructors, destructors and inline definitions.
- For other blocks, put the opening brace on the header line when the body has
  at most five nonblank code lines, otherwise on its own line. Count nested
  blocks, but exclude the body's own braces and comment-only lines. This applies
  to namespaces, classes, control blocks and lambdas.
- Use braces and multiline bodies even for a single statement. Empty bodies may
  have no interior lines, but their closing brace still occupies its own line.
  Braced value initializers are expressions, not block bodies.
- Put `else`, `catch` and the trailing `while` of a `do` block on a new line.
- Put one statement per line, except the clauses within a `for` header.
- Separate functions and distinct logical steps with one blank line. Group by
  purpose, not a fixed number of statements.
- Prefer lines within 120 columns. Preserve literal contents; long literals may
  exceed that limit. Wrap signatures and calls at meaningful argument boundaries.
- Keep pointers and references next to the declarator: `const string &value`,
  `const char *bytes`. Do not change declarations merely to adjust their layout.
- Preserve include order and include only what the file needs.

## Explicit member access

Always qualify current-instance fields and method calls with `this->`, even when
C++ allows omitting it: `this->modules.size()`, `this->state_`,
`this->byte_size()`. C++ `this` is a pointer, so use `this->member`, not
`this.member`. Access another object through its explicit receiver as usual.

Constructor member-initializer names and member declarations remain bare because
C++ requires that syntax. Static members have no `this`; qualify their uses with
the owning class (`Model::ready_`, `Model::catalogs()`). Locals, parameters and
free functions do not take a `this->` prefix. Preserve `*this` when returning or
passing the current object.

Apply this convention in generators too; regenerate their output instead of
editing generated files or published dependencies directly.

## Comments and ordering

Functions with more than five counted body lines need a purpose documentation
comment immediately above their declaration. Short functions need comments when
their intent is unclear. Explain contracts, ownership, invariants or non-obvious
control flow; do not narrate obvious statements.

Multiline documentation uses an opening `/**` on its own line, content lines
prefixed with ` *`, and a closing ` */` on its own line. Keep existing useful
comments. Place comments immediately above the declaration or group they explain.

Prefer initialization, entry points and supporting helpers in useful call-flow
order. Preserve an already clear order and the C++ declaration requirements;
do not move methods between owners or invent wrappers for presentation.

```cpp
static void reject(const char *message, const string &detail)
{
	textbuilder text;
	text.append(message);
	text.append(": ");
	text.append(detail);

	fail(text.finish());
}
```

## Verification

Formatting must preserve literals, statement order and behavior. Review brace
insertion around formerly unbraced conditions and loops. Use the focused build
and tests described in `../README.md`; rebuild middleware explicitly when its
handwritten sources change. Runtime recompilation is not required for this work.

A generic formatter alone does not enforce the body-length brace rule, meaningful
grouping or purpose comments. Review those explicitly; do not claim that ordinary
clang-format output fully implements this style.
