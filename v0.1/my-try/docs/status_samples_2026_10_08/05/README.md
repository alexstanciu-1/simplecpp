# 05 — Bitwise/shift operators and compound assignment
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$bits int = 3;
$bits <<= 2;
$bits |= 1;
$bits ^= 2;
return ($bits & 7) >> 1;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/05/main.phs", 0);
	int_t<> bits = required_cast<int_t<>>(static_cast<int_t<> >(3));
	bits <<= static_cast<int_t<> >(2);
	bits |= static_cast<int_t<> >(1);
	bits ^= static_cast<int_t<> >(2);
	return ((bits & static_cast<int_t<> >(7)) >> static_cast<int_t<> >(1));
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
	scpp::int_t<> local_bits = static_cast<scpp::int_t<>>(3LL);
	([&]() -> scpp::int_t<> { auto& target_0 = local_bits; target_0 = (target_0 << static_cast<scpp::int_t<>>(2LL)); return target_0; }());
	([&]() -> scpp::int_t<> { auto& target_1 = local_bits; target_1 = (target_1 | static_cast<scpp::int_t<>>(1LL)); return target_1; }());
	([&]() -> scpp::int_t<> { auto& target_2 = local_bits; target_2 = (target_2 ^ static_cast<scpp::int_t<>>(2LL)); return target_2; }());
	return static_cast<int>((((local_bits & static_cast<scpp::int_t<>>(7LL)) >> static_cast<scpp::int_t<>>(1LL))).native_value());
	return 0;
}
```

