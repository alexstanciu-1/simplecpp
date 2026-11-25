# 84 — Nonfatal reference-return/performance advice
Doc Status: supporting

Reference-return/copy source shape only. S2S output does not establish advisory delivery; STAN was not invoked.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
function &identity(int &$value): int { return $value; }
$value int = 7;
$alias =& identity($value);
$copy int = $value;
return $copy;
```

## S2S generated v1

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

**S2S only; advisory/STAN behavior not measured.**

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
int_t<>& _norm_identity__value(T_value&& _value) {
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

int_t<>& identity__exec(int_t<>& value);

template <typename T_value>
int_t<>& identity(T_value&& _value) {
int_t<>& value = _norm_identity__value(std::forward<T_value>(_value));
	return identity__exec(value);
}

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int_t<>& identity__exec(int_t<>& value) {
	SCPP_CALL_DEPTH_GUARD("identity__exec", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/84/main.phs", 1);
	return value;
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/84/main.phs", 0);
	int_t<> value = required_cast<int_t<>>(static_cast<int_t<> >(7));
	auto& alias = identity(value);
	int_t<> copy = required_cast<int_t<>>(value);
	return copy;
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

**S2S only; advisory behavior not measured.**

[Complete emitted text](v2.cpp.txt). File separators identify the emitted units.

```cpp
// Readable bundle. File separator comments added by the report renderer.

// === callables/n6964656e74697479.hpp ===
#pragma once
#include "scpp/int_t.hpp"
scpp::int_t<>& function_identity(scpp::int_t<>& local_value);

// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "callables/n6964656e74697479.hpp"
#include "scpp/int_t.hpp"
scpp::int_t<>& function_identity(scpp::int_t<>& local_value)
{
	return local_value;
}


// === entry.cpp ===
#include "scpp/int_t.hpp"
#include "callables/n6964656e74697479.hpp"

int main()
{
	scpp::int_t<> local_value = static_cast<scpp::int_t<>>(7LL);
	scpp::int_t<>& local_alias = ([&]() -> scpp::int_t<>& {
	scpp::int_t<>& argument_0 = local_value;
	return function_identity(argument_0);
}());
	scpp::int_t<> local_copy = local_value;
	return static_cast<int>((local_copy).native_value());
	return 0;
}
```

