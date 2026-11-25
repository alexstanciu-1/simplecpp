# 26 — Empty `new vector<T>()` / `new hash<T,K>()` constructors
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$values = new vector<int>();
$table = new hash<int, string>();
$values[] = 3;
$table["item"] = 4;
return $values[0] + $table["item"];
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/26/main.phs", 0);
	auto values = vector_t<int_t<>>{};
	auto table = hash_t<int_t<>, string_t>{};
	(void) values.push_back(static_cast<int_t<> >(3));
	table[cast<string_t>(string_t("item"))] = static_cast<int_t<> >(4);
	return (values.at(static_cast<int_t<> >(0)) + table.at(cast<string_t>(string_t("item"))));
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

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
S2S generation failed: S2S new requires a source struct or class
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

