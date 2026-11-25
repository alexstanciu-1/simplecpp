# 59 — Explicit `data class` / `data struct` keyed construction
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
data class Point { public int $x; public int $y = 2; }
$point Point = {x: 7};
$x = $point->x;
$y = $point->y;
return $x + $y;
```

## S2S generated v1

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
ParseError: syntax error, unexpected token "class"
```

No C++ emitted. [Raw diagnostic](v1.diagnostic.txt).

## S2S generated v2

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

[Complete emitted text](v2.cpp.txt). File separators identify the emitted units.

```cpp
// Readable bundle. File separator comments added by the report renderer.

// === types/n506f696e74.hpp ===
#pragma once
#include "scpp/int_t.hpp"
#include "utility"
struct record_Point
{
public:
	scpp::int_t<> field_x;
public:
	scpp::int_t<> field_y = static_cast<scpp::int_t<>>(2LL);
public:
	struct scpp_data_tag {};
	record_Point() = default;
	record_Point(scpp_data_tag, scpp::int_t<> input_0, scpp::int_t<> input_1) : field_x(std::move(input_0)), field_y(std::move(input_1)) {}
};


// === entry.cpp ===
#include "utility"
#include "types/n506f696e74.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/int_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	::scpp::shared_p<record_Point> local_point = ([&]() -> ::scpp::shared_p<record_Point> {
scpp::int_t<> record_input_0 = static_cast<scpp::int_t<>>(7LL);
scpp::int_t<> record_default_1 = static_cast<scpp::int_t<>>(2LL);
return ::scpp::shared<record_Point>(record_Point::scpp_data_tag{}, std::move(record_input_0), std::move(record_default_1));
}());
	auto local_x = (local_point)->field_x;
	auto local_y = (local_point)->field_y;
	return static_cast<int>(((local_x + local_y)).native_value());
	return 0;
}
```

