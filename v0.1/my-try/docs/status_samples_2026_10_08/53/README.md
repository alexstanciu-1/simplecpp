# 53 — Classes/structs, typed properties, construction and property defaults
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Box {
    public int $value = 2;
    public function __construct(int $initial) { $this->value = $initial; }
}
$box = new Box(7);
return $box->value;
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

class Box;

class Box : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> value = static_cast<int_t<> >(2);
	Box(int_t<> initial);
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t Box::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Box::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

Box::Box(int_t<> initial) {
	SCPP_CALL_DEPTH_GUARD("Box::__construct", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/53/main.phs", 3);
	this->value = initial;
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/53/main.phs", 0);
	auto box = create<Box>(static_cast<int_t<> >(7));
	return box->value;
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

// === types/n426f78.hpp ===
#pragma once
#include "scpp/int_t.hpp"
struct record_Box
{
public:
	scpp::int_t<> field_value = static_cast<scpp::int_t<>>(2LL);
public:
public:
	record_Box(scpp::int_t<> local_initial);
};


// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "types/n426f78.hpp"
#include "scpp/int_t.hpp"
record_Box::record_Box(scpp::int_t<> local_initial)
{
	(this)->field_value = local_initial;
}


// === entry.cpp ===
#include "types/n426f78.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/int_t.hpp"

int main()
{
	auto local_box = ([&]() -> ::scpp::shared_p<record_Box> {
	scpp::int_t<> argument_0 = static_cast<scpp::int_t<>>(7LL);
	return ::scpp::shared<record_Box>(argument_0);
}());
	return static_cast<int>(((local_box)->field_value).native_value());
	return 0;
}
```

