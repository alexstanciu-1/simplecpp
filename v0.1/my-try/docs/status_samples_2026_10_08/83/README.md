# 83 — Process handles, polling/results/cleanup and dt_sleep_ms
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$handle process_handle;
$failure error;
$args vector<string> = ["--version"];
if (!take($handle, $failure, process_start("tool", $args, "", 1000, "."))) { return 1; }
$done bool = false;
take($done, $failure, process_poll($handle));
dt_sleep_ms(1);
$closed bool = false;
take($closed, $failure, process_close($handle));
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/83/main.phs", 0);
	shared_p<process_handle> handle;
	error_t failure;
	vector_t<string_t> args = required_cast<vector_t<string_t>>(vector_t<string_t>{string_t("--version")});
	if (static_cast<bool>((!php::take(handle, failure, process::start(string_t("tool"), args, string_t(""), static_cast<int_t<> >(1000), string_t(".")))))) {
		return static_cast<int_t<> >(1);
	}
	bool_t done = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	php::take(done, failure, process::poll(handle));
	dt::sleep_millis(static_cast<int_t<> >(1));
	bool_t closed = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	php::take(closed, failure, process::close(handle));
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
S2S generation failed: S2S needs one resolved type for process_handle
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

