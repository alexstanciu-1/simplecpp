# 11 — `switch` and `match`
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$value int = 2;
switch ($value) { case 1: $value = 3; break; default: $value = 4; }
$result = match ($value) { 4 => 7, default => 0 };
return $result;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/11/main.phs", 0);
	int_t<> value = required_cast<int_t<>>(static_cast<int_t<> >(2));
	switch ((value).native_value()) {
		case 1:
		value = static_cast<int_t<> >(3);
		break;
		default:
		value = static_cast<int_t<> >(4);
	}
	auto result = ([&]() { auto __match_subject_1 = value; if (static_cast<bool>(php::identical(__match_subject_1, static_cast<int_t<> >(4)))) { 	return static_cast<int_t<> >(7); } return static_cast<int_t<> >(0); }());
	return result;
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

int main()
{
	scpp::int_t<> local_value = static_cast<scpp::int_t<>>(2LL);
switch ((local_value).native_value()) {
case 1LL:
{
	local_value = static_cast<scpp::int_t<>>(3LL);
break;
}
default:
{
	local_value = static_cast<scpp::int_t<>>(4LL);
}
}
	auto local_result = ([&]() -> scpp::int_t<> { const auto match_subject_0 = (local_value).native_value(); if ((match_subject_0 == 4LL)) { return static_cast<scpp::int_t<>>(7LL); } return static_cast<scpp::int_t<>>(0LL); }());
	return static_cast<int>((local_result).native_value());
	return 0;
}
```

