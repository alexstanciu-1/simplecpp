# PHP generator sample set

This set is designed to exercise the currently decided rules in `specs/rules_catalog.md`.

## Files

- `01_literals_and_assignments.php` — literals, first assignment, reassignment, variable chains
- `02_functions_basic.php` — basic free functions and simple call flow
- `03_namespace_exec_ok.php` — executable flow in one namespace
- `04_namespace_nested_decl_only.php` — parent execution with nested declaration-only namespace
- `05_class_basic.php` — same-namespace class construction
- `06_class_static_access.php` — same-namespace, rooted, and instance-based static access
- `07_typed_locals_phpdoc.php` — explicit typed local variables via PHPDoc comments
- `08_references.php` — explicit reference params and returns
- `09_constants_and_strings.php` — constants and strings
- `10_negative_cases.php` — intentionally rejected constructs

## Suggested use

- Treat files `01` to `09` as positive fixtures.
- Treat file `10` as a negative fixture.
- Export matching AST/token JSON beside each PHP file using the same basename.
- For positive fixtures, compare exact stdout from PHP execution against exact stdout from the generated C++ executable.
- For negative fixtures, keep the existing expectation: generator rejection before C++ compilation.


## `know_how/`

`know_how/` is the parser/exporter behavior folder.

Use it to pin down how the current php-ast JSON exporter actually shapes the AST for tricky constructs before changing lowering logic.

Current confirmed findings:
- `echo a, b, c;` is exported as multiple sibling `AST_ECHO` nodes, not one variadic node.
- `unset($a, $b, $c);` is exported as multiple sibling `AST_UNSET` nodes, not one variadic node.
- `isset($a, $b, $c)` is exported as a boolean-expression tree combining single-operand `AST_ISSET` nodes.
- Parentheses in the tested `echo` / `isset` cases do not introduce an important wrapper node in this exporter.
- Arithmetic grouping must therefore be preserved from the recursive AST structure itself during C++ emission.

Interpolation AST finding:
- interpolated strings are represented as `AST_ENCAPS_LIST`, not as binary concat chains
- generator lowering should join each part in order and cast interpolated non-string values to `string_t` explicitly
- `samples/know_how/` remains the exporter-behavior reference folder for these checks

