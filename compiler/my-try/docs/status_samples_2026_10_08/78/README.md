# 78 — String byte/text helpers, UTF-8 conversion, search and checked hex decoding
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$text string = "abc";
$length = string_byte_len($text);
$byte = string_byte_at($text, 0);
$part = string_byte_slice($text, 1, 2);
$prefix = str_starts_with($text, "a");
return $length;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/78/main.phs", 0);
	string_t text = required_cast<string_t>(string_t("abc"));
	auto length = str::byte_length(text);
	auto byte = php::string_byte_at(text, static_cast<int_t<> >(0));
	auto part = str::byte_slice(text, static_cast<int_t<> >(1), static_cast<int_t<> >(2));
	auto prefix = str::starts_with(text, string_t("a"));
	return length;
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
S2S generation failed: S2S needs one resolved function for string_byte_len
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

