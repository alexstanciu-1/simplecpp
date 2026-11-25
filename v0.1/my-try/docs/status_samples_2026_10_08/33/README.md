# 33 — Global `Iterator` protocol and typed object foreach
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Counter implements \Iterator {
    private int $position = 0;
    public function current(): int { return $this->position; }
    public function key(): int { return $this->position; }
    public function valid(): bool { return $this->position < 2; }
    public function next(): void { $this->position++; }
    public function rewind(): void { $this->position = 0; }
}
$sum int = 0;
foreach (new Counter() as $value) { $sum += $value; }
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

class Counter;

class Counter : public ::scpp::iterator_protocol, public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
private:
	int_t<> position = static_cast<int_t<> >(0);
public:
	virtual int_t<> current();
	virtual int_t<> key();
	virtual bool_t valid();
	virtual void next();
	virtual void rewind();
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t Counter::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Counter::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

int_t<> Counter::current() {
	SCPP_CALL_DEPTH_GUARD("Counter::current", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/33/main.phs", 3);
	return this->position;
}

int_t<> Counter::key() {
	SCPP_CALL_DEPTH_GUARD("Counter::key", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/33/main.phs", 4);
	return this->position;
}

bool_t Counter::valid() {
	SCPP_CALL_DEPTH_GUARD("Counter::valid", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/33/main.phs", 5);
	return bool_t((this->position < static_cast<int_t<> >(2)));
}

void Counter::next() {
	SCPP_CALL_DEPTH_GUARD("Counter::next", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/33/main.phs", 6);
	(this->position++);
}

void Counter::rewind() {
	SCPP_CALL_DEPTH_GUARD("Counter::rewind", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/33/main.phs", 7);
	this->position = static_cast<int_t<> >(0);
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/33/main.phs", 0);
	int_t<> sum = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __scpp_foreach_source_10 = create<Counter>();
	for (auto __scpp_foreach_entry_10 : foreach_range(__scpp_foreach_source_10)) {
		auto value = __scpp_foreach_entry_10.value_copy();
		sum += value;
	}
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

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
S2S generation failed: S2S needs one resolved type for \Iterator
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).

