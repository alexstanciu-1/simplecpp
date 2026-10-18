# 15 — Typed trailing variadics
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
function sum(int ...$values): int {
    $total int = 0;
    foreach ($values as $value) { $total += $value; }
    return $total;
}
return sum(1, 2, 3);
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

int_t<> sum(const vector_t<int_t<>>& values);

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int_t<> sum(const vector_t<int_t<>>& values) {
	SCPP_CALL_DEPTH_GUARD("sum", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/15/main.phs", 1);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __scpp_foreach_source_3 = values;
	for (auto __scpp_foreach_entry_3 : foreach_range(__scpp_foreach_source_3)) {
		auto value = __scpp_foreach_entry_3.value_copy();
		total += value;
	}
	return total;
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/15/main.phs", 0);
	return sum(vector_t<int_t<>>{static_cast<int_t<> >(1), static_cast<int_t<> >(2), static_cast<int_t<> >(3)});
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

// === callables/n73756d.hpp ===
#pragma once
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
scpp::int_t<> function_sum(scpp::vector_t<scpp::int_t<>> local_values = scpp::vector_t<scpp::int_t<>>{});

// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "callables/n73756d.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/value_iteration.hpp"
#include "scpp/generated/operators.hpp"
scpp::int_t<> function_sum(scpp::vector_t<scpp::int_t<>> local_values)
{
	scpp::int_t<> local_total = static_cast<scpp::int_t<>>(0LL);
{
const auto& foreach_source_0 = local_values;
for (auto foreach_entry_0 : scpp::value_iteration(foreach_source_0)) {
scpp::int_t<> local_value = foreach_entry_0.value_copy();
{
	([&]() -> scpp::int_t<> { auto& target_1 = local_total; target_1 = (target_1 + local_value); return target_1; }());
}
}
}
	return local_total;
}


// === entry.cpp ===
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "callables/n73756d.hpp"

int main()
{
	return static_cast<int>((([&]() -> scpp::int_t<> {
	scpp::int_t<> argument_0 = static_cast<scpp::int_t<>>(1LL);
	scpp::int_t<> argument_1 = static_cast<scpp::int_t<>>(2LL);
	scpp::int_t<> argument_2 = static_cast<scpp::int_t<>>(3LL);
	return function_sum(scpp::vector_t<scpp::int_t<>>{argument_0, argument_1, argument_2});
}())).native_value());
	return 0;
}
```

