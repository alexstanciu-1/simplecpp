# 40 — Shared, unique, weak and inline/value ownership
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Item { public int $value = 7; }
$owner = new Item();
$observer = weak($owner);
$held = lock($observer);
return $held->value;
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

class Item;

class Item : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> value = static_cast<int_t<> >(7);
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t Item::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Item::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/40/main.phs", 0);
	auto owner = create<Item>();
	auto observer = weak(owner);
	auto held = lock(observer);
	return held->value;
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
	scpp::int_t<> field_value = static_cast<scpp::int_t<>>(7LL);
public:
};


// === entry.cpp ===
#include "types/n4974656d.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/weak_p.hpp"

int main()
{
	auto local_owner = ::scpp::shared<record_Item>();
	auto local_observer = ::scpp::weak(local_owner);
	auto local_held = (local_observer).lock();
	return static_cast<int>(((local_held)->field_value).native_value());
	return 0;
}
```

