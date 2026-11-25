# 44 — Multiple sources and selected executable entry
Doc Status: supporting

Two source files. v1 per-file emission is collected without project include/link assembly; v2 discovers both with main.phs selected as entry.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$result int = helper(3);
return $result;
```

[Companion source](helper.phs):

```php
function helper(int $value): int { return $value + 2; }
```

## S2S generated v1

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

**Per-file C++; project include/link assembly not performed.**

[Complete emitted text](v1.cpp.txt). File separators identify the emitted units.

```cpp
// Per-file emission only: project include/link assembly not performed.
// === helper.hpp ===
#pragma once

#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>

namespace scpp {

int_t<> helper(int_t<> value);

}

// === helper.cpp ===
#include "helper.hpp"

namespace scpp {
	using namespace ::scpp;

int_t<> helper(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("helper", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/44/helper.phs", 1);
	return (value + static_cast<int_t<> >(2));
}

}

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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/44/main.phs", 0);
	int_t<> result = required_cast<int_t<>>(helper(static_cast<int_t<> >(3)));
	return result;
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

// === callables/n68656c706572.hpp ===
#pragma once
#include "scpp/int_t.hpp"
scpp::int_t<> function_helper(scpp::int_t<> local_value);

// === units/n6d61696e/n68656c7065722e706873.cpp ===
#include "callables/n68656c706572.hpp"
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
scpp::int_t<> function_helper(scpp::int_t<> local_value)
{
	return (local_value + static_cast<scpp::int_t<>>(2LL));
}


// === entry.cpp ===
#include "scpp/int_t.hpp"
#include "callables/n68656c706572.hpp"

int main()
{
	scpp::int_t<> local_result = ([&]() -> scpp::int_t<> {
	scpp::int_t<> argument_0 = static_cast<scpp::int_t<>>(3LL);
	return function_helper(argument_0);
}());
	return static_cast<int>((local_result).native_value());
	return 0;
}
```

