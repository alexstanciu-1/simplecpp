# 10 — Braced `if`/`elseif`/`else`, `while`, `do`, `for`; ordinary `break`/`continue`
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
$sum int = 0;
for ($i = 0; $i < 4; $i++) {
    if ($i === 1) { continue; }
    elseif ($i === 3) { break; }
    else { $sum += $i; }
}
while ($sum < 4) { $sum++; }
do { $sum--; } while ($sum > 3);
return $sum;
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
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/10/main.phs", 0);
	int_t<> sum = required_cast<int_t<>>(static_cast<int_t<> >(0));
	for (auto i = static_cast<int_t<> >(0); static_cast<bool>((i < static_cast<int_t<> >(4))); (i++)) {
		if (static_cast<bool>(php::identical(i, static_cast<int_t<> >(1)))) {
			continue;
		}
		else if (static_cast<bool>(php::identical(i, static_cast<int_t<> >(3)))) {
			break;
		}
		else {
			sum += i;
		}
	}
	while (static_cast<bool>((sum < static_cast<int_t<> >(4)))) {
		(sum++);
	}
	do {
		(sum--);
	} while (static_cast<bool>((sum > static_cast<int_t<> >(3))));
	return sum;
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
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/operators/identity/strict_identity.hpp"

int main()
{
	scpp::int_t<> local_sum = static_cast<scpp::int_t<>>(0LL);
{
	auto local_i = static_cast<scpp::int_t<>>(0LL);
for (; ((local_i < static_cast<scpp::int_t<>>(4LL))).native_value(); (void)(static_cast<scpp::int_t<>>(local_i++))) {
if ((scpp::php::identical(local_i, static_cast<scpp::int_t<>>(1LL))).native_value()) {
continue;
}
else if ((scpp::php::identical(local_i, static_cast<scpp::int_t<>>(3LL))).native_value()) {
break;
}
else {
	([&]() -> scpp::int_t<> { auto& target_0 = local_sum; target_0 = (target_0 + local_i); return target_0; }());
}
}
}
while (((local_sum < static_cast<scpp::int_t<>>(4LL))).native_value()) {
	static_cast<scpp::int_t<>>(local_sum++);
}
do {
	static_cast<scpp::int_t<>>(local_sum--);
}
while (((local_sum > static_cast<scpp::int_t<>>(3LL))).native_value());
	return static_cast<int>((local_sum).native_value());
	return 0;
}
```

