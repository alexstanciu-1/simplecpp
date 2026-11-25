# 04 — Scalar arithmetic, comparisons, boolean logic and unary operators
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$left int = 7;
$right int = 3;
$sum = $left + $right * 2;
$ok = ($sum > 4) && !($right === 0);
return $ok ? -$sum : 0;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/04/main.phs", 0);
	int_t<> left = required_cast<int_t<>>(static_cast<int_t<> >(7));
	int_t<> right = required_cast<int_t<>>(static_cast<int_t<> >(3));
	auto sum = (left + (right * static_cast<int_t<> >(2)));
	auto ok = ((sum > static_cast<int_t<> >(4)) && (!php::identical(right, static_cast<int_t<> >(0))));
	return php::ternary_eval([&]() -> decltype(auto) { return ok; }, [&]() -> decltype(auto) { return (-sum); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); });
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
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/operators/identity/strict_identity.hpp"
#include "scpp/bool_t.hpp"

int main()
{
	scpp::int_t<> local_left = static_cast<scpp::int_t<>>(7LL);
	scpp::int_t<> local_right = static_cast<scpp::int_t<>>(3LL);
	auto local_sum = (local_left + (local_right * static_cast<scpp::int_t<>>(2LL)));
	auto local_ok = scpp::bool_t(static_cast<bool>((local_sum > static_cast<scpp::int_t<>>(4LL))) && static_cast<bool>((!scpp::php::identical(local_right, static_cast<scpp::int_t<>>(0LL)))));
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((local_ok).native_value()) { return (-local_sum); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
```

