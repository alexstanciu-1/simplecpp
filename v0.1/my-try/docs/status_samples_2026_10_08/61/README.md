# 61 — Abstract classes, interfaces and explicit virtual/override dispatch
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
interface Reader { public function read(): int; }
abstract class Base implements Reader { abstract public function read(): int; }
class Child extends Base { public function read(): int { return 7; } }
$reader Reader = new Child();
return $reader->read();
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
class Reader;

class Reader : public virtual ::scpp::shared_self {
public:
	virtual ~Reader() = default;
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	virtual int_t<> read() = 0;
};

class Base : public Reader, public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	virtual int_t<> read() = 0;
};

class Child : public Base, public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	virtual int_t<> read();
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

bool_t Child::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Child::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

int_t<> Child::read() {
	SCPP_CALL_DEPTH_GUARD("Child::read", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/61/main.phs", 3);
	return static_cast<int_t<> >(7);
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/61/main.phs", 0);
	shared_p<Reader> reader = create<Child>();
	return reader->read();
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

// === types/n526561646572.hpp ===
#pragma once
#include "scpp/int_t.hpp"
struct record_Reader
{
public:
	virtual scpp::int_t<> dispatch_Reader__method_read() = 0;
scpp::int_t<> method_read();
public:
	virtual ~record_Reader() noexcept = default;
};


// === types/n42617365.hpp ===
#pragma once
#include "scpp/int_t.hpp"
#include "types/n526561646572.hpp"
struct record_Base : public virtual record_Reader
{
public:
public:
	virtual scpp::int_t<> dispatch_Base__method_read() = 0;
scpp::int_t<> method_read();
public:
	scpp::int_t<> dispatch_Reader__method_read() override;
public:
	virtual ~record_Base() noexcept = default;
	record_Base() = default;
	record_Base(const record_Base&) = default;
	record_Base(record_Base&&) = default;
	record_Base& operator=(const record_Base&) = default;
	record_Base& operator=(record_Base&&) = default;
};


// === types/n4368696c64.hpp ===
#pragma once
#include "scpp/int_t.hpp"
#include "types/n42617365.hpp"
struct record_Child : public record_Base
{
public:
public:
	scpp::int_t<> implementation_Child__method_read();
	virtual scpp::int_t<> dispatch_Child__method_read();
scpp::int_t<> method_read();
public:
	scpp::int_t<> dispatch_Base__method_read() override;
public:
	scpp::int_t<> dispatch_Reader__method_read() override;
public:
	virtual ~record_Child() noexcept = default;
	record_Child() = default;
	record_Child(const record_Child&) = default;
	record_Child(record_Child&&) = default;
	record_Child& operator=(const record_Child&) = default;
	record_Child& operator=(record_Child&&) = default;
};


// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "types/n4368696c64.hpp"
#include "scpp/int_t.hpp"
#include "types/n526561646572.hpp"
#include "types/n42617365.hpp"
scpp::int_t<> record_Reader::method_read() { return dispatch_Reader__method_read(); }
scpp::int_t<> record_Base::method_read() { return dispatch_Base__method_read(); }
scpp::int_t<> record_Base::dispatch_Reader__method_read() { return dispatch_Base__method_read(); }
scpp::int_t<> record_Child::dispatch_Child__method_read() { return record_Child::implementation_Child__method_read(); }
scpp::int_t<> record_Child::method_read() { return dispatch_Child__method_read(); }
scpp::int_t<> record_Child::dispatch_Base__method_read() { return dispatch_Child__method_read(); }
scpp::int_t<> record_Child::dispatch_Reader__method_read() { return dispatch_Child__method_read(); }
scpp::int_t<> record_Child::implementation_Child__method_read()
{
	return static_cast<scpp::int_t<>>(7LL);
}


// === entry.cpp ===
#include "types/n4368696c64.hpp"
#include "scpp/memory.hpp"
#include "types/n526561646572.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/int_t.hpp"

int main()
{
	::scpp::shared_p<record_Reader> local_reader = ::scpp::shared_p<record_Reader>{::scpp::shared<record_Child>()};
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto receiver_0 = local_reader;
(void)receiver_0.operator->();
	return receiver_0->method_read();
}())).native_value());
	return 0;
}
```

