# 57 — Destructors and automatic cleanup
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Lifetime {
    public static int $destroyed = 0;
    public function __destruct() { self::$destroyed++; }
}
function scope(): void { $object = new Lifetime(); }
scope();
return Lifetime::$destroyed;
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

class Lifetime;

class Lifetime : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	static int_t<> destroyed;
	~Lifetime();
};

void scope();

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int_t<> Lifetime::destroyed = static_cast<int_t<> >(0);

bool_t Lifetime::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Lifetime::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

Lifetime::~Lifetime() {
	SCPP_CALL_DEPTH_GUARD("Lifetime::__destruct", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/57/main.phs", 3);
	(Lifetime::destroyed++);
}

void scope() {
	SCPP_CALL_DEPTH_GUARD("scope", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/57/main.phs", 5);
	auto object = create<Lifetime>();
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/57/main.phs", 0);
	scope();
	return Lifetime::destroyed;
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

// === types/n4c69666574696d65.hpp ===
#pragma once
#include "scpp/int_t.hpp"
struct record_Lifetime
{
public:
	static scpp::int_t<> field_destroyed;
public:
public:
	~record_Lifetime() noexcept;
public:
	record_Lifetime() = default;
	record_Lifetime(const record_Lifetime&) = default;
	record_Lifetime(record_Lifetime&&) = default;
	record_Lifetime& operator=(const record_Lifetime&) = default;
	record_Lifetime& operator=(record_Lifetime&&) = default;
};


// === callables/n73636f7065.hpp ===
#pragma once
void function_scope();

// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "types/n4c69666574696d65.hpp"
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
#include "callables/n73636f7065.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
scpp::int_t<> record_Lifetime::field_destroyed = static_cast<scpp::int_t<>>(0LL);
record_Lifetime::~record_Lifetime() noexcept
{
	static_cast<scpp::int_t<>>(record_Lifetime::field_destroyed++);
}

void function_scope()
{
	auto local_object = ::scpp::shared<record_Lifetime>();
}


// === entry.cpp ===
#include "callables/n73636f7065.hpp"
#include "types/n4c69666574696d65.hpp"

int main()
{
	([&]() -> void {
	return function_scope();
}());
	return static_cast<int>((record_Lifetime::field_destroyed).native_value());
	return 0;
}
```

