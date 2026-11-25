# 31 — Value and key/value `foreach`
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$values vector<int> = [3, 4];
$sum int = 0;
foreach ($values as $key => $value) { $sum += $key + $value; }
return $sum;
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

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/31/main.phs", 0);
	vector_t<int_t<>> values = required_cast<vector_t<int_t<>>>(vector_t<int_t<>>{static_cast<int_t<> >(3), static_cast<int_t<> >(4)});
	int_t<> sum = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __scpp_foreach_source_3 = values;
	for (auto __scpp_foreach_entry_3 : foreach_range(__scpp_foreach_source_3)) {
		auto&& key = __scpp_foreach_entry_3.key();
		auto value = __scpp_foreach_entry_3.value_copy();
		sum += (key + value);
	}
	return sum;
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

// === entry.cpp ===
#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/value_iteration.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::vector_t<scpp::int_t<>> local_values = ([&]() -> scpp::vector_t<scpp::int_t<>> {
scpp::initializer_buffer<scpp::int_t<>> initializer_0(2);
{
scpp::int_t<> initializer_value_1 = static_cast<scpp::int_t<>>(3LL);
initializer_0.append(initializer_value_1);
}
{
scpp::int_t<> initializer_value_2 = static_cast<scpp::int_t<>>(4LL);
initializer_0.append(initializer_value_2);
}
return scpp::data_initializer<scpp::vector_t<scpp::int_t<>>>::from_list(initializer_0.view());
}());
	scpp::int_t<> local_sum = static_cast<scpp::int_t<>>(0LL);
{
const auto& foreach_source_3 = local_values;
for (auto foreach_entry_3 : scpp::value_iteration(foreach_source_3)) {
scpp::int_t<> local_key = foreach_entry_3.key();
scpp::int_t<> local_value = foreach_entry_3.value_copy();
{
	([&]() -> scpp::int_t<> { auto& target_4 = local_sum; target_4 = (target_4 + (local_key + local_value)); return target_4; }());
}
}
}
	return static_cast<int>((local_sum).native_value());
	return 0;
}
```

