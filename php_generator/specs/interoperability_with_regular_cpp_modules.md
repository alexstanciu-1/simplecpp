# Interoperability with regular C++ modules

## Goal
Generated Simple C++ code should remain linkable from ordinary handwritten C++ modules without leaking PHP-runtime convenience imports through generated headers.

## Current rule
- generated `.cpp` namespace blocks may contain:
  ```cpp
  namespace scpp {
  	using namespace ::scpp::php;
  }
  ```
- generated headers must **not** contain `using namespace ::scpp::php;`
- header-side references to predefined/runtime PHP constants must stay explicit when needed

## Why
Putting `using namespace ::scpp::php;` in a generated header changes lookup for every translation unit that includes that header. That makes interoperability with ordinary C++ code worse, increases collision risk, and weakens the public API boundary.

Keeping the using-directive in generated `.cpp` files only localizes the convenience import to implementation code. Handwritten C++ modules can still include generated headers and link against generated code without inheriting the PHP-runtime namespace flood.

## Constant policy
The generator snapshots `get_defined_constants()` once at startup.

- names found in that predefined-runtime snapshot are treated as PHP predefined/runtime constants and lower to `::scpp::php::...`
- user-defined non-class constants remain in the generated user namespace model
- class constants keep their own class-constant lowering rules

This means constant classification depends on the PHP runtime/version used to execute the generator. The generator runtime therefore needs to stay aligned with the target/test PHP version for predictable output.
