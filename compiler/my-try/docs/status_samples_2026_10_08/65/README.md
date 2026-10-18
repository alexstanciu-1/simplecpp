# 65 — Static-context called-type binding and shared factories
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Base { public static function value(): int { return static::number(); } public static function number(): int { return 1; } }
class Child extends Base { public static function number(): int { return 7; } }
return Child::value();
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

class Base;
class Child;

class Base : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	static int_t<> __scpp_static_call_number();

	static int_t<> value();
	static int_t<> number();
};

class Child : public Base, public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	static int_t<> number();
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t Base::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Base::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	else if (__scpp_token == Child::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

int_t<> Base::__scpp_static_call_number() {
	if (::scpp::php::current_static_token_for<Base>() == Base::__scpp_static_token()) {
		return Base::number();
	}
	else if (::scpp::php::current_static_token_for<Base>() == Child::__scpp_static_token()) {
		return Child::number();
	}
	return Base::number();
}

int_t<> Base::value() {
	SCPP_CALL_DEPTH_GUARD("Base::value", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/65/main.phs", 1);
	return ::scpp::php::_static<Base, Base>([&]() -> decltype(auto) { return Base::__scpp_static_call_number(); });
}

int_t<> Base::number() {
	SCPP_CALL_DEPTH_GUARD("Base::number", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/65/main.phs", 1);
	return static_cast<int_t<> >(1);
}

bool_t Child::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Child::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

int_t<> Child::number() {
	SCPP_CALL_DEPTH_GUARD("Child::number", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/65/main.phs", 2);
	return static_cast<int_t<> >(7);
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/65/main.phs", 0);
	return ::scpp::php::_static<Child, Child>([&]() -> decltype(auto) { return Child::value(); });
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

// === types/n42617365.hpp ===
#pragma once
#include "scpp/int_t.hpp"
struct record_Base
{
public:
public:
	static scpp::int_t<> implementation_value_for_type_Child();
};


// === types/n4368696c64.hpp ===
#pragma once
#include "scpp/int_t.hpp"
#include "types/n42617365.hpp"
struct record_Child : public record_Base
{
public:
public:
	static scpp::int_t<> implementation_number_for_type_Child();
};


// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "types/n42617365.hpp"
#include "scpp/int_t.hpp"
#include "types/n4368696c64.hpp"
scpp::int_t<> record_Base::implementation_value_for_type_Child()
{
	return ([&]() -> scpp::int_t<> {
	return record_Child::implementation_number_for_type_Child();
}());
}

scpp::int_t<> record_Child::implementation_number_for_type_Child()
{
	return static_cast<scpp::int_t<>>(7LL);
}


// === entry.cpp ===
#include "scpp/int_t.hpp"
#include "types/n42617365.hpp"

int main()
{
	return static_cast<int>((([&]() -> scpp::int_t<> {
	return record_Base::implementation_value_for_type_Child();
}())).native_value());
	return 0;
}
```

