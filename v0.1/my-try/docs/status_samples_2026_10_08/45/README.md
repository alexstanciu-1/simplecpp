# 45 — Namespaces, qualified names, imports and scalar source constants
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
namespace Library { const OFFSET = 2; function add(int $value): int { return $value + OFFSET; } }
namespace App { use function Library\add; return add(3); }
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

namespace scpp::Library {

inline const auto OFFSET = static_cast<int_t<> >(2);

int_t<> add(int_t<> value);

}

namespace scpp::App {

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp::Library {
	using namespace ::scpp;

int_t<> add(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("add", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/45/main.phs", 1);
	return (value + OFFSET);
}

}

namespace scpp::App {
	using namespace ::scpp;

	using ::scpp::Library::add;

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/45/main.phs", 0);
	return add(static_cast<int_t<> >(3));
	return 0;
}
}

int main(int __scpp_argc, char** __scpp_argv) {
	try {
		::scpp::php::set_cli_args(__scpp_argc, __scpp_argv);
		return scpp::App::__scpp_main();
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

// === callables/n4c6962726172795c616464.hpp ===
#pragma once
#include "scpp/int_t.hpp"
scpp::int_t<> function_LibraryUqadd(scpp::int_t<> local_value);

// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "callables/n4c6962726172795c616464.hpp"
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
scpp::int_t<> function_LibraryUqadd(scpp::int_t<> local_value)
{
	return (local_value + static_cast<scpp::int_t<>>(2LL));
}


// === entry.cpp ===
#include "scpp/int_t.hpp"
#include "callables/n4c6962726172795c616464.hpp"

int main()
{
	return static_cast<int>((([&]() -> scpp::int_t<> {
	scpp::int_t<> argument_0 = static_cast<scpp::int_t<>>(3LL);
	return function_LibraryUqadd(argument_0);
}())).native_value());
	return 0;
}
```

