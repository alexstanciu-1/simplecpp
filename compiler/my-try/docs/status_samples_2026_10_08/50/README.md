# 50 — Struct/inline record storage and explicit inline class values
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
struct Point { public int32 $x; }
$point = new Point();
$point->x = 7;
$copy = $point;
return (int) $copy->x;
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

struct Point;

struct Point {
	Point* operator->() { return this; }
	const Point* operator->() const { return this; }
	int_t<std::int32_t> x;
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/50/main.phs", 0);
	auto point = Point{};
	point->x = static_cast<int_t<> >(7);
	auto copy = point;
	return cast<int_t<>>(copy->x);
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

// === types/n506f696e74.hpp ===
#pragma once
#include "scpp/int_t.hpp"
struct record_Point
{
public:
	scpp::int_t<std::int32_t> field_x;
public:
};


// === entry.cpp ===
#include "types/n506f696e74.hpp"
#include "scpp/int_t.hpp"
#include "scpp/cast.hpp"

int main()
{
	auto local_point = record_Point{};
	(local_point).field_x = static_cast<scpp::int_t<std::int32_t>>((static_cast<scpp::int_t<>>(7LL)).native_value());
	auto local_copy = local_point;
	return static_cast<int>((scpp::cast<scpp::int_t<>>((local_copy).field_x)).native_value());
	return 0;
}
```

