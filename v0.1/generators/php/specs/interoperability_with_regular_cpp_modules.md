# Interoperability with regular C++ modules
Doc Status: normative
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
- generator-emitted runtime/helper references inside generated expression/type code must use the relative `scpp`-namespace path recorded in the active profile registry (`generators/php/specs/php_runtime_symbols_legacy.json` or `generators/php/specs/php_runtime_symbols_strict.json`), and must not use rooted `::scpp` / `::scpp::php` forms
- user-defined non-class constants remain in the generated user namespace model
- class constants keep their own class-constant lowering rules

This means constant classification depends on the PHP runtime/version used to execute the generator. The generator runtime therefore needs to stay aligned with the target/test PHP version for predictable output.


## Runtime Symbol Registry (relative to scpp)

Any runtime function intended to be callable from transpiled PHP code through the registry **must be registered** in the active profile file:

`generators/php/specs/php_runtime_symbols_legacy.json`

`generators/php/specs/php_runtime_symbols_strict.json`

The S2S generator uses this registry to emit the registered relative path directly. For example:

    php::function_name(...)

Or, for strict flat visible names:

    fs_is_file(...)  ->  fs::is_file(...)

### Precedence
User-defined PHP functions take precedence over runtime symbols with the same name.  
The registry is only applied when no user-defined function is resolved. Bare source calls may resolve through the registry by unique tail-name match.

### Important
If a symbol is not present in the registry, the generator will **not** rewrite it through the runtime-symbol registry, even if it exists in the runtime.



See: module_inclusion_model.md
