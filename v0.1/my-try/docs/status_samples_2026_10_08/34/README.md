# 34 — Whole-container calls, copies and references
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
function append(vector<int> &$values): void { $values[] = 9; }
$original vector<int> = [1];
$copy = $original;
append($copy);
$first = $original[0];
$last = $copy[1];
return $first + $last;
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

void append(vector_t<int_t<>>& values);

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

void append(vector_t<int_t<>>& values) {
	SCPP_CALL_DEPTH_GUARD("append", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/34/main.phs", 1);
	(void) values.push_back(static_cast<int_t<> >(9));
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/34/main.phs", 0);
	vector_t<int_t<>> original = required_cast<vector_t<int_t<>>>(vector_t<int_t<>>{static_cast<int_t<> >(1)});
	auto copy = original;
	append(copy);
	auto first = original.at(static_cast<int_t<> >(0));
	auto last = copy.at(static_cast<int_t<> >(1));
	return (first + last);
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

// === callables/n617070656e64.hpp ===
#pragma once
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
void function_append(scpp::vector_t<scpp::int_t<>>& local_values);

// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "callables/n617070656e64.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/index_write.hpp"
void function_append(scpp::vector_t<scpp::int_t<>>& local_values)
{
	{ auto& write_receiver_0 = local_values; scpp::int_t<> write_value_0 = static_cast<scpp::int_t<>>(9LL); scpp::index_append(write_receiver_0, write_value_0); }
}


// === entry.cpp ===
#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "callables/n617070656e64.hpp"
#include "scpp/index_read.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::vector_t<scpp::int_t<>> local_original = ([&]() -> scpp::vector_t<scpp::int_t<>> {
scpp::initializer_buffer<scpp::int_t<>> initializer_0(1);
{
scpp::int_t<> initializer_value_1 = static_cast<scpp::int_t<>>(1LL);
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<scpp::int_t<>>>::from_list(initializer_0.view());
}());
	auto local_copy = local_original;
	([&]() -> void {
	scpp::vector_t<scpp::int_t<>>& argument_2 = local_copy;
	return function_append(argument_2);
}());
	auto local_first = ([&]() -> scpp::int_t<> { const auto& index_receiver_3 = local_original; scpp::int_t<> index_key_3 = static_cast<scpp::int_t<>>(0LL); return scpp::checked_index_read(index_receiver_3, index_key_3); }());
	auto local_last = ([&]() -> scpp::int_t<> { const auto& index_receiver_4 = local_copy; scpp::int_t<> index_key_4 = static_cast<scpp::int_t<>>(1LL); return scpp::checked_index_read(index_receiver_4, index_key_4); }());
	return static_cast<int>(((local_first + local_last)).native_value());
	return 0;
}
```

