# 20 — No-error state, copying and error truthiness
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$failure error;
$copy error = $failure;
return !$copy ? 0 : 1;
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

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/20/main.phs", 0);
	error_t failure;
	error_t copy = required_cast<error_t>(failure);
	return php::ternary_eval([&]() -> decltype(auto) { return (!copy); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); });
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

// === entry.cpp ===
#include "scpp/error_t.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"

int main()
{
	scpp::error_t local_failure;
	scpp::error_t local_copy = local_failure;
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((scpp::bool_t(!(scpp::php::condition_truthy(local_copy).native_value()))).native_value()) { return static_cast<scpp::int_t<>>(0LL); } return static_cast<scpp::int_t<>>(1LL); }())).native_value());
	return 0;
}
```

