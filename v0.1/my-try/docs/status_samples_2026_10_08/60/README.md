# 60 — Concrete inheritance, parent calls/construction
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Base {
    protected int $value;
    public function __construct(int $value) { $this->value = $value; }
    public function read(): int { return $this->value; }
}
class Child extends Base {
    public function __construct() { parent::__construct(7); }
    public function doubled(): int { $value = parent::read(); return $value * 2; }
}
return (new Child())->doubled();
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
protected:
	int_t<> value;
public:
	Base(int_t<> value);
	virtual int_t<> read();
};

class Child : public Base, public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	Child();
	virtual int_t<> doubled();
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

Base::Base(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("Base::__construct", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/60/main.phs", 3);
	this->value = value;
}

int_t<> Base::read() {
	SCPP_CALL_DEPTH_GUARD("Base::read", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/60/main.phs", 4);
	return this->value;
}

bool_t Child::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Child::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

Child::Child() : Base(static_cast<int_t<> >(7)) {
	SCPP_CALL_DEPTH_GUARD("Child::__construct", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/60/main.phs", 7);
}

int_t<> Child::doubled() {
	SCPP_CALL_DEPTH_GUARD("Child::doubled", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/60/main.phs", 8);
	auto value = Base::read();
	return (value * static_cast<int_t<> >(2));
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/60/main.phs", 0);
	return create<Child>()->doubled();
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
protected:
	scpp::int_t<> field_value;
public:
public:
	record_Base(scpp::int_t<> local_value);
public:
	scpp::int_t<> implementation_Base__method_read();
scpp::int_t<> method_read();
};


// === types/n4368696c64.hpp ===
#pragma once
#include "scpp/int_t.hpp"
#include "types/n42617365.hpp"
struct record_Child : public record_Base
{
public:
public:
	record_Child();
public:
	scpp::int_t<> implementation_Child__method_doubled();
scpp::int_t<> method_doubled();
};


// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "types/n42617365.hpp"
#include "scpp/int_t.hpp"
#include "types/n4368696c64.hpp"
#include "scpp/generated/operators.hpp"
scpp::int_t<> record_Base::method_read() { return record_Base::implementation_Base__method_read(); }
record_Base::record_Base(scpp::int_t<> local_value)
{
	(this)->field_value = local_value;
}

scpp::int_t<> record_Base::implementation_Base__method_read()
{
	return (this)->field_value;
}

scpp::int_t<> record_Child::method_doubled() { return record_Child::implementation_Child__method_doubled(); }
record_Child::record_Child() : record_Base{scpp::int_t<>(static_cast<scpp::int_t<>>(7LL))}
{
}

scpp::int_t<> record_Child::implementation_Child__method_doubled()
{
	auto local_value = ([&]() -> scpp::int_t<> {
auto receiver_0 = this;
	return receiver_0->record_Base::implementation_Base__method_read();
}());
	return (local_value * static_cast<scpp::int_t<>>(2LL));
}


// === entry.cpp ===
#include "types/n4368696c64.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/int_t.hpp"

int main()
{
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto receiver_0 = ([&]() -> ::scpp::shared_p<record_Child> {
	return ::scpp::shared<record_Child>();
}());
(void)receiver_0.operator->();
	return receiver_0->method_doubled();
}())).native_value());
	return 0;
}
```

