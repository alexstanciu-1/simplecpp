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
