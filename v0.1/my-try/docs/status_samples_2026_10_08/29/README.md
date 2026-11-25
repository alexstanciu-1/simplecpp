# 29 — Keyed `isset`, `empty`, `unset`
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$table hash<string> = ["x" => "present"];
$present = isset($table["x"]);
$text string = "";
$blank = empty($text);
unset($table["x"]);
return $present && $blank && !isset($table["x"]) ? 0 : 1;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/29/main.phs", 0);
	hash_t<string_t> table = required_cast<hash_t<string_t>>([&]() -> hash_t<string_t> {
	hash_t<string_t> __scpp_hash_value{};
	__scpp_hash_value.set(string_t("x"), string_t("present"));
	return __scpp_hash_value;
}());
	auto present = php::isset(table, string_t("x"));
	string_t text = required_cast<string_t>(string_t(""));
	auto blank = php::empty(text);
	table.remove(string_t("x"));
	return php::ternary_eval([&]() -> decltype(auto) { return ((present && blank) && (!php::isset(table, string_t("x")))); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); });
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
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/keyed_path.hpp"
#include "scpp/bool_t.hpp"
#include "lang/php/operators/empty/empty.hpp"
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::hash_t<scpp::string_t, scpp::string_t> local_table = ([&]() -> scpp::hash_t<scpp::string_t, scpp::string_t> {
scpp::initializer_buffer<scpp::initializer_entry<scpp::string_t, scpp::string_t>> initializer_0(1);
{
scpp::string_t initializer_key_1 = scpp::string_t("x");
scpp::string_t initializer_value_2 = scpp::string_t("present");
initializer_0.append(scpp::initializer_entry<scpp::string_t, scpp::string_t>{initializer_key_1, initializer_value_2});
}
return scpp::data_initializer<scpp::hash_t<scpp::string_t, scpp::string_t>>::from_key_value(initializer_0.view());
}());
	auto local_present = ([&]() -> scpp::bool_t { const auto& keyed_root_3 = local_table; auto keyed_resolve_3_0 = [&]() -> const scpp::hash_t<scpp::string_t, scpp::string_t>* { return &keyed_root_3; }; if (!scpp::keyed_ready(keyed_resolve_3_0(), false)) { return scpp::bool_t{false}; } scpp::string_t keyed_key_3_0 = scpp::string_t("x"); return scpp::keyed_probe(keyed_resolve_3_0(), keyed_key_3_0, false); }());
	scpp::string_t local_text = scpp::string_t("");
	auto local_blank = scpp::php::empty(local_text);
	([&]() -> void { auto& keyed_root_4 = local_table; auto keyed_resolve_4_0 = [&]() -> const scpp::hash_t<scpp::string_t, scpp::string_t>* { return &keyed_root_4; }; if (!scpp::keyed_ready(keyed_resolve_4_0(), true)) { return; } scpp::string_t keyed_key_4_0 = scpp::string_t("x"); if (!scpp::keyed_lookup(keyed_resolve_4_0(), keyed_key_4_0, true)) { return; } scpp::keyed_erase(keyed_root_4, keyed_key_4_0); }());
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((scpp::bool_t(static_cast<bool>(scpp::bool_t(static_cast<bool>(local_present) && static_cast<bool>(local_blank))) && static_cast<bool>((!([&]() -> scpp::bool_t { const auto& keyed_root_5 = local_table; auto keyed_resolve_5_0 = [&]() -> const scpp::hash_t<scpp::string_t, scpp::string_t>* { return &keyed_root_5; }; if (!scpp::keyed_ready(keyed_resolve_5_0(), false)) { return scpp::bool_t{false}; } scpp::string_t keyed_key_5_0 = scpp::string_t("x"); return scpp::keyed_probe(keyed_resolve_5_0(), keyed_key_5_0, false); }()))))).native_value()) { return static_cast<scpp::int_t<>>(0LL); } return static_cast<scpp::int_t<>>(1LL); }())).native_value());
	return 0;
}
```

