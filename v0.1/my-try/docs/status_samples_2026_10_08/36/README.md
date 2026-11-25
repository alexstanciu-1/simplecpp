# 36 — Boxed JSON source calls (`json_decode` / `json_encode`)
Doc Status: supporting

Current v2 json_decode uses dynamic; json_decode_mixed is the separate mixed-family API. Same dynamic source sent to v1; C++ validity remains untested.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$value dynamic;
$failure error;
$text string = "";
if (!take($value, $failure, json_decode("{\"n\":7}"))) { return 1; }
if (!take($text, $failure, json_encode($value))) { return 2; }
return 0;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/36/main.phs", 0);
	dynamic_t<> value;
	error_t failure;
	string_t text = required_cast<string_t>(string_t(""));
	if (static_cast<bool>((!php::take(value, failure, json::decode(string_t("{\"n\":7}")))))) {
		return static_cast<int_t<> >(1);
	}
	if (static_cast<bool>((!php::take(text, failure, json::encode(value))))) {
		return static_cast<int_t<> >(2);
	}
	return static_cast<int_t<> >(0);
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
#include "scpp/dynamic_t.hpp"
#include "scpp/error_t.hpp"
#include "scpp/string_t.hpp"
#include "lang/php/support/php_take.hpp"
#include "scpp/result.hpp"
#include "modules/json/json.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/int_t.hpp"

int main()
{
	scpp::dynamic_t<> local_value;
	scpp::error_t local_failure;
	scpp::string_t local_text = scpp::string_t("");
if (((!([&]() -> scpp::bool_t { auto take_source_1 = ([&]() -> scpp::result<scpp::dynamic_t<>> {
	scpp::string_t argument_0 = scpp::string_t("{\"n\":7}");
	return scpp::json::decode(argument_0);
}()); return scpp::php::take(local_value, local_failure, take_source_1); }()))).native_value()) {
	return static_cast<int>((static_cast<scpp::int_t<>>(1LL)).native_value());
}
if (((!([&]() -> scpp::bool_t { auto take_source_3 = ([&]() -> scpp::result<scpp::string_t> {
	scpp::dynamic_t<> argument_2 = local_value;
	return scpp::json::encode(argument_2);
}()); return scpp::php::take(local_text, local_failure, take_source_3); }()))).native_value()) {
	return static_cast<int>((static_cast<scpp::int_t<>>(2LL)).native_value());
}
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
```

