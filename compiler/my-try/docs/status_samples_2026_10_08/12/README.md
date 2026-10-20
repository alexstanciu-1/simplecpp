# 12 — Ternary, Elvis and null coalescing
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$optional ?int = null;
$fallback = $optional ?? 5;
$chosen = $fallback > 2 ? 1 : 0;
$elvis = $fallback ?: 9;
return $chosen + $elvis;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/12/main.phs", 0);
	nullable<int_t<>> optional = null;
	auto fallback = php::coalesce_eval([&]() -> decltype(auto) { return optional; }, [&]() -> decltype(auto) { return static_cast<int_t<> >(5); });
	auto chosen = php::ternary_eval([&]() -> decltype(auto) { return (fallback > static_cast<int_t<> >(2)); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); });
	auto elvis = ([&]() -> auto { auto __scpp_cond_value = fallback; return php::ternary_eval([&]() -> decltype(auto) { return __scpp_cond_value; }, [&]() -> decltype(auto) { return __scpp_cond_value; }, [&]() -> decltype(auto) { return static_cast<int_t<> >(9); }); }());
	return (chosen + elvis);
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
#include "scpp/null_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_optional = scpp::nullable<scpp::int_t<>>{scpp::null};
	auto local_fallback = ([&]() -> scpp::int_t<> { scpp::nullable<scpp::int_t<>> coalesce_0 = local_optional; if (coalesce_0.has_value().native_value()) { return scpp::int_t<>((coalesce_0).require_value("nullable required-value extraction")); } return static_cast<scpp::int_t<>>(5LL); }());
	auto local_chosen = ([&]() -> scpp::int_t<> { if (((local_fallback > static_cast<scpp::int_t<>>(2LL))).native_value()) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }());
	auto local_elvis = ([&]() -> scpp::int_t<> { scpp::int_t<> conditional_1 = local_fallback; if (scpp::php::condition_truthy(conditional_1).native_value()) { return conditional_1; } return static_cast<scpp::int_t<>>(9LL); }());
	return static_cast<int>(((local_chosen + local_elvis)).native_value());
	return 0;
}
```

