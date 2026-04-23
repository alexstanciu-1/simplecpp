# PHP generator sample set
Doc Status: supporting
This set is designed to exercise the currently decided rules in `specs/rules_catalog.md`.

## Files

- `01_literals_and_assignments.php` - literals, first assignment, reassignment, variable chains
- `02_functions_basic.php` - basic free functions and simple call flow
- `03_namespace_exec_ok.php` - executable flow in one namespace
- `04_namespace_nested_decl_only.php` - parent execution with nested declaration-only namespace
- `05_class_basic.php` - same-namespace class construction
- `06_class_static_access.php` - same-namespace, rooted, and instance-based static access
- `07_typed_locals_phpdoc.php` - explicit typed local variables via PHPDoc comments
- `08_references.php` - explicit reference params and returns
- `09_constants_and_strings.php` - constants and strings
- `10_negative_cases.php` - intentionally rejected constructs

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


Generator namespace/import note:
- generated `.cpp` namespace blocks inject `using namespace ::scpp;` and `using namespace ::scpp::php;`
- generator-emitted runtime/helper references inside expression/type code must therefore stay unqualified
- rooted `::scpp` / `::scpp::php` helper references in generated expression/type code are regressions

Assignment-expression note:
- ordinary assignment statements and simple assignment expressions should lower directly without a helper lambda
- helper lambdas are reserved for complex expression contexts where the generator must preserve PHP assignment-value semantics while guaranteeing single evaluation, especially append expressions or larger composed expressions
- nested lvalue chains stay on mutating access for the full target path, so `$x[0]["name"] = "first";` must not lower through `get(...)` on the left-hand side


Array literal note:
- untyped `$x = [];` and `$x = [ ... ];` declare as `mixed_t x = mixed_t{table_(...)}`, not `auto x = table_(...)`, so generated locals expose `append(...)`, `operator[]`, and `get(...)` immediately.
- untyped first-assignment `$x = null;` declares as `mixed_t x = null;`, not `auto x = null;`, so later fat-value operations such as `append(...)`, `operator[]`, and `get(...)` compile against the null-state `mixed_t`.
- nested append writes keep the whole left-hand-side chain mutating: `$x["users"][] = ["name" => "Alice"];` lowers through `x[string_t("users")].append(...)`, never `x.get(string_t("users")).append(...)`.


Direct DIM call arguments
- `add($x[0])` lowers as `add(x[0])`.
- This applies only to ordinary value passing. Direct DIM call arguments are not native-reference bindable by virtue of being direct DIM expressions.
- For read-only intent, prefer an explicit safe-read form in PHP, such as `($x[0] ?? null)`.
