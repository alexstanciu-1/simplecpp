# 54 — Instance methods, visibility, static methods/properties/constants
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Counter {
    private int $value = 1;
    public static int $total = 2;
    public const EXTRA = 3;
    public function read(): int { return $this->value; }
    public static function extra(): int { $total = self::$total; return $total + self::EXTRA; }
}
$counter = new Counter();
$value = $counter->read();
$extra = Counter::extra();
return $value + $extra;
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

class Counter;

class Counter : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
private:
	int_t<> value = static_cast<int_t<> >(1);
public:
	static int_t<> total;
	static inline const auto EXTRA = static_cast<int_t<> >(3);
	virtual int_t<> read();
	static int_t<> extra();
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int_t<> Counter::total = static_cast<int_t<> >(2);

bool_t Counter::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Counter::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

int_t<> Counter::read() {
	SCPP_CALL_DEPTH_GUARD("Counter::read", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/54/main.phs", 5);
	return this->value;
}

int_t<> Counter::extra() {
	SCPP_CALL_DEPTH_GUARD("Counter::extra", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/54/main.phs", 6);
	auto total = Counter::total;
	return (total + Counter::EXTRA);
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/54/main.phs", 0);
	auto counter = create<Counter>();
	auto value = counter->read();
	auto extra = Counter::extra();
	return (value + extra);
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

// === types/n436f756e746572.hpp ===
#pragma once
#include "scpp/int_t.hpp"
struct record_Counter
{
private:
	scpp::int_t<> field_value = static_cast<scpp::int_t<>>(1LL);
public:
	static scpp::int_t<> field_total;
public:
public:
	scpp::int_t<> implementation_Counter__method_read();
scpp::int_t<> method_read();
public:
	static scpp::int_t<> implementation_extra_for_type_Counter();
};


// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "types/n436f756e746572.hpp"
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
scpp::int_t<> record_Counter::method_read() { return record_Counter::implementation_Counter__method_read(); }
scpp::int_t<> record_Counter::field_total = static_cast<scpp::int_t<>>(2LL);
scpp::int_t<> record_Counter::implementation_Counter__method_read()
{
	return (this)->field_value;
}

scpp::int_t<> record_Counter::implementation_extra_for_type_Counter()
{
	auto local_total = record_Counter::field_total;
	return (local_total + static_cast<scpp::int_t<>>(3LL));
}


// === entry.cpp ===
#include "types/n436f756e746572.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	auto local_counter = ::scpp::shared<record_Counter>();
	auto local_value = ([&]() -> scpp::int_t<> {
auto receiver_0 = local_counter;
(void)receiver_0.operator->();
	return receiver_0->method_read();
}());
	auto local_extra = ([&]() -> scpp::int_t<> {
	return record_Counter::implementation_extra_for_type_Counter();
}());
	return static_cast<int>(((local_value + local_extra)).native_value());
	return 0;
}
```

