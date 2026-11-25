# 27 — Shared PHP-like and JS-like data construction notation
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$php_style hash<int> = ["left" => 1];
$js_style hash<int> = {right: 2};
return $php_style["left"] + $js_style["right"];
```

## S2S generated v1

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
ParseError: syntax error, unexpected token "{"
```

No C++ emitted. [Raw diagnostic](v1.diagnostic.txt).

## S2S generated v2

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

[Complete emitted text](v2.cpp.txt). File separators identify the emitted units.

```cpp
// Readable bundle. File separator comments added by the report renderer.

// === entry.cpp ===
#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/index_read.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::hash_t<scpp::int_t<>, scpp::string_t> local_phpU_style = ([&]() -> scpp::hash_t<scpp::int_t<>, scpp::string_t> {
scpp::initializer_buffer<scpp::initializer_entry<scpp::int_t<>, scpp::string_t>> initializer_0(1);
{
scpp::string_t initializer_key_1 = scpp::string_t("left");
scpp::int_t<> initializer_value_2 = static_cast<scpp::int_t<>>(1LL);
initializer_0.append(scpp::initializer_entry<scpp::int_t<>, scpp::string_t>{initializer_key_1, initializer_value_2});
}
return scpp::data_initializer<scpp::hash_t<scpp::int_t<>, scpp::string_t>>::from_key_value(initializer_0.view());
}());
	scpp::hash_t<scpp::int_t<>, scpp::string_t> local_jsU_style = ([&]() -> scpp::hash_t<scpp::int_t<>, scpp::string_t> {
scpp::initializer_buffer<scpp::initializer_entry<scpp::int_t<>, scpp::string_t>> initializer_3(1);
{
scpp::string_t initializer_key_4 = scpp::string_t("right");
scpp::int_t<> initializer_value_5 = static_cast<scpp::int_t<>>(2LL);
initializer_3.append(scpp::initializer_entry<scpp::int_t<>, scpp::string_t>{initializer_key_4, initializer_value_5});
}
return scpp::data_initializer<scpp::hash_t<scpp::int_t<>, scpp::string_t>>::from_key_value(initializer_3.view());
}());
	return static_cast<int>(((([&]() -> scpp::int_t<> { const auto& index_receiver_6 = local_phpU_style; scpp::string_t index_key_6 = scpp::string_t("left"); return scpp::checked_index_read(index_receiver_6, index_key_6); }()) + ([&]() -> scpp::int_t<> { const auto& index_receiver_7 = local_jsU_style; scpp::string_t index_key_7 = scpp::string_t("right"); return scpp::checked_index_read(index_receiver_7, index_key_7); }()))).native_value());
	return 0;
}
```

