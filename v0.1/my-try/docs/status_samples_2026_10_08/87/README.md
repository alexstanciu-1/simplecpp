# 87 — Safe structural mutation/source replacement during iteration
Doc Status: supporting

Source replacement during iteration is emitted only; no execution or safety guarantee is claimed.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$values vector<int> = [1, 2];
foreach ($values as $value) { $values = [3, 4]; }
return $values[0];
```

## S2S generated v1

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

**Emission does not establish iteration safety.**

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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/87/main.phs", 0);
	vector_t<int_t<>> values = required_cast<vector_t<int_t<>>>(vector_t<int_t<>>{static_cast<int_t<> >(1), static_cast<int_t<> >(2)});
	auto& __scpp_foreach_source_2 = values;
	for (auto __scpp_foreach_entry_2 : foreach_range(__scpp_foreach_source_2)) {
		auto value = __scpp_foreach_entry_2.value_copy();
		values = required_cast<vector_t<int_t<>>>(vector_t<int_t<>>{static_cast<int_t<> >(3), static_cast<int_t<> >(4)});
	}
	return values.at(static_cast<int_t<> >(0));
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

**Emission does not establish iteration safety.**

[Complete emitted text](v2.cpp.txt). File separators identify the emitted units.

```cpp
// Readable bundle. File separator comments added by the report renderer.

// === entry.cpp ===
#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/value_iteration.hpp"
#include "scpp/index_read.hpp"

int main()
{
	scpp::vector_t<scpp::int_t<>> local_values = ([&]() -> scpp::vector_t<scpp::int_t<>> {
scpp::initializer_buffer<scpp::int_t<>> initializer_0(2);
{
scpp::int_t<> initializer_value_1 = static_cast<scpp::int_t<>>(1LL);
initializer_0.append(initializer_value_1);
}
{
scpp::int_t<> initializer_value_2 = static_cast<scpp::int_t<>>(2LL);
initializer_0.append(initializer_value_2);
}
return scpp::data_initializer<scpp::vector_t<scpp::int_t<>>>::from_list(initializer_0.view());
}());
{
const auto& foreach_source_3 = local_values;
for (auto foreach_entry_3 : scpp::value_iteration(foreach_source_3)) {
scpp::int_t<> local_value = foreach_entry_3.value_copy();
{
	local_values = ([&]() -> scpp::vector_t<scpp::int_t<>> {
scpp::initializer_buffer<scpp::int_t<>> initializer_4(2);
{
scpp::int_t<> initializer_value_5 = static_cast<scpp::int_t<>>(3LL);
initializer_4.append(initializer_value_5);
}
{
scpp::int_t<> initializer_value_6 = static_cast<scpp::int_t<>>(4LL);
initializer_4.append(initializer_value_6);
}
return scpp::data_initializer<scpp::vector_t<scpp::int_t<>>>::from_list(initializer_4.view());
}());
}
}
}
	return static_cast<int>((([&]() -> scpp::int_t<> { const auto& index_receiver_7 = local_values; scpp::int_t<> index_key_7 = static_cast<scpp::int_t<>>(0LL); return scpp::checked_index_read(index_receiver_7, index_key_7); }())).native_value());
	return 0;
}
```

