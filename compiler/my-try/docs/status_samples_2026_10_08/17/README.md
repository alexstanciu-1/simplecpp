# 17 — Scalar casts and stabilization from `mixed` into typed scalar destinations
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$boxed mixed = 7;
$number int = $boxed;
$text string = (string) $number;
return (int) $text;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/17/main.phs", 0);
	mixed_t boxed = static_cast<int_t<> >(7);
	int_t<> number = required_cast<int_t<>>(required_cast<int_t<>>(boxed));
	string_t text = required_cast<string_t>(cast<string_t>(number));
	return cast<int_t<>>(text);
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
#include "scpp/cast.hpp"
#include "scpp/string_t.hpp"

int main()
{
	scpp::mixed_t local_boxed = scpp::mixed_t{static_cast<scpp::int_t<>>(7LL)};
	scpp::int_t<> local_number = scpp::required_cast<scpp::int_t<>>(local_boxed);
	scpp::string_t local_text = scpp::cast<scpp::string_t>(local_number);
	return static_cast<int>((scpp::cast<scpp::int_t<>>(local_text)).native_value());
	return 0;
}
```

