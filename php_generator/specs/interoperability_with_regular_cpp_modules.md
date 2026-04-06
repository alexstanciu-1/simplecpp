# Interoperability with regular C++ modules

## Goal
Generated Prism++ code should remain linkable from ordinary handwritten C++ modules without leaking PHP-runtime convenience imports through generated headers.

## Current rule
- generated `.cpp` namespace blocks may contain:
  ```cpp
  namespace scpp {
  	using namespace ::scpp;
  	using namespace ::scpp::php;
  }
  ```
- generated headers must **not** contain `using namespace ::scpp;` or `using namespace ::scpp::php;`
- header-side references to predefined/runtime PHP constants must stay explicit when needed

## Why
Putting `using namespace ::scpp;` or `using namespace ::scpp::php;` in a generated header changes lookup for every translation unit that includes that header. That makes interoperability with ordinary C++ code worse, increases collision risk, and weakens the public API boundary.

Keeping those using-directives in generated `.cpp` files only localizes the convenience imports to implementation code. Handwritten C++ modules can still include generated headers and link against generated code without inheriting the PHP-runtime namespace flood.

## Constant policy
The generator snapshots `get_defined_constants()` once at startup.

- names found in that predefined-runtime snapshot are treated as PHP predefined/runtime constants and lower to unqualified helper/constant names inside generated source because the source namespace block already uses `using namespace ::scpp;` and `using namespace ::scpp::php;`
- generator-emitted runtime/helper references inside generated expression/type code must stay unqualified and must not use rooted `::scpp` / `::scpp::php` forms
- user-defined non-class constants remain in the generated user namespace model
- class constants keep their own class-constant lowering rules

This means constant classification depends on the PHP runtime/version used to execute the generator. The generator runtime therefore needs to stay aligned with the target/test PHP version for predictable output.
