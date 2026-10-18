# 37 — Explicit reference parameters and stable local aliases
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
function bump(int &$value): void { $value++; }
$value int = 1;
$alias =& $value;
bump($alias);
return $value;
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

template <typename T_value>
int_t<>& _norm_bump__value(T_value&& _value) {
	using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_value>>;
	if constexpr (std::is_same_v<_norm_arg_t, int_t<>>) {
		return _value;
	}
	else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
		if (static_cast<bool>(_value.is_int())) {
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $value");
		}
		throw std::runtime_error("Unsupported runtime kind for normalized parameter $value");
	} else {
		static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
	}
}

void bump__exec(int_t<>& value);

template <typename T_value>
void bump(T_value&& _value) {
int_t<>& value = _norm_bump__value(std::forward<T_value>(_value));
	bump__exec(value);
}

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

void bump__exec(int_t<>& value) {
	SCPP_CALL_DEPTH_GUARD("bump__exec", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/37/main.phs", 1);
	(value++);
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/37/main.phs", 0);
	int_t<> value = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto& alias = value;
	bump(alias);
	return value;
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

// === callables/n62756d70.hpp ===
#pragma once
#include "scpp/int_t.hpp"
void function_bump(scpp::int_t<>& local_value);

// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "callables/n62756d70.hpp"
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
void function_bump(scpp::int_t<>& local_value)
{
	static_cast<scpp::int_t<>>(local_value++);
}


// === entry.cpp ===
#include "scpp/int_t.hpp"
#include "callables/n62756d70.hpp"

int main()
{
	scpp::int_t<> local_value = static_cast<scpp::int_t<>>(1LL);
	scpp::int_t<>& local_alias = local_value;
	([&]() -> void {
	scpp::int_t<>& argument_0 = local_alias;
	return function_bump(argument_0);
}());
	return static_cast<int>((local_value).native_value());
	return 0;
}
```

