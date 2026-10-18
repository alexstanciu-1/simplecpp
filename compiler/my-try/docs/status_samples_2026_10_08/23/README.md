# 23 — Typed vector/hash construction; nested mixed/container literals
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$values vector<int> = [1, 2];
$table hash<vector<int>> = ["row" => [3, 4]];
$boxed mixed = ["nested" => [1, false]];
return $values[0];
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/23/main.phs", 0);
	vector_t<int_t<>> values = required_cast<vector_t<int_t<>>>(vector_t<int_t<>>{static_cast<int_t<> >(1), static_cast<int_t<> >(2)});
	hash_t<vector_t<int_t<>>> table = required_cast<hash_t<vector_t<int_t<>>>>([&]() -> hash_t<vector_t<int_t<>>> {
	hash_t<vector_t<int_t<>>> __scpp_hash_value{};
	__scpp_hash_value.set(string_t("row"), vector_t<int_t<>>{static_cast<int_t<> >(3), static_cast<int_t<> >(4)});
	return __scpp_hash_value;
}());
	mixed_t boxed = mixed_t{shared_table_(table_kv_(string_t("nested"), mixed_t{shared_table_(table_item_(static_cast<int_t<> >(1)), table_item_(static_cast<bool_t>(false)))}))};
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

[Complete emitted text](v2.cpp.txt). File separators identify the emitted units.

```cpp
// Readable bundle. File separator comments added by the report renderer.

// === entry.cpp ===
#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/bool_t.hpp"
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
	scpp::hash_t<scpp::vector_t<scpp::int_t<>>, scpp::string_t> local_table = ([&]() -> scpp::hash_t<scpp::vector_t<scpp::int_t<>>, scpp::string_t> {
scpp::initializer_buffer<scpp::initializer_entry<scpp::vector_t<scpp::int_t<>>, scpp::string_t>> initializer_3(1);
{
scpp::string_t initializer_key_4 = scpp::string_t("row");
scpp::vector_t<scpp::int_t<>> initializer_value_5 = ([&]() -> scpp::vector_t<scpp::int_t<>> {
scpp::initializer_buffer<scpp::int_t<>> initializer_6(2);
{
scpp::int_t<> initializer_value_7 = static_cast<scpp::int_t<>>(3LL);
initializer_6.append(initializer_value_7);
}
{
scpp::int_t<> initializer_value_8 = static_cast<scpp::int_t<>>(4LL);
initializer_6.append(initializer_value_8);
}
return scpp::data_initializer<scpp::vector_t<scpp::int_t<>>>::from_list(initializer_6.view());
}());
initializer_3.append(scpp::initializer_entry<scpp::vector_t<scpp::int_t<>>, scpp::string_t>{initializer_key_4, initializer_value_5});
}
return scpp::data_initializer<scpp::hash_t<scpp::vector_t<scpp::int_t<>>, scpp::string_t>>::from_key_value(initializer_3.view());
}());
	scpp::mixed_t local_boxed = ([&]() -> scpp::mixed_t {
scpp::initializer_buffer<scpp::initializer_entry<scpp::mixed_t, scpp::mixed_t>> initializer_9(1);
{
scpp::mixed_t initializer_key_10 = scpp::mixed_t{scpp::string_t("nested")};
scpp::mixed_t initializer_value_11 = ([&]() -> scpp::mixed_t {
scpp::initializer_buffer<scpp::mixed_t> initializer_12(2);
{
scpp::mixed_t initializer_value_13 = scpp::mixed_t{static_cast<scpp::int_t<>>(1LL)};
initializer_12.append(initializer_value_13);
}
{
scpp::mixed_t initializer_value_14 = scpp::mixed_t{static_cast<scpp::bool_t>(false)};
initializer_12.append(initializer_value_14);
}
return scpp::data_initializer<scpp::mixed_t>::from_list(initializer_12.view());
}());
initializer_9.append(scpp::initializer_entry<scpp::mixed_t, scpp::mixed_t>{initializer_key_10, initializer_value_11});
}
return scpp::data_initializer<scpp::mixed_t>::from_key_value(initializer_9.view());
}());
	return static_cast<int>((([&]() -> scpp::int_t<> { const auto& index_receiver_15 = local_values; scpp::int_t<> index_key_15 = static_cast<scpp::int_t<>>(0LL); return scpp::checked_index_read(index_receiver_15, index_key_15); }())).native_value());
	return 0;
}
```

