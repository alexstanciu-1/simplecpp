# 06 — Prefix/postfix updates and assignment expressions
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$value int = 1;
$before = $value++;
$after = ++$value;
$assigned = ($value = 8);
return $before + $after + $assigned;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/06/main.phs", 0);
	int_t<> value = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto before = (value++);
	auto after = (++value);
	auto assigned = (value = static_cast<int_t<> >(8));
	return ((before + after) + assigned);
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
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::int_t<> local_value = static_cast<scpp::int_t<>>(1LL);
	auto local_before = static_cast<scpp::int_t<>>(local_value++);
	auto local_after = static_cast<scpp::int_t<>>(++local_value);
	local_value = static_cast<scpp::int_t<>>(8LL);
	auto local_assigned = local_value;
	return static_cast<int>((((local_before + local_after) + local_assigned)).native_value());
	return 0;
}
```

