# 80 — File-lock handles and acquire/release/transfer
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$handle file_lock_handle;
$acquired bool = false;
$failure error;
if (!take($acquired, $failure, fs_lock_try($handle, "sample.lock", false))) { return 1; }
if ($acquired) { $released bool = false; take($released, $failure, fs_lock_release($handle)); }
return 0;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/80/main.phs", 0);
	shared_p<file_lock_handle> handle;
	bool_t acquired = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	error_t failure;
	if (static_cast<bool>((!php::take(acquired, failure, fs::lock_try(handle, string_t("sample.lock"), static_cast<bool_t>(false)))))) {
		return static_cast<int_t<> >(1);
	}
	if (static_cast<bool>(php::condition_truthy(acquired))) {
		bool_t released = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
		php::take(released, failure, fs::lock_release(handle));
	}
	return static_cast<int_t<> >(0);
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

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
S2S generation failed: S2S needs one resolved type for file_lock_handle
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

