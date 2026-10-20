# 86 — Reference `foreach`, with or without explicit key
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$values vector<int> = [1, 2];
foreach ($values as $key => &$value) { $value += $key; }
return $values[1];
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/86/main.phs", 0);
	vector_t<int_t<>> values = required_cast<vector_t<int_t<>>>(vector_t<int_t<>>{static_cast<int_t<> >(1), static_cast<int_t<> >(2)});
	auto& __scpp_foreach_source_2 = values;
	for (auto __scpp_foreach_entry_2 : foreach_range(__scpp_foreach_source_2)) {
		auto&& key = __scpp_foreach_entry_2.key();
		__scpp_foreach_entry_2.value_ref() += key;
	}
	return values.at(static_cast<int_t<> >(1));
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
S2S generation failed: main.phs: foreach requires a simple value binding; references and destructuring are deferred at main.phs: byte 58
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

