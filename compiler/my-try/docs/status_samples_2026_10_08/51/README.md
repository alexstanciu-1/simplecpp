# 51 — Unit and integral-backed enums
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
enum Mode { case Ready; case Busy; }
enum Code: uint16 { case Ok = 0; case Failed = 1; }
$mode = Mode::Ready;
$code = Code::Ok;
return $mode === Mode::Ready && $code === Code::Ok ? 0 : 1;
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


enum class Mode : std::uint8_t {
	Ready,
	Busy
};

enum class Code : std::uint16_t {
	Ok = 0,
	Failed = 1
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/51/main.phs", 0);
	auto mode = Mode::Ready;
	auto code = Code::Ok;
	return php::ternary_eval([&]() -> decltype(auto) { return (php::identical(mode, Mode::Ready) && php::identical(code, Code::Ok)); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); });
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

// === types/n4d6f6465.hpp ===
#pragma once
#include "cstdint"
enum class enum_Mode : std::uint8_t {
	case_Ready = 0ULL,
	case_Busy = 1ULL,
};

// === types/n436f6465.hpp ===
#pragma once
#include "cstdint"
enum class enum_Code : std::uint16_t {
	case_Ok = 0ULL,
	case_Failed = 1ULL,
};

// === entry.cpp ===
#include "types/n4d6f6465.hpp"
#include "types/n436f6465.hpp"
#include "scpp/int_t.hpp"
#include "scpp/bool_t.hpp"

int main()
{
	auto local_mode = enum_Mode::case_Ready;
	auto local_code = enum_Code::case_Ok;
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((scpp::bool_t(static_cast<bool>(scpp::bool_t(local_mode == enum_Mode::case_Ready)) && static_cast<bool>(scpp::bool_t(local_code == enum_Code::case_Ok)))).native_value()) { return static_cast<scpp::int_t<>>(0LL); } return static_cast<scpp::int_t<>>(1LL); }())).native_value());
	return 0;
}
```

