# 18 — Nullable and Result families: success values, extraction, state tests
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
function wrapped(): result<int> { return 7; }
$value int = 0;
$failure error;
if (take($value, $failure, wrapped())) { return $value; }
return 1;
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

result<int_t<>> wrapped();

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

result<int_t<>> wrapped() {
	SCPP_CALL_DEPTH_GUARD("wrapped", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/18/main.phs", 1);
	return static_cast<int_t<> >(7);
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/18/main.phs", 0);
	int_t<> value = required_cast<int_t<>>(static_cast<int_t<> >(0));
	error_t failure;
	if (static_cast<bool>(php::condition_truthy(php::take(value, failure, wrapped())))) {
		return value;
	}
	return static_cast<int_t<> >(1);
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

// === callables/n77726170706564.hpp ===
#pragma once
#include "scpp/int_t.hpp"
#include "scpp/result.hpp"
scpp::result<scpp::int_t<>> function_wrapped();

// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "callables/n77726170706564.hpp"
#include "scpp/int_t.hpp"
#include "scpp/result.hpp"
scpp::result<scpp::int_t<>> function_wrapped()
{
	return scpp::result<scpp::int_t<>>{static_cast<scpp::int_t<>>(7LL)};
}


// === entry.cpp ===
#include "scpp/int_t.hpp"
#include "scpp/error_t.hpp"
#include "lang/php/support/php_take.hpp"
#include "scpp/result.hpp"
#include "callables/n77726170706564.hpp"

int main()
{
	scpp::int_t<> local_value = static_cast<scpp::int_t<>>(0LL);
	scpp::error_t local_failure;
if ((([&]() -> scpp::bool_t { auto take_source_0 = ([&]() -> scpp::result<scpp::int_t<>> {
	return function_wrapped();
}()); return scpp::php::take(local_value, local_failure, take_source_0); }())).native_value()) {
	return static_cast<int>((local_value).native_value());
}
	return static_cast<int>((static_cast<scpp::int_t<>>(1LL)).native_value());
	return 0;
}
```

