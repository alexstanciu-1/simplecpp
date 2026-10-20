# 56 — Readonly instance-field declarations and write enforcement
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Entry {
    public readonly string $name;
    public function __construct(string $value) { $this->name = $value; }
}
$entry = new Entry("main");
return $entry->name === "main" ? 0 : 1;
```

## S2S generated v1

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

**Readonly qualifier is not preserved/enforced in emitted field.**

[Complete emitted text](v1.cpp.txt). File separators identify the emitted units.

```cpp
// === main.hpp ===
#pragma once

#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>

namespace scpp {

class Entry;

class Entry : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t name;
	Entry(const string_t& value);
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t Entry::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Entry::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

Entry::Entry(const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("Entry::__construct", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/56/main.phs", 3);
	this->name = value;
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/56/main.phs", 0);
	auto entry = create<Entry>(string_t("main"));
	return php::ternary_eval([&]() -> decltype(auto) { return php::identical(entry->name, string_t("main")); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); });
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
S2S generation failed: Member requires public, protected or private visibility and supported modifiers
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

