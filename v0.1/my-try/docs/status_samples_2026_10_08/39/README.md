# 39 — Explicit read-only reference parameters
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
function first(const vector<int> &$values): int { return $values[0]; }
$values vector<int> = [1, 2];
return first($values);
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

int_t<> first(const vector_t<int_t<>>& values);

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

int_t<> first(const vector_t<int_t<>>& values) {
	SCPP_CALL_DEPTH_GUARD("first", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/39/main.phs", 1);
	return values.at(static_cast<int_t<> >(0));
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/39/main.phs", 0);
	vector_t<int_t<>> values = required_cast<vector_t<int_t<>>>(vector_t<int_t<>>{static_cast<int_t<> >(1), static_cast<int_t<> >(2)});
	return first(values);
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

// === callables/n6669727374.hpp ===
#pragma once
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
scpp::int_t<> function_first(const scpp::vector_t<scpp::int_t<>>& local_values);

// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "callables/n6669727374.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/index_read.hpp"
scpp::int_t<> function_first(const scpp::vector_t<scpp::int_t<>>& local_values)
{
	return ([&]() -> scpp::int_t<> { const auto& index_receiver_0 = local_values; scpp::int_t<> index_key_0 = static_cast<scpp::int_t<>>(0LL); return scpp::checked_index_read(index_receiver_0, index_key_0); }());
}


// === entry.cpp ===
#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "callables/n6669727374.hpp"

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
	return static_cast<int>((([&]() -> scpp::int_t<> {
	const scpp::vector_t<scpp::int_t<>>& argument_3 = local_values;
	return function_first(argument_3);
}())).native_value());
	return 0;
}
```

