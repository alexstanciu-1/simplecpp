# 13 — Typed signatures, positional calls, returns and terminating recursion
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
function factorial(int $n): int {
    if ($n < 2) { return 1; }
    $previous = factorial($n - 1);
    return $n * $previous;
}
return factorial(4);
```

## S2S generated v1

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

[Complete emitted text](v1.cpp.txt). File separators identify the emitted units.

```cpp
// === main.hpp ===
#pragma once

#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>

namespace scpp {

int_t<> factorial(int_t<> n);

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int_t<> factorial(int_t<> n) {
	SCPP_CALL_DEPTH_GUARD("factorial", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/13/main.phs", 1);
	if (static_cast<bool>((n < static_cast<int_t<> >(2)))) {
		return static_cast<int_t<> >(1);
	}
	auto previous = factorial((n - static_cast<int_t<> >(1)));
	return (n * previous);
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/13/main.phs", 0);
	return factorial(static_cast<int_t<> >(4));
	return 0;
}
}

int main(int __scpp_argc, char** __scpp_argv) {
	try {
		::scpp::php::set_cli_args(__scpp_argc, __scpp_argv);
		return scpp::__scpp_main();
	} catch (const std::exception &exception) {
		::scpp::print_runtime_exception(exception);
		return 1;
	}
}
```

## S2S generated v2

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

[Complete emitted text](v2.cpp.txt). File separators identify the emitted units.

```cpp
// Readable bundle. File separator comments added by the report renderer.

// === callables/n666163746f7269616c.hpp ===
#pragma once
#include "scpp/int_t.hpp"
scpp::int_t<> function_factorial(scpp::int_t<> local_n);

// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "callables/n666163746f7269616c.hpp"
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
scpp::int_t<> function_factorial(scpp::int_t<> local_n)
{
if (((local_n < static_cast<scpp::int_t<>>(2LL))).native_value()) {
	return static_cast<scpp::int_t<>>(1LL);
}
	auto local_previous = ([&]() -> scpp::int_t<> {
	scpp::int_t<> argument_0 = (local_n - static_cast<scpp::int_t<>>(1LL));
	return function_factorial(argument_0);
}());
	return (local_n * local_previous);
}


// === entry.cpp ===
#include "scpp/int_t.hpp"
#include "callables/n666163746f7269616c.hpp"

int main()
{
	return static_cast<int>((([&]() -> scpp::int_t<> {
	scpp::int_t<> argument_0 = static_cast<scpp::int_t<>>(4LL);
	return function_factorial(argument_0);
}())).native_value());
	return 0;
}
```

