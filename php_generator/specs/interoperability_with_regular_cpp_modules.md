# Interoperability with regular C++ modules

## Goal
Generated Prism++ code should remain linkable from ordinary handwritten C++ modules without leaking PHP-runtime convenience imports through generated headers.

## Current rule
- generated `.cpp` namespace blocks may contain:
  ```cpp
  namespace scpp {
  	using namespace ::scpp;
  }
  ```
- generated headers must **not** contain `using namespace ::scpp;` or `using namespace ::scpp::php;`
- header-side references to predefined/runtime PHP constants must stay explicit when needed

## Why
Putting `using namespace ::scpp;` or `using namespace ::scpp::php;` in a generated header changes lookup for every translation unit that includes that header. That makes interoperability with ordinary C++ code worse, increases collision risk, and weakens the public API boundary.

Keeping that `using namespace ::scpp;` directive in generated `.cpp` files localizes the convenience import to implementation code. Handwritten C++ modules can still include generated headers and link against generated code without inheriting the PHP-runtime namespace flood.

## Constant policy
The generator snapshots `get_defined_constants()` once at startup.

- names found in that predefined-runtime snapshot are treated as PHP predefined/runtime constants and lower to unqualified helper/constant names inside generated source because the source namespace block already uses `using namespace ::scpp;``
- generator-emitted runtime/helper references inside generated expression/type code must use the relative `php::...` form when they are listed in `php_generator/specs/php_runtime_symbols.json`, and must not use rooted `::scpp` / `::scpp::php` forms
- user-defined non-class constants remain in the generated user namespace model
- class constants keep their own class-constant lowering rules

This means constant classification depends on the PHP runtime/version used to execute the generator. The generator runtime therefore needs to stay aligned with the target/test PHP version for predictable output.


## Runtime Symbol Registry (scpp::php)

Any runtime function defined under `scpp::php` that is intended to be callable from transpiled PHP code **must be registered** in:

`php_generator/specs/php_runtime_symbols.json`

The S2S generator uses this registry to qualify calls as:

    php::function_name(...)

### Precedence
User-defined PHP functions take precedence over runtime symbols with the same name.  
The registry is only applied when no user-defined function is resolved.

### Important
If a symbol is not present in the registry, the generator will **not** qualify it with `php::`, even if it exists in the runtime.



See: module_inclusion_model.md
