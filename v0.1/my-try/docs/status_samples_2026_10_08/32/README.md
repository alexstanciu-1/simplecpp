# 32 — Typed `Storage_Cursor<T>` construction and read-view widening
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Base { public int $value = 3; }
class Child extends Base {}
$rows Storage<Child> = new Storage<Child>();
$rows->append(new Child());
$cursor Storage_Cursor<Base> = new Storage_Cursor<Base>($rows);
$cursor->rewind();
return $cursor->current()->value;
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
	int_t<> value = static_cast<int_t<> >(3);
};

class Child : public Base, public virtual ::scpp::shared_self {
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

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/32/main.phs", 0);
	::scpp::compiler::Storage<Child> rows = required_cast<::scpp::compiler::Storage<Child>>(::scpp::compiler::Storage<Child>());
	int_t<>(rows.append(create<Child>()));
	::scpp::compiler::Storage_Cursor<Base> cursor = required_cast<::scpp::compiler::Storage_Cursor<Base>>(::scpp::compiler::Storage_Cursor<Base>(rows));
	cursor.rewind();
	return cursor.current()->value;
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

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
S2S generation failed: S2S needs one resolved template type for Storage
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

