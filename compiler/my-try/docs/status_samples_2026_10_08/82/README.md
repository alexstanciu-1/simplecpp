# 82 — Immutable JSON document/node types, parse and typed queries
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$document json_document;
$diagnostic json_parse_error = null;
$failure error;
if (!take($document, $failure, json_document_parse("{}", $diagnostic, 128))) { return 1; }
$node json_node;
if (!take($node, $failure, json_document_root($document))) { return 2; }
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/82/main.phs", 0);
	shared_p<json_document> document;
	shared_p<json_parse_error> diagnostic = null;
	error_t failure;
	if (static_cast<bool>((!php::take(document, failure, json::document_parse(string_t("{}"), diagnostic, static_cast<int_t<> >(128)))))) {
		return static_cast<int_t<> >(1);
	}
	shared_p<json_node> node;
	if (static_cast<bool>((!php::take(node, failure, json::document_root(document))))) {
		return static_cast<int_t<> >(2);
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
S2S generation failed: S2S needs one resolved type for json_document
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

