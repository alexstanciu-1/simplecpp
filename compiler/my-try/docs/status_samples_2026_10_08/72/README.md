# 72 — `finally`
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
function cleanup(): int {
    $value int = 1;
    try { return $value; }
    finally { $value = 2; }
}
return cleanup();
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

int_t<> cleanup();

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int_t<> cleanup() {
	SCPP_CALL_DEPTH_GUARD("cleanup", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/72/main.phs", 1);
	int_t<> value = required_cast<int_t<>>(static_cast<int_t<> >(1));
	{
		std::exception_ptr __scpp_pending_exception_1;
		std::optional<int_t<>> __scpp_return_value_3;
		bool __scpp_pending_return_2 = false;
		try {
		if (!__scpp_pending_return_2) {
			__scpp_return_value_3 = value;
			__scpp_pending_return_2 = true;
		}
		}
		catch (...) {
			__scpp_pending_exception_1 = std::current_exception();
		}
	
		{
			value = static_cast<int_t<> >(2);
		}
	
		if (__scpp_pending_exception_1) {
			std::rethrow_exception(__scpp_pending_exception_1);
		}
		if (__scpp_pending_return_2) {
			return *__scpp_return_value_3;
		}
	}
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/72/main.phs", 0);
	return cleanup();
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

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
S2S generation failed: main.phs: Expected ';' at main.phs: byte 54
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

