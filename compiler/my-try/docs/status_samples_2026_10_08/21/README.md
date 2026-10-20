# 21 — Separated mixed/value and dynamic/shared container families, explicit bridges
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$boxed mixed = ["count" => 1];
$shared dynamic = ["count" => 2];
$copy = $boxed;
$alias = $shared;
$alias["count"] = 3;
return (int) $copy["count"];
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/21/main.phs", 0);
	mixed_t boxed = mixed_t{shared_table_(table_kv_(string_t("count"), static_cast<int_t<> >(1)))};
	dynamic_t<> shared = required_cast<dynamic_t<>>(mixed_t{shared_table_(table_kv_(string_t("count"), static_cast<int_t<> >(2)))});
	mixed_t copy = boxed;
	auto alias = shared;
	(*alias)[string_t("count")] = static_cast<int_t<> >(3);
	return cast<int_t<>>(copy.get(string_t("count")));
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
#include "scpp/mixed_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/dynamic_t.hpp"
#include "scpp/index_write.hpp"
#include "scpp/index_read.hpp"
#include "scpp/cast.hpp"

int main()
{
	scpp::mixed_t local_boxed = ([&]() -> scpp::mixed_t {
scpp::initializer_buffer<scpp::initializer_entry<scpp::mixed_t, scpp::mixed_t>> initializer_0(1);
{
scpp::mixed_t initializer_key_1 = scpp::mixed_t{scpp::string_t("count")};
scpp::mixed_t initializer_value_2 = scpp::mixed_t{static_cast<scpp::int_t<>>(1LL)};
initializer_0.append(scpp::initializer_entry<scpp::mixed_t, scpp::mixed_t>{initializer_key_1, initializer_value_2});
}
return scpp::data_initializer<scpp::mixed_t>::from_key_value(initializer_0.view());
}());
	scpp::dynamic_t<> local_shared = ([&]() -> scpp::dynamic_t<> {
scpp::initializer_buffer<scpp::initializer_entry<scpp::dynamic_t<>, scpp::mixed_t>> initializer_3(1);
{
scpp::mixed_t initializer_key_4 = scpp::mixed_t{scpp::string_t("count")};
scpp::dynamic_t<> initializer_value_5 = scpp::dynamic_t<>{static_cast<scpp::int_t<>>(2LL)};
initializer_3.append(scpp::initializer_entry<scpp::dynamic_t<>, scpp::mixed_t>{initializer_key_4, initializer_value_5});
}
return scpp::data_initializer<scpp::dynamic_t<>>::from_key_value(initializer_3.view());
}());
	auto local_copy = local_boxed;
	auto local_alias = local_shared;
	{ auto& write_receiver_6 = local_alias; scpp::mixed_t write_key_6 = scpp::mixed_t{scpp::string_t("count")}; scpp::require_write_key(write_key_6); scpp::dynamic_t<> write_value_6 = scpp::dynamic_t<>{static_cast<scpp::int_t<>>(3LL)}; scpp::index_store(write_receiver_6, write_key_6, write_value_6); }
	return static_cast<int>((scpp::cast<scpp::int_t<>>(([&]() -> scpp::mixed_t { const auto& index_receiver_7 = local_copy; scpp::mixed_t index_key_7 = scpp::mixed_t{scpp::string_t("count")}; return scpp::null_index_read(index_receiver_7, index_key_7); }()))).native_value());
	return 0;
}
```

