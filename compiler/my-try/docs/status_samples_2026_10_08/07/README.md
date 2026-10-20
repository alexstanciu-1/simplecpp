# 07 — Keyword `and`, `or`, `xor`
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$value bool = true;
$other bool = false;
$both = ($value and $other);
$either = ($value or $other);
$different = ($value xor $other);
return $different ? 1 : 0;
```

## S2S generated v1

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

**Unsupported xor placeholder in emitted text.**

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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/07/main.phs", 0);
	bool_t value = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
	bool_t other = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	auto both = (value && other);
	auto either = (value || other);
	auto different = /* unsupported-binary-op-15 */;
	return php::ternary_eval([&]() -> decltype(auto) { return different; }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); });
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
#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"

int main()
{
	scpp::bool_t local_value = static_cast<scpp::bool_t>(true);
	scpp::bool_t local_other = static_cast<scpp::bool_t>(false);
	auto local_both = scpp::bool_t(static_cast<bool>(local_value) && static_cast<bool>(local_other));
	auto local_either = scpp::bool_t(static_cast<bool>(local_value) || static_cast<bool>(local_other));
	auto local_different = ([&]() -> scpp::bool_t { bool operand_0 = static_cast<bool>(local_value); bool operand_1 = static_cast<bool>(local_other); return scpp::bool_t(operand_0 != operand_1); }());
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((local_different).native_value()) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
```

