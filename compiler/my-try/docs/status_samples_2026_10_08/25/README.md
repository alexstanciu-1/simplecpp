# 25 — Object-identity keys in typed hashes
Doc Status: supporting

Exact converted source uses explicit shared<Owner>; v2 rejects this before reaching the separate object-key capability gap.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Owner { public int $id = 1; }
$owner = new Owner();
$seen hash<bool, shared<Owner>> = new hash<bool, shared<Owner>>();
$seen[$owner] = true;
return isset($seen[$owner]) ? 0 : 1;
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

class Owner;

class Owner : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> id = static_cast<int_t<> >(1);
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t Owner::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Owner::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/25/main.phs", 0);
	auto owner = create<Owner>();
	hash_t<bool_t, shared_p<Owner>> seen = required_cast<hash_t<bool_t, shared_p<Owner>>>(hash_t<bool_t, shared_p<Owner>>{});
	seen[cast<shared_p<Owner>>(owner)] = static_cast<bool_t>(true);
	return php::ternary_eval([&]() -> decltype(auto) { return php::isset(seen, owner); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); });
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
S2S generation failed: shared<Class> repeats the declaration default storage
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

