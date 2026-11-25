# 22 — Mixed arithmetic, comparisons, logical operators and text updates
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$left mixed = 4;
$right mixed = 2;
$sum mixed = $left + $right;
$text mixed = "a";
$text .= "b";
return $sum === 6 ? 0 : 1;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/22/main.phs", 0);
	mixed_t left = static_cast<int_t<> >(4);
	mixed_t right = static_cast<int_t<> >(2);
	mixed_t sum = (left + right);
	mixed_t text = string_t("a");
	text = (text + string_t("b"));
	return php::ternary_eval([&]() -> decltype(auto) { return php::identical(sum, static_cast<int_t<> >(6)); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); });
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
#include "scpp/mixed_t.hpp"
#include "operators/arithmetic/boxed_numeric.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/string_t.hpp"
#include "lang/php/support/php_string.hpp"
#include "lang/php/operators/identity/strict_identity.hpp"

int main()
{
	scpp::mixed_t local_left = scpp::mixed_t{static_cast<scpp::int_t<>>(4LL)};
	scpp::mixed_t local_right = scpp::mixed_t{static_cast<scpp::int_t<>>(2LL)};
	scpp::mixed_t local_sum = (local_left + local_right);
	scpp::mixed_t local_text = scpp::mixed_t{scpp::string_t("a")};
	([&]() -> scpp::mixed_t { auto& target_0 = local_text; target_0 = scpp::mixed_t{(scpp::php::to_string(target_0) + scpp::string_t("b"))}; return target_0; }());
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((scpp::php::identical(local_sum, static_cast<scpp::int_t<>>(6LL))).native_value()) { return static_cast<scpp::int_t<>>(0LL); } return static_cast<scpp::int_t<>>(1LL); }())).native_value());
	return 0;
}
```

