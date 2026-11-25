# 19 — `take` extraction
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$optional ?int = 7;
$value int = 0;
if (take($value, $optional)) { return $value; }
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/19/main.phs", 0);
	nullable<int_t<>> optional = static_cast<int_t<> >(7);
	int_t<> value = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>(php::condition_truthy(php::take(value, optional)))) {
		return value;
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
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "lang/php/support/php_take.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_optional = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(7LL)};
	scpp::int_t<> local_value = static_cast<scpp::int_t<>>(0LL);
if ((([&]() -> scpp::bool_t { auto take_source_0 = local_optional; return scpp::php::take(local_value, take_source_0); }())).native_value()) {
	return static_cast<int>((local_value).native_value());
}
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
```

