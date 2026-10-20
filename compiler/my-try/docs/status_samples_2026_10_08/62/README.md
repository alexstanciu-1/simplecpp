# 62 — Traits, nested trait use and abstract trait requirements
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
trait Has_Value { public function value(): int { return 7; } }
trait Nested { use Has_Value; }
class Item { use Nested; }
return (new Item())->value();
```

## S2S generated v1

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

**Trait declarations emitted as classes; trait use not composed.**

[Complete emitted text](v1.cpp.txt). File separators identify the emitted units.

```cpp
// === main.hpp ===
#pragma once

#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>

namespace scpp {

class Has_Value;
class Item;
class Nested;

class Has_Value : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	virtual int_t<> value();
};

class Nested : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
};

class Item : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t Has_Value::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Has_Value::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

int_t<> Has_Value::value() {
	SCPP_CALL_DEPTH_GUARD("Has_Value::value", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/62/main.phs", 1);
	return static_cast<int_t<> >(7);
}

bool_t Nested::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Nested::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t Item::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Item::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/62/main.phs", 0);
	return create<Item>()->value();
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

// === types/n4974656d.hpp ===
#pragma once
#include "scpp/int_t.hpp"
struct record_Item
{
public:
public:
	scpp::int_t<> implementation_Item__method_value();
scpp::int_t<> method_value();
};


// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "types/n4974656d.hpp"
#include "scpp/int_t.hpp"
scpp::int_t<> record_Item::method_value() { return record_Item::implementation_Item__method_value(); }
scpp::int_t<> record_Item::implementation_Item__method_value()
{
	return static_cast<scpp::int_t<>>(7LL);
}


// === entry.cpp ===
#include "types/n4974656d.hpp"
#include "scpp/memory.hpp"
#include "scpp/int_t.hpp"

int main()
{
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto receiver_0 = ::scpp::shared<record_Item>();
(void)receiver_0.operator->();
	return receiver_0->method_value();
}())).native_value());
	return 0;
}
```

