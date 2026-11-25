# 28 — Container reads, direct/nested writes and appends
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$rows vector<vector<int>> = [[1, 2]];
$rows[0][1] = 7;
$rows[] = [3, 4];
return $rows[0][1];
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/28/main.phs", 0);
	vector_t<vector_t<int_t<>>> rows = required_cast<vector_t<vector_t<int_t<>>>>(vector_t<vector_t<int_t<>>>{vector_t<int_t<>>{static_cast<int_t<> >(1), static_cast<int_t<> >(2)}});
	rows.at(static_cast<int_t<> >(0)).at(static_cast<int_t<> >(1)) = static_cast<int_t<> >(7);
	(void) rows.push_back(mixed_t{shared_table_(table_item_(static_cast<int_t<> >(3)), table_item_(static_cast<int_t<> >(4)))});
	return rows.at(static_cast<int_t<> >(0)).at(static_cast<int_t<> >(1));
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
#include "scpp/index_write.hpp"
#include "scpp/index_write_path.hpp"
#include "scpp/index_read.hpp"

int main()
{
	scpp::vector_t<scpp::vector_t<scpp::int_t<>>> local_rows = ([&]() -> scpp::vector_t<scpp::vector_t<scpp::int_t<>>> {
scpp::initializer_buffer<scpp::vector_t<scpp::int_t<>>> initializer_0(1);
{
scpp::vector_t<scpp::int_t<>> initializer_value_1 = ([&]() -> scpp::vector_t<scpp::int_t<>> {
scpp::initializer_buffer<scpp::int_t<>> initializer_2(2);
{
scpp::int_t<> initializer_value_3 = static_cast<scpp::int_t<>>(1LL);
initializer_2.append(initializer_value_3);
}
{
scpp::int_t<> initializer_value_4 = static_cast<scpp::int_t<>>(2LL);
initializer_2.append(initializer_value_4);
}
return scpp::data_initializer<scpp::vector_t<scpp::int_t<>>>::from_list(initializer_2.view());
}());
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<scpp::vector_t<scpp::int_t<>>>>::from_list(initializer_0.view());
}());
	{ auto& write_receiver_5 = local_rows; scpp::int_t<> write_path_key_5_0 = static_cast<scpp::int_t<>>(0LL); scpp::require_write_key(write_path_key_5_0); scpp::int_t<> write_key_5 = static_cast<scpp::int_t<>>(1LL); scpp::require_write_key(write_key_5); scpp::int_t<> write_value_5 = static_cast<scpp::int_t<>>(7LL); { const scpp::vector_t<scpp::int_t<>>& write_probe_5_0 = scpp::index_path_probe(write_receiver_5, write_path_key_5_0); scpp::index_path_check_store(write_probe_5_0, write_key_5, write_value_5); } scpp::vector_t<scpp::int_t<>>& write_target_5_0 = scpp::index_path_descend(write_receiver_5, write_path_key_5_0); scpp::index_store(write_target_5_0, write_key_5, write_value_5); }
	{ auto& write_receiver_6 = local_rows; scpp::vector_t<scpp::int_t<>> write_value_6 = ([&]() -> scpp::vector_t<scpp::int_t<>> {
scpp::initializer_buffer<scpp::int_t<>> initializer_7(2);
{
scpp::int_t<> initializer_value_8 = static_cast<scpp::int_t<>>(3LL);
initializer_7.append(initializer_value_8);
}
{
scpp::int_t<> initializer_value_9 = static_cast<scpp::int_t<>>(4LL);
initializer_7.append(initializer_value_9);
}
return scpp::data_initializer<scpp::vector_t<scpp::int_t<>>>::from_list(initializer_7.view());
}()); scpp::index_append(write_receiver_6, write_value_6); }
	return static_cast<int>((([&]() -> scpp::int_t<> { const auto& index_receiver_10 = ([&]() -> scpp::vector_t<scpp::int_t<>> { const auto& index_receiver_11 = local_rows; scpp::int_t<> index_key_11 = static_cast<scpp::int_t<>>(0LL); return scpp::checked_index_read(index_receiver_11, index_key_11); }()); scpp::int_t<> index_key_10 = static_cast<scpp::int_t<>>(1LL); return scpp::checked_index_read(index_receiver_10, index_key_10); }())).native_value());
	return 0;
}
```

