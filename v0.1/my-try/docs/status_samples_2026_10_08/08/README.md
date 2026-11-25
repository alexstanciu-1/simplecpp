# 08 — String concatenation and simple variable interpolation
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$name string = "compiler";
$text string = "Hello $name" . "!";
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/08/main.phs", 0);
	string_t name = required_cast<string_t>(string_t("compiler"));
	string_t text = required_cast<string_t>(((string_t("Hello ") + cast<string_t>(name)) + string_t("!")));
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
#include "scpp/string_t.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/int_t.hpp"

int main()
{
	scpp::string_t local_name = scpp::string_t("compiler");
	scpp::string_t local_text = (([&]() -> scpp::string_t { scpp::string_t interpolation_0; interpolation_0.append(scpp::string_t("Hello ")); interpolation_0.append(local_name); return interpolation_0; }()) + scpp::string_t("!"));
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
```

